<?php

namespace App\Services;

use App\Models\ContactVerificationChallengeModel;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\Response;
use Ramsey\Uuid\Uuid;

class ContactVerificationService
{
    public const CODE_TTL_MINUTES = 10;
    public const GRANT_TTL_MINUTES = 30;
    public const RESEND_SECONDS = 60;
    public const MAX_ATTEMPTS = 5;
    public const MAX_SENDS_PER_HOUR = 5;

    protected ContactVerificationChallengeModel $model;
    protected EmailTemplateService $templates;
    protected BrevoEmailService $email;
    protected BrevoSmsService $sms;

    public function __construct()
    {
        $this->model = new ContactVerificationChallengeModel();
        $this->templates = new EmailTemplateService();
        $this->email = new BrevoEmailService();
        $this->sms = new BrevoSmsService();
    }

    public function request(array $data): array
    {
        [$sessionId, $method, $email, $phone] = $this->validateContact($data);
        // Keep the public-flow table bounded without needing a scheduled job.
        $this->model->where('created_at <', date('Y-m-d H:i:s', time() - 86400))->delete();
        $recent = $this->model->where('session_id', $sessionId)
            ->where('created_at >=', date('Y-m-d H:i:s', time() - 3600))
            ->orderBy('created_at', 'DESC')->findAll(self::MAX_SENDS_PER_HOUR + 1);

        if (count($recent) >= self::MAX_SENDS_PER_HOUR) {
            throw new HTTPException('Too many verification codes requested. Please try again later.', 429);
        }
        if ($recent && strtotime($recent[0]->created_at) > time() - self::RESEND_SECONDS) {
            throw new HTTPException('Please wait before requesting another code.', 429);
        }

        $code = (string) random_int(100000, 999999);
        $id = Uuid::uuid4()->toString();
        $masked = $method === 'email' ? $this->maskEmail($email) : $this->maskPhone($phone);
        $now = date('Y-m-d H:i:s');
        $this->model->insert([
            'id' => $id, 'session_id' => $sessionId, 'method' => $method,
            'email' => $email, 'phone' => $phone, 'destination_masked' => $masked,
            'code_hash' => password_hash($code, PASSWORD_DEFAULT),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+' . self::CODE_TTL_MINUTES . ' minutes')),
            'created_at' => $now,
        ]);

        try {
            if ($method === 'email') {
                $rendered = $this->templates->render('contact_verification', [
                    'customer_name' => trim((string) ($data['first_name'] ?? '')) ?: 'there',
                    'verification_code' => $code,
                    'expires_minutes' => (string) self::CODE_TTL_MINUTES,
                ]);
                $this->email->sendEmail($email, $rendered['subject'], $rendered['body']);
            } else {
                $this->sms->send($phone, "Jam with Jamie: Your verification code is {$code}. It expires in 10 minutes. Do not share it.");
            }
        } catch (\Throwable $e) {
            $this->model->delete($id);
            log_message('error', 'Contact verification delivery failed: ' . $e->getMessage());
            throw new HTTPException('We could not send the verification code. Please try another method.', Response::HTTP_BAD_GATEWAY);
        }

        return ['challenge_id' => $id, 'method' => $method, 'destination' => $masked,
            'expires_in' => self::CODE_TTL_MINUTES * 60, 'resend_in' => self::RESEND_SECONDS];
    }

    public function verify(array $data): array
    {
        $id = trim((string) ($data['challenge_id'] ?? ''));
        $code = trim((string) ($data['code'] ?? ''));
        if ($id === '' || !preg_match('/^\d{6}$/', $code)) {
            throw new HTTPException('Enter the 6-digit verification code.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $challenge = $this->model->find($id);
        if (!$challenge || $challenge->consumed_at || $challenge->verified_at) {
            throw new HTTPException('This verification code is no longer valid.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if (strtotime($challenge->expires_at) < time()) {
            throw new HTTPException('This verification code has expired. Request a new one.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if ((int) $challenge->attempts >= self::MAX_ATTEMPTS) {
            throw new HTTPException('Too many incorrect attempts. Request a new code.', 429);
        }
        if (!password_verify($code, $challenge->code_hash)) {
            $attempts = (int) $challenge->attempts + 1;
            $this->model->update($id, ['attempts' => $attempts]);
            throw new HTTPException('That code is incorrect. Please try again.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $token = bin2hex(random_bytes(32));
        $this->model->update($id, [
            'verification_hash' => hash('sha256', $token),
            'verified_at' => date('Y-m-d H:i:s'),
        ]);
        return ['verification_token' => $token, 'verified' => true];
    }

    public function assertValidForReservation(array $formData): object
    {
        $token = trim((string) ($formData['verification_token'] ?? ''));
        $sessionId = trim((string) ($formData['session_id'] ?? ''));
        $customer = is_array($formData['customer'] ?? null) ? $formData['customer'] : [];
        $challenge = $token === '' ? null : $this->model
            ->where('verification_hash', hash('sha256', $token))->first();
        $valid = $challenge && !$challenge->consumed_at && $challenge->verified_at
            && hash_equals($challenge->session_id, $sessionId)
            && hash_equals($challenge->email, $this->normalizeEmail((string) ($customer['email'] ?? '')))
            && hash_equals($challenge->phone, $this->normalizePhone((string) ($customer['phone'] ?? '')))
            && strtotime($challenge->verified_at) >= time() - self::GRANT_TTL_MINUTES * 60;
        if (!$valid) {
            throw new HTTPException('Please verify your email or phone number before submitting the reservation.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        return $challenge;
    }

    public function consume(object $challenge): void
    {
        $this->model->update($challenge->id, ['consumed_at' => date('Y-m-d H:i:s')]);
    }

    private function validateContact(array $data): array
    {
        $sessionId = trim((string) ($data['session_id'] ?? ''));
        $method = strtolower(trim((string) ($data['method'] ?? '')));
        $email = $this->normalizeEmail((string) ($data['email'] ?? ''));
        $phone = $this->normalizePhone((string) ($data['phone'] ?? ''));
        if ($method === 'sms') {
            throw new HTTPException('SMS verification is not available yet. Please use email.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if (!preg_match('/^[0-9a-f-]{36}$/i', $sessionId) || !in_array($method, ['email', 'sms'], true)
            || filter_var($email, FILTER_VALIDATE_EMAIL) === false || !preg_match('/^\+\d{7,15}$/', $phone)) {
            throw new HTTPException('Please provide a valid email address and mobile number.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if ($method === 'sms' && !preg_match('/^\+[1-9]\d{7,14}$/', $phone)) {
            throw new HTTPException('Enter a mobile number with country code to receive an SMS.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        return [$sessionId, $method, $email, $phone];
    }

    private function normalizeEmail(string $email): string { return strtolower(trim($email)); }
    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (strlen($digits) === 10) $digits = '1' . $digits;
        return '+' . $digits;
    }
    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2);
        return substr($local, 0, 1) . str_repeat('*', max(2, strlen($local) - 1)) . '@' . $domain;
    }
    private function maskPhone(string $phone): string { return str_repeat('•', max(0, strlen($phone) - 4)) . substr($phone, -4); }
}

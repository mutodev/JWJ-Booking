<?php

namespace App\Services;

use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Configuration;
use Brevo\Client\Model\SendSmtpEmail;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use GuzzleHttp\Client;

class BrevoEmailService
{
    /**
     * Maximum number of CC recipients accepted per send.
     */
    public const MAX_CC = 10;

    protected $apiInstance;

    /**
     * Global default CC list, read once per instance from getenv('email.defaultCc').
     * A comma/semicolon separated list. Empty when the variable is not set.
     *
     * @var string[]
     */
    protected array $defaultCc = [];

    public function __construct()
    {
        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', getenv('brevo.apiKey'));

        $this->apiInstance = new TransactionalEmailsApi(
            new Client([
                'verify' => false
            ]),
            $config
        );

        $this->defaultCc = $this->parseConfiguredCc((string) getenv('email.defaultCc'));
    }

    /**
     * Enviar correo electrónico
     *
     * @param string $to
     * @param string $subject
     * @param string $htmlContent
     * @param string[] $cc   Extra CC recipients for this send. Merged with the global default CC.
     * @param string[] $bcc  Extra BCC recipients for this send. Not exposed in the UI yet.
     * @return \Brevo\Client\Model\CreateSmtpEmail
     */
    public function sendEmail($to, $subject, $htmlContent, array $cc = [], array $bcc = [])
    {
        $payload = [
            'subject' => $subject,
            'sender' => ['name' => getenv("brevo.fromName"), 'email' => getenv('brevo.fromEmail')],
            'to' => [['email' => $to]],
            'htmlContent' => $htmlContent
        ];

        $resolvedCc = $this->resolveCc($cc, (string) $to);
        if (!empty($resolvedCc)) {
            $payload['cc'] = array_map(static fn (string $email): array => ['email' => $email], $resolvedCc);
        }

        $resolvedBcc = $this->normalizeRecipients($bcc, (string) $to);
        if (!empty($resolvedBcc)) {
            $payload['bcc'] = array_map(static fn (string $email): array => ['email' => $email], $resolvedBcc);
        }

        $sendSmtpEmail = new SendSmtpEmail($payload);

        return $this->apiInstance->sendTransacEmail($sendSmtpEmail);
    }

    /**
     * Final CC list actually applied to a send: the global default CC merged with
     * the per-send list, normalized and deduplicated. Callers use this to record
     * exactly which addresses were copied.
     *
     * @param string[] $cc
     * @return string[]
     */
    public function resolveCc(array $cc, string $to): array
    {
        return $this->normalizeRecipients(array_merge($this->defaultCc, $cc), $to);
    }

    /**
     * Pure normalization of a recipient list:
     * - drop non-string entries
     * - trim and lowercase
     * - reject anything containing CR/LF (email header injection)
     * - keep only addresses that pass FILTER_VALIDATE_EMAIL
     * - drop the primary recipient so it is never duplicated
     * - deduplicate
     * - cap at self::MAX_CC
     *
     * Invalid entries are discarded silently (with a log line), never thrown.
     * Explicit client input is validated earlier by assertValidClientCc().
     *
     * @param array $cc
     * @param string $to
     * @return string[]
     */
    public function normalizeRecipients(array $cc, string $to): array
    {
        $primary = strtolower(trim($to));
        $seen = [];
        $result = [];

        foreach ($cc as $address) {
            if (!is_string($address)) {
                continue;
            }

            $clean = strtolower(trim($address));

            if ($clean === '' || $clean === $primary) {
                continue;
            }

            if (strpbrk($clean, "\r\n") !== false) {
                log_message('warning', 'BrevoEmailService: rejected CC address with line breaks');
                continue;
            }

            if (filter_var($clean, FILTER_VALIDATE_EMAIL) === false) {
                log_message('warning', 'BrevoEmailService: discarded invalid CC address "' . $clean . '"');
                continue;
            }

            if (isset($seen[$clean])) {
                continue;
            }

            $seen[$clean] = true;
            $result[] = $clean;
        }

        return array_slice($result, 0, self::MAX_CC);
    }

    /**
     * Validate a CC list explicitly supplied by an admin from the client.
     * Unlike the default config CC, bad client input is rejected with a 400.
     *
     * @param mixed $cc
     * @return string[] Cleaned list of trimmed address strings (still normalized at send time).
     */
    public static function assertValidClientCc($cc): array
    {
        if ($cc === null || $cc === '' || $cc === []) {
            return [];
        }

        if (!is_array($cc)) {
            throw new HTTPException('CC must be a list of email addresses', 400);
        }

        $clean = [];
        foreach ($cc as $entry) {
            if (!is_string($entry)) {
                throw new HTTPException('CC must be a list of email addresses', 400);
            }

            $trimmed = trim($entry);
            if ($trimmed === '') {
                continue;
            }

            if (strpbrk($trimmed, "\r\n") !== false || filter_var($trimmed, FILTER_VALIDATE_EMAIL) === false) {
                throw new HTTPException('Invalid CC email address: ' . $trimmed, 400);
            }

            $clean[] = $trimmed;
        }

        if (count($clean) > self::MAX_CC) {
            throw new HTTPException('A maximum of ' . self::MAX_CC . ' CC recipients is allowed', 400);
        }

        return $clean;
    }

    /**
     * Parse the comma/semicolon separated getenv('email.defaultCc') string into
     * a list of trimmed non-empty tokens. Validation happens later in
     * normalizeRecipients() so a misconfigured value never breaks a send.
     *
     * @return string[]
     */
    private function parseConfiguredCc(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }

        $parts = preg_split('/[,;]+/', $raw) ?: [];
        $parts = array_map('trim', $parts);

        return array_values(array_filter($parts, static fn (string $v): bool => $v !== ''));
    }
}

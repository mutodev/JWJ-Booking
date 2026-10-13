<?php

namespace App\Services;

use App\Repositories\UserRepository;
use CodeIgniter\Encryption\Exceptions\EncryptionException;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\Response;
use Firebase\JWT\JWT;

class MfaService
{
    private const CHALLENGE_TTL_SECONDS = 600;
    private const ADMIN_ROLE_ID = 'a1b2c3d4-e5f6-7890-abcd-ef1234567890';

    public function __construct(
        private ?UserRepository $userRepository = null,
        private ?TotpService $totpService = null
    ) {
        $this->userRepository ??= new UserRepository();
        $this->totpService ??= new TotpService();
    }

    public function createLoginChallenge(array $user): array
    {
        $enabled = (bool) ($user['mfa_enabled'] ?? false);
        $purpose = $enabled ? 'verify' : 'setup';
        $result = [
            'mfa_required' => true,
            'mfa_setup_required' => !$enabled,
            'challenge_token' => $this->issueChallengeToken((string) $user['id'], $purpose),
        ];

        if (!$enabled) {
            $secret = $this->ensureEnrollmentSecret((string) $user['id']);
            $result['secret'] = $secret;
            $result['provisioning_uri'] = $this->totpService->provisioningUri($secret, (string) $user['email']);
        }

        return $result;
    }

    /** @return array{user_id: string, recovery_codes: string[]} */
    public function verifyChallenge(string $challengeToken, string $code): array
    {
        $claims = verify_token($challengeToken);
        if (($claims['token_type'] ?? null) !== 'mfa_challenge') {
            throw new HTTPException('Invalid MFA challenge', Response::HTTP_UNAUTHORIZED);
        }

        $userId = (string) ($claims['sub'] ?? '');
        $purpose = (string) ($claims['purpose'] ?? '');
        $challengeId = (string) ($claims['jti'] ?? '');
        if ($userId === '' || $challengeId === '' || !in_array($purpose, ['setup', 'verify'], true)) {
            throw new HTTPException('Invalid MFA challenge', Response::HTTP_UNAUTHORIZED);
        }

        $challengeCacheKey = 'mfa_challenge_used_' . hash('sha256', $challengeId);
        if (service('cache')->get($challengeCacheKey)) {
            throw new HTTPException('MFA challenge has already been used', Response::HTTP_UNAUTHORIZED);
        }

        $user = $this->userRepository->getUserForAuthenticationById($userId);
        if (!$user || !(bool) $user->is_active || !(bool) $user->state) {
            throw new HTTPException('Invalid MFA challenge', Response::HTTP_UNAUTHORIZED);
        }

        $secret = $this->decryptSecret((string) ($user->mfa_secret ?? ''));
        $recoveryCodes = [];
        $lastStep = $user->mfa_last_used_step === null ? null : (int) $user->mfa_last_used_step;
        $acceptedStep = $this->totpService->verifyCode($secret, $code, $lastStep);

        if ($acceptedStep !== null) {
            if ($purpose === 'setup' && !(bool) $user->mfa_enabled) {
                $recoveryCodes = $this->totpService->generateRecoveryCodes();
                $updated = $this->userRepository->claimMfaStep($userId, $acceptedStep, [
                    'mfa_enabled' => true,
                    'mfa_confirmed_at' => date('Y-m-d H:i:s'),
                    'mfa_recovery_codes' => json_encode(
                        $this->totpService->hashRecoveryCodes($recoveryCodes),
                        JSON_THROW_ON_ERROR
                    ),
                ]);
            } else {
                $updated = $this->userRepository->claimMfaStep($userId, $acceptedStep);
            }

            if (!$updated) {
                throw new HTTPException('Authentication code has already been used', Response::HTTP_UNAUTHORIZED);
            }

            service('cache')->save($challengeCacheKey, true, self::CHALLENGE_TTL_SECONDS);
            return ['user_id' => $userId, 'recovery_codes' => $recoveryCodes];
        }

        if ((bool) $user->mfa_enabled && $this->consumeRecoveryCode($user, $code)) {
            service('cache')->save($challengeCacheKey, true, self::CHALLENGE_TTL_SECONDS);
            return ['user_id' => $userId, 'recovery_codes' => []];
        }

        throw new HTTPException('Invalid authentication code', Response::HTTP_UNAUTHORIZED);
    }

    public function resetForUser(string $targetUserId, object $actor): void
    {
        if ((string) ($actor->role_id ?? '') !== self::ADMIN_ROLE_ID) {
            throw new HTTPException('Only administrators can reset MFA', Response::HTTP_FORBIDDEN);
        }

        $target = $this->userRepository->getUserForAuthenticationById($targetUserId);
        if (!$target) {
            throw new HTTPException('User not found', Response::HTTP_NOT_FOUND);
        }

        if (!$this->userRepository->resetMfa($targetUserId)) {
            throw new HTTPException('Unable to reset MFA', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function ensureEnrollmentSecret(string $userId): string
    {
        $user = $this->userRepository->getUserForAuthenticationById($userId);
        if (!$user) {
            throw new HTTPException('User not found', Response::HTTP_NOT_FOUND);
        }

        if (!empty($user->mfa_secret)) {
            return $this->decryptSecret((string) $user->mfa_secret);
        }

        $secret = $this->totpService->generateSecret();
        if (!$this->userRepository->updateMfa($userId, ['mfa_secret' => $this->encryptSecret($secret)])) {
            throw new HTTPException('Unable to initialize MFA', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        return $secret;
    }

    private function consumeRecoveryCode(object $user, string $code): bool
    {
        $storedJson = (string) ($user->mfa_recovery_codes ?? '[]');
        $hashes = json_decode($storedJson, true);
        if (!is_array($hashes)) {
            return false;
        }

        $result = $this->totpService->consumeRecoveryCode($code, $hashes);
        if (!$result['matched']) {
            return false;
        }

        $newJson = json_encode($result['remaining'], JSON_THROW_ON_ERROR);
        return $this->userRepository->replaceRecoveryCodes((string) $user->id, $storedJson, $newJson);
    }

    private function issueChallengeToken(string $userId, string $purpose): string
    {
        $now = time();
        return JWT::encode([
            'sub' => $userId,
            'purpose' => $purpose,
            'token_type' => 'mfa_challenge',
            'jti' => bin2hex(random_bytes(16)),
            'iat' => $now,
            'exp' => $now + self::CHALLENGE_TTL_SECONDS,
        ], (string) env('encryption.key'), 'HS256');
    }

    private function encryptSecret(string $secret): string
    {
        return base64_encode(service('encrypter')->encrypt($secret));
    }

    private function decryptSecret(string $encrypted): string
    {
        $raw = base64_decode($encrypted, true);
        if ($raw === false || $raw === '') {
            throw new HTTPException('MFA is not configured correctly', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            return (string) service('encrypter')->decrypt($raw);
        } catch (EncryptionException|\Throwable $e) {
            throw new HTTPException('MFA is not configured correctly', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

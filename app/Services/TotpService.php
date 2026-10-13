<?php

namespace App\Services;

use OTPHP\TOTP;
use ParagonIE\ConstantTime\Base32;

class TotpService
{
    public const PERIOD = 30;
    private const RECOVERY_CODE_COUNT = 8;

    public function generateSecret(): string
    {
        return rtrim(Base32::encodeUpper(random_bytes(20)), '=');
    }

    public function provisioningUri(string $secret, string $email): string
    {
        $totp = $this->totp($secret);
        $totp->setLabel($email);
        $totp->setIssuer('Jam with Jamie');

        return $totp->getProvisioningUri();
    }

    /**
     * Returns the accepted RFC 6238 time step, or null when invalid/replayed.
     * One adjacent step is accepted in either direction for small clock drift.
     */
    public function verifyCode(string $secret, string $code, ?int $lastUsedStep = null, ?int $now = null): ?int
    {
        $code = preg_replace('/\D/', '', $code) ?? '';
        if (!preg_match('/^\d{6}$/', $code)) {
            return null;
        }

        $now ??= time();
        $currentStep = intdiv($now, self::PERIOD);
        $totp = $this->totp($secret);

        foreach ([-1, 0, 1] as $offset) {
            $step = $currentStep + $offset;
            if ($lastUsedStep !== null && $step <= $lastUsedStep) {
                continue;
            }

            if ($totp->verify($code, $step * self::PERIOD)) {
                return $step;
            }
        }

        return null;
    }

    /** @return string[] */
    public function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < self::RECOVERY_CODE_COUNT; $i++) {
            $raw = strtoupper(bin2hex(random_bytes(5)));
            $codes[] = substr($raw, 0, 5) . '-' . substr($raw, 5);
        }

        return $codes;
    }

    /** @param string[] $codes */
    public function hashRecoveryCodes(array $codes): array
    {
        return array_map(
            static fn (string $code): string => password_hash(self::normalizeRecoveryCode($code), PASSWORD_DEFAULT),
            $codes
        );
    }

    /**
     * @param string[] $hashes
     * @return array{matched: bool, remaining: string[]}
     */
    public function consumeRecoveryCode(string $code, array $hashes): array
    {
        $normalized = self::normalizeRecoveryCode($code);
        if (!preg_match('/^[A-F0-9]{10}$/', $normalized)) {
            return ['matched' => false, 'remaining' => $hashes];
        }

        foreach ($hashes as $index => $hash) {
            if (is_string($hash) && password_verify($normalized, $hash)) {
                unset($hashes[$index]);
                return ['matched' => true, 'remaining' => array_values($hashes)];
            }
        }

        return ['matched' => false, 'remaining' => $hashes];
    }

    private function totp(string $secret): TOTP
    {
        /** @var TOTP $totp */
        $totp = TOTP::create($secret, self::PERIOD, 'sha1', 6);
        return $totp;
    }

    private static function normalizeRecoveryCode(string $code): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', trim($code)) ?? '');
    }
}

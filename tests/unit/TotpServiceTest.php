<?php

namespace Tests\Unit;

use App\Services\TotpService;
use CodeIgniter\Test\CIUnitTestCase;
use OTPHP\TOTP;

class TotpServiceTest extends CIUnitTestCase
{
    public function testProvisioningAndVerificationAreAuthenticatorCompatible(): void
    {
        $service = new TotpService();
        $secret = $service->generateSecret();
        $now = 1_800_000_015;

        $this->assertMatchesRegularExpression('/^[A-Z2-7]{32}$/', $secret);

        $uri = $service->provisioningUri($secret, 'admin@example.com');
        $this->assertStringStartsWith('otpauth://totp/', $uri);
        $this->assertStringContainsString('issuer=Jam%20with%20Jamie', $uri);
        $this->assertStringContainsString('admin%40example.com', $uri);

        $totp = TOTP::create($secret, TotpService::PERIOD, 'sha1', 6);
        $code = $totp->at($now);
        $step = intdiv($now, TotpService::PERIOD);

        $this->assertSame($step, $service->verifyCode($secret, $code, null, $now));
        $this->assertNull($service->verifyCode($secret, $code, $step, $now));
    }

    public function testVerificationAllowsOneAdjacentClockStep(): void
    {
        $service = new TotpService();
        $secret = $service->generateSecret();
        $now = 1_800_000_015;
        $totp = TOTP::create($secret, TotpService::PERIOD, 'sha1', 6);

        $previousCode = $totp->at($now - TotpService::PERIOD);
        $nextCode = $totp->at($now + TotpService::PERIOD);

        $this->assertSame(intdiv($now, TotpService::PERIOD) - 1, $service->verifyCode($secret, $previousCode, null, $now));
        $this->assertSame(intdiv($now, TotpService::PERIOD) + 1, $service->verifyCode($secret, $nextCode, null, $now));
    }

    public function testRecoveryCodesAreSingleUse(): void
    {
        $service = new TotpService();
        $codes = $service->generateRecoveryCodes();
        $hashes = $service->hashRecoveryCodes($codes);

        $this->assertCount(8, $codes);
        $this->assertCount(8, array_unique($codes));
        $this->assertMatchesRegularExpression('/^[A-F0-9]{5}-[A-F0-9]{5}$/', $codes[0]);

        $firstUse = $service->consumeRecoveryCode(strtolower($codes[0]), $hashes);
        $this->assertTrue($firstUse['matched']);
        $this->assertCount(7, $firstUse['remaining']);

        $secondUse = $service->consumeRecoveryCode($codes[0], $firstUse['remaining']);
        $this->assertFalse($secondUse['matched']);
    }
}

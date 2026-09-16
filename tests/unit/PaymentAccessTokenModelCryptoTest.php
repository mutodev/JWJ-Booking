<?php

namespace Tests\Unit;

use App\Models\PaymentAccessTokenModel;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * PaymentAccessTokenModel::encodeToken()/decodeToken() — the token is not a
 * random opaque string, it's an encrypted, self-contained credential (see the
 * class docblock). This is pure crypto (CodeIgniter's Encryption service,
 * `encryption.key`) with no DB involved, so it's tested directly against the
 * real model — encodeToken() is invoked via Reflection since it's private and
 * only ever called from issueFor() (which does need a DB).
 *
 * @internal
 */
final class PaymentAccessTokenModelCryptoTest extends CIUnitTestCase
{
    private PaymentAccessTokenModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new PaymentAccessTokenModel();
    }

    private function encode(string $targetType, string $targetId): string
    {
        $method = new \ReflectionMethod(PaymentAccessTokenModel::class, 'encodeToken');
        $method->setAccessible(true);

        return $method->invoke($this->model, $targetType, $targetId);
    }

    public function testRoundTripRecoversTheOriginalPayload(): void
    {
        $token = $this->encode('reservation', 'res-123');

        $this->assertSame(
            ['target_type' => 'reservation', 'target_id' => 'res-123'],
            $this->model->decodeToken($token)
        );
    }

    public function testRoundTripWorksForCustomPaymentLinkToo(): void
    {
        $token = $this->encode('custom_payment_link', 'link-abc');

        $this->assertSame(
            ['target_type' => 'custom_payment_link', 'target_id' => 'link-abc'],
            $this->model->decodeToken($token)
        );
    }

    public function testTokenIsUrlSafe(): void
    {
        $token = $this->encode('reservation', 'res-123');

        // No characters that would need percent-encoding in a path segment.
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9\-_]+$/', $token);
    }

    public function testSameTargetProducesADifferentTokenEachTime(): void
    {
        $first  = $this->encode('reservation', 'res-123');
        $second = $this->encode('reservation', 'res-123');

        // AES-CTR uses a fresh random IV per call — same plaintext, different
        // ciphertext. This is what gives every issueFor() call a genuinely
        // new token without a separate random nonce.
        $this->assertNotSame($first, $second);
        $this->assertSame(
            $this->model->decodeToken($first),
            $this->model->decodeToken($second)
        );
    }

    public function testGarbageInputDecodesToNullInsteadOfThrowing(): void
    {
        $this->assertNull($this->model->decodeToken('not-a-real-token'));
        $this->assertNull($this->model->decodeToken(''));
        $this->assertNull($this->model->decodeToken(str_repeat('a', 500)));
    }

    public function testTamperedTokenFailsAuthenticationAndDecodesToNull(): void
    {
        $token = $this->encode('reservation', 'res-123');

        // Flip one character in the middle of the HMAC portion — authentication
        // must reject this, not silently decrypt to garbage or a different payload.
        $pos        = (int) floor(strlen($token) / 2);
        $middleChar = $token[$pos];
        $flippedTo  = $middleChar === 'a' ? 'b' : 'a';
        $tampered   = substr($token, 0, $pos) . $flippedTo . substr($token, $pos + 1);

        $this->assertNull($this->model->decodeToken($tampered));
    }
}

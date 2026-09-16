<?php

namespace App\Models;

use CodeIgniter\Encryption\Exceptions\EncryptionException;
use CodeIgniter\Model;

/**
 * Gateway tokens standing in for the raw Stripe Checkout URL in emails.
 *
 * The token is not just an unguessable random string: it's an encrypted,
 * self-contained credential (see encodeToken()/decodeToken()) using
 * CodeIgniter's Encryption service (AES-256-CTR + HMAC, `encryption.key` —
 * the same key already backing generate_token()/verify_token() elsewhere in
 * this app). Decrypting it is the authoritative way to know what it grants
 * access to; PaymentAccessService::redeem() never trusts the plaintext
 * target_type/target_id columns for that decision — those columns exist only
 * so findActiveFor() can look up "the current token for this target" without
 * decrypting every row.
 *
 * One active token per (target_type, target_id) — issuing a new one deletes
 * the previous row, giving every resend a fresh expiry (sliding renewal).
 *
 * NOTE: rotating `encryption.key` invalidates every outstanding token (they
 * fail to decrypt under the new key) — a resend mints a fresh one, but a
 * customer mid-flow on an old link would see it as "expired".
 */
class PaymentAccessTokenModel extends Model
{
    protected $table            = 'payment_access_tokens';
    protected $primaryKey       = 'token';
    protected $useAutoIncrement = false;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'token',
        'target_type',
        'target_id',
        'expires_at',
        'created_at',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = false;

    /** @var \CodeIgniter\Encryption\EncrypterInterface|null Lazy — see encrypter(). */
    protected $encrypter = null;

    protected function encrypter()
    {
        if ($this->encrypter === null) {
            $this->encrypter = service('encrypter');
        }

        return $this->encrypter;
    }

    /**
     * Issue a fresh token for the target, invalidating any token already
     * outstanding for it (sliding renewal — every resend gets a new window).
     */
    public function issueFor(string $targetType, string $targetId, int $days = 6): object
    {
        $this->where('target_type', $targetType)
            ->where('target_id', $targetId)
            ->delete();

        $now       = date('Y-m-d H:i:s');
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$days} days"));
        $token     = $this->encodeToken($targetType, $targetId);

        $this->insert([
            'token'       => $token,
            'target_type' => $targetType,
            'target_id'   => $targetId,
            'expires_at'  => $expiresAt,
            'created_at'  => $now,
        ]);

        return (object) [
            'token'       => $token,
            'target_type' => $targetType,
            'target_id'   => $targetId,
            'expires_at'  => $expiresAt,
        ];
    }

    /**
     * Find a token that exists and has not yet expired. This confirms the
     * token is still active (not superseded by a resend, not past its 6-day
     * window) — it does NOT authorize what the token points to. Use
     * decodeToken() for that.
     */
    public function findValid(string $token): ?object
    {
        return $this->where('token', $token)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->first();
    }

    /**
     * The currently active (non-expired) token for a target, if any. Used to
     * reuse a link without resetting its expiry (see PaymentAccessService::ensureLink()).
     */
    public function findActiveFor(string $targetType, string $targetId): ?object
    {
        return $this->where('target_type', $targetType)
            ->where('target_id', $targetId)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->first();
    }

    /**
     * Encrypt {target_type, target_id} into the token string itself.
     * AES-CTR + a fresh random IV per call means the same target produces a
     * different ciphertext every time — exactly the per-issuance uniqueness
     * we want, with no separate random nonce needed.
     */
    private function encodeToken(string $targetType, string $targetId): string
    {
        $payload = json_encode(['t' => $targetType, 'i' => $targetId], JSON_THROW_ON_ERROR);
        $cipher  = $this->encrypter()->encrypt($payload);

        return rtrim(strtr(base64_encode($cipher), '+/', '-_'), '=');
    }

    /**
     * Decrypt a token back into {target_type, target_id}. This is the
     * authoritative source of truth for what a token grants access to —
     * PaymentAccessService::redeem() uses this, not the DB row's plaintext
     * columns. Returns null for anything that isn't a token this model
     * issued: malformed input, wrong/rotated encryption.key, or a tampered
     * value — all treated the same as "invalid" by the caller.
     */
    public function decodeToken(string $token): ?array
    {
        $raw = base64_decode(strtr($token, '-_', '+/'), true);
        if ($raw === false) {
            return null;
        }

        try {
            $payload = $this->encrypter()->decrypt($raw);
        } catch (EncryptionException|\Throwable $e) {
            return null;
        }

        $data = json_decode((string) $payload, true);
        if (!is_array($data) || !isset($data['t'], $data['i']) || !is_string($data['t']) || !is_string($data['i'])) {
            return null;
        }

        return ['target_type' => $data['t'], 'target_id' => $data['i']];
    }
}

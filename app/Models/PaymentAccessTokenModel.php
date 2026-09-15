<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Gateway tokens standing in for the raw Stripe Checkout URL in emails.
 *
 * One active token per (target_type, target_id) — issuing a new one deletes
 * the previous row, giving every resend a fresh expiry (sliding renewal).
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
        $token     = bin2hex(random_bytes(32));

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
     * Find a token that exists and has not yet expired.
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
}

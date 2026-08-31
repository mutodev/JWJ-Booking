<?php

namespace App\Models;

use App\Entities\CustomPaymentLink;
use CodeIgniter\Model;

class CustomPaymentLinkModel extends Model
{
    protected $table            = 'custom_payment_links';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = CustomPaymentLink::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

    /**
     * Second whitelist (Model layer). The Repository exposes a narrower
     * request-facing whitelist for create()/update; the sensitive columns
     * below are only ever written by CustomPaymentLinkService through the
     * dedicated repository methods (attachSession, markPaid, updateStatus).
     */
    protected $allowedFields = [
        'id',
        'reservation_id',
        'customer_name',
        'customer_email',
        'description',
        'amount',
        'currency',
        'status',
        'stripe_session_id',
        'stripe_payment_intent_id',
        'payment_url',
        'paid_at',
        'expires_at',
        'created_by',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts        = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = ['generateUUID'];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    protected function generateUUID(array $data)
    {
        return generate_uuid_data($data);
    }
}

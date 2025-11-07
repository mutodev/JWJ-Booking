<?php

namespace App\Models;

use CodeIgniter\Model;

class ReservationDraftModel extends Model
{
    protected $table            = 'reservation_drafts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'id',
        'session_id',
        'email',
        'phone',
        'current_step',
        'form_data',
        'completed',
        'reservation_id',
        'ip_address',
        'user_agent',
        'last_activity_at',
        'created_at',
        'updated_at'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [
        'form_data' => 'json',
        'completed' => 'boolean'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = ['generateUUID'];

    protected function generateUUID(array $data)
    {
        return generate_uuid_data($data);
    }

    /**
     * Find draft by session ID
     */
    public function findBySession(string $sessionId)
    {
        return $this->where('session_id', $sessionId)
            ->where('completed', 0)
            ->orderBy('last_activity_at', 'DESC')
            ->first();
    }

    /**
     * Find draft by email
     */
    public function findByEmail(string $email)
    {
        return $this->where('email', $email)
            ->where('completed', 0)
            ->orderBy('last_activity_at', 'DESC')
            ->first();
    }

    /**
     * Get abandoned drafts (older than X hours)
     */
    public function getAbandoned(int $hoursOld = 24)
    {
        return $this->where('completed', 0)
            ->where('email IS NOT NULL')
            ->where('last_activity_at <', date('Y-m-d H:i:s', strtotime("-{$hoursOld} hours")))
            ->orderBy('last_activity_at', 'DESC')
            ->findAll();
    }

    /**
     * Get funnel analytics
     */
    public function getFunnelStats()
    {
        $db = $this->db;

        return [
            'total_drafts' => $db->table($this->table)->countAllResults(),
            'completed' => $db->table($this->table)->where('completed', 1)->countAllResults(),
            'abandoned' => $db->table($this->table)->where('completed', 0)->countAllResults(),
            'by_step' => $db->table($this->table)
                ->select('current_step, COUNT(*) as count')
                ->where('completed', 0)
                ->groupBy('current_step')
                ->orderBy('current_step', 'ASC')
                ->get()
                ->getResultArray()
        ];
    }
}

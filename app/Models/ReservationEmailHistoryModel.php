<?php

namespace App\Models;

use CodeIgniter\Model;

class ReservationEmailHistoryModel extends Model
{
    protected $table            = 'reservation_email_history';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'id',
        'reservation_id',
        'template_id',
        'template_name',
        'sent_by',
        'recipient_email',
        'email_subject',
        'email_body',
        'status',
        'sent_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $beforeInsert = ['generateUUID'];

    protected function generateUUID(array $data)
    {
        return generate_uuid_data($data);
    }
}

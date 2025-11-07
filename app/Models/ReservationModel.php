<?php

namespace App\Models;

use App\Entities\Reservation;
use CodeIgniter\Model;

class ReservationModel extends Model
{
    protected $table            = 'reservations';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = Reservation::class;
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'id', // Añadido: necesario para inserción manual con UUID
        'customer_id',
        'service_id',
        'zipcode_id',
        'service_price_id',
        'event_address',
        'event_date',
        'event_time',
        'children_count',
        'arrival_parking_instructions',
        'entertainment_start_time',
        'birthday_child_name',
        'birthday_child_age',
        'children_age_range',
        'song_requests',
        'sing_happy_birthday',
        'expedition_fee',
        'extra_children_fee',
        'discount_amount',
        'promo_code',
        'event_type',
        'performers_count',
        'duration_hours',
        'price_type',
        'base_price',
        'addons_total',
        'total_amount',
        'status',
        'is_invoiced',
        'is_paid',
        'customer_notes',
        'internal_notes',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

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
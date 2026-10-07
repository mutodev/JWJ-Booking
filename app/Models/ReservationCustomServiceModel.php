<?php

namespace App\Models;

use App\Entities\ReservationCustomService;
use CodeIgniter\Model;

class ReservationCustomServiceModel extends Model
{
    protected $table            = 'reservation_custom_services';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = ReservationCustomService::class;
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'reservation_id',
        'custom_service_id',
        'name',
        'detail',
        'catalog_price',
        'price_at_time',
        'created_by',
        'updated_by',
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

<?php

namespace App\Models;

use App\Entities\TypeAddon;
use CodeIgniter\Model;

class TypeAddonModel extends Model
{
    protected $table            = 'type_addons';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = TypeAddon::class;
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'name',
        'image',
        'description',
        'is_active',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = ['generateUUID'];

    protected function generateUUID(array $data)
    {
        return generate_uuid_data($data);
    }
}

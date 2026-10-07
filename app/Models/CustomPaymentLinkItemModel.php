<?php

namespace App\Models;

use CodeIgniter\Model;

class CustomPaymentLinkItemModel extends Model
{
    protected $table            = 'custom_payment_link_items';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'id',
        'payment_link_id',
        'item_type',
        'item_id',
        'name',
        'detail',
        'catalog_price',
        'price',
        'quantity',
        'sort_order',
    ];

    protected bool $allowEmptyInserts = false;

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}

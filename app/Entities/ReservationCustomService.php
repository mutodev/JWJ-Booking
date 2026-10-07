<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class ReservationCustomService extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at', 'deleted_at'];
    protected $casts   = [
        'id' => 'string',
        'reservation_id' => 'string',
        'custom_service_id' => 'string',
        'name' => 'string',
        'detail' => '?string',
        'catalog_price' => 'float',
        'price_at_time' => 'float',
        'created_by' => '?string',
        'updated_by' => '?string',
    ];
}

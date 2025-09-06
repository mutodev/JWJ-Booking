<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class ReservationAddon extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at'];
    protected $casts   = [
        'id' => 'string',
        'reservation_id' => 'string',
        'addon_id' => 'string',
        'quantity' => 'integer',
        'price_at_time' => 'float'
    ];
}

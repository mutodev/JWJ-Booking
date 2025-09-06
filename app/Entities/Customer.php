<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Customer extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at', 'last_reservation_date'];
    protected $casts   = [
        'id' => 'string',
        'email' => 'string',
        'phone' => 'string',
        'full_name' => 'string',
        'billing_address' => 'string',
        'segment' => 'string',
    ];
}

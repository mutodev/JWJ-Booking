<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class CustomPaymentLink extends Entity
{
    protected $datamap = [];
    protected $dates   = ['paid_at', 'expires_at', 'created_at', 'updated_at'];
    protected $casts   = [
        'id'             => 'string',
        'reservation_id' => '?string',
        'customer_name'  => '?string',
        'customer_email' => 'string',
        'description'    => 'string',
        'amount'         => 'float',
        'currency'       => 'string',
        'status'         => 'string',
    ];
}

<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Reservation extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at', 'deleted_at', 'event_date'];
    protected $casts   = [
        'id' => 'string',
        'customer_id' => 'string',
        'service_id' => 'string',
        'zipcode_id' => 'string',
        'service_price_id' => 'string',
        'children_count' => 'integer',
        'performers_count' => 'integer',
        'duration_hours' => 'integer',
        'base_price' => 'float',
        'addons_total' => 'float',
        'expedition_fee' => 'float',
        'extra_children_fee' => 'float',
        'total_amount' => 'float',
        'is_invoiced' => 'boolean',
        'is_paid' => 'boolean',
        'birthday_child_age' => 'integer',
        'sing_happy_birthday' => 'boolean'
    ];
}

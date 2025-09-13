<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Reservation extends Entity
{
    protected $datamap = [];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'event_date',
    ];

    protected $casts = [
        'id' => 'string',
        'customer_id' => 'string',
        'service_price_id' => 'string',
        'zipcode_id' => 'string',
        'event_address' => 'string',
        'event_date' => 'string',
        'event_time' => 'string',
        'children_count' => 'integer',
        'performers_count' => 'integer',
        'duration_hours' => 'integer',
        'price_type' => 'string',
        'base_price' => 'float',
        'addons_total' => 'float',
        'expedition_fee' => 'float',
        'extra_children_fee' => 'float',
        'total_amount' => 'float',
        'status' => 'string',
        'is_invoiced' => 'boolean',
        'is_paid' => 'boolean',
        'arrival_parking_instructions' => 'string',
        'entertainment_start_time' => 'string',
        'birthday_child_name' => 'string',
        'birthday_child_age' => 'integer',
        'children_age_range' => 'string',
        'song_requests' => 'string',
        'sing_happy_birthday' => 'boolean',
        'customer_notes' => 'string',
        'internal_notes' => 'string',
    ];
}

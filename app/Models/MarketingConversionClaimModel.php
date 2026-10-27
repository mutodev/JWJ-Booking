<?php

namespace App\Models;

use CodeIgniter\Model;

class MarketingConversionClaimModel extends Model
{
    protected $table = 'marketing_conversion_claims';
    protected $primaryKey = 'event_key';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $allowedFields = [
        'event_key', 'event_type', 'reference_id', 'amount', 'currency', 'created_at',
    ];
    protected $useTimestamps = false;
}

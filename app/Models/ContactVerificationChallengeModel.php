<?php

namespace App\Models;

use CodeIgniter\Model;

class ContactVerificationChallengeModel extends Model
{
    protected $table = 'contact_verification_challenges';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = 'object';
    protected $allowedFields = [
        'id', 'session_id', 'method', 'email', 'phone', 'destination_masked',
        'code_hash', 'verification_hash', 'attempts', 'expires_at',
        'verified_at', 'consumed_at', 'created_at',
    ];
    protected $useTimestamps = false;
}

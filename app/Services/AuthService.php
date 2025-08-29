<?php

namespace App\Services;

class AuthService
{
    protected $user;

    public function setUser($user)
    {
        $this->user = $user;
    }

    public function user()
    {
        return $this->user;
    }
}

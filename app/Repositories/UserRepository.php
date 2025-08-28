<?php

namespace App\Repositories;

use App\Entities\User;
use App\Models\UserModel;

class UserRepository
{
    protected UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }
}

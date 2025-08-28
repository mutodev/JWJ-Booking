<?php

namespace App\Repositories;

use App\Models\MenuModel;

class MenuRepository
{

    protected $menuModel;

    public function __construct()
    {
        $this->menuModel = new MenuModel();
    }
}

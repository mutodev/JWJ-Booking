<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;

class HomeController extends ResourceController
{
    /**
     * Retorno de vista inicial
     *
     * @return {string} view
     */
    public function index()
    {
        return view('index');
    }
}

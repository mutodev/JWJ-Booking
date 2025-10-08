<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;

class HomeController extends ResourceController
{
    /**
     * Retorno de vista inicial para SPA de Vue.js
     * Esta función maneja todas las rutas del frontend
     *
     * @return string view
     */
    public function index()
    {
        // Log para debugging (solo en desarrollo)
        if (ENVIRONMENT === 'development') {
            log_message('info', 'SPA Route accessed: ' . current_url());
        }

        // Verificar que los archivos de build existen
        $manifestPath = FCPATH . 'build/.vite/manifest.json';
        if (!file_exists($manifestPath)) {
            log_message('error', 'Vite manifest not found. Run: npm run build');
        }

        $env = getenv('CI_ENVIRONMENT') ?: 'production';

        return view('index', [
            'APP_ENV' => $env
        ]);
    }
}

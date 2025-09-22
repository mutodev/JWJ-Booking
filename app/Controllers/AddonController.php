<?php

namespace App\Controllers;

use App\Services\AddonService;
use CodeIgniter\RESTful\ResourceController;

/**
 * Addon Controller
 *
 * RESTful controller for managing addon operations.
 * Handles CRUD operations with image upload support and proper error handling.
 *
 * Features:
 * - Complete CRUD operations for addons
 * - Image upload support for create and update
 * - Search functionality by name
 * - Active addons filtering
 * - Comprehensive error handling and logging
 * - Standardized API responses
 *
 * @package App\Controllers
 * @author  System Generated
 */
class AddonController extends ResourceController
{
    protected AddonService $service;

    /**
     * Constructor - Initialize addon service
     */
    public function __construct()
    {
        $this->service = new AddonService();
    }

    /**
     * Get all addons
     *
     * @return \CodeIgniter\HTTP\ResponseInterface JSON response with all addons
     */
    public function getAll()
    {
        try {
            $addons = $this->service->getAll();
            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response(lang('App.addon_list'), $addons));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Get all active addons
     *
     * @return \CodeIgniter\HTTP\ResponseInterface JSON response with active addons only
     */
    public function getAllActive()
    {
        try {
            $activeAddons = $this->service->getAllActive();
            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response(lang('App.addon_list_active'), $activeAddons));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Search addons by name
     *
     * @param string $name Name pattern to search for
     * @return \CodeIgniter\HTTP\ResponseInterface JSON response with matching addons
     */
    public function search($name)
    {
        try {
            $results = $this->service->search($name);
            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response(lang('App.addon_search_results', ['name' => $name]), $results));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Get addon by ID
     *
     * @param string $id Addon ID
     * @return \CodeIgniter\HTTP\ResponseInterface JSON response with addon data
     */
    public function getById($id)
    {
        try {
            $addon = $this->service->getById($id);
            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response(lang('App.addon_found'), $addon));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Create new addon with image support
     *
     * Handles both JSON and FormData requests for creating addons.
     * Supports image upload with validation and proper error handling.
     *
     * @return \CodeIgniter\HTTP\ResponseInterface JSON response with created addon ID
     */
    public function create()
    {
        try {
            $addonId = $this->service->createWithImage($this->request);

            return $this->response
                ->setStatusCode(201)
                ->setJSON(create_response(lang('App.addon_created'), $addonId));
        } catch (\Throwable $th) {
            log_message('error', 'Addon creation error: ' . $th->getMessage());
            log_message('error', 'Addon creation trace: ' . $th->getTraceAsString());
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Update existing addon with image support
     *
     * Handles both JSON and FormData requests for updating addons.
     * Supports image upload and replacement with validation.
     *
     * @param string $id Addon ID to update
     * @return \CodeIgniter\HTTP\ResponseInterface JSON response with update result
     */
    public function updateData($id)
    {
        try {
            $result = $this->service->updateWithImage($id, $this->request);

            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response(lang('App.addon_updated'), $result));
        } catch (\Throwable $th) {
            log_message('error', 'Addon update error: ' . $th->getMessage());
            log_message('error', 'Addon update trace: ' . $th->getTraceAsString());
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Delete addon
     *
     * Performs soft delete of the specified addon.
     *
     * @param string $id Addon ID to delete
     * @return \CodeIgniter\HTTP\ResponseInterface JSON response with deletion result
     */
    public function deleteData($id)
    {
        try {
            $result = $this->service->delete($id);
            return $this->response
                ->setStatusCode(200)
                ->setJSON(create_response(lang('App.addon_deleted'), $result));
        } catch (\Throwable $th) {
            return $this->response
                ->setStatusCode($th->getCode() == 0 ? 500 : $th->getCode())
                ->setJSON(['message' => $th->getMessage()]);
        }
    }
}

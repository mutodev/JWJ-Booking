<?php

namespace App\Controllers;

use App\Services\ReservationService;
use App\Services\ReservationDraftService;
use CodeIgniter\RESTful\ResourceController;

class ReservationController extends ResourceController
{
    protected $service;
    protected $draftService;

    public function __construct()
    {
        $this->service = new ReservationService();
        $this->draftService = new ReservationDraftService();
    }

    public function getAll()
    {
        try {
            return $this->response->setStatusCode(200)
                ->setJSON(create_response(lang('Reservation.list'), $this->service->getAll()));
        } catch (\Throwable $th) {
            print_r($th);
            die();
            return $this->response->setStatusCode($th->getCode() ?: 500)
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    public function getById($id)
    {
        try {
            return $this->response->setStatusCode(200)
                ->setJSON(create_response(lang('Reservation.found'), $this->service->getById($id)));
        } catch (\Throwable $th) {
            return $this->response->setStatusCode($th->getCode() ?: 500)
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    public function create()
    {
        try {
            $json = $this->request->getBody();
            $data = json_decode($json, true);

            return $this->response->setStatusCode(201)
                ->setJSON(create_response(lang('Reservation.created'), $this->service->create($data)));
        } catch (\Throwable $th) {
            return $this->response->setStatusCode($th->getCode() ?: 500)
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Crear reserva desde el formulario del frontend
     * Recibe todos los datos de los steps y los mapea correctamente
     */
    public function createFromForm()
    {
        try {
            $json = $this->request->getBody();
            $formData = json_decode($json, true);

            $result = $this->service->createFromForm($formData);

            // Mark draft as completed if session_id provided
            if (isset($formData['session_id']) && $result['reservation']) {
                $this->draftService->completeDraft(
                    $formData['session_id'],
                    $result['reservation']->id
                );
            }

            return $this->response->setStatusCode(201)
                ->setJSON(create_response(lang('Reservation.created'), $result));
        } catch (\Throwable $th) {
            return $this->response->setStatusCode($th->getCode() ?: 500)
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    public function updateData($id)
    {
        try {
            $json = $this->request->getBody();
            $data = json_decode($json, true);

            return $this->response->setStatusCode(200)
                ->setJSON(create_response(lang('Reservation.updated'), $this->service->update($id, $data)));
        } catch (\Throwable $th) {
            return $this->response->setStatusCode($th->getCode() ?: 500)
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    public function deleteData($id)
    {
        try {
            return $this->response->setStatusCode(200)
                ->setJSON(create_response(lang('Reservation.deleted'), $this->service->delete($id)));
        } catch (\Throwable $th) {
            return $this->response->setStatusCode($th->getCode() ?: 500)
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    public function sendPaymentEmail()
    {
        try {
            $json = $this->request->getBody();
            $data = json_decode($json, true);

            $this->service->sendPaymentEmail($data['reservationId'], $data['paymentUrl']);

            return $this->response->setStatusCode(200)
                ->setJSON(create_response('Payment email sent successfully', null));
        } catch (\Throwable $th) {
            return $this->response->setStatusCode($th->getCode() ?: 500)
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Save draft progress
     */
    public function saveDraft()
    {
        try {
            $json = $this->request->getBody();
            $data = json_decode($json, true);

            // Add IP and user agent
            $data['ip_address'] = $this->request->getIPAddress();
            $data['user_agent'] = $this->request->getUserAgent()->getAgentString();

            $result = $this->draftService->saveDraft($data);

            if ($result['success']) {
                return $this->response->setStatusCode(200)
                    ->setJSON(create_response('Draft saved successfully', ['draft_id' => $result['draft_id']]));
            } else {
                return $this->response->setStatusCode(400)
                    ->setJSON(create_response($result['message'], null));
            }
        } catch (\Throwable $th) {
            log_message('error', 'Error saving draft: ' . $th->getMessage());
            return $this->response->setStatusCode(500)
                ->setJSON(create_response('Failed to save draft', null));
        }
    }

    /**
     * Get draft by session
     */
    public function getDraft()
    {
        try {
            $sessionId = $this->request->getGet('session_id');
            $email = $this->request->getGet('email');

            if (!$sessionId) {
                return $this->response->setStatusCode(400)
                    ->setJSON(create_response('Session ID is required', null));
            }

            $draft = $this->draftService->getDraft($sessionId, $email);

            if ($draft) {
                return $this->response->setStatusCode(200)
                    ->setJSON(create_response('Draft found', $draft));
            } else {
                return $this->response->setStatusCode(404)
                    ->setJSON(create_response('No draft found', null));
            }
        } catch (\Throwable $th) {
            log_message('error', 'Error getting draft: ' . $th->getMessage());
            return $this->response->setStatusCode(500)
                ->setJSON(create_response('Failed to get draft', null));
        }
    }
}

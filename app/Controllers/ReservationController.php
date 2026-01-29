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

            $result = $this->service->sendPaymentEmail($data['reservationId']);

            return $this->response->setStatusCode(200)
                ->setJSON(create_response('Payment email sent successfully', $result));
        } catch (\Throwable $th) {
            // Ensure we only use valid HTTP status codes
            $statusCode = 500;
            if ($th->getCode() >= 400 && $th->getCode() < 600) {
                $statusCode = $th->getCode();
            }

            return $this->response->setStatusCode($statusCode)
                ->setJSON(['message' => $th->getMessage()]);
        }
    }

    /**
     * Handle Stripe webhook events
     */
    public function stripeWebhook()
    {
        try {
            $payload = file_get_contents('php://input');
            $sigHeader = $this->request->getHeaderLine('Stripe-Signature');

            if (empty($sigHeader)) {
                return $this->response->setStatusCode(400)
                    ->setJSON(['message' => 'Missing Stripe-Signature header']);
            }

            $stripeService = new \App\Services\StripeService();
            $event = $stripeService->verifyWebhookSignature($payload, $sigHeader);

            if ($event->type === 'checkout.session.completed') {
                $session = $event->data->object;
                $reservationId = $session->metadata->reservation_id ?? null;
                $paymentIntentId = $session->payment_intent ?? null;

                if ($reservationId) {
                    $this->service->handlePaymentCompleted($reservationId, $paymentIntentId);
                }
            }

            return $this->response->setStatusCode(200)
                ->setJSON(['received' => true]);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            log_message('error', 'Stripe webhook signature verification failed: ' . $e->getMessage());
            return $this->response->setStatusCode(400)
                ->setJSON(['message' => 'Invalid signature']);
        } catch (\Throwable $th) {
            log_message('error', 'Stripe webhook error: ' . $th->getMessage());
            return $this->response->setStatusCode(500)
                ->setJSON(['message' => 'Webhook processing failed']);
        }
    }

    /**
     * Update confirmation fields only
     */
    public function updateConfirmation($id)
    {
        try {
            $json = $this->request->getBody();
            $data = json_decode($json, true);

            $allowedFields = [
                'event_address',
                'arrival_parking_instructions',
                'event_time',
                'entertainment_start_time',
                'birthday_child_name',
                'birthday_child_age',
                'children_age_range',
                'song_requests',
                'sing_happy_birthday'
            ];

            $updateData = [];
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (empty($updateData)) {
                throw new \Exception('No valid fields to update', 400);
            }

            $this->service->updateConfirmation($id, $updateData);

            return $this->response->setStatusCode(200)
                ->setJSON(create_response('Confirmation details updated successfully', null));
        } catch (\Throwable $th) {
            // Ensure we only use valid HTTP status codes
            $statusCode = 500;
            if ($th->getCode() >= 400 && $th->getCode() < 600) {
                $statusCode = $th->getCode();
            }

            return $this->response->setStatusCode($statusCode)
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

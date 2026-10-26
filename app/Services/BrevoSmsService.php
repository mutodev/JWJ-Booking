<?php

namespace App\Services;

use Brevo\Client\Api\TransactionalSMSApi;
use Brevo\Client\Configuration;
use Brevo\Client\Model\SendTransacSms;
use GuzzleHttp\Client;

class BrevoSmsService
{
    protected TransactionalSMSApi $api;

    public function __construct(?TransactionalSMSApi $api = null)
    {
        if ($api !== null) {
            $this->api = $api;
            return;
        }

        // Brevo uses the same API key for transactional email and SMS.
        $apiKey = trim((string) getenv('brevo.apiKey'));
        if ($apiKey === '') {
            throw new \RuntimeException('Brevo API key is not configured.');
        }

        $config = Configuration::getDefaultConfiguration()
            ->setApiKey('api-key', $apiKey);
        $this->api = new TransactionalSMSApi(new Client(['verify' => false]), $config);
    }

    public function send(string $recipient, string $content)
    {
        $recipient = trim($recipient);
        if (!preg_match('/^\+[1-9]\d{7,14}$/', $recipient)) {
            throw new \InvalidArgumentException('SMS recipient must be a valid E.164 phone number.');
        }

        $content = trim($content);
        if ($content === '') {
            throw new \InvalidArgumentException('SMS content cannot be empty.');
        }

        $sender = preg_replace('/[^A-Za-z0-9]/', '', (string) (getenv('brevo.smsSender') ?: 'JamJamie'));
        if ($sender === '') {
            $sender = 'JamJamie';
        }
        $payload = new SendTransacSms([
            'sender' => substr($sender, 0, 11),
            'recipient' => $recipient,
            'content' => $content,
            'type' => 'transactional',
        ]);

        return $this->api->sendTransacSms($payload);
    }
}

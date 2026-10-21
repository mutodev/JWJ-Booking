<?php

namespace App\Services;

use Brevo\Client\Api\TransactionalSMSApi;
use Brevo\Client\Configuration;
use Brevo\Client\Model\SendTransacSms;
use GuzzleHttp\Client;

class BrevoSmsService
{
    protected TransactionalSMSApi $api;

    public function __construct()
    {
        $config = Configuration::getDefaultConfiguration()
            ->setApiKey('api-key', (string) getenv('brevo.apiKey'));
        $this->api = new TransactionalSMSApi(new Client(['verify' => false]), $config);
    }

    public function send(string $recipient, string $content)
    {
        $sender = preg_replace('/[^A-Za-z0-9]/', '', (string) (getenv('brevo.smsSender') ?: 'JamJamie'));
        $payload = new SendTransacSms([
            'sender' => substr($sender, 0, 11),
            'recipient' => $recipient,
            'content' => $content,
            'type' => 'transactional',
        ]);

        return $this->api->sendTransacSms($payload);
    }
}

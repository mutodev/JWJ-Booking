<?php

namespace App\Services;

use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Configuration;
use Brevo\Client\Model\SendSmtpEmail;
use GuzzleHttp\Client;

class BrevoEmailService
{
    protected $apiInstance;

    public function __construct()
    {
        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', getenv('brevo.apiKey'));

        $this->apiInstance = new TransactionalEmailsApi(
            new Client([
                'verify' => false
            ]),
            $config
        );
    }

    /**
     * Enviar correo electrónico
     *
     * @param string $to
     * @param string $subject
     * @param string $htmlContent
     * @return \Brevo\Client\Model\CreateSmtpEmail
     */
    public function sendEmail($to, $subject, $htmlContent)
    {
        $sendSmtpEmail = new SendSmtpEmail([
            'subject' => $subject,
            'sender' => ['name' => getenv("brevo.fromName"), 'email' => getenv('brevo.fromEmail')],
            'to' => [['email' => $to]],
            'htmlContent' => $htmlContent
        ]);

        return $this->apiInstance->sendTransacEmail($sendSmtpEmail);
    }
}

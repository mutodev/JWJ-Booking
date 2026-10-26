<?php

namespace App\Services;

use GuzzleHttp\Client;

final class BrevoHttpClientFactory
{
    public static function create(?int $timeout = null): Client
    {
        // Reuse the existing CA setting for every Brevo API, not only contact
        // synchronization. Linux normally uses its system CA store; Windows
        // development environments may point this to a PEM bundle.
        $caBundle = trim((string) getenv('brevo.contacts.caBundle'));
        $verify = $caBundle !== '' ? $caBundle : true;

        if (is_string($verify) && !is_file($verify)) {
            throw new \RuntimeException('Brevo CA bundle does not exist: ' . $verify);
        }

        $options = ['verify' => $verify];
        if ($timeout !== null) {
            $options['timeout'] = max(1, $timeout);
        }

        return new Client($options);
    }
}

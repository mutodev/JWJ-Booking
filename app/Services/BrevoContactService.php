<?php

namespace App\Services;

use Brevo\Client\Api\ContactsApi;
use Brevo\Client\Configuration;
use Brevo\Client\Model\CreateContact;
use GuzzleHttp\Client;

class BrevoContactService
{
    private const DEFAULT_LIST_ID = 25;

    protected ContactsApi $apiInstance;
    protected bool $enabled;
    protected int $listId;

    public function __construct(?ContactsApi $apiInstance = null, ?bool $enabled = null, ?int $listId = null)
    {
        $apiKey = trim((string) getenv('brevo.apiKey'));
        $this->enabled = $enabled ?? filter_var(
            getenv('brevo.contacts.enabled') ?: 'false',
            FILTER_VALIDATE_BOOLEAN
        );
        $this->listId = $listId ?? (int) (getenv('brevo.contacts.listId') ?: self::DEFAULT_LIST_ID);

        if ($apiInstance !== null) {
            $this->apiInstance = $apiInstance;
            return;
        }

        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $apiKey);
        $timeout = max(1, (int) (getenv('brevo.contacts.timeout') ?: 5));
        $caBundle = trim((string) getenv('brevo.contacts.caBundle'));
        $verify = $caBundle !== '' ? $caBundle : true;

        if (is_string($verify) && !is_file($verify)) {
            throw new \RuntimeException('Brevo CA bundle does not exist: ' . $verify);
        }

        $this->apiInstance = new ContactsApi(new Client([
            'timeout' => $timeout,
            'verify' => $verify,
        ]), $config);
    }

    public function isEnabled(): bool
    {
        return $this->enabled && $this->listId > 0 && trim((string) getenv('brevo.apiKey')) !== '';
    }

    /**
     * Creates or updates a Brevo contact and subscribes it to the configured list.
     */
    public function syncContact(array $contact): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $email = strtolower(trim((string) ($contact['email'] ?? '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        [$firstName, $lastName] = $this->resolveNames($contact);
        $attributes = [
            'FIRSTNAME' => $firstName,
            'LASTNAME' => $lastName,
        ];

        $phone = $this->normalizePhone((string) ($contact['phone'] ?? ''));
        if ($phone !== '') {
            $attributes['SMS'] = $phone;
        }

        $payload = new CreateContact([
            'email' => $email,
            'attributes' => $attributes,
            'emailBlacklisted' => false,
            'listIds' => [$this->listId],
            'updateEnabled' => true,
        ]);

        $this->apiInstance->createContact($payload);
        return true;
    }

    private function resolveNames(array $contact): array
    {
        $firstName = trim((string) ($contact['first_name'] ?? $contact['firstName'] ?? ''));
        $lastName = trim((string) ($contact['last_name'] ?? $contact['lastName'] ?? ''));

        if ($firstName === '' && $lastName === '') {
            $parts = preg_split('/\s+/', trim((string) ($contact['full_name'] ?? '')), 2);
            $firstName = $parts[0] ?? '';
            $lastName = $parts[1] ?? '';
        }

        return [$firstName, $lastName];
    }

    private function normalizePhone(string $phone): string
    {
        $hasInternationalPrefix = str_starts_with(trim($phone), '+');
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (strlen($digits) === 10) {
            return '+1' . $digits;
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '1')) {
            return '+' . $digits;
        }

        return $hasInternationalPrefix && strlen($digits) >= 7 ? '+' . $digits : $digits;
    }
}

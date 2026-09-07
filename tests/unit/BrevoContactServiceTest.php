<?php

namespace Tests\Unit;

use App\Services\BrevoContactService;
use App\Services\ReservationService;
use Brevo\Client\Api\ContactsApi;
use CodeIgniter\Test\CIUnitTestCase;

final class BrevoContactServiceTest extends CIUnitTestCase
{
    public function testSyncBuildsExpectedNewsletterContactPayload(): void
    {
        $api = new class extends ContactsApi {
            public $payload;

            public function createContact($body)
            {
                $this->payload = $body;
                return (object) ['id' => 123];
            }
        };

        $previousApiKey = getenv('brevo.apiKey');
        putenv('brevo.apiKey=test-key');

        try {
            $service = new BrevoContactService($api, true, 25);
            $result = $service->syncContact([
                'first_name' => 'Jamie',
                'last_name' => 'Smith',
                'email' => ' JAMIE@EXAMPLE.COM ',
                'phone' => '(212) 555-0123',
            ]);

            $this->assertTrue($result);
            $this->assertSame('jamie@example.com', $api->payload->getEmail());
            $this->assertSame(['FIRSTNAME' => 'Jamie', 'LASTNAME' => 'Smith', 'SMS' => '+12125550123'], $api->payload->getAttributes());
            $this->assertSame([25], $api->payload->getListIds());
            $this->assertTrue($api->payload->getUpdateEnabled());
            $this->assertFalse($api->payload->getEmailBlacklisted());
        } finally {
            putenv($previousApiKey === false ? 'brevo.apiKey' : 'brevo.apiKey=' . $previousApiKey);
        }
    }

    public function testForFollowUpTargetsListFromEnvAndDefaultsTo58(): void
    {
        $api = new class extends ContactsApi {
            public $payload;

            public function createContact($body)
            {
                $this->payload = $body;
                return (object) ['id' => 1];
            }
        };

        $prevKey = getenv('brevo.apiKey');
        $prevEnabled = getenv('brevo.contacts.followUpEnabled');
        $prevList = getenv('brevo.contacts.followUpListId');
        putenv('brevo.apiKey=test-key');
        putenv('brevo.contacts.followUpEnabled=true');
        putenv('brevo.contacts.followUpListId'); // unset -> falls back to the 58 default

        try {
            $service = BrevoContactService::forFollowUp($api);

            $this->assertTrue($service->isEnabled());
            $this->assertTrue($service->syncContact([
                'full_name' => 'Jamie Buyer',
                'email' => 'buyer@example.com',
            ]));
            $this->assertSame([58], $api->payload->getListIds());
        } finally {
            putenv($prevKey === false ? 'brevo.apiKey' : 'brevo.apiKey=' . $prevKey);
            putenv($prevEnabled === false ? 'brevo.contacts.followUpEnabled' : 'brevo.contacts.followUpEnabled=' . $prevEnabled);
            putenv($prevList === false ? 'brevo.contacts.followUpListId' : 'brevo.contacts.followUpListId=' . $prevList);
        }
    }

    public function testForFollowUpIsDisabledByDefault(): void
    {
        $prevEnabled = getenv('brevo.contacts.followUpEnabled');
        putenv('brevo.contacts.followUpEnabled'); // unset

        try {
            $api = new class extends ContactsApi {
                public bool $called = false;

                public function createContact($body)
                {
                    $this->called = true;
                    return null;
                }
            };

            $service = BrevoContactService::forFollowUp($api);

            $this->assertFalse($service->isEnabled());
            $this->assertFalse($service->syncContact(['email' => 'buyer@example.com']));
            $this->assertFalse($api->called);
        } finally {
            putenv($prevEnabled === false ? 'brevo.contacts.followUpEnabled' : 'brevo.contacts.followUpEnabled=' . $prevEnabled);
        }
    }

    public function testDisabledSyncMakesNoApiCall(): void
    {
        $api = new class extends ContactsApi {
            public bool $called = false;

            public function createContact($body)
            {
                $this->called = true;
                return null;
            }
        };

        $service = new BrevoContactService($api, false, 25);

        $this->assertFalse($service->syncContact(['email' => 'customer@example.com']));
        $this->assertFalse($api->called);
    }

    public function testBrevoFailureDoesNotEscapeReservationService(): void
    {
        $failingService = new class extends BrevoContactService {
            public function __construct()
            {
            }

            public function syncContact(array $contact): bool
            {
                throw new \RuntimeException('Brevo unavailable');
            }
        };

        $reservationService = new ReservationService();
        $property = new \ReflectionProperty(ReservationService::class, 'brevoContactService');
        $property->setAccessible(true);
        $property->setValue($reservationService, $failingService);

        $method = new \ReflectionMethod(ReservationService::class, 'syncBrevoContactSafely');
        $method->setAccessible(true);

        $method->invoke($reservationService, ['email' => 'customer@example.com'], 'test');
        $this->addToAssertionCount(1);
    }
}

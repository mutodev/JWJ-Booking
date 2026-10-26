<?php

namespace Tests\Unit;

use App\Services\BrevoSmsService;
use Brevo\Client\Api\TransactionalSMSApi;
use CodeIgniter\Test\CIUnitTestCase;

final class BrevoSmsServiceTest extends CIUnitTestCase
{
    public function testSendBuildsTransactionalSmsPayload(): void
    {
        $api = new class extends TransactionalSMSApi {
            public $payload;

            public function sendTransacSms($body)
            {
                $this->payload = $body;
                return (object) ['messageId' => 123];
            }
        };

        $previousSender = getenv('brevo.smsSender');
        putenv('brevo.smsSender=Jam With Jamie!');

        try {
            $result = (new BrevoSmsService($api))->send('+3508675012', 'Local SMS test');

            $this->assertSame(123, $result->messageId);
            $this->assertSame('JamWithJami', $api->payload->getSender());
            $this->assertSame('+3508675012', $api->payload->getRecipient());
            $this->assertSame('Local SMS test', $api->payload->getContent());
            $this->assertSame('transactional', $api->payload->getType());
        } finally {
            putenv($previousSender === false ? 'brevo.smsSender' : 'brevo.smsSender=' . $previousSender);
        }
    }

    public function testRejectsInvalidRecipientBeforeCallingBrevo(): void
    {
        $api = new class extends TransactionalSMSApi {
            public bool $called = false;

            public function sendTransacSms($body)
            {
                $this->called = true;
            }
        };

        $this->expectException(\InvalidArgumentException::class);

        try {
            (new BrevoSmsService($api))->send('350 867 5012', 'Local SMS test');
        } finally {
            $this->assertFalse($api->called);
        }
    }
}

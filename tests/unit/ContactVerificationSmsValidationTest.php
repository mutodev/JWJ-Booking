<?php

namespace Tests\Unit;

use App\Services\ContactVerificationService;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\Test\CIUnitTestCase;

final class ContactVerificationSmsValidationTest extends CIUnitTestCase
{
    public function testSmsContactIsRejectedWhileFeatureIsDisabled(): void
    {
        $service = (new \ReflectionClass(ContactVerificationService::class))->newInstanceWithoutConstructor();
        $validate = new \ReflectionMethod(ContactVerificationService::class, 'validateContact');
        $validate->setAccessible(true);

        $this->expectException(HTTPException::class);
        $this->expectExceptionCode(422);
        $this->expectExceptionMessage('SMS verification is not available yet. Please use email.');

        $validate->invoke($service, [
            'session_id' => '123e4567-e89b-12d3-a456-426614174000',
            'method' => 'SMS',
            'email' => ' CUSTOMER@EXAMPLE.COM ',
            'phone' => '+3508675012',
        ]);
    }
}

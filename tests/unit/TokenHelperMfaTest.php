<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;

class TokenHelperMfaTest extends CIUnitTestCase
{
    public function testAccessTokenCarriesTypeAndAuthenticationVersion(): void
    {
        $token = generate_token([
            'id' => 'user-1',
            'email' => 'admin@example.com',
            'auth_version' => 3,
        ]);

        $claims = verify_token($token);

        $this->assertSame('access', $claims['token_type']);
        $this->assertSame(3, $claims['auth_version']);
        $this->assertSame('admin@example.com', $claims['email']);
        $this->assertGreaterThan(time(), $claims['exp']);
    }
}

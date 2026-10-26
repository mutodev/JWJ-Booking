<?php

namespace Tests\Unit;

use App\Services\AuthorizationPolicy;
use CodeIgniter\Entity\Entity;
use CodeIgniter\Test\CIUnitTestCase;

class AuthorizationPolicyTest extends CIUnitTestCase
{
    private AuthorizationPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new AuthorizationPolicy();
    }

    public function testMapsCrudMethodsToMenuPermissions(): void
    {
        $this->assertSame(
            ['menu_uri' => '/admin/clients', 'permission' => 'can_view'],
            $this->policy->requirement('GET', '/api/customers')
        );
        $this->assertSame(
            ['menu_uri' => '/admin/clients', 'permission' => 'can_create'],
            $this->policy->requirement('POST', '/api/customers')
        );
        $this->assertSame(
            ['menu_uri' => '/admin/clients', 'permission' => 'can_update'],
            $this->policy->requirement('PUT', '/api/customers/id')
        );
        $this->assertSame(
            ['menu_uri' => '/admin/clients', 'permission' => 'can_delete'],
            $this->policy->requirement('DELETE', '/api/customers/id')
        );
    }

    public function testMapsSpecialAdministrativeActions(): void
    {
        $this->assertSame('can_delete', $this->policy
            ->requirement('POST', '/api/reservations/bulk-delete')['permission']);
        $this->assertSame('can_update', $this->policy
            ->requirement('POST', '/api/reservations/id/send-update-email')['permission']);
        $this->assertSame('can_view', $this->policy
            ->requirement('POST', '/api/reservation-drafts/id/follow-up')['permission']);
        $this->assertSame('can_view', $this->policy
            ->requirement('POST', '/api/email-templates/preview')['permission']);
        $this->assertSame('can_update', $this->policy
            ->requirement('POST', '/api/reservation-addons')['permission']);
        $this->assertSame('can_update', $this->policy
            ->requirement('DELETE', '/api/reservation-custom-services/id')['permission']);
        $this->assertSame('can_update', $this->policy
            ->requirement('POST', '/api/payment-links')['permission']);
    }

    public function testAllowsOnlyTheRequestedPermission(): void
    {
        $permissions = [[
            'menu_uri' => '/admin/reservations',
            'can_view' => true,
            'can_create' => false,
            'can_update' => true,
            'can_delete' => false,
        ]];

        $this->assertTrue($this->policy->allows($permissions, [
            'menu_uri' => '/admin/reservations', 'permission' => 'can_update',
        ]));
        $this->assertFalse($this->policy->allows($permissions, [
            'menu_uri' => '/admin/reservations', 'permission' => 'can_delete',
        ]));

        $this->assertTrue($this->policy->allows([new Entity($permissions[0])], [
            'menu_uri' => '/admin/reservations', 'permission' => 'can_view',
        ]));
    }

    public function testSelfServiceRoutesRemainAvailable(): void
    {
        $requirement = $this->policy->requirement('PUT', '/api/users/user-1', 'user-1');
        $this->assertSame('@self', $requirement['menu_uri']);
        $this->assertTrue($this->policy->allows([], $requirement));
    }

    public function testUnknownAuthenticatedResourceIsNotMapped(): void
    {
        $this->assertNull($this->policy->requirement('GET', '/api/future-admin-route'));
    }

    public function testEveryProtectedResourceHasAMenuMapping(): void
    {
        $resources = [
            'users', 'roles', 'metropolitan-areas', 'counties', 'cities',
            'zipcodes', 'custom-services', 'services', 'service-prices',
            'addons', 'type-addons', 'customers', 'reservations',
            'reservation-custom-services', 'reservation-addons', 'durations',
            'children-ranges', 'dashboard', 'email-templates', 'promo-codes',
            'payment-links', 'reservation-drafts',
        ];

        foreach ($resources as $resource) {
            $this->assertNotNull(
                $this->policy->requirement('GET', '/api/' . $resource),
                'Missing authorization mapping for ' . $resource
            );
        }
    }
}

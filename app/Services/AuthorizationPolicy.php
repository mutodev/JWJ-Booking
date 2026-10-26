<?php

namespace App\Services;

/**
 * Maps authenticated API requests to the same menu permissions used by Vue.
 * This class is intentionally database-free so the policy can be unit tested.
 */
class AuthorizationPolicy
{
    private const RESOURCE_MENUS = [
        'users'                       => '/admin/config/users',
        'roles'                       => '/admin/config/roles',
        'metropolitan-areas'          => '/admin/areas/metropolitan-areas',
        'counties'                    => '/admin/areas/counties',
        'cities'                      => '/admin/areas/cities',
        'zipcodes'                    => '/admin/areas/postal-codes',
        'custom-services'             => '/admin/services/custom-services',
        'services'                    => '/admin/services/jam-types',
        'service-prices'              => '/admin/services/prices',
        'addons'                      => '/admin/services/addons',
        'type-addons'                 => '/admin/services/type-addons',
        'customers'                   => '/admin/clients',
        'reservations'                => '/admin/reservations',
        'reservation-custom-services' => '/admin/reservations',
        'reservation-addons'          => '/admin/reservations',
        'durations'                    => '/admin/services/prices',
        'children-ranges'              => '/admin/services/prices',
        'dashboard'                    => '/admin/dashboard',
        'email-templates'              => '/admin/config/email-templates',
        'promo-codes'                  => '/admin/promo-codes',
        'payment-links'                => '/admin/reservations',
        'reservation-drafts'           => '/admin/abandoned-carts',
    ];

    /**
     * @return array{menu_uri:string, permission:string}|null
     */
    public function requirement(string $method, string $path, ?string $userId = null): ?array
    {
        $path = trim((string) parse_url($path, PHP_URL_PATH), '/');
        $path = preg_replace('#^api/#', '', $path) ?? $path;
        $segments = $path === '' ? [] : explode('/', $path);
        $resource = $segments[0] ?? '';

        if (!isset(self::RESOURCE_MENUS[$resource])) {
            return null;
        }

        // Every authenticated user may maintain their own profile/password.
        if ($resource === 'users' && $userId !== null) {
            if (($segments[1] ?? '') === 'change-password') {
                return ['menu_uri' => '@self', 'permission' => 'can_update'];
            }
            if (strtoupper($method) === 'PUT' && ($segments[1] ?? '') === $userId) {
                return ['menu_uri' => '@self', 'permission' => 'can_update'];
            }
        }

        return [
            'menu_uri' => self::RESOURCE_MENUS[$resource],
            'permission' => $this->permissionFor(strtoupper($method), $resource, $segments),
        ];
    }

    /**
     * @param array<int, object|array<string,mixed>> $permissions
     */
    public function allows(array $permissions, array $requirement): bool
    {
        if ($requirement['menu_uri'] === '@self') {
            return true;
        }

        foreach ($permissions as $permission) {
            if (is_array($permission)) {
                $row = $permission;
            } elseif (method_exists($permission, 'toArray')) {
                $row = $permission->toArray();
            } else {
                $row = get_object_vars($permission);
            }
            if (($row['menu_uri'] ?? null) !== $requirement['menu_uri']) {
                continue;
            }

            return filter_var($row[$requirement['permission']] ?? false, FILTER_VALIDATE_BOOLEAN);
        }

        return false;
    }

    /** @param list<string> $segments */
    private function permissionFor(string $method, string $resource, array $segments): string
    {
        if ($method === 'GET' || $method === 'HEAD') {
            return 'can_view';
        }

        // These endpoints edit children of a reservation. The Vue interface
        // exposes all of them under the reservation "update" permission.
        if (in_array($resource, [
            'reservation-custom-services', 'reservation-addons', 'payment-links',
        ], true)) {
            return 'can_update';
        }

        if ($method === 'DELETE') {
            return 'can_delete';
        }
        if ($method === 'PUT' || $method === 'PATCH') {
            return 'can_update';
        }

        $tail = implode('/', array_slice($segments, 1));
        if ($method === 'POST') {
            if (str_contains($tail, 'bulk-delete')) {
                return 'can_delete';
            }
            if ($resource === 'reservation-drafts' && str_ends_with($tail, '/follow-up')) {
                // Existing UI exposes follow-up to roles that have view-only access.
                return 'can_view';
            }
            if (($resource === 'services' && str_starts_with($tail, 'update/'))
                || ($resource === 'type-addons' && $tail !== '')
                || ($resource === 'users' && str_ends_with($tail, '/mfa/reset'))
                || ($resource === 'reservations' && $tail !== '')) {
                return 'can_update';
            }
            if ($resource === 'email-templates'
                && in_array($tail, ['preview', 'compose-preview'], true)) {
                return 'can_view';
            }
        }

        return 'can_create';
    }
}

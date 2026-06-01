<?php

namespace App\Modules\PettyCash\Support;

use App\Modules\PettyCash\Models\PettyUser;

class PettyAccess
{
    private static ?bool $permissionsTableExists = null;
    private static ?array $allPermissionKeys = null;
    /** @var array<int,array<int,string>> */
    private static array $resolvedPermissionsByUser = [];

    /**
     * @return array<string,string>
     */
    public static function roleOptions(): array
    {
        return [
            'admin' => 'Admin',
            'finance' => 'Finance',
            'customer_care' => 'Customer Care',
            'viewer' => 'Viewer',
        ];
    }

    public static function normalizeRole(?string $role): string
    {
        $normalized = strtolower(trim((string) $role));
        $normalized = str_replace([' ', '-'], '_', $normalized);

        return match ($normalized) {
            'accountant' => 'finance',
            'customer', 'customer_service', 'customer_services', 'customercare' => 'customer_care',
            default => $normalized,
        };
    }

    public static function isAdmin(?PettyUser $user): bool
    {
        return self::normalizeRole($user?->role) === 'admin';
    }

    /**
     * @return array<string,array{label:string,actions:array<string,string>}>
     */
    public static function permissionCatalog(): array
    {
        return [
            'dashboard' => [
                'label' => 'Dashboard',
                'actions' => [
                    'view' => 'Open dashboard',
                    'summary' => 'View summary mini dashboard cards',
                    'category_totals' => 'View category totals mini dashboard',
                    'breakdown' => 'View breakdown mini dashboard',
                ],
            ],
            'reports' => [
                'label' => 'Reports',
                'actions' => [
                    'view' => 'View and export reports',
                ],
            ],
            'ledger' => [
                'label' => 'Ledger',
                'actions' => [
                    'view' => 'View and export ledger',
                ],
            ],
            'credits' => [
                'label' => 'Credits',
                'actions' => [
                    'view' => 'View credits',
                    'create' => 'Create credits',
                    'edit' => 'Edit credits',
                    'delete' => 'Delete credits',
                ],
            ],
            'batches' => [
                'label' => 'Batches',
                'actions' => [
                    'view' => 'View batches',
                ],
            ],
            'bikes' => [
                'label' => 'Motor Vehicle Spendings',
                'actions' => [
                    'view' => 'View bike spendings',
                    'create' => 'Create bike spendings',
                    'edit' => 'Edit bike spendings',
                    'delete' => 'Delete bike spendings',
                ],
            ],
            'meals' => [
                'label' => 'Meal Spendings',
                'actions' => [
                    'view' => 'View meal spendings',
                    'create' => 'Create meal spendings',
                    'edit' => 'Edit meal spendings',
                    'delete' => 'Delete meal spendings',
                ],
            ],
            'meals_daily' => [
                'label' => 'Meal Daily Bills',
                'actions' => [
                    'view' => 'View daily meal spendings and bills',
                    'create' => 'Record daily meal spendings',
                    'edit_bill' => 'Edit daily meal bills',
                    'delete_bill' => 'Delete daily meal bills',
                    'record_payment' => 'Record meal bill payments',
                    'edit_payment' => 'Edit meal bill payments',
                    'delete_payment' => 'Delete meal bill payments',
                ],
            ],
            'tokens' => [
                'label' => 'Token Hostels',
                'actions' => [
                    'view' => 'View hostels and recent payments',
                    'create_hostel' => 'Add hostels',
                    'edit_hostel' => 'Edit hostel details',
                    'record_payment' => 'Record payments',
                    'edit_payment' => 'Edit payments',
                    'delete_payment' => 'Delete payments',
                ],
            ],
            'others' => [
                'label' => 'Other Spendings',
                'actions' => [
                    'view' => 'View other spendings',
                    'create' => 'Create other spendings',
                    'edit' => 'Edit other spendings',
                    'delete' => 'Delete other spendings',
                ],
            ],
            'bikes_master' => [
                'label' => 'Master Vehicles',
                'actions' => [
                    'view' => 'View vehicles',
                    'create' => 'Create vehicles',
                    'edit' => 'Edit vehicles',
                    'delete' => 'Delete vehicles',
                ],
            ],
            'respondents' => [
                'label' => 'Respondents',
                'actions' => [
                    'view' => 'View respondents',
                    'create' => 'Create respondents',
                    'edit' => 'Edit respondents',
                    'delete' => 'Delete respondents',
                ],
            ],
            'maintenances' => [
                'label' => 'Bike Service',
                'actions' => [
                    'view' => 'View maintenance pages',
                    'create_service' => 'Create service records',
                    'unroadworthy' => 'Mark/unmark unroadworthy',
                ],
            ],
            'notifications' => [
                'label' => 'Notifications',
                'actions' => [
                    'view' => 'View notifications',
                    'manage' => 'Manage contacts/templates/SMS settings',
                ],
            ],
            'profile' => [
                'label' => 'Profile',
                'actions' => [
                    'view' => 'View own profile and sessions',
                ],
            ],
            'settings' => [
                'label' => 'Settings',
                'actions' => [
                    'manage_users' => 'Manage users, roles, and permissions',
                ],
            ],
        ];
    }

    /**
     * @return array<string,string>
     */
    public static function routePermissions(): array
    {
        return [
            'petty.dashboard' => 'dashboard.view',

            'petty.reports.index' => 'reports.view',
            'petty.reports.bike.form' => 'reports.view',
            'petty.reports.bike.pdf' => 'reports.view',
            'petty.reports.respondent.form' => 'reports.view',
            'petty.reports.respondent.pdf' => 'reports.view',
            'petty.reports.batch.form' => 'reports.view',
            'petty.reports.batch.pdf' => 'reports.view',
            'petty.reports.general.form' => 'reports.view',
            'petty.reports.general.pdf' => 'reports.view',

            'petty.ledger.spendings' => 'ledger.view',
            'petty.ledger.spendings.pdf' => 'ledger.view',

            'petty.credits.index' => 'credits.view',
            'petty.credits.pdf' => 'credits.view',
            'petty.credits.create' => 'credits.create',
            'petty.credits.store' => 'credits.create',
            'petty.credits.edit' => 'credits.edit',
            'petty.credits.update' => 'credits.edit',
            'petty.credits.destroy' => 'credits.delete',

            'petty.batches.index' => 'batches.view',
            'petty.batches.show' => 'batches.view',

            'petty.bikes.index' => 'bikes.view',
            'petty.bikes.pdf' => 'bikes.view',
            'petty.bikes.byBike' => 'bikes.view',
            'petty.bikes.create' => 'bikes.create',
            'petty.bikes.store' => 'bikes.create',
            'petty.bikes.edit' => 'bikes.edit',
            'petty.bikes.update' => 'bikes.edit',
            'petty.bikes.destroy' => 'bikes.delete',

            'petty.meals.index' => 'meals.view',
            'petty.meals.pdf' => 'meals.view',
            'petty.meals.create' => 'meals.create',
            'petty.meals.store' => 'meals.create',
            'petty.meals.edit' => 'meals.edit',
            'petty.meals.update' => 'meals.edit',
            'petty.meals.destroy' => 'meals.delete',

            'petty.meals.daily.index' => 'meals_daily.view',
            'petty.meals.daily.calculate' => 'meals_daily.record_payment',
            'petty.meals.daily.store' => 'meals_daily.create',
            'petty.meals.daily.edit' => 'meals_daily.edit_bill',
            'petty.meals.daily.update' => 'meals_daily.edit_bill',
            'petty.meals.daily.destroy' => 'meals_daily.delete_bill',
            'petty.meals.daily.payments.store' => 'meals_daily.record_payment',
            'petty.meals.daily.payments.edit' => 'meals_daily.edit_payment',
            'petty.meals.daily.payments.update' => 'meals_daily.edit_payment',
            'petty.meals.daily.payments.destroy' => 'meals_daily.delete_payment',

            'petty.tokens.index' => 'tokens.view',
            'petty.tokens.agreements.apply_history' => 'tokens.edit_hostel',
            'petty.tokens.gateway.index' => 'tokens.view',
            'petty.tokens.gateway.show' => 'tokens.view',
            'petty.tokens.gateway.create' => 'tokens.record_payment',
            'petty.tokens.gateway.store' => 'tokens.record_payment',
            'petty.tokens.gateway.devices.store' => 'tokens.record_payment',
            'petty.tokens.gateway.devices.update' => 'tokens.record_payment',
            'petty.tokens.gateway.devices.destroy' => 'tokens.record_payment',
            'petty.tokens.gateway.devices.activate' => 'tokens.record_payment',
            'petty.tokens.gateway.test_sms' => 'tokens.record_payment',
            'petty.tokens.gateway.send' => 'tokens.record_payment',
            'petty.tokens.gateway.refresh_check' => 'tokens.record_payment',
            'petty.tokens.gateway.manual_link' => 'tokens.record_payment',
            'petty.tokens.gateway.confirm' => 'tokens.record_payment',
            'petty.tokens.gateway.queue_customer_sms' => 'tokens.record_payment',
            'petty.tokens.gateway.retry' => 'tokens.record_payment',
            'petty.tokens.gateway.cancel' => 'tokens.record_payment',
            'petty.tokens.hostels.gateway_pay' => 'tokens.record_payment',
            'petty.tokens.hostels.show' => 'tokens.view',
            'petty.tokens.pdf' => 'tokens.view',
            'petty.tokens.hostels.pdf' => 'tokens.view',
            'petty.tokens.create' => 'tokens.create_hostel',
            'petty.tokens.hostels.store' => 'tokens.create_hostel',
            'petty.tokens.hostels.search' => 'tokens.view',
            'petty.tokens.hostels.agreement' => 'tokens.view',
            'petty.tokens.hostels.agreement.update' => 'tokens.view',
            'petty.tokens.hostels.agreement.terminate' => 'tokens.edit_hostel',
            'petty.tokens.hostels.update' => 'tokens.edit_hostel',
            'petty.tokens.hostels.merge_ont' => 'tokens.edit_hostel',
            'petty.tokens.hostels.refresh_ont_sn' => 'tokens.edit_hostel',
            'petty.tokens.payments.store' => 'tokens.record_payment',
            'petty.tokens.payments.helper_sync' => 'tokens.record_payment',
            'petty.tokens.hostels.overpay.apply' => 'tokens.record_payment',
            'petty.tokens.hostels.pending_credits.store' => 'tokens.record_payment',
            'petty.tokens.hostels.pending_credits.sort' => 'tokens.record_payment',
            'petty.tokens.payments.edit' => 'tokens.edit_payment',
            'petty.tokens.payments.update' => 'tokens.edit_payment',
            'petty.tokens.payments.destroy' => 'tokens.delete_payment',

            'petty.others.index' => 'others.view',
            'petty.others.pdf' => 'others.view',
            'petty.others.create' => 'others.create',
            'petty.others.store' => 'others.create',
            'petty.others.edit' => 'others.edit',
            'petty.others.update' => 'others.edit',
            'petty.others.destroy' => 'others.delete',

            'petty.bikes_master.index' => 'bikes_master.view',
            'petty.bikes_master.create' => 'bikes_master.create',
            'petty.bikes_master.store' => 'bikes_master.create',
            'petty.bikes_master.edit' => 'bikes_master.edit',
            'petty.bikes_master.update' => 'bikes_master.edit',
            'petty.bikes_master.destroy' => 'bikes_master.delete',

            'petty.respondents.index' => 'respondents.view',
            'petty.respondents.create' => 'respondents.create',
            'petty.respondents.store' => 'respondents.create',
            'petty.respondents.show' => 'respondents.view',
            'petty.respondents.edit' => 'respondents.edit',
            'petty.respondents.update' => 'respondents.edit',
            'petty.respondents.destroy' => 'respondents.delete',
            'petty.respondents.card.generate' => 'respondents.edit',
            'petty.respondents.card.sms' => 'respondents.edit',

            'petty.maintenances.index' => 'maintenances.view',
            'petty.maintenances.show' => 'maintenances.view',
            'petty.maintenances.service.create' => 'maintenances.create_service',
            'petty.maintenances.service.store' => 'maintenances.create_service',
            'petty.maintenances.unroadworthy' => 'maintenances.unroadworthy',

            'petty.notifications.index' => 'notifications.view',
            'petty.notifications.read_all' => 'notifications.view',
            'petty.notifications.read_one' => 'notifications.view',
            'petty.notifications.auto_check' => 'notifications.manage',
            'petty.notifications.admin_contacts.store' => 'notifications.manage',
            'petty.notifications.admin_contacts.destroy' => 'notifications.manage',
            'petty.notifications.sms_templates.store' => 'notifications.manage',
            'petty.notifications.sms_templates.destroy' => 'notifications.manage',
            'petty.notifications.sms_settings.save' => 'notifications.manage',

            'petty.profile.index' => 'profile.view',
            'petty.profile.users.send_login_sms' => 'settings.manage_users',

            'petty.settings.index' => 'settings.manage_users',
            'petty.settings.users.store' => 'settings.manage_users',
            'petty.settings.users.update' => 'settings.manage_users',
        ];
    }

    public static function permissionForRoute(?string $routeName): ?string
    {
        if ($routeName === null || $routeName === '') {
            return null;
        }

        return self::routePermissions()[$routeName] ?? null;
    }

    /**
     * @return array<int,string>
     */
    public static function allPermissionKeys(): array
    {
        if (self::$allPermissionKeys !== null) {
            return self::$allPermissionKeys;
        }

        $keys = [];

        foreach (self::permissionCatalog() as $pageKey => $pageMeta) {
            foreach (array_keys((array) ($pageMeta['actions'] ?? [])) as $actionKey) {
                $keys[] = $pageKey . '.' . $actionKey;
            }
        }

        self::$allPermissionKeys = array_values(array_unique($keys));

        return self::$allPermissionKeys;
    }

    /**
     * @param array<int,mixed> $permissions
     * @return array<int,string>
     */
    public static function normalizePermissionList(array $permissions): array
    {
        $allowed = array_flip(self::allPermissionKeys());
        $normalized = [];

        foreach ($permissions as $permission) {
            $key = strtolower(trim((string) $permission));
            if ($key === '' || !isset($allowed[$key])) {
                continue;
            }

            $normalized[$key] = $key;
        }

        return array_values($normalized);
    }

    /**
     * Ensure page-level view permission exists whenever an action under that page is selected.
     *
     * @param array<int,mixed> $permissions
     * @return array<int,string>
     */
    public static function withImplicitViewPermissions(array $permissions): array
    {
        $normalized = self::normalizePermissionList($permissions);
        $map = array_fill_keys($normalized, true);
        $catalog = self::permissionCatalog();

        foreach ($normalized as $permission) {
            [$page, $action] = array_pad(explode('.', $permission, 2), 2, '');
            if ($page === '' || $action === '' || $action === 'view') {
                continue;
            }

            if (isset($catalog[$page]['actions']['view'])) {
                $map[$page . '.view'] = true;
            }
        }

        return array_keys($map);
    }

    /**
     * @return array<int,string>
     */
    public static function defaultPermissionsForRole(?string $role): array
    {
        $normalizedRole = self::normalizeRole($role);

        if ($normalizedRole === 'admin') {
            return self::allPermissionKeys();
        }

        if ($normalizedRole === 'finance') {
            return array_values(array_filter(
                self::allPermissionKeys(),
                fn (string $permission) => !in_array($permission, [
                    'settings.manage_users',
                ], true)
            ));
        }

        if ($normalizedRole === 'customer_care') {
            return [
                'dashboard.view',
                'dashboard.summary',
                'meals_daily.view',
                'meals_daily.create',
                'tokens.view',
                'profile.view',
            ];
        }

        // Default viewer profile is read-only.
        return [
            'dashboard.view',
            'dashboard.summary',
            'dashboard.category_totals',
            'dashboard.breakdown',
            'reports.view',
            'ledger.view',
            'credits.view',
            'batches.view',
            'bikes.view',
            'meals.view',
            'meals_daily.view',
            'meals_daily.edit_bill',
            'meals_daily.edit_payment',
            'tokens.view',
            'others.view',
            'bikes_master.view',
            'respondents.view',
            'maintenances.view',
            'notifications.view',
            'profile.view',
        ];
    }

    /**
     * @return array<int,string>|null
     */
    public static function explicitPermissionsForUser(?PettyUser $user): ?array
    {
        if (!$user || !self::permissionsTableExists()) {
            return null;
        }

        $profile = $user->relationLoaded('permissionProfile')
            ? $user->getRelation('permissionProfile')
            : $user->permissionProfile()->first();

        if (!$profile) {
            return null;
        }

        $permissions = is_array($profile->permissions)
            ? $profile->permissions
            : [];

        return self::normalizePermissionList($permissions);
    }

    /**
     * @return array<int,string>
     */
    public static function permissionsForUser(?PettyUser $user): array
    {
        if (!$user) {
            return [];
        }

        $userId = (int) ($user->id ?? 0);
        if ($userId > 0 && isset(self::$resolvedPermissionsByUser[$userId])) {
            return self::$resolvedPermissionsByUser[$userId];
        }

        if (self::isAdmin($user)) {
            $all = self::allPermissionKeys();
            if ($userId > 0) {
                self::$resolvedPermissionsByUser[$userId] = $all;
            }

            return $all;
        }

        $explicit = self::explicitPermissionsForUser($user);

        if ($explicit !== null) {
            if (!in_array('profile.view', $explicit, true)) {
                $explicit[] = 'profile.view';
            }

            $resolved = self::normalizePermissionList($explicit);
            if ($userId > 0) {
                self::$resolvedPermissionsByUser[$userId] = $resolved;
            }

            return $resolved;
        }

        $resolved = self::defaultPermissionsForRole((string) $user->role);
        if ($userId > 0) {
            self::$resolvedPermissionsByUser[$userId] = $resolved;
        }

        return $resolved;
    }

    public static function allows(?PettyUser $user, ?string $permission): bool
    {
        if (!$user) {
            return false;
        }

        $permissionKey = strtolower(trim((string) $permission));
        if ($permissionKey === '') {
            return true;
        }

        if (self::isAdmin($user)) {
            return true;
        }

        return in_array($permissionKey, self::permissionsForUser($user), true);
    }

    private static function permissionsTableExists(): bool
    {
        if (self::$permissionsTableExists === null) {
            self::$permissionsTableExists = PettyDatabase::schema()->hasTable('petty_user_permissions');
        }

        return self::$permissionsTableExists;
    }
}

<?php

namespace App\Enums;

enum UserRole: string
{
    case SUPER_ADMIN = 'super_admin';
    case ADMIN = 'admin';
    case USER = 'user';

    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Admin',
            self::ADMIN => 'Admin',
            self::USER => 'User',
        };
    }

    public function permissions(): array
    {
        return match ($this) {
            self::SUPER_ADMIN => [
                'users.view',
                'users.create',
                'users.update',
                'users.delete',
                'cars.view',
                'cars.create',
                'cars.update',
                'cars.delete',
                'cars-sales.view',
                'cars-sales.create',
                'cars-sales.update',
                'cars-sales.delete',
                'sale-payments.view',
                'sale-payments.create',
                'sale-payments.update',
                'sale-payments.delete',
                'expenses.view',
                'expenses.create',
                'expenses.update',
                'expenses.delete',
                'reports.view',
                'reports.create',
                'settings.manage',
            ],
            self::ADMIN => [
                'users.view',
                'users.create',
                'users.update',
                'cars.view',
                'cars.create',
                'cars.update',
                'cars.delete',
                'cars-sales.view',
                'cars-sales.create',
                'cars-sales.update',
                'cars-sales.delete',
                'sale-payments.view',
                'sale-payments.create',
                'sale-payments.update',
                'sale-payments.delete',
                'expenses.view',
                'expenses.create',
                'expenses.update',
                'expenses.delete',
                'reports.view',
                'reports.create',
            ],
            self::USER => [
                'cars.view',
                'cars.create',
                'cars.update',
                'expenses.view',
                'expenses.create',
                'expenses.update',
                'reports.view',
                'cars-sales.view',
                'cars-sales.create',
                'cars-sales.update',
                'sale-payments.view',
                'sale-payments.create',
                'sale-payments.update'
            ],
        };
    }
}

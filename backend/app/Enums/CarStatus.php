<?php

namespace App\Enums;

enum CarStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case MAINTENANCE = 'maintenance';
    case SOLD = 'sold';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::MAINTENANCE => 'Under Maintenance',
            self::SOLD => 'Sold'
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::INACTIVE => 'danger',
            self::MAINTENANCE => 'warning',
            self::SOLD => 'info',
        };
    }
}

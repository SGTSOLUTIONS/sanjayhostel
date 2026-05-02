<?php

namespace App\Enums;

enum ActiveStatusEnum: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::ACTIVE => 'bg-success',
            self::INACTIVE => 'bg-secondary',
        };
    }
}
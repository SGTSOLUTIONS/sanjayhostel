<?php

namespace App\Enums;

enum RoleEnum: string
{
    // System roles
    case SUPERADMIN = 'superadmin';
    case ADMIN = 'admin';


    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match($this) {
            self::SUPERADMIN => 'Super Admin',
            self::ADMIN  => 'Administrator',

        };
    }
}

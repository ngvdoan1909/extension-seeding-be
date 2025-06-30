<?php
namespace App\Enums;

enum RoleEnum: int
{
    case ADMIN = 1;
    case MEMBER = 2;

    public function label()
    {
        return match ($this) {
            self::ADMIN => 'ADMIN',
            self::MEMBER => 'MEMBER',
        };
    }
}
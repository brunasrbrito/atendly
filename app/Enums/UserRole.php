<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Tecnico = 'tecnico';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Tecnico => 'Técnico',
        };
    }
}

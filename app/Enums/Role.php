<?php

namespace App\Enums;

enum Role: string
{
    case Root = 'root';

    case Admin = 'admin';

    /**
     * Get the human-readable label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::Root => 'Superusuario',
            self::Admin => 'Administrador',
        };
    }
}

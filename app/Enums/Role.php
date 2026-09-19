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
            self::Root => 'Superuser',
            self::Admin => 'Administrator',
        };
    }

    /**
     * Get the roles that can be assigned through user management.
     *
     * Root is never assignable, so no additional superusers can be created.
     *
     * @return array<int, self>
     */
    public static function assignable(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $role): bool => $role !== self::Root,
        ));
    }

    /**
     * Get the values of the roles that can be assigned through user management.
     *
     * @return array<int, string>
     */
    public static function assignableValues(): array
    {
        return array_map(fn (self $role): string => $role->value, self::assignable());
    }
}

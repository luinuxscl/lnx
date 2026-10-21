<?php

namespace App\Support\Notifications;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Notification as BaseNotification;
use InvalidArgumentException;

/**
 * In-memory registry of the notification types declared by installed packages.
 *
 * A package's service provider declares its own notification types during
 * boot; the kit never renders a type just because a package sits in `vendor/`.
 * The existing notification center reads the registry to build the message and
 * the link of each notification, so a declared type is shown, navigates and is
 * marked as read through the same center. A type that is not declared keeps the
 * safe generic fallback: its stored data is never rendered.
 */
class NotificationTypeRegistry
{
    /**
     * Declared types, keyed by their notification class.
     *
     * @var array<string, array{message: callable, url: callable|null, help: callable|null, permission: string|null}>
     */
    private array $types = [];

    /**
     * Declare the notification types owned by a package.
     *
     * Each type is keyed by the notification class the package sends and
     * declares a `message` callable (and optionally a `url` callable, a
     * `help` callable that returns a help center slug, and a `permission` the
     * identity must hold to get the link).
     *
     * @param  array<array-key, mixed>  $types
     *
     * @throws InvalidArgumentException when a type is not a notification class or lacks a message
     */
    public function register(string $source, array $types): void
    {
        if ($source === '') {
            throw new InvalidArgumentException('The package source must be a non-empty string.');
        }

        foreach ($types as $class => $definition) {
            if (! is_string($class) || ! is_subclass_of($class, BaseNotification::class)) {
                $name = is_string($class) ? $class : get_debug_type($class);

                throw new InvalidArgumentException("Notification [{$name}] must extend ".BaseNotification::class.'.');
            }

            if (! is_array($definition) || ! isset($definition['message']) || ! is_callable($definition['message'])) {
                throw new InvalidArgumentException("Notification [{$class}] must declare a callable message.");
            }

            $url = $definition['url'] ?? null;
            if ($url !== null && ! is_callable($url)) {
                throw new InvalidArgumentException("Notification [{$class}] must declare a callable url or null.");
            }

            $help = $definition['help'] ?? null;
            if ($help !== null && ! is_callable($help)) {
                throw new InvalidArgumentException("Notification [{$class}] must declare a callable help or null.");
            }

            $permission = $definition['permission'] ?? null;
            if ($permission !== null && (! is_string($permission) || $permission === '')) {
                throw new InvalidArgumentException("Notification [{$class}] must declare a non-empty permission or null.");
            }

            $this->types[$class] = [
                'message' => $definition['message'],
                'url' => $url,
                'help' => $help,
                'permission' => $permission,
            ];
        }
    }

    /**
     * Whether a package declared the given notification type.
     */
    public function has(string $class): bool
    {
        return array_key_exists($class, $this->types);
    }

    /**
     * The declared message for a notification, or null when the type is not declared.
     */
    public function message(DatabaseNotification $notification): ?string
    {
        $type = $this->types[$notification->type] ?? null;

        if ($type === null) {
            return null;
        }

        $message = call_user_func($type['message'], $notification);

        return is_string($message) ? $message : null;
    }

    /**
     * The help center slug declared for a notification, or null when the type
     * does not declare one. Visibility is checked by the caller, which renders
     * the link only when the identity may read the entry.
     */
    public function helpTopic(DatabaseNotification $notification): ?string
    {
        $type = $this->types[$notification->type] ?? null;

        if ($type === null || $type['help'] === null) {
            return null;
        }

        $slug = call_user_func($type['help'], $notification);

        return is_string($slug) && $slug !== '' ? $slug : null;
    }

    /**
     * The declared link for a notification, or null when none is declared or
     * the identity lacks the declared permission.
     */
    public function url(DatabaseNotification $notification, User $user): ?string
    {
        $type = $this->types[$notification->type] ?? null;

        if ($type === null || $type['url'] === null) {
            return null;
        }

        if ($type['permission'] !== null && ! $user->can($type['permission'])) {
            return null;
        }

        $url = call_user_func($type['url'], $notification);

        return is_string($url) ? $url : null;
    }
}

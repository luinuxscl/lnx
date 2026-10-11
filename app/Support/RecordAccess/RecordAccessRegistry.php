<?php

namespace App\Support\RecordAccess;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * In-memory registry of the access-controlled models declared by packages.
 *
 * A package's service provider registers the models it filters by identity
 * during boot; the kit never scopes a model just because a package sits in
 * `vendor/`. Registering a class that does not implement {@see AccessControlled}
 * is a detectable error, so a declaration that would silently expose records
 * fails loudly instead.
 */
class RecordAccessRegistry
{
    /**
     * Registered model class names, keyed by the package source that declared them.
     *
     * @var array<string, list<class-string<Model&AccessControlled>>>
     */
    private array $models = [];

    /**
     * Register the access-controlled models declared by a package.
     *
     * @param  array<array-key, mixed>  $models
     *
     * @throws InvalidArgumentException when an entry is not an access-controlled Eloquent model
     */
    public function register(string $source, array $models): void
    {
        if ($source === '') {
            throw new InvalidArgumentException('The package source must be a non-empty string.');
        }

        foreach ($models as $model) {
            if (
                ! is_string($model)
                || ! is_subclass_of($model, Model::class)
                || ! is_subclass_of($model, AccessControlled::class)
            ) {
                $name = is_string($model) ? $model : get_debug_type($model);

                throw new InvalidArgumentException(
                    "Registered model [{$name}] must extend ".Model::class.' and implement '.AccessControlled::class.'.'
                );
            }

            $this->models[$source] ??= [];

            if (! in_array($model, $this->models[$source], true)) {
                $this->models[$source][] = $model;
            }
        }
    }

    /**
     * Every registered model class, deduplicated and in registration order.
     *
     * @return list<class-string<Model&AccessControlled>>
     */
    public function all(): array
    {
        $models = [];

        foreach ($this->models as $registered) {
            foreach ($registered as $model) {
                if (! in_array($model, $models, true)) {
                    $models[] = $model;
                }
            }
        }

        return $models;
    }
}

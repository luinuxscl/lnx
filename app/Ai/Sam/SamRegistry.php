<?php

namespace App\Ai\Sam;

use App\Models\User;
use InvalidArgumentException;
use Livewire\Component;

/**
 * In-memory registry of the contributions installed packages declare for Sam.
 *
 * A package registers its contributor, its identity profile and its surface
 * during boot; the kit never exposes them just because the package sits in
 * `vendor/`. Declarations are validated and registering the same source twice
 * is idempotent: the last declaration wins.
 */
class SamRegistry
{
    /**
     * Registered contributors, keyed by their source.
     *
     * @var array<string, SamContributor>
     */
    private array $contributors = [];

    private ?SamProfile $profile = null;

    private ?string $surface = null;

    /**
     * Registered preference sources, keyed by their package.
     *
     * @var array<string, SamPreferencesPresenter>
     */
    private array $preferences = [];

    /**
     * Declare the contributor a module contributes to Sam.
     *
     * @param  class-string  $contributor
     *
     * @throws InvalidArgumentException when the source is empty, does not match
     *                                  the contributor, or the class does not
     *                                  implement the contract
     */
    public function register(string $source, string $contributor): void
    {
        $source = $this->normalizeSource($source);

        $instance = $this->resolve($contributor, SamContributor::class, 'contributor');

        $declared = $this->normalizeSource($instance->source());

        if ($declared !== $source) {
            throw new InvalidArgumentException(
                "The registered source [{$source}] does not match SamContributor::source() [{$declared}]."
            );
        }

        $this->contributors[$source] = $instance;
    }

    /**
     * Declare the identity profile of the agent.
     *
     * @param  class-string  $profile
     */
    public function registerProfile(string $source, string $profile): void
    {
        $this->normalizeSource($source);

        $this->profile = $this->resolve($profile, SamProfile::class, 'profile');
    }

    /**
     * Declare the Livewire component that mounts the global panel.
     *
     * @param  class-string  $component
     */
    public function registerSurface(string $source, string $component): void
    {
        $this->normalizeSource($source);

        if (! class_exists($component)) {
            throw new InvalidArgumentException('The Sam surface must be an existing Livewire component class.');
        }

        if (! is_subclass_of($component, Component::class)) {
            throw new InvalidArgumentException(
                "The Sam surface [{$component}] must extend ".Component::class.'.'
            );
        }

        $this->surface = $component;
    }

    /**
     * Declare the source that stores the person's preferences.
     *
     * @param  class-string  $presenter
     */
    public function registerPreferences(string $source, string $presenter): void
    {
        $this->normalizeSource($source);

        $this->preferences[$source] = $this->resolve($presenter, SamPreferencesPresenter::class, 'preferences');
    }

    /**
     * Preferences of the actor, or the defaults when no source answers.
     */
    public function preferencesFor(User $actor): SamPreferences
    {
        foreach ($this->preferences as $presenter) {
            $preferences = $presenter->preferences($actor);

            if ($preferences instanceof SamPreferences) {
                return $preferences;
            }
        }

        return SamPreferences::defaults();
    }

    public function has(string $source): bool
    {
        return array_key_exists($this->normalizeSource($source), $this->contributors);
    }

    public function contributor(string $source): ?SamContributor
    {
        return $this->contributors[$this->normalizeSource($source)] ?? null;
    }

    /**
     * @return array<string, SamContributor>
     */
    public function contributors(): array
    {
        return $this->contributors;
    }

    public function profile(): ?SamProfile
    {
        return $this->profile;
    }

    public function surface(): ?string
    {
        return $this->surface;
    }

    /**
     * Reset the registry. Used by the test suite between cases.
     */
    public function flush(): void
    {
        $this->contributors = [];
        $this->profile = null;
        $this->surface = null;
        $this->preferences = [];
    }

    private function normalizeSource(string $source): string
    {
        $source = trim($source);

        if ($source === '') {
            throw new InvalidArgumentException('The package source must be a non-empty string.');
        }

        return $source;
    }

    /**
     * @template T of object
     *
     * @param  class-string  $class
     * @param  class-string<T>  $contract
     * @return T
     */
    private function resolve(string $class, string $contract, string $kind): object
    {
        if (! class_exists($class)) {
            throw new InvalidArgumentException("The Sam {$kind} [{$class}] does not exist.");
        }

        $instance = app($class);

        if (! $instance instanceof $contract) {
            throw new InvalidArgumentException(
                "The Sam {$kind} [{$class}] must implement {$contract}."
            );
        }

        return $instance;
    }
}

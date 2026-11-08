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
     * Declared capabilities, keyed by their global key.
     *
     * A capability is declared once by its module and read by every surface,
     * so the registry refuses a duplicate instead of letting two modules
     * describe the same operation differently.
     *
     * @var array<string, SamCapability>
     */
    private array $capabilities = [];

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

        $this->declareCapabilities($source, $instance);
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
     * Capabilities declared by the installed modules, keyed by their key.
     *
     * @return array<string, SamCapability>
     */
    public function capabilities(): array
    {
        return $this->capabilities;
    }

    /**
     * Capabilities of one module, keyed by their key.
     *
     * @return array<string, SamCapability>
     */
    public function capabilitiesFor(string $source): array
    {
        $source = $this->normalizeSource($source);

        return array_filter(
            $this->capabilities,
            fn (SamCapability $capability): bool => $capability->source === $source,
        );
    }

    public function capability(string $key): ?SamCapability
    {
        return $this->capabilities[trim($key)] ?? null;
    }

    /**
     * Capability implemented by one tool class, when the module declared it.
     *
     * A tool derives its risk, its minimum mode and its undo from here, so the
     * declaration wins. Without a manifest the tool keeps its own constants and
     * `sam:doctor` reports the pending migration.
     */
    public function capabilityForHandler(string $handler): ?SamCapability
    {
        foreach ($this->capabilities as $capability) {
            if (in_array($handler, $capability->handlers, true)) {
                return $capability;
            }
        }

        return null;
    }

    /**
     * Whether a module already declares capabilities.
     *
     * `sam:doctor` uses it to tell a module pending migration from one that
     * simply has no operations, which is not the same thing.
     */
    public function declaresCapabilities(string $source): bool
    {
        return $this->capabilitiesFor($source) !== [];
    }

    /**
     * Read the manifest of a module and reject an inconsistent declaration.
     *
     * The prefix and the coherence of risk and confirmation are validated by
     * {@see SamCapability}; here we only check that a key is unique across
     * modules and that the declaration belongs to the contributor.
     *
     * @throws InvalidArgumentException when a capability is repeated or
     *                                  declares another module as its source
     */
    private function declareCapabilities(string $source, SamContributor $contributor): void
    {
        // Registering a source twice is idempotent: the last declaration wins,
        // so the previous manifest of the same module is dropped first.
        foreach ($this->capabilities as $key => $capability) {
            if ($capability->source === $source) {
                unset($this->capabilities[$key]);
            }
        }

        if (! $contributor instanceof SamCapabilityProvider) {
            return;
        }

        foreach ($contributor->capabilities() as $capability) {
            if ($capability->source !== $source) {
                throw new InvalidArgumentException(
                    "The capability [{$capability->key}] declares the source "
                    ."[{$capability->source}] inside the contributor [{$source}]."
                );
            }

            if (isset($this->capabilities[$capability->key])) {
                throw new InvalidArgumentException(
                    "The capability [{$capability->key}] is declared twice."
                );
            }

            $this->capabilities[$capability->key] = $capability;
        }
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
        $this->capabilities = [];
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

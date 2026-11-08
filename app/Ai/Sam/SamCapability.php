<?php

namespace App\Ai\Sam;

use InvalidArgumentException;

/**
 * One operation a module exposes to the surfaces that act on its behalf.
 *
 * It describes the operation, not a transport: risk, permission and minimum
 * mode are declared once here and read by Sam, MCP, the HTTP API and the
 * module UI. A surface may harden the confirmation; it can never relax it.
 */
final class SamCapability
{
    /**
     * Surfaces that may expose a capability today.
     *
     * @var list<string>
     */
    public const SURFACES = ['sam', 'mcp', 'api', 'ui'];

    public readonly bool $requiresPhrase;

    /**
     * @param  list<string>  $surfaces
     * @param  list<class-string>  $handlers  Tools that implement the capability.
     */
    public function __construct(
        public readonly string $key,
        public readonly string $source,
        public readonly string $label,
        public readonly SamCapabilityKind $kind,
        public readonly ?string $permission = null,
        public readonly ?SamRisk $risk = null,
        public readonly SamMode $minMode = SamMode::Draft,
        ?bool $requiresPhrase = null,
        public readonly bool $undoable = false,
        public readonly string $auditAction = 'read',
        public readonly array $surfaces = ['sam'],
        public readonly array $handlers = [],
    ) {
        $key = trim($this->key);

        if ($key === '') {
            throw new InvalidArgumentException('A Sam capability must declare a non-empty key.');
        }

        if (trim($this->source) === '') {
            throw new InvalidArgumentException("The capability [{$key}] must declare its source.");
        }

        $prefix = $this->source.'.';

        if (! str_starts_with($key, $prefix)) {
            throw new InvalidArgumentException(
                "The capability [{$key}] must be prefixed with its source [{$prefix}]."
            );
        }

        $this->assertSurfaceList($key);
        $this->assertHandlerList($key);

        if ($this->kind === SamCapabilityKind::Read) {
            if ($this->risk !== null) {
                throw new InvalidArgumentException(
                    "The read capability [{$key}] cannot declare a confirmation risk."
                );
            }

            if ($this->undoable) {
                throw new InvalidArgumentException(
                    "The read capability [{$key}] cannot declare an undo."
                );
            }
        }

        if ($this->kind === SamCapabilityKind::Propose) {
            if ($this->risk === null) {
                throw new InvalidArgumentException(
                    "The proposal [{$key}] must declare the risk of confirming it."
                );
            }

            if ($this->risk === SamRisk::Reversible && ! $this->undoable) {
                throw new InvalidArgumentException(
                    "The proposal [{$key}] is reversible but declares no undo; "
                    .'a reversible change is the only one that may skip the phrase.'
                );
            }
        }

        if ($requiresPhrase === true && $this->kind === SamCapabilityKind::Read) {
            throw new InvalidArgumentException(
                "The read capability [{$key}] cannot require a confirmation phrase."
            );
        }

        // A surface may only harden the confirmation, so a declared phrase is
        // valid here; the derived default is what the risk dictates.
        $this->requiresPhrase = $requiresPhrase ?? ($this->risk?->requiresPhrase() ?? false);
    }

    /**
     * @param  list<string>  $surfaces
     * @param  list<class-string>  $handlers
     */
    public static function read(
        string $key,
        string $source,
        string $label,
        ?string $permission = null,
        array $surfaces = ['sam'],
        array $handlers = [],
    ): self {
        return new self(
            key: $key,
            source: $source,
            label: $label,
            kind: SamCapabilityKind::Read,
            permission: $permission,
            risk: null,
            minMode: SamMode::ReadOnly,
            requiresPhrase: false,
            undoable: false,
            auditAction: 'read',
            surfaces: $surfaces,
            handlers: $handlers,
        );
    }

    /**
     * @param  list<string>  $surfaces
     * @param  list<class-string>  $handlers
     */
    public static function propose(
        string $key,
        string $source,
        string $label,
        SamRisk $risk,
        bool $undoable = false,
        SamMode $minMode = SamMode::Draft,
        ?string $permission = null,
        ?bool $requiresPhrase = null,
        array $surfaces = ['sam'],
        array $handlers = [],
    ): self {
        return new self(
            key: $key,
            source: $source,
            label: $label,
            kind: SamCapabilityKind::Propose,
            permission: $permission,
            risk: $risk,
            minMode: $minMode,
            requiresPhrase: $requiresPhrase,
            undoable: $undoable,
            auditAction: 'propose',
            surfaces: $surfaces,
            handlers: $handlers,
        );
    }

    /**
     * Whether the capability is exposed by the given surface.
     */
    public function exposes(string $surface): bool
    {
        return in_array($surface, $this->surfaces, true);
    }

    /**
     * How a surface confirms the capability, in the words of the matrix.
     */
    public function confirmation(): string
    {
        if ($this->kind === SamCapabilityKind::Read) {
            return 'none';
        }

        return $this->requiresPhrase ? 'phrase' : 'click';
    }

    /**
     * Bounded representation for the catalogue and the audit context.
     *
     * @return array{
     *     key: string,
     *     source: string,
     *     label: string,
     *     kind: string,
     *     permission: ?string,
     *     risk: ?string,
     *     min_mode: string,
     *     requires_phrase: bool,
     *     undoable: bool,
     *     audit_action: string,
     *     surfaces: list<string>,
     *     handlers: list<class-string>
     * }
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'source' => $this->source,
            'label' => $this->label,
            'kind' => $this->kind->value,
            'permission' => $this->permission,
            'risk' => $this->risk?->value,
            'min_mode' => $this->minMode->value,
            'requires_phrase' => $this->requiresPhrase,
            'undoable' => $this->undoable,
            'audit_action' => $this->auditAction,
            'surfaces' => $this->surfaces,
            'handlers' => $this->handlers,
        ];
    }

    private function assertSurfaceList(string $key): void
    {
        if ($this->surfaces === []) {
            throw new InvalidArgumentException(
                "The capability [{$key}] must be exposed by at least one surface."
            );
        }

        foreach ($this->surfaces as $surface) {
            if (! in_array($surface, self::SURFACES, true)) {
                throw new InvalidArgumentException(
                    "The capability [{$key}] declares an unknown surface [{$surface}]."
                );
            }
        }
    }

    private function assertHandlerList(string $key): void
    {
        foreach ($this->handlers as $handler) {
            if (! class_exists($handler)) {
                throw new InvalidArgumentException(
                    "The capability [{$key}] declares an unknown handler [{$handler}]."
                );
            }
        }
    }
}

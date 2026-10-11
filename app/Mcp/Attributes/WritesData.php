<?php

namespace App\Mcp\Attributes;

use App\Mcp\Tools\WriteTool;
use Attribute;
use InvalidArgumentException;

/**
 * Declares that an MCP tool writes data.
 *
 * Every {@see WriteTool} must carry this attribute: a tool that
 * writes without declaring it is a detectable error, never a silent oversight.
 * The declaration drives the discovery (a token needs the declared permission
 * to see the tool), the audit record (resource and operation) and the
 * idempotency requirement.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class WritesData
{
    public const CREATE = 'create';

    public const UPDATE = 'update';

    public const ARCHIVE = 'archive';

    /**
     * @var list<string>
     */
    public const OPERATIONS = [self::CREATE, self::UPDATE, self::ARCHIVE];

    /**
     * @param  string  $resource  Domain resource written (e.g. `document`).
     * @param  string  $operation  One of create, update or archive. Deleting is not allowed.
     * @param  string|null  $permission  Permission a token must hold to see and invoke the tool.
     * @param  bool  $idempotent  Whether the tool requires an idempotency key and replays safe retries.
     */
    public function __construct(
        public readonly string $resource,
        public readonly string $operation,
        public readonly ?string $permission = null,
        public readonly bool $idempotent = false,
    ) {
        if ($this->resource === '') {
            throw new InvalidArgumentException('The written resource must be a non-empty string.');
        }

        if (! in_array($this->operation, self::OPERATIONS, true)) {
            throw new InvalidArgumentException(
                "Invalid write operation [{$this->operation}]; allowed: ".implode(', ', self::OPERATIONS).'.'
            );
        }

        if ($this->permission !== null && $this->permission === '') {
            throw new InvalidArgumentException('The declared permission must be a non-empty string or null.');
        }
    }
}

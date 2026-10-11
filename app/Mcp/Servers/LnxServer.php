<?php

namespace App\Mcp\Servers;

use App\Mcp\McpToolRegistry;
use App\Mcp\Tools\GetAdministrativeActivityTool;
use App\Mcp\Tools\GetPublicConfigurationTool;
use App\Mcp\Tools\ListCapabilitiesTool;
use App\Mcp\Tools\SearchUsersTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\ServerContext;
use Laravel\Mcp\Server\Tool;

#[Name('lnx')]
#[Version('1.0.0')]
#[Instructions(<<<'MARKDOWN'
    Access to this lnx application for authorized agents.

    Read capabilities are limited by the connected identity's role. The server
    is read-only by default: a package may declare write tools, but they are
    only registered and announced when the deployment explicitly enables
    writes, and only for identities holding the declared permission. The server
    never exposes PHP, SQL or shell execution, nor generic file access. Use the
    discovery tool to list the capabilities your identity can actually invoke.
    MARKDOWN)]
class LnxServer extends Server
{
    /**
     * The tools shipped with the kit. Package-declared tools are merged in
     * {@see self::createContext()} so the server keeps no fixed list of them.
     *
     * @var array<int, class-string<Tool>>
     */
    protected array $tools = [
        ListCapabilitiesTool::class,
        GetPublicConfigurationTool::class,
        SearchUsersTool::class,
        GetAdministrativeActivityTool::class,
    ];

    /**
     * Build the server context with the kit tools plus the ones declared by
     * installed packages.
     */
    public function createContext(): ServerContext
    {
        $declared = app(McpToolRegistry::class)->all();

        $this->tools = array_values(array_unique([...$this->tools, ...$declared]));

        return parent::createContext();
    }
}

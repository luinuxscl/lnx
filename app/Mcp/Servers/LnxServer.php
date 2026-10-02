<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\GetAdministrativeActivityTool;
use App\Mcp\Tools\GetPublicConfigurationTool;
use App\Mcp\Tools\ListCapabilitiesTool;
use App\Mcp\Tools\SearchUsersTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Tool;

#[Name('lnx')]
#[Version('1.0.0')]
#[Instructions(<<<'MARKDOWN'
    Read-only access to this lnx application for authorized agents.

    Every connection is authenticated with a revocable Bearer access token tied
    to a user. Capabilities are limited by that user's role: users can be
    searched by root and admins, while administrative activity and the public
    configuration are only available to root.

    The server never exposes PHP, SQL or shell execution, nor generic file
    access. Use the discovery tool to list the capabilities your identity can
    actually invoke.
    MARKDOWN)]
class LnxServer extends Server
{
    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<Tool>>
     */
    protected array $tools = [
        ListCapabilitiesTool::class,
        GetPublicConfigurationTool::class,
        SearchUsersTool::class,
        GetAdministrativeActivityTool::class,
    ];
}

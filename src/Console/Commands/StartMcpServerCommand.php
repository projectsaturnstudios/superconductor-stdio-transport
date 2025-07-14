<?php

namespace MCP\Transports\Stdio\Console\Commands;

use Illuminate\Console\Command;
use MCP\Transports\Stdio\StdioTransport;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand('mcp:serve', 'Start the MCP server using the Stdio transport protocol.')]
class StartMcpServerCommand extends Command
{
    protected $signature = 'mcp:serve {session_id?}';
    public function handle(): int
    {
        $session_id = $this->argument('session_id') ?? new_uuid();
        (new StdioTransport())->listen($session_id);
        return 0;
    }
}

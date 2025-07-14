<?php

namespace MCP\Transports\Stdio;

use MCP\Transports\Stdio\Actions\Sagas\TransportProtocol\InitializeStdioTransportNode;

class StdioTransport
{
    public function listen(string $session_id): void
    {
        $shared = [];
        flow(new InitializeStdioTransportNode($session_id), $shared);
    }
}

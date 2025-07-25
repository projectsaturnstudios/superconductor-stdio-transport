<?php

namespace MCP\Transports\Stdio;

use MCP\Sessions\ClientSession;
use MCP\Transports\Stdio\Actions\Sagas\ClientConnection\InitializeClientConnectionNode;
use MCP\Transports\Stdio\Actions\Sagas\TransportProtocol\InitializeStdioTransportNode;

class StdioTransport
{
    public function listen(string $session_id): void
    {
        $shared = [];
        flow(new InitializeStdioTransportNode($session_id), $shared);
    }

    public function connect(ClientSession $session): ClientSession
    {
        $shared = ['session' => $session];
        flow(new InitializeClientConnectionNode, $shared);

        return $shared['session'];
    }
}

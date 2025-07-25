<?php

namespace MCP\Transports\Stdio\Drivers;

use MCP\Sessions\ClientSession;
use MCP\Drivers\ClientConnectionDriver;
use MCP\Transports\Stdio\Actions\Sagas\ClientInteraction\SendCallToolRequestNode;
use MCP\Transports\Stdio\Actions\Sagas\ClientInteraction\SendListToolsRequestNode;
use MCP\Transports\Stdio\StdioTransport;

class StdioClientConnectionDriver extends ClientConnectionDriver
{
    /**
     * Connects to the server using standard input/output.
     *
     * @param ClientSession $session
     * @return ClientSession
     */
    public function connect(ClientSession $session): ClientSession
    {
        return (new StdioTransport)->connect($session);
    }

    public function list_tools(ClientSession $session, array $connection): array
    {
        $shared = [
            'session' => $session,
            'pipes' => $connection['pipes'],
            'process' => $connection['process'],
        ];
        return flow(new SendListToolsRequestNode, $shared);
    }

    public function call_tool(ClientSession $session, array $connection, array $content): array
    {
        $shared = [
            'session' => $session,
            'pipes' => $connection['pipes'],
            'process' => $connection['process'],
            'content' => $content,
        ];

        return flow(new SendCallToolRequestNode(), $shared);
    }
}


<?php

namespace MCP\Transports\Stdio\Actions\Sagas\ClientConnection;

use MCP\Sessions\ClientSession;
use ProjectSaturnStudios\PocketFlow\Node;

class InitializeClientConnectionNode extends Node
{
    public function prep(mixed &$shared): mixed
    {
        return $shared['session'];
    }

    public function exec(mixed $prep_res): string
    {
        /** @var ClientSession $session */
        $session = $prep_res;
        $command = escapeshellcmd($session->server['command']);
        $args = array_map('escapeshellarg', $session->server['args']);
        return $command . ' ' . implode(' ', $args);
    }
    public function post(mixed &$shared, mixed $prep_res, mixed $exec_res): mixed
    {
        $shared['command'] = $exec_res;
        $shared['session'] = $shared['session']->setProtocol('stdio');
        $this->next(new StartClientStreamNode, 'stream');
        return 'stream';
    }
}

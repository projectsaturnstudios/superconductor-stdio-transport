<?php

namespace MCP\Transports\Stdio\Actions\Sagas\ClientConnection;

use MCP\Sessions\ClientSession;
use MCP\Transports\Stdio\Jobs\ClientCommStream;
use ProjectSaturnStudios\PocketFlow\Node;

class StartClientStreamNode extends Node
{
    public function prep(mixed &$shared): mixed
    {
        return [
            'session' => $shared['session'],
            'command' => $shared['command']
        ];
    }

    public function exec(mixed $prep_res): array
    {
        $pipes = [];
        $descriptorSpec = [
            0 => ['pipe', 'r'],  // STDIN for the server process.
            1 => ['pipe', 'w'],  // STDOUT from the server process.
            2 => ['pipe', 'w'],  // STDERR from the server process.
        ];
        $env = $prep_res['session']->server['env'] ?? null;
        if (!isset($env['PATH'])) {
            $env['PATH'] = $_SERVER['PATH'] ?? getenv('PATH');
        }

        $command = $prep_res['command'];
        $process = proc_open($command, $descriptorSpec, $pipes, null, $env);

        if ($process === false || !is_resource($process)) {
            throw new \RuntimeException("Failed to start process: $command");
        }

        // Set non-blocking mode for STDOUT and STDERR.
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        //ClientCommStream::dispatch($prep_res)->onQueue('mcp');

        return [
            'process' => $process,
            'pipes' => $pipes
        ];
    }
    public function post(mixed &$shared, mixed $prep_res, mixed $exec_res): mixed
    {
        $shared['process'] = $exec_res['process'];
        $shared['pipes'] = $exec_res['pipes'];
        $this->next(new SendInitializeRequestNode, 'send-init');
        return 'send-init';
    }
}

<?php

namespace MCP\Transports\Stdio\Actions\Sagas\ClientConnection;

use ProjectSaturnStudios\PocketFlow\Node;

class SendInitializedNotificationNode extends Node
{
    public function prep(mixed &$shared): mixed
    {
        // @todo - change this to an RPC controller request
        $payload = [
            'jsonrpc' => '2.0',
            'method' => 'notifications/initialized',
        ];

        return [
            'payload' => $payload,
            'pipes' => $shared['pipes'],
            'process' => $shared['process'],
        ];
    }

    public function exec(mixed $prep_res): mixed
    {
        $pipe = $prep_res['pipes'][0]; // STDIN pipe
        $process = $prep_res['process'];

        if (!is_resource($pipe)) {
            echo("Write pipe is no longer a valid resource\n");
            throw new \RuntimeException('Write pipe is invalid');
        }

        $status = proc_get_status($process);

        if (!$status['running']) {
            echo("Server process has terminated. Exit code: " . $status['exitcode']."\n");
            throw new \RuntimeException('Server process has terminated');
        }

        $json = json_encode($prep_res['payload'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new \RuntimeException('Failed to encode JsonRpcMessage to JSON: ' . json_last_error_msg());
        }

        $json .= "\n"; // Append newline as message delimiter.

        $bytesWritten = fwrite($pipe, $json);
        if ($bytesWritten === false) {
            //$this->logger->error('Failed to write message to server.');
            throw new \RuntimeException('Failed to write message to server.');
        }

        fflush($pipe);
        stream_set_blocking($prep_res['pipes'][1], false);
        stream_set_blocking($prep_res['pipes'][2], false);

        // After writing the request, before the current fgets loop:
        $read = [$prep_res['pipes'][1], $prep_res['pipes'][2]]; // STDOUT, STDERR
        $write = null;
        $except = null;
        $timeout = 5;



        return null;
    }
    public function post(mixed &$shared, mixed $prep_res, mixed $exec_res): mixed
    {
        $shared['session'] = $shared['session']->setConnection([
            'pipes' => $shared['pipes'],
            'process' => $shared['process'],
        ]);

        return null;
    }
}

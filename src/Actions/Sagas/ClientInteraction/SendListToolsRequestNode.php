<?php

namespace MCP\Transports\Stdio\Actions\Sagas\ClientInteraction;

use ProjectSaturnStudios\PocketFlow\Node;
use Symfony\Component\VarDumper\VarDumper;

class SendListToolsRequestNode extends Node
{
    public function prep(mixed &$shared): mixed
    {
        // @todo - change this to an RPC controller request
        $payload = [
            'jsonrpc' => '2.0',
            'id' => count($shared['session']->messages_sent) + 1,
            'method' => 'tools/list',
            'params' => new \stdClass()
        ];

        $shared['session'] = $shared['session']->logSendMessage($payload);
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

        $keep_going = true;
        $data = null;
        while($keep_going)
        {
            $buffer = '';
            while (($chunk = fgets($prep_res['pipes'][1])) !== false) {
                $buffer .= $chunk;
                if (str_ends_with(trim($buffer), '}') || str_ends_with(trim($buffer), ']')) {
                    $data = json_decode(trim($buffer), true, 512, JSON_THROW_ON_ERROR);
                    $keep_going = false;
                    break;
                }
            }
        }

        return $data;
    }
    public function post(mixed &$shared, mixed $prep_res, mixed $exec_res): mixed
    {
        $shared['session'] = $shared['session']->logReceivedMessage($exec_res);
        $shared['tools'] = $exec_res['result']['tools'] ?? [];

        return null;
    }
}

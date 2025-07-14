<?php

namespace MCP\Transports\Stdio\Actions\Sagas\TransportProtocol;

use JSONRPC\RPCResponse;
use ProjectSaturnStudios\PocketFlow\Node;

class TransmitStdioMessagesNode extends Node
{
    public function __construct(protected string $session_id)
    {
        parent::__construct();
    }

    public function prep(mixed &$shared): mixed
    {

        logger()->log('info', "TransmitStdioMessagesNode - prep: Setting up shared", [
            'outgoing_message' => $shared['current_message']->toJsonRpc(),
        ]);
        return $shared['current_message'];
    }

    /**
     * @param mixed $prep_res
     * @return mixed
     * @throws \JsonException
     */
    public function exec(mixed $prep_res): mixed
    {
        /** @var RPCResponse $outbound */
        $outbound = $prep_res;
        fwrite(STDOUT, $outbound->toJsonRpc() . PHP_EOL);
        return null;
    }

    public function post(mixed &$shared, mixed $prep_res, mixed $exec_res): mixed
    {
        // @todo - save everything to session
        $shared['current_message'] = null;
        $this->next(new ReceiveStdinMessagesNode($this->session_id), 'read');
        return 'read';
    }
}

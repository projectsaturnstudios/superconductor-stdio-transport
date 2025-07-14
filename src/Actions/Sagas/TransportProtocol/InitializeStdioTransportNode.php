<?php

namespace MCP\Transports\Stdio\Actions\Sagas\TransportProtocol;

use ProjectSaturnStudios\PocketFlow\Node;

class InitializeStdioTransportNode extends Node
{
    public function __construct(protected string $session_id)
    {
        parent::__construct();
    }

    public function prep(mixed &$shared): mixed
    {
        // @todo - load a session's data here
        return [
            'incoming_messages' => [],
            'outgoing_messages' => [],
            'current_message' => null,
        ];
    }

    public function exec(mixed $prep_res): mixed
    {
        return ['success' => true, 'message' => 'MCP Start Node executed successfully.'];
    }

    public function post(mixed &$shared, mixed $prep_res, mixed $exec_res): mixed
    {
        $shared = $prep_res;
        $this->next(new ReceiveStdinMessagesNode($this->session_id), 'read');
        return 'read';
    }
}

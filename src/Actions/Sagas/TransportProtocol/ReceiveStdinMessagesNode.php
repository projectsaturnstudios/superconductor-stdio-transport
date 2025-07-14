<?php

namespace MCP\Transports\Stdio\Actions\Sagas\TransportProtocol;

use JSONRPC\RPCNotification;
use JSONRPC\RPCRequest;
use JSONRPC\RPCResponse;
use MCP\Support\Facades\MCP;
use ProjectSaturnStudios\PocketFlow\Node;

class ReceiveStdinMessagesNode extends Node
{
    public function __construct(protected string $session_id)
    {
        parent::__construct();
    }

    public function prep(mixed &$shared): mixed
    {
        /*logger()->log('info', "ReceiveStdinMessagesNode - prep: Sharing shared", [
            'shared' => $shared,
        ]);*/
        return $shared;
    }

    public function exec(mixed $prep_res): mixed
    {
        $results = false;
        $in = fopen('php://stdin', 'r');
        while( $line = fgets( $in ) ) {
            if(is_array($request = json_decode($line, true)))
            {
                if(!is_array($results)) $results = [];
                if(is_array($results)) $results[] = $request;
            }
            break;
        }

        logger()->log('info', "ReceiveStdinMessagesNode - exec", [
            'raw_messages' => $results,
        ]);

        return $results;
    }

    public function post(mixed &$shared, mixed $prep_res, mixed $exec_res): mixed
    {
        if(!empty($exec_res))
        {
            foreach($exec_res as $idx => $incoming_message)
            {
                $new_message = MCP::buildMessage($incoming_message);
                if($new_message)
                {
                    if($new_message instanceof RPCResponse)
                    {
                        logger()->log('info', "ReceiveStdinMessagesNode - post: Received Response", [
                            'response' => $new_message,
                        ]);
                    }
                    elseif($new_message instanceof  RPCNotification)
                    {
                        logger()->log('info', "ReceiveStdinMessagesNode - pos: Received Notification", [
                            'notification' => $new_message,
                        ]);
                    }
                    elseif($new_message instanceof  RPCRequest)
                    {
                        $new_message = $new_message->additional(['session_id' => $this->session_id]);
                        $shared['current_message'] = $new_message;
                        $shared['incoming_messages'][] = $new_message;
                        $this->next(new RequestDispatchNode($this->session_id), 'dispatch');
                        return 'dispatch';
                    }
                    else
                    {
                        logger()->log('info', "ReceiveStdinMessagesNode - post: Unknown Message", [
                            'unsupported' => $incoming_message,
                        ]);
                    }
                }
                else
                {
                    logger()->log('info', "ReceiveStdinMessagesNode - post: Unknown Message", [
                        'unknown' => $incoming_message,
                    ]);
                }
            }

        }

        usleep(10000);
        //$this->next(new static($this->session_id), 'read');
        $this->next(new CheckCacheForOutgoingMessagesNode($this->session_id), 'outgoing');
        return 'outgoing';
    }
}

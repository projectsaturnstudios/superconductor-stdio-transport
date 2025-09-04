<?php

namespace Superconductor\Transports\Stdio\Console\Commands\Server;

use Illuminate\Support\Facades\Event;
use Superconductor\Mcp\DTO\Messages\Requests\InitializeRequest;
use Superconductor\Mcp\Servers\MCPServer;
use Superconductor\Rpc\DTO\Messages\Incoming\RpcNotification;
use Superconductor\Rpc\DTO\Messages\Incoming\RpcRequest;
use Superconductor\Rpc\DTO\Messages\Outgoing\RpcResult;
use Superconductor\Rpc\DTO\Messages\RpcMessage;
use Superconductor\Rpc\Enums\RPCErrorCode;
use Superconductor\Mcp\Servers\ServerRoute;
use Superconductor\Mcp\Support\Facades\MCPServers;
use Superconductor\Rpc\DTO\Messages\Outgoing\RpcError;
use Superconductor\Rpc\Support\Facades\RPC;
use Superconductor\Transports\Stdio\Support\Facades\Stdio;

class McpServerCommand extends RpcServerCommand
{
    protected $signature = 'mcp:serve {server=default}';

    protected $description = 'Start the Stdio server';

    public function handle(): int
    {
        $server_name = $this->argument('server');
        if($server_name === 'default') return parent::handle();

        $servers = MCPServers::getServers();
        if(!isset($servers[$server_name]))
        {
            $error = new RpcError(0, RPCErrorCode::SERVER_ERROR, "MCP Server '{$server_name}' not found.");
            $this->direct($error->toJsonRpc(true));
            return 1;
        }

        /** @var ServerRoute $server_route */
        $server_route = $servers[$server_name];
        /** @var MCPServer $mcp_server */
        $mcp_server = new $server_route->class_name();
        $transport_server = Stdio::server();
        $transport_server->send('error', 'Starting MCP Server: ' . $server_name);
        $stop = false;

        Event::listen('notification-fired', fn(RpcNotification $notification) => $transport_server->send('write', $notification->toJsonRpc(true)));

        while(!$stop)
        {
            $message = $transport_server->loop();
            if($message)
            {
                logger()->log('info', 'MCP Server Command - Message Received', [
                    'message' => $message,
                ]);

                $message = RpcMessage::fromJsonRpc($message);
                if($message instanceof RpcRequest)
                {
                    $message = $message->additional([
                        'server' => &$mcp_server,
                        'notification_channel' => 'notification-fired'
                    ]);

                    // Only log debug messages if explicitly enabled to avoid broken pipe errors
                    if (config('app.debug', false)) {
                        $transport_server->send('error', "Received Request - {$message->method}");
                    }
                    /** @var RpcResult|RpcError $result */
                    $result = RPC::call($message);

                    if(isset($result->getAdditionalData()['server'])) $mcp_server = $result->getAdditionalData()['server'];
                    if($result instanceof RpcResult) $transport_server->send('write', $result->toJsonRpc(true));
                    else
                    {
                        $transport_server->send('write', $result->toJsonRpc(true));
                        $error = new RpcError($message->id, RPCErrorCode::SERVER_ERROR, "Invalid JSONRPC message received.", $message->toJsonRpc());
                        $transport_server->send('write', $error->toJsonRpc(true));
                        if($message instanceof InitializeRequest) $stop = true;
                    }
                }
                elseif($message instanceof RpcNotification)
                {
                    $message = $message->additional([
                        'server' => &$mcp_server
                    ]);

                    /** @var RpcResult|RpcError $result */
                    $result = RPC::notify($message);
                    $ready = $mcp_server->isClientReady() ? 'true' : 'false';
                    if($result) $transport_server->send('error', "Received notification - {$message->method}  - Server is ready? {$ready}");
                    else $transport_server->send('error', "Received notification - {$message->method} - No handler found.");

                }
                elseif($message instanceof RpcError)
                {
                    logger()->log('info', 'Error from MCP Client', [
                        'message' => $message,
                    ]);
                    $error = new RpcError(0, RPCErrorCode::PARSE_ERROR, "Invalid JSONRPC message received.");
                    $transport_server->send('write', $error->toJsonRpc(true));
                    $stop = true;
                }
                elseif($message instanceof RpcResult)
                {
                    logger()->log('info', 'Result from MCP Client', [
                        '$message' => $message,
                    ]);
                    $error = new RpcError(0, RPCErrorCode::PARSE_ERROR, "Invalid JSONRPC message received.");
                    $transport_server->send('write', $error->toJsonRpc(true));
                    $stop = true;
                }
                else
                {
                    $error = new RpcError(0, RPCErrorCode::PARSE_ERROR, "Invalid JSONRPC message received.");
                    $transport_server->send('write', $error->toJsonRpc(true));
                    $stop = true;
                }
            }
        }

        $error = new RpcError(0, RPCErrorCode::SERVER_ERROR, "Thanks for playing.");
        $this->direct($error->toJsonRpc(true));
        return 0;
    }
}

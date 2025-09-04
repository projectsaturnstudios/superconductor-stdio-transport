<?php

namespace Superconductor\Transports\Stdio\Console\Commands\Server;

use Illuminate\Console\Command;
use Superconductor\Rpc\DTO\Messages\Outgoing\RpcError;
use Superconductor\Rpc\Enums\RPCErrorCode;

class RpcServerCommand extends Command
{
    protected $signature = 'stdio:serve';

    protected $description = 'JSONRPC Server running over Standard IO';

    public function handle(): int
    {
        //$this->log("Starting PC Server over Standard IO");
        $error = new RpcError(0, RPCErrorCode::SERVER_ERROR, "RPC Server over Standard IO not yet implemented.");
        $this->direct($error->toJsonRpc(true));
        return 0;
    }

    protected function log(string $message): void
    {
        fwrite(STDERR, $message . PHP_EOL);
        fflush(STDERR);
    }

    protected function direct(string $message): void
    {
        fwrite(STDOUT, $message . PHP_EOL);
        fflush(STDOUT);
    }
}

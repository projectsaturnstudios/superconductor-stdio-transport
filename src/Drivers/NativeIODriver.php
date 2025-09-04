<?php

namespace Superconductor\Transports\Stdio\Drivers;

use RuntimeException;
use Illuminate\Contracts\Container\Container;
use Superconductor\Transports\Stdio\StandardIO;
use Superconductor\Transports\Stdio\StdioCommunicator;
use Superconductor\Transports\Stdio\DTO\Servers\ProcessCommandConfig;
use Superconductor\Transports\Stdio\IOResources\Reading\NativeReadableResource;
use Superconductor\Transports\Stdio\IOResources\Writing\NativeWriteableResource;

class NativeIODriver extends IODriver
{
    public function __construct(
        protected array $config,
        protected Container $app
    ) {}

    public function server(): StdioCommunicator
    {
        $stdio = new StandardIO(
            new NativeReadableResource(STDIN, $this->config['read_byte_size']),
            new NativeWriteableResource(STDOUT),
            new NativeWriteableResource(STDERR)
        );

        return new StdioCommunicator($stdio);
    }

    public function client(ProcessCommandConfig $command): StdioCommunicator
    {
        $spec = [
            0 => ['pipe', 'r'], // child STDIN  (we write to this)
            1 => ['pipe', 'w'], // child STDOUT (we read from this)
            2 => ['pipe', 'w'], // child STDERR (optional read)
        ];

        $process = proc_open($command->toCommand(), $spec, $pipes, null, $command->env());

        if ($process === false || !is_resource($process)) {
            throw new RuntimeException('Failed to start MCP server process.');
        }

        // Non-blocking pipes
        stream_set_blocking($pipes[0], false);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        if (!is_resource($pipes[0])) {
            throw new RuntimeException('Write pipe is invalid');
        }

        usleep(400000); // Allow some time for the process to start
        $status = proc_get_status($process);
        if(!$status['running']) throw new RuntimeException('Unable to run external server process.');

        $stdio = new StandardIO(
            new NativeReadableResource($pipes[1], $this->config['read_byte_size']),
            new NativeWriteableResource($pipes[0]),
            new NativeReadableResource($pipes[2], $this->config['read_byte_size'])
        );

        return (new StdioCommunicator($stdio))->withProcess($process);
    }
}

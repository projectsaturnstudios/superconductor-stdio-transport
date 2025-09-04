<?php

namespace Superconductor\Transports\Stdio\Drivers;

use RuntimeException;
use React\EventLoop\LoopInterface;
use Illuminate\Contracts\Container\Container;
use Superconductor\Transports\Stdio\StandardIO;
use Superconductor\Transports\Stdio\StdioCommunicator;
use Superconductor\Transports\Stdio\DTO\Servers\ProcessCommandConfig;
use Superconductor\Transports\Stdio\IOResources\Reading\ReactReadableResource;
use Superconductor\Transports\Stdio\IOResources\Writing\ReactWriteableResource;

class ReactPHPIODriver extends IODriver
{
    public function __construct(
        protected array $config,
        protected Container $app
    ) {}

    public function server(): StdioCommunicator
    {
        $stdio = new StandardIO(
            new ReactReadableResource(STDIN, $this->config['loop_time']),
            new ReactWriteableResource(STDOUT),
            new ReactWriteableResource(STDERR)
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

        /** @var LoopInterface $loop */
        $loop = $this->config['loop_interface']::get();
        $stdio = (new StandardIO(
            new ReactReadableResource($pipes[1], $this->config['loop_time'], $loop),
            new ReactWriteableResource($pipes[0], $loop),
            new ReactReadableResource($pipes[2], $this->config['loop_time'],$loop)
        ))->withProcess($process);

        return (new StdioCommunicator($stdio))->withProcess($process);
    }
}

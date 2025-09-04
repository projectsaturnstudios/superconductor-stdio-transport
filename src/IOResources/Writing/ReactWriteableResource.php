<?php

namespace Superconductor\Transports\Stdio\IOResources\Writing;

use React\EventLoop\Loop;
use InvalidArgumentException;
use React\EventLoop\LoopInterface;
use React\Stream\WritableResourceStream;
use Superconductor\Transports\Stdio\Contracts\WritingResourceInterface;

class ReactWriteableResource implements WritingResourceInterface
{
    protected WritableResourceStream $output;

    /**
     * @param resource|string $stream
     */
    public function __construct(mixed $stream, protected ?LoopInterface $loop = null) {
        if(is_string($stream)) $stream = fopen($stream, 'r');
        if(!is_resource($stream)) throw new InvalidArgumentException("Stream must be a valid resource or string path.");
        stream_set_blocking($stream, false);
        $loop ??= Loop::get();
        $this->output = new WritableResourceStream($stream, $loop);
    }

    public function write(string $message): bool
    {
        if(!$this->output->isWritable()) return false;
        
        try {
            return $this->output->write($message . PHP_EOL);
        } catch (\Throwable $e) {
            // If ReactPHP fails (e.g., closed streams), return false gracefully
            return false;
        }
    }

    public function close(): void
    {
        $this->output->close();
    }
}

<?php

namespace Superconductor\Transports\Stdio\IOResources\Reading;

use React\EventLoop\Loop;
use InvalidArgumentException;
use React\EventLoop\LoopInterface;
use React\Stream\ReadableResourceStream;
use Superconductor\Transports\Stdio\Contracts\ReadingResourceInterface;

class ReactReadableResource implements ReadingResourceInterface
{
    protected ReadableResourceStream $input;

    /** @var string Buffer for incomplete messages */
    protected string $buffer = '';

    /** @var array Queue of complete messages */
    protected array $messageQueue = [];

    /**
     * @param resource|string $stream
     */
    public function __construct(
        mixed $stream,
        protected ?float $loop_time = null,
        protected ?LoopInterface $loop = null
    ) {
        if(is_string($stream)) $stream = fopen($stream, 'r');
        if(!is_resource($stream)) throw new InvalidArgumentException("Stream must be a valid resource or string path.");
        stream_set_blocking($stream, false);
        $this->loop ??= Loop::get();
        $this->input = new ReadableResourceStream($stream, $loop);
        $this->loop_time ??= config('superconductor.transports.stdio.stream_management.managers.react.settings.loop_time', 2.0);
        $this->loop_listener();
    }

    public function read(): string|false
    {
        // Check if we have a complete message in the queue
        if (!empty($this->messageQueue)) {
            return array_shift($this->messageQueue);
        }

        // If no messages in queue, try to get more data
        if (empty($this->messageQueue)) {
            // Check if the stream is still valid before running the event loop
            if (!$this->input->isReadable()) {
                return false;
            }

            $this->loop->addTimer($this->loop_time, fn() => $this->loop->stop());

            try {
                $this->loop->run();
            } catch (\Throwable $e) {
                // If ReactPHP fails (e.g., closed streams), return false gracefully
                return false;
            }
        }

        // Return the next complete message if available
        return !empty($this->messageQueue) ? array_shift($this->messageQueue) : false;
    }

    private function loop_listener(): void
    {
        $this->input->on('data', function($data) {
            // Add new data to buffer
            $this->buffer .= $data;

            // Process the buffer to extract complete messages
            $this->frameMessages();
        });
    }

    /**
     * Extract complete JSON-RPC messages from the buffer
     */
    protected function frameMessages(): void
    {
        while (($pos = strpos($this->buffer, "\n")) !== false) {
            $line = trim(substr($this->buffer, 0, $pos));
            $this->buffer = substr($this->buffer, $pos + 1);

            // Skip empty lines
            if ($line === '') continue;

            // Validate JSON and add to queue
            $msg = json_decode($line, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($msg)) {
                $this->messageQueue[] = $line; // Store the original JSON string
            }
        }
    }

    public function close(): void
    {
        $this->input->close();
    }
}

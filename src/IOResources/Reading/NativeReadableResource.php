<?php

namespace Superconductor\Transports\Stdio\IOResources\Reading;

use InvalidArgumentException;
use Superconductor\Transports\Stdio\Contracts\ReadingResourceInterface;

class NativeReadableResource implements ReadingResourceInterface
{
    /** @var resource $input  */
    protected mixed $input;
    
    /** @var string Buffer for incomplete messages */
    protected string $buffer = '';
    
    /** @var array Queue of complete messages */
    protected array $messageQueue = [];

    /**
     * @param resource|string $stream
     */
    public function __construct(mixed $stream, protected ?int $byte_size = null) {
        if(is_string($stream)) $stream = fopen($stream, 'r');
        if(!is_resource($stream)) throw new InvalidArgumentException("Stream must be a valid resource or string path.");
        stream_set_blocking($stream, false);
        $this->input = $stream;
        $this->byte_size ??= config('superconductor.transports.stdio.stream_management.managers.native.settings.read_byte_size', 512);
    }

    public function read(): string|false
    {
        // Check if we have a complete message in the queue
        if (!empty($this->messageQueue)) {
            return array_shift($this->messageQueue);
        }
        
        // Check if the stream is still valid
        if (!is_resource($this->input)) {
            return false;
        }
        
        // Read new data from the stream
        $data = fread($this->input, $this->byte_size);
        if ($data === false || $data === '') {
            return false;
        }
        
        // Add new data to buffer
        $this->buffer .= $data;
        
        // Process the buffer to extract complete messages
        $this->frameMessages();
        
        // Return the next complete message if available
        return !empty($this->messageQueue) ? array_shift($this->messageQueue) : false;
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
        if (is_resource($this->input)) {
            fclose($this->input);
        }
    }
}

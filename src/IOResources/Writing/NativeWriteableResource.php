<?php

namespace Superconductor\Transports\Stdio\IOResources\Writing;

use InvalidArgumentException;
use Superconductor\Transports\Stdio\Contracts\WritingResourceInterface;

class NativeWriteableResource implements WritingResourceInterface
{
    /** @var resource $output  */
    protected mixed $output;

    /**
     * @param resource $stream
     */
    public function __construct($stream) {
        if(is_string($stream)) $stream = fopen($stream, 'r');
        if(!is_resource($stream)) throw new InvalidArgumentException("Stream must be a valid resource or string path.");
        stream_set_blocking($stream, false);
        $this->output = $stream;
    }

    public function write(string $message): bool
    {
        // Check if the stream is still valid before writing
        if (!is_resource($this->output)) {
            return false;
        }

        $fullMessage = $message . PHP_EOL;
        $totalBytes = strlen($fullMessage);
        $written = 0;

        while ($written < $totalBytes) {
            // Double-check the resource is still valid in the loop
            if (!is_resource($this->output)) {
                return false;
            }

            try {
                $bytes = fwrite($this->output, substr($fullMessage, $written));
                if ($bytes === false) {
                    return false;
                }
                $written += $bytes;

                // If we couldn't write everything, wait a bit and try again
                if ($written < $totalBytes) {
                    usleep(1000); // 1ms
                }
            } catch (\Throwable $e) {
                // If fwrite fails (e.g., broken pipe), return false gracefully
                return false;
            }
        }

        try {
            fflush($this->output);
        } catch (\Throwable $e) {
            // If flush fails, still consider the write successful if we wrote all bytes
            return $written === $totalBytes;
        }
        
        return true;
    }

    public function close(): void
    {
        fclose($this->output);
    }
}

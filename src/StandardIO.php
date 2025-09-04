<?php

namespace Superconductor\Transports\Stdio;

use Superconductor\Transports\Stdio\Contracts\ReadingResourceInterface;
use Superconductor\Transports\Stdio\Contracts\WritingResourceInterface;

class StandardIO
{
    protected mixed $process = null;

    public function __construct(
        protected ReadingResourceInterface $read_channel,
        protected WritingResourceInterface $write_channel,
        protected ReadingResourceInterface|WritingResourceInterface $error_channel,
    ) {}

    public function send(string $message, string $channel = 'write'): bool
    {
        $channel = strtolower($channel).'_channel';
        return $this->$channel->write($message);
    }

    public function listen(string $channel = 'read'): mixed
    {
        $channel = strtolower($channel).'_channel';
        return $this->$channel->read();
    }

    public function log(?string $message = null): mixed
    {
        if($message) return $this->error_channel->write($message);
        else return $this->error_channel->read();
    }

    public function close(): null
    {
        $this->read_channel->close();
        $this->write_channel->close();
        $this->error_channel->close();
        return null;
    }

    public function withProcess($process): static
    {
        $this->process = $process;
        return $this;
    }

    public function __destruct()
    {
        // Ensure proper cleanup of the process when the object is destroyed
        if ($this->process && is_resource($this->process)) {
            proc_close($this->process);
        }
    }
}

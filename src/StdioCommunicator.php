<?php

namespace Superconductor\Transports\Stdio;

class StdioCommunicator
{
    protected mixed $process = null;
    public function __construct(
        protected ?StandardIO $stdio
    ) {}

    public function poll(string $channel = 'read'): mixed
    {
        return $this->stdio?->listen($channel) ?? null;
    }

    /**
     * Loops the poll method a specified number of times or until data is retrieved.
     * @param string $channel
     * @param int $num_loops
     * @return mixed
     */
    public function loop(string $channel = 'read', int $num_loops = 1): mixed
    {
        $data = null;
        for ($i = 0; $i < $num_loops; $i++) {
            $data = $this->poll($channel);
            if ($data !== null && $data !== false) break;
            usleep(10000); // Sleep for 1ms to prevent busy waiting
        }
        return $data;
    }

    public function send(string $channel, string $data): bool|array
    {
        if($channel == 'io') return $this->io($data);
        return $this->stdio?->send($data, $channel) ?? false;
    }

    public function io(string $data): array
    {
        $sent = $this->send('write', $data);
        if (!$sent) {
            return ['error' => 'Failed to send data'];
        }
        // Give the server more time to respond
        $response = $this->loop('read', 500); // Try 50 times with 10ms delays = 500ms total
        if ($response === null) {
            return ['error' => 'No response from server'];
        }

        // Try to decode JSON response
        $decoded = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        return ['response' => $response];
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

<?php

namespace Superconductor\Transports\Stdio\DTO\Servers;

use Spatie\LaravelData\Data;

class ProcessCommandConfig extends Data
{
    public readonly array $env;

    public function __construct(
        public readonly string $command,
        public readonly array $args = [],
        array $env_vars = []
    ) {
        $env_vars['PATH'] ??= ($_SERVER['PATH'] ?? getenv('PATH'));
        $this->env = $env_vars;
    }

    public function env(): array
    {
        return $this->env;
    }

    public function toCommand(): array
    {
        return [$this->command, ...$this->args];
    }
}

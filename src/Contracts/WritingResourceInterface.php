<?php

namespace Superconductor\Transports\Stdio\Contracts;


interface WritingResourceInterface
{
    public function write(string $message): bool;
    public function close();
}

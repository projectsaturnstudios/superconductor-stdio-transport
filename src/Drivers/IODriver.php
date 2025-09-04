<?php

namespace Superconductor\Transports\Stdio\Drivers;

use Superconductor\Transports\Stdio\DTO\Servers\ProcessCommandConfig;

abstract class IODriver
{
    abstract public function server();
    abstract public function client(ProcessCommandConfig $command);
}

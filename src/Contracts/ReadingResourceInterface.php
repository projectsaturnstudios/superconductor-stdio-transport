<?php

namespace Superconductor\Transports\Stdio\Contracts;


interface ReadingResourceInterface
{
    public function read();
    public function close();
}

<?php

namespace Superconductor\Transports\Stdio\Peers;

use Superconductor\Transports\Stdio\StdioCommunicator;
use Superconductor\Transports\Stdio\Support\Facades\Stdio;

class StdioServer
{
    public function __construct(
        protected StdioCommunicator $communicator,
    ) {}

    public static function boot(): void
    {
        app()->instance(self::class, function ($app) {
            $io_driver = Stdio::driver()->server();

            return new self($io_driver);
        });
    }
}

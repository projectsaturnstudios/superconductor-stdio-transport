<?php

namespace Superconductor\Transports\Stdio\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Superconductor\Transports\Stdio\Drivers\IODriver;
use Superconductor\Transports\Stdio\StdioCommunicator;
use Superconductor\Transports\Stdio\Managers\StdioPeerManager;
use Superconductor\Transports\Stdio\DTO\Servers\ProcessCommandConfig;

/**
 * @method static IODriver driver(?string $name = null)
 * @method static StdioCommunicator server()
 * @method static StdioCommunicator client(ProcessCommandConfig $command)
 *
 * @see StdioPeerManager
 */
class Stdio extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'stdio';
    }
}

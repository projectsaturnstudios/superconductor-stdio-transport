<?php

namespace Superconductor\Transports\Stdio\Providers;

use Superconductor\Transports\Stdio\Console\Commands\Server\McpServerCommand;
use Superconductor\Transports\Stdio\Console\Commands\Server\RpcServerCommand;
use Superconductor\Transports\Stdio\Managers\StdioPeerManager;
use ProjectSaturnStudios\LaravelDesignPatterns\Providers\BaseServiceProvider;

class StdioTransportServiceProvider extends BaseServiceProvider
{
    protected array $config = [
        'superconductor.transports.stdio' => __DIR__ . '/../../config/stdio.php',
    ];
    protected array $publishable_config = [
        ['key' => 'superconductor.transports.stdio', 'file_path' => 'stdio.php', 'groups' => ['superconductor', 'superconductor.transports', 'superconductor.transports.stdio']],
    ];
    protected array $commands = [
        McpServerCommand::class,
        RpcServerCommand::class,
    ];
    protected array $bootables = [
        StdioPeerManager::class
    ];
    protected array $routes = [];
}

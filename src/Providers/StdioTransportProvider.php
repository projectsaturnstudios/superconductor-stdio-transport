<?php

namespace MCP\Transports\Stdio\Providers;

use Illuminate\Support\ServiceProvider;
use MCP\Transports\Stdio\Console\Commands\StartMcpServerCommand;

class StdioTransportProvider extends ServiceProvider
{
    protected array $config = [
        'mcp.transports.stdio' => __DIR__ .'/../../config/transports/stdio.php',
    ];

    protected array $commands = [
        StartMcpServerCommand::class,
    ];

    public function register(): void
    {
        $this->registerConfigs();
    }

    public function boot(): void
    {
        $this->publishConfigs();
        $this->commands($this->commands);
    }

    protected function publishConfigs() : void
    {
        $this->publishes([
            $this->config['mcp.transports.stdio'] => config_path('mcp/transports/stdio.php'),
        ], ['mcp.transports', 'mcp.transports.stdio']);
    }

    protected function registerConfigs() : void
    {
        foreach ($this->config as $key => $path) {
            $this->mergeConfigFrom($path, $key);
        }
    }
}

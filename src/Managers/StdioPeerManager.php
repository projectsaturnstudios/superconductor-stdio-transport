<?php

namespace Superconductor\Transports\Stdio\Managers;

use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Manager;
use Superconductor\Transports\Stdio\Drivers\IODriver;
use Superconductor\Transports\Stdio\Drivers\NativeIODriver;
use Superconductor\Transports\Stdio\Drivers\ReactPHPIODriver;

#[Singleton]
class StdioPeerManager extends Manager
{
    public function createReactDriver(): IODriver
    {
        $config_key = 'superconductor.transports.stdio.stream_management.managers.react.settings';
        return new ReactPHPIODriver(config($config_key), $this->container);
    }

    public function createNativeDriver(): IODriver
    {
        $config_key = 'superconductor.transports.stdio.stream_management.managers.native.settings';
        return new NativeIODriver(config($config_key), $this->container);
    }

    public function getDefaultDriver(): string
    {
        $config_key = 'superconductor.transports.stdio.stream_management';
        $key = config("{$config_key}.default", 'native');
        return config("{$config_key}.managers.{$key}.driver", 'native');
    }

    public static function boot(): void
    {
        app()->singleton('stdio', function ($app) {
            $results = new static($app);

            // @todo - extension addon integration from other packages

            return $results;
        });

    }
}

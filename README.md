1. To spin up a client:
```php
use Superconductor\Transports\Stdio\DTO\Servers\ProcessCommandConfig;
use Superconductor\Transports\Stdio\Support\Facades\Stdio;
use Superconductor\Capabilities\Tools\DTO\Messages\Requests\ListToolsRequest;

// Define the command to run the MCP server
$mcp_server = [
    "command" => "php",
    "args" => ["artisan","boost:mcp"],
    'env' => [],
];

// Create a ProcessCommandConfig instance
$command = new ProcessCommandConfig(...$mcp_server);

// Start the client by passing in the command configuration
$client = Stdio::client($command)

$request = new ListToolsRequest();

```
2. Spinning up the server instance is more straightforward:
```php
use Superconductor\Transports\Stdio\Support\Facades\Stdio;

// Start the server
$server = Stdio::server();
```

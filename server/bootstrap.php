<?php
declare(strict_types=1);

use Tna\Config\AppConfig;
use Tna\Config\DatabaseConfig;
use Tna\Database\ConnectionFactory;
use Tna\Http\ErrorHandler;
use Tna\Http\Request;
use Tna\Http\Response;
use Tna\Http\Router;
use Tna\Middleware\JsonContentTypeMiddleware;
use Tna\Middleware\RequestIdMiddleware;
use Tna\Middleware\RequestSizeMiddleware;
use Tna\Middleware\SecurityHeadersMiddleware;
use Tna\Security\PublicIdGenerator;
use Tna\Support\Clock;
use Tna\Support\Environment;
use Tna\Support\Logger;

$autoload = __DIR__ . '/vendor/autoload.php';
if (is_file($autoload)) {
    require $autoload;
} else {
    spl_autoload_register(static function (string $class): void {
        $prefix = 'Tna\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }
        $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
        $file = __DIR__ . '/src/' . $relative . '.php';
        if (is_file($file)) {
            require $file;
        }
    });
}

$configPath = Environment::value('TNA_CONFIG_PATH') ?: Environment::value('TNA_PRIVATE_CONFIG_PATH') ?: dirname(__DIR__) . '/tna-config.php';
$config = is_file($configPath) ? require $configPath : require __DIR__ . '/config.example.php';
if (!is_array($config)) {
    throw new RuntimeException('Configuration must return an array.');
}

$appConfig = AppConfig::fromArray($config);
date_default_timezone_set($appConfig->timezone);
$clock = new Clock();
$logger = new Logger($clock);
$errorHandler = new ErrorHandler($appConfig, $clock, $logger);
$errorHandler->register();
$securityHeaders = new SecurityHeadersMiddleware();
$securityHeaders->send();

$request = Request::fromGlobals();
$request = (new RequestIdMiddleware(new PublicIdGenerator()))->handle($request);

try {
    (new RequestSizeMiddleware($appConfig->maxBodyBytes))->handle($request);
    (new JsonContentTypeMiddleware())->handle($request);

    $router = new Router();
    $connectionFactory = new ConnectionFactory(DatabaseConfig::fromArray($config));
    unset($connectionFactory); // kept for routes that need PDO later; ping intentionally does not connect.

    $router->get('/v1/ping', static fn (Request $request): Response => Response::envelope([
        'status' => 'ok',
        'application' => 'the-ninth-art-api',
        'databaseChecked' => false,
    ], $request->requestId() ?? 'unavailable', $clock));

    $router->dispatch($request)->send();
} catch (Throwable $throwable) {
    $errorHandler->handle($throwable, $request)->send();
}

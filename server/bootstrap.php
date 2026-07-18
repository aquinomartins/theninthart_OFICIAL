<?php
declare(strict_types=1);

use Tna\Config\AppConfig;
use Tna\Config\DatabaseConfig;
use Tna\Controller\BootstrapController;
use Tna\Controller\HealthController;
use Tna\Controller\PublicStateController;
use Tna\Controller\SessionController;
use Tna\Database\ConnectionFactory;
use Tna\Database\TransactionManager;
use Tna\Http\ErrorHandler;
use Tna\Http\Request;
use Tna\Http\Router;
use Tna\Middleware\JsonContentTypeMiddleware;
use Tna\Middleware\RequestIdMiddleware;
use Tna\Middleware\RequestSizeMiddleware;
use Tna\Middleware\SecurityHeadersMiddleware;
use Tna\Security\PublicIdGenerator;
use Tna\Support\Clock;
use Tna\Support\Environment;
use Tna\Support\Logger;
use Tna\Service\BootstrapService;
use Tna\Service\HealthService;
use Tna\Service\PublicStateService;
use Tna\Service\SessionService;

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
    $migrationsPath = dirname(__DIR__) . '/database/migrations';

    $router->get('/v1/health', new HealthController(new HealthService($connectionFactory, $migrationsPath), $clock));
    $router->get('/v1/bootstrap', new BootstrapController(new BootstrapService($connectionFactory), $clock));
    $router->get('/v1/public-state', new PublicStateController(new PublicStateService($connectionFactory), $clock));
    $sessionController = new SessionController(new SessionService(new TransactionManager($connectionFactory), $clock), $clock);
    $router->post('/v1/sessions', [$sessionController, 'create']);
    $router->get('/v1/sessions/{sessionId}', [$sessionController, 'get']);
    $router->put('/v1/sessions/{sessionId}/controls', [$sessionController, 'controls']);
    $router->put('/v1/sessions/{sessionId}/widgets/{widgetId}', [$sessionController, 'widget']);

    $router->dispatch($request)->send();
} catch (Throwable $throwable) {
    $errorHandler->handle($throwable, $request)->send();
}

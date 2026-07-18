<?php
declare(strict_types=1);

$bootstrapPath = getenv('TNA_SERVER_BOOTSTRAP_PATH');
if (is_string($bootstrapPath) && $bootstrapPath !== '' && is_file($bootstrapPath)) {
    require $bootstrapPath;
    return;
}

$privateBootstrap = dirname(__DIR__, 2) . '/private/theninthart/server/bootstrap.php';
if (is_file($privateBootstrap)) {
    require $privateBootstrap;
    return;
}

require dirname(__DIR__) . '/server/bootstrap.php';

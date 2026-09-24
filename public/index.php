<?php

declare(strict_types=1);

use OverDark\Core\Http\Request;

// Servidor embutido do PHP (composer serve): entrega arquivos estáticos diretamente.
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

    if (is_file($file)) {
        return false;
    }
}

/** @var \OverDark\Core\Application $app */
$app = require dirname(__DIR__) . '/bootstrap/app.php';

$app->handle(Request::fromGlobals())->send();

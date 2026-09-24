<?php

declare(strict_types=1);

use OverDark\Core\Application;
use OverDark\Core\Config\Env;
use OverDark\Core\Container\Container;
use OverDark\Core\Http\Router;
use OverDark\Core\Security\VerifyCsrfToken;
use OverDark\Core\View\View;

/**
 * Monta a aplicação: autoload, variáveis de ambiente, container e rotas.
 * Usado pelo front controller (public/index.php), pelo bin/console e pelos testes.
 */

$root = dirname(__DIR__);

require_once $root . '/vendor/autoload.php';

Env::load($root . '/.env');

/** @var array{name: string, env: string, debug: bool, timezone: string} $config */
$config = require $root . '/config/app.php';

date_default_timezone_set($config['timezone']);

$container = new Container();
$container->set(View::class, static fn () => new View($root . '/src/Modules', $root . '/resources/views'));

(require $root . '/config/services.php')($container);

$router = new Router();
(require $root . '/config/routes.php')($router);

return new Application(
    $container,
    $router,
    $container->get(View::class),
    $config['debug'],
    globalMiddleware: [VerifyCsrfToken::class],
);

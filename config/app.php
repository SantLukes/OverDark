<?php

declare(strict_types=1);

use OverDark\Core\Config\Env;

return [
    'name' => 'OverDark',
    'env' => (string) Env::get('APP_ENV', 'production'),
    'debug' => Env::get('APP_DEBUG', false) === true,
    'timezone' => (string) Env::get('APP_TIMEZONE', 'America/Sao_Paulo'),
];

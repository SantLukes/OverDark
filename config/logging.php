<?php

declare(strict_types=1);

use OverDark\Core\Config\Env;

return [
    // Arquivo JSON Lines com todos os logs a partir de LOG_LEVEL.
    'arquivo' => dirname(__DIR__) . '/' . ltrim((string) Env::get('LOG_PATH', 'storage/logs/overdark.log'), '/'),
    'nivel' => (string) Env::get('LOG_LEVEL', 'debug'),

    // Slack: só envia se houver webhook; por padrão, apenas error e acima.
    'slack' => [
        'webhook' => (string) Env::get('SLACK_WEBHOOK_URL', ''),
        'nivel' => (string) Env::get('LOG_SLACK_LEVEL', 'error'),
    ],
];

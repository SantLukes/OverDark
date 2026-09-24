<?php

declare(strict_types=1);

namespace OverDark\Core\Logging\Handler;

use OverDark\Core\Logging\LogRecord;

/**
 * Destino de logs (arquivo, Slack, memória...).
 */
interface Handler
{
    public function aceita(LogRecord $record): bool;

    public function registrar(LogRecord $record): void;
}

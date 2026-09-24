<?php

declare(strict_types=1);

namespace OverDark\Core\Logging\Handler;

use OverDark\Core\Logging\Level;
use OverDark\Core\Logging\LogRecord;
use RuntimeException;

/**
 * Um JSON por linha (JSON Lines): fácil de ler com `tail`, `jq`, ou de
 * enviar para ferramentas como Loki, Elastic, Datadog.
 */
final class JsonLinesFileHandler implements Handler
{
    public function __construct(
        private readonly string $caminho,
        private readonly string $nivelMinimo = 'debug',
    ) {
    }

    public function aceita(LogRecord $record): bool
    {
        return Level::atinge($record->level, $this->nivelMinimo);
    }

    public function registrar(LogRecord $record): void
    {
        $diretorio = dirname($this->caminho);

        if (!is_dir($diretorio) && !@mkdir($diretorio, 0775, true) && !is_dir($diretorio)) {
            throw new RuntimeException(sprintf('Não foi possível criar o diretório de logs %s.', $diretorio));
        }

        if (@file_put_contents($this->caminho, $record->toJson() . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
            throw new RuntimeException(sprintf('Não foi possível escrever em %s.', $this->caminho));
        }
    }
}

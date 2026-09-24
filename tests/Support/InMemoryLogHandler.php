<?php

declare(strict_types=1);

namespace OverDark\Tests\Support;

use OverDark\Core\Logging\Handler\Handler;
use OverDark\Core\Logging\LogRecord;

/**
 * Guarda os logs em memória para os testes verificarem o que foi registrado.
 */
final class InMemoryLogHandler implements Handler
{
    /** @var list<LogRecord> */
    public array $records = [];

    public function aceita(LogRecord $record): bool
    {
        return true;
    }

    public function registrar(LogRecord $record): void
    {
        $this->records[] = $record;
    }

    /**
     * @return list<LogRecord>
     */
    public function doEvento(string $event): array
    {
        return array_values(array_filter($this->records, static fn (LogRecord $r): bool => $r->event === $event));
    }

    public function unico(string $event): LogRecord
    {
        $encontrados = $this->doEvento($event);

        if (count($encontrados) !== 1) {
            throw new \RuntimeException(sprintf(
                'Esperava 1 log "%s", encontrei %d. Eventos: %s',
                $event,
                count($encontrados),
                implode(', ', array_map(static fn (LogRecord $r): string => $r->event, $this->records)),
            ));
        }

        return $encontrados[0];
    }
}

<?php

declare(strict_types=1);

namespace OverDark\Core\Logging;

use DateTimeImmutable;

/**
 * Um log já montado e higienizado, pronto para os handlers.
 */
final class LogRecord
{
    /** Campos obrigatórios do contrato, sempre presentes e nesta ordem. */
    public const CAMPOS_BASE = ['event', 'operacao', 'status', 'correlation_id'];

    /**
     * @param array<string, mixed> $campos contexto (sem os campos base)
     */
    public function __construct(
        public readonly DateTimeImmutable $timestamp,
        public readonly string $level,
        public readonly string $event,
        public readonly ?string $operacao,
        public readonly string|int|null $status,
        public readonly string $correlationId,
        public readonly array $campos,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'timestamp' => $this->timestamp->format('Y-m-d\TH:i:s.vP'),
            'level' => $this->level,
            'event' => $this->event,
            'operacao' => $this->operacao,
            'status' => $this->status,
            'correlation_id' => $this->correlationId,
            ...$this->campos,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR) ?: '{}';
    }
}

<?php

declare(strict_types=1);

namespace OverDark\Core\Logging;

/**
 * Contexto da execução atual (uma requisição HTTP ou um comando CLI).
 *
 * Tudo o que está aqui é anexado automaticamente a cada log: quem chama o
 * logger não precisa repassar correlation_id nem usuario_id.
 */
final class LogContext
{
    private ?string $correlationId = null;

    private ?int $usuarioId = null;

    /**
     * Inicia uma nova execução. Reaproveita um correlation_id recebido
     * (ex.: header X-Correlation-ID de outro serviço) se ele for seguro.
     */
    public function iniciar(?string $correlationIdRecebido = null, string $prefixo = 'req'): string
    {
        $this->usuarioId = null;
        $id = $correlationIdRecebido !== null && self::valido($correlationIdRecebido)
            ? $correlationIdRecebido
            : $prefixo . '_' . bin2hex(random_bytes(3));

        return $this->correlationId = $id;
    }

    public function correlationId(): string
    {
        return $this->correlationId ??= $this->iniciar();
    }

    public function definirUsuario(int $usuarioId): void
    {
        $this->usuarioId = $usuarioId;
    }

    public function usuarioId(): ?int
    {
        return $this->usuarioId;
    }

    private static function valido(string $id): bool
    {
        return preg_match('/^[A-Za-z0-9_-]{6,64}$/', $id) === 1;
    }
}

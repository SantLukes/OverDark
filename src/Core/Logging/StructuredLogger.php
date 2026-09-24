<?php

declare(strict_types=1);

namespace OverDark\Core\Logging;

use DateTimeImmutable;
use OverDark\Core\Logging\Handler\Handler;
use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;
use Stringable;
use Throwable;

/**
 * Logger PSR-3 que aplica o contrato de log do OverDark:
 *
 *   $logger->info('movimentacao_registrada', [
 *       'operacao' => 'registrar_movimentacao',
 *       'status' => 'SUCCESS',
 *       'movimentacao_id' => 42,
 *   ]);
 *
 * - A "mensagem" do PSR-3 é o nome do evento (snake_case, estável, sem frase genérica).
 * - event, operacao, status e correlation_id saem sempre, na mesma ordem.
 * - correlation_id e usuario_id vêm do LogContext automaticamente.
 * - Chaves sensíveis (senha, token, webhook...) são mascaradas.
 * - Falha de um destino (ex.: Slack fora do ar) nunca derruba a aplicação.
 */
final class StructuredLogger extends AbstractLogger
{
    /**
     * @param list<Handler> $handlers
     */
    public function __construct(
        private readonly LogContext $contexto,
        private readonly array $handlers,
    ) {
    }

    /**
     * @param mixed $level
     * @param array<array-key, mixed> $context
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $level = strtolower((string) $level);

        if (!Level::valido($level)) {
            throw new InvalidArgumentException(sprintf('Nível de log inválido: "%s".', $level));
        }

        $this->despachar($this->montar($level, (string) $message, $context), $this->handlers);
    }

    /**
     * @param array<array-key, mixed> $context
     */
    private function montar(string $level, string $event, array $context): LogRecord
    {
        $operacao = $context['operacao'] ?? null;
        $status = $context['status'] ?? null;
        unset($context['event'], $context['operacao'], $context['status'], $context['correlation_id']);

        if (array_key_exists('exception', $context)) {
            $context['excecao'] = $context['exception'];
            unset($context['exception']);
        }

        $usuarioId = $this->contexto->usuarioId();
        if ($usuarioId !== null && !array_key_exists('usuario_id', $context)) {
            $context = ['usuario_id' => $usuarioId, ...$context];
        }

        /** @var array<string, mixed> $campos */
        $campos = Sanitizer::limpar($context);

        return new LogRecord(
            timestamp: new DateTimeImmutable(),
            level: $level,
            event: $event,
            operacao: is_scalar($operacao) ? (string) $operacao : null,
            status: is_int($status) || is_string($status) ? $status : null,
            correlationId: $this->contexto->correlationId(),
            campos: $campos,
        );
    }

    /**
     * @param list<Handler> $handlers
     */
    private function despachar(LogRecord $record, array $handlers): void
    {
        foreach ($handlers as $handler) {
            if (!$handler->aceita($record)) {
                continue;
            }

            try {
                $handler->registrar($record);
            } catch (Throwable $e) {
                $this->registrarFalhaDoHandler($handler, $record, $e);
            }
        }
    }

    /**
     * Avisa (pelos outros destinos) que um destino falhou. Sem recursão:
     * a falha não é reenviada ao destino que falhou.
     */
    private function registrarFalhaDoHandler(Handler $falhou, LogRecord $original, Throwable $e): void
    {
        $nome = (new \ReflectionClass($falhou))->getShortName();
        $aviso = $this->montar('warning', 'log_destino_falhou', [
            'operacao' => 'registrar_log',
            'status' => 'FAILED',
            'integracao' => $nome === 'SlackWebhookHandler' ? 'Slack' : $nome,
            'motivo' => $e->getMessage(),
            'evento_original' => $original->event,
        ]);

        $restantes = array_values(array_filter($this->handlers, static fn (Handler $h): bool => $h !== $falhou));

        if ($restantes === []) {
            error_log('[overdark] log_destino_falhou: ' . $e->getMessage());

            return;
        }

        foreach ($restantes as $handler) {
            try {
                if ($handler->aceita($aviso)) {
                    $handler->registrar($aviso);
                }
            } catch (Throwable) {
                error_log('[overdark] log_destino_falhou: ' . $e->getMessage());
            }
        }
    }
}

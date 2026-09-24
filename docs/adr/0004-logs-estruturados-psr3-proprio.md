# 0004 — Logs estruturados com logger próprio sobre a interface PSR-3

**Status:** Aceito · 2026-09-23

## Contexto

O OverDark precisava de logs que sirvam para **investigar**, e não só para "ter log". Era preciso um contrato fixo (`event`, `operacao`, `status`, `correlation_id` + domínio + `motivo`/`integracao`), correlação entre logs da mesma requisição, proteção de dados sensíveis e alerta no Slack para falhas. O padrão também serve de referência para outros sistemas do time.

Opções consideradas:

1. **Monolog:** padrão de mercado em PHP, com dezenas de handlers (inclusive Slack). Adiciona dependência de produção, e o contrato ainda precisaria ser implementado via processors/formatters.
2. **Logger próprio sem interface padrão:** simples, mas acopla todo o código a uma API caseira.
3. **Logger próprio implementando `Psr\Log\LoggerInterface`:** o código de negócio depende só da interface PSR-3 (`psr/log`, que contém só interfaces), e a implementação aplica o contrato.

## Decisão

Opção 3:

- `Core\Logging\StructuredLogger extends Psr\Log\AbstractLogger`. A mensagem PSR-3 **é o nome do evento**.
- `LogContext` guarda `correlation_id` e `usuario_id` da execução e os injeta em todo log.
- `Sanitizer` mascara chaves sensíveis e e-mails e resume exceções (5 frames).
- Destinos são `Handler`s: `JsonLinesFileHandler` (arquivo JSON Lines) e `SlackWebhookHandler` (Incoming Webhook, a partir de `error`).
- A falha de um destino é registrada nos demais (`log_destino_falhou`) e nunca derruba a requisição.
- `Application::handle()` gera o correlation_id, registra toda requisição e converte exceções não tratadas em `error`/`critical`.

## Consequências

- ✅ Casos de uso dependem só de `LoggerInterface`. Trocar pela Monolog é mudar uma ligação em `config/services.php`, desde que se replique o contrato num formatter.
- ✅ O contrato é garantido por teste (`tests/Feature/LogsTest.php`, `tests/Unit/Logging/`).
- ✅ Código pequeno e legível, bom pra ensinar como um logger estruturado funciona por dentro.
- ⚠️ Envio ao Slack é síncrono (timeout de 3s). Com volume alto, mover para fila/worker ou usar um coletor (Vector, Fluent Bit).
- ⚠️ Sem rotação de arquivo embutida. Em produção, usar `logrotate` ou enviar stdout para um agregador (Loki, Elastic, Datadog).
- ⚠️ Sem deduplicação/rate limit de alertas: uma queda do banco gera um alerta por requisição.

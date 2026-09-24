<?php

declare(strict_types=1);

namespace OverDark\Core\Logging\Handler;

use OverDark\Core\Logging\Level;
use OverDark\Core\Logging\LogRecord;

/**
 * Envia logs a partir de um nível (padrão: error) para um canal do Slack,
 * no mesmo formato "campo : valor" do contrato de log.
 */
final class SlackWebhookHandler implements Handler
{
    private const EMOJI = [
        'debug' => '⚪', 'info' => '✅', 'notice' => '🔵', 'warning' => '🟡',
        'error' => '🔴', 'critical' => '🚨', 'alert' => '🚨', 'emergency' => '🚨',
    ];

    /** Campos que não vão para o Slack (ruído para quem lê no celular). */
    private const OMITIDOS = ['trace'];

    public function __construct(
        private readonly string $webhookUrl,
        private readonly SlackTransport $transport,
        private readonly string $nivelMinimo = 'error',
        private readonly string $aplicacao = 'OverDark',
        private readonly string $ambiente = 'local',
    ) {
    }

    public function aceita(LogRecord $record): bool
    {
        return $this->webhookUrl !== '' && Level::atinge($record->level, $this->nivelMinimo);
    }

    public function registrar(LogRecord $record): void
    {
        $this->transport->enviar($this->webhookUrl, $this->payload($record));
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(LogRecord $record): array
    {
        $titulo = sprintf('%s %s · %s', self::EMOJI[$record->level] ?? '•', strtoupper($record->level), $record->event);
        $linhas = $this->linhas($record);
        $largura = max([0, ...array_map('strlen', array_keys($linhas))]);

        $corpo = '';
        foreach ($linhas as $campo => $valor) {
            $corpo .= str_pad($campo, $largura) . ' : ' . $valor . "\n";
        }

        return [
            'text' => $titulo . ' (' . $record->correlationId . ')',
            'blocks' => [
                ['type' => 'header', 'text' => ['type' => 'plain_text', 'text' => $titulo, 'emoji' => true]],
                ['type' => 'section', 'text' => ['type' => 'mrkdwn', 'text' => "```\n" . rtrim($corpo) . "\n```"]],
                ['type' => 'context', 'elements' => [[
                    'type' => 'mrkdwn',
                    'text' => sprintf('*%s* · %s · %s', $this->aplicacao, $this->ambiente, $record->timestamp->format('d/m/Y H:i:s')),
                ]]],
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function linhas(LogRecord $record): array
    {
        $dados = $record->toArray();
        unset($dados['timestamp'], $dados['level']);

        $linhas = [];
        foreach ($dados as $campo => $valor) {
            if ($valor === null || in_array($campo, self::OMITIDOS, true)) {
                continue;
            }

            if ($campo === 'excecao' && is_array($valor)) {
                $linhas['excecao'] = (string) ($valor['classe'] ?? '');
                $linhas['mensagem'] = (string) ($valor['mensagem'] ?? '');
                $linhas['arquivo'] = (string) ($valor['arquivo'] ?? '');
                continue;
            }

            $linhas[(string) $campo] = is_scalar($valor)
                ? (string) $valor
                : (json_encode($valor, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
        }

        return $linhas;
    }
}

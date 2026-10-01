<?php

declare(strict_types=1);

namespace OverDark\Tests\Unit\Logging;

use DateTimeImmutable;
use OverDark\Core\Logging\Handler\SlackTransport;
use OverDark\Core\Logging\Handler\SlackWebhookHandler;
use OverDark\Core\Logging\LogRecord;
use PHPUnit\Framework\TestCase;

final class SlackWebhookHandlerTest extends TestCase
{
    public function testSoEnviaAPartirDoNivelConfiguradoESeHouverWebhook(): void
    {
        $handler = new SlackWebhookHandler('https://hooks.slack.test/x', $this->transporte(), 'error');

        self::assertFalse($handler->aceita($this->record('warning')));
        self::assertTrue($handler->aceita($this->record('error')));
        self::assertTrue($handler->aceita($this->record('critical')));
        self::assertFalse((new SlackWebhookHandler('', $this->transporte()))->aceita($this->record('critical')));
    }

    public function testMensagemSegueOContratoCampoValor(): void
    {
        $transporte = $this->transporte();
        $handler = new SlackWebhookHandler('https://hooks.slack.test/x', $transporte, 'error', 'OverDark', 'local');

        $handler->registrar($this->record('critical'));

        self::assertCount(1, $transporte->enviados);
        $payload = $transporte->enviados[0];
        $blocos = $payload['blocks'];

        self::assertSame('🚨 CRITICAL · banco_indisponivel', $blocos[0]['text']['text']);
        $corpo = $blocos[1]['text']['text'];
        self::assertStringContainsString('event          : banco_indisponivel', $corpo);
        self::assertStringContainsString('operacao       : conectar_banco', $corpo);
        self::assertStringContainsString('status         : 503', $corpo);
        self::assertStringContainsString('correlation_id : req_884a1c', $corpo);
        self::assertStringContainsString('integracao     : MySQL', $corpo);
        self::assertStringContainsString('valor          : R$ 3.999,99', $corpo, 'Centavos exibidos em reais.');
        self::assertStringNotContainsString('valor_centavos', $corpo);
        self::assertStringNotContainsString('trace', $corpo, 'Stack trace fica só no arquivo.');
        self::assertStringContainsString('*OverDark* · local', $blocos[2]['elements'][0]['text']);
    }

    private function record(string $level): LogRecord
    {
        return new LogRecord(
            new DateTimeImmutable('2026-09-24 10:00:00'),
            $level,
            'banco_indisponivel',
            'conectar_banco',
            503,
            'req_884a1c',
            ['motivo' => 'conexao_recusada', 'integracao' => 'MySQL', 'valor_centavos' => 399999, 'trace' => ['a', 'b']],
        );
    }

    /**
     * @return SlackTransport&object{enviados: list<array<string, mixed>>}
     */
    private function transporte(): SlackTransport
    {
        return new class implements SlackTransport {
            /** @var list<array<string, mixed>> */
            public array $enviados = [];

            public function enviar(string $webhookUrl, array $payload): void
            {
                $this->enviados[] = $payload;
            }
        };
    }
}

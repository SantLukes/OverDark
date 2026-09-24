<?php

declare(strict_types=1);

namespace OverDark\Tests\Unit\Logging;

use OverDark\Core\Logging\Handler\Handler;
use OverDark\Core\Logging\LogContext;
use OverDark\Core\Logging\LogRecord;
use OverDark\Core\Logging\Sanitizer;
use OverDark\Core\Logging\StructuredLogger;
use OverDark\Tests\Support\InMemoryLogHandler;
use PHPUnit\Framework\TestCase;
use Psr\Log\InvalidArgumentException;
use RuntimeException;

final class StructuredLoggerTest extends TestCase
{
    private LogContext $contexto;
    private InMemoryLogHandler $logs;
    private StructuredLogger $logger;

    protected function setUp(): void
    {
        $this->contexto = new LogContext();
        $this->contexto->iniciar('req_teste1');
        $this->logs = new InMemoryLogHandler();
        $this->logger = new StructuredLogger($this->contexto, [$this->logs]);
    }

    public function testCamposBaseSaemSempreNaMesmaOrdem(): void
    {
        $this->logger->info('pedido_emitido', ['pedido_id' => 5821, 'status' => 'SUCCESS', 'operacao' => 'emitir_pedido']);

        $dados = $this->logs->unico('pedido_emitido')->toArray();

        self::assertSame(['timestamp', 'level', 'event', 'operacao', 'status', 'correlation_id', 'pedido_id'], array_keys($dados));
        self::assertSame('emitir_pedido', $dados['operacao']);
        self::assertSame('SUCCESS', $dados['status']);
        self::assertSame('req_teste1', $dados['correlation_id']);
    }

    public function testCamposBaseAusentesFicamNulosMasPresentes(): void
    {
        $this->logger->notice('algo_aconteceu');

        $dados = $this->logs->unico('algo_aconteceu')->toArray();

        self::assertArrayHasKey('operacao', $dados);
        self::assertNull($dados['operacao']);
    }

    public function testUsuarioDoContextoEntraAutomaticamente(): void
    {
        $this->contexto->definirUsuario(7);
        $this->logger->info('evento', ['operacao' => 'x', 'status' => 'SUCCESS']);

        self::assertSame(7, $this->logs->unico('evento')->campos['usuario_id']);
    }

    public function testSegredosNuncaChegamAoLog(): void
    {
        $this->logger->warning('login_recusado', [
            'senha' => 'minha-senha',
            '_token' => 'abc',
            'dados' => ['password' => 'x', 'SLACK_WEBHOOK_URL' => 'https://hooks.slack.com/...'],
        ]);

        $json = $this->logs->unico('login_recusado')->toJson();

        self::assertStringNotContainsString('minha-senha', $json);
        self::assertStringNotContainsString('hooks.slack.com', $json);
        self::assertSame(4, substr_count($json, '[REDACTED]'));
    }

    public function testExcecaoViraResumoEstruturadoComPoucosFrames(): void
    {
        $this->logger->error('erro_inesperado', ['exception' => new RuntimeException('falhou')]);

        $excecao = $this->logs->unico('erro_inesperado')->campos['excecao'];

        self::assertIsArray($excecao);
        self::assertSame(RuntimeException::class, $excecao['classe']);
        self::assertSame('falhou', $excecao['mensagem']);
        self::assertStringStartsWith('tests/Unit/Logging/StructuredLoggerTest.php:', $excecao['arquivo']);
        self::assertLessThanOrEqual(5, count($excecao['trace']));
    }

    public function testDestinoQueFalhaNaoDerrubaAplicacaoEAvisaOsOutros(): void
    {
        $quebrado = new class implements Handler {
            public function aceita(LogRecord $record): bool
            {
                return true;
            }

            public function registrar(LogRecord $record): void
            {
                throw new RuntimeException('Slack respondeu 404: no_service');
            }
        };

        (new StructuredLogger($this->contexto, [$quebrado, $this->logs]))->error('erro_inesperado');

        $aviso = $this->logs->unico('log_destino_falhou');
        self::assertSame('erro_inesperado', $aviso->campos['evento_original']);
        self::assertSame('Slack respondeu 404: no_service', $aviso->campos['motivo']);
        self::assertCount(1, $this->logs->doEvento('erro_inesperado'), 'O log original ainda chega aos outros destinos.');
    }

    public function testNivelInvalido(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->logger->log('grave', 'x');
    }

    public function testCorrelationIdRecebidoSoEhReaproveitadoSeForSeguro(): void
    {
        $contexto = new LogContext();

        self::assertSame('req_externo_123', $contexto->iniciar('req_externo_123'));
        self::assertMatchesRegularExpression('/^req_[0-9a-f]{6}$/', $contexto->iniciar("abc\n<script>"));
        self::assertMatchesRegularExpression('/^cli_[0-9a-f]{6}$/', $contexto->iniciar(null, 'cli'));
    }

    public function testMascaraDeEmail(): void
    {
        self::assertSame('l***@gmail.com', Sanitizer::mascararEmail('lucas.santana@gmail.com'));
        self::assertSame('n***', Sanitizer::mascararEmail('nao-e-email'));
        self::assertSame('', Sanitizer::mascararEmail(''));
    }
}

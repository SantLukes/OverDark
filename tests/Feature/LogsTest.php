<?php

declare(strict_types=1);

namespace OverDark\Tests\Feature;

use OverDark\Core\Database\DatabaseUnavailable;
use OverDark\Core\Http\Request;
use OverDark\Core\Logging\LogRecord;
use OverDark\Modules\Dashboard\Application\DashboardService;
use OverDark\Tests\Support\FeatureTestCase;
use PDO;
use PDOException;
use RuntimeException;

/**
 * Garante que o sistema emite os logs do contrato (docs/observabilidade.md).
 */
final class LogsTest extends FeatureTestCase
{
    public function testLoginComSucesso(): void
    {
        $usuario = $this->criarUsuario();

        $this->post('/login', ['email' => 'lucas@overdark.local', 'senha' => 'senha-secreta']);

        $log = $this->logs->unico('usuario_autenticado');
        self::assertSame('info', $log->level);
        self::assertSame('autenticar_usuario', $log->operacao);
        self::assertSame('SUCCESS', $log->status);
        self::assertSame($usuario->id, $log->campos['usuario_id']);
        self::assertSame('l***@overdark.local', $log->campos['email']);
    }

    public function testLoginRecusadoRegistraOMotivoRealSemASenha(): void
    {
        $this->criarUsuario();

        $this->post('/login', ['email' => 'lucas@overdark.local', 'senha' => 'senha-errada']);

        $log = $this->logs->unico('login_recusado');
        self::assertSame('warning', $log->level);
        self::assertSame(401, $log->status);
        self::assertSame('senha_incorreta', $log->campos['motivo']);
        self::assertStringNotContainsString('senha-errada', $this->todosOsLogs());
    }

    public function testMovimentacaoRegistrada(): void
    {
        $this->logarComo();

        $this->post('/movimentacoes', ['tipo' => 'receita', 'data' => '2026-03-05', 'descricao' => 'Salário', 'valor' => '5.200']);

        $log = $this->logs->unico('movimentacao_registrada');
        self::assertSame('registrar_movimentacao', $log->operacao);
        self::assertSame('SUCCESS', $log->status);
        self::assertSame(520000, $log->campos['valor_centavos']);
        self::assertSame('2026-03', $log->campos['competencia']);
        self::assertIsInt($log->campos['movimentacao_id']);
        self::assertStringNotContainsString('Salário', $log->toJson(), 'Descrição digitada pelo usuário não vai para o log.');
    }

    public function testCompraParceladaRegistrada(): void
    {
        $this->logarComo();

        $this->post('/movimentacoes', ['tipo' => 'cartao', 'data' => '2026-03-11', 'descricao' => 'Notebook', 'valor' => '3999,99', 'parcelas' => '10']);

        $log = $this->logs->unico('compra_parcelada_registrada');
        self::assertSame(10, $log->campos['parcelas']);
        self::assertSame(399999, $log->campos['valor_total_centavos']);
        self::assertSame('2026-03', $log->campos['primeira_competencia']);
        self::assertSame('2026-12', $log->campos['ultima_competencia']);
        self::assertCount(10, (array) $log->campos['movimentacao_ids']);
    }

    public function testValidacaoRecusadaRegistraOsCamposSemOsValores(): void
    {
        $this->logarComo();

        $this->post('/movimentacoes', ['tipo' => 'gasto', 'data' => '2026-03-10', 'descricao' => 'Mercado', 'valor' => 'abc']);

        $log = $this->logs->unico('movimentacao_validacao_recusada');
        self::assertSame('warning', $log->level);
        self::assertSame(422, $log->status);
        self::assertSame(['valor'], $log->campos['campos']);
        self::assertStringNotContainsString('abc', $log->toJson());
    }

    public function testExclusaoEExclusaoNegada(): void
    {
        $this->logarComo();
        $this->post('/movimentacoes', ['tipo' => 'cartao', 'data' => '2026-03-11', 'descricao' => 'TV', 'valor' => '1000', 'parcelas' => '4']);
        $id = (int) $this->valor('SELECT MIN(id) FROM movimentacoes');

        $this->post('/movimentacoes/excluir', ['id' => $id]);
        $this->post('/movimentacoes/excluir', ['id' => $id]);

        $excluida = $this->logs->unico('movimentacao_excluida');
        self::assertSame(4, $excluida->campos['quantidade']);
        self::assertNotNull($excluida->campos['grupo_parcelamento']);

        $negada = $this->logs->unico('movimentacao_exclusao_negada');
        self::assertSame(404, $negada->status);
        self::assertSame($id, $negada->campos['movimentacao_id']);
    }

    public function testTodaRequisicaoGeraUmLogComOCorrelationIdDoHeader(): void
    {
        $this->logarComo();

        $response = $this->get('/dashboard');

        $log = $this->logs->unico('http_requisicao_concluida');
        self::assertSame(200, $log->status);
        self::assertSame('DashboardController::index', $log->campos['handler']);
        self::assertIsFloat($log->campos['duracao_ms']);
        self::assertSame($response->headers['X-Correlation-ID'], $log->correlationId);
    }

    public function testCorrelationIdRecebidoEhPropagado(): void
    {
        $this->app->handle(new Request('GET', '/login', headers: ['x-correlation-id' => 'req_vindo_do_gateway']));

        self::assertSame('req_vindo_do_gateway', $this->logs->unico('http_requisicao_concluida')->correlationId);
    }

    public function testTodosOsLogsDeUmaRequisicaoCompartilhamOCorrelationId(): void
    {
        $this->logarComo();
        $this->logs->records = []; // descarta o usuario_criado do preparo do teste

        $this->post('/movimentacoes', ['tipo' => 'receita', 'data' => '2026-03-05', 'descricao' => 'X', 'valor' => '1']);

        $ids = array_unique(array_map(static fn (LogRecord $r): string => $r->correlationId, $this->logs->records));
        self::assertCount(1, $ids);
        self::assertCount(2, $this->logs->records, 'movimentacao_registrada + http_requisicao_concluida');
    }

    public function testBancoForaDoArViraCriticalCom503(): void
    {
        $this->logarComo();
        $this->app->container()->set(PDO::class, static fn () => throw new DatabaseUnavailable('db:3306', 'overdark', new PDOException('getaddrinfo for db failed')));

        $response = $this->get('/dashboard');

        self::assertSame(503, $response->status);
        self::assertStringContainsString($response->headers['X-Correlation-ID'], $response->body, 'Tela de erro mostra o código para o suporte.');

        $log = $this->logs->unico('banco_indisponivel');
        self::assertSame('critical', $log->level);
        self::assertSame(503, $log->status);
        self::assertSame('MySQL', $log->campos['integracao']);
        self::assertSame('conexao_recusada', $log->campos['motivo']);
    }

    public function testErroInesperadoVira500ComExcecaoNoLog(): void
    {
        $this->logarComo();
        $this->app->container()->set(DashboardService::class, static fn () => throw new RuntimeException('bug proposital'));

        self::assertSame(500, $this->get('/dashboard')->status);

        $log = $this->logs->unico('erro_inesperado');
        self::assertSame('error', $log->level);
        self::assertSame('RuntimeException', $log->campos['motivo']);
        self::assertSame('bug proposital', ((array) $log->campos['excecao'])['mensagem']);
    }

    public function testCsrfInvalidoEhRegistrado(): void
    {
        $this->post('/login', ['email' => 'x@y.z', 'senha' => '123'], comCsrf: false);

        self::assertSame(419, $this->logs->unico('csrf_token_invalido')->status);
    }

    public function testTodoLogTemOsCamposBaseDoContrato(): void
    {
        $this->criarUsuario();
        $this->post('/login', ['email' => 'lucas@overdark.local', 'senha' => 'senha-secreta']);
        $this->post('/movimentacoes', ['tipo' => 'receita', 'data' => '2026-03-05', 'descricao' => 'X', 'valor' => '1']);
        $this->post('/movimentacoes', ['tipo' => 'receita', 'data' => '', 'descricao' => '', 'valor' => '']);
        $this->post('/logout');
        $this->get('/nao-existe');

        self::assertGreaterThan(8, count($this->logs->records));

        foreach ($this->logs->records as $log) {
            self::assertMatchesRegularExpression('/^[a-z]+(_[a-z]+)+$/', $log->event, 'event em snake_case');
            self::assertNotNull($log->operacao, "{$log->event} sem operacao");
            self::assertNotNull($log->status, "{$log->event} sem status");
            self::assertMatchesRegularExpression('/^req_[0-9a-f]{6}$/', $log->correlationId);
        }
    }

    private function todosOsLogs(): string
    {
        return implode("\n", array_map(static fn (LogRecord $r): string => $r->toJson(), $this->logs->records));
    }
}

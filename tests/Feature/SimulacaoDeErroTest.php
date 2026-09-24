<?php

declare(strict_types=1);

namespace OverDark\Tests\Feature;

use OverDark\Modules\Movimentacoes\Infrastructure\Demo\FechamentoMensalEmAndamento;
use OverDark\Tests\Support\FeatureTestCase;

/**
 * Reproduz, com lock REAL no MySQL, o cenário da demo: a rotina de fechamento
 * mensal trava as movimentações da cliente enquanto ela tenta cadastrar.
 *
 * O sistema deve: mostrar a tela com o código do erro, devolver o mesmo código
 * no header X-Correlation-ID, registrar UM log de erro com o contexto de negócio
 * e não gravar nada pela metade. Outros usuários não são afetados.
 */
final class SimulacaoDeErroTest extends FeatureTestCase
{
    private const COMPRA = ['tipo' => 'cartao', 'data' => '2026-03-11', 'descricao' => 'Notebook', 'valor' => '3999,99', 'parcelas' => '10'];

    public function testRotinaConcorrenteTravandoARegistroGeraLogComContextoETelaComCodigo(): void
    {
        $cliente = $this->criarUsuario('cliente@overdark.local');
        $outra = $this->criarUsuario('outra@overdark.local');
        // Banco "de produção": as duas já têm histórico. (Com a tabela vazia, o lock
        // de intervalo do InnoDB cobriria o índice inteiro e travaria todo mundo.)
        $this->pdo()->exec(sprintf(
            "INSERT INTO movimentacoes (usuario_id, tipo, descricao, valor_centavos, data, criado_em)
             VALUES (%d, 'receita', 'Salário', 500000, '2026-01-05', NOW()), (%d, 'receita', 'Salário', 500000, '2026-01-05', NOW())",
            $cliente->id,
            $outra->id,
        ));

        $this->logarComo($cliente);
        $this->logs->records = [];

        $rotina = FechamentoMensalEmAndamento::travarMovimentacoesDo($cliente->id);
        $response = $this->post('/movimentacoes', self::COMPRA);

        $codigo = $response->headers['X-Correlation-ID'];

        // Cliente: tela de erro com o código para informar ao suporte
        self::assertSame(500, $response->status);
        self::assertStringContainsString('<code id="errorCode">' . $codigo . '</code>', $response->body);

        // Log: um único erro, com contexto de negócio e causa técnica legível
        $log = $this->logs->unico('movimentacao_registro_falhou');
        self::assertSame('error', $log->level);
        self::assertSame('registrar_movimentacao', $log->operacao);
        self::assertSame(500, $log->status);
        self::assertSame($codigo, $log->correlationId);
        self::assertSame($cliente->id, $log->campos['usuario_id']);
        self::assertSame('cartao', $log->campos['tipo']);
        self::assertSame(10, $log->campos['parcelas']);
        self::assertSame(399999, $log->campos['valor_centavos']);
        self::assertSame('lock_wait_timeout', $log->campos['motivo']);
        self::assertSame('MySQL', $log->campos['integracao']);
        self::assertSame(1205, $log->campos['errno']);
        self::assertTrue($log->campos['retentavel']);
        self::assertGreaterThanOrEqual(900, $log->campos['duracao_ms'], 'Esperou o lock antes de desistir.');
        self::assertSame([], $this->logs->doEvento('consulta_banco_falhou'), 'Sem log duplicado no kernel.');
        self::assertSame([], $this->logs->doEvento('erro_inesperado'));
        self::assertSame(1, (int) $this->valor('SELECT COUNT(*) FROM movimentacoes WHERE usuario_id = ' . $cliente->id), 'Nada gravado pela metade.');

        // Enquanto isso, outra usuária grava normalmente: o problema é localizado
        $this->session->invalidate();
        $this->logarComo($outra);
        $daOutra = $this->post('/movimentacoes', self::COMPRA);
        self::assertSame(302, $daOutra->status, 'Outra usuária não é afetada.');

        // A rotina termina → a cliente tenta de novo e consegue
        unset($rotina);
        $this->session->invalidate();
        $this->logarComo($cliente);
        $novaTentativa = $this->post('/movimentacoes', self::COMPRA);
        self::assertSame(302, $novaTentativa->status, 'Depois da rotina, a cliente consegue.');
        self::assertCount(2, $this->logs->doEvento('compra_parcelada_registrada'));
    }
}

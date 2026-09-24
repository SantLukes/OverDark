<?php

declare(strict_types=1);

namespace OverDark\Tests\Feature;

use OverDark\Tests\Support\FeatureTestCase;

final class MovimentacoesTest extends FeatureTestCase
{
    public function testComecaZerada(): void
    {
        $this->logarComo();

        $body = $this->get('/movimentacoes')->body;

        self::assertStringContainsString('Nenhuma movimentação em Março 2026', $body);
        self::assertSame(3, substr_count($body, '<h2>R$ 0</h2>'));
    }

    public function testRegistraEListaNoMesDaData(): void
    {
        $this->logarComo();

        $response = $this->post('/movimentacoes', [
            'tipo' => 'receita', 'data' => '2026-03-05', 'descricao' => 'Salário', 'valor' => '5.200,00',
        ]);

        self::assertRedirect('/movimentacoes?competencia=2026-03', $response);

        $body = $this->get('/movimentacoes', ['competencia' => '2026-03'])->body;
        self::assertStringContainsString('Movimentação registrada: receita &quot;Salário&quot; de R$ 5.200.', $body);
        self::assertStringContainsString('<h2>R$ 5.200</h2>', $body);
        self::assertStringContainsString('05/03/2026', $body);
    }

    public function testCompraParceladaPersisteUmaLinhaPorMes(): void
    {
        $this->logarComo();

        $this->post('/movimentacoes', [
            'tipo' => 'cartao', 'data' => '2026-03-11', 'descricao' => 'Notebook', 'valor' => '3999,99', 'parcelas' => '10',
        ]);

        $linhas = $this->linhas('SELECT valor_centavos, data, parcela_numero FROM movimentacoes ORDER BY parcela_numero');
        self::assertCount(10, $linhas);
        self::assertSame(399999, array_sum(array_map(static fn (array $l): int => (int) $l['valor_centavos'], $linhas)));
        self::assertSame('2026-12-11', $linhas[9]['data']);

        $abril = $this->get('/movimentacoes', ['competencia' => '2026-04'])->body;
        self::assertStringContainsString('>2/10</span>', $abril);
        self::assertStringContainsString('R$ 399,99 no cartão', $abril);
    }

    public function testErroDeValidacaoReabreOModalComMensagensEValores(): void
    {
        $this->logarComo();

        $response = $this->post('/movimentacoes', [
            'tipo' => 'gasto', 'data' => '2026-03-10', 'descricao' => 'Mercado', 'valor' => 'abc', 'competencia_atual' => '2026-03',
        ]);

        self::assertRedirect('/movimentacoes?competencia=2026-03', $response);
        self::assertSame(0, (int) $this->valor('SELECT COUNT(*) FROM movimentacoes'));

        $body = $this->get('/movimentacoes', ['competencia' => '2026-03'])->body;
        self::assertStringContainsString('data-modal-autoopen', $body);
        self::assertStringContainsString('Informe um valor válido', $body);
        self::assertStringContainsString('value="Mercado"', $body);
    }

    public function testExcluirParcelaRemoveTodasAsParcelas(): void
    {
        $this->logarComo();
        $this->post('/movimentacoes', ['tipo' => 'cartao', 'data' => '2026-03-11', 'descricao' => 'TV', 'valor' => '1000', 'parcelas' => '4']);
        $id = (int) $this->valor('SELECT id FROM movimentacoes WHERE parcela_numero = 3');

        self::assertRedirect('/movimentacoes?competencia=2026-05', $this->post('/movimentacoes/excluir', ['id' => $id, 'competencia_atual' => '2026-05']));
        self::assertSame(0, (int) $this->valor('SELECT COUNT(*) FROM movimentacoes'));
        self::assertStringContainsString('excluída (4 parcelas)', $this->get('/movimentacoes', ['competencia' => '2026-05'])->body);
    }

    public function testUsuarioNaoVeNemExcluiMovimentacaoDeOutro(): void
    {
        $dono = $this->logarComo();
        $this->post('/movimentacoes', ['tipo' => 'receita', 'data' => '2026-03-05', 'descricao' => 'Segredo', 'valor' => '100']);
        $id = (int) $this->valor('SELECT id FROM movimentacoes');

        $this->session->invalidate(); // logout do dono
        $this->logarComo($this->criarUsuario('outro@overdark.local'));

        self::assertStringNotContainsString('Segredo', $this->get('/movimentacoes')->body);
        self::assertSame(404, $this->post('/movimentacoes/excluir', ['id' => $id])->status);
        self::assertSame(1, (int) $this->valor('SELECT COUNT(*) FROM movimentacoes WHERE usuario_id = ' . $dono->id));
    }

    public function testEscritaSemCsrfEhRejeitada(): void
    {
        $this->logarComo();

        $response = $this->post('/movimentacoes', ['tipo' => 'receita', 'data' => '2026-03-05', 'descricao' => 'X', 'valor' => '1'], comCsrf: false);

        self::assertSame(419, $response->status);
    }
}

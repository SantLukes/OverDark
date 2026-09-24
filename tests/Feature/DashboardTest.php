<?php

declare(strict_types=1);

namespace OverDark\Tests\Feature;

use OverDark\Tests\Support\FeatureTestCase;

final class DashboardTest extends FeatureTestCase
{
    public function testComecaZerado(): void
    {
        $this->logarComo();

        $body = $this->get('/dashboard')->body;

        self::assertSame(4, substr_count($body, '<h5 class="fw-bold">R$ 0</h5>'));
        self::assertStringContainsString('Receitas (Março)', $body);
    }

    public function testRefleteAsMovimentacoesDoMes(): void
    {
        $this->logarComo();
        $this->post('/movimentacoes', ['tipo' => 'receita', 'data' => '2026-03-05', 'descricao' => 'Salário', 'valor' => '3200']);
        $this->post('/movimentacoes', ['tipo' => 'gasto', 'data' => '2026-03-08', 'descricao' => 'Mercado', 'valor' => '1450']);
        $this->post('/movimentacoes', ['tipo' => 'cartao', 'data' => '2026-03-11', 'descricao' => 'Fone', 'valor' => '800', 'parcelas' => '2']);

        $body = $this->get('/dashboard')->body;

        self::assertStringContainsString('<h5 class="fw-bold">R$ 3.200</h5>', $body);
        self::assertStringContainsString('<h5 class="fw-bold">R$ 1.850</h5>', $body);
        self::assertStringContainsString('<h5 class="fw-bold">R$ 1.350</h5>', $body);
        self::assertStringContainsString('<h5 class="fw-bold">R$ 400</h5>', $body);
        self::assertMatchesRegularExpression('/data-chart="\{&quot;labels&quot;:\[&quot;Out&quot;/', $body);
        self::assertStringContainsString('<span class="is-negative">- R$ 400</span>', $body, 'Abril tem só a 2ª parcela.');
    }
}

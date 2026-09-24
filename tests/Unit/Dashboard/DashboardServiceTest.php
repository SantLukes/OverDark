<?php

declare(strict_types=1);

namespace OverDark\Tests\Unit\Dashboard;

use DateTimeImmutable;
use OverDark\Core\Clock\FixedClock;
use OverDark\Modules\Dashboard\Application\DashboardService;
use OverDark\Modules\Movimentacoes\Application\MovimentacaoService;
use OverDark\Modules\Movimentacoes\Application\NovaMovimentacao;
use OverDark\Modules\Movimentacoes\Application\RegistrarMovimentacao;
use OverDark\Shared\Domain\Money;
use OverDark\Shared\Domain\PontoMensal;
use OverDark\Tests\Support\InMemoryMovimentacaoRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class DashboardServiceTest extends TestCase
{
    public function testIndicadoresEHistoricoVemDasMovimentacoes(): void
    {
        $repo = new InMemoryMovimentacaoRepository();
        $registrar = new RegistrarMovimentacao($repo, new NullLogger());
        $registrar->executar(1, new NovaMovimentacao('receita', '2026-03-05', 'Salário', '3200'));
        $registrar->executar(1, new NovaMovimentacao('gasto', '2026-03-08', 'Mercado', '1450'));
        $registrar->executar(1, new NovaMovimentacao('cartao', '2026-03-11', 'Fone', '800', '2'));
        $registrar->executar(2, new NovaMovimentacao('receita', '2026-03-05', 'Outro usuário', '9999'));

        $servico = new DashboardService(new MovimentacaoService($repo, new FixedClock(new DateTimeImmutable('2026-03-15'))));
        $painel = $servico->painel(1);

        self::assertSame(320000, $painel->indicadores->receitas->cents);
        self::assertSame(185000, $painel->indicadores->gastos->cents, 'Gastos = 1450 + 1ª parcela de 400.');
        self::assertSame(40000, $painel->indicadores->cartao->cents);
        self::assertSame(135000, $painel->indicadores->saldo()->cents);

        self::assertSame(['Out', 'Nov', 'Dez', 'Jan', 'Fev', 'Mar'], array_map(static fn (PontoMensal $p) => $p->rotulo, $painel->evolucaoSaldo));
        self::assertCount(12, $painel->resumoMensal);
        self::assertSame('Abril', $painel->resumoMensal[3]->rotulo);
        self::assertTrue($painel->resumoMensal[3]->valor->equals(Money::fromReais(-400)), '2ª parcela cai em abril.');
    }
}

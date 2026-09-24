<?php

declare(strict_types=1);

namespace OverDark\Tests\Unit\Investimentos;

use InvalidArgumentException;
use OverDark\Modules\Investimentos\Domain\Alocacao;
use OverDark\Modules\Investimentos\Domain\Investimento;
use OverDark\Modules\Investimentos\Domain\ResumoCarteira;
use OverDark\Modules\Investimentos\Domain\TipoInvestimento;
use OverDark\Shared\Domain\Money;
use PHPUnit\Framework\TestCase;

final class InvestimentoTest extends TestCase
{
    public function testResultadoEhValorAtualMenosInvestido(): void
    {
        $lucro = new Investimento('CDI', TipoInvestimento::Reserva, Money::fromReais(10000), Money::fromReais(10860));
        self::assertSame(86000, $lucro->resultado()->cents);
        self::assertEqualsWithDelta(8.6, $lucro->rentabilidade(), 0.001);

        $prejuizo = new Investimento('BTC', TipoInvestimento::Cripto, Money::fromReais(1000), Money::fromReais(800));
        self::assertSame(-20000, $prejuizo->resultado()->cents);
    }

    public function testValorAtualOpcionalAssumeValorInvestido(): void
    {
        $investimento = new Investimento('Caixa', TipoInvestimento::Reserva, Money::fromReais(5000));

        self::assertTrue($investimento->valorAtual->equals(Money::fromReais(5000)));
        self::assertTrue($investimento->resultado()->isZero());
    }

    public function testValorInvestidoDeveSerPositivo(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Investimento('X', TipoInvestimento::Acoes, Money::zero());
    }

    public function testResumoDaCarteira(): void
    {
        $resumo = new ResumoCarteira(Money::fromReais(62300), [
            new Alocacao(TipoInvestimento::Reserva, Money::fromReais(12400)),
            new Alocacao(TipoInvestimento::RendaFixa, Money::fromReais(18600)),
            new Alocacao(TipoInvestimento::Fundos, Money::fromReais(14800)),
            new Alocacao(TipoInvestimento::Acoes, Money::fromReais(15900)),
            new Alocacao(TipoInvestimento::Cripto, Money::fromReais(7240)),
        ]);

        self::assertSame(6894000, $resumo->valorAtual()->cents);
        self::assertSame(664000, $resumo->lucro()->cents);
        self::assertEqualsWithDelta(10.66, $resumo->rentabilidade(), 0.005);
        self::assertSame(5, $resumo->quantidadeClasses());
    }

    public function testClasseSemSaldoNaoContaNaDistribuicao(): void
    {
        $resumo = new ResumoCarteira(Money::fromReais(100), [
            new Alocacao(TipoInvestimento::Reserva, Money::fromReais(100)),
            new Alocacao(TipoInvestimento::Cripto, Money::zero()),
        ]);

        self::assertSame(1, $resumo->quantidadeClasses());
    }
}

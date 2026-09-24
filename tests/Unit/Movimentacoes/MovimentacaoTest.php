<?php

declare(strict_types=1);

namespace OverDark\Tests\Unit\Movimentacoes;

use DateTimeImmutable;
use InvalidArgumentException;
use OverDark\Modules\Movimentacoes\Domain\ConsolidadoMensal;
use OverDark\Modules\Movimentacoes\Domain\Movimentacao;
use OverDark\Modules\Movimentacoes\Domain\Parcela;
use OverDark\Modules\Movimentacoes\Domain\SituacaoFluxo;
use OverDark\Modules\Movimentacoes\Domain\TipoMovimentacao;
use OverDark\Shared\Domain\Competencia;
use OverDark\Shared\Domain\Money;
use PHPUnit\Framework\TestCase;

final class MovimentacaoTest extends TestCase
{
    public function testReceitaEntraEGastoECartaoSaemDoFluxo(): void
    {
        self::assertSame(520000, $this->mov(TipoMovimentacao::Receita, 5200)->valorNoFluxo()->cents);
        self::assertSame(-42000, $this->mov(TipoMovimentacao::Gasto, 420)->valorNoFluxo()->cents);
        self::assertSame(-39900, $this->mov(TipoMovimentacao::CartaoDeCredito, 399)->valorNoFluxo()->cents);
    }

    public function testSomenteCartaoPodeSerParcela(): void
    {
        $parcela = new Parcela(1, 3, 'grupo');
        self::assertSame('1/3', $this->mov(TipoMovimentacao::CartaoDeCredito, 100, $parcela)->parcela?->rotulo());

        $this->expectException(InvalidArgumentException::class);
        $this->mov(TipoMovimentacao::Gasto, 100, $parcela);
    }

    public function testValorDeveSerPositivo(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->mov(TipoMovimentacao::Receita, 0);
    }

    public function testDescricaoObrigatoria(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Movimentacao(TipoMovimentacao::Receita, '  ', Money::fromReais(10), new DateTimeImmutable());
    }

    public function testDescricaoTemLimiteDeTamanho(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Movimentacao(TipoMovimentacao::Receita, str_repeat('a', 161), Money::fromReais(10), new DateTimeImmutable());
    }

    public function testCompetenciaVemDaData(): void
    {
        self::assertSame('2026-03', $this->mov(TipoMovimentacao::Receita, 1)->competencia()->chave());
    }

    public function testSaldoConsideraGastosECartao(): void
    {
        $c = new ConsolidadoMensal(new Competencia(2026, 3), Money::fromReais(8450), Money::fromReais(5000), Money::fromReais(920), 48);

        self::assertSame(592000, $c->gastosTotais()->cents);
        self::assertSame(253000, $c->saldo()->cents);
        self::assertSame(SituacaoFluxo::Positivo, $c->situacao());
    }

    public function testSaldoZeroEhPositivoENegativoEhNegativo(): void
    {
        $mes = new Competencia(2026, 3);

        self::assertSame(SituacaoFluxo::Positivo, (new ConsolidadoMensal($mes, Money::fromReais(100), Money::fromReais(100), Money::zero(), 1))->situacao());
        self::assertSame(SituacaoFluxo::Negativo, (new ConsolidadoMensal($mes, Money::fromReais(100), Money::fromReais(100), Money::fromReais(1), 2))->situacao());
    }

    public function testVariacaoDaReceitaEmRelacaoAoMesAnterior(): void
    {
        $fev = new ConsolidadoMensal(new Competencia(2026, 2), Money::fromReais(1000), Money::zero(), Money::zero(), 1);
        $mar = new ConsolidadoMensal(new Competencia(2026, 3), Money::fromReais(1124), Money::zero(), Money::zero(), 1);

        self::assertEqualsWithDelta(12.4, $mar->comparadoCom($fev)->variacaoReceita, 0.001);
        self::assertNull($mar->comparadoCom(ConsolidadoMensal::vazio(new Competencia(2026, 2)))->variacaoReceita);
    }

    private function mov(TipoMovimentacao $tipo, int $reais, ?Parcela $parcela = null): Movimentacao
    {
        return new Movimentacao($tipo, 'Teste', Money::fromReais($reais), new DateTimeImmutable('2026-03-01'), $parcela);
    }
}

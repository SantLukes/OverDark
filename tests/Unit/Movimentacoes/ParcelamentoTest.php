<?php

declare(strict_types=1);

namespace OverDark\Tests\Unit\Movimentacoes;

use DateTimeImmutable;
use InvalidArgumentException;
use OverDark\Modules\Movimentacoes\Domain\Parcelamento;
use OverDark\Shared\Domain\Money;
use PHPUnit\Framework\TestCase;

final class ParcelamentoTest extends TestCase
{
    public function testDivideOTotalECentavosQueSobramVaoParaAPrimeira(): void
    {
        $valores = array_map(
            static fn (Money $m): int => $m->cents,
            (new Parcelamento(Money::fromReais('3999.99'), 10))->valores(),
        );

        self::assertSame(40008, $valores[0]);
        self::assertSame(array_fill(0, 9, 39999), array_slice($valores, 1));
        self::assertSame(399999, array_sum($valores), 'A soma das parcelas deve fechar com o total.');
    }

    public function testValorDaParcelaInformadoPrevalece(): void
    {
        $valores = (new Parcelamento(Money::fromReais(1000), 3, Money::fromReais(350)))->valores();

        self::assertCount(3, $valores);
        self::assertSame([35000, 35000, 35000], array_map(static fn (Money $m): int => $m->cents, $valores));
    }

    public function testParcelasCaemNoMesmoDiaDosMesesSeguintesAjustandoFimDeMes(): void
    {
        $datas = (new Parcelamento(Money::fromReais(100), 4))->datas(new DateTimeImmutable('2026-01-31'));

        self::assertSame(
            ['2026-01-31', '2026-02-28', '2026-03-31', '2026-04-30'],
            array_map(static fn (DateTimeImmutable $d): string => $d->format('Y-m-d'), $datas),
        );
    }

    public function testParcelamentoAtravessaOAno(): void
    {
        $datas = (new Parcelamento(Money::fromReais(100), 3))->datas(new DateTimeImmutable('2026-11-10'));

        self::assertSame('2027-01-10', $datas[2]->format('Y-m-d'));
    }

    public function testQuantidadeRestritaAsOpcoes(): void
    {
        self::assertSame([2, 3, 4, 6, 10], Parcelamento::OPCOES);

        $this->expectException(InvalidArgumentException::class);
        new Parcelamento(Money::fromReais(100), 5);
    }
}

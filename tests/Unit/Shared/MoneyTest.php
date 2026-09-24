<?php

declare(strict_types=1);

namespace OverDark\Tests\Unit\Shared;

use InvalidArgumentException;
use OverDark\Shared\Domain\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function testGuardaValoresEmCentavos(): void
    {
        self::assertSame(320000, Money::fromReais(3200)->cents);
        self::assertSame(123456, Money::fromReais('1234.56')->cents);
        self::assertSame(1050, Money::fromReais('10.5')->cents);
        self::assertSame(-990, Money::fromReais('-9.90')->cents);
    }

    public function testNaoSofreComImprecisaoDeFloat(): void
    {
        $total = Money::fromReais('0.1')->plus(Money::fromReais('0.2'));

        self::assertTrue($total->equals(Money::fromReais('0.3')));
    }

    #[DataProvider('valoresInvalidos')]
    public function testRejeitaStringInvalida(string $valor): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromReais($valor);
    }

    /** @return iterable<array{string}> */
    public static function valoresInvalidos(): iterable
    {
        yield ['abc'];
        yield ['1,50'];
        yield ['1.234'];
        yield [''];
    }

    public function testAritmetica(): void
    {
        $a = Money::fromReais(100);
        $b = Money::fromReais(30);

        self::assertSame(13000, $a->plus($b)->cents);
        self::assertSame(7000, $a->minus($b)->cents);
        self::assertSame(-7000, $b->minus($a)->cents);
        self::assertSame(7000, $b->minus($a)->abs()->cents);
        self::assertSame(16000, Money::sum($a, $b, $b)->cents);
        self::assertSame(0, Money::sum()->cents);
    }

    public function testPercentual(): void
    {
        self::assertEqualsWithDelta(10.658, Money::fromReais(6640)->percentOf(Money::fromReais(62300)), 0.001);
        self::assertSame(0.0, Money::fromReais(10)->percentOf(Money::zero()));
    }

    public function testSinal(): void
    {
        self::assertTrue(Money::fromCents(1)->isPositive());
        self::assertTrue(Money::fromCents(-1)->isNegative());
        self::assertTrue(Money::zero()->isZero());
    }
}

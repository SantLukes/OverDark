<?php

declare(strict_types=1);

namespace OverDark\Tests\Unit\Shared;

use DateTimeImmutable;
use OverDark\Shared\Domain\Money;
use OverDark\Shared\Formatting\Formatter;
use PHPUnit\Framework\TestCase;

final class FormatterTest extends TestCase
{
    public function testMoedaSemCentavosOmiteDecimais(): void
    {
        self::assertSame('R$ 3.200', Formatter::money(Money::fromReais(3200)));
        self::assertSame('R$ 68.940', Formatter::money(Money::fromReais(68940)));
        self::assertSame('R$ 0', Formatter::money(Money::zero()));
    }

    public function testMoedaComCentavos(): void
    {
        self::assertSame('R$ 1.234,56', Formatter::money(Money::fromReais('1234.56')));
        self::assertSame('- R$ 9,90', Formatter::money(Money::fromReais('-9.90')));
    }

    public function testMoedaComSinal(): void
    {
        self::assertSame('+ R$ 860', Formatter::signedMoney(Money::fromReais(860)));
        self::assertSame('- R$ 120', Formatter::signedMoney(Money::fromReais(-120)));
        self::assertSame('R$ 0', Formatter::signedMoney(Money::zero()));
    }

    public function testPercentual(): void
    {
        self::assertSame('10,66%', Formatter::percent(10.658));
        self::assertSame('+10,66%', Formatter::percent(10.658, signed: true));
        self::assertSame('-3,20%', Formatter::percent(-3.2, signed: true));
        self::assertSame('+12,4%', Formatter::percent(12.4, signed: true, decimals: 1));
    }

    public function testData(): void
    {
        self::assertSame('05/03/2026', Formatter::date(new DateTimeImmutable('2026-03-05')));
    }
}

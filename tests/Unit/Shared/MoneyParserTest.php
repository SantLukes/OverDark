<?php

declare(strict_types=1);

namespace OverDark\Tests\Unit\Shared;

use InvalidArgumentException;
use OverDark\Shared\Formatting\MoneyParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MoneyParserTest extends TestCase
{
    #[DataProvider('validos')]
    public function testAceitaFormatosComuns(string $entrada, int $centavos): void
    {
        self::assertSame($centavos, MoneyParser::parse($entrada)->cents);
    }

    /** @return iterable<array{string, int}> */
    public static function validos(): iterable
    {
        yield ['1234,56', 123456];
        yield ['1.234,56', 123456];
        yield ['R$ 1.234,56', 123456];
        yield ['1234.56', 123456];
        yield ['1234', 123400];
        yield ['1.234', 123400];
        yield ['0,5', 50];
        yield ['  42  ', 4200];
    }

    #[DataProvider('invalidos')]
    public function testRejeitaEntradasInvalidas(string $entrada): void
    {
        $this->expectException(InvalidArgumentException::class);
        MoneyParser::parse($entrada);
    }

    /** @return iterable<array{string}> */
    public static function invalidos(): iterable
    {
        yield [''];
        yield ['abc'];
        yield ['12,345'];
        yield ['1,234,56'];
        yield ['-10'];
    }
}

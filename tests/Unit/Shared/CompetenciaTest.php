<?php

declare(strict_types=1);

namespace OverDark\Tests\Unit\Shared;

use DateTimeImmutable;
use InvalidArgumentException;
use OverDark\Shared\Domain\Competencia;
use PHPUnit\Framework\TestCase;

final class CompetenciaTest extends TestCase
{
    public function testRotulosEChave(): void
    {
        $c = Competencia::fromString('2026-03');

        self::assertSame('2026-03', $c->chave());
        self::assertSame('Março 2026', $c->rotulo());
        self::assertSame('Março', $c->nomeMes());
        self::assertSame('Mar', $c->rotuloCurto());
    }

    public function testSomarMesesAtravessaAnos(): void
    {
        self::assertSame('2027-02', (new Competencia(2026, 11))->somarMeses(3)->chave());
        self::assertSame('2025-12', (new Competencia(2026, 1))->anterior()->chave());
        self::assertSame('2025-10', (new Competencia(2026, 3))->somarMeses(-5)->chave());
    }

    public function testPrimeiroEUltimoDia(): void
    {
        $fev = new Competencia(2028, 2);

        self::assertSame('2028-02-01', $fev->primeiroDia()->format('Y-m-d'));
        self::assertSame('2028-02-29', $fev->ultimoDia()->format('Y-m-d'));
    }

    public function testDaData(): void
    {
        self::assertSame('2026-09', Competencia::daData(new DateTimeImmutable('2026-09-23'))->chave());
    }

    public function testRejeitaFormatoInvalido(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Competencia::fromString('2026-13');
    }
}

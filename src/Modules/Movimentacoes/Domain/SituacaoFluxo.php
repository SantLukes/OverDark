<?php

declare(strict_types=1);

namespace OverDark\Modules\Movimentacoes\Domain;

use OverDark\Shared\Domain\Money;

enum SituacaoFluxo: string
{
    case Positivo = 'positive';
    case Negativo = 'negative';

    /** Saldo zero ou acima é considerado positivo. */
    public static function doSaldo(Money $saldo): self
    {
        return $saldo->isNegative() ? self::Negativo : self::Positivo;
    }

    public function rotulo(): string
    {
        return match ($this) {
            self::Positivo => 'Positivo',
            self::Negativo => 'Negativo',
        };
    }

    public function descricao(): string
    {
        return match ($this) {
            self::Positivo => 'Fluxo saudável neste mês',
            self::Negativo => 'Gastos acima das receitas neste mês',
        };
    }

    public function icone(): string
    {
        return $this === self::Positivo ? '↑' : '↓';
    }
}

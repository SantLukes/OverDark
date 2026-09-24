<?php

declare(strict_types=1);

namespace OverDark\Shared\Formatting;

use DateTimeInterface;
use OverDark\Shared\Domain\Money;

/**
 * Formatação pt-BR para apresentação (moeda, percentual, data).
 */
final class Formatter
{
    /**
     * "R$ 3.200" quando não há centavos; "R$ 3.200,50" quando há.
     */
    public static function money(Money $money): string
    {
        $prefix = $money->isNegative() ? '- ' : '';

        return $prefix . 'R$ ' . self::amount($money->abs());
    }

    /**
     * Valor com sinal explícito: "+ R$ 860", "- R$ 120" ou "R$ 0".
     */
    public static function signedMoney(Money $money): string
    {
        if ($money->isZero()) {
            return 'R$ 0';
        }

        return ($money->isPositive() ? '+ ' : '- ') . 'R$ ' . self::amount($money->abs());
    }

    /**
     * "10,66%" ou, com sinal, "+10,66%" / "-3,20%".
     */
    public static function percent(float $value, bool $signed = false, int $decimals = 2): string
    {
        $sign = $signed && $value > 0 ? '+' : '';

        return $sign . number_format($value, $decimals, ',', '.') . '%';
    }

    public static function date(DateTimeInterface $date): string
    {
        return $date->format('d/m/Y');
    }

    private static function amount(Money $money): string
    {
        $decimals = $money->cents % 100 === 0 ? 0 : 2;

        return number_format($money->toFloat(), $decimals, ',', '.');
    }
}

<?php

declare(strict_types=1);

namespace OverDark\Shared\Formatting;

use InvalidArgumentException;
use OverDark\Shared\Domain\Money;

/**
 * Converte o que o usuário digita em um Money.
 *
 * Aceita: "1234,56", "1.234,56", "R$ 1.234,56", "1234.56", "1234", "0,5".
 */
final class MoneyParser
{
    public static function parse(string $input): Money
    {
        $valor = trim(str_replace(['R$', ' ', "\u{00A0}"], '', $input));

        if ($valor === '') {
            throw new InvalidArgumentException('Valor não informado.');
        }

        if (str_contains($valor, ',')) {
            // Formato brasileiro: ponto é milhar, vírgula é decimal.
            if (!preg_match('/^\d{1,3}(\.\d{3})*,\d{1,2}$|^\d+,\d{1,2}$/', $valor)) {
                throw new InvalidArgumentException(sprintf('Valor inválido: "%s".', $input));
            }

            $valor = str_replace(['.', ','], ['', '.'], $valor);
        } elseif (preg_match('/^\d{1,3}(\.\d{3})+$/', $valor)) {
            // "1.234" sem vírgula: ponto de milhar.
            $valor = str_replace('.', '', $valor);
        }

        if (!preg_match('/^\d+(\.\d{1,2})?$/', $valor)) {
            throw new InvalidArgumentException(sprintf('Valor inválido: "%s".', $input));
        }

        return Money::fromReais($valor);
    }
}

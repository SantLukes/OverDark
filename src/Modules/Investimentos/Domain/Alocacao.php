<?php

declare(strict_types=1);

namespace OverDark\Modules\Investimentos\Domain;

use OverDark\Shared\Domain\Money;

/**
 * Quanto da carteira está em um tipo de investimento.
 */
final class Alocacao
{
    public function __construct(
        public readonly TipoInvestimento $tipo,
        public readonly Money $valor,
    ) {
    }
}

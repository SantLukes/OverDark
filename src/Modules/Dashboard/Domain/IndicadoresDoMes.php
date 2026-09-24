<?php

declare(strict_types=1);

namespace OverDark\Modules\Dashboard\Domain;

use OverDark\Shared\Domain\Competencia;
use OverDark\Shared\Domain\Money;

/**
 * Números de topo do dashboard para o mês atual.
 */
final class IndicadoresDoMes
{
    public function __construct(
        public readonly Competencia $competencia,
        public readonly Money $receitas,
        /** Tudo o que saiu no mês (gastos + cartão). */
        public readonly Money $gastos,
        /** Parte dos gastos que é cartão de crédito (compras e parcelas do mês). */
        public readonly Money $cartao,
    ) {
    }

    /** Saldo do mês = receitas - gastos. */
    public function saldo(): Money
    {
        return $this->receitas->minus($this->gastos);
    }
}

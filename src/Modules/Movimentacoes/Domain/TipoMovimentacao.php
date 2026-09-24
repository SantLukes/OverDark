<?php

declare(strict_types=1);

namespace OverDark\Modules\Movimentacoes\Domain;

enum TipoMovimentacao: string
{
    case Receita = 'receita';
    case Gasto = 'gasto';
    case CartaoDeCredito = 'cartao';

    /** Rótulo curto (badges da listagem). */
    public function rotulo(): string
    {
        return match ($this) {
            self::Receita => 'Receita',
            self::Gasto => 'Gasto',
            self::CartaoDeCredito => 'Cartão',
        };
    }

    /** Rótulo completo (formulários). */
    public function rotuloCompleto(): string
    {
        return match ($this) {
            self::CartaoDeCredito => 'Cartão de crédito',
            default => $this->rotulo(),
        };
    }

    /** Receita entra no caixa; gasto e cartão saem. */
    public function isEntrada(): bool
    {
        return $this === self::Receita;
    }

    /** Somente compras no cartão de crédito podem ser parceladas. */
    public function permiteParcelamento(): bool
    {
        return $this === self::CartaoDeCredito;
    }
}

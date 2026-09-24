<?php

declare(strict_types=1);

namespace OverDark\Modules\Movimentacoes\Domain;

use InvalidArgumentException;

/**
 * Identifica uma movimentação como "parcela N de M" de uma compra parcelada.
 */
final class Parcela
{
    public function __construct(
        public readonly int $numero,
        public readonly int $total,
        /** Identificador comum a todas as parcelas da mesma compra. */
        public readonly string $grupo,
    ) {
        if ($numero < 1 || $numero > $total) {
            throw new InvalidArgumentException(sprintf('Parcela %d/%d inválida.', $numero, $total));
        }
    }

    /** "3/10" */
    public function rotulo(): string
    {
        return $this->numero . '/' . $this->total;
    }
}

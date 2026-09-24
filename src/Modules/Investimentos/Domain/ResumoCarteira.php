<?php

declare(strict_types=1);

namespace OverDark\Modules\Investimentos\Domain;

use OverDark\Shared\Domain\Money;

/**
 * Indicadores consolidados da carteira.
 */
final class ResumoCarteira
{
    /**
     * @param list<Alocacao> $composicao valor atual da carteira distribuído por tipo
     */
    public function __construct(
        public readonly Money $totalInvestido,
        public readonly array $composicao,
    ) {
    }

    /** Valor atual = soma das alocações por tipo. */
    public function valorAtual(): Money
    {
        return Money::sum(...array_map(static fn (Alocacao $a): Money => $a->valor, $this->composicao));
    }

    /** Lucro/prejuízo = valor atual - total investido. */
    public function lucro(): Money
    {
        return $this->valorAtual()->minus($this->totalInvestido);
    }

    /** Rentabilidade (%) = lucro / total investido. */
    public function rentabilidade(): float
    {
        return $this->lucro()->percentOf($this->totalInvestido);
    }

    /** Quantidade de classes de ativo com saldo. */
    public function quantidadeClasses(): int
    {
        return count(array_filter($this->composicao, static fn (Alocacao $a): bool => $a->valor->isPositive()));
    }
}

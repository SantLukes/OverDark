<?php

declare(strict_types=1);

namespace OverDark\Modules\Movimentacoes\Domain;

use OverDark\Shared\Domain\Competencia;
use OverDark\Shared\Domain\Money;

/**
 * Totais de uma competência (mês), calculados a partir dos lançamentos.
 */
final class ConsolidadoMensal
{
    public function __construct(
        public readonly Competencia $competencia,
        public readonly Money $receitas,
        /** Gastos avulsos (sem cartão). */
        public readonly Money $gastos,
        /** Parcelas/compras no cartão de crédito que caem neste mês. */
        public readonly Money $cartao,
        public readonly int $quantidadeLancamentos,
        /** Variação % da receita em relação ao mês anterior (nula se o anterior não teve receita). */
        public readonly ?float $variacaoReceita = null,
    ) {
    }

    public static function vazio(Competencia $competencia): self
    {
        return new self($competencia, Money::zero(), Money::zero(), Money::zero(), 0);
    }

    /**
     * Calcula a variação da receita em relação ao consolidado do mês anterior.
     */
    public function comparadoCom(self $anterior): self
    {
        $variacao = $anterior->receitas->isZero()
            ? null
            : $this->receitas->minus($anterior->receitas)->percentOf($anterior->receitas);

        return new self($this->competencia, $this->receitas, $this->gastos, $this->cartao, $this->quantidadeLancamentos, $variacao);
    }

    /** Tudo o que saiu: gastos + cartão. */
    public function gastosTotais(): Money
    {
        return $this->gastos->plus($this->cartao);
    }

    /** Saldo do mês = receitas - (gastos + cartão). */
    public function saldo(): Money
    {
        return $this->receitas->minus($this->gastosTotais());
    }

    public function situacao(): SituacaoFluxo
    {
        return SituacaoFluxo::doSaldo($this->saldo());
    }
}

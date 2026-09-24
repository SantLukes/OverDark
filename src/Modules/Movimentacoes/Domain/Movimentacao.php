<?php

declare(strict_types=1);

namespace OverDark\Modules\Movimentacoes\Domain;

use DateTimeImmutable;
use InvalidArgumentException;
use OverDark\Shared\Domain\Competencia;
use OverDark\Shared\Domain\Money;

/**
 * Um lançamento financeiro: receita, gasto ou compra no cartão de crédito
 * (neste caso, possivelmente uma parcela de uma compra parcelada).
 */
final class Movimentacao
{
    public const DESCRICAO_MAXIMA = 160;

    public function __construct(
        public readonly TipoMovimentacao $tipo,
        public readonly string $descricao,
        public readonly Money $valor,
        public readonly DateTimeImmutable $data,
        public readonly ?Parcela $parcela = null,
        /** Nulo enquanto não foi persistida. */
        public readonly ?int $id = null,
    ) {
        if (trim($descricao) === '') {
            throw new InvalidArgumentException('A descrição da movimentação é obrigatória.');
        }

        if (mb_strlen($descricao) > self::DESCRICAO_MAXIMA) {
            throw new InvalidArgumentException(sprintf('A descrição deve ter no máximo %d caracteres.', self::DESCRICAO_MAXIMA));
        }

        if (!$valor->isPositive()) {
            throw new InvalidArgumentException('O valor da movimentação deve ser maior que zero.');
        }

        if ($parcela !== null && !$tipo->permiteParcelamento()) {
            throw new InvalidArgumentException('Somente movimentações no cartão de crédito podem ser parceladas.');
        }
    }

    public function comId(int $id): self
    {
        return new self($this->tipo, $this->descricao, $this->valor, $this->data, $this->parcela, $id);
    }

    public function isEntrada(): bool
    {
        return $this->tipo->isEntrada();
    }

    public function competencia(): Competencia
    {
        return Competencia::daData($this->data);
    }

    /**
     * Valor com sinal para o fluxo de caixa: positivo para entradas, negativo para saídas.
     */
    public function valorNoFluxo(): Money
    {
        return $this->isEntrada() ? $this->valor : Money::zero()->minus($this->valor);
    }
}

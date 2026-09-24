<?php

declare(strict_types=1);

namespace OverDark\Modules\Investimentos\Domain;

use DateTimeImmutable;
use InvalidArgumentException;
use OverDark\Shared\Domain\Money;

/**
 * Uma posição da carteira.
 */
final class Investimento
{
    public readonly Money $valorAtual;

    /**
     * @param Money|null $valorAtual opcional no cadastro; quando ausente, assume o valor investido
     */
    public function __construct(
        public readonly string $nome,
        public readonly TipoInvestimento $tipo,
        public readonly Money $valorInvestido,
        ?Money $valorAtual = null,
        public readonly ?DateTimeImmutable $dataAporte = null,
    ) {
        if (trim($nome) === '') {
            throw new InvalidArgumentException('O nome do investimento é obrigatório.');
        }

        if (!$valorInvestido->isPositive()) {
            throw new InvalidArgumentException('O valor investido deve ser maior que zero.');
        }

        if ($valorAtual !== null && $valorAtual->isNegative()) {
            throw new InvalidArgumentException('O valor atual não pode ser negativo.');
        }

        $this->valorAtual = $valorAtual ?? $valorInvestido;
    }

    /** Lucro (positivo) ou prejuízo (negativo) = valor atual - valor investido. */
    public function resultado(): Money
    {
        return $this->valorAtual->minus($this->valorInvestido);
    }

    public function rentabilidade(): float
    {
        return $this->resultado()->percentOf($this->valorInvestido);
    }
}

<?php

declare(strict_types=1);

namespace OverDark\Shared\Domain;

/**
 * Valor monetário em Real (BRL), armazenado em centavos (inteiro).
 *
 * Nunca use float para dinheiro: 0.1 + 0.2 !== 0.3. Toda aritmética
 * financeira do sistema passa por este value object imutável.
 */
final class Money
{
    private function __construct(public readonly int $cents)
    {
    }

    public static function fromCents(int $cents): self
    {
        return new self($cents);
    }

    /**
     * Cria a partir de reais inteiros ou string decimal ("1234.56").
     */
    public static function fromReais(int|string $reais): self
    {
        if (is_int($reais)) {
            return new self($reais * 100);
        }

        if (!preg_match('/^(-)?(\d+)(?:\.(\d{1,2}))?$/', trim($reais), $m)) {
            throw new \InvalidArgumentException(sprintf('Valor monetário inválido: "%s".', $reais));
        }

        $cents = (int) $m[2] * 100 + (int) str_pad($m[3] ?? '0', 2, '0');

        return new self($m[1] === '-' ? -$cents : $cents);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public static function sum(self ...$values): self
    {
        return new self(array_sum(array_map(static fn (self $m): int => $m->cents, $values)));
    }

    public function plus(self $other): self
    {
        return new self($this->cents + $other->cents);
    }

    public function minus(self $other): self
    {
        return new self($this->cents - $other->cents);
    }

    public function abs(): self
    {
        return new self(abs($this->cents));
    }

    public function isPositive(): bool
    {
        return $this->cents > 0;
    }

    public function isNegative(): bool
    {
        return $this->cents < 0;
    }

    public function isZero(): bool
    {
        return $this->cents === 0;
    }

    /**
     * Quanto este valor representa de $base, em percentual (ex.: 10.66).
     */
    public function percentOf(self $base): float
    {
        if ($base->isZero()) {
            return 0.0;
        }

        return $this->cents / $base->cents * 100;
    }

    public function equals(self $other): bool
    {
        return $this->cents === $other->cents;
    }

    /**
     * Valor em reais como float — somente para apresentação (ex.: gráficos).
     */
    public function toFloat(): float
    {
        return $this->cents / 100;
    }
}

<?php

declare(strict_types=1);

namespace OverDark\Shared\Domain;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

/**
 * Mês/ano de referência (ex.: março de 2026). Imutável.
 */
final class Competencia
{
    private const MESES = [
        1 => 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
        'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro',
    ];

    public function __construct(
        public readonly int $ano,
        public readonly int $mes,
    ) {
        if ($mes < 1 || $mes > 12) {
            throw new InvalidArgumentException(sprintf('Mês inválido: %d.', $mes));
        }
    }

    /**
     * @param string $valor formato "AAAA-MM"
     */
    public static function fromString(string $valor): self
    {
        if (!preg_match('/^(\d{4})-(\d{2})$/', $valor, $m)) {
            throw new InvalidArgumentException(sprintf('Competência inválida: "%s". Use AAAA-MM.', $valor));
        }

        return new self((int) $m[1], (int) $m[2]);
    }

    public static function daData(DateTimeInterface $data): self
    {
        return new self((int) $data->format('Y'), (int) $data->format('n'));
    }

    /** Soma (ou subtrai, com n negativo) meses. */
    public function somarMeses(int $meses): self
    {
        $indice = $this->ano * 12 + ($this->mes - 1) + $meses;

        return new self(intdiv($indice, 12), $indice % 12 + 1);
    }

    public function anterior(): self
    {
        return $this->somarMeses(-1);
    }

    public function primeiroDia(): DateTimeImmutable
    {
        return new DateTimeImmutable(sprintf('%04d-%02d-01', $this->ano, $this->mes));
    }

    public function ultimoDia(): DateTimeImmutable
    {
        return $this->primeiroDia()->modify('last day of this month');
    }

    /** "2026-03" */
    public function chave(): string
    {
        return sprintf('%04d-%02d', $this->ano, $this->mes);
    }

    /** "Março 2026" */
    public function rotulo(): string
    {
        return $this->nomeMes() . ' ' . $this->ano;
    }

    /** "Março" */
    public function nomeMes(): string
    {
        return self::MESES[$this->mes];
    }

    /** "Mar" */
    public function rotuloCurto(): string
    {
        return mb_substr($this->nomeMes(), 0, 3);
    }

    public function equals(self $outra): bool
    {
        return $this->chave() === $outra->chave();
    }
}

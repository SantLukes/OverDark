<?php

declare(strict_types=1);

namespace OverDark\Shared\Domain;

/**
 * Um ponto de uma série histórica mensal (ex.: saldo de março, patrimônio em janeiro).
 */
final class PontoMensal
{
    public function __construct(
        public readonly string $rotulo,
        public readonly Money $valor,
    ) {
    }

    /**
     * Converte uma série em dados para gráfico: ['labels' => [...], 'values' => [...]].
     *
     * @param list<self> $serie
     * @return array{labels: list<string>, values: list<float>}
     */
    public static function paraGrafico(array $serie): array
    {
        return [
            'labels' => array_map(static fn (self $p): string => $p->rotulo, $serie),
            'values' => array_map(static fn (self $p): float => $p->valor->toFloat(), $serie),
        ];
    }
}

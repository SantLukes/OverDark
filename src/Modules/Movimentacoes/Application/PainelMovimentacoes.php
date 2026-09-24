<?php

declare(strict_types=1);

namespace OverDark\Modules\Movimentacoes\Application;

use DateTimeImmutable;
use OverDark\Modules\Movimentacoes\Domain\ConsolidadoMensal;
use OverDark\Modules\Movimentacoes\Domain\Movimentacao;
use OverDark\Modules\Movimentacoes\Domain\TipoMovimentacao;
use OverDark\Shared\Domain\Competencia;

/**
 * Tudo o que a tela de Movimentações precisa, já calculado.
 */
final class PainelMovimentacoes
{
    /**
     * @param list<Competencia> $competencias opções do filtro de mês
     * @param list<Movimentacao> $movimentacoes
     * @param list<TipoMovimentacao> $tipos
     * @param list<int> $opcoesParcelas
     */
    public function __construct(
        public readonly Competencia $competencia,
        public readonly array $competencias,
        public readonly ConsolidadoMensal $consolidado,
        public readonly array $movimentacoes,
        public readonly array $tipos,
        public readonly array $opcoesParcelas,
        public readonly DateTimeImmutable $dataPadrao,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace OverDark\Modules\Investimentos\Application;

use DateTimeImmutable;
use OverDark\Modules\Investimentos\Domain\Investimento;
use OverDark\Modules\Investimentos\Domain\ResumoCarteira;
use OverDark\Modules\Investimentos\Domain\TipoInvestimento;
use OverDark\Shared\Domain\PontoMensal;

/**
 * Tudo o que a tela de Investimentos precisa, já calculado.
 */
final class PainelInvestimentos
{
    /**
     * @param list<Investimento> $investimentos
     * @param list<PontoMensal> $evolucao
     * @param list<TipoInvestimento> $tipos
     */
    public function __construct(
        public readonly ResumoCarteira $resumo,
        public readonly array $investimentos,
        public readonly array $evolucao,
        public readonly array $tipos,
        public readonly DateTimeImmutable $dataPadrao,
    ) {
    }
}

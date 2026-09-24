<?php

declare(strict_types=1);

namespace OverDark\Modules\Dashboard\Application;

use OverDark\Modules\Dashboard\Domain\IndicadoresDoMes;
use OverDark\Shared\Domain\PontoMensal;

final class PainelDashboard
{
    /**
     * @param list<PontoMensal> $evolucaoSaldo saldo dos últimos meses (gráfico)
     * @param list<PontoMensal> $resumoMensal saldo de cada mês do ano corrente
     */
    public function __construct(
        public readonly IndicadoresDoMes $indicadores,
        public readonly array $evolucaoSaldo,
        public readonly array $resumoMensal,
        public readonly int $ano,
    ) {
    }
}

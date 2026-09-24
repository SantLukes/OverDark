<?php

declare(strict_types=1);

namespace OverDark\Modules\Dashboard\Application;

use OverDark\Modules\Dashboard\Domain\IndicadoresDoMes;
use OverDark\Modules\Movimentacoes\Application\MovimentacaoService;
use OverDark\Modules\Movimentacoes\Domain\ConsolidadoMensal;
use OverDark\Shared\Domain\Competencia;
use OverDark\Shared\Domain\PontoMensal;

/**
 * O dashboard não tem dados próprios: agrega os números do módulo
 * Movimentações através do serviço público dele.
 */
final class DashboardService
{
    /** Quantos meses (incluindo o atual) aparecem no gráfico de evolução. */
    public const MESES_NO_GRAFICO = 6;

    public function __construct(private readonly MovimentacaoService $movimentacoes)
    {
    }

    public function painel(int $usuarioId): PainelDashboard
    {
        $atual = $this->movimentacoes->competenciaAtual();
        $mes = $this->movimentacoes->consolidado($usuarioId, $atual);

        $evolucao = $this->movimentacoes->historico($usuarioId, $atual->somarMeses(-(self::MESES_NO_GRAFICO - 1)), $atual);
        $ano = $this->movimentacoes->historico($usuarioId, new Competencia($atual->ano, 1), new Competencia($atual->ano, 12));

        return new PainelDashboard(
            indicadores: new IndicadoresDoMes($atual, $mes->receitas, $mes->gastosTotais(), $mes->cartao),
            evolucaoSaldo: array_map(static fn (ConsolidadoMensal $c): PontoMensal => new PontoMensal($c->competencia->rotuloCurto(), $c->saldo()), $evolucao),
            resumoMensal: array_map(static fn (ConsolidadoMensal $c): PontoMensal => new PontoMensal($c->competencia->nomeMes(), $c->saldo()), $ano),
            ano: $atual->ano,
        );
    }
}

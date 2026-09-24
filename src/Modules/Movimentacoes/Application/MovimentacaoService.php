<?php

declare(strict_types=1);

namespace OverDark\Modules\Movimentacoes\Application;

use OverDark\Core\Clock\Clock;
use OverDark\Modules\Movimentacoes\Domain\ConsolidadoMensal;
use OverDark\Modules\Movimentacoes\Domain\MovimentacaoRepository;
use OverDark\Modules\Movimentacoes\Domain\Parcelamento;
use OverDark\Modules\Movimentacoes\Domain\TipoMovimentacao;
use OverDark\Shared\Domain\Competencia;

/**
 * Consultas de movimentações. Também é a porta de entrada de outros módulos
 * (ex.: Dashboard) para os números financeiros.
 */
final class MovimentacaoService
{
    public function __construct(
        private readonly MovimentacaoRepository $repository,
        private readonly Clock $clock,
    ) {
    }

    public function competenciaAtual(): Competencia
    {
        return Competencia::daData($this->clock->now());
    }

    /**
     * Painel da competência pedida — ou do mês atual, se nenhuma for informada.
     */
    public function painel(int $usuarioId, ?Competencia $competencia = null): PainelMovimentacoes
    {
        $competencia ??= $this->competenciaAtual();

        return new PainelMovimentacoes(
            competencia: $competencia,
            competencias: $this->opcoesDeCompetencia($usuarioId, $competencia),
            consolidado: $this->consolidado($usuarioId, $competencia),
            movimentacoes: $this->repository->listarPorCompetencia($usuarioId, $competencia),
            tipos: TipoMovimentacao::cases(),
            opcoesParcelas: Parcelamento::OPCOES,
            dataPadrao: $this->clock->now(),
        );
    }

    /**
     * Consolidado do mês, já com a variação de receita em relação ao mês anterior.
     */
    public function consolidado(int $usuarioId, Competencia $competencia): ConsolidadoMensal
    {
        $totais = $this->repository->totaisPorCompetencia($usuarioId, $competencia->anterior(), $competencia);

        $atual = $totais[$competencia->chave()] ?? ConsolidadoMensal::vazio($competencia);
        $anterior = $totais[$competencia->anterior()->chave()] ?? ConsolidadoMensal::vazio($competencia->anterior());

        return $atual->comparadoCom($anterior);
    }

    /**
     * Um consolidado por mês no intervalo, inclusive meses sem lançamentos (zerados).
     *
     * @return list<ConsolidadoMensal>
     */
    public function historico(int $usuarioId, Competencia $de, Competencia $ate): array
    {
        $totais = $this->repository->totaisPorCompetencia($usuarioId, $de, $ate);
        $historico = [];

        for ($mes = $de; $mes->chave() <= $ate->chave(); $mes = $mes->somarMeses(1)) {
            $historico[] = $totais[$mes->chave()] ?? ConsolidadoMensal::vazio($mes);
        }

        return $historico;
    }

    /**
     * Meses com lançamento + mês atual + mês selecionado, do mais recente ao mais antigo.
     *
     * @return list<Competencia>
     */
    private function opcoesDeCompetencia(int $usuarioId, Competencia $selecionada): array
    {
        $opcoes = [];

        foreach ([...$this->repository->competenciasComLancamentos($usuarioId), $this->competenciaAtual(), $selecionada] as $competencia) {
            $opcoes[$competencia->chave()] = $competencia;
        }

        krsort($opcoes);

        return array_values($opcoes);
    }
}

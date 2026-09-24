<?php

declare(strict_types=1);

namespace OverDark\Modules\Movimentacoes\Domain;

use OverDark\Shared\Domain\Competencia;

/**
 * Persistência das movimentações. Toda operação é restrita ao usuário dono.
 */
interface MovimentacaoRepository
{
    /**
     * Salva todas as movimentações de forma atômica (todas ou nenhuma).
     *
     * @param list<Movimentacao> $movimentacoes
     * @return list<Movimentacao> as mesmas movimentações, com id
     */
    public function salvar(int $usuarioId, array $movimentacoes): array;

    public function buscar(int $usuarioId, int $id): ?Movimentacao;

    /**
     * @return list<Movimentacao> todas as parcelas de uma compra
     */
    public function buscarGrupo(int $usuarioId, string $grupo): array;

    /**
     * @param list<int> $ids
     * @return int quantidade de linhas removidas
     */
    public function excluir(int $usuarioId, array $ids): int;

    /**
     * @return list<Movimentacao> ordenadas por data e id
     */
    public function listarPorCompetencia(int $usuarioId, Competencia $competencia): array;

    /**
     * Totais por mês no intervalo [de, ate]. Meses sem lançamento não aparecem.
     *
     * @return array<string, ConsolidadoMensal> indexado por Competencia::chave()
     */
    public function totaisPorCompetencia(int $usuarioId, Competencia $de, Competencia $ate): array;

    /**
     * @return list<Competencia> meses com ao menos um lançamento, do mais recente para o mais antigo
     */
    public function competenciasComLancamentos(int $usuarioId): array;
}

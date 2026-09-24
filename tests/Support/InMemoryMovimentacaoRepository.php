<?php

declare(strict_types=1);

namespace OverDark\Tests\Support;

use OverDark\Modules\Movimentacoes\Domain\ConsolidadoMensal;
use OverDark\Modules\Movimentacoes\Domain\Movimentacao;
use OverDark\Modules\Movimentacoes\Domain\MovimentacaoRepository;
use OverDark\Modules\Movimentacoes\Domain\TipoMovimentacao;
use OverDark\Shared\Domain\Competencia;
use OverDark\Shared\Domain\Money;

/**
 * Implementação em memória do repositório — dublê para testes unitários.
 */
class InMemoryMovimentacaoRepository implements MovimentacaoRepository
{
    /** @var array<int, array{usuario: int, movimentacao: Movimentacao}> */
    private array $linhas = [];

    private int $proximoId = 1;

    public function salvar(int $usuarioId, array $movimentacoes): array
    {
        $salvas = [];

        foreach ($movimentacoes as $movimentacao) {
            $salva = $movimentacao->comId($this->proximoId++);
            $this->linhas[(int) $salva->id] = ['usuario' => $usuarioId, 'movimentacao' => $salva];
            $salvas[] = $salva;
        }

        return $salvas;
    }

    public function buscar(int $usuarioId, int $id): ?Movimentacao
    {
        $linha = $this->linhas[$id] ?? null;

        return $linha !== null && $linha['usuario'] === $usuarioId ? $linha['movimentacao'] : null;
    }

    public function buscarGrupo(int $usuarioId, string $grupo): array
    {
        return array_values(array_filter(
            $this->doUsuario($usuarioId),
            static fn (Movimentacao $m): bool => $m->parcela?->grupo === $grupo,
        ));
    }

    public function excluir(int $usuarioId, array $ids): int
    {
        $removidas = 0;

        foreach ($ids as $id) {
            if ($this->buscar($usuarioId, $id) !== null) {
                unset($this->linhas[$id]);
                $removidas++;
            }
        }

        return $removidas;
    }

    public function listarPorCompetencia(int $usuarioId, Competencia $competencia): array
    {
        $lista = array_values(array_filter(
            $this->doUsuario($usuarioId),
            static fn (Movimentacao $m): bool => $m->competencia()->equals($competencia),
        ));
        usort($lista, static fn (Movimentacao $a, Movimentacao $b): int => [$a->data, $a->id] <=> [$b->data, $b->id]);

        return $lista;
    }

    public function totaisPorCompetencia(int $usuarioId, Competencia $de, Competencia $ate): array
    {
        /** @var array<string, array{Competencia, Money, Money, Money, int}> $acumulado */
        $acumulado = [];

        foreach ($this->doUsuario($usuarioId) as $m) {
            $chave = $m->competencia()->chave();

            if ($chave < $de->chave() || $chave > $ate->chave()) {
                continue;
            }

            $acumulado[$chave] ??= [$m->competencia(), Money::zero(), Money::zero(), Money::zero(), 0];
            $indice = match ($m->tipo) {
                TipoMovimentacao::Receita => 1,
                TipoMovimentacao::Gasto => 2,
                TipoMovimentacao::CartaoDeCredito => 3,
            };
            $acumulado[$chave][$indice] = $acumulado[$chave][$indice]->plus($m->valor);
            $acumulado[$chave][4]++;
        }

        return array_map(static fn (array $a): ConsolidadoMensal => new ConsolidadoMensal(...$a), $acumulado);
    }

    public function competenciasComLancamentos(int $usuarioId): array
    {
        $competencias = [];

        foreach ($this->doUsuario($usuarioId) as $m) {
            $competencias[$m->competencia()->chave()] = $m->competencia();
        }

        krsort($competencias);

        return array_values($competencias);
    }

    /**
     * @return list<Movimentacao>
     */
    private function doUsuario(int $usuarioId): array
    {
        $lista = [];

        foreach ($this->linhas as $linha) {
            if ($linha['usuario'] === $usuarioId) {
                $lista[] = $linha['movimentacao'];
            }
        }

        return $lista;
    }
}

<?php

declare(strict_types=1);

namespace OverDark\Modules\Movimentacoes\Infrastructure;

use DateTimeImmutable;
use OverDark\Core\Clock\Clock;
use OverDark\Modules\Movimentacoes\Domain\ConsolidadoMensal;
use OverDark\Modules\Movimentacoes\Domain\Movimentacao;
use OverDark\Modules\Movimentacoes\Domain\MovimentacaoRepository;
use OverDark\Modules\Movimentacoes\Domain\Parcela;
use OverDark\Modules\Movimentacoes\Domain\TipoMovimentacao;
use OverDark\Shared\Domain\Competencia;
use OverDark\Shared\Domain\Money;
use PDO;
use Throwable;

final class PdoMovimentacaoRepository implements MovimentacaoRepository
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly Clock $clock,
    ) {
    }

    public function salvar(int $usuarioId, array $movimentacoes): array
    {
        $criadoEm = $this->clock->now()->format('Y-m-d H:i:s');
        $salvas = [];

        // ─────────────────────────────────────────────────────────────────────────
        // DESCOMENTE a linha abaixo para simular. COMENTE de volta para "encerrar a rotina".
        // (O teste SimulacaoDeErroTest impede que isso vá descomentado para o git.)
        $rotinaConcorrente = Demo\FechamentoMensalEmAndamento::travarMovimentacoesDo($usuarioId);
        // ─────────────────────────────────────────────────────────────────────────

        $this->pdo->beginTransaction();

        try {
            foreach ($movimentacoes as $movimentacao) {
                $colunas = [
                    'usuario_id' => $usuarioId,
                    'tipo' => $movimentacao->tipo->value,
                    'descricao' => $movimentacao->descricao,
                    'valor_centavos' => $movimentacao->valor->cents,
                    'data' => $movimentacao->data->format('Y-m-d'),
                    'parcela_numero' => $movimentacao->parcela?->numero,
                    'parcelas_total' => $movimentacao->parcela?->total,
                    'grupo_parcelamento' => $movimentacao->parcela?->grupo,
                    'criado_em' => $criadoEm,
                ];

                $sql = sprintf(
                    'INSERT INTO movimentacoes (%s) VALUES (%s)',
                    implode(', ', array_keys($colunas)),
                    implode(', ', array_fill(0, count($colunas), '?')),
                );
                $this->pdo->prepare($sql)->execute(array_values($colunas));

                $salvas[] = $movimentacao->comId((int) $this->pdo->lastInsertId());
            }

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();

            throw $e;
        }

        return $salvas;
    }

    public function buscar(int $usuarioId, int $id): ?Movimentacao
    {
        $stmt = $this->pdo->prepare('SELECT * FROM movimentacoes WHERE usuario_id = ? AND id = ?');
        $stmt->execute([$usuarioId, $id]);
        $linha = $stmt->fetch();

        return is_array($linha) ? $this->hidratar($linha) : null;
    }

    public function buscarGrupo(int $usuarioId, string $grupo): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM movimentacoes WHERE usuario_id = ? AND grupo_parcelamento = ? ORDER BY parcela_numero'
        );
        $stmt->execute([$usuarioId, $grupo]);

        return $this->hidratarTodas($stmt->fetchAll());
    }

    public function excluir(int $usuarioId, array $ids): int
    {
        if ($ids === []) {
            return 0;
        }

        $marcadores = implode(', ', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare("DELETE FROM movimentacoes WHERE usuario_id = ? AND id IN ($marcadores)");
        $stmt->execute([$usuarioId, ...$ids]);

        return $stmt->rowCount();
    }

    public function listarPorCompetencia(int $usuarioId, Competencia $competencia): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM movimentacoes
             WHERE usuario_id = ? AND data BETWEEN ? AND ?
             ORDER BY data, id'
        );
        $stmt->execute([
            $usuarioId,
            $competencia->primeiroDia()->format('Y-m-d'),
            $competencia->ultimoDia()->format('Y-m-d'),
        ]);

        return $this->hidratarTodas($stmt->fetchAll());
    }

    public function totaisPorCompetencia(int $usuarioId, Competencia $de, Competencia $ate): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT DATE_FORMAT(data, '%Y-%m') AS competencia,
                    SUM(CASE WHEN tipo = 'receita' THEN valor_centavos ELSE 0 END) AS receitas,
                    SUM(CASE WHEN tipo = 'gasto'   THEN valor_centavos ELSE 0 END) AS gastos,
                    SUM(CASE WHEN tipo = 'cartao'  THEN valor_centavos ELSE 0 END) AS cartao,
                    COUNT(*) AS quantidade
             FROM movimentacoes
             WHERE usuario_id = ? AND data BETWEEN ? AND ?
             GROUP BY competencia"
        );
        $stmt->execute([$usuarioId, $de->primeiroDia()->format('Y-m-d'), $ate->ultimoDia()->format('Y-m-d')]);

        $totais = [];

        foreach ($stmt->fetchAll() as $linha) {
            $competencia = Competencia::fromString((string) $linha['competencia']);
            $totais[$competencia->chave()] = new ConsolidadoMensal(
                $competencia,
                Money::fromCents((int) $linha['receitas']),
                Money::fromCents((int) $linha['gastos']),
                Money::fromCents((int) $linha['cartao']),
                (int) $linha['quantidade'],
            );
        }

        return $totais;
    }

    public function competenciasComLancamentos(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT DISTINCT DATE_FORMAT(data, '%Y-%m') AS competencia
             FROM movimentacoes WHERE usuario_id = ?
             ORDER BY competencia DESC"
        );
        $stmt->execute([$usuarioId]);

        return array_values(array_map(
            static fn (mixed $valor): Competencia => Competencia::fromString((string) $valor),
            $stmt->fetchAll(PDO::FETCH_COLUMN),
        ));
    }

    /**
     * @param array<array-key, mixed> $linhas
     * @return list<Movimentacao>
     */
    private function hidratarTodas(array $linhas): array
    {
        return array_values(array_map(fn (mixed $linha): Movimentacao => $this->hidratar((array) $linha), $linhas));
    }

    /**
     * @param array<array-key, mixed> $linha
     */
    private function hidratar(array $linha): Movimentacao
    {
        $parcela = $linha['grupo_parcelamento'] !== null
            ? new Parcela((int) $linha['parcela_numero'], (int) $linha['parcelas_total'], (string) $linha['grupo_parcelamento'])
            : null;

        return new Movimentacao(
            TipoMovimentacao::from((string) $linha['tipo']),
            (string) $linha['descricao'],
            Money::fromCents((int) $linha['valor_centavos']),
            new DateTimeImmutable((string) $linha['data']),
            $parcela,
            (int) $linha['id'],
        );
    }
}

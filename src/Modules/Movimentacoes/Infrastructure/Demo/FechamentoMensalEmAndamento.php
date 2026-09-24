<?php

declare(strict_types=1);

namespace OverDark\Modules\Movimentacoes\Infrastructure\Demo;

use OverDark\Core\Database\ConnectionFactory;
use PDO;

/**
 * SIMULAÇÃO PARA A DEMO DE LOGS — não é usada no fluxo normal.
 *
 * Imita uma rotina em lote de "fechamento mensal" que, em outra conexão com o
 * banco, abre uma transação e trava as movimentações de um usuário enquanto
 * recalcula os totais. Enquanto este objeto existir, qualquer gravação desse
 * usuário espera o lock e, passado o DB_LOCK_WAIT_TIMEOUT, falha com
 * "Lock wait timeout exceeded" (MySQL 1205) — um erro real de concorrência,
 * com o código da aplicação correto.
 */
final class FechamentoMensalEmAndamento
{
    private function __construct(private readonly PDO $conexao)
    {
    }

    public static function travarMovimentacoesDo(int $usuarioId): self
    {
        $conexao = ConnectionFactory::fromEnv()->create();
        $conexao->beginTransaction();

        $stmt = $conexao->prepare('SELECT id FROM movimentacoes WHERE usuario_id = ? FOR UPDATE');
        $stmt->execute([$usuarioId]);

        return new self($conexao);
    }

    public function __destruct()
    {
        if ($this->conexao->inTransaction()) {
            $this->conexao->rollBack();
        }
    }
}

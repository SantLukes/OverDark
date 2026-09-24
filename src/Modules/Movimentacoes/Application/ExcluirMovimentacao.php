<?php

declare(strict_types=1);

namespace OverDark\Modules\Movimentacoes\Application;

use OverDark\Modules\Movimentacoes\Domain\Movimentacao;
use OverDark\Modules\Movimentacoes\Domain\MovimentacaoNaoEncontrada;
use OverDark\Modules\Movimentacoes\Domain\MovimentacaoRepository;
use Psr\Log\LoggerInterface;

/**
 * Caso de uso: excluir uma movimentação. Se ela for parcela de uma compra
 * parcelada, todas as parcelas da compra são excluídas juntas.
 */
final class ExcluirMovimentacao
{
    private const OPERACAO = 'excluir_movimentacao';

    public function __construct(
        private readonly MovimentacaoRepository $repository,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return list<Movimentacao> movimentações removidas
     *
     * @throws MovimentacaoNaoEncontrada inclusive quando pertence a outro usuário
     */
    public function executar(int $usuarioId, int $id): array
    {
        $movimentacao = $this->repository->buscar($usuarioId, $id);

        if ($movimentacao === null) {
            // Inexistente ou de outro usuário: não diferenciamos para quem pede,
            // mas o log deixa rastro de tentativas suspeitas.
            $this->logger->warning('movimentacao_exclusao_negada', [
                'operacao' => self::OPERACAO,
                'status' => 404,
                'motivo' => 'inexistente_ou_de_outro_usuario',
                'usuario_id' => $usuarioId,
                'movimentacao_id' => $id,
            ]);

            throw new MovimentacaoNaoEncontrada($id);
        }

        $removidas = $movimentacao->parcela !== null
            ? $this->repository->buscarGrupo($usuarioId, $movimentacao->parcela->grupo)
            : [$movimentacao];

        $ids = array_map(static fn (Movimentacao $m): int => (int) $m->id, $removidas);
        $this->repository->excluir($usuarioId, $ids);

        $this->logger->info('movimentacao_excluida', [
            'operacao' => self::OPERACAO,
            'status' => 'SUCCESS',
            'usuario_id' => $usuarioId,
            'movimentacao_id' => $id,
            'movimentacao_ids' => $ids,
            'quantidade' => count($ids),
            'grupo_parcelamento' => $movimentacao->parcela?->grupo,
            'tipo' => $movimentacao->tipo->value,
        ]);

        return $removidas;
    }
}

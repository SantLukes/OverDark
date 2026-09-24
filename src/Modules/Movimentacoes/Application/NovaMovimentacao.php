<?php

declare(strict_types=1);

namespace OverDark\Modules\Movimentacoes\Application;

/**
 * Dados brutos do formulário de nova movimentação (ainda não validados).
 */
final class NovaMovimentacao
{
    public function __construct(
        public readonly string $tipo,
        public readonly string $data,
        public readonly string $descricao,
        public readonly string $valor,
        public readonly string $parcelas = '1',
        public readonly string $valorParcela = '',
    ) {
    }

    /**
     * @return array<string, string> para repopular o formulário após erro
     */
    public function toArray(): array
    {
        return [
            'tipo' => $this->tipo,
            'data' => $this->data,
            'descricao' => $this->descricao,
            'valor' => $this->valor,
            'parcelas' => $this->parcelas,
            'valor_parcela' => $this->valorParcela,
        ];
    }
}

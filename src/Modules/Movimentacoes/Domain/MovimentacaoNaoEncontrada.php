<?php

declare(strict_types=1);

namespace OverDark\Modules\Movimentacoes\Domain;

use RuntimeException;

final class MovimentacaoNaoEncontrada extends RuntimeException
{
    public function __construct(public readonly int $id)
    {
        parent::__construct(sprintf('Movimentação %d não encontrada.', $id));
    }
}

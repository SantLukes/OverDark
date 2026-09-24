<?php

declare(strict_types=1);

namespace OverDark\Modules\Movimentacoes\Application;

use OverDark\Core\Logging\AlreadyLogged;
use RuntimeException;
use Throwable;

/**
 * A movimentação era válida, mas não pôde ser gravada (falha de infraestrutura).
 * Já foi registrada como `movimentacao_registro_falhou`.
 */
final class FalhaAoRegistrarMovimentacao extends RuntimeException implements AlreadyLogged
{
    public function __construct(Throwable $previous)
    {
        parent::__construct('Não foi possível registrar a movimentação.', 0, $previous);
    }
}

<?php

declare(strict_types=1);

namespace OverDark\Core\Database;

use PDOException;
use RuntimeException;

/**
 * Não foi possível conectar ao banco (servidor fora do ar, DNS, credenciais).
 * Diferente de uma consulta que falhou: aqui o sistema inteiro está impedido.
 */
final class DatabaseUnavailable extends RuntimeException
{
    public function __construct(
        public readonly string $host,
        public readonly string $database,
        PDOException $previous,
    ) {
        parent::__construct(sprintf('Banco "%s" em %s indisponível: %s', $database, $host, $previous->getMessage()), 0, $previous);
    }
}

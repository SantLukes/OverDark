<?php

declare(strict_types=1);

namespace OverDark\Core\Http;

use RuntimeException;

/**
 * Erro HTTP "esperado" (404, 405...), convertido em resposta pela Application.
 */
final class HttpException extends RuntimeException
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public readonly int $status,
        string $message = '',
        public readonly array $headers = [],
    ) {
        parent::__construct($message, $status);
    }

    public static function notFound(string $path): self
    {
        return new self(404, sprintf('Rota "%s" não encontrada.', $path));
    }

    /**
     * @param list<string> $allowed
     */
    public static function methodNotAllowed(string $method, string $path, array $allowed): self
    {
        return new self(
            405,
            sprintf('Método %s não permitido em "%s".', $method, $path),
            ['Allow' => implode(', ', $allowed)],
        );
    }
}

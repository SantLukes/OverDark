<?php

declare(strict_types=1);

namespace OverDark\Core\Validation;

use RuntimeException;

/**
 * Dados de entrada inválidos. Carrega uma mensagem por campo.
 */
final class ValidationException extends RuntimeException
{
    /**
     * @param array<string, string> $errors campo => mensagem
     */
    public function __construct(public readonly array $errors)
    {
        parent::__construct('Dados inválidos: ' . implode(' ', $errors));
    }

    public static function campo(string $campo, string $mensagem): self
    {
        return new self([$campo => $mensagem]);
    }
}

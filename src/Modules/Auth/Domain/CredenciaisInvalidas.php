<?php

declare(strict_types=1);

namespace OverDark\Modules\Auth\Domain;

use RuntimeException;

/**
 * E-mail inexistente ou senha incorreta. A mensagem ao usuário é
 * propositalmente a mesma nos dois casos (não revela quais e-mails existem).
 */
final class CredenciaisInvalidas extends RuntimeException
{
    public const MENSAGEM = 'E-mail ou senha inválidos.';

    public function __construct(public readonly string $email, public readonly string $motivo)
    {
        parent::__construct(self::MENSAGEM);
    }
}

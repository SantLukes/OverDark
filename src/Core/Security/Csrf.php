<?php

declare(strict_types=1);

namespace OverDark\Core\Security;

use OverDark\Core\Session\Session;

/**
 * Token anti-CSRF por sessão. Todo formulário POST envia o campo `_token`.
 */
final class Csrf
{
    public const FIELD = '_token';
    private const KEY = '_csrf_token';

    public function __construct(private readonly Session $session)
    {
    }

    public function token(): string
    {
        $token = $this->session->get(self::KEY);

        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            $this->session->set(self::KEY, $token);
        }

        return $token;
    }

    public function isValid(mixed $token): bool
    {
        return is_string($token) && hash_equals($this->token(), $token);
    }
}

<?php

declare(strict_types=1);

namespace OverDark\Shared\Auth;

use LogicException;
use OverDark\Core\Http\Request;

/**
 * Identidade do usuário logado, anexada ao Request pelo middleware de autenticação.
 *
 * Fica em Shared para que qualquer módulo saiba "quem está logado"
 * sem depender das classes internas do módulo Auth.
 */
final class UsuarioAutenticado
{
    public const ATRIBUTO = 'usuario';

    public function __construct(
        public readonly int $id,
        public readonly string $nome,
        public readonly string $email,
    ) {
    }

    public static function doRequest(Request $request): self
    {
        $usuario = $request->attribute(self::ATRIBUTO);

        if (!$usuario instanceof self) {
            throw new LogicException('Rota sem usuário autenticado. Ela está no grupo protegido em config/routes.php?');
        }

        return $usuario;
    }

    /** Letra exibida no avatar do header. */
    public function inicial(): string
    {
        return mb_strtoupper(mb_substr($this->nome, 0, 1));
    }
}

<?php

declare(strict_types=1);

namespace OverDark\Modules\Auth\Domain;

use DateTimeImmutable;
use OverDark\Shared\Auth\UsuarioAutenticado;

final class Usuario
{
    public function __construct(
        public readonly int $id,
        public readonly string $nome,
        public readonly string $email,
        public readonly string $senhaHash,
        public readonly DateTimeImmutable $criadoEm,
    ) {
    }

    public function senhaConfere(string $senha): bool
    {
        return password_verify($senha, $this->senhaHash);
    }

    public function identidade(): UsuarioAutenticado
    {
        return new UsuarioAutenticado($this->id, $this->nome, $this->email);
    }
}

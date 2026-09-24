<?php

declare(strict_types=1);

namespace OverDark\Tests\Support;

use DateTimeImmutable;
use OverDark\Modules\Auth\Domain\Usuario;
use OverDark\Modules\Auth\Domain\UsuarioRepository;

final class InMemoryUsuarioRepository implements UsuarioRepository
{
    /** @var array<int, Usuario> */
    private array $usuarios = [];

    public function buscarPorId(int $id): ?Usuario
    {
        return $this->usuarios[$id] ?? null;
    }

    public function buscarPorEmail(string $email): ?Usuario
    {
        foreach ($this->usuarios as $usuario) {
            if ($usuario->email === $email) {
                return $usuario;
            }
        }

        return null;
    }

    public function criar(string $nome, string $email, string $senhaHash, DateTimeImmutable $criadoEm): Usuario
    {
        $id = count($this->usuarios) + 1;

        return $this->usuarios[$id] = new Usuario($id, $nome, $email, $senhaHash, $criadoEm);
    }
}

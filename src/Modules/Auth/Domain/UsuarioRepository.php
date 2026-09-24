<?php

declare(strict_types=1);

namespace OverDark\Modules\Auth\Domain;

use DateTimeImmutable;

interface UsuarioRepository
{
    public function buscarPorId(int $id): ?Usuario;

    public function buscarPorEmail(string $email): ?Usuario;

    public function criar(string $nome, string $email, string $senhaHash, DateTimeImmutable $criadoEm): Usuario;
}

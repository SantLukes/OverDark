<?php

declare(strict_types=1);

namespace OverDark\Modules\Auth\Infrastructure;

use DateTimeImmutable;
use OverDark\Modules\Auth\Domain\Usuario;
use OverDark\Modules\Auth\Domain\UsuarioRepository;
use PDO;

final class PdoUsuarioRepository implements UsuarioRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function buscarPorId(int $id): ?Usuario
    {
        $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE id = ?');
        $stmt->execute([$id]);

        return $this->hidratar($stmt->fetch());
    }

    public function buscarPorEmail(string $email): ?Usuario
    {
        $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE email = ?');
        $stmt->execute([$email]);

        return $this->hidratar($stmt->fetch());
    }

    public function criar(string $nome, string $email, string $senhaHash, DateTimeImmutable $criadoEm): Usuario
    {
        $this->pdo
            ->prepare('INSERT INTO usuarios (nome, email, senha_hash, criado_em) VALUES (?, ?, ?, ?)')
            ->execute([$nome, $email, $senhaHash, $criadoEm->format('Y-m-d H:i:s')]);

        return new Usuario((int) $this->pdo->lastInsertId(), $nome, $email, $senhaHash, $criadoEm);
    }

    private function hidratar(mixed $linha): ?Usuario
    {
        if (!is_array($linha)) {
            return null;
        }

        return new Usuario(
            (int) $linha['id'],
            (string) $linha['nome'],
            (string) $linha['email'],
            (string) $linha['senha_hash'],
            new DateTimeImmutable((string) $linha['criado_em']),
        );
    }
}

<?php

declare(strict_types=1);

namespace OverDark\Modules\Auth\Application;

use OverDark\Core\Clock\Clock;
use OverDark\Core\Logging\Sanitizer;
use OverDark\Core\Validation\ValidationException;
use OverDark\Modules\Auth\Domain\Usuario;
use OverDark\Modules\Auth\Domain\UsuarioRepository;
use Psr\Log\LoggerInterface;

/**
 * Caso de uso: cadastrar um usuário (hoje exposto apenas via `bin/console usuario:criar`).
 */
final class CriarUsuario
{
    public const SENHA_MINIMA = 8;

    public function __construct(
        private readonly UsuarioRepository $usuarios,
        private readonly Clock $clock,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function executar(string $nome, string $email, string $senha): Usuario
    {
        $nome = trim($nome);
        $email = mb_strtolower(trim($email));
        $erros = [];

        if ($nome === '') {
            $erros['nome'] = 'Informe o nome.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erros['email'] = 'Informe um e-mail válido.';
        } elseif ($this->usuarios->buscarPorEmail($email) !== null) {
            $erros['email'] = 'Já existe um usuário com este e-mail.';
        }

        if (mb_strlen($senha) < self::SENHA_MINIMA) {
            $erros['senha'] = sprintf('A senha deve ter ao menos %d caracteres.', self::SENHA_MINIMA);
        }

        if ($erros !== []) {
            $this->logger->warning('usuario_cadastro_recusado', [
                'operacao' => 'criar_usuario',
                'status' => 422,
                'motivo' => 'dados_invalidos',
                'campos' => array_keys($erros),
                'email' => Sanitizer::mascararEmail($email),
            ]);

            throw new ValidationException($erros);
        }

        $usuario = $this->usuarios->criar($nome, $email, password_hash($senha, PASSWORD_DEFAULT), $this->clock->now());

        $this->logger->info('usuario_criado', [
            'operacao' => 'criar_usuario',
            'status' => 'SUCCESS',
            'usuario_id' => $usuario->id,
            'email' => Sanitizer::mascararEmail($email),
        ]);

        return $usuario;
    }
}

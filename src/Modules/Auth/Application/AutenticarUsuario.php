<?php

declare(strict_types=1);

namespace OverDark\Modules\Auth\Application;

use OverDark\Core\Logging\Sanitizer;
use OverDark\Core\Validation\ValidationException;
use OverDark\Modules\Auth\Domain\CredenciaisInvalidas;
use OverDark\Modules\Auth\Domain\Usuario;
use OverDark\Modules\Auth\Domain\UsuarioRepository;
use Psr\Log\LoggerInterface;

/**
 * Caso de uso: verificar e-mail + senha.
 */
final class AutenticarUsuario
{
    /**
     * Hash de uma senha qualquer, usado quando o e-mail não existe: assim o tempo
     * de resposta é o mesmo e não dá para descobrir e-mails cadastrados pelo relógio.
     */
    private const HASH_FICTICIO = '$2y$10$eq/mQHoJaH043UK9.C.D0eWFFNBpZCbdtyge5lA7YxCivV1/iswC2';

    private const OPERACAO = 'autenticar_usuario';

    public function __construct(
        private readonly UsuarioRepository $usuarios,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @throws ValidationException quando e-mail/senha não foram preenchidos corretamente
     * @throws CredenciaisInvalidas quando não confere
     */
    public function executar(string $email, string $senha): Usuario
    {
        $email = mb_strtolower(trim($email));
        $erros = [];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erros['email'] = 'Informe um e-mail válido.';
        }

        if ($senha === '') {
            $erros['senha'] = 'Informe a senha.';
        }

        if ($erros !== []) {
            $this->logger->warning('login_recusado', [
                'operacao' => self::OPERACAO,
                'status' => 422,
                'motivo' => 'dados_invalidos',
                'campos' => array_keys($erros),
                'email' => Sanitizer::mascararEmail($email),
            ]);

            throw new ValidationException($erros);
        }

        $usuario = $this->usuarios->buscarPorEmail($email);

        if ($usuario === null) {
            password_verify($senha, self::HASH_FICTICIO);

            throw $this->recusar($email, 'usuario_inexistente');
        }

        if (!$usuario->senhaConfere($senha)) {
            throw $this->recusar($email, 'senha_incorreta', $usuario->id);
        }

        $this->logger->info('usuario_autenticado', [
            'operacao' => self::OPERACAO,
            'status' => 'SUCCESS',
            'usuario_id' => $usuario->id,
            'email' => Sanitizer::mascararEmail($email),
        ]);

        return $usuario;
    }

    /**
     * O usuário vê sempre a mesma mensagem; o log guarda o motivo real.
     */
    private function recusar(string $email, string $motivo, ?int $usuarioId = null): CredenciaisInvalidas
    {
        $this->logger->warning('login_recusado', [
            'operacao' => self::OPERACAO,
            'status' => 401,
            'motivo' => $motivo,
            'usuario_id' => $usuarioId,
            'email' => Sanitizer::mascararEmail($email),
        ]);

        return new CredenciaisInvalidas($email, $motivo);
    }
}

<?php

declare(strict_types=1);

namespace OverDark\Modules\Auth\Application;

use OverDark\Core\Session\Session;
use OverDark\Modules\Auth\Domain\Usuario;
use OverDark\Modules\Auth\Domain\UsuarioRepository;
use Psr\Log\LoggerInterface;

/**
 * Quem está logado nesta sessão.
 */
final class SessaoUsuario
{
    private const CHAVE = 'usuario_id';

    public function __construct(
        private readonly Session $session,
        private readonly UsuarioRepository $usuarios,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function entrar(Usuario $usuario): void
    {
        // Novo ID de sessão a cada login: impede session fixation.
        $this->session->regenerate();
        $this->session->set(self::CHAVE, $usuario->id);
    }

    public function sair(): void
    {
        $usuarioId = $this->session->get(self::CHAVE);
        $this->session->invalidate();

        $this->logger->info('logout_realizado', [
            'operacao' => 'encerrar_sessao',
            'status' => 'SUCCESS',
            'usuario_id' => is_int($usuarioId) ? $usuarioId : null,
        ]);
    }

    public function usuario(): ?Usuario
    {
        $id = $this->session->get(self::CHAVE);

        if (!is_int($id)) {
            return null;
        }

        $usuario = $this->usuarios->buscarPorId($id);

        // Usuário removido do banco com sessão ainda aberta.
        if ($usuario === null) {
            $this->session->remove(self::CHAVE);
        }

        return $usuario;
    }

    public function estaLogado(): bool
    {
        return is_int($this->session->get(self::CHAVE));
    }
}

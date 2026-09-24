<?php

declare(strict_types=1);

namespace OverDark\Modules\Auth\Http;

use OverDark\Core\Http\Request;
use OverDark\Core\Http\Response;
use OverDark\Core\Logging\LogContext;
use OverDark\Core\Session\Session;
use OverDark\Core\Validation\ValidationException;
use OverDark\Core\View\View;
use OverDark\Modules\Auth\Application\AutenticarUsuario;
use OverDark\Modules\Auth\Application\SessaoUsuario;
use OverDark\Modules\Auth\Domain\CredenciaisInvalidas;

final class AuthController
{
    public function __construct(
        private readonly AutenticarUsuario $autenticar,
        private readonly SessaoUsuario $sessao,
        private readonly Session $session,
        private readonly View $view,
        private readonly LogContext $logContext,
    ) {
    }

    /** GET / e GET /login */
    public function showLogin(Request $request): Response
    {
        return Response::html($this->view->render('Auth::login', [
            'erros' => $this->session->pullFlash('erros', []),
            'emailAnterior' => $this->session->pullFlash('email', ''),
        ]));
    }

    /** POST /login */
    public function login(Request $request): Response
    {
        $email = $request->string('email');

        try {
            $usuario = $this->autenticar->executar($email, (string) $request->input('senha', ''));
        } catch (ValidationException $e) {
            return $this->voltarAoLogin($email, $e->errors);
        } catch (CredenciaisInvalidas $e) {
            return $this->voltarAoLogin($email, ['geral' => $e->getMessage()]);
        }

        $this->sessao->entrar($usuario);
        $this->logContext->definirUsuario($usuario->id);

        return Response::redirect('/dashboard');
    }

    /** POST /logout */
    public function logout(Request $request): Response
    {
        $this->sessao->sair();

        return Response::redirect('/login');
    }

    /**
     * @param array<string, string> $erros
     */
    private function voltarAoLogin(string $email, array $erros): Response
    {
        $this->session->flash('erros', $erros);
        $this->session->flash('email', $email);

        return Response::redirect('/login');
    }
}

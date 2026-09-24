<?php

declare(strict_types=1);

namespace OverDark\Modules\Auth\Http\Middleware;

use Closure;
use OverDark\Core\Http\Middleware;
use OverDark\Core\Http\Request;
use OverDark\Core\Http\Response;
use OverDark\Core\Logging\LogContext;
use OverDark\Core\View\View;
use OverDark\Modules\Auth\Application\SessaoUsuario;
use OverDark\Shared\Auth\UsuarioAutenticado;

/**
 * Protege rotas internas: sem sessão válida, redireciona para o login.
 * Com sessão, anexa o UsuarioAutenticado ao Request, às views e ao contexto de log.
 */
final class Autenticado implements Middleware
{
    public function __construct(
        private readonly SessaoUsuario $sessao,
        private readonly View $view,
        private readonly LogContext $logContext,
    ) {
    }

    public function process(Request $request, Closure $next): Response
    {
        $usuario = $this->sessao->usuario();

        if ($usuario === null) {
            return Response::redirect('/login');
        }

        $identidade = $usuario->identidade();
        // A partir daqui, todo log desta requisição sai com usuario_id.
        $this->logContext->definirUsuario($identidade->id);
        $this->view->share('usuarioLogado', $identidade);

        return $next($request->withAttribute(UsuarioAutenticado::ATRIBUTO, $identidade));
    }
}

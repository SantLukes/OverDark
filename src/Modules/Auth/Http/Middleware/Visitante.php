<?php

declare(strict_types=1);

namespace OverDark\Modules\Auth\Http\Middleware;

use Closure;
use OverDark\Core\Http\Middleware;
use OverDark\Core\Http\Request;
use OverDark\Core\Http\Response;
use OverDark\Modules\Auth\Application\SessaoUsuario;

/**
 * Páginas só para quem NÃO está logado (login): quem já está vai para o dashboard.
 */
final class Visitante implements Middleware
{
    public function __construct(private readonly SessaoUsuario $sessao)
    {
    }

    public function process(Request $request, Closure $next): Response
    {
        if ($this->sessao->estaLogado()) {
            return Response::redirect('/dashboard');
        }

        return $next($request);
    }
}

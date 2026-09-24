<?php

declare(strict_types=1);

namespace OverDark\Core\Security;

use Closure;
use OverDark\Core\Http\HttpException;
use OverDark\Core\Http\Middleware;
use OverDark\Core\Http\Request;
use OverDark\Core\Http\Response;
use OverDark\Core\View\View;

/**
 * Middleware global: rejeita (419) requisições que alteram estado sem token
 * CSRF válido e disponibiliza o token para as views.
 */
final class VerifyCsrfToken implements Middleware
{
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    public function __construct(
        private readonly Csrf $csrf,
        private readonly View $view,
    ) {
    }

    public function process(Request $request, Closure $next): Response
    {
        if (!in_array($request->method, self::SAFE_METHODS, true) && !$this->csrf->isValid($request->input(Csrf::FIELD))) {
            throw new HttpException(419, 'Token CSRF ausente ou inválido.');
        }

        $this->view->share('csrfToken', $this->csrf->token());

        return $next($request);
    }
}

<?php

declare(strict_types=1);

namespace OverDark\Core\Http;

use Closure;

/**
 * Camada que envolve o controller: pode barrar, alterar ou enriquecer
 * a requisição antes, e a resposta depois.
 */
interface Middleware
{
    /**
     * @param Closure(Request): Response $next
     */
    public function process(Request $request, Closure $next): Response;
}

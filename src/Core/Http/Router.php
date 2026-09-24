<?php

declare(strict_types=1);

namespace OverDark\Core\Http;

use Closure;

/**
 * Roteador de rotas estáticas (método + caminho exato), com grupos de middleware.
 *
 * Um handler é sempre um par [ClasseDoController::class, 'metodo'],
 * resolvido pelo container na Application.
 */
final class Router
{
    /** @var array<string, array<string, Route>> caminho => método => rota */
    private array $routes = [];

    /** @var list<class-string<Middleware>> middleware do grupo em construção */
    private array $groupMiddleware = [];

    /**
     * @param array{class-string, string} $handler
     */
    public function get(string $path, array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    /**
     * @param array{class-string, string} $handler
     */
    public function post(string $path, array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    /**
     * @param array{class-string, string} $handler
     */
    public function add(string $method, string $path, array $handler): void
    {
        $path = Request::normalizePath($path);
        $method = strtoupper($method);

        $this->routes[$path][$method] = new Route($method, $path, $handler, $this->groupMiddleware);
    }

    /**
     * Todas as rotas registradas dentro do callback recebem os middlewares informados.
     *
     * @param list<class-string<Middleware>> $middleware
     * @param Closure(self): void $routes
     */
    public function group(array $middleware, Closure $routes): void
    {
        $previous = $this->groupMiddleware;
        $this->groupMiddleware = [...$previous, ...$middleware];

        try {
            $routes($this);
        } finally {
            $this->groupMiddleware = $previous;
        }
    }

    /**
     * @throws HttpException 404 quando o caminho não existe, 405 quando o método não é aceito.
     */
    public function match(string $method, string $path): Route
    {
        $path = Request::normalizePath($path);
        $method = strtoupper($method);

        if (!isset($this->routes[$path])) {
            throw HttpException::notFound($path);
        }

        // HEAD é tratado como GET (sem corpo, cuidado do servidor web).
        $lookup = $method === 'HEAD' ? 'GET' : $method;

        return $this->routes[$path][$lookup]
            ?? throw HttpException::methodNotAllowed($method, $path, array_keys($this->routes[$path]));
    }
}

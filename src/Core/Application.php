<?php

declare(strict_types=1);

namespace OverDark\Core;

use Closure;
use OverDark\Core\Container\Container;
use OverDark\Core\Database\DatabaseUnavailable;
use OverDark\Core\Database\SqlErrorReason;
use OverDark\Core\Http\HttpException;
use OverDark\Core\Http\Middleware;
use OverDark\Core\Http\Request;
use OverDark\Core\Http\Response;
use OverDark\Core\Http\Route;
use OverDark\Core\Http\Router;
use OverDark\Core\Logging\AlreadyLogged;
use OverDark\Core\Logging\LogContext;
use OverDark\Core\View\View;
use PDOException;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Kernel HTTP: recebe um Request, passa pelos middlewares, despacha para o
 * controller e devolve um Response.
 *
 * É o ponto único por onde toda requisição passa, por isso é aqui que:
 *  - nasce o correlation_id (devolvido no header X-Correlation-ID);
 *  - toda requisição é registrada (http_requisicao_concluida);
 *  - todo erro não tratado vira log estruturado (e alerta no Slack),
 *    exceto os marcados como AlreadyLogged (já registrados por quem tinha mais contexto).
 */
final class Application
{
    public const HEADER_CORRELATION = 'X-Correlation-ID';

    /**
     * @param list<class-string<Middleware>> $globalMiddleware executados em toda rota, antes dos da rota
     */
    public function __construct(
        private readonly Container $container,
        private readonly Router $router,
        private readonly View $view,
        private readonly bool $debug = false,
        private readonly array $globalMiddleware = [],
    ) {
    }

    public function handle(Request $request): Response
    {
        $inicio = hrtime(true);
        $correlationId = $this->logContext()->iniciar($request->header(self::HEADER_CORRELATION));
        $this->view->share('correlationId', $correlationId);
        $handler = null;

        try {
            $route = $this->router->match($request->method, $request->path);
            $handler = $route->handler;
            $response = $this->pipeline($route)($request);
        } catch (AlreadyLogged $e) {
            // Quem lançou já registrou o erro com mais contexto: só renderiza.
            $response = $this->renderError(500, $e, $request);
        } catch (HttpException $e) {
            $this->logHttpException($e, $request);
            $response = $this->renderError($e->status, $e, $request)->withHeaders($e->headers);
        } catch (DatabaseUnavailable $e) {
            $this->logger()->critical('banco_indisponivel', [
                'operacao' => 'conectar_banco',
                'status' => 503,
                'motivo' => 'conexao_recusada',
                'integracao' => 'MySQL',
                'host' => $e->host,
                'database' => $e->database,
                'rota' => $request->method . ' ' . $request->path,
                'exception' => $e,
            ]);
            $response = $this->renderError(503, $e, $request);
        } catch (PDOException $e) {
            $this->logger()->error('consulta_banco_falhou', [
                'operacao' => 'executar_consulta',
                'status' => 500,
                'motivo' => SqlErrorReason::de($e),
                'integracao' => 'MySQL',
                'sqlstate' => SqlErrorReason::sqlstate($e),
                'rota' => $request->method . ' ' . $request->path,
                'exception' => $e,
            ]);
            $response = $this->renderError(500, $e, $request);
        } catch (Throwable $e) {
            $this->logger()->error('erro_inesperado', [
                'operacao' => 'processar_requisicao',
                'status' => 500,
                'motivo' => (new \ReflectionClass($e))->getShortName(),
                'rota' => $request->method . ' ' . $request->path,
                'exception' => $e,
            ]);
            $response = $this->renderError(500, $e, $request);
        }

        $this->logRequisicao($request, $response, $handler, $inicio);

        return $response->withHeaders([self::HEADER_CORRELATION => $correlationId]);
    }

    public function container(): Container
    {
        return $this->container;
    }

    /** Resolvido a cada uso para que testes possam trocar o logger no container. */
    private function logger(): LoggerInterface
    {
        /** @var LoggerInterface */
        return $this->container->get(LoggerInterface::class);
    }

    private function logContext(): LogContext
    {
        /** @var LogContext */
        return $this->container->get(LogContext::class);
    }

    /**
     * Monta a cadeia middleware1 → middleware2 → ... → controller.
     *
     * @return Closure(Request): Response
     */
    private function pipeline(Route $route): Closure
    {
        [$controllerClass, $action] = $route->handler;

        $next = function (Request $request) use ($controllerClass, $action): Response {
            /** @var Response */
            return $this->container->get($controllerClass)->{$action}($request);
        };

        $middlewares = array_reverse([...$this->globalMiddleware, ...$route->middleware]);

        foreach ($middlewares as $middlewareClass) {
            $next = function (Request $request) use ($middlewareClass, $next): Response {
                /** @var Middleware $middleware */
                $middleware = $this->container->get($middlewareClass);

                return $middleware->process($request, $next);
            };
        }

        return $next;
    }

    /**
     * Uma linha por requisição (o "access log" estruturado).
     *
     * @param array{class-string, string}|null $handler
     */
    private function logRequisicao(Request $request, Response $response, ?array $handler, int $inicio): void
    {
        $nivel = match (true) {
            $response->status >= 500 => 'warning', // o erro em si já foi logado como error/critical
            $response->status >= 400 => 'notice',
            default => 'info',
        };

        $this->logger()->log($nivel, 'http_requisicao_concluida', [
            'operacao' => 'processar_requisicao',
            'status' => $response->status,
            'metodo' => $request->method,
            'rota' => $request->path,
            'handler' => $handler !== null ? (new \ReflectionClass($handler[0]))->getShortName() . '::' . $handler[1] : null,
            'duracao_ms' => round((hrtime(true) - $inicio) / 1e6, 1),
            'ip' => $request->ip !== '' ? $request->ip : null,
        ]);
    }

    private function logHttpException(HttpException $e, Request $request): void
    {
        [$nivel, $evento, $motivo] = match ($e->status) {
            404 => ['notice', 'recurso_nao_encontrado', 'rota_ou_recurso_inexistente'],
            405 => ['notice', 'metodo_nao_permitido', 'metodo_' . strtolower($request->method)],
            419 => ['warning', 'csrf_token_invalido', 'token_ausente_ou_expirado'],
            default => ['warning', 'requisicao_recusada', 'http_' . $e->status],
        };

        $this->logger()->log($nivel, $evento, [
            'operacao' => 'processar_requisicao',
            'status' => $e->status,
            'motivo' => $motivo,
            'rota' => $request->method . ' ' . $request->path,
            'detalhe' => $e->getMessage(),
        ]);
    }

    private function renderError(int $status, Throwable $e, Request $request): Response
    {
        try {
            $body = $this->view->render('errors/error', [
                'status' => $status,
                'rota' => $request->method . ' ' . $request->path,
                'horario' => new \DateTimeImmutable(),
                'exception' => $this->debug ? $e : null,
            ]);
        } catch (Throwable) {
            $body = sprintf('%d - Erro ao processar a requisição. Código: %s', $status, $this->logContext()->correlationId());
        }

        return Response::html($body, $status);
    }
}

<?php

declare(strict_types=1);

use OverDark\Core\Http\Router;
use OverDark\Modules\Auth\Http\AuthController;
use OverDark\Modules\Auth\Http\Middleware\Autenticado;
use OverDark\Modules\Auth\Http\Middleware\Visitante;
use OverDark\Modules\Dashboard\Http\DashboardController;
use OverDark\Modules\Investimentos\Http\InvestimentoController;
use OverDark\Modules\Movimentacoes\Http\MovimentacaoController;

/*
 * Toda rota passa pelo middleware global de CSRF (ver bootstrap/app.php).
 * Rotas internas ficam no grupo Autenticado.
 */
return static function (Router $router): void {
    // Públicas (quem já está logado é mandado para o dashboard)
    $router->group([Visitante::class], static function (Router $router): void {
        $router->get('/', [AuthController::class, 'showLogin']);
        $router->get('/login', [AuthController::class, 'showLogin']);
        $router->post('/login', [AuthController::class, 'login']);
    });

    // Protegidas
    $router->group([Autenticado::class], static function (Router $router): void {
        $router->post('/logout', [AuthController::class, 'logout']);

        $router->get('/dashboard', [DashboardController::class, 'index']);

        $router->get('/movimentacoes', [MovimentacaoController::class, 'index']);
        $router->post('/movimentacoes', [MovimentacaoController::class, 'store']);
        $router->post('/movimentacoes/excluir', [MovimentacaoController::class, 'destroy']);

        $router->get('/investimentos', [InvestimentoController::class, 'index']);
    });
};

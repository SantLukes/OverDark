<?php

declare(strict_types=1);

namespace OverDark\Modules\Dashboard\Http;

use OverDark\Core\Http\Request;
use OverDark\Core\Http\Response;
use OverDark\Core\View\View;
use OverDark\Modules\Dashboard\Application\DashboardService;
use OverDark\Shared\Auth\UsuarioAutenticado;

final class DashboardController
{
    public function __construct(
        private readonly DashboardService $service,
        private readonly View $view,
    ) {
    }

    /** GET /dashboard */
    public function index(Request $request): Response
    {
        $usuario = UsuarioAutenticado::doRequest($request);

        return Response::html($this->view->render('Dashboard::index', [
            'painel' => $this->service->painel($usuario->id),
        ]));
    }
}

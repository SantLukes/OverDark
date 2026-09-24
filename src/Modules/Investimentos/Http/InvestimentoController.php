<?php

declare(strict_types=1);

namespace OverDark\Modules\Investimentos\Http;

use OverDark\Core\Http\Request;
use OverDark\Core\Http\Response;
use OverDark\Core\View\View;
use OverDark\Modules\Investimentos\Application\InvestimentoService;

final class InvestimentoController
{
    public function __construct(
        private readonly InvestimentoService $service,
        private readonly View $view,
    ) {
    }

    /** GET /investimentos */
    public function index(Request $request): Response
    {
        return Response::html($this->view->render('Investimentos::index', [
            'painel' => $this->service->painel(),
        ]));
    }
}

<?php

declare(strict_types=1);

namespace OverDark\Modules\Movimentacoes\Http;

use InvalidArgumentException;
use OverDark\Core\Http\HttpException;
use OverDark\Core\Http\Request;
use OverDark\Core\Http\Response;
use OverDark\Core\Session\Session;
use OverDark\Core\Validation\ValidationException;
use OverDark\Core\View\View;
use OverDark\Modules\Movimentacoes\Application\ExcluirMovimentacao;
use OverDark\Modules\Movimentacoes\Application\MovimentacaoService;
use OverDark\Modules\Movimentacoes\Application\NovaMovimentacao;
use OverDark\Modules\Movimentacoes\Application\RegistrarMovimentacao;
use OverDark\Modules\Movimentacoes\Domain\MovimentacaoNaoEncontrada;
use OverDark\Shared\Auth\UsuarioAutenticado;
use OverDark\Shared\Domain\Competencia;
use OverDark\Shared\Formatting\Formatter;

/**
 * Escritas seguem Post/Redirect/Get: o POST processa, guarda o resultado em
 * flash e redireciona para a listagem (F5 não reenvia o formulário).
 */
final class MovimentacaoController
{
    public function __construct(
        private readonly MovimentacaoService $service,
        private readonly RegistrarMovimentacao $registrar,
        private readonly ExcluirMovimentacao $excluir,
        private readonly Session $session,
        private readonly View $view,
    ) {
    }

    /** GET /movimentacoes[?competencia=AAAA-MM] */
    public function index(Request $request): Response
    {
        $usuario = UsuarioAutenticado::doRequest($request);

        return Response::html($this->view->render('Movimentacoes::index', [
            'painel' => $this->service->painel($usuario->id, $this->competenciaDaQuery($request)),
            'sucesso' => $this->session->pullFlash('sucesso'),
            'erros' => $this->session->pullFlash('erros', []),
            'antigo' => $this->session->pullFlash('antigo', []),
        ]));
    }

    /** POST /movimentacoes */
    public function store(Request $request): Response
    {
        $usuario = UsuarioAutenticado::doRequest($request);

        $dados = new NovaMovimentacao(
            tipo: $request->string('tipo'),
            data: $request->string('data'),
            descricao: $request->string('descricao'),
            valor: $request->string('valor'),
            parcelas: $request->string('parcelas') ?: '1',
            valorParcela: $request->string('valor_parcela'),
        );

        try {
            $criadas = $this->registrar->executar($usuario->id, $dados);
        } catch (ValidationException $e) {
            $this->session->flash('erros', $e->errors);
            $this->session->flash('antigo', $dados->toArray());

            return Response::redirect('/movimentacoes' . $this->queryCompetencia($request->string('competencia_atual')));
        }

        $primeira = $criadas[0];
        $this->session->flash('sucesso', count($criadas) > 1
            ? sprintf('Compra "%s" registrada em %d parcelas.', $primeira->descricao, count($criadas))
            : sprintf('Movimentação registrada: %s "%s" de %s.', mb_strtolower($primeira->tipo->rotulo()), $primeira->descricao, Formatter::money($primeira->valor)));

        return Response::redirect('/movimentacoes?competencia=' . $primeira->competencia()->chave());
    }

    /** POST /movimentacoes/excluir */
    public function destroy(Request $request): Response
    {
        $usuario = UsuarioAutenticado::doRequest($request);
        $id = filter_var($request->input('id'), FILTER_VALIDATE_INT);

        if ($id === false) {
            throw new HttpException(400, 'Identificador de movimentação inválido.');
        }

        try {
            $removidas = $this->excluir->executar($usuario->id, $id);
        } catch (MovimentacaoNaoEncontrada $e) {
            throw new HttpException(404, $e->getMessage());
        }

        $this->session->flash('sucesso', count($removidas) > 1
            ? sprintf('Compra "%s" excluída (%d parcelas).', $removidas[0]->descricao, count($removidas))
            : sprintf('Movimentação "%s" excluída.', $removidas[0]->descricao));

        return Response::redirect('/movimentacoes' . $this->queryCompetencia($request->string('competencia_atual')));
    }

    private function competenciaDaQuery(Request $request): ?Competencia
    {
        $valor = $request->query('competencia');

        return is_string($valor) ? $this->competencia($valor) : null;
    }

    private function queryCompetencia(string $valor): string
    {
        $competencia = $this->competencia($valor);

        return $competencia !== null ? '?competencia=' . $competencia->chave() : '';
    }

    private function competencia(string $valor): ?Competencia
    {
        try {
            return Competencia::fromString($valor);
        } catch (InvalidArgumentException) {
            return null;
        }
    }
}

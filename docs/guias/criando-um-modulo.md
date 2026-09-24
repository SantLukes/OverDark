# Guia: criando um módulo

Exemplo: um módulo **Metas** com a rota `GET /metas`.

## 1. Estrutura

```bash
mkdir -p src/Modules/Metas/{Domain,Application,Infrastructure,Http,Views}
```

## 2. Domínio (comece por aqui)

- Entidade `Domain/Meta.php`: `final`, propriedades `readonly`, valida invariantes no construtor. Dinheiro sempre como `Money`.
- Interface `Domain/MetaRepository.php` com os métodos que o caso de uso precisa (não um CRUD genérico).
- **Escreva o teste unitário da regra** em `tests/Unit/Metas/` antes de seguir.

## 3. Banco + Infraestrutura

- Crie `database/migrations/NNNN_criar_tabela_metas.sql` (com `usuario_id` + FK para `usuarios`) e rode `bin/console migrate`.
- `Infrastructure/PdoMetaRepository.php implements MetaRepository`: prepared statements e **sempre** `WHERE usuario_id = ?`.
- Para os testes unitários, crie o dublê `tests/Support/InMemoryMetaRepository.php`.

## 4. Aplicação (com logs)

- Consultas: `Application/MetaService.php` recebe `MetaRepository` (e `Clock`, se depender de "hoje").
- Escritas: um caso de uso por verbo (`Application/DefinirMeta.php`), com `executar(int $usuarioId, DTO $dados)`, que lança `ValidationException` com todos os erros por campo.
- `Application/PainelMetas.php`: DTO com tudo o que a tela precisa, **já calculado**.
- Injete `Psr\Log\LoggerInterface` nos casos de uso e registre o sucesso (`meta_definida`, info) e as recusas (`meta_validacao_recusada`, warning) seguindo o [contrato de log](../observabilidade.md).

## 5. HTTP + View

```php
// Http/MetaController.php
public function index(Request $request): Response
{
    $usuario = UsuarioAutenticado::doRequest($request);

    return Response::html($this->view->render('Metas::index', [
        'painel' => $this->service->painel($usuario->id),
        'sucesso' => $this->session->pullFlash('sucesso'),
        'erros' => $this->session->pullFlash('erros', []),
    ]));
}

public function store(Request $request): Response
{
    try {
        $this->definir->executar(UsuarioAutenticado::doRequest($request)->id, new NovaMeta(...));
    } catch (ValidationException $e) {
        $this->session->flash('erros', $e->errors);
        return Response::redirect('/metas');
    }

    $this->session->flash('sucesso', 'Meta salva.');
    return Response::redirect('/metas');   // Post/Redirect/Get
}
```

Todo `<form method="post">` da view precisa de `<?= $this->csrf() ?>`.

```php
<?php // Views/index.php
$this->layout('layouts/app', [
    'title' => 'Metas',
    'nav' => 'metas',
    'styles' => ['components.css', 'pages/metas.css'],
    'scripts' => ['pages/metas.js'],
]);
?>
<main class="page-shell">...</main>
```

## 6. Registrar

- `config/services.php`: ligue a interface à implementação, depois o serviço e o controller.
- `config/routes.php`: **dentro do grupo `Autenticado`**: `$router->get('/metas', [MetaController::class, 'index']);`
- `resources/views/partials/header.php`: adicione `'metas' => ['/metas', 'Metas']` ao menu, se for uma tela principal.
- Assets: `public/assets/css/pages/metas.css` e `public/assets/js/pages/metas.js`, se precisar.

## 7. Testar

- Unitários em `tests/Unit/Metas/` (domínio + casos de uso com o dublê em memória).
- Feature em `tests/Feature/MetasTest.php`, estendendo `FeatureTestCase`: use `logarComo()`, `get()`, `post()` (já envia o CSRF) e `valor()`/`linhas()` para conferir o banco. Inclua `TRUNCATE metas` no `setUp` do `FeatureTestCase`.
- `docker compose exec app composer check`

## 8. Documentar (obrigatório)

- Copie `docs/modulos/_TEMPLATE.md` para `docs/modulos/metas.md` e preencha.
- Adicione as regras novas em `docs/regras-de-negocio.md`, com código próprio (ex.: `T1`).
- Atualize a tabela de rotas do `README.md` e o índice em `docs/README.md`.
- Se tomou uma decisão estrutural (nova dependência, novo padrão), registre um ADR em `docs/adr/`.

## Checklist

- [ ] Nenhuma regra de negócio no controller ou na view
- [ ] Nenhum `float` para dinheiro
- [ ] Toda saída da view escapada (`$this->e()` ou helpers)
- [ ] O módulo não importa classes de outro módulo (exceto via serviço)
- [ ] Rotas no grupo `Autenticado`, todo POST com `$this->csrf()`
- [ ] Toda query filtra por `usuario_id`
- [ ] Eventos de sucesso e recusa logados, no catálogo de `docs/observabilidade.md` e cobertos em `tests/Feature/LogsTest.php`
- [ ] `composer check` verde
- [ ] Ficha do módulo criada

# Arquitetura

## Princípios

1. **Modular por funcionalidade.** Tudo o que pertence a "Movimentações" fica em `src/Modules/Movimentacoes/`: domínio, casos de uso, repositório, controller e view.
2. **Regra de negócio no domínio.** Cálculos e validações ficam em entidades e value objects (`Domain/`), nunca em controllers ou views.
3. **Dependa de interfaces.** Casos de uso recebem a *interface* do repositório. A implementação concreta (`Pdo*`) é escolhida em `config/services.php`, e os testes usam dublês em memória.
4. **Sem framework, mas com as mesmas ideias.** `src/Core/` é um "mini framework" com front controller, router, middlewares, container, views, sessão, CSRF e PDO. Ele segue o modelo mental de Laravel/Symfony. Ver [ADR 0001](adr/0001-arquitetura-modular-sem-framework.md).

## Camadas

```
src/
├── Core/                           Infraestrutura genérica. NÃO conhece regras de negócio.
│   ├── Application.php             Kernel: request → middlewares → controller → response; trata erros
│   ├── Clock/                      Clock (interface), SystemClock, FixedClock (testes)
│   ├── Config/Env.php              Leitura do .env
│   ├── Container/Container.php     DI com factories (singleton por requisição)
│   ├── Database/                   ConnectionFactory (PDO) e Migrator (database/migrations/*.sql)
│   ├── Http/                       Request, Response, Router, Route, Middleware, HttpException
│   ├── Logging/                    StructuredLogger (PSR-3), LogContext, LogRecord, Sanitizer, Handler/ (arquivo, Slack)
│   ├── Security/                   Csrf (token por sessão) e VerifyCsrfToken (middleware global)
│   ├── Session/                    Session (interface), NativeSession, ArraySession (testes)
│   ├── Validation/                 ValidationException (erros por campo)
│   └── View/                       View (resolve nomes, dados compartilhados) + Template ($this nas views)
├── Shared/                         Tipos usados por mais de um módulo
│   ├── Auth/UsuarioAutenticado     Identidade do usuário logado (id, nome, e-mail)
│   ├── Domain/                     Money, Competencia (mês/ano), PontoMensal
│   └── Formatting/                 Formatter (pt-BR) e MoneyParser (entrada do usuário)
└── Modules/<Modulo>/
    ├── Domain/                     Entidades, enums, value objects, exceções, interface do repositório
    ├── Application/                Casos de uso (verbos: RegistrarMovimentacao), serviços de consulta e DTOs Painel*
    ├── Infrastructure/             Pdo*Repository (MySQL) ou InMemory*Repository (demonstração)
    ├── Http/                       Controllers e, se houver, Middleware/
    └── Views/                      Templates PHP da tela
```

### Regras de dependência

| Camada | Pode depender de | Não pode depender de |
|---|---|---|
| `Domain` | `Shared/Domain` | `Application`, `Infrastructure`, `Http`, `Core` |
| `Application` | `Domain`, `Shared`, `Core\Clock`, `Core\Validation`, `Core\Session`, `Psr\Log\LoggerInterface` | `Http`, `Infrastructure` concreta |
| `Infrastructure` | `Domain`, `Shared`, `Core\Clock`, PDO | `Http`, `Application` |
| `Http` | `Application`, `Domain`, `Shared`, `Core\Http`, `Core\View`, `Core\Session` | `Infrastructure` |
| `Views` | Objetos que o controller passou + helpers do `Template` | Repositórios, serviços |
| `Core` | Nada de `Modules` | — |

**Integração entre módulos:** um módulo só usa o **serviço público** de outro, nunca o repositório. Exemplo: o Dashboard chama `MovimentacaoService`. Para saber quem está logado, use `Shared\Auth\UsuarioAutenticado`, e não as classes do módulo Auth.

## Ciclo de uma requisição

```
1. nginx          try_files → public/index.php (arquivos estáticos saem direto do nginx)
2. index.php      require bootstrap/app.php
3. bootstrap      autoload → Env::load(.env) → timezone → Container + config/services.php → Router + config/routes.php
4. Application    Router::match(método, caminho)  → Route (handler + middlewares do grupo) ou HttpException 404/405
                  pipeline: VerifyCsrfToken → [Visitante | Autenticado] → Controller
5. Middlewares    VerifyCsrfToken: POST sem _token válido → 419; compartilha csrfToken com as views
                  Autenticado:     sem sessão → 302 /login; com sessão → Request + View recebem UsuarioAutenticado
                  Visitante:       com sessão → 302 /dashboard
6. Controller     lê o Request → chama caso de uso/serviço → Response::html(View::render(...)) ou Response::redirect(...)
7. index.php      Response::send() → status + headers + corpo
```

### Middlewares

Implementam `Core\Http\Middleware::process(Request, Closure $next): Response`.

- **Globais:** declarados no `bootstrap/app.php` (hoje só `VerifyCsrfToken`).
- **Por rota:** via `$router->group([Middleware::class], fn ($r) => ...)` em `config/routes.php`.
- Ordem de execução: globais → grupo externo → grupo interno → controller.

### Escritas: Post/Redirect/Get + flash

Todo POST termina em `302`. Resultados e erros viajam para a próxima página pela sessão:

| Chave de flash | Conteúdo | Quem lê |
|---|---|---|
| `sucesso` | Mensagem de confirmação | View mostra `.flash-success` |
| `erros` | `array<campo, mensagem>` de `ValidationException` | View marca campos e reabre o modal |
| `antigo` / `email` | Valores enviados, para repopular o formulário | View |

### Tratamento de erros

| Situação | Resposta |
|---|---|
| Rota inexistente, ou recurso de outro usuário | 404 |
| Método não aceito | 405 + header `Allow` |
| POST sem token CSRF válido | 419 (sessão expirada) |
| Dados inválidos | Não é erro HTTP: vira flash `erros` + redirect |
| Credenciais inválidas | Não é erro HTTP: flash + redirect para `/login` |
| Banco fora do ar (`DatabaseUnavailable`) | 503 + log `critical` + Slack |
| Falha já registrada pelo caso de uso (`AlreadyLogged`) | 500, sem novo log |
| Qualquer outra exceção | 500 + log `error` + Slack. Com `APP_DEBUG=true`, mostra a stack trace |

Toda resposta de erro usa `resources/views/errors/error.php`: título amigável, **código do erro** (`correlation_id`) com botão Copiar, data/hora e `console.error` com o código (`public/assets/js/pages/erro.js`).

### Logs

Toda requisição passa por `Application::handle()`, que:

1. inicia o `LogContext` e gera (ou reaproveita, via header `X-Correlation-ID`) o `correlation_id`;
2. converte exceções em logs (exceto as `AlreadyLogged`, que já foram registradas com mais contexto pelo caso de uso): `HttpException` → notice/warning, `DatabaseUnavailable` → **critical** `banco_indisponivel` (503), `PDOException` → **error** `consulta_banco_falhou`, qualquer outra → **error** `erro_inesperado`;
3. registra `http_requisicao_concluida` (status, rota, handler, duração);
4. devolve o `correlation_id` no header `X-Correlation-ID` e nas telas de erro.

Eventos de negócio são logados nos casos de uso. Contrato, catálogo e regras: [observabilidade.md](observabilidade.md).

## Sessão, autenticação e CSRF

- `NativeSession` inicia a sessão sob demanda. O cookie é `overdark_session`, com `HttpOnly` e `SameSite=Lax`. Nos testes, `ArraySession`.
- Login: `AutenticarUsuario` valida o formato, busca por e-mail (normalizado em minúsculas) e confere com `password_verify`. Quando o e-mail não existe, verifica contra um hash fictício, para que o tempo de resposta não revele quais e-mails estão cadastrados. As duas falhas devolvem a mesma mensagem.
- `SessaoUsuario::entrar()` chama `session_regenerate_id` (contra session fixation) e guarda só o `usuario_id`. O logout chama `invalidate()`.
- CSRF: um token por sessão (`random_bytes(32)`), comparado com `hash_equals`. Todo `<form method="post">` precisa de `<?= $this->csrf() ?>`.

## Banco de dados

- `ConnectionFactory` cria o PDO com `ERRMODE_EXCEPTION`, `EMULATE_PREPARES=false`, charset `utf8mb4` e timeout de 3s. A conexão só é aberta quando um repositório é usado.
- **Sempre prepared statements.** Nenhum valor do usuário é concatenado em SQL.
- Toda consulta de dados do usuário filtra por `usuario_id`.
- Escritas de várias linhas (parcelas) rodam em transação: grava tudo ou nada.
- Schema em `database/migrations/NNNN_descricao.sql`, aplicado por `bin/console migrate` e registrado na tabela `migrations`. Ver [ADR 0003](adr/0003-persistencia-pdo-sql-puro.md).

## Views

- Nomes: `'Modulo::arquivo'` → `src/Modules/Modulo/Views/arquivo.php`; `'pasta/arquivo'` → `resources/views/pasta/arquivo.php`.
- `View::share()` disponibiliza dados em todas as views da requisição: `csrfToken` (pelo `VerifyCsrfToken`) e `usuarioLogado` (pelo `Autenticado`).
- Dentro do template, `$this` é um `OverDark\Core\View\Template`:

| Helper | Uso |
|---|---|
| `$this->layout('layouts/app', [...])` | Declara o layout (chamar no topo) |
| `$this->insert('partials/x', [...])` | Inclui um partial |
| `$this->e($valor)` | Escapa HTML. **Toda saída dinâmica passa por ele** |
| `$this->csrf()` | `<input type="hidden" name="_token">`, **obrigatório em todo POST** |
| `$this->money(Money)` / `signedMoney(Money)` | `R$ 3.200` / `+ R$ 860` |
| `$this->percent(float, signed, decimals)` | `+10,66%` |
| `$this->date(DateTimeInterface)` | `05/03/2026` |
| `$this->asset('css/base.css')` | `/assets/css/base.css` |
| `$this->json($dados)` | JSON escapado para atributos (ex.: `data-chart`) |

- Parâmetros de `layouts/app`: `title`, `nav` (item ativo), `styles`, `scripts`, `charts` (carrega o Chart.js).

## Convenções de código

- `declare(strict_types=1);` em todo arquivo PHP. Classes `final` por padrão e propriedades `readonly`.
- **Nomes de domínio em português** (`Movimentacao`, `Competencia`, `Parcelamento`). **Nomes técnicos em inglês** (`Controller`, `Repository`, `Middleware`).
- Casos de uso são classes com nome de verbo (`RegistrarMovimentacao`, `ExcluirMovimentacao`, `AutenticarUsuario`) e um método `executar()`.
- Entrada bruta de formulário vira um DTO (`NovaMovimentacao`). O caso de uso valida e lança `ValidationException` com **todos** os erros de uma vez.
- Invariantes da entidade são validadas no construtor: se o objeto existe, ele é válido.
- "Agora" vem sempre do `Clock` injetado, nunca de `new DateTimeImmutable()` direto nas regras.
- Enums com *backing value* estável (`'receita'`, `'cartao'`), usado no HTML, no CSS e no banco.

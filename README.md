# OverDark

Sistema pessoal de acompanhamento financeiro: receitas, gastos, compras parceladas no cartão de crédito e carteira de investimentos, tudo consolidado mês a mês.

> **Status**
> - ✅ **Login, Dashboard e Movimentações funcionam de verdade:** MySQL, sessão, CSRF, cadastro, parcelamento e exclusão.
> - 🟡 **Investimentos** ainda usa dados de demonstração em memória.
> - ✅ **Logs estruturados** (JSON Lines) com `correlation_id` em toda requisição e alerta no **Slack** para falhas. Ver [docs/observabilidade.md](docs/observabilidade.md).
>
> Veja o [roadmap](docs/roadmap.md).

---

## Sumário

- [Stack](#stack)
- [Rodando o projeto](#rodando-o-projeto)
- [Comandos do dia a dia](#comandos-do-dia-a-dia)
- [Estrutura de pastas](#estrutura-de-pastas)
- [Rotas](#rotas)
- [Arquitetura em 1 minuto](#arquitetura-em-1-minuto)
- [Configuração (.env)](#configuração-env)
- [Qualidade: testes e análise estática](#qualidade-testes-e-análise-estática)
- [Documentação](#documentação)

---

## Stack

| Camada | Tecnologia |
|---|---|
| Linguagem | PHP 8.2 (sem framework) com `declare(strict_types=1)` |
| Autoload / deps | Composer (PSR-4, namespace `OverDark\`), zero dependências de produção |
| Servidor | nginx 1.27 → PHP-FPM 8.2 (Docker) |
| Banco | MySQL 8.0 via PDO, schema versionado em `database/migrations/*.sql` |
| Sessão / segurança | Sessão nativa do PHP (cookie `HttpOnly`/`SameSite=Lax`), CSRF por sessão, senhas com `password_hash` |
| Front | HTML renderizado no servidor + Bootstrap 5.3 (CDN) + CSS próprio + JS em módulos ES |
| Gráficos | Chart.js 4 (CDN) |
| Logs | PSR-3 (`psr/log`) + logger estruturado próprio: arquivo JSON Lines + Slack Incoming Webhook |
| Testes | PHPUnit 11: unitários + feature contra um banco MySQL de teste |
| Análise estática | PHPStan 2 (nível 8) |

## Rodando o projeto

### Primeira vez

```bash
cp .env.example .env                                 # ajuste UID/GID se `id -u` não for 1000
docker compose up -d --build
docker compose exec app composer install
docker compose exec app bin/console migrate          # cria as tabelas
docker compose exec app bin/console usuario:criar "Seu Nome" voce@email.com   # pede a senha
```

Acesse **http://localhost:8086** e entre com o e-mail e a senha criados. O sistema começa **zerado**.

> Não existe tela de cadastro de usuário: contas são criadas pelo `bin/console`.

### Sem Docker (servidor embutido do PHP)

Requisitos: PHP ≥ 8.2 com `pdo_mysql`, `dom`, `mbstring` e `xml`, além de um MySQL acessível (ajuste `DB_*` no `.env`).

```bash
composer install
php bin/console migrate
composer serve        # http://localhost:8000
```

## Comandos do dia a dia

| Comando | O que faz |
|---|---|
| `docker compose exec app bin/console migrate` | Executa as migrations pendentes |
| `docker compose exec app bin/console usuario:criar "Nome" email [senha]` | Cria usuário (pede a senha, sem eco, se omitida) |
| `docker compose exec app bin/console logs:tail` | Logs formatados e coloridos, acompanhando em tempo real |
| `docker compose exec app bin/console slack:testar` | Envia uma mensagem de teste ao webhook do Slack |
| `docker compose exec app composer check` | PHPStan + PHPUnit. **Rode antes de todo commit** |
| `docker compose exec app composer test` | Só os testes |
| `docker compose exec app composer analyse` | Só a análise estática |
| `docker compose exec db mysql -uoverdark -poverdark overdark` | Console SQL do banco de desenvolvimento |
| `docker compose restart web` | Recarrega o nginx após mudar `docker/nginx/default.conf` |

## Estrutura de pastas

```
OverDark/
├── bin/console                # CLI: migrate, usuario:criar
├── bootstrap/app.php          # Monta a aplicação (autoload, .env, timezone, container, rotas)
├── config/
│   ├── app.php                # Configurações gerais (env, debug, timezone)
│   ├── routes.php             # Tabela de rotas, com grupos Visitante/Autenticado
│   └── services.php           # Container de DI: toda classe injetável entra aqui
├── database/migrations/       # Schema em SQL puro, executado em ordem pelo bin/console migrate
├── storage/logs/              # overdark.log (JSON Lines). Não versionado
├── public/                    # ÚNICA pasta exposta pelo servidor web
│   ├── index.php              # Front controller
│   └── assets/{css,js,img}/
├── resources/views/           # Views compartilhadas: layouts/, partials/, errors/
├── src/
│   ├── Core/                  # "Mini framework": HTTP, roteador, middleware, container, views,
│   │                          #   sessão, CSRF, banco (PDO + migrator), relógio, validação, logging
│   ├── Shared/                # Tipos comuns: Money, Competencia, PontoMensal, Formatter,
│   │                          #   MoneyParser, UsuarioAutenticado
│   └── Modules/<Modulo>/
│       ├── Domain/            # Entidades, enums, value objects, interface do repositório
│       ├── Application/       # Casos de uso e serviços + DTOs de tela
│       ├── Infrastructure/    # Repositórios (Pdo*, InMemory*)
│       ├── Http/              # Controllers (+ Middleware/ quando houver)
│       └── Views/             # Templates da tela
├── tests/
│   ├── Unit/                  # Regras e casos de uso, com dublês em memória
│   ├── Feature/               # Aplicação real + banco overdark_test
│   └── Support/               # FeatureTestCase e repositórios em memória
├── docs/                      # Documentação técnica e por módulo
├── docker/                    # Dockerfile do PHP, nginx e script de init do MySQL
└── AGENTS.md / CLAUDE.md      # Instruções para assistentes de IA
```

## Rotas

| Método | Caminho | Acesso | Ação |
|---|---|---|---|
| GET | `/` e `/login` | visitante | Tela de login |
| POST | `/login` | visitante | Autentica e redireciona para `/dashboard` |
| POST | `/logout` | logado | Encerra a sessão |
| GET | `/dashboard` | logado | Indicadores do mês, evolução do saldo e saldo mês a mês do ano |
| GET | `/movimentacoes[?competencia=AAAA-MM]` | logado | Lançamentos e consolidado do mês (padrão: mês atual) |
| POST | `/movimentacoes` | logado | Registra movimentação (cartão parcelado gera N parcelas) |
| POST | `/movimentacoes/excluir` | logado | Exclui movimentação (parcela exclui a compra inteira) |
| GET | `/investimentos` | logado | Carteira (dados de demonstração) |

- **Visitante** logado é mandado para o dashboard. **Logado** sem sessão é mandado para o login.
- Todo POST exige token CSRF. Sem ele, a resposta é **419**.
- Rota inexistente dá **404**. Método errado dá **405**, com o header `Allow`.
- Links antigos no formato `index.php?url=/rota` continuam funcionando.

## Arquitetura em 1 minuto

```
Request ─► public/index.php ─► Application::handle()
             ├─ Router::match()                      → Route (handler + middlewares)
             ├─ VerifyCsrfToken (global)             → 419 se POST sem token
             ├─ Autenticado / Visitante (por grupo)  → redireciona ou anexa UsuarioAutenticado
             ├─ Controller → Caso de uso / Service → Repository (interface) → Pdo* (MySQL)
             └─ View::render('Modulo::tela')         → layout + partials → Response
```

- **Regra de negócio mora no `Domain/`.** Por exemplo: saldo = receitas − (gastos + cartão), parcelas nos meses seguintes, só cartão parcela.
- **Dinheiro é sempre `Money` em centavos (inteiro).** No banco, `BIGINT` em centavos. Ver [ADR 0002](docs/adr/0002-dinheiro-em-centavos.md).
- **Escritas seguem Post/Redirect/Get:** o resultado vai para a sessão como *flash* e aparece na próxima página.
- **Todo dado é do usuário logado.** Os repositórios sempre filtram por `usuario_id`.
- **Erros são tratados em um ponto só** (`Application::handle`). Ali nasce o `correlation_id`, toda requisição é logada e toda falha vira `error`/`critical` (e alerta no Slack).
- **Eventos de negócio** (`movimentacao_registrada`, `login_recusado`...) são logados nos casos de uso, seguindo o [contrato de log](docs/observabilidade.md).

Detalhes em [docs/arquitetura.md](docs/arquitetura.md).

## Configuração (.env)

| Variável | Padrão | Uso |
|---|---|---|
| `APP_ENV` | `production` | Ambiente (`local`, `production`) |
| `APP_DEBUG` | `false` | `true` exibe stack trace nas páginas de erro. **Nunca em produção** |
| `APP_TIMEZONE` | `America/Sao_Paulo` | Fuso usado para "hoje", competência atual e datas gravadas |
| `UID` / `GID` | `1000` | Usuário com que o container `app` roda (deve ser o seu no host) |
| `WEB_PORT` | `8086` | Porta do nginx no host |
| `DB_HOST_PORT` | `3307` | Porta do MySQL exposta no host (DBeaver, PhpStorm...) |
| `DB_HOST` / `DB_PORT` | `db` / `3306` | Conexão da aplicação (rede interna do Docker) |
| `DB_DATABASE` | `overdark` | Banco da aplicação (os testes usam `overdark_test`) |
| `DB_USERNAME` / `DB_PASSWORD` | `overdark` / `overdark` | Usuário do banco da aplicação |
| `DB_ROOT_PASSWORD` | `root` | Senha root do MySQL (apenas desenvolvimento) |
| `DB_LOCK_WAIT_TIMEOUT` | `3` | Segundos que uma gravação espera por registro travado antes de falhar |
| `LOG_PATH` | `storage/logs/overdark.log` | Arquivo de log (JSON Lines) |
| `LOG_LEVEL` | `debug` | Nível mínimo gravado no arquivo |
| `SLACK_WEBHOOK_URL` | vazio | Incoming Webhook do Slack. **Segredo: nunca versione.** Vazio = desligado |
| `LOG_SLACK_LEVEL` | `error` | Nível mínimo enviado ao Slack (`info` para demonstrar sucessos) |

Variáveis reais do ambiente têm prioridade sobre o `.env`.

## Qualidade: testes e análise estática

- **Unit** (`tests/Unit`): `Money`, `MoneyParser`, `Competencia`, `Formatter`, `Router`, parcelamento, regras de movimentação e investimento, autenticação, casos de uso e Dashboard (com repositórios em memória de `tests/Support`).
- **Feature** (`tests/Feature`): sobe a aplicação real contra o banco **`overdark_test`**, que é recriado a cada execução, com sessão em memória e relógio fixo em 15/03/2026. Cobre login/logout, proteção de rotas, CSRF, cadastro, parcelamento, exclusão, isolamento entre usuários e os números do dashboard.
- **PHPStan nível 8** em `src/`, `config/`, `bootstrap/` e `tests/`. As views ficam de fora porque dependem de `extract()`.

## Documentação

| Documento | Conteúdo |
|---|---|
| [docs/README.md](docs/README.md) | Índice da documentação |
| [docs/arquitetura.md](docs/arquitetura.md) | Camadas, ciclo da requisição, middlewares, sessão, banco, convenções |
| [docs/regras-de-negocio.md](docs/regras-de-negocio.md) | Todas as regras de negócio num lugar só |
| [docs/frontend.md](docs/frontend.md) | Camadas de CSS, componentes JS, data-attributes, formulários |
| [docs/observabilidade.md](docs/observabilidade.md) | Contrato de log, catálogo de eventos, Slack, correlation_id, **simulação de erro de produção** e roteiro de demo |
| [docs/infraestrutura.md](docs/infraestrutura.md) | Docker, nginx, banco, migrations, variáveis |
| [docs/modulos/](docs/modulos/) | Uma ficha por módulo |
| [docs/guias/criando-um-modulo.md](docs/guias/criando-um-modulo.md) | Passo a passo para adicionar uma funcionalidade |
| [docs/adr/](docs/adr/) | Registro de decisões de arquitetura |
| [docs/roadmap.md](docs/roadmap.md) | O que falta e em que ordem |

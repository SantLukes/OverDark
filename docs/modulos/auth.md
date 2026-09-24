# Módulo: Auth

## Propósito

Controlar quem entra no sistema: login por e-mail e senha, sessão, logout e proteção das páginas internas.

## Status

**Funcional.** Não há tela de cadastro: usuários são criados pelo `bin/console usuario:criar`.

## Rotas

| Método | Caminho | Grupo | Ação | Descrição |
|---|---|---|---|---|
| GET | `/`, `/login` | Visitante | `AuthController::showLogin` | Tela de login, com erros e e-mail vindos do flash |
| POST | `/login` | Visitante | `AuthController::login` | Sucesso → 302 `/dashboard`; falha → flash + 302 `/login` |
| POST | `/logout` | Autenticado | `AuthController::logout` | Invalida a sessão → 302 `/login` |

## Estrutura

| Classe | Camada | Responsabilidade |
|---|---|---|
| `Domain\Usuario` | Domain | id, nome, e-mail, hash. `senhaConfere()`, `identidade()` |
| `Domain\UsuarioRepository` | Domain | `buscarPorId`, `buscarPorEmail`, `criar` |
| `Domain\CredenciaisInvalidas` | Domain | Exceção com mensagem única para o usuário e `$motivo` (`usuario_inexistente`/`senha_incorreta`) para log |
| `Application\AutenticarUsuario` | Application | Valida formato, busca, confere senha (com hash fictício contra timing attack) |
| `Application\CriarUsuario` | Application | Valida nome/e-mail único/senha ≥ 8 e grava com `password_hash` |
| `Application\SessaoUsuario` | Application | `entrar()` (regenera o ID de sessão), `sair()`, `usuario()`, `estaLogado()` |
| `Infrastructure\PdoUsuarioRepository` | Infrastructure | Tabela `usuarios` |
| `Http\AuthController` | Http | Login/logout (Post/Redirect/Get) |
| `Http\Middleware\Autenticado` | Http | Sem sessão → `/login`. Com sessão, anexa `Shared\Auth\UsuarioAutenticado` ao Request (`UsuarioAutenticado::doRequest($request)`) e às views (`$usuarioLogado`) |
| `Http\Middleware\Visitante` | Http | Logado → `/dashboard` |
| `Views/login.php` | View | Layout `guest`, alerta de erro, `is-invalid` por campo, CSRF |

## Banco

Tabela `usuarios` (migration `0001`): `id`, `nome`, `email` (único), `senha_hash`, `criado_em`.

## Regras de negócio

`A1` a `A7`. Ver [regras-de-negocio.md](../regras-de-negocio.md#usuários-e-acesso).

## Dependências

- Core: `Session`, `Csrf`, `Clock`, `ValidationException`.
- Shared: `UsuarioAutenticado`, a identidade que os outros módulos consomem.
- Front: `css/pages/login.css`. O header (`resources/views/partials/header.php`) mostra a inicial e o nome e tem o botão Sair.
- `config/services.php`: `UsuarioRepository` → `PdoUsuarioRepository`.

## Logs emitidos

| Evento | Nível | Onde |
|---|---|---|
| `usuario_autenticado` | info | `AutenticarUsuario` |
| `login_recusado` (401 `usuario_inexistente`/`senha_incorreta`, 422 `dados_invalidos`) | warning | `AutenticarUsuario` |
| `logout_realizado` | info | `SessaoUsuario::sair` |
| `usuario_criado` / `usuario_cadastro_recusado` | info / warning | `CriarUsuario` |

`Autenticado` e `AuthController::login` definem o `usuario_id` no `LogContext`. Ver [observabilidade.md](../observabilidade.md).

## Testes

| Arquivo | Cobre |
|---|---|
| `tests/Unit/Auth/AuthTest.php` | Hash, normalização de e-mail, credenciais certas/erradas, mensagem única, validações de cadastro |
| `tests/Feature/AuthTest.php` | Rotas protegidas, login/logout de ponta a ponta, flash de erro, CSRF 419, header com usuário |

## Pendências

- [ ] Limite de tentativas de login (rate limit)
- [ ] "Lembrar de mim" / expiração de sessão configurável
- [ ] Tela de cadastro e troca de senha

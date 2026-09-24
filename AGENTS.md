# AGENTS.md — instruções para assistentes de IA

Este arquivo orienta agentes de IA (Claude Code, Copilot, Cursor, MCPs de leitura de repositório etc.) que trabalham no OverDark. Leia antes de alterar qualquer coisa.

## O projeto em 5 linhas

- Sistema **pessoal** de controle financeiro: receitas, gastos, cartão de crédito parcelado e investimentos.
- **PHP 8.2 sem framework**, Composer (PSR-4 `OverDark\` → `src/`), nginx + PHP-FPM + MySQL via Docker.
- Arquitetura **modular por funcionalidade**: `src/Modules/<Modulo>/{Domain,Application,Infrastructure,Http,Views}`.
- **MySQL via PDO** (repositórios `Pdo*`), schema em `database/migrations/*.sql`. Login real com sessão + CSRF.
- Módulos funcionais: **Auth, Dashboard, Movimentações**. **Investimentos** ainda usa dados de demonstração em memória.
- **Logs estruturados** (PSR-3, JSON Lines + Slack) com contrato fixo: `docs/observabilidade.md`.

## Mapa de leitura (fonte da verdade)

| Preciso de... | Leia |
|---|---|
| Visão geral, setup, rotas | `README.md` |
| Camadas, ciclo da requisição, convenções | `docs/arquitetura.md` |
| **Qualquer cálculo financeiro** | `docs/regras-de-negocio.md` |
| **Qualquer log** | `docs/observabilidade.md` |
| Detalhes de um módulo | `docs/modulos/<modulo>.md` |
| CSS/JS, componentes e data-attributes | `docs/frontend.md` |
| Docker, nginx, banco | `docs/infraestrutura.md` |
| Criar funcionalidade nova | `docs/guias/criando-um-modulo.md` |
| Por que algo é assim | `docs/adr/` |

## Comandos

Rode sempre **dentro do container** (o PHP do host pode não ter as extensões necessárias):

```bash
docker compose exec app composer check                 # PHPStan nível 8 + PHPUnit. Precisa passar antes de concluir qualquer tarefa
docker compose exec app composer test
docker compose exec app composer analyse
docker compose exec app bin/console migrate            # aplica migrations pendentes
docker compose exec app bin/console usuario:criar "Nome" email senha
docker compose exec db mysql -uoverdark -poverdark overdark   # consultar o banco de dev
docker compose exec app bin/console logs:tail          # logs formatados, em tempo real
docker compose restart web                             # após alterar docker/nginx/default.conf
```

Os testes de feature usam o banco `overdark_test` (recriado a cada execução). Nunca aponte testes para `overdark`.

App em http://localhost:8086.

## Regras obrigatórias

1. **Dinheiro é `Money` (centavos, int). Nunca float.** Crie com `Money::fromReais(int|string)`. `toFloat()` só para gráficos.
2. **Regra de negócio só em `Domain/`.** Controllers leem o request e renderizam. Views só exibem.
3. **Views escapam tudo:** `$this->e()`, `$this->money()`, `$this->date()`... Nada de `echo $var` cru.
4. **Um módulo não importa classes de outro.** Integração entre módulos passa pelo *serviço* injetado no container.
5. **Novas classes injetáveis** são registradas em `config/services.php`. **Novas rotas** vão em `config/routes.php`.
6. `declare(strict_types=1);`, classes `final`, propriedades `readonly`, invariantes validadas no construtor.
7. **Nomes de domínio em português** (`Movimentacao`, `Competencia`), nomes técnicos em inglês (`Controller`, `Repository`).
8. Front: nada de JS inline nem CSS em `<style>`. Use `public/assets/{css,js}` e os componentes existentes (`modal.js`, `line-chart.js`, `user-menu.js`). Não use as classes `.modal`/`.modal-backdrop`, que são do Bootstrap. Use `.app-modal*`.
9. **Segurança de dados:** rotas internas ficam no grupo `Autenticado` em `config/routes.php`. Todo `<form method="post">` tem `<?= $this->csrf() ?>`. Toda query filtra por `usuario_id`, e o usuário vem de `UsuarioAutenticado::doRequest($request)`. Sempre prepared statements.
10. **Escritas:** caso de uso com verbo (`RegistrarX`) que lança `ValidationException` com todos os erros. O controller faz Post/Redirect/Get com flash (`sucesso`, `erros`, `antigo`).
11. **"Agora" vem do `Clock` injetado**, nunca de `new DateTimeImmutable()` dentro de regras.
11b. **Formulários:** `data-validate` + atributos de `components/form-validation.js`, com as **mesmas mensagens** do backend (`data-msg-*`). A validação em JS é só UX: o backend continua validando tudo. Campos de dinheiro usam `data-mask="money"`. Nunca use `alert`/`confirm`/`prompt` nativos: ações destrutivas usam `data-confirm` (modal).
12. **Banco:** mudança de schema = **nova** migration `NNNN_*.sql`. Nunca edite uma já aplicada.
13. Não mude os valores de demonstração do módulo Investimentos sem pedido explícito.
14. **Logs:** injete `LoggerInterface`. A mensagem é o **nome do evento** em snake_case no passado (`movimentacao_registrada`). Sempre `operacao` e `status`, mais ids de domínio. `motivo` em recusas/falhas, `integracao` para serviços externos. Nunca logue senha, token, texto digitado pelo usuário ou e-mail sem máscara (`Sanitizer::mascararEmail`). Recusa esperada = `warning`; falha do sistema = `error`. Não logue `correlation_id`/`usuario_id` à mão (vêm do `LogContext`) nem erros não tratados (o kernel já loga). Todo evento novo entra no catálogo e em `tests/Feature/LogsTest.php`.
15. `SLACK_WEBHOOK_URL` é segredo: só no `.env`, nunca em código, docs ou commits.

## Documentação faz parte da entrega

Ao terminar uma mudança, atualize na mesma tarefa:

| Se você... | Atualize |
|---|---|
| Criou um módulo | `docs/modulos/<modulo>.md` (a partir de `_TEMPLATE.md`), `docs/README.md`, tabela de rotas do `README.md` |
| Mudou rota, classe ou dependência de um módulo | A ficha em `docs/modulos/` |
| Criou ou alterou uma regra de cálculo/validação | `docs/regras-de-negocio.md` (com código da regra e o teste que a cobre) |
| Criou componente de CSS/JS ou data-attribute | `docs/frontend.md` |
| Mudou Docker, nginx, variáveis ou schema do banco | `docs/infraestrutura.md` (tabela de migrations) e `.env.example` |
| Tomou decisão estrutural (lib nova, novo padrão) | Novo ADR em `docs/adr/` |
| Criou/alterou um evento de log | Catálogo em `docs/observabilidade.md` + seção "Logs emitidos" da ficha do módulo |
| Concluiu item do roadmap | `docs/roadmap.md` |

## Armadilhas conhecidas

- `extract()` recebe o array por referência: em `Template::capture()` usamos uma cópia (`[...$this->data]`) porque `$data` é `readonly`.
- O container `app` roda com `UID`/`GID` do host. Se aparecerem arquivos com dono root, confira `UID`/`GID` no `.env`.
- Flash é consumido na leitura (`pullFlash`): leia uma vez no controller e passe para a view.
- `extract()` nas views: variáveis compartilhadas (`csrfToken`, `usuarioLogado`) podem ser sobrescritas por dados com o mesmo nome passados pelo controller. Evite reutilizar esses nomes.
- `Request::normalizePath` trata `/index.php` como `/`. Links legados `?url=/rota` continuam funcionando.
- `favicon.ico` não existe (404 esperado no console).
- **Simulação de erro da demo:** `PdoMovimentacaoRepository::salvar()` tem a linha `// $rotinaConcorrente = Demo\FechamentoMensalEmAndamento::travarMovimentacoesDo($usuarioId);`. Ela deve ficar **comentada**; o `SimulacaoDeErroTest` falha se não estiver. Não remova o bloco nem a classe `Infrastructure/Demo/FechamentoMensalEmAndamento`: eles são usados na apresentação.
- Lock wait timeout de sessão (`DB_LOCK_WAIT_TIMEOUT`, padrão 3s) é aplicado pelo `ConnectionFactory`. Com a tabela vazia, `SELECT ... FOR UPDATE` trava o índice inteiro (gap lock); os testes semeiam dados para refletir produção.
- Falhas logadas dentro de um caso de uso devem relançar uma exceção `AlreadyLogged`, para o kernel não duplicar o log/alerta.

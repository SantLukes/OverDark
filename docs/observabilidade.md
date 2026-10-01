# Observabilidade: logs estruturados

> Mais contexto → menos perguntas → investigação mais rápida (menor MTTR).

Este documento é o **contrato de log** do OverDark: o formato, o catálogo de eventos e as regras para criar novos logs. É o mesmo padrão proposto para os sistemas do time (sistema interno de farmácia, e-commerces e blogs).

## O contrato

Todo log é **um objeto JSON por linha** (JSON Lines) com esta estrutura:

```json
{
  "timestamp": "2026-09-23T22:07:35.917-03:00",
  "level": "info",
  "event": "compra_parcelada_registrada",
  "operacao": "registrar_movimentacao",
  "status": "SUCCESS",
  "correlation_id": "req_dee997",
  "usuario_id": 1,
  "grupo_parcelamento": "59a8af715fbebe393f202f6606ec1fc1",
  "parcelas": 10,
  "valor_total_centavos": 399999
}
```

| Grupo | Campo | O que é | Exemplo |
|---|---|---|---|
| **Base** (sempre presente, nesta ordem) | `event` | Nome estável do acontecimento, em `snake_case`. **É a mensagem**: nada de "Erro ao processar" | `movimentacao_registrada` |
| | `operacao` | Rotina/etapa executada | `registrar_movimentacao` |
| | `status` | Desfecho: `SUCCESS`, `FAILED` ou um código HTTP | `SUCCESS`, `422`, `503` |
| | `correlation_id` | Liga todos os logs de uma mesma requisição (ou comando) | `req_884a1c`, `cli_3f2a10` |
| **Domínio** (depende da operação) | `usuario_id`, `movimentacao_id`, `grupo_parcelamento`, `competencia`... | Recursos afetados | `movimentacao_id: 42` |
| **Opcional** | `motivo` | Causa da falha ou desvio | `senha_incorreta`, `conexao_recusada` |
| | `integracao` | Serviço externo envolvido | `MySQL`, `Slack` |
| Automático | `timestamp`, `level` | ISO 8601 com milissegundos; nível PSR-3 | |

Regras:

- **Nem todo log precisa de todos os campos.** A base é fixa, e o resto depende da operação.
- `correlation_id` e `usuario_id` são **preenchidos automaticamente** pelo `LogContext`. Quem loga não repassa esses valores.
- Campo base ausente sai como `null`, mas continua presente, pra estrutura ser sempre previsível.

## Como registrar um log

Injete `Psr\Log\LoggerInterface` e use o **nome do evento como mensagem**:

```php
$this->logger->info('movimentacao_registrada', [
    'operacao' => 'registrar_movimentacao',
    'status' => 'SUCCESS',
    'movimentacao_id' => $movimentacao->id,
    'valor_centavos' => $movimentacao->valor->cents,
]);
```

Onde logar:

| Tipo de evento | Onde | Exemplo |
|---|---|---|
| Regra de negócio (sucesso ou recusa) | **Caso de uso** (`Application/`) | `RegistrarMovimentacao` |
| Requisição HTTP, erros não tratados | Automático, em `Application::handle()` | Já feito, não repita |
| Contexto da execução (usuário) | Middleware `Autenticado` / `AuthController` | `LogContext::definirUsuario()` |
| Falha de infraestrutura **dentro** de um caso de uso | No caso de uso, com o contexto de negócio, lançando uma exceção `AlreadyLogged` | `movimentacao_registro_falhou` |

### Logue uma vez, onde há mais contexto

Se o caso de uso sabe *quem, o quê, quanto*, é ele quem registra a falha. Depois ele relança uma exceção que implementa `Core\Logging\AlreadyLogged`, e o kernel apenas mostra a tela de erro, **sem logar de novo** (e sem alerta duplicado no Slack).

```php
try {
    $salvas = $this->repository->salvar($usuarioId, $movimentacoes);
} catch (PDOException $e) {
    $this->logger->error('movimentacao_registro_falhou', [
        'operacao' => 'registrar_movimentacao', 'status' => 500,
        'tipo' => $tipo->value, 'parcelas' => count($movimentacoes), 'valor_centavos' => $valor->cents,
        'motivo' => SqlErrorReason::de($e), 'integracao' => 'MySQL', 'exception' => $e,
    ]);
    throw new FalhaAoRegistrarMovimentacao($e);   // implements AlreadyLogged
}
```

`SqlErrorReason` traduz o erro do banco em `motivo` legível (pelo errno do MySQL e, na falta dele, pelo SQLSTATE): `1205` → `lock_wait_timeout`, `1213` → `deadlock`, `1062` → `registro_duplicado`, `2006` → `conexao_perdida` etc. Também indica se a falha é **retentável**: falhas transitórias como lock e deadlock resolvem ao tentar de novo, e o suporte pode orientar a cliente sem acionar o dev.

## Níveis: use o certo

| Nível | Quando | Vai pro Slack? |
|---|---|---|
| `debug` | Detalhe de desenvolvimento | não |
| `info` | **Sucesso de negócio** e requisições concluídas | não* |
| `notice` | Algo normal, mas digno de nota (404, 405) | não* |
| `warning` | O usuário/cliente errou e o sistema se defendeu (validação, senha errada, CSRF, acesso negado) | não* |
| `error` | O **sistema** falhou (exceção não tratada, consulta que quebrou) | **sim** |
| `critical` | Uma dependência inteira caiu (banco fora do ar) | **sim** |

\* Configurável por `LOG_SLACK_LEVEL`. Pra demonstrar eventos de sucesso no Slack, use `info` temporariamente.

> Validação recusada **não é erro**. Se tudo for `error`, o canal vira ruído e ninguém mais olha.

## Catálogo de eventos

### Sucesso

| `event` | `operacao` | Nível | Campos de domínio |
|---|---|---|---|
| `http_requisicao_concluida` | `processar_requisicao` | info (notice p/ 4xx, warning p/ 5xx) | `metodo`, `rota`, `handler`, `duracao_ms`, `ip`, `status` = HTTP |
| `usuario_autenticado` | `autenticar_usuario` | info | `usuario_id`, `email` (mascarado) |
| `logout_realizado` | `encerrar_sessao` | info | `usuario_id` |
| `movimentacao_registrada` | `registrar_movimentacao` | info | `movimentacao_id`, `tipo`, `valor_centavos`, `data`, `competencia` |
| `compra_parcelada_registrada` | `registrar_movimentacao` | info | `grupo_parcelamento`, `movimentacao_ids`, `parcelas`, `valor_total_centavos`, `valor_informado_centavos`, `primeira_competencia`, `ultima_competencia` |
| `movimentacao_excluida` | `excluir_movimentacao` | info | `movimentacao_id`, `movimentacao_ids`, `quantidade`, `grupo_parcelamento`, `tipo` |
| `usuario_criado` | `criar_usuario` | info | `usuario_id`, `email` (mascarado) |
| `migrations_executadas` | `migrar_banco` | info | `migrations`, `quantidade`, `integracao: MySQL` |

### Recusas esperadas (o sistema se defendeu)

| `event` | `status` | `motivo` | Nível |
|---|---|---|---|
| `login_recusado` | 401 | `usuario_inexistente` / `senha_incorreta` | warning |
| `login_recusado` | 422 | `dados_invalidos` (+ `campos`) | warning |
| `movimentacao_validacao_recusada` | 422 | `dados_invalidos` (+ `campos`, `tipo`) | warning |
| `movimentacao_exclusao_negada` | 404 | `inexistente_ou_de_outro_usuario` | warning |
| `usuario_cadastro_recusado` | 422 | `dados_invalidos` | warning |
| `csrf_token_invalido` | 419 | `token_ausente_ou_expirado` | warning |
| `recurso_nao_encontrado` | 404 | `rota_ou_recurso_inexistente` | notice |
| `metodo_nao_permitido` | 405 | `metodo_<verbo>` | notice |

### Falhas do sistema (alertam no Slack)

| `event` | `status` | `motivo` / `integracao` | Nível |
|---|---|---|---|
| `banco_indisponivel` | 503 | `conexao_recusada` / `MySQL` (+ `host`, `database`, `excecao`) | **critical** |
| `movimentacao_registro_falhou` | 500 | ex.: `lock_wait_timeout`, `deadlock` / `MySQL` (+ `usuario_id`, `tipo`, `parcelas`, `valor_centavos`, `data`, `sqlstate`, `errno`, `retentavel`, `duracao_ms`, `excecao`) | **error** |
| `consulta_banco_falhou` | 500 | ex.: `tabela_inexistente` / `MySQL` (+ `sqlstate`) | **error** |
| `erro_inesperado` | 500 | nome da exceção (+ `excecao`) | **error** |
| `comando_falhou` | FAILED | nome da exceção (CLI) | **error** |
| `log_destino_falhou` | FAILED | mensagem do destino / `Slack` | warning |

## O que NUNCA vai para o log

O `Sanitizer` aplica isso automaticamente, e há teste garantindo:

- Valor de qualquer chave que contenha `senha`, `password`, `secret`, `token`, `webhook`, `authorization`, `cookie` ou `session` sai como `[REDACTED]`.
- **E-mail** sai mascarado: `l***@gmail.com`.
- **Texto digitado pelo usuário** (descrição, valor inválido) não é logado. Registramos *quais campos* falharam, não *o que* foi digitado.
- **Stack trace** entra só com os 5 primeiros frames (filtrar o irrelevante), e **só no arquivo**, nunca no Slack.

Mensagem pro usuário ≠ log: a tela diz "E-mail ou senha inválidos", e o log diz `motivo: senha_incorreta`.

## Correlation ID

- Nasce em `Application::handle()` como `req_` + 6 hex. Comandos CLI usam `cli_`.
- Se a requisição chegar com o header `X-Correlation-ID` (de um gateway ou outro serviço) e ele for seguro (`[A-Za-z0-9_-]{6,64}`), **ele é reaproveitado**.
- Volta no header de resposta `X-Correlation-ID` (aba **Network** do DevTools).
- Aparece na **tela de erro** ("Código do erro", com botão Copiar e data/hora) e é escrito no **Console** do navegador (`console.error`).
- Na investigação: o alerta chega no Slack com o `correlation_id` → `grep req_ccb8bf storage/logs/overdark.log` → você vê a requisição inteira.

## Destinos

```
LoggerInterface (PSR-3)
      │
StructuredLogger ── monta o LogRecord (contrato + LogContext + Sanitizer)
      │
      ├── JsonLinesFileHandler   storage/logs/overdark.log   (tudo ≥ LOG_LEVEL)
      └── SlackWebhookHandler    canal do Slack              (≥ LOG_SLACK_LEVEL, só se SLACK_WEBHOOK_URL existir)
```

- **Gravado para a máquina, exibido para a pessoa:** o arquivo guarda o dado bruto (`valor_centavos: 555`, exato e fácil de filtrar ou somar). No Slack e no `logs:tail`, todo campo `*_centavos` aparece em reais: `valor : R$ 5,55` (`Core\Logging\ExibicaoHumana`).
- **Um destino que falha nunca derruba a aplicação.** Se o Slack não responder em 3s, a requisição segue e os outros destinos recebem um `log_destino_falhou`.
- Formato Slack: título `🚨 CRITICAL · banco_indisponivel`, bloco `campo : valor` (igual ao slide) e rodapé com app, ambiente e horário.

## Configuração

| Variável | Padrão | Uso |
|---|---|---|
| `LOG_PATH` | `storage/logs/overdark.log` | Arquivo JSON Lines (relativo à raiz do projeto) |
| `LOG_LEVEL` | `debug` | Nível mínimo gravado no arquivo |
| `SLACK_WEBHOOK_URL` | vazio | Incoming Webhook. **Nunca versione.** Vazio = Slack desligado |
| `DB_LOCK_WAIT_TIMEOUT` | `3` | Segundos que uma gravação espera por registro travado (o MySQL usa 50 por padrão; em requisição web, falhar rápido é melhor) |
| `LOG_SLACK_LEVEL` | `error` | Nível mínimo enviado ao Slack |

Os testes forçam `LOG_PATH=storage/logs/testes.log` e `SLACK_WEBHOOK_URL` vazio (ver `phpunit.xml.dist`).

## Lendo os logs

```bash
# Formatado e colorido, acompanhando em tempo real (ideal para demo)
docker compose exec app bin/console logs:tail

# JSON bruto, filtrando com jq (no host)
tail -f storage/logs/overdark.log | jq .
jq 'select(.level == "error" or .level == "critical")' storage/logs/overdark.log
jq 'select(.correlation_id == "req_ccb8bf")' storage/logs/overdark.log
jq 'select(.event == "login_recusado") | {timestamp, motivo, email}' storage/logs/overdark.log

# Testar o webhook do Slack
docker compose exec app bin/console slack:testar
```

## Simulação de erro de produção (demo)

**Cenário:** a rotina em lote de **fechamento mensal** está rodando e travou as movimentações da cliente enquanto recalcula os totais. Ela tenta cadastrar uma compra, o banco espera `DB_LOCK_WAIT_TIMEOUT` (3s) pelo registro travado e desiste com `Lock wait timeout exceeded` (MySQL 1205).

É um erro **real de produção**: o código está correto, o ambiente de desenvolvimento não reproduz ("na minha máquina funciona"), só acontece no horário da rotina e só com quem está sendo processado. **Outros usuários continuam gravando normalmente.** Sem log com contexto, ninguém descobre. Com log, o `motivo: lock_wait_timeout` + o horário apontam direto para a rotina concorrente.

**Ligar/desligar:** em `src/Modules/Movimentacoes/Infrastructure/PdoMovimentacaoRepository.php`, método `salvar()`, bloco `🔥 SIMULAÇÃO DE ERRO DE PRODUÇÃO`:

```php
// $rotinaConcorrente = Demo\FechamentoMensalEmAndamento::travarMovimentacoesDo($usuarioId);   ← descomente
```

A classe `Infrastructure/Demo/FechamentoMensalEmAndamento` abre **outra conexão** com o banco e faz `SELECT ... FOR UPDATE` nas movimentações do usuário, como uma rotina de verdade faria. O lock é real, e o erro vem do próprio MySQL. Não precisa reiniciar nada.

> O teste `tests/Unit/Movimentacoes/SimulacaoDeErroTest.php` **falha se a linha estiver descomentada**, então a simulação nunca vai ligada para o repositório. O cenário completo, com lock real, é coberto por `tests/Feature/SimulacaoDeErroTest.php`.

### O que acontece com a simulação ligada

| Onde | O que aparece |
|---|---|
| **Tela da cliente** | Após ~3s: "Algo deu errado" + **Código do erro `req_xxxxxx`** (botão Copiar) + data/hora |
| **DevTools → Network** | `POST /movimentacoes` → **500** (~3s), header `X-Correlation-ID: req_xxxxxx` |
| **DevTools → Console** | `OverDark  Erro 500 em POST /movimentacoes · correlation_id: req_xxxxxx` |
| **Slack** | `🔴 ERROR · movimentacao_registro_falhou`: usuário, tipo, parcelas, valor, `motivo: lock_wait_timeout`, `integracao: MySQL`, `errno: 1205`, `retentavel: true`, `duracao_ms: ~3000`, mensagem do banco e arquivo:linha |
| **Arquivo** | O mesmo evento + `http_requisicao_concluida` (500), ligados pelo `correlation_id` |
| **Banco** | Nada gravado pela metade (transação desfeita) |

### Fluxo de atendimento (roteiro da palestra)

1. **Cliente:** "Tentei cadastrar uma compra no cartão e deu erro." Ela lê o código da tela: `req_290268`.
2. **Suporte**, por um destes caminhos:
   - busca o código no canal do Slack;
   - ou reproduz na conta/computador da cliente com o DevTools aberto: Network → `POST /movimentacoes 500` → header `X-Correlation-ID`, ou Console;
   - ou procura no Slack pelos alertas recentes com o `usuario_id` da cliente.
3. **O alerta responde sem perguntas:** quem (`usuario_id`), o quê (`registrar_movimentacao`, cartão, 10x, R$ 3.999,99), por quê (`lock_wait_timeout`: o registro estava travado), quanto tempo esperou (`duracao_ms: 3008`) e **o que fazer agora** (`retentavel: true` → "tente novamente em alguns minutos").
4. **Suporte → dev:** repassa o código. O dev cruza o horário do alerta com as rotinas agendadas e acha o fechamento mensal. Sem o log, o dev testaria na máquina dele, "funcionaria", e o chamado seria fechado como "não reproduzido".
5. **Resolução:** a rotina termina (na demo: comentar a linha) → a cliente tenta de novo → `compra_parcelada_registrada`. Correção definitiva: rodar a rotina fora do horário comercial ou processar em lotes menores.
6. **Slide:** *mais contexto → menos perguntas → investigação mais rápida.*

> Dica: use `APP_DEBUG=false` no `.env` durante a demo, pra plateia ver exatamente a tela da cliente.

## Roteiro de demonstração completo

| # | Ação | O que aparece |
|---|---|---|
| 1 | Terminal com `bin/console logs:tail` aberto | — |
| 2 | Login com senha errada | `login_recusado` · 401 · `motivo: senha_incorreta`. A tela não revela o motivo |
| 3 | Login correto | `usuario_autenticado` + `http_requisicao_concluida` com o mesmo `correlation_id` |
| 4 | Nova movimentação com valor inválido | `movimentacao_validacao_recusada` · 422 · `campos: ["valor"]`, sem o valor digitado |
| 5 | Compra no cartão em 10x | `compra_parcelada_registrada`: grupo, 10 ids, total, primeira e última competência |
| 6 | **Descomentar a simulação** e repetir a compra | ~3s de espera · tela com código · Network 500 · Console · **Slack `movimentacao_registro_falhou` (`lock_wait_timeout`)** |
| 7 | Seguir o fluxo de atendimento acima | Do código até a linha do erro, sem perguntar nada à cliente |
| 8 | Comentar a linha e tentar de novo | `compra_parcelada_registrada` (resolvido) |
| 9 | Extra: `docker compose stop db`, recarregar e depois `docker compose start db` | `banco_indisponivel` (**critical**) no Slack |

## Adicionando um evento novo (checklist)

- [ ] Nome no passado e em `snake_case`, descrevendo o acontecimento (`pedido_emitido`, não `emitir` nem `erro`)
- [ ] `operacao` e `status` sempre informados
- [ ] Ids de domínio suficientes pra investigar **sem abrir o banco**
- [ ] `motivo` em toda recusa/falha. `integracao` quando envolver serviço externo
- [ ] Nível certo (recusa esperada = warning, falha do sistema = error)
- [ ] Nenhum dado pessoal/segredo além do necessário
- [ ] Falha com contexto de negócio: logada no caso de uso + exceção `AlreadyLogged` (sem duplicar no kernel)
- [ ] Teste em `tests/Feature/LogsTest.php` (use `$this->logs->unico('evento')`)
- [ ] Linha no catálogo acima

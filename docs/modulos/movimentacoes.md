# Módulo: Movimentações

## Propósito

Registrar e consultar os lançamentos do mês (receitas, gastos e compras no cartão, à vista ou parceladas) e mostrar o consolidado: receita, gastos, saldo e situação do fluxo. Também é a fonte dos números do Dashboard.

## Status

**Funcional:** cadastro, listagem por mês e exclusão, persistidos no MySQL. Edição ainda não existe.

## Rotas

| Método | Caminho | Grupo | Ação | Descrição |
|---|---|---|---|---|
| GET | `/movimentacoes[?competencia=AAAA-MM]` | Autenticado | `index` | Painel do mês. Sem `competencia` (ou inválida), usa o mês atual |
| POST | `/movimentacoes` | Autenticado | `store` | Campos: `tipo`, `data` (AAAA-MM-DD), `descricao`, `valor`, `parcelas`, `valor_parcela`, `competencia_atual`. Sucesso → 302 para o mês da (1ª) movimentação |
| POST | `/movimentacoes/excluir` | Autenticado | `destroy` | Campos: `id`, `competencia_atual`. Id inválido → 400. De outro usuário ou inexistente → 404 |

## Estrutura

| Classe | Camada | Responsabilidade |
|---|---|---|
| `Domain\TipoMovimentacao` | Domain | Enum `receita`/`gasto`/`cartao`, rótulos, `isEntrada()`, `permiteParcelamento()` |
| `Domain\Movimentacao` | Domain | Entidade (id opcional). Valida descrição, valor > 0 e parcela só no cartão. `valorNoFluxo()`, `competencia()` |
| `Domain\Parcelamento` | Domain | Calcula `valores()` (resto na 1ª) e `datas()` (mesmo dia nos meses seguintes, ajustando o fim de mês) |
| `Domain\Parcela` | Domain | "N de M" + grupo da compra. `rotulo()` → `3/10` |
| `Domain\ConsolidadoMensal` | Domain | Receitas, gastos, cartão, quantidade, variação. `gastosTotais()`, `saldo()`, `situacao()`, `comparadoCom()` |
| `Domain\SituacaoFluxo` | Domain | Positivo/Negativo, com rótulo, descrição e ícone |
| `Domain\MovimentacaoRepository` | Domain | Contrato (sempre por `usuarioId`) |
| `Domain\MovimentacaoNaoEncontrada` | Domain | Exceção → 404 |
| `Application\RegistrarMovimentacao` | Application | Valida `NovaMovimentacao`, gera 1 ou N movimentações e salva |
| `Application\ExcluirMovimentacao` | Application | Exclui a movimentação ou o grupo inteiro de parcelas |
| `Application\MovimentacaoService` | Application | `painel()`, `consolidado()`, `historico()`, `competenciaAtual()`. **API pública para outros módulos** |
| `Application\NovaMovimentacao` | Application | DTO com os campos brutos do formulário |
| `Application\PainelMovimentacoes` | Application | DTO da tela |
| `Infrastructure\PdoMovimentacaoRepository` | Infrastructure | MySQL: insert em transação, agregação com `GROUP BY DATE_FORMAT(data, '%Y-%m')` |
| `Http\MovimentacaoController` | Http | index/store/destroy com Post/Redirect/Get + flash |

## Banco

Tabela `movimentacoes` (migration `0002`):

| Coluna | Tipo | Observação |
|---|---|---|
| `usuario_id` | FK → `usuarios` | `ON DELETE CASCADE` |
| `tipo` | `VARCHAR(20)` | `CHECK IN ('receita','gasto','cartao')` |
| `descricao` | `VARCHAR(160)` | |
| `valor_centavos` | `BIGINT` | `CHECK > 0`. Numa parcela, é o valor **da parcela** |
| `data` | `DATE` | Define a competência |
| `parcela_numero`, `parcelas_total`, `grupo_parcelamento` | nulos quando não é parcela | O grupo é um hex de 32 caracteres |

Índices: `(usuario_id, data)` e `(grupo_parcelamento)`.

## Regras de negócio

`M1` a `M18`. Ver [regras-de-negocio.md](../regras-de-negocio.md#movimentações).

## Dependências

- Core: `Clock`, `Session`, `ValidationException`, `HttpException`.
- Shared: `Money`, `MoneyParser`, `Competencia`, `Formatter`, `UsuarioAutenticado`.
- Consumido por: **Dashboard** (via `MovimentacaoService`).
- Front: `css/components.css`, `css/pages/movimentacoes.css`, `js/pages/movimentacoes.js` (`modal.js`, `confirm.js`).

## Logs emitidos

| Evento | Nível | Onde |
|---|---|---|
| `movimentacao_registrada` | info | `RegistrarMovimentacao` |
| `compra_parcelada_registrada` | info | `RegistrarMovimentacao` |
| `movimentacao_validacao_recusada` (422, `campos`) | warning | `RegistrarMovimentacao` |
| `movimentacao_registro_falhou` (500, `motivo`/`errno`/`retentavel` via `SqlErrorReason`, `duracao_ms`, `integracao: MySQL`) | **error** → Slack | `RegistrarMovimentacao` (lança `FalhaAoRegistrarMovimentacao`, que é `AlreadyLogged`) |
| `movimentacao_excluida` | info | `ExcluirMovimentacao` |
| `movimentacao_exclusao_negada` (404) | warning | `ExcluirMovimentacao` |

Descrições e valores digitados **não** vão para o log. Ver [observabilidade.md](../observabilidade.md).

**Simulação de erro de produção** (demo): linha comentada `$rotinaConcorrente = Demo\FechamentoMensalEmAndamento::...` em `PdoMovimentacaoRepository::salvar()` (lock real concorrente → `lock_wait_timeout`). Ver [observabilidade.md](../observabilidade.md#simulação-de-erro-de-produção-demo).

## Testes

| Arquivo | Cobre |
|---|---|
| `tests/Unit/Movimentacoes/MovimentacaoTest.php` | Entidade, fluxo, consolidado, variação |
| `tests/Unit/Movimentacoes/ParcelamentoTest.php` | Divisão com resto, valor informado, datas com fim de mês e virada de ano |
| `tests/Unit/Movimentacoes/CasosDeUsoTest.php` | Registrar (simples, parcelado, à vista, validações), excluir grupo, isolamento, histórico, painel |
| `tests/Feature/SimulacaoDeErroTest.php` | Lock real concorrente: tela com código, um único log com contexto, transação desfeita, outra usuária não afetada, nova tentativa funciona |
| `tests/Unit/Movimentacoes/SimulacaoDeErroTest.php` | Garante que a simulação está **comentada** no código versionado |
| `tests/Feature/MovimentacoesTest.php` | De ponta a ponta com MySQL: tela zerada, cadastro, 10 parcelas no banco, erro reabrindo o modal, exclusão, isolamento, CSRF |

## Pendências

- [ ] Editar movimentação (M17)
- [ ] Meta de saldo (M18)
- [ ] Dia de fechamento da fatura do cartão (M16)
- [ ] Paginação/busca quando o volume crescer

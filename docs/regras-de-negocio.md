# Regras de negócio

Fonte única das regras do OverDark. Cada regra aponta para a classe que a implementa e para o teste que a garante.

> Legenda: ✅ implementada e testada · 🟡 implementada, mas é uma decisão provisória que vale revisar · ⏳ ainda não implementada

## Dinheiro

| # | Regra | Onde |
|---|---|---|
| D1 ✅ | Valores monetários são guardados em **centavos (inteiro)**, nunca float. No banco, `BIGINT` | `Shared/Domain/Money` · `MoneyTest` |
| D2 ✅ | Exibição em pt-BR: `R$ 3.200` quando não há centavos, `R$ 1.234,56` quando há | `Shared/Formatting/Formatter` · `FormatterTest` |
| D3 ✅ | Valores com sinal: `+ R$ 860`, `- R$ 120`, `R$ 0` | `Formatter::signedMoney` |
| D4 ✅ | Percentual sobre base zero é 0% (sem divisão por zero) | `Money::percentOf` |
| D5 ✅ | Entrada do usuário aceita `1.234,56`, `1234,56`, `R$ 1.234,56`, `1234.56` e `1234`. `1.234` sem vírgula é lido como milhar (R$ 1.234). Valores negativos ou mal formatados são recusados | `Shared/Formatting/MoneyParser` · `MoneyParserTest` |

## Usuários e acesso

| # | Regra | Onde |
|---|---|---|
| A1 ✅ | Login por e-mail + senha. E-mail normalizado (minúsculas, sem espaços) | `AutenticarUsuario` · `Unit/Auth/AuthTest` |
| A2 ✅ | E-mail inexistente e senha errada devolvem **a mesma mensagem** ("E-mail ou senha inválidos."), sem revelar quais e-mails existem. O motivo real fica em `CredenciaisInvalidas::$motivo`, para log | `CredenciaisInvalidas` |
| A3 ✅ | Senhas guardadas só como hash (`password_hash`). Mínimo de 8 caracteres. E-mail único | `CriarUsuario` |
| A4 ✅ | Páginas internas exigem sessão. Sem ela, redireciona para `/login`. Quem está logado não vê o login | `Autenticado`, `Visitante` · `Feature/AuthTest` |
| A5 ✅ | Todo formulário POST exige token CSRF válido. Sem ele, a resposta é 419 | `VerifyCsrfToken` |
| A6 ✅ | **Cada usuário só vê e altera os próprios dados.** Recurso de outro usuário responde 404 | Repositórios filtram por `usuario_id` · `Feature/MovimentacoesTest` |
| A7 🟡 | Usuários são criados apenas pelo `bin/console usuario:criar` (não há cadastro público) | `bin/console` |

## Movimentações

| # | Regra | Onde |
|---|---|---|
| M1 ✅ | Tipos: **Receita**, **Gasto** e **Cartão de crédito** | `TipoMovimentacao` |
| M2 ✅ | Receita **entra** no caixa. Gasto e cartão **saem** | `TipoMovimentacao::isEntrada`, `Movimentacao::valorNoFluxo` |
| M3 ✅ | Obrigatórios: tipo válido, data válida, descrição (até 160 caracteres) e valor > 0. Todos os erros são devolvidos juntos, um por campo | `RegistrarMovimentacao` · `CasosDeUsoTest` |
| M4 ✅ | **Somente cartão de crédito pode ser parcelado.** Para outros tipos, o campo de parcelas é ignorado | `TipoMovimentacao::permiteParcelamento` |
| M5 ✅ | Cartão aceita **à vista (1x)** ou **2, 3, 4, 6 ou 10 parcelas** | `Parcelamento::OPCOES` |
| M6 ✅ | No parcelamento, o campo *Valor* é o **total da compra**. Sem valor de parcela informado, o total é dividido igualmente e **os centavos que sobram vão para a 1ª parcela**, então a soma fecha exatamente com o total | `Parcelamento::valores` · `ParcelamentoTest` |
| M7 ✅ | Com valor de parcela informado (ex.: compra com juros), todas as parcelas têm esse valor | `Parcelamento::valores` |
| M8 ✅ | **Cada parcela é lançada no seu mês:** a 1ª na data da compra, as demais no mesmo dia dos meses seguintes. Em meses mais curtos, cai no último dia (31/01 → 28/02) | `Parcelamento::datas`, `RegistrarMovimentacao` |
| M9 ✅ | As parcelas de uma compra compartilham um `grupo_parcelamento` e são gravadas em transação (todas ou nenhuma) | `PdoMovimentacaoRepository::salvar` |
| M10 ✅ | **Excluir uma parcela exclui a compra inteira** (todas as parcelas). A tela pede confirmação avisando isso | `ExcluirMovimentacao` |
| M11 ✅ | Lançamentos são agrupados por **competência** (mês/ano da data). Sem competência informada, vale o **mês atual** | `MovimentacaoService::painel` |
| M12 ✅ | O filtro de mês oferece: meses com lançamentos, o mês atual e o mês selecionado, do mais recente ao mais antigo | `MovimentacaoService` |
| M13 ✅ | **Saldo do mês = receitas − (gastos + cartão)** | `ConsolidadoMensal::saldo` |
| M14 ✅ | Situação: **Positivo** se saldo ≥ 0, **Negativo** se < 0 | `SituacaoFluxo::doSaldo` |
| M15 ✅ | Variação da receita = (receita do mês − receita do mês anterior) ÷ receita do mês anterior. Se o mês anterior não teve receita, a variação não é exibida | `ConsolidadoMensal::comparadoCom` |
| M16 🟡 | A parcela conta no mês da própria data, não no mês da fatura (não há dia de fechamento do cartão) | — |
| M17 ⏳ | Editar movimentação | — |
| M18 ⏳ | Meta de saldo mensal | — |

## Dashboard

| # | Regra | Onde |
|---|---|---|
| P1 ✅ | Os números vêm das movimentações do usuário, **sempre do mês atual** | `DashboardService` · `DashboardServiceTest` |
| P2 ✅ | Cards: Receitas, Gastos (gastos + cartão), Saldo e Cartão de crédito (parcelas/compras do mês) | `IndicadoresDoMes` |
| P3 ✅ | Gráfico: saldo dos **últimos 6 meses**, incluindo o atual. Meses sem lançamento aparecem como R$ 0 | `DashboardService::MESES_NO_GRAFICO` |
| P4 ✅ | Lista lateral: saldo de **cada mês do ano corrente** (jan–dez), inclusive meses futuros que já têm parcelas | `DashboardService` |
| P5 ⏳ | Card de investimentos (volta quando o módulo Investimentos tiver persistência) | — |

## Investimentos (dados de demonstração)

| # | Regra | Onde |
|---|---|---|
| I1 ✅ | Classes de ativo: **Reserva, Renda fixa, Fundos, Ações, Cripto** | `TipoInvestimento` |
| I2 ✅ | Nome obrigatório. Valor investido > 0. Valor atual não pode ser negativo | `Investimento::__construct` |
| I3 ✅ | Valor atual é opcional. Se não for informado, é igual ao investido | `Investimento` · `InvestimentoTest` |
| I4 ✅ | Resultado = valor atual − valor investido | `Investimento::resultado` |
| I5 ✅ | Valor atual da carteira = soma das alocações por classe | `ResumoCarteira::valorAtual` |
| I6 ✅ | Lucro = valor atual − total investido. Rentabilidade (%) = lucro ÷ total investido × 100 | `ResumoCarteira` |
| I7 ✅ | "Aportes distribuídos em N classes" conta só as classes com saldo > 0 | `ResumoCarteira::quantidadeClasses` |
| I8 ⏳ | Persistência, cadastro e exclusão (hoje a tela mostra fixtures do protótipo) | — |

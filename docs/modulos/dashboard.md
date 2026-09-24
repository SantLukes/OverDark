# Módulo: Dashboard

## Propósito

Visão geral do **mês atual**: receitas, gastos, saldo, quanto foi no cartão, a evolução do saldo nos últimos 6 meses e o saldo de cada mês do ano.

## Status

**Funcional.** Todos os números vêm das movimentações do usuário logado. Não há dados próprios.

## Rotas

| Método | Caminho | Grupo | Ação |
|---|---|---|---|
| GET | `/dashboard` | Autenticado | `DashboardController::index` |

## Estrutura

| Classe | Camada | Responsabilidade |
|---|---|---|
| `Domain\IndicadoresDoMes` | Domain | Receitas, gastos (gastos + cartão) e cartão do mês. Calcula `saldo()` |
| `Application\DashboardService` | Application | Consome `MovimentacaoService`: consolidado do mês, histórico dos últimos 6 meses e do ano |
| `Application\PainelDashboard` | Application | DTO da tela |
| `Http\DashboardController` | Http | `GET /dashboard` |
| `Views/index.php` | View | 4 cards, gráfico (`data-chart`) e lista mensal (saldos negativos em vermelho) |

Não há `Infrastructure/`: o módulo não acessa o banco diretamente.

## Regras de negócio

`P1` a `P5`. Ver [regras-de-negocio.md](../regras-de-negocio.md#dashboard).

## Dependências

- **Módulo Movimentações**, via `MovimentacaoService`: `competenciaAtual()`, `consolidado()`, `historico()`.
- Shared: `Competencia`, `Money`, `PontoMensal`.
- Front: `css/pages/dashboard.css`, `js/pages/dashboard.js` (`components/line-chart.js`). Layout com `charts: true`.

## Testes

| Arquivo | Cobre |
|---|---|
| `tests/Unit/Dashboard/DashboardServiceTest.php` | Indicadores, isolamento por usuário, rótulos do gráfico, parcela futura no resumo anual |
| `tests/Feature/DashboardTest.php` | Tela zerada e valores após cadastrar movimentações |

## Pendências

- [ ] Card de investimentos quando o módulo Investimentos for persistido (P5)
- [ ] Escolher outro mês além do atual

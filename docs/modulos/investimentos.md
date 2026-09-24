# Módulo: Investimentos

## Propósito

Acompanhar a carteira: total investido, valor atual, lucro/prejuízo, rentabilidade, composição por classe de ativo, evolução do patrimônio e a lista de posições.

## Status

**Mock (dados em memória).** A rota exige login, mas os números são as fixtures do protótipo e são os mesmos para qualquer usuário. O modal de cadastro ainda não envia dados. Por isso o Dashboard ainda não mostra o card de investimentos.

## Rotas

| Método | Caminho | Ação | Descrição |
|---|---|---|---|
| GET | `/investimentos` | `InvestimentoController::index` | Painel da carteira (grupo Autenticado) |

## Estrutura

| Classe | Camada | Responsabilidade |
|---|---|---|
| `Domain\TipoInvestimento` | Domain | Enum `reserva`/`renda-fixa`/`fundos`/`acoes`/`cripto` + rótulos |
| `Domain\Investimento` | Domain | Posição. Valor atual opcional (assume o investido). `resultado()` e `rentabilidade()` |
| `Domain\Alocacao` | Domain | Valor atual de uma classe de ativo |
| `Domain\ResumoCarteira` | Domain | Total investido + composição. Calcula `valorAtual()`, `lucro()`, `rentabilidade()`, `quantidadeClasses()` |
| `Domain\InvestimentoRepository` | Domain | Contrato: `listar()`, `resumo()`, `evolucaoPatrimonio()` |
| `Application\InvestimentoService` | Application | Monta o `PainelInvestimentos` |
| `Application\PainelInvestimentos` | Application | DTO da tela |
| `Infrastructure\InMemoryInvestimentoRepository` | Infrastructure | Fixtures |
| `Http\InvestimentoController` | Http | `GET /investimentos` |

## Regras de negócio

`I1` a `I8`. Ver [regras-de-negocio.md](../regras-de-negocio.md#investimentos).

## Dependências

- `Shared\Domain\Money`, `Shared\Domain\PontoMensal`.
- Front: `css/components.css`, `css/pages/investimentos.css` (cores por classe de ativo), `js/pages/investimentos.js` (modal + gráfico). Layout com `charts: true`.
- `config/services.php`: `InvestimentoRepository` → `InMemoryInvestimentoRepository`.

## Testes

| Arquivo | Cobre |
|---|---|
| `tests/Unit/Investimentos/InvestimentoTest.php` | Resultado, valor atual opcional, validações, resumo da carteira |
| `tests/Feature/RoutesTest.php` | Página renderiza para usuário logado com os totais de demonstração |

## Pendências

- [ ] Migration `investimentos` + `PdoInvestimentoRepository` (filtrando por `usuario_id`)
- [ ] `POST /investimentos` (cadastro pelo modal)
- [ ] Card de total investido no Dashboard
- [ ] Editar/excluir e atualização do valor atual
- [ ] Composição e evolução calculadas a partir das posições (hoje são fixtures)

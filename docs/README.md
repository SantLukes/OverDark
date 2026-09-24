# Documentação do OverDark

Índice da documentação técnica. Comece pelo [README principal](../README.md) se ainda não rodou o projeto.

## Visão geral

| Documento | Quando ler |
|---|---|
| [arquitetura.md](arquitetura.md) | Para entender como uma requisição vira uma página e onde cada tipo de código deve morar |
| [regras-de-negocio.md](regras-de-negocio.md) | Antes de mexer em qualquer cálculo financeiro |
| [frontend.md](frontend.md) | Antes de criar/alterar telas, CSS ou JS |
| [observabilidade.md](observabilidade.md) | **Contrato de log**, catálogo de eventos, Slack, correlation_id, roteiro de demo |
| [infraestrutura.md](infraestrutura.md) | Docker, nginx, banco, variáveis de ambiente |
| [roadmap.md](roadmap.md) | O que ainda é mock e o que vem a seguir |

## Módulos

Cada módulo de negócio tem uma ficha em [`modulos/`](modulos/). A ficha diz o que o módulo faz, quais são as rotas, as classes, as regras e os pontos pendentes.

| Módulo | Ficha |
|---|---|
| Auth | [modulos/auth.md](modulos/auth.md) |
| Dashboard | [modulos/dashboard.md](modulos/dashboard.md) |
| Movimentações | [modulos/movimentacoes.md](modulos/movimentacoes.md) |
| Investimentos | [modulos/investimentos.md](modulos/investimentos.md) |

Criou um módulo novo? Copie [`modulos/_TEMPLATE.md`](modulos/_TEMPLATE.md) e siga o [guia de criação de módulo](guias/criando-um-modulo.md).

## Decisões de arquitetura (ADR)

Registros curtos do **porquê** das decisões. Ver [`adr/`](adr/README.md).

## Convenções desta pasta

- Escrita em português, direta, orientada a quem vai manter o código (humano ou IA).
- Um assunto por arquivo. Links relativos entre documentos.
- **Documentação faz parte da entrega:** quem altera rota, regra ou estrutura de um módulo atualiza a ficha dele no mesmo PR.

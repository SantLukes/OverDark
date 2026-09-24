# Módulo: <Nome>

> Copie este arquivo para `docs/modulos/<nome-do-modulo>.md` ao criar um módulo e mantenha-o atualizado a cada mudança.

## Propósito

Uma ou duas frases: que problema do usuário este módulo resolve.

## Status

`Estável` | `Em desenvolvimento` | `Mock (dados em memória)`, com uma frase sobre o que falta.

## Rotas

| Método | Caminho | Ação | Descrição |
|---|---|---|---|
| GET | `/exemplo` | `ExemploController::index` | ... |

## Estrutura

```
src/Modules/<Nome>/
├── Domain/
├── Application/
├── Infrastructure/
├── Http/
└── Views/
```

| Classe | Camada | Responsabilidade |
|---|---|---|
| `...` | Domain | ... |

## Regras de negócio

Liste as regras com o código usado em [regras-de-negocio.md](../regras-de-negocio.md) (ex.: `X1`). Descreva-as lá, não aqui.

## Dependências

- Outros módulos consumidos (sempre via serviço):
- Assets de front (`public/assets/css/pages/...`, `public/assets/js/pages/...`):
- Ligações em `config/services.php`:

## Logs emitidos

| Evento | Nível | Onde |
|---|---|---|
| `recurso_acao_realizada` | info | `Application\...` |

Siga o contrato em [observabilidade.md](../observabilidade.md) e adicione os eventos ao catálogo de lá.

## Testes

| Arquivo | Cobre |
|---|---|
| `tests/Unit/<Nome>/...` | ... |

## Pendências / próximos passos

- [ ] ...

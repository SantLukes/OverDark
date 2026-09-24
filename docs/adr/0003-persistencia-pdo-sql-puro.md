# 0003 — Persistência com PDO e migrations em SQL puro

**Status:** Aceito · 2026-09-23

## Contexto

Login, Movimentações e Dashboard precisavam sair dos dados fictícios e gravar no MySQL que já existia no Docker. O projeto não usa framework (ADR 0001) e não tem dependências de produção.

Opções consideradas:

1. **ORM (Doctrine/Eloquent standalone):** mapeamento automático, mas traz dependência pesada, configuração e "mágica" para duas tabelas.
2. **Query builder:** menos pesado que um ORM, mas ainda é uma dependência e uma abstração a aprender.
3. **PDO + repositórios escritos à mão + migrations `.sql`:** o SQL fica explícito e cada repositório implementa a interface do domínio.

## Decisão

Opção 3:

- `Core\Database\ConnectionFactory` cria o PDO (`ERRMODE_EXCEPTION`, prepared statements reais, `utf8mb4`).
- Cada módulo tem `Infrastructure/Pdo<Entidade>Repository` implementando a interface de `Domain/`. Hidratação manual para entidades imutáveis.
- Schema em `database/migrations/NNNN_*.sql`, aplicado em ordem por `bin/console migrate` e registrado na tabela `migrations`.
- Testes de feature rodam contra um banco MySQL real e separado (`overdark_test`). Testes unitários usam repositórios em memória (`tests/Support`).

## Consequências

- ✅ SQL visível e revisável. As agregações (`SUM ... GROUP BY mês`) ficam no banco, que é onde rodam melhor.
- ✅ Zero dependências de produção. Trocar para outro banco = outra implementação da interface.
- ✅ Testes de feature pegam erros reais de SQL, constraint e charset.
- ⚠️ Mapeamento linha → objeto é manual. Se o número de tabelas crescer muito, reavaliar um query builder.
- ⚠️ Migrations não têm "down". Correções são novas migrations (sempre para frente).

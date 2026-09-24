# 0002 — Dinheiro como inteiro em centavos

**Status:** Aceito · 2026-09-23

## Contexto

O OverDark é um sistema financeiro: soma lançamentos, calcula saldos, divide parcelas e mede rentabilidade. Números de ponto flutuante não representam decimais com exatidão (`0.1 + 0.2 = 0.30000000000000004`), e o erro se acumula em somas longas.

## Decisão

- Todo valor monetário é um `OverDark\Shared\Domain\Money`, imutável, com a quantia em **centavos** (`int`).
- Criação por `Money::fromReais(3200)` ou `Money::fromReais('1234.56')` (string validada), nunca a partir de float.
- `toFloat()` existe **somente** para apresentação (ex.: série de gráfico).
- Formatação pt-BR fica isolada em `Shared\Formatting\Formatter`.
- No banco, colunas monetárias serão `BIGINT` em centavos.

## Consequências

- ✅ Somas e subtrações são exatas.
- ✅ As regras ficam explícitas: `plus`, `minus`, `percentOf`, `isNegative`...
- ⚠️ Divisões (ex.: parcelas) precisam de uma política de arredondamento explícita. Hoje: arredonda para baixo (ver regra M6).
- ⚠️ Inputs do usuário ("R$ 1.234,56") precisarão de um parser dedicado quando o cadastro for implementado.

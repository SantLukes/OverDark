# 0001 — Arquitetura modular sem framework

**Status:** Aceito · 2026-09-23

## Contexto

O OverDark nasceu como um conjunto de páginas PHP com HTML e dados fixos, um `index.php` roteando com `if`s e CSS/JS duplicados em cada tela. Para evoluir (persistência, cadastro, autenticação) era preciso separar responsabilidades, sem transformar um projeto pessoal pequeno numa aplicação pesada.

Opções consideradas:

1. **Adotar Laravel/Symfony:** resolve tudo, mas exige reescrever do zero e traz uma curva e um peso desproporcionais ao tamanho atual.
2. **Manter scripts PHP soltos:** zero custo agora, mas regra de negócio continuaria espalhada no HTML.
3. **Núcleo próprio mínimo + módulos por funcionalidade:** poucas centenas de linhas de infraestrutura, com o mesmo modelo mental dos frameworks.

## Decisão

Opção 3:

- `src/Core`: front controller, router de rotas estáticas, container de DI com factories, views em PHP com layout, leitura de `.env`.
- `src/Modules/<Modulo>/{Domain,Application,Infrastructure,Http,Views}`: cada funcionalidade é autocontida.
- Composer apenas para autoload PSR-4 e ferramentas de desenvolvimento (PHPUnit, PHPStan). **Zero dependências de produção.**

## Consequências

- ✅ Regra de negócio testável isoladamente. A persistência pode ser trocada sem tocar controllers e views.
- ✅ Adicionar uma funcionalidade = adicionar uma pasta + registrar rota/serviços (ver [guia](../guias/criando-um-modulo.md)).
- ✅ Migrar para um framework no futuro é mecânico: Domain e Application não dependem do Core.
- ⚠️ Recursos que um framework daria de graça (rotas com parâmetros, middleware, sessão, CSRF, validação de formulário) precisam ser escritos quando forem necessários. Se a lista crescer demais, reavaliar a opção 1.

# Frontend

HTML renderizado no servidor (PHP), estilizado com Bootstrap 5.3 + CSS próprio, com JavaScript em **módulos ES** sem build step.

## Organização

```
public/assets/
├── css/
│   ├── base.css            1. Tokens (:root), reset, header, navegação, menu do usuário, .page-shell
│   ├── components.css      2. Componentes de telas de cadastro: toolbar, botões, cards, painel, tabela, modal, formulário
│   └── pages/<pagina>.css  3. Somente o que é exclusivo da página (badges por tipo, grids, gráfico)
├── js/
│   ├── components/         Comportamentos reutilizáveis, ativados por data-attributes
│   │   ├── user-menu.js    Dropdown do avatar (carregado em todas as páginas pelo layout)
│   │   ├── modal.js        Abrir/fechar modais (inclusive abrir sozinho após erro de validação)
│   │   ├── confirm.js      Modal de confirmação para ações destrutivas (sem window.confirm)
│   │   ├── money-mask.js   Máscara de moeda: só dígitos, preenche da direita para a esquerda
│   │   ├── form-validation.js  Validação de formulários no navegador (data-attributes)
│   │   └── line-chart.js   Gráfico de linha no tema do app (Chart.js)
│   └── pages/<pagina>.js   Ponto de entrada da página: importa os componentes que ela usa
└── img/
```

### Ordem de carregamento do CSS

`bootstrap` → `base.css` → CSS extras declarados pela view (`components.css`, `pages/x.css`).
A página vem por último para poder sobrescrever componentes (ex.: `min-width` diferente do `.type-badge` em cada tela).

| Página | CSS carregado |
|---|---|
| Login | `base.css` + `pages/login.css` (layout `guest`, sem Bootstrap) |
| Dashboard | `base.css` + `pages/dashboard.css` |
| Movimentações | `base.css` + `components.css` + `pages/movimentacoes.css` |
| Investimentos | `base.css` + `components.css` + `pages/investimentos.css` |

## Identidade visual: "lilás na escuridão"

Inspirada na logo original (`public/assets/img/logo1.jpg`): **pássaro preto sobre lilás**, com um lilás mais vivo pra dar vida ao tema escuro. Os princípios:

- **Fundo quase preto** com fundo violeta, e superfícies em carvão. O "sombrio" do OverDark.
- **Lilás é a cor da marca e da ação** (botão principal, selo da logo, item ativo, gráfico), com brilho sutil (`--brand-glow`). O botão principal usa **texto preto sobre lilás**, o mesmo contraste da logo.
- **O dado é o protagonista:** títulos e valores em off-white, não em roxo.
- **Cor com significado:** verde só para entrada/lucro, vermelho só para saída/erro. Ambos dessaturados.
- **Cores de fundo nunca saturadas:** o lilás vivo aparece em pontos de destaque, não em áreas grandes.
- **Tipografia:** Manrope (Google Fonts), com `font-variant-numeric: tabular-nums` nos valores para alinharem em coluna.

### Logo

- `img/logo-mark.png`: pássaro recortado rente (256×256, fundo transparente). É o que se usa na interface.
- Sempre dentro do **selo lilás** (`.brand-mark`), porque o pássaro é preto e sumiria no fundo escuro. No login, o mesmo pássaro aparece gigante e translúcido atrás do slogan (`.login-bird-watermark`, via `mask-image`):

```html
<span class="brand-mark"><img src="/assets/img/logo-mark.png" alt=""></span>
<span class="brand-name">Over<span>Dark</span></span>
```

- `img/logo1.jpg` (original com fundo) e `img/logo2.png` (original transparente) ficam como referência.

## Design tokens (`base.css`)

| Token | Valor | Uso |
|---|---|---|
| `--bg-main` | `#0c0a10` | Fundo da página |
| `--bg-surface` / `--bg-surface-2` | `#15121b` / `#1c1824` | Cards e painéis / modal, dropdown |
| `--bg-input` | `#100e15` | Inputs, linhas internas |
| `--brand` / `--brand-light` / `--brand-deep` | `#b196f0` / `#cbb6fa` / `#8b6fd6` | Lilás da marca: selo, botão principal, item ativo, linha do gráfico |
| `--brand-glow` | lilás a 35% | Brilho do selo, do botão e do indicador ativo |
| `--brand-tint` | lilás a 14% | Fundo de item ativo, badge "cartão", anel de foco de input |
| `--brand-ink` | `#0c0a10` | Texto sobre lilás |
| `--text-main` / `--text-muted` / `--text-subtle` | `#ece8f2` / `#9a91ad` / `#6b6478` | Texto principal / secundário / terciário |
| `--success` / `--danger` / `--danger-soft` | `#6fbf98` / `#d9707c` / `#e39aa3` | Entrada, lucro / saída, erro |
| `--border` / `--border-strong` | lilás a 14% / 28% | Bordas |
| `--radius` | `16px` | Cards e painéis |

Cores de classe de ativo (`--reserve`, `--fixed`, `--funds`, `--stocks`, `--crypto`) ficam em `pages/investimentos.css`, também dessaturadas. As cores do gráfico ficam em `components/line-chart.js` (`COLORS`) e espelham os tokens.

## Componentes JS e contratos de marcação

### Modal (`components/modal.js`)

```html
<button data-modal-open="meuModal">Abrir</button>

<div class="app-modal-backdrop" data-modal-backdrop="meuModal" hidden></div>
<div class="app-modal" id="meuModal" role="dialog" aria-modal="true" hidden>
  <div class="app-modal-card">
    <button data-modal-close>×</button>
  </div>
</div>
```

Fecha com o botão `data-modal-close`, com **Esc** ou com **clique fora do card**. Devolve o foco para o botão que abriu.
Com `data-modal-autoopen` no `.app-modal`, ele já abre ao carregar a página. É o que acontece quando o formulário volta do servidor com erros.

### Confirmação (`components/confirm.js`)

```html
<form method="post" action="/movimentacoes/excluir"
      data-confirm="A movimentação &quot;Mercado&quot; será removida."
      data-confirm-title="Excluir movimentação?"
      data-confirm-button="Excluir">...</form>
```

Abre um **modal no visual do sistema** (o sistema não usa `alert`/`confirm` nativos), com ícone de alerta, título, mensagem e os botões **Cancelar** e **Excluir** (`.danger-button`). O foco começa em Cancelar, que é a opção segura. Esc, Cancelar ou clique fora fecham sem enviar. Confirmar envia o formulário original.

### Máscara de moeda (`components/money-mask.js`)

```html
<input name="valor" data-mask="money" data-rule="money" required>
```

Aceita **somente dígitos**: letras e símbolos são bloqueados na digitação e removidos ao colar. Preenche da direita para a esquerda, como em apps de banco: `3` → `0,03`, `399999` → `3.999,99`. O valor enviado (`1.234,56`) é aceito pelo `MoneyParser` do backend. Use sempre junto com `data-rule="money"` (validação de obrigatório e > 0).

> Os nomes são `app-modal*` de propósito: `.modal` e `.modal-backdrop` já são classes do Bootstrap.

### Menu do usuário (`components/user-menu.js`)

`[data-user-menu]` > `[data-user-menu-toggle]` + `[data-user-menu-dropdown]`. Fecha com clique fora ou Esc.

### Gráfico de linha (`components/line-chart.js`)

O servidor serializa a série no canvas, e o JS só desenha:

```php
<canvas id="x" data-chart="<?= $this->json(PontoMensal::paraGrafico($serie)) ?>"></canvas>
```

```js
import { renderLineChart } from '../components/line-chart.js';
renderLineChart(document.getElementById('x'), { label: 'Saldo', currencyTicks: true });
```

A view precisa passar `'charts' => true` ao layout para carregar o Chart.js.

### Específicos de Movimentações (`pages/movimentacoes.js`)

- `select[data-autosubmit]`: envia o formulário ao trocar a opção (filtro de competência).
- `select[data-installments-toggle="idDoBloco"]`: mostra o bloco de parcelas quando a opção selecionada tem `data-parcelavel`. Hoje isso vale só para Cartão de crédito, e vem de `TipoMovimentacao::permiteParcelamento()`. Elementos com `data-installments-only` (ex.: a dica "(total da compra)") seguem a mesma visibilidade.

## Validação no navegador (`components/form-validation.js`)

Primeira barreira, para dar **resposta imediata**. O servidor **continua validando tudo** e é a fonte da verdade. As mensagens são **as mesmas do backend**, então o usuário vê o mesmo texto, venha o erro do navegador ou do servidor.

```html
<form method="post" action="/movimentacoes" id="movementForm" novalidate data-validate>
  <input name="valor" required data-rule="money"
         data-msg-required="Informe um valor válido (ex.: 1.234,56)."
         data-msg-invalid="Informe um valor válido (ex.: 1.234,56)."
         data-msg-min="O valor deve ser maior que zero.">
</form>
```

| Atributo | Efeito |
|---|---|
| `data-validate` (no form) | Ativa a validação |
| `data-error-class` (no form) | Classe do elemento de erro (padrão `field-error`; o login usa `login-field-error`) |
| `data-no-submit` (no form) | Valida, mas não envia: dispara o evento `form:valid` (usado em Investimentos, que ainda não tem backend) |
| `required` | Obrigatório (`data-msg-required`) |
| `data-rule="money"` | Mesmas regras do `MoneyParser` (PHP) e > 0 (`data-msg-invalid`, `data-msg-min`). Ao sair do campo, formata: `3999.9` → `3.999,90` |
| `data-rule="email"` / `data-rule="date"` | Formato de e-mail / data real (`AAAA-MM-DD`) |
| `maxlength` | Limite de caracteres (`data-msg-maxlength`) |
| `data-loading-text` (no botão) | Texto do botão enquanto envia (padrão "Salvando...") |

Comportamento:
- **No envio:** valida tudo, mostra os erros, foca o primeiro inválido e bloqueia o envio. Se estiver válido, **trava o botão** pra evitar cadastro duplicado por duplo clique.
- **Ao sair de um campo preenchido:** valida aquele campo. **Depois do primeiro erro:** revalida enquanto o usuário digita, e o erro some assim que o campo fica correto.
- **Campos dentro de `[hidden]`** (ex.: parcelas quando o tipo não é cartão) são ignorados.
- **Erros do servidor** usam o mesmo elemento (`data-error-for="campo"`). O JS reaproveita e remove esse elemento quando o campo é corrigido.

> Ao criar uma regra no backend, replique a mensagem no `data-msg-*` do campo. Ao criar uma regra de formato nova, adicione em `validateField()`.

## Formulários

Padrão para todo formulário que grava dados:

```php
<form method="post" action="/recurso" id="recursoForm" novalidate data-validate>
    <?= $this->csrf() ?>                                         <!-- obrigatório: sem ele, 419 -->
    <input name="campo" value="<?= $this->e($antigo['campo']) ?>" required data-msg-required="Mesma mensagem do backend."<?= isset($erros['campo']) ? ' aria-invalid="true"' : '' ?>>
    <?php if (isset($erros['campo'])): ?><small class="field-error" data-error-for="campo"><?= $this->e($erros['campo']) ?></small><?php endif; ?>
</form>
```

- O servidor responde com redirect (Post/Redirect/Get). Sucesso aparece como `.flash.flash-success` e erro geral como `.flash.flash-error`.
- `[aria-invalid="true"]` recebe a borda vermelha; `.field-error` é a mensagem abaixo do campo.
- `novalidate` desliga os balões nativos do navegador para que as mensagens venham sempre do servidor, em português e consistentes.
- A tela de login tem classes próprias (`login-field`, `login-alert`, `is-invalid`) e não usa Bootstrap.

## Boas práticas adotadas

- **Nada de JS inline** nas views. Dados vão por `data-*` e comportamento fica em `public/assets/js`.
- **Nada de valor de negócio fixo no HTML.** Opções de select (tipos, parcelas, competências) vêm dos enums e serviços, e nenhum número exibido é fictício (exceto a tela de Investimentos, ainda em demonstração).
- Toda saída dinâmica passa por `$this->e()` (ou pelos helpers que já escapam).
- Acessibilidade: `aria-current` na navegação ativa, `role="dialog"`/`aria-modal` nos modais, `label` associada aos inputs e foco visível.

# Roadmap

O que falta e a ordem sugerida. Atualize ao concluir ou repriorizar itens.

## ✅ Concluído

- [x] Refatoração para arquitetura modular + documentação (2026-09-23)
- [x] Persistência MySQL com migrations (`usuarios`, `movimentacoes`)
- [x] Login real, sessão, logout, proteção de rotas e CSRF
- [x] Cadastro e exclusão de movimentações, parcelamento no cartão com parcelas nos meses seguintes
- [x] Dashboard calculado a partir das movimentações (sem dados fictícios)

## ✅ Observabilidade (2026-09-23)

- [x] Contrato de log (event, operacao, status, correlation_id + domínio + motivo/integracao)
- [x] Logger PSR-3 com `correlation_id` por requisição (header `X-Correlation-ID`)
- [x] Log automático de toda requisição e de toda exceção em `Application::handle()`
- [x] Eventos de negócio de sucesso e recusa em Auth e Movimentações
- [x] Alerta no Slack (error+) via Incoming Webhook
- [ ] Testar com o webhook real (`bin/console slack:testar`)
- [ ] Deduplicar alertas repetidos (ex.: banco fora gera um alerta por requisição)

## Depois

- [ ] Editar movimentação
- [ ] Investimentos com persistência + card no dashboard
- [ ] Limite de tentativas de login
- [ ] Dia de fechamento da fatura do cartão
- [ ] Meta de saldo mensal
- [ ] Reformulação visual do front
- [ ] CI rodando `composer check`
- [ ] Favicon (hoje o navegador recebe 404 em `/favicon.ico`)

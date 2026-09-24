# Infraestrutura

## Serviços (`docker-compose.yml`)

| Serviço | Imagem | Porta host | Função |
|---|---|---|---|
| `app` | `docker/php/Dockerfile` (php:8.2-fpm + Composer 2 + pdo_mysql + zip) | — | Executa o PHP via FastCGI (porta 9000 interna). Sobe só depois que o `db` estiver saudável |
| `web` | `nginx:1.27-alpine` | `${WEB_PORT:-8086}` | Serve `public/` e repassa o resto para o `app` |
| `db` | `mysql:8.0` | `${DB_HOST_PORT:-3307}` | Bancos `overdark` (aplicação) e `overdark_test` (testes). Tem healthcheck |

Detalhes importantes:

- O `app` monta o projeto em `/var/www/html` e roda como `${UID}:${GID}`, para que os arquivos gerados pertençam ao seu usuário.
- O `web` monta **somente** `public/`, em modo somente leitura. Código-fonte, `.env` e `vendor/` nunca ficam expostos.
- Os dados do MySQL persistem no volume `db_data`.

## nginx (`docker/nginx/default.conf`)

```
location /              → try_files $uri /index.php?$query_string   (estático ou front controller)
location = /index.php   → fastcgi_pass app:9000                     (único PHP executável)
location ~ \.php$       → 404                                       (nenhum outro .php roda)
location ~ /\.          → deny                                      (dotfiles)
```

Depois de alterar esse arquivo: `docker compose restart web`.

## Banco de dados

### Usuários e bancos

| Usuário | Senha (dev) | Acesso |
|---|---|---|
| `overdark` | `DB_PASSWORD` | `overdark` e `overdark_test`. É o usuário que a aplicação usa |
| `root` | `DB_ROOT_PASSWORD` | Administração |

- **Volume novo:** a imagem do MySQL cria o banco `overdark` e o usuário `overdark` (`MYSQL_*`), e `docker/mysql/init/01-banco-de-testes.sql` cria `overdark_test` e dá acesso a ele.
- **Volume antigo** (criado antes dessas variáveis): rode uma vez como root:

```sql
CREATE USER IF NOT EXISTS 'overdark'@'%' IDENTIFIED BY 'overdark';
GRANT ALL PRIVILEGES ON overdark.* TO 'overdark'@'%';
CREATE DATABASE IF NOT EXISTS overdark_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON overdark_test.* TO 'overdark'@'%';
```

### Migrations

- Arquivos em `database/migrations/NNNN_descricao.sql`, executados em ordem alfabética.
- `bin/console migrate` executa só os que não constam na tabela `migrations`.
- **Nunca edite uma migration já aplicada.** Crie uma nova (`0003_...sql`) com o `ALTER TABLE`.
- Convenções: `utf8mb4`, InnoDB, dinheiro em `BIGINT` de centavos, enums como `VARCHAR` + `CHECK`, FKs com `ON DELETE CASCADE` para dados do usuário, índice começando por `usuario_id`.

| Migration | Cria |
|---|---|
| `0001_criar_tabela_usuarios.sql` | `usuarios` |
| `0002_criar_tabela_movimentacoes.sql` | `movimentacoes` |

### Consultas úteis

```bash
docker compose exec db mysql -uoverdark -poverdark overdark
```

```sql
-- Totais por mês de um usuário
SELECT DATE_FORMAT(data, '%Y-%m') mes, tipo, SUM(valor_centavos)/100 total
FROM movimentacoes WHERE usuario_id = 1 GROUP BY mes, tipo ORDER BY mes;

-- Parcelas de uma compra
SELECT parcela_numero, data, valor_centavos/100 FROM movimentacoes
WHERE grupo_parcelamento = '<grupo>' ORDER BY parcela_numero;
```

### Banco de testes

`phpunit.xml.dist` força `DB_DATABASE=overdark_test`. O `FeatureTestCase` **apaga e recria o schema** uma vez por execução e limpa as tabelas antes de cada teste. Ele se recusa a rodar se o banco conectado não se chamar `overdark_test`.

## Logs

- Arquivo `storage/logs/overdark.log` (JSON Lines), gravado pelo container `app` e visível no host (volume). Não é versionado.
- Os testes gravam em `storage/logs/testes.log` e nunca enviam ao Slack.
- Sem rotação automática em desenvolvimento. Em produção, usar `logrotate` ou enviar para um agregador. Ver [observabilidade.md](observabilidade.md).

## Variáveis de ambiente

Ver a tabela no [README](../README.md#configuração-env). O `.env` **não é versionado**. O `.env.example` é o contrato e deve receber toda variável nova.

> `DB_HOST_PORT` é a porta **no seu computador**. `DB_PORT` é a porta que a **aplicação** usa dentro da rede do Docker (sempre 3306).

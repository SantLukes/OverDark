-- Executado automaticamente pelo MySQL apenas quando o volume é criado.
-- O usuário da aplicação (MYSQL_USER) já é criado pela imagem com acesso ao banco principal.
CREATE DATABASE IF NOT EXISTS overdark_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON overdark_test.* TO 'overdark'@'%';
FLUSH PRIVILEGES;

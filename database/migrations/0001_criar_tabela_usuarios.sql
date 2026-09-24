CREATE TABLE usuarios (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome        VARCHAR(120)    NOT NULL,
    email       VARCHAR(190)    NOT NULL,
    senha_hash  VARCHAR(255)    NOT NULL,
    criado_em   DATETIME        NOT NULL,
    CONSTRAINT uq_usuarios_email UNIQUE (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

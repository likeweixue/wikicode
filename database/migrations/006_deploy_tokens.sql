CREATE TABLE IF NOT EXISTS deploy_tokens (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL DEFAULT '',
    scope VARCHAR(20) NOT NULL DEFAULT 'write',
    token_hash CHAR(64) NOT NULL,
    last_used_at DATETIME NULL,
    last_used_ip VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_deploy_tokens_user (user_id),
    UNIQUE KEY uniq_token_hash (token_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

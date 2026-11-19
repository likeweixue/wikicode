CREATE TABLE IF NOT EXISTS releases (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    repository_id INT UNSIGNED NOT NULL,
    tag VARCHAR(100) NOT NULL,
    title VARCHAR(200) NOT NULL DEFAULT '',
    body MEDIUMTEXT NULL,
    author_id INT UNSIGNED NULL,
    is_prerelease TINYINT(1) NOT NULL DEFAULT 0,
    download_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_release_tag (repository_id, tag),
    KEY idx_release_repository (repository_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS release_assets (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    release_id INT UNSIGNED NOT NULL,
    filename VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    size BIGINT UNSIGNED NOT NULL DEFAULT 0,
    downloads INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_release_assets (release_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

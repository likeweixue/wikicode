-- 仓库增强字段（幂等，单独执行避免 DDL 事务问题）
SET @sql = (
    SELECT IF(
        EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'repositories' AND COLUMN_NAME = 'homepage'),
        'SELECT "homepage already exists"',
        'ALTER TABLE repositories ADD COLUMN homepage VARCHAR(255) NOT NULL DEFAULT "" AFTER description'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (
    SELECT IF(
        EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'repositories' AND COLUMN_NAME = 'license'),
        'SELECT "license already exists"',
        'ALTER TABLE repositories ADD COLUMN license VARCHAR(64) NOT NULL DEFAULT "" AFTER homepage'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Star / 收藏
CREATE TABLE IF NOT EXISTS stars (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    repository_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY stars_repo_user_unique (repository_id, user_id),
    KEY stars_user_index (user_id),
    CONSTRAINT stars_repository_fk FOREIGN KEY (repository_id) REFERENCES repositories(id) ON DELETE CASCADE,
    CONSTRAINT stars_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Watcher / 关注
CREATE TABLE IF NOT EXISTS watchers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    repository_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY watchers_repo_user_unique (repository_id, user_id),
    KEY watchers_user_index (user_id),
    CONSTRAINT watchers_repository_fk FOREIGN KEY (repository_id) REFERENCES repositories(id) ON DELETE CASCADE,
    CONSTRAINT watchers_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fork / 复刻
CREATE TABLE IF NOT EXISTS forks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    repository_id BIGINT UNSIGNED NOT NULL,
    fork_repository_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY forks_fork_repo_unique (fork_repository_id),
    KEY forks_repository_index (repository_id),
    KEY forks_user_index (user_id),
    CONSTRAINT forks_repository_fk FOREIGN KEY (repository_id) REFERENCES repositories(id) ON DELETE CASCADE,
    CONSTRAINT forks_fork_repository_fk FOREIGN KEY (fork_repository_id) REFERENCES repositories(id) ON DELETE CASCADE,
    CONSTRAINT forks_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Wiki 页面
CREATE TABLE IF NOT EXISTS wiki_pages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    repository_id BIGINT UNSIGNED NOT NULL,
    slug VARCHAR(100) NOT NULL,
    title VARCHAR(200) NOT NULL,
    parent_slug VARCHAR(100) NOT NULL DEFAULT '',
    body MEDIUMTEXT NOT NULL,
    position INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY wiki_repo_slug_unique (repository_id, slug),
    KEY wiki_repo_index (repository_id),
    CONSTRAINT wiki_repository_fk FOREIGN KEY (repository_id) REFERENCES repositories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

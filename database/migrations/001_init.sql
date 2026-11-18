CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(32) NOT NULL UNIQUE,
    email VARCHAR(190) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(16) NOT NULL DEFAULT 'user',
    locale VARCHAR(8) NOT NULL DEFAULT 'zh-CN',
    theme VARCHAR(8) NOT NULL DEFAULT 'light',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS repositories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(50) NOT NULL,
    description VARCHAR(255) NOT NULL DEFAULT '',
    visibility VARCHAR(16) NOT NULL DEFAULT 'public',
    default_branch VARCHAR(100) NOT NULL DEFAULT 'main',
    issue_seq BIGINT UNSIGNED NOT NULL DEFAULT 0,
    pr_seq BIGINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY repositories_owner_name_unique (owner_id, name),
    KEY repositories_visibility_index (visibility),
    CONSTRAINT repositories_owner_fk FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS issues (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    repository_id BIGINT UNSIGNED NOT NULL,
    number BIGINT UNSIGNED NOT NULL,
    author_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    body MEDIUMTEXT NOT NULL,
    status VARCHAR(16) NOT NULL DEFAULT 'open',
    closed_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY issues_repository_number_unique (repository_id, number),
    KEY issues_repository_status_index (repository_id, status),
    CONSTRAINT issues_repository_fk FOREIGN KEY (repository_id) REFERENCES repositories(id) ON DELETE CASCADE,
    CONSTRAINT issues_author_fk FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS issue_comments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    issue_id BIGINT UNSIGNED NOT NULL,
    author_id BIGINT UNSIGNED NOT NULL,
    body MEDIUMTEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT issue_comments_issue_fk FOREIGN KEY (issue_id) REFERENCES issues(id) ON DELETE CASCADE,
    CONSTRAINT issue_comments_author_fk FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pull_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    repository_id BIGINT UNSIGNED NOT NULL,
    number BIGINT UNSIGNED NOT NULL,
    author_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    body MEDIUMTEXT NOT NULL,
    base_branch VARCHAR(150) NOT NULL,
    head_branch VARCHAR(150) NOT NULL,
    base_sha CHAR(40) NOT NULL DEFAULT '',
    head_sha CHAR(40) NOT NULL DEFAULT '',
    status VARCHAR(16) NOT NULL DEFAULT 'open',
    merge_commit CHAR(40) NULL DEFAULT NULL,
    merged_by BIGINT UNSIGNED NULL DEFAULT NULL,
    merged_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY pull_requests_repository_number_unique (repository_id, number),
    KEY pull_requests_repository_status_index (repository_id, status),
    CONSTRAINT pull_requests_repository_fk FOREIGN KEY (repository_id) REFERENCES repositories(id) ON DELETE CASCADE,
    CONSTRAINT pull_requests_author_fk FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT pull_requests_merged_by_fk FOREIGN KEY (merged_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
    `key` VARCHAR(100) PRIMARY KEY,
    `value` MEDIUMTEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

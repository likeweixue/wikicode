CREATE TABLE IF NOT EXISTS forum_categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NOT NULL DEFAULT '',
    position INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS forum_boards (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NOT NULL DEFAULT '',
    position INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY forum_boards_category_index (category_id),
    CONSTRAINT forum_boards_category_fk FOREIGN KEY (category_id) REFERENCES forum_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS forum_topics (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    board_id BIGINT UNSIGNED NOT NULL,
    author_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    body MEDIUMTEXT NOT NULL,
    views INT UNSIGNED NOT NULL DEFAULT 0,
    is_locked TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY forum_topics_board_index (board_id),
    KEY forum_topics_updated_index (updated_at),
    CONSTRAINT forum_topics_board_fk FOREIGN KEY (board_id) REFERENCES forum_boards(id) ON DELETE CASCADE,
    CONSTRAINT forum_topics_author_fk FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS forum_posts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    topic_id BIGINT UNSIGNED NOT NULL,
    author_id BIGINT UNSIGNED NOT NULL,
    body MEDIUMTEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY forum_posts_topic_index (topic_id),
    CONSTRAINT forum_posts_topic_fk FOREIGN KEY (topic_id) REFERENCES forum_topics(id) ON DELETE CASCADE,
    CONSTRAINT forum_posts_author_fk FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO forum_categories (name, description, position)
SELECT '综合讨论', '产品、技术与日常想法，都在这里聊聊', 1
WHERE NOT EXISTS (SELECT 1 FROM forum_categories WHERE name = '综合讨论');

INSERT INTO forum_categories (name, description, position)
SELECT '项目展示', '分享作品、寻求反馈与协作', 2
WHERE NOT EXISTS (SELECT 1 FROM forum_categories WHERE name = '项目展示');

INSERT INTO forum_boards (category_id, name, description, position)
SELECT id, '站点公告', '版本更新、维护通知与社区规则', 1 FROM forum_categories
WHERE name = '综合讨论' AND NOT EXISTS (SELECT 1 FROM forum_boards WHERE name = '站点公告');

INSERT INTO forum_boards (category_id, name, description, position)
SELECT id, '技术交流', '语言、框架、架构与工程实践', 2 FROM forum_categories
WHERE name = '综合讨论' AND NOT EXISTS (SELECT 1 FROM forum_boards WHERE name = '技术交流');

INSERT INTO forum_boards (category_id, name, description, position)
SELECT id, '问题求助', '遇到问题？把复现步骤贴出来', 3 FROM forum_categories
WHERE name = '综合讨论' AND NOT EXISTS (SELECT 1 FROM forum_boards WHERE name = '问题求助');

INSERT INTO forum_boards (category_id, name, description, position)
SELECT id, '作品分享', '展示你的项目，收集社区的反馈', 1 FROM forum_categories
WHERE name = '项目展示' AND NOT EXISTS (SELECT 1 FROM forum_boards WHERE name = '作品分享');

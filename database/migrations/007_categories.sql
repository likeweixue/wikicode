CREATE TABLE IF NOT EXISTS categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    description VARCHAR(255) NOT NULL DEFAULT '',
    icon VARCHAR(50) NOT NULL DEFAULT '',
    position INT NOT NULL DEFAULT 0,
    is_builtin TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY categories_slug_unique (slug),
    KEY categories_position_index (position, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE repositories ADD COLUMN category_id BIGINT UNSIGNED NULL DEFAULT NULL AFTER visibility;
ALTER TABLE repositories ADD KEY repositories_category_index (category_id);
ALTER TABLE repositories ADD CONSTRAINT repositories_category_fk FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL;

INSERT INTO categories (name, slug, description, icon, position, is_builtin) VALUES
('建站CMS', 'cms', '内容管理系统 / 建站程序', '🏗', 1, 1),
('建站论坛', 'forum', '论坛 / 社区建站程序', '💬', 2, 1),
('图床程序', 'image-host', '图片托管 / 图床', '🖼', 3, 1),
('网盘程序', 'cloud-disk', '网盘 / 文件分享程序', '☁️', 4, 1),
('下载工具', 'download', '下载 / 采集工具', '⬇️', 5, 1),
('博客程序', 'blog', '博客 / 文章发布程序', '📝', 6, 1),
('导航程序', 'nav', '导航 / 书签站程序', '🧭', 7, 1),
('其他', 'other', '未归类项目', '📦', 99, 1);

UPDATE nav_items SET label_key = 'Home' WHERE url = '/' AND is_core = 1;
INSERT INTO nav_items (label_key, url, position, requires_auth, requires_admin, is_core)
SELECT 'Explore', '/explore', 2, 0, 0, 1
WHERE NOT EXISTS (SELECT 1 FROM nav_items WHERE url = '/explore');
UPDATE nav_items SET position = 3 WHERE url = '/community' AND is_core = 1;
UPDATE nav_items SET position = 4 WHERE url = '/dashboard' AND is_core = 1;
UPDATE nav_items SET position = 5 WHERE url = '/new' AND is_core = 1;
UPDATE nav_items SET position = 6 WHERE url = '/admin' AND is_core = 1;

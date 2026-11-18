CREATE TABLE IF NOT EXISTS nav_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(100) NOT NULL DEFAULT '',
    label_key VARCHAR(100) NOT NULL DEFAULT '',
    url VARCHAR(255) NOT NULL,
    position INT NOT NULL DEFAULT 0,
    is_visible TINYINT(1) NOT NULL DEFAULT 1,
    requires_auth TINYINT(1) NOT NULL DEFAULT 0,
    requires_admin TINYINT(1) NOT NULL DEFAULT 0,
    is_core TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO nav_items (label_key, url, position, requires_auth, requires_admin, is_core)
SELECT 'Explore', '/', 1, 0, 0, 1
WHERE NOT EXISTS (SELECT 1 FROM nav_items WHERE url = '/');

INSERT INTO nav_items (label_key, url, position, requires_auth, requires_admin, is_core)
SELECT 'Community', '/community', 2, 0, 0, 1
WHERE NOT EXISTS (SELECT 1 FROM nav_items WHERE url = '/community');

INSERT INTO nav_items (label_key, url, position, requires_auth, requires_admin, is_core)
SELECT 'Dashboard', '/dashboard', 3, 1, 0, 1
WHERE NOT EXISTS (SELECT 1 FROM nav_items WHERE url = '/dashboard');

INSERT INTO nav_items (label_key, url, position, requires_auth, requires_admin, is_core)
SELECT 'New repository', '/new', 4, 1, 0, 1
WHERE NOT EXISTS (SELECT 1 FROM nav_items WHERE url = '/new');

INSERT INTO nav_items (label_key, url, position, requires_auth, requires_admin, is_core)
SELECT 'Administration', '/admin', 5, 1, 1, 1
WHERE NOT EXISTS (SELECT 1 FROM nav_items WHERE url = '/admin');

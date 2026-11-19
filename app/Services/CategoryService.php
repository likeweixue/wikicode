<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use InvalidArgumentException;

/**
 * 仓库分类管理。
 *
 * 分类用于"探索"页左侧筛选，并在创建仓库 / 仓库设置时可选。
 */
final class CategoryService
{
    /**
     * 全部内置分类（slug => [name, icon, position]）。
     *
     * @return array<string, array{name: string, icon: string, position: int}>
     */
    public static function builtins(): array
    {
        return [
            'cms'         => ['name' => '建站CMS',   'icon' => '🏗', 'position' => 1],
            'forum'       => ['name' => '建站论坛',  'icon' => '💬', 'position' => 2],
            'image-host'  => ['name' => '图床程序',  'icon' => '🖼', 'position' => 3],
            'cloud-disk'  => ['name' => '网盘程序',  'icon' => '☁️', 'position' => 4],
            'download'    => ['name' => '下载工具',  'icon' => '⬇️', 'position' => 5],
            'blog'        => ['name' => '博客程序',  'icon' => '📝', 'position' => 6],
            'nav'         => ['name' => '导航程序',  'icon' => '🧭', 'position' => 7],
            'other'       => ['name' => '其他',      'icon' => '📦', 'position' => 99],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function all(): array
    {
        return Database::fetchAll('SELECT * FROM categories ORDER BY position, id');
    }

    /**
     * 每个分类附带公开仓库数量。
     *
     * @return list<array<string, mixed>>
     */
    public static function allWithPublicCount(): array
    {
        $rows = Database::fetchAll(
            'SELECT c.*,
                (SELECT COUNT(*) FROM repositories r WHERE r.category_id = c.id AND r.visibility = \'public\') AS repo_count
             FROM categories c ORDER BY c.position, c.id'
        );
        return $rows;
    }

    public static function find(int $id): ?array
    {
        if ($id < 1) {
            return null;
        }
        return Database::fetch('SELECT * FROM categories WHERE id = ? LIMIT 1', [$id]);
    }

    public static function findBySlug(string $slug): ?array
    {
        $slug = trim($slug);
        if ($slug === '') {
            return null;
        }
        return Database::fetch('SELECT * FROM categories WHERE slug = ? LIMIT 1', [$slug]);
    }

    /**
     * 校验 category_id：0 / null 表示"未分类"，其余必须存在。
     */
    public static function normalizeId(mixed $raw): int
    {
        $id = (int) $raw;
        if ($id <= 0) {
            return 0;
        }
        return self::find($id) !== null ? $id : 0;
    }

    public static function store(string $name, string $description = '', string $icon = '', int $position = 0): int
    {
        $name = trim($name);
        if ($name === '') {
            throw new InvalidArgumentException('Category name is required.');
        }
        $slug = self::slugify($name);
        if (self::findBySlug($slug) !== null) {
            throw new InvalidArgumentException('A category with this name already exists.');
        }
        Database::execute(
            'INSERT INTO categories (name, slug, description, icon, position, is_builtin) VALUES (?, ?, ?, ?, ?, 0)',
            [$name, $slug, trim($description), trim($icon), $position]
        );
        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, string $name, string $description = '', string $icon = '', int $position = 0): void
    {
        $category = self::find($id);
        if ($category === null) {
            throw new InvalidArgumentException('Category not found: ' . $id);
        }
        $name = trim($name);
        if ($name === '') {
            throw new InvalidArgumentException('Category name is required.');
        }
        $slug = self::slugify($name);
        $duplicate = Database::fetch('SELECT id FROM categories WHERE slug = ? AND id <> ? LIMIT 1', [$slug, $id]);
        if ($duplicate !== null) {
            throw new InvalidArgumentException('A category with this name already exists.');
        }
        Database::execute(
            'UPDATE categories SET name = ?, slug = ?, description = ?, icon = ?, position = ? WHERE id = ?',
            [$name, $slug, trim($description), trim($icon), $position, $id]
        );
    }

    public static function delete(int $id): void
    {
        $category = self::find($id);
        if ($category === null) {
            return;
        }
        // 内置分类不允许删除（避免破坏默认结构），只能重命名
        if (!empty($category['is_builtin'])) {
            throw new InvalidArgumentException('Built-in categories cannot be deleted.');
        }
        // 先解除关联，再删除（FK ON DELETE SET NULL 也会处理）
        Database::execute('UPDATE repositories SET category_id = NULL WHERE category_id = ?', [$id]);
        Database::execute('DELETE FROM categories WHERE id = ?', [$id]);
    }

    private static function slugify(string $name): string
    {
        $name = strtolower(trim($name));
        // 中文直接保留并转拼音占位不可靠，这里用 md5 短码兜底；纯 ASCII 走常规 slug
        $ascii = preg_replace('/[^a-z0-9]+/', '-', $name) ?? '';
        $ascii = trim($ascii, '-');
        if ($ascii !== '') {
            return substr($ascii, 0, 90);
        }
        return 'cat-' . substr(md5($name), 0, 12);
    }
}

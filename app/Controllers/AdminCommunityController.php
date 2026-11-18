<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;

final class AdminCommunityController
{
    public function index(): void
    {
        Auth::requireAdmin();
        $categories = Database::fetchAll('SELECT * FROM forum_categories ORDER BY position, id');
        $boards = Database::fetchAll(
            'SELECT b.*, c.name AS category_name,
                (SELECT COUNT(*) FROM forum_topics t WHERE t.board_id = b.id) AS topic_count
             FROM forum_boards b JOIN forum_categories c ON c.id = b.category_id
             ORDER BY c.position, b.position, b.id'
        );
        View::render('admin/community', [
            'title' => t('Community settings'),
            'active' => 'community',
            'categories' => $categories,
            'boards' => $boards,
        ]);
    }

    public function storeCategory(): void
    {
        Auth::requireAdmin();
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') {
            flash('error', t('Please provide a category name.'));
            $this->back();
        }
        Database::execute(
            'INSERT INTO forum_categories (name, description, position) VALUES (?, ?, ?)',
            [$name, trim((string) ($_POST['description'] ?? '')), (int) ($_POST['position'] ?? 0)]
        );
        flash('success', t('Category created.'));
        $this->back();
    }

    public function updateCategory(string $id): void
    {
        Auth::requireAdmin();
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') {
            flash('error', t('Please provide a category name.'));
            $this->back();
        }
        Database::execute(
            'UPDATE forum_categories SET name = ?, description = ?, position = ? WHERE id = ?',
            [$name, trim((string) ($_POST['description'] ?? '')), (int) ($_POST['position'] ?? 0), (int) $id]
        );
        flash('success', t('Category updated.'));
        $this->back();
    }

    public function deleteCategory(string $id): void
    {
        Auth::requireAdmin();
        Database::execute('DELETE FROM forum_categories WHERE id = ?', [(int) $id]);
        flash('success', t('Category deleted.'));
        $this->back();
    }

    public function storeBoard(): void
    {
        Auth::requireAdmin();
        $name = trim((string) ($_POST['name'] ?? ''));
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        if ($name === '' || $categoryId <= 0) {
            flash('error', t('Please choose a category and provide a board name.'));
            $this->back();
        }
        Database::execute(
            'INSERT INTO forum_boards (category_id, name, description, position) VALUES (?, ?, ?, ?)',
            [$categoryId, $name, trim((string) ($_POST['description'] ?? '')), (int) ($_POST['position'] ?? 0)]
        );
        flash('success', t('Board created.'));
        $this->back();
    }

    public function updateBoard(string $id): void
    {
        Auth::requireAdmin();
        $name = trim((string) ($_POST['name'] ?? ''));
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        if ($name === '' || $categoryId <= 0) {
            flash('error', t('Please choose a category and provide a board name.'));
            $this->back();
        }
        Database::execute(
            'UPDATE forum_boards SET category_id = ?, name = ?, description = ?, position = ? WHERE id = ?',
            [$categoryId, $name, trim((string) ($_POST['description'] ?? '')), (int) ($_POST['position'] ?? 0), (int) $id]
        );
        flash('success', t('Board updated.'));
        $this->back();
    }

    public function deleteBoard(string $id): void
    {
        Auth::requireAdmin();
        Database::execute('DELETE FROM forum_boards WHERE id = ?', [(int) $id]);
        flash('success', t('Board deleted.'));
        $this->back();
    }

    private function back(): void
    {
        redirect('/admin/community');
    }
}

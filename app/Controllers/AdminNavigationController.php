<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;

final class AdminNavigationController
{
    public function index(): void
    {
        Auth::requireAdmin();
        $items = Database::fetchAll('SELECT * FROM nav_items ORDER BY position, id');
        View::render('admin/navigation', [
            'title' => t('Navigation settings'),
            'active' => 'navigation',
            'items' => $items,
        ]);
    }

    public function store(): void
    {
        Auth::requireAdmin();
        $label = trim((string) ($_POST['label'] ?? ''));
        $url = trim((string) ($_POST['url'] ?? ''));
        if ($label === '' || !$this->validUrl($url)) {
            flash('error', t('Please provide a label and a valid URL.'));
            redirect('/admin/navigation');
        }
        Database::execute(
            'INSERT INTO nav_items (label, url, position, is_visible, requires_auth, requires_admin, is_core) VALUES (?, ?, ?, ?, ?, ?, 0)',
            [
                $label,
                $url,
                (int) ($_POST['position'] ?? 0),
                empty($_POST['is_visible']) ? 0 : 1,
                empty($_POST['requires_auth']) ? 0 : 1,
                empty($_POST['requires_admin']) ? 0 : 1,
            ]
        );
        flash('success', t('Menu item created.'));
        redirect('/admin/navigation');
    }

    public function update(string $id): void
    {
        Auth::requireAdmin();
        $item = $this->find((int) $id);
        $url = trim((string) ($_POST['url'] ?? ''));
        if (!$this->validUrl($url)) {
            flash('error', t('Please provide a valid URL.'));
            redirect('/admin/navigation');
        }
        $label = trim((string) ($_POST['label'] ?? ''));
        if ($label === '' && $item['label_key'] === '') {
            flash('error', t('Please provide a menu label.'));
            redirect('/admin/navigation');
        }
        Database::execute(
            'UPDATE nav_items SET label = ?, url = ?, position = ?, is_visible = ? WHERE id = ?',
            [
                $label,
                $url,
                (int) ($_POST['position'] ?? 0),
                empty($_POST['is_visible']) ? 0 : 1,
                (int) $id,
            ]
        );
        flash('success', t('Menu item updated.'));
        redirect('/admin/navigation');
    }

    public function delete(string $id): void
    {
        Auth::requireAdmin();
        $item = $this->find((int) $id);
        if ((int) $item['is_core'] === 1) {
            flash('error', t('Built-in menu items can be hidden but not deleted.'));
            redirect('/admin/navigation');
        }
        Database::execute('DELETE FROM nav_items WHERE id = ?', [(int) $id]);
        flash('success', t('Menu item deleted.'));
        redirect('/admin/navigation');
    }

    private function find(int $id): array
    {
        $item = Database::fetch('SELECT * FROM nav_items WHERE id = ?', [$id]);
        if (!$item) {
            abort(404);
        }
        return $item;
    }

    private function validUrl(string $url): bool
    {
        return $url !== '' && ($url[0] === '/' || str_starts_with($url, 'http://') || str_starts_with($url, 'https://'));
    }
}

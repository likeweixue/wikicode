<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Services\CategoryService;
use Throwable;

final class AdminCategoriesController
{
    public function index(): void
    {
        Auth::requireAdmin();
        $categories = CategoryService::all();
        View::render('admin/categories', [
            'title' => t('Category settings'),
            'active' => 'categories',
            'categories' => $categories,
        ]);
    }

    public function store(): void
    {
        Auth::requireAdmin();
        $name = trim((string) ($_POST['name'] ?? ''));
        try {
            CategoryService::store(
                $name,
                (string) ($_POST['description'] ?? ''),
                (string) ($_POST['icon'] ?? ''),
                (int) ($_POST['position'] ?? 0)
            );
            flash('success', t('Category created.'));
        } catch (Throwable $exception) {
            flash('error', $exception->getMessage());
        }
        redirect('/admin/categories');
    }

    public function update(string $id): void
    {
        Auth::requireAdmin();
        $name = trim((string) ($_POST['name'] ?? ''));
        try {
            CategoryService::update(
                (int) $id,
                $name,
                (string) ($_POST['description'] ?? ''),
                (string) ($_POST['icon'] ?? ''),
                (int) ($_POST['position'] ?? 0)
            );
            flash('success', t('Category updated.'));
        } catch (Throwable $exception) {
            flash('error', $exception->getMessage());
        }
        redirect('/admin/categories');
    }

    public function delete(string $id): void
    {
        Auth::requireAdmin();
        try {
            CategoryService::delete((int) $id);
            flash('success', t('Category deleted.'));
        } catch (Throwable $exception) {
            flash('error', $exception->getMessage());
        }
        redirect('/admin/categories');
    }
}

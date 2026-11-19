<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Setting;
use App\Core\View;
use App\Services\CategoryService;
use App\Services\RepositoryService;

final class ExploreController
{
    public function index(): void
    {
        // 分类筛选（可选）
        $categorySlug = trim((string) ($_GET['category'] ?? ''));
        $currentCategory = $categorySlug !== '' ? CategoryService::findBySlug($categorySlug) : null;
        $categoryId = $currentCategory !== null ? (int) $currentCategory['id'] : null;

        $repositories = RepositoryService::latestPublic($categoryId, 120);
        $categories = CategoryService::allWithPublicCount();
        $totalPublic = count(RepositoryService::latestPublic(null, 9999));

        // 右栏：热门 / 推荐
        $hotTitle = (string) Setting::get('explore_hot_title', '热门');
        $hotMode = (string) Setting::get('explore_hot_mode', 'stars');
        if ($hotMode === 'featured') {
            $raw = (string) Setting::get('explore_featured_repos', '');
            $ids = array_map('intval', preg_split('/\s*,\s*/', $raw) ?: []);
            $hotRepos = RepositoryService::byIdsPublic($ids);
        } else {
            $hotRepos = RepositoryService::topStarred(10);
        }

        View::render('explore', [
            'title' => t('Explore') . ' — ' . setting('site_name', 'wikicode'),
            'categories' => $categories,
            'repositories' => $repositories,
            'totalPublic' => $totalPublic,
            'currentCategory' => $currentCategory,
            'currentCategorySlug' => $currentCategory['slug'] ?? '',
            'hotTitle' => $hotTitle,
            'hotMode' => $hotMode,
            'hotRepos' => $hotRepos,
        ]);
    }
}

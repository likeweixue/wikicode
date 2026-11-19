<?php

declare(strict_types=1);

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\AdminCommunityController;
use App\Controllers\AdminNavigationController;
use App\Controllers\DashboardController;
use App\Controllers\ForumController;
use App\Controllers\HomeController;
use App\Controllers\InsightsController;
use App\Controllers\IssueController;
use App\Controllers\PullRequestController;
use App\Controllers\ReleaseController;
use App\Controllers\RepositoryController;
use App\Controllers\WikiController;
use App\Core\Router;
use App\Core\View;

require dirname(__DIR__) . '/app/bootstrap.php';

try {
    Router::get('/', [HomeController::class, 'index']);
    Router::get('/login', [AuthController::class, 'showLogin']);
    Router::post('/login', [AuthController::class, 'login']);
    Router::get('/register', [AuthController::class, 'showRegister']);
    Router::post('/register', [AuthController::class, 'register']);
    Router::post('/logout', [AuthController::class, 'logout']);
    Router::post('/locale', [AuthController::class, 'locale']);
    Router::post('/theme', [AuthController::class, 'theme']);
    Router::get('/dashboard', [DashboardController::class, 'index']);
    Router::get('/community', [ForumController::class, 'index']);
    Router::get('/community/board/{board}', [ForumController::class, 'board']);
    Router::get('/community/board/{board}/new', [ForumController::class, 'createTopic']);
    Router::post('/community/board/{board}/new', [ForumController::class, 'createTopic']);
    Router::get('/community/topic/{topic}', [ForumController::class, 'topic']);
    Router::post('/community/topic/{topic}/reply', [ForumController::class, 'reply']);
    Router::get('/admin', [AdminController::class, 'dashboard']);
    Router::get('/admin/users', [AdminController::class, 'users']);
    Router::post('/admin/users/{id}/toggle', [AdminController::class, 'toggleUser']);
    Router::post('/admin/users/{id}/role', [AdminController::class, 'userRole']);
    Router::post('/admin/users/{id}/delete', [AdminController::class, 'deleteUser']);
    Router::get('/admin/repositories', [AdminController::class, 'repositories']);
    Router::post('/admin/repositories/{id}/delete', [AdminController::class, 'deleteRepository']);
    Router::get('/admin/settings/site', [AdminController::class, 'siteSettings']);
    Router::post('/admin/settings/site', [AdminController::class, 'saveSiteSettings']);
    Router::get('/admin/settings/mail', [AdminController::class, 'mailSettings']);
    Router::post('/admin/settings/mail', [AdminController::class, 'saveMailSettings']);
    Router::post('/admin/settings/mail/test', [AdminController::class, 'testMail']);
    Router::get('/admin/community', [AdminCommunityController::class, 'index']);
    Router::post('/admin/community/categories', [AdminCommunityController::class, 'storeCategory']);
    Router::post('/admin/community/categories/{id}', [AdminCommunityController::class, 'updateCategory']);
    Router::post('/admin/community/categories/{id}/delete', [AdminCommunityController::class, 'deleteCategory']);
    Router::post('/admin/community/boards', [AdminCommunityController::class, 'storeBoard']);
    Router::post('/admin/community/boards/{id}', [AdminCommunityController::class, 'updateBoard']);
    Router::post('/admin/community/boards/{id}/delete', [AdminCommunityController::class, 'deleteBoard']);
    Router::get('/admin/navigation', [AdminNavigationController::class, 'index']);
    Router::post('/admin/navigation/items', [AdminNavigationController::class, 'store']);
    Router::post('/admin/navigation/items/{id}', [AdminNavigationController::class, 'update']);
    Router::post('/admin/navigation/items/{id}/delete', [AdminNavigationController::class, 'delete']);
    Router::get('/new', [RepositoryController::class, 'create']);
    Router::post('/new', [RepositoryController::class, 'store']);

    Router::get('/{owner}/{repo}/issues', [IssueController::class, 'index']);
    Router::post('/{owner}/{repo}/issues', [IssueController::class, 'store']);
    Router::get('/{owner}/{repo}/issues/new', [IssueController::class, 'create']);
    Router::get('/{owner}/{repo}/issues/{number}', [IssueController::class, 'show']);
    Router::post('/{owner}/{repo}/issues/{number}/comments', [IssueController::class, 'comment']);
    Router::post('/{owner}/{repo}/issues/{number}/toggle', [IssueController::class, 'toggle']);

    Router::get('/{owner}/{repo}/pulls', [PullRequestController::class, 'index']);
    Router::post('/{owner}/{repo}/pulls', [PullRequestController::class, 'store']);
    Router::get('/{owner}/{repo}/pulls/new', [PullRequestController::class, 'create']);
    Router::get('/{owner}/{repo}/pulls/{number}', [PullRequestController::class, 'show']);
    Router::post('/{owner}/{repo}/pulls/{number}/merge', [PullRequestController::class, 'merge']);
    Router::post('/{owner}/{repo}/pulls/{number}/toggle', [PullRequestController::class, 'toggle']);

    Router::get('/{owner}/{repo}/blob', [RepositoryController::class, 'blob']);
    Router::get('/{owner}/{repo}/raw', [RepositoryController::class, 'raw']);
    Router::get('/{owner}/{repo}/commits', [RepositoryController::class, 'commits']);
    Router::get('/{owner}/{repo}/archive', [RepositoryController::class, 'archive']);
    Router::get('/{owner}/{repo}/new-file', [RepositoryController::class, 'newFile']);
    Router::post('/{owner}/{repo}/new-file', [RepositoryController::class, 'newFile']);
    Router::get('/{owner}/{repo}/edit', [RepositoryController::class, 'edit']);
    Router::post('/{owner}/{repo}/edit', [RepositoryController::class, 'edit']);
    // Releases & tags
    Router::get('/{owner}/{repo}/releases', [ReleaseController::class, 'index']);
    Router::get('/{owner}/{repo}/releases/new', [ReleaseController::class, 'create']);
    Router::post('/{owner}/{repo}/releases', [ReleaseController::class, 'store']);
    Router::get('/{owner}/{repo}/releases/{release}/assets/{asset}', [ReleaseController::class, 'download']);
    Router::get('/{owner}/{repo}/releases/{release}', [ReleaseController::class, 'show']);
    Router::post('/{owner}/{repo}/releases/{release}/assets', [ReleaseController::class, 'uploadAsset']);
    Router::get('/{owner}/{repo}/tags', [ReleaseController::class, 'tags']);
    Router::post('/{owner}/{repo}/tags', [ReleaseController::class, 'storeTag']);

    Router::get('/{owner}/{repo}/commit/{sha}', [RepositoryController::class, 'commit']);
    Router::get('/{owner}/{repo}/settings', [RepositoryController::class, 'settings']);
    Router::post('/{owner}/{repo}/settings', [RepositoryController::class, 'update']);
    Router::post('/{owner}/{repo}/delete', [RepositoryController::class, 'delete']);
    Router::post('/{owner}/{repo}/star', [RepositoryController::class, 'toggleStar']);
    Router::post('/{owner}/{repo}/watch', [RepositoryController::class, 'toggleWatch']);

    // Wiki
    Router::get('/{owner}/{repo}/wiki', [WikiController::class, 'index']);
    Router::get('/{owner}/{repo}/wiki/_new', [WikiController::class, 'create']);
    Router::post('/{owner}/{repo}/wiki/_new', [WikiController::class, 'create']);
    Router::get('/{owner}/{repo}/wiki/{slug}/_edit', [WikiController::class, 'edit']);
    Router::post('/{owner}/{repo}/wiki/{slug}/_edit', [WikiController::class, 'edit']);
    Router::post('/{owner}/{repo}/wiki/{slug}/_delete', [WikiController::class, 'delete']);
    Router::get('/{owner}/{repo}/wiki/{slug}', [WikiController::class, 'show']);

    // Insights
    Router::get('/{owner}/{repo}/insights', [InsightsController::class, 'index']);

    Router::get('/{owner}/{repo}', [RepositoryController::class, 'show']);

    Router::dispatch($_SERVER['REQUEST_METHOD'], parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);
    View::render('errors/500', ['title' => t('System error')]);
}

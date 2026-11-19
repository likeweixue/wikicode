<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Services\DeployTokenService;

final class DeployTokenController
{
    public function index(): void
    {
        Auth::requireUser();
        $tokens = Auth::isAdmin()
            ? DeployTokenService::listAll()
            : DeployTokenService::listForUser((int) Auth::id());

        View::render('admin/deploy_tokens', [
            'title' => t('Deploy tokens'),
            'active' => Auth::isAdmin() ? 'deploy-tokens' : '',
            'tokens' => $tokens,
            'justCreated' => null,
        ]);
    }

    public function store(): void
    {
        Auth::requireUser();
        $name = trim((string) ($_POST['name'] ?? ''));
        $scope = ($_POST['scope'] ?? 'write') === 'read' ? 'read' : 'write';
        if ($name === '') {
            flash('error', t('Please provide a name for the token.'));
            redirect('/user/deploy-tokens');
        }

        $plain = DeployTokenService::create((int) Auth::id(), $name, $scope);

        $tokens = Auth::isAdmin()
            ? DeployTokenService::listAll()
            : DeployTokenService::listForUser((int) Auth::id());

        View::render('admin/deploy_tokens', [
            'title' => t('Deploy tokens'),
            'active' => Auth::isAdmin() ? 'deploy-tokens' : '',
            'tokens' => $tokens,
            'justCreated' => ['name' => $name, 'scope' => $scope, 'token' => $plain],
        ]);
    }

    public function revoke(string $id): void
    {
        Auth::requireUser();
        $revoked = Auth::isAdmin()
            ? DeployTokenService::revokeAny((int) $id)
            : DeployTokenService::revoke((int) $id, (int) Auth::id());

        if ($revoked) {
            flash('success', t('Deploy token revoked.'));
        } else {
            flash('error', t('Unable to revoke the deploy token.'));
        }
        redirect(Auth::isAdmin() ? '/admin/deploy-tokens' : '/user/deploy-tokens');
    }
}

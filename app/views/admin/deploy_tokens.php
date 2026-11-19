<div class="admin-layout">
    <?php require __DIR__ . '/_nav.php'; ?>
    <div class="admin-body">
    <?php $isAdmin = \App\Core\Auth::isAdmin(); $myId = (int) \App\Core\Auth::id(); ?>
    <?php if (!empty($justCreated)): ?>
        <div class="tech-panel token-created">
            <div class="panel-topline"><span><?= t('Deploy token created') ?></span><b>NEW / COPY NOW</b></div>
            <p><?= t('Copy this token right now. It will only be shown once.') ?></p>
            <div class="token-copy-row">
                <code class="token-copy" data-copy="<?= e($justCreated['token']) ?>"><?= e($justCreated['token']) ?></code>
                <button type="button" data-copy-trigger><?= t('Copy') ?></button>
            </div>
            <dl class="token-meta">
                <dt><?= t('Name') ?></dt><dd><?= e($justCreated['name']) ?></dd>
                <dt><?= t('Scope') ?></dt><dd><?= $justCreated['scope'] === 'read' ? t('Read only') : t('Read & write (push allowed)') ?></dd>
            </dl>
            <p class="token-url-help">
                <?= t('Use it like this:') ?><br>
                <code>git remote add wikicode https://oauth2:<?= e($justCreated['token']) ?>@<?= e($_SERVER['HTTP_HOST'] ?? 'host') ?>/owner/repo.git</code><br>
                <code>git push wikicode main</code>
            </p>
        </div>
    <?php endif; ?>

    <section class="tech-panel">
        <div class="panel-topline"><span><?= t('My deploy tokens') ?></span><b>TOKENS / <?= count($tokens) ?></b></div>

        <form method="post" action="<?= e($isAdmin ? '/admin/deploy-tokens' : '/user/deploy-tokens') ?>" class="token-create-form">
            <?= csrf_field() ?>
            <label class="token-name-field">
                <span><?= t('Token name') ?> <i class="required">*</i></span>
                <input type="text" name="name" required maxlength="120" placeholder="<?= t('e.g. CI bot, my laptop') ?>">
            </label>
            <label class="token-scope-field">
                <span><?= t('Scope') ?></span>
                <select name="scope">
                    <option value="write"><?= t('Read & write — allows push') ?></option>
                    <option value="read"><?= t('Read only — pull / clone only') ?></option>
                </select>
            </label>
            <div class="token-create-actions">
                <button class="btn btn-primary" type="submit">＋ <?= t('Generate token') ?></button>
            </div>
        </form>

        <?php if ($tokens): ?>
            <table class="token-table">
                <thead>
                    <tr>
                        <th><?= t('Name') ?></th>
                        <?php if ($isAdmin): ?><th><?= t('Owner') ?></th><?php endif; ?>
                        <th><?= t('Scope') ?></th>
                        <th><?= t('Created') ?></th>
                        <th><?= t('Last used') ?></th>
                        <th><?= t('Actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tokens as $token): ?>
                        <tr>
                            <td class="token-name"><?= e($token['name'] !== '' ? $token['name'] : t('Unnamed')) ?></td>
                            <?php if ($isAdmin): ?><td><?= e($token['username'] ?? '') ?></td><?php endif; ?>
                            <td><span class="badge <?= $token['scope'] === 'write' ? 'badge-write' : 'badge-read' ?>"><?= $token['scope'] === 'write' ? t('Write') : t('Read') ?></span></td>
                            <td><?= e(format_date($token['created_at'])) ?></td>
                            <td>
                                <?= $token['last_used_at'] ? e(format_date($token['last_used_at'])) : '<span class="muted">' . t('Never') . '</span>' ?>
                                <?php if (!empty($token['last_used_ip'])): ?><br><small class="muted"><?= e($token['last_used_ip']) ?></small><?php endif; ?>
                            </td>
                            <td>
                                <form method="post" action="<?= e((($isAdmin) ? '/admin/deploy-tokens/' : '/user/deploy-tokens/') . $token['id'] . '/revoke') ?>" onsubmit="return confirm('<?= t('Revoke this deploy token? Any existing git remote using it will stop working immediately.') ?>')">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-ghost btn-danger" type="submit"><?= t('Revoke') ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="muted"><?= t('No deploy tokens yet. Generate one above to push or clone over HTTP without a password.') ?></p>
        <?php endif; ?>
    </section>
    </div>
</div>

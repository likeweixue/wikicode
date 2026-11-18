<section class="page-heading">
    <div><span class="section-code">REPOSITORY INITIALIZATION / NEW</span><h1><?= t('Initialize a repository') ?></h1><p><?= t('Create a new coordinate for your code and its history.') ?></p></div>
</section>
<div class="split-layout">
    <section class="form-panel tech-panel">
        <div class="panel-topline"><span><?= t('Repository identity') ?></span><b>GIT / BARE / MAIN</b></div>
        <?php if (!empty($error)): ?><div class="form-error"><?= e($error) ?></div><?php endif; ?>
        <form method="post" action="/new" class="stack-form">
            <?= csrf_field() ?>
            <div class="form-grid-two">
                <label><span><?= t('Owner') ?></span><input type="text" value="<?= e($currentUser['username']) ?>" disabled></label>
                <label><span><?= t('Repository name') ?> <i class="required">*</i></span><input type="text" name="name" required maxlength="50" pattern="[A-Za-z0-9][A-Za-z0-9._-]{0,49}" value="<?= e($values['name'] ?? '') ?>" autofocus placeholder="research-project"></label>
            </div>
            <label><span><?= t('Description') ?></span><input type="text" name="description" maxlength="255" value="<?= e($values['description'] ?? '') ?>" placeholder="<?= t('A concise description of this archive') ?>"></label>
            <label><span><?= t('Project homepage') ?></span><input type="url" name="homepage" maxlength="255" value="<?= e($values['homepage'] ?? '') ?>" placeholder="https://example.com"></label>
            <div class="field"><span><?= t('Visibility') ?></span><div class="filter-tabs">
                <label class="checkbox-line"><input type="radio" name="visibility" value="public" <?= ($values['visibility'] ?? 'public') === 'public' ? 'checked' : '' ?>> <?= t('Public') ?> — <?= t('Anyone can read') ?></label>
                <label class="checkbox-line"><input type="radio" name="visibility" value="private" <?= ($values['visibility'] ?? '') === 'private' ? 'checked' : '' ?>> <?= t('Private') ?> — <?= t('Only you and administrators') ?></label>
            </div></div>
            <div class="field"><span><?= t('License') ?></span><select name="license" class="license-select">
                <option value=""><?= t('No license') ?></option>
                <?php $licenses = \App\Services\LicenseService::available(); foreach ($licenses as $id => $info): ?>
                    <option value="<?= e($id) ?>" <?= ($values['license'] ?? '') === $id ? 'selected' : '' ?>><?= e($info['name']) ?></option>
                <?php endforeach; ?>
            </select>
                <small class="field-hint"><?= t('An appropriate LICENSE file will be added to the first commit.') ?></small>
            </div>
            <div class="inline-notice"><?= t('A main branch and an initial README commit will be created automatically. You can clone, pull and push immediately.') ?></div>
            <div class="form-actions"><button class="btn btn-primary" type="submit"><?= t('Create repository') ?> →</button><a class="btn btn-ghost" href="/dashboard"><?= t('Cancel') ?></a></div>
        </form>
    </section>
    <aside class="sidebar-panel">
        <h3>INITIALIZATION PROTOCOL</h3>
        <p><b>01.</b> <?= t('Choose a unique, URL-safe repository name.') ?></p>
        <p><b>02.</b> <?= t('Set who can read the code. Pushing always requires ownership.') ?></p>
        <p><b>03.</b> <?= t('Use the provided HTTP address with any standard Git client.') ?></p>
        <div class="code-content">git clone <?= e(app_url($currentUser['username'] . '/repository.git')) ?></div>
    </aside>
</div>

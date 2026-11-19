<div class="admin-layout">
    <?php require __DIR__ . '/_nav.php'; ?>
    <div>
        <section class="page-heading">
            <div>
                <span class="section-code">CONFIGURATION / INSTANCE</span>
                <h1><?= e(t('Site settings')) ?></h1>
                <p><?= e(t('Configure the public identity, language and registration policy.')) ?></p>
            </div>
        </section>

        <section class="settings-section tech-panel">
            <h2><?= e(t('General settings')) ?></h2>
            <p><?= e(t('These settings apply throughout the wikicode instance.')) ?></p>
            <form method="post" action="/admin/settings/site" class="stack-form">
                <?= csrf_field() ?>
                <div class="settings-grid">
                    <label><span><?= e(t('Site name')) ?> <b class="required">*</b></span><input type="text" name="site_name" value="<?= e($values['site_name']) ?>" required maxlength="100"></label>
                    <label><span><?= e(t('Default language')) ?></span><select name="default_language"><option value="zh-CN" <?= $values['default_language'] === 'zh-CN' ? 'selected' : '' ?>>简体中文</option><option value="en" <?= $values['default_language'] === 'en' ? 'selected' : '' ?>>English</option></select></label>
                    <label class="full"><span><?= e(t('Site description')) ?></span><textarea name="site_description" maxlength="500"><?= e($values['site_description']) ?></textarea><small><?= e(t('A short description shown to visitors.')) ?></small></label>
                    <div class="checkbox-line full"><input id="allow_registration" type="checkbox" name="allow_registration" value="1" <?= $values['allow_registration'] === '1' ? 'checked' : '' ?>><label for="allow_registration"><?= e(t('Allow new users to register')) ?></label></div>
                </div>
                <div class="form-actions"><button class="btn btn-primary" type="submit"><?= e(t('Save site settings')) ?> →</button></div>
            </form>
        </section>

        <section class="settings-section tech-panel">
            <h2><?= e(t('Explore sidebar')) ?></h2>
            <p><?= e(t('Configure the right-hand panel on the Explore page.')) ?></p>
            <form method="post" action="/admin/settings/site" class="stack-form">
                <?= csrf_field() ?>
                <input type="hidden" name="site_name" value="<?= e($values['site_name']) ?>">
                <input type="hidden" name="default_language" value="<?= e($values['default_language']) ?>">
                <input type="hidden" name="site_description" value="<?= e($values['site_description']) ?>">
                <input type="hidden" name="allow_registration" value="<?= e($values['allow_registration']) ?>">
                <div class="settings-grid">
                    <label><span><?= e(t('Hot / featured panel title')) ?></span><input type="text" name="explore_hot_title" value="<?= e($values['explore_hot_title']) ?>" maxlength="50"></label>
                    <label><span><?= e(t('Panel source')) ?></span><select name="explore_hot_mode">
                        <option value="stars" <?= $values['explore_hot_mode'] === 'stars' ? 'selected' : '' ?>><?= e(t('Top starred (auto)')) ?></option>
                        <option value="featured" <?= $values['explore_hot_mode'] === 'featured' ? 'selected' : '' ?>><?= e(t('Featured by admin')) ?></option>
                    </select></label>
                    <label class="full"><span><?= e(t('Featured repository IDs')) ?></span><input type="text" name="explore_featured_repos" value="<?= e($values['explore_featured_repos']) ?>" placeholder="<?= e(t('Comma-separated repository IDs, e.g. 1,3,7')) ?>"><small><?= e(t('Only used when panel source is "Featured by admin".')) ?></small></label>
                </div>
                <div class="form-actions"><button class="btn btn-primary" type="submit"><?= e(t('Save site settings')) ?> →</button></div>
            </form>
        </section>
    </div>
</div>

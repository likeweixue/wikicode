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
    </div>
</div>

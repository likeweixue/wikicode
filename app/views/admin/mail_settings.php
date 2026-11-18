<div class="admin-layout">
    <?php require __DIR__ . '/_nav.php'; ?>
    <div>
        <section class="page-heading">
            <div>
                <span class="section-code">CONFIGURATION / SMTP</span>
                <h1><?= e(t('Mail settings')) ?></h1>
                <p><?= e(t('Configure authenticated SMTP delivery and verify the connection.')) ?></p>
            </div>
        </section>

        <section class="settings-section tech-panel">
            <h2><?= e(t('SMTP server')) ?></h2>
            <p><?= e(t('Use SSL for port 465 or STARTTLS for port 587, according to your provider.')) ?></p>
            <form method="post" action="/admin/settings/mail" class="stack-form">
                <?= csrf_field() ?>
                <div class="settings-grid">
                    <label><span><?= e(t('SMTP host')) ?> <b class="required">*</b></span><input type="text" name="smtp_host" value="<?= e($values['smtp_host']) ?>" required maxlength="255" placeholder="smtp.example.com"></label>
                    <label><span><?= e(t('SMTP port')) ?> <b class="required">*</b></span><input type="number" name="smtp_port" value="<?= e($values['smtp_port']) ?>" required min="1" max="65535"></label>
                    <label><span><?= e(t('Encryption')) ?></span><select name="smtp_encryption"><option value="none" <?= $values['smtp_encryption'] === 'none' ? 'selected' : '' ?>><?= e(t('None')) ?></option><option value="tls" <?= $values['smtp_encryption'] === 'tls' ? 'selected' : '' ?>>STARTTLS</option><option value="ssl" <?= $values['smtp_encryption'] === 'ssl' ? 'selected' : '' ?>>SSL</option></select></label>
                    <label><span><?= e(t('Username')) ?></span><input type="text" name="smtp_username" value="<?= e($values['smtp_username']) ?>" maxlength="190" autocomplete="off"><small><?= e(t('Leave empty when authentication is not required.')) ?></small></label>
                    <label class="full"><span><?= e(t('Password')) ?></span><input type="password" name="smtp_password" value="" autocomplete="new-password" placeholder="<?= e(t('Leave blank to keep the current password')) ?>"><small><?= e($passwordConfigured ? t('An encrypted password is stored. Leave this field blank to keep it.') : t('Leave blank to keep the current password.')) ?></small></label>
                    <label><span><?= e(t('Sender email address')) ?> <b class="required">*</b></span><input type="email" name="smtp_from_address" value="<?= e($values['smtp_from_address']) ?>" required maxlength="190" placeholder="noreply@example.com"></label>
                    <label><span><?= e(t('Sender name')) ?> <b class="required">*</b></span><input type="text" name="smtp_from_name" value="<?= e($values['smtp_from_name']) ?>" required maxlength="100"></label>
                </div>
                <div class="inline-notice"><?= e(t('Passwords are encrypted with APP_KEY before being stored. Connection and read timeouts are limited to 15 seconds.')) ?></div>
                <div class="form-actions"><button class="btn btn-primary" type="submit"><?= e(t('Save mail settings')) ?> →</button></div>
            </form>
        </section>

        <section class="settings-section tech-panel">
            <h2><?= e(t('Connection test')) ?></h2>
            <p><?= e(t('Send a test message to :email using the saved settings.', ['email' => $currentUser['email']])) ?></p>
            <form method="post" action="/admin/settings/mail/test">
                <?= csrf_field() ?>
                <button class="btn" type="submit"><?= e(t('Send test mail')) ?> ↗</button>
            </form>
        </section>
    </div>
</div>

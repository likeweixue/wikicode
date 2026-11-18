<section class="auth-layout">
    <div class="auth-intro">
        <span class="section-code">IDENTITY REGISTRATION / 02</span>
        <h1><?= t('Join the archive.') ?></h1>
        <p><?= t('Create your contributor identity and start building shared knowledge through code.') ?></p>
        <ol class="registration-steps"><li><b>01</b><?= t('Create identity') ?></li><li><b>02</b><?= t('Initialize repository') ?></li><li><b>03</b><?= t('Build together') ?></li></ol>
    </div>
    <div class="auth-card tech-panel">
        <div class="panel-topline"><span><?= t('Create account') ?></span><b>REGISTER / 02</b></div>
        <?php if (!empty($error)): ?><div class="form-error"><?= e($error) ?></div><?php endif; ?>
        <form method="post" action="/register" class="stack-form">
            <?= csrf_field() ?>
            <label><span><?= t('Username') ?></span><input type="text" name="username" value="<?= e($values['username'] ?? '') ?>" required autofocus autocomplete="username" pattern="[A-Za-z0-9][A-Za-z0-9_-]{1,31}" placeholder="joyce.moore"><small><?= t('2–32 letters, numbers, hyphens or underscores.') ?></small></label>
            <label><span><?= t('Email address') ?></span><input type="email" name="email" value="<?= e($values['email'] ?? '') ?>" required autocomplete="email" placeholder="you@example.com"></label>
            <div class="form-grid-two">
                <label><span><?= t('Password') ?></span><input type="password" name="password" required minlength="12" autocomplete="new-password" placeholder="12+ characters"></label>
                <label><span><?= t('Confirm password') ?></span><input type="password" name="password_confirmation" required minlength="12" autocomplete="new-password" placeholder="Repeat password"></label>
            </div>
            <button class="btn btn-primary btn-large btn-block" type="submit"><?= t('Create identity') ?> →</button>
        </form>
        <div class="auth-alternative"><?= t('Already registered?') ?> <a href="/login"><?= t('Sign in') ?> ↗</a></div>
    </div>
</section>

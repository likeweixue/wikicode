<section class="auth-layout">
    <div class="auth-intro">
        <span class="section-code">IDENTITY VERIFICATION / 01</span>
        <h1><?= t('Welcome back.') ?></h1>
        <p><?= t('Authenticate to access your repositories, discussions and merge requests.') ?></p>
        <div class="auth-diagram" aria-hidden="true"><span>AUTH</span><i></i><b>+</b><i></i><span>ACCESS</span></div>
        <small>ENCRYPTED CREDENTIAL CHECK · SESSION PROTECTED</small>
    </div>
    <div class="auth-card tech-panel">
        <div class="panel-topline"><span><?= t('Sign in') ?></span><b>SECURE / 01</b></div>
        <?php if (!empty($error)): ?><div class="form-error"><?= e($error) ?></div><?php endif; ?>
        <form method="post" action="/login" class="stack-form">
            <?= csrf_field() ?>
            <label><span><?= t('Username or email') ?></span><input type="text" name="login" value="<?= e($loginValue ?? '') ?>" required autofocus autocomplete="username" placeholder="joyce.moore"></label>
            <label><span><?= t('Password') ?></span><input type="password" name="password" required autocomplete="current-password" placeholder="••••••••••••"></label>
            <button class="btn btn-primary btn-large btn-block" type="submit"><?= t('Verify identity') ?> →</button>
        </form>
        <div class="auth-alternative"><?= t('New to wikicode?') ?> <a href="/register"><?= t('Create an identity') ?> ↗</a></div>
    </div>
</section>

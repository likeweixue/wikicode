<section class="hero">
    <div class="hero-copy">
        <div class="eyebrow"><span>001</span> DISTRIBUTED DEVELOPMENT SYSTEM</div>
        <h1><?= t('Build code') ?><br><em><?= t('together.') ?></em></h1>
        <p class="hero-lead"><?= t('A collaborative code archive built by everyone. Host repositories, examine ideas and merge progress—all in one precise workspace.') ?></p>
        <div class="hero-actions">
            <a class="btn btn-primary btn-large" href="/register"><?= t('Start building') ?> <span>→</span></a>
            <a class="btn btn-large" href="#explore"><?= t('Explore repositories') ?></a>
        </div>
        <div class="hero-note"><span>ACCESS LEVEL</span><b>PUBLIC / OPEN COLLABORATION</b></div>
    </div>
    <div class="hero-instrument" aria-hidden="true">
        <div class="instrument-label">ARCHIVE NODE / 01</div>
        <div class="crosshair">
            <span class="crosshair-ring ring-one"></span>
            <span class="crosshair-ring ring-two"></span>
            <span class="crosshair-axis axis-x"></span>
            <span class="crosshair-axis axis-y"></span>
            <strong>W/C</strong>
        </div>
        <div class="instrument-readout">
            <div><span>REPOSITORIES</span><b><?= str_pad((string) $stats['repositories'], 3, '0', STR_PAD_LEFT) ?></b></div>
            <div><span>CONTRIBUTORS</span><b><?= str_pad((string) $stats['users'], 3, '0', STR_PAD_LEFT) ?></b></div>
            <div><span>MERGE REQUESTS</span><b><?= str_pad((string) $stats['pull_requests'], 3, '0', STR_PAD_LEFT) ?></b></div>
        </div>
    </div>
</section>

<section class="feature-strip" aria-label="Platform capabilities">
    <article><span>01 / GIT</span><h2><?= t('Version everything') ?></h2><p><?= t('Real Git smart HTTP for cloning, pulling and pushing your work.') ?></p></article>
    <article><span>02 / ISSUES</span><h2><?= t('Discuss clearly') ?></h2><p><?= t('Turn questions and defects into traceable, focused conversations.') ?></p></article>
    <article><span>03 / MERGE</span><h2><?= t('Advance together') ?></h2><p><?= t('Compare branches, review changes and merge with confidence.') ?></p></article>
</section>

<section class="section-block" id="explore">
    <div class="section-heading">
        <div><span class="section-code">PUBLIC ARCHIVE / INDEX</span><h2><?= t('Explore repositories') ?></h2></div>
        <span class="section-count"><?= count($repositories) ?> / <?= $stats['repositories'] ?></span>
    </div>
    <?php $showOwner = true; require __DIR__ . '/components/repository_list.php'; ?>
</section>

<?php require __DIR__ . '/../components/repository_header.php'; ?>
<div class="repo-layout repo-layout-insights">
    <div class="repo-main repo-main-full">
        <div class="insights-grid">
            <div class="insights-card tech-panel">
                <div class="insights-card-title"><span><?= t('Commits') ?></span><b><?= number_format($commits) ?></b></div>
                <p class="insights-sub"><?= t('Across :count branch|branches', ['count' => $branchCount]) ?></p>
            </div>
            <div class="insights-card tech-panel">
                <div class="insights-card-title"><span><?= t('Contributors') ?></span><b><?= count($contributors) ?></b></div>
            </div>
            <div class="insights-card tech-panel">
                <div class="insights-card-title"><span><?= t('Issues') ?></span><b><?= $issueOpen ?></b></div>
                <p class="insights-sub"><?= t(':open open · :closed closed', ['open' => $issueOpen, 'closed' => $issueClosed]) ?></p>
            </div>
            <div class="insights-card tech-panel">
                <div class="insights-card-title"><span><?= t('Pull requests') ?></span><b><?= $prOpen ?></b></div>
                <p class="insights-sub"><?= t(':open open · :merged merged', ['open' => $prOpen, 'merged' => $prMerged]) ?></p>
            </div>
        </div>

        <section class="insights-block tech-panel">
            <div class="section-code">CONTRIBUTION / 52 WEEKS</div>
            <h3><?= t('Commit activity') ?></h3>
            <?php
            $maxWeek = 1;
            if (!empty($weeklyCommits)) {
                $maxWeek = max(1, ...array_values($weeklyCommits));
            }
            $cols = 52;
            for ($i = 0; $i < $cols; $i++) {
                $dt = new \DateTime('-' . ($cols - 1 - $i) . ' weeks');
                $key = $dt->format('Y-W');
                $count = $weeklyCommits[$key] ?? 0;
                $height = $count > 0 ? max(4, round(($count / $maxWeek) * 100)) : 1;
                $label = $count . ' commit' . ($count === 1 ? '' : 's') . ' on week ' . $key;
                echo '<div class="bar-cell" style="height:' . $height . '%" title="' . $label . '"></div>';
            }
            ?>
        </section>

        <section class="insights-block tech-panel">
            <div class="section-code">CONTRIBUTORS</div>
            <h3><?= t('Top contributors') ?></h3>
            <?php if (empty($contributors)): ?>
                <p class="insights-empty"><?= t('No contributors yet.') ?></p>
            <?php else: ?>
                <ul class="contributor-list">
                    <?php $maxC = max(array_column($contributors, 'commits')); foreach ($contributors as $email => $c): ?>
                        <li>
                            <div class="contributor-info">
                                <span class="contributor-avatar"><?= e(mb_substr($c['name'], 0, 1)) ?></span>
                                <span class="contributor-name"><?= e($c['name']) ?></span>
                                <span class="contributor-email muted"><?= e($email) ?></span>
                            </div>
                            <div class="contributor-bar-wrap">
                                <div class="contributor-bar" style="width: <?= round(($c['commits'] / max(1, $maxC)) * 100) ?>%"></div>
                            </div>
                            <span class="contributor-count mono"><?= $c['commits'] ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <?php if (!empty($languages)): ?>
        <section class="insights-block tech-panel">
            <div class="section-code">LANGUAGES</div>
            <h3><?= t('Language breakdown') ?></h3>
            <div class="insights-lang-bar">
                <?php $colors = \App\Services\RepositoryService::languageColors(); foreach ($languages as $lang => $pct): $color = $colors[$lang] ?? '#999'; ?>
                    <span class="lang-segment" style="background: <?= $color ?>; width: <?= min($pct, 100) ?>%"></span>
                <?php endforeach; ?>
            </div>
            <ul class="insights-lang-list">
                <?php foreach ($languages as $lang => $pct): $color = $colors[$lang] ?? '#999'; ?>
                    <li><span class="lang-dot" style="background: <?= $color ?>"></span><b><?= e($lang) ?></b> <span><?= $pct ?>%</span></li>
                <?php endforeach; ?>
            </ul>
        </section>
        <?php endif; ?>
    </div>
</div>

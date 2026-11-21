<?php require __DIR__ . '/../components/repository_header.php'; ?>
<div class="repository-body">
    <div class="repo-toolbar">
        <div class="breadcrumbs">
            <a href="<?= e(repo_url($repository, 'releases')) ?>"><?= t('Releases') ?></a>
            <span>/</span>
            <span><?= t('New release') ?></span>
        </div>
        <span class="section-code">RELEASE / NEW</span>
    </div>

    <?php if (!empty($error)): ?><div class="form-error"><?= e($error) ?></div><?php endif; ?>

    <div class="release-new-layout">
        <section class="tech-panel form-panel wide">
            <div class="panel-topline"><span><?= t('New release') ?></span><b>PUBLISH / TAGGED</b></div>
            <form method="post" action="<?= e(repo_url($repository, 'releases')) ?>" class="stack-form" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <label><span><?= t('Tag name') ?> <i class="required">*</i></span>
                    <input type="text" name="tag" required maxlength="100" list="tag-options" value="<?= e($values['tag']) ?>" placeholder="v1.0.0" autofocus>
                    <datalist id="tag-options"><?php foreach ($tags as $tag): ?><option value="<?= e($tag) ?>"></option><?php endforeach; ?></datalist>
                    <small><?= t('An existing tag can be used, or a new tag will be created on the target branch.') ?></small>
                </label>
                <label><span><?= t('Target branch') ?></span>
                    <select name="branch">
                        <?php foreach ($branches as $branch): ?>
                            <option value="<?= e($branch) ?>" <?= $branch === $values['branch'] ? 'selected' : '' ?>><?= e($branch) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><span><?= t('Release title') ?></span>
                    <input type="text" name="title" maxlength="200" value="<?= e($values['title']) ?>" placeholder="<?= e($values['tag'] !== '' ? $values['tag'] : 'v1.0.0') ?>">
                </label>
                <label><span><?= t('Release notes') ?></span>
                    <textarea name="body" rows="12" placeholder="<?= t('Describe what changed in this release…') ?>"><?= e($values['body']) ?></textarea>
                    <small><?= t('Basic Markdown headings, lists, bold and inline code are supported.') ?></small>
                </label>
                <label><span><?= t('Attach files') ?></span>
                    <label class="file-picker"><input type="file" name="assets[]" multiple><span><?= t('Choose files') ?></span></label>
                    <small><?= t('Optional. Binaries, archives or any release artifacts (up to 64 MB per file).') ?></small>
                </label>
                <div class="form-options">
                    <label class="checkbox-line"><input type="checkbox" name="prerelease" value="1" <?= $values['prerelease'] ? 'checked' : '' ?>><span><?= t('Mark as a pre-release') ?></span></label>
                </div>
                <div class="form-actions">
                    <button class="btn btn-primary" type="submit"><?= t('Publish release') ?> →</button>
                    <a class="btn btn-ghost" href="<?= e(repo_url($repository, 'releases')) ?>"><?= t('Cancel') ?></a>
                </div>
            </form>
        </section>

        <aside class="tech-panel release-tips-panel">
            <div class="panel-topline"><span><?= t('Tips') ?></span><b>GUIDE</b></div>
            <div class="release-tips-body">
                <section class="release-tip">
                    <h4><span class="tip-index">01</span>版本号怎么起？</h4>
                    <p>推荐使用 <a href="https://semver.org/lang/zh-CN/" target="_blank" rel="noopener noreferrer" class="tip-link">语义化版本 2.0.0 ↗</a>，格式为 <code>主版本.次版本.修订号</code>：</p>
                    <p class="tip-mono">v&lt;MAJOR&gt;.&lt;MINOR&gt;.&lt;PATCH&gt;</p>
                    <ul class="tip-list">
                        <li><b>MAJOR</b> 主版本 — 做了不兼容的大改动</li>
                        <li><b>MINOR</b> 次版本 — 加了新功能，向下兼容</li>
                        <li><b>PATCH</b> 修订号 — 修了 bug，向下兼容</li>
                    </ul>
                    <p>例如从 <code>v1.2.3</code> 升到 <code>v1.3.0</code> 表示加了新功能；升到 <code>v2.0.0</code> 则意味着可能不兼容旧版本。</p>
                </section>
                <section class="release-tip">
                    <h4><span class="tip-index">02</span>测试版 / 预发布</h4>
                    <p>在版本号后面加后缀即可标记 beta、RC（候选发布）等测试版本，并勾选下方的 <b>pre-release</b> 复选框：</p>
                    <ul class="tip-list tip-mono">
                        <li>v1.0.0-beta.1</li>
                        <li>v1.0.0-rc.2</li>
                        <li>v1.0.0-alpha</li>
                    </ul>
                    <p>带后缀的版本会自动打上「Pre-release」徽章，不会被认为是稳定版。</p>
                </section>
                <section class="release-tip">
                    <h4><span class="tip-index">03</span>发行说明怎么写</h4>
                    <ul class="tip-list">
                        <li>按 <b>Features / Fixes / Docs</b> 分组列改动</li>
                        <li>用 <code>#123</code> 关联到对应 issue</li>
                        <li>用 <code>@username</code> 感谢贡献者</li>
                    </ul>
                </section>
            </div>
        </aside>
    </div>
</div>

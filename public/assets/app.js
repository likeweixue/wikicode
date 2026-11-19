document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.querySelector('[data-nav-toggle]');
    const nav = document.querySelector('[data-nav]');
    if (toggle && nav) {
        toggle.addEventListener('click', () => {
            const open = nav.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    document.querySelectorAll('[data-dismiss]').forEach((button) => {
        button.addEventListener('click', () => button.closest('[data-flash]')?.remove());
    });

    document.querySelectorAll('[data-copy]').forEach((button) => {
        button.addEventListener('click', async () => {
            const target = document.querySelector(button.dataset.copy);
            if (!target) return;
            const value = 'value' in target ? target.value : target.textContent;
            try {
                await navigator.clipboard.writeText(value);
                const original = button.textContent;
                button.textContent = button.dataset.copied || 'COPIED';
                setTimeout(() => { button.textContent = original; }, 1500);
            } catch (_) {
                target.select?.();
            }
        });
    });

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) event.preventDefault();
        });
    });

    document.querySelectorAll('[data-auto-submit]').forEach((input) => {
        input.addEventListener('change', () => input.form?.submit());
    });

    // 下拉菜单：互斥展开 + 点击外部关闭
    const dropdowns = Array.from(document.querySelectorAll('details.dropdown'));
    dropdowns.forEach((d) => {
        d.addEventListener('toggle', () => {
            if (d.open) dropdowns.forEach((o) => { if (o !== d) o.open = false; });
        });
    });
    document.addEventListener('click', (e) => {
        dropdowns.forEach((d) => { if (d.open && !d.contains(e.target)) d.open = false; });
    });

    // README 目录：默认展开，可折叠
    const tocShell = document.querySelector('[data-readme-shell]');
    const tocToggle = document.querySelector('[data-toc-toggle]');
    if (tocShell && tocToggle) {
        const storageKey = 'wikicode:readme-toc-collapsed';
        const apply = (collapsed) => {
            tocShell.classList.toggle('is-collapsed', collapsed);
            tocToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            try { localStorage.setItem(storageKey, collapsed ? '1' : '0'); } catch (_) {}
        };
        try { apply(localStorage.getItem(storageKey) === '1'); } catch (_) {}
        tocToggle.addEventListener('click', () => {
            apply(!tocShell.classList.contains('is-collapsed'));
        });
    }

    // 项目文档 Tab 切换（README / LICENSE / SECURITY …）
    const docShell = document.querySelector('[data-doc-shell]');
    if (docShell) {
        const tabs = Array.from(docShell.querySelectorAll('[data-doc-tab]'));
        const bodies = Array.from(docShell.querySelectorAll('[data-doc-body]'));
        const tocs = Array.from(docShell.querySelectorAll('[data-doc-toc]'));
        const titleEl = docShell.querySelector('[data-doc-title]');
        const editBtn = docShell.querySelector('[data-doc-edit]');
        const activate = (key, path) => {
            tabs.forEach(t => {
                const active = t.dataset.docTab === key;
                t.classList.toggle('is-active', active);
                t.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
            bodies.forEach(b => {
                const active = b.dataset.docBody === key;
                b.classList.toggle('is-active', active);
                b.hidden = !active;
            });
            tocs.forEach(t => {
                const active = t.dataset.docToc === key;
                t.classList.toggle('is-active', active);
                t.hidden = !active;
            });
            if (titleEl && path) {
                titleEl.textContent = path + ' / PROJECT DOCUMENT';
            }
            if (editBtn && path) {
                const base = editBtn.dataset.editBase || '';
                editBtn.setAttribute('href', base + encodeURIComponent(path));
                editBtn.dataset.docEdit = path;
                editBtn.setAttribute('title', 'Edit: ' + path);
            }
            // 记录选中的 doc，下次刷新仍停留在那个 tab
            try {
                const url = new URL(window.location.href);
                url.searchParams.set('doc', key);
                window.history.replaceState(null, '', url.toString());
            } catch (_) {}
            // 滚动到文档面板顶部（如果当前视口已经在 markdown 里）
            if (window.scrollY > 400) {
                const top = docShell.getBoundingClientRect().top + window.scrollY - 40;
                window.scrollTo({ top, behavior: 'smooth' });
            }
        };
        tabs.forEach(t => {
            t.addEventListener('click', () => activate(t.dataset.docTab, t.dataset.docPath));
        });
    }

    // 新建文件页：许可证模板搜索与插入
    const licenseHelper = document.querySelector('[data-license-helper]');
    const licenseData = document.getElementById('license-data');
    if (licenseHelper && licenseData) {
        let licenses = [];
        try { licenses = JSON.parse(licenseData.textContent || '[]'); } catch (_) {}
        const searchInput = licenseHelper.querySelector('[data-license-search]');
        const results = licenseHelper.querySelector('[data-license-results]');
        const pathInput = document.querySelector('input[name="file_path"]');
        const contentInput = document.querySelector('textarea[name="content"]');
        const messageInput = document.querySelector('input[name="message"]');
        const renderResults = () => {
            const query = searchInput.value.trim().toLowerCase();
            results.innerHTML = '';
            if (!query) return;
            licenses
                .filter((item) => (item.name + ' ' + item.id).toLowerCase().includes(query))
                .slice(0, 6)
                .forEach((item) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'license-card';
                    const title = document.createElement('strong');
                    title.textContent = item.name;
                    const meta = document.createElement('span');
                    meta.textContent = item.id.toUpperCase();
                    button.append(title, meta);
                    button.addEventListener('click', () => {
                        if (pathInput && pathInput.value.trim() === '') pathInput.value = 'LICENSE';
                        if (contentInput) contentInput.value = item.body;
                        if (messageInput && messageInput.value.trim() === '') messageInput.value = 'Add ' + item.name;
                        searchInput.value = item.name;
                        results.innerHTML = '';
                    });
                    results.appendChild(button);
                });
        };
        searchInput.addEventListener('input', renderResults);
    }
});

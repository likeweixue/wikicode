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
});

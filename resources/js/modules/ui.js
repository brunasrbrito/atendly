/** Pequenos comportamentos de interface. */
export function initUi() {
    // Menu lateral no celular (aria-expanded informa o estado ao leitor de tela; Esc fecha)
    const sidebar = document.querySelector('.sidebar');
    const menuButton = document.querySelector('.topbar__menu');
    const setMenu = (open) => {
        sidebar?.classList.toggle('is-open', open);
        menuButton?.setAttribute('aria-expanded', String(open));
        if (open) sidebar?.querySelector('a')?.focus();
    };
    document.querySelectorAll('[data-toggle-sidebar]').forEach((btn) =>
        btn.addEventListener('click', () => setMenu(!sidebar?.classList.contains('is-open'))),
    );
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sidebar?.classList.contains('is-open')) {
            setMenu(false);
            menuButton?.focus();
        }
    });

    // Liga cada mensagem de erro ao seu campo: o leitor de tela anuncia o erro ao focar.
    document.querySelectorAll('.field__error[id]').forEach((error) => {
        const control = error.closest('.field')?.querySelector('input:not([type=hidden]), select, textarea');
        if (!control) return;
        control.setAttribute('aria-invalid', 'true');
        control.setAttribute('aria-describedby', [control.getAttribute('aria-describedby'), error.id].filter(Boolean).join(' '));
    });
    document.querySelector('[aria-invalid=true]')?.focus();

    // Atalho "/" foca a busca de soluções (fora de campos de texto)
    const search = document.querySelector('[data-search-shortcut]');
    document.addEventListener('keydown', (e) => {
        if (!search || e.key !== '/' || e.ctrlKey || e.metaKey) return;
        if (e.target.closest('input, textarea, select, [contenteditable]')) return;
        e.preventDefault();
        search.focus();
    });

    // Diálogos: <button data-open-dialog="#id">, fecha com [data-close-dialog] ou Esc.
    document.querySelectorAll('[data-open-dialog]').forEach((btn) =>
        btn.addEventListener('click', () => document.querySelector(btn.dataset.openDialog)?.showModal()),
    );
    document.querySelectorAll('dialog').forEach((dialog) => {
        dialog.querySelectorAll('[data-close-dialog]').forEach((btn) => btn.addEventListener('click', () => dialog.close()));
        // Clique fora do conteúdo fecha
        dialog.addEventListener('click', (e) => {
            if (e.target === dialog) dialog.close();
        });
        // Abre direto quando a URL aponta para ele (#resolver) ou houve erro de validação
        if (dialog.hasAttribute('data-open-on-load') || (dialog.id && location.hash === `#${dialog.id}`)) {
            dialog.showModal();
        }
    });

    // Confirmação antes de ações destrutivas
    document.querySelectorAll('form[data-confirm]').forEach((form) =>
        form.addEventListener('submit', (e) => {
            if (!confirm(form.dataset.confirm)) e.preventDefault();
        }),
    );

    // Lista os arquivos escolhidos ao lado do input
    document.querySelectorAll('input[type=file][data-file-list]').forEach((input) => {
        const target = document.querySelector(input.dataset.fileList);
        input.addEventListener('change', () => {
            target.textContent = [...input.files].map((f) => f.name).join(', ');
        });
    });

    // Tema claro/escuro
    const root = document.documentElement;
    try {
        const saved = localStorage.getItem('atendly:theme');
        if (saved) root.dataset.theme = saved;
    } catch {
        /* sem armazenamento */
    }
    document.querySelectorAll('[data-toggle-theme]').forEach((btn) =>
        btn.addEventListener('click', () => {
            const dark = root.dataset.theme
                ? root.dataset.theme === 'dark'
                : matchMedia('(prefers-color-scheme: dark)').matches;
            root.dataset.theme = dark ? 'light' : 'dark';
            try {
                localStorage.setItem('atendly:theme', root.dataset.theme);
            } catch {
                /* sem armazenamento */
            }
        }),
    );
}

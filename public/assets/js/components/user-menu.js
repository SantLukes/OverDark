/**
 * Menu do usuário no header (avatar + dropdown).
 *
 * Marcação esperada:
 *   [data-user-menu] > [data-user-menu-toggle] + [data-user-menu-dropdown]
 */
const menu = document.querySelector('[data-user-menu]');

if (menu) {
    const toggle = menu.querySelector('[data-user-menu-toggle]');
    const dropdown = menu.querySelector('[data-user-menu-dropdown]');

    const close = () => {
        menu.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        dropdown.hidden = true;
    };

    const open = () => {
        dropdown.hidden = false;

        // Espera um frame para a transição de opacidade/escala acontecer.
        requestAnimationFrame(() => {
            menu.classList.add('is-open');
            toggle.setAttribute('aria-expanded', 'true');
        });
    };

    toggle.addEventListener('click', (event) => {
        event.stopPropagation();
        menu.classList.contains('is-open') ? close() : open();
    });

    document.addEventListener('click', (event) => {
        if (!menu.contains(event.target)) {
            close();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && menu.classList.contains('is-open')) {
            close();
            toggle.focus();
        }
    });
}

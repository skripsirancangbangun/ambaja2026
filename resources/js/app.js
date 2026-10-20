
import Alpine from 'alpinejs';
import { createIcons, icons } from 'lucide';

window.Alpine = Alpine;

Alpine.start();

/**
 * Render ikon Lucide pada elemen data-lucide.
 */
function renderLucideIcons(root = document) {
    createIcons({
        icons,
        root,
        attrs: {
            'stroke-width': 2,
        },
    });
}

window.renderLucideIcons = renderLucideIcons;

/**
 * Perbaiki interaksi submenu sidebar.
 * Submenu dibuka atau ditutup dengan satu kali klik.
 */
function initializeSidebarSubmenus() {
    const sidebar = document.querySelector('aside');

    if (!sidebar || sidebar.dataset.submenuInitialized === 'true') {
        return;
    }

    sidebar.dataset.submenuInitialized = 'true';

    sidebar.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const summary = event.target.closest('summary');

        if (!summary || !sidebar.contains(summary)) {
            return;
        }

        const details = summary.parentElement;

        if (!(details instanceof HTMLDetailsElement)) {
            return;
        }

        // Cegah toggle bawaan browser agar tidak terjadi toggle ganda.
        event.preventDefault();

        // Buka atau tutup submenu secara eksplisit.
        details.open = !details.open;
    });
}

/**
 * Inisialisasi setelah DOM tersedia.
 */
function initializeAppUI() {
    initializeSidebarSubmenus();
    renderLucideIcons();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeAppUI, {
        once: true,
    });
} else {
    initializeAppUI();
}

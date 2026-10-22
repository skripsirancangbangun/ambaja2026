
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
 * Searchable dropdown untuk pilihan barang.
 *
 * Select asli tetap menjadi sumber nilai form, sehingga name/value, validasi
 * server, dan event change (termasuk Alpine x-model) tetap dipakai.
 * Mendukung select yang dibuat secara dinamis oleh Alpine/JavaScript.
 */
function initializeSearchableBarangSelect(select) {
    if (!(select instanceof HTMLSelectElement)
        || select.multiple
        || select.dataset.searchableInitialized === 'true'
        || !select.matches([
            'select#barang_input_id',
            'select#barang_id',
            'select[name="barang_input_id"]',
            'select[name="barang_id"]',
            'select[name$="[barang_id]"]',
            'select.item-barang',
            'select.output-barang',
            'select[data-searchable="barang"]',
        ].join(','))) {
        return;
    }

    select.dataset.searchableInitialized = 'true';

    const wasRequired = select.required;
    const wasDisabled = select.disabled;
    const originalId = select.id;
    const wrapper = document.createElement('div');
    wrapper.className = 'ambaja-search-select';
    wrapper.style.width = select.classList.contains('w-full') ? '100%' : '';
    wrapper.style.maxWidth = '100%';

    // Preserve fixed-width controls such as the item selector in purchase orders.
    if (!select.classList.contains('w-full')) {
        const currentWidth = window.getComputedStyle(select).width;
        if (currentWidth && currentWidth !== 'auto') {
            wrapper.style.width = currentWidth;
        } else {
            wrapper.style.width = '100%';
        }
    }

    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'ambaja-search-select-input';
    input.autocomplete = 'off';
    input.spellcheck = false;
    input.required = wasRequired;
    input.placeholder = 'Cari nama atau kode barang...';
    input.setAttribute('role', 'combobox');
    input.setAttribute('aria-autocomplete', 'list');
    input.setAttribute('aria-expanded', 'false');

    const inputId = originalId
        ? `${originalId}-search`
        : `ambaja-barang-search-${++initializeSearchableBarangSelect.nextId}`;
    input.id = inputId;
    if (originalId) {
        input.setAttribute('aria-controls', `${inputId}-listbox`);
    }

    const label = originalId
        ? Array.from(document.querySelectorAll('label[for]'))
            .find((candidate) => candidate.htmlFor === originalId)
        : null;
    if (label) {
        input.setAttribute('aria-label', label.textContent.trim());
        label.htmlFor = inputId;
    } else {
        input.setAttribute('aria-label', 'Cari nama atau kode barang');
    }

    const listbox = document.createElement('div');
    listbox.className = 'ambaja-search-select-options';
    listbox.id = `${inputId}-listbox`;
    listbox.setAttribute('role', 'listbox');
    listbox.hidden = true;

    const hint = document.createElement('div');
    hint.className = 'ambaja-search-select-hint';
    hint.textContent = 'Ketik nama atau kode barang untuk mencari.';
    listbox.appendChild(hint);

    wrapper.append(input, listbox);
    select.parentNode.insertBefore(wrapper, select);
    wrapper.appendChild(select);

    // Keep the real select in the form, but expose the accessible searchable UI.
    select.classList.add('ambaja-search-select-source');
    select.tabIndex = -1;
    select.setAttribute('aria-hidden', 'true');
    select.required = false;

    const state = {
        isTyping: false,
        open: false,
        activeIndex: -1,
        suppressSync: false,
        options: [],
    };

    function selectedOption() {
        return select.options[select.selectedIndex] || null;
    }

    function optionLabel(option) {
        return (option?.textContent || '').replace(/\s+/g, ' ').trim();
    }

    function syncFromSelect() {
        if (state.suppressSync || state.isTyping) return;
        const selected = selectedOption();
        input.value = selected && selected.value !== '' ? optionLabel(selected) : '';
        input.setCustomValidity('');
        input.disabled = select.disabled;
        input.setAttribute('aria-expanded', String(state.open));
    }

    function closeList() {
        state.open = false;
        state.activeIndex = -1;
        listbox.hidden = true;
        input.setAttribute('aria-expanded', 'false');
        input.removeAttribute('aria-activedescendant');
    }

    function chooseOption(option) {
        if (!option || option.disabled || option.value === '') return;

        select.value = option.value;
        state.isTyping = false;
        input.value = optionLabel(option);
        input.setCustomValidity('');
        state.suppressSync = false;
        select.dispatchEvent(new Event('change', { bubbles: true }));
        closeList();
    }

    function renderOptions() {
        const query = state.isTyping ? input.value.trim().toLocaleLowerCase('id-ID') : '';
        const matching = state.options.filter((option) => {
            if (option.disabled || option.value === '') return false;
            const label = optionLabel(option).toLocaleLowerCase('id-ID');
            const searchData = (option.dataset.search || '').toLocaleLowerCase('id-ID');
            return !query || label.includes(query) || searchData.includes(query);
        });

        listbox.replaceChildren();
        state.activeIndex = -1;

        if (matching.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'ambaja-search-select-empty';
            empty.textContent = query
                ? 'Barang tidak ditemukan. Coba kata kunci lain.'
                : 'Belum ada pilihan barang.';
            listbox.appendChild(empty);
        } else {
            matching.slice(0, 150).forEach((option, index) => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'ambaja-search-select-option';
                item.setAttribute('role', 'option');
                item.id = `${inputId}-option-${index}`;
                item.dataset.optionValue = option.value;
                item.textContent = optionLabel(option);
                item.setAttribute('aria-selected', String(option.value === select.value));
                item.addEventListener('mousedown', (event) => event.preventDefault());
                item.addEventListener('click', () => chooseOption(option));
                listbox.appendChild(item);
            });

            if (matching.length > 150) {
                const more = document.createElement('div');
                more.className = 'ambaja-search-select-hint';
                more.textContent = `Menampilkan 150 dari ${matching.length} hasil. Perjelas kata kunci.`;
                listbox.appendChild(more);
            }
        }

        listbox.hidden = !state.open;
        input.setAttribute('aria-expanded', String(state.open));
    }

    function refreshOptions() {
        state.options = Array.from(select.options);
        if (!state.isTyping) syncFromSelect();
        renderOptions();
    }

    input.addEventListener('focus', () => {
        if (select.disabled) return;
        state.open = true;
        state.isTyping = false;
        renderOptions();
        if (select.value) input.select();
    });

    input.addEventListener('click', () => {
        if (select.disabled) return;
        state.open = true;
        renderOptions();
    });

    input.addEventListener('input', () => {
        state.isTyping = true;
        state.open = true;

        if (select.value !== '') {
            select.value = '';
            state.suppressSync = true;
            select.dispatchEvent(new Event('change', { bubbles: true }));
            state.suppressSync = false;
        }

        input.setCustomValidity(
            wasRequired ? 'Pilih barang dari daftar hasil pencarian.' : ''
        );
        renderOptions();
    });

    input.addEventListener('keydown', (event) => {
        const options = Array.from(listbox.querySelectorAll('[role="option"]'));
        if (event.key === 'Escape') {
            closeList();
            syncFromSelect();
            input.blur();
            return;
        }

        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            state.open = true;
            if (options.length === 0) {
                renderOptions();
                return;
            }
            const direction = event.key === 'ArrowDown' ? 1 : -1;
            state.activeIndex = state.activeIndex < 0
                ? (direction === 1 ? 0 : options.length - 1)
                : (state.activeIndex + direction + options.length) % options.length;

            options.forEach((option, index) => {
                option.classList.toggle('is-active', index === state.activeIndex);
            });
            const active = options[state.activeIndex];
            input.setAttribute('aria-activedescendant', active.id);
            active.scrollIntoView({ block: 'nearest' });
            listbox.hidden = false;
        }

        if (event.key === 'Enter' && state.open) {
            const active = options[state.activeIndex];
            if (active) {
                event.preventDefault();
                const option = state.options.find((candidate) =>
                    candidate.value === active.dataset.optionValue
                );
                chooseOption(option);
            }
        }
    });

    input.addEventListener('blur', () => {
        window.setTimeout(() => {
            if (!wrapper.contains(document.activeElement)) {
                closeList();
                if (!state.isTyping) syncFromSelect();
            }
        }, 120);
    });

    select.addEventListener('change', syncFromSelect);
    select.addEventListener('ambaja:options-updated', refreshOptions);

    // Keep required validation tied to an actual selected option, not free text.
    const form = select.closest('form');
    if (form && wasRequired && !form.dataset.searchableBarangValidation) {
        form.dataset.searchableBarangValidation = 'true';
        form.addEventListener('submit', (event) => {
            const invalidInput = Array.from(form.querySelectorAll(
                'select.ambaja-search-select-source'
            )).find((candidate) =>
                candidate.required === false &&
                candidate.dataset.searchableInitialized === 'true' &&
                candidate.dataset.wasRequired === 'true' &&
                candidate.value === ''
            );
            if (invalidInput) {
                event.preventDefault();
                const searchInput = invalidInput.parentElement.querySelector(
                    '.ambaja-search-select-input'
                );
                searchInput.setCustomValidity('Pilih barang dari daftar hasil pencarian.');
                searchInput.reportValidity();
                searchInput.focus();
            }
        }, true);
    }
    if (wasRequired) select.dataset.wasRequired = 'true';

    refreshOptions();
}

initializeSearchableBarangSelect.nextId = 0;

function initializeSearchableBarangSelects(root = document) {
    if (root instanceof HTMLSelectElement) {
        initializeSearchableBarangSelect(root);
        return;
    }
    if (!(root instanceof Element || root instanceof Document)) return;

    root.querySelectorAll([
        'select#barang_input_id',
        'select#barang_id',
        'select[name="barang_input_id"]',
        'select[name="barang_id"]',
        'select[name$="[barang_id]"]',
        'select.item-barang',
        'select.output-barang',
        'select[data-searchable="barang"]',
    ].join(',')).forEach(initializeSearchableBarangSelect);
}

function observeDynamicBarangSelects() {
    if (!document.body || document.body.dataset.barangSelectObserver === 'true') return;
    document.body.dataset.barangSelectObserver = 'true';

    const observer = new MutationObserver((records) => {
        records.forEach((record) => {
            if (record.type === 'childList') {
                record.addedNodes.forEach((node) => {
                    if (node instanceof Element) {
                        initializeSearchableBarangSelects(node);
                    }
                });
            }

            if (record.target instanceof HTMLSelectElement
                && record.target.dataset.searchableInitialized === 'true') {
                const wrapper = record.target.parentElement;
                if (wrapper?.classList.contains('ambaja-search-select')) {
                    const input = wrapper.querySelector('.ambaja-search-select-input');
                    const options = Array.from(record.target.options);
                    const query = input?.value || '';
                    const isTyping = input && document.activeElement === input && query !== '';
                    if (!isTyping) {
                        input.value = record.target.value
                            ? (record.target.selectedOptions[0]?.textContent || '').trim()
                            : '';
                    }
                    // Trigger the wrapper's option renderer after options are changed.
                    record.target.dispatchEvent(new Event('ambaja:options-updated'));
                }
            }
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['disabled'],
    });
}


/**
 * Inisialisasi setelah DOM tersedia.
 */
function initializeAppUI() {
    initializeSidebarSubmenus();
    initializeSearchableBarangSelects();
    observeDynamicBarangSelects();
    renderLucideIcons();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeAppUI, {
        once: true,
    });
} else {
    initializeAppUI();
}

{{-- Modal pencarian barang berdasarkan supplier terpilih. --}}
<div id="barang-picker-modal" class="fixed inset-0 z-[100] hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-950/50" data-barang-picker-close></div>

    <div class="relative mx-auto my-5 flex max-h-[calc(100vh-2.5rem)] w-[calc(100%-2rem)] max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl sm:my-8">
        <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4 sm:px-6">
            <div>
                <h3 class="text-base font-semibold text-slate-800">Pilih Barang Supplier</h3>
                <p id="barang-picker-supplier" class="mt-1 text-xs text-slate-500">Pilih supplier terlebih dahulu.</p>
            </div>
            <button type="button" data-barang-picker-close class="rounded-lg px-3 py-1.5 text-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Tutup">&times;</button>
        </div>

        <div class="space-y-3 border-b border-slate-100 px-5 py-4 sm:px-6">
            <div class="relative">
                <x-icon name="search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <input
                    type="search"
                    id="barang-picker-search"
                    placeholder="Cari kode, nama, spesifikasi, atau merek barang..."
                    autocomplete="off"
                    class="w-full rounded-xl border border-slate-300 py-3 pl-10 pr-4 text-sm outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                >
            </div>
            <div class="flex flex-col gap-1 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                <span>Stok ditampilkan sebagai total seluruh gudang dan rincian per gudang.</span>
                <a id="barang-picker-manage-link" href="#" target="_blank" rel="noopener" class="font-semibold text-teal-700 hover:underline">Kelola daftar barang supplier</a>
            </div>
        </div>

        <div id="barang-picker-results" class="min-h-0 flex-1 overflow-y-auto px-5 py-3 sm:px-6" aria-live="polite">
            <div class="py-10 text-center text-sm text-slate-400">Memuat daftar barang...</div>
        </div>

        <div class="flex items-center justify-between border-t border-slate-200 bg-slate-50 px-5 py-3 sm:px-6">
            <p id="barang-picker-count" class="text-xs text-slate-500">-</p>
            <div class="flex items-center gap-2">
                <button type="button" id="barang-picker-prev" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-600 disabled:cursor-not-allowed disabled:opacity-40">Sebelumnya</button>
                <span id="barang-picker-page" class="min-w-16 text-center text-xs text-slate-500">Halaman 1</span>
                <button type="button" id="barang-picker-next" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-600 disabled:cursor-not-allowed disabled:opacity-40">Berikutnya</button>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const selectedBarangData = @json($selectedBarangData ?? []);
    const initialItems = @json($poInitialItems ?? $initialItems ?? []);
    const isRequestBasedPO = @json($isRequestBasedPO ?? false);
    const supplierBarangUrlTemplate = @json(route('purchase-order.supplier-barangs', ['supplier' => '__SUPPLIER__']));
    const supplierManageUrlTemplate = @json(route('supplier.barang.index', ['supplier' => '__SUPPLIER__']));

    const supplierSelect = document.getElementById('supplier_id');
    const addItemButton = document.getElementById('add-item');
    const container = document.getElementById('items-container');
    const modal = document.getElementById('barang-picker-modal');
    const searchInput = document.getElementById('barang-picker-search');
    const resultsElement = document.getElementById('barang-picker-results');
    const supplierLabel = document.getElementById('barang-picker-supplier');
    const manageLink = document.getElementById('barang-picker-manage-link');
    const countElement = document.getElementById('barang-picker-count');
    const pageElement = document.getElementById('barang-picker-page');
    const previousButton = document.getElementById('barang-picker-prev');
    const nextButton = document.getElementById('barang-picker-next');

    if (!supplierSelect || !container || !modal) {
        return;
    }

    const barangCache = new Map(
        selectedBarangData.map((barang) => [String(barang.id), barang])
    );

    let itemIndex = 0;
    let activeRow = null;
    let currentPage = 1;
    let lastPage = 1;
    let searchTimer = null;
    let requestSequence = 0;
    let previousSupplierId = supplierSelect.value;

    function escapeHtml(value) {
        if (value === null || value === undefined) return '';

        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function formatRupiah(value) {
        return new Intl.NumberFormat('id-ID', {
            maximumFractionDigits: 2,
        }).format(Number(value) || 0);
    }

    function formatQuantity(value) {
        return new Intl.NumberFormat('id-ID', {
            maximumFractionDigits: 2,
        }).format(Number(value) || 0);
    }

    function barangLabel(barang) {
        if (!barang) return 'Pilih barang dari supplier';
        return `${barang.kode} - ${barang.nama}`;
    }

    function barangDescription(barang) {
        if (!barang) return 'Klik untuk mencari barang';

        return [barang.spesifikasi, barang.merek, barang.satuan]
            .filter(Boolean)
            .join(' • ') || 'Tidak ada spesifikasi tambahan';
    }

    function stockDescription(barang) {
        if (!barang) return 'Stok belum dipilih';

        const total = `Stok total: ${formatQuantity(barang.stok_total)} ${barang.satuan || ''}`.trim();
        const warehouses = (barang.stok_by_warehouse || [])
            .map((stock) => `${stock.kode}: ${formatQuantity(stock.qty)}`)
            .join(' • ');

        return warehouses ? `${total} | ${warehouses}` : `${total} | Belum ada saldo per gudang`;
    }

    function findBarang(barangId) {
        return barangCache.get(String(barangId)) || null;
    }

    function updateHargaReference(row, barang) {
        const reference = row.querySelector('.harga-reference');
        if (!reference) return;

        if (!barang) {
            reference.textContent = '';
            return;
        }

        reference.textContent = Number(barang.harga_beli_default) > 0
            ? `Harga default: Rp ${formatRupiah(barang.harga_beli_default)}`
            : 'Harga beli default belum tersedia.';
    }

    function updateRowBarang(row, barang) {
        const hiddenInput = row.querySelector('.item-barang');
        const title = row.querySelector('.item-picker-title');
        const description = row.querySelector('.item-picker-description');
        const stock = row.querySelector('.item-picker-stock');
        const pickerButton = row.querySelector('.item-picker');

        hiddenInput.value = barang ? barang.id : '';
        title.textContent = barangLabel(barang);
        description.textContent = barangDescription(barang);
        stock.textContent = stockDescription(barang);
        pickerButton.classList.toggle('border-teal-300', Boolean(barang));
        pickerButton.classList.toggle('bg-teal-50/50', Boolean(barang));
        updateHargaReference(row, barang);
    }

    function bindRow(row) {
        row.querySelector('.item-picker').addEventListener('click', () => openBarangPicker(row));

        row.querySelectorAll('.item-qty, .item-harga, .item-diskon').forEach((input) => {
            input.addEventListener('input', calculateTotals);
        });

        row.querySelector('.remove-item').addEventListener('click', () => {
            const rows = container.querySelectorAll('.po-item-row');

            if (rows.length <= 1) {
                alert('Purchase Order minimal memiliki satu barang.');
                return;
            }

            row.remove();
            calculateTotals();
        });
    }

    function addItem(data = {}) {
        const index = itemIndex++;
        const barangId = data.barang_id ?? '';
        const qty = data.qty ?? 1;
        const harga = data.harga_satuan ?? 0;
        const diskon = data.diskon ?? 0;
        const keterangan = data.keterangan ?? '';
        const barang = barangId ? findBarang(barangId) : null;

        const row = document.createElement('tr');
        row.className = 'po-item-row';
        row.innerHTML = `
            <td class="px-4 py-3 align-top">
                <input type="hidden" name="items[${index}][barang_id]" class="item-barang" value="${escapeHtml(barangId)}">
                <button type="button" class="item-picker w-[390px] max-w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-left outline-none transition hover:border-[#32AAA6] focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100" ${isRequestBasedPO ? 'disabled' : ''}>
                    <span class="item-picker-title block truncate text-sm font-medium text-slate-700">${escapeHtml(barangLabel(barang))}</span>
                    <span class="item-picker-description mt-0.5 block truncate text-[11px] text-slate-400">${escapeHtml(barangDescription(barang))}</span>
                    <span class="item-picker-stock mt-1 block text-[10px] text-slate-500">${escapeHtml(stockDescription(barang))}</span>
                </button>
            </td>
            <td class="px-4 py-3 align-top">
                <input type="number" name="items[${index}][qty]" min="0.01" step="0.01" value="${escapeHtml(qty)}" ${isRequestBasedPO ? 'readonly' : ''} class="item-qty w-24 rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100" required>
            </td>
            <td class="px-4 py-3 align-top">
                <div>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">Rp</span>
                        <input type="number" name="items[${index}][harga_satuan]" min="0" step="0.01" value="${escapeHtml(harga)}" class="item-harga w-40 rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-sm text-slate-700 outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100" required>
                    </div>
                    <p class="harga-reference mt-1 text-[10px] text-slate-400"></p>
                </div>
            </td>
            <td class="px-4 py-3 align-top">
                <input type="number" name="items[${index}][diskon]" min="0" step="0.01" value="${escapeHtml(diskon)}" class="item-diskon w-32 rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100">
            </td>
            <td class="item-subtotal whitespace-nowrap px-4 py-5 align-top font-medium text-slate-700">Rp 0</td>
            <td class="px-4 py-3 align-top">
                <input type="text" name="items[${index}][keterangan]" value="${escapeHtml(keterangan)}" placeholder="Opsional" class="w-48 rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100">
            </td>
            <td class="px-4 py-3 text-right align-top">
                <button type="button" class="remove-item rounded-lg px-3 py-2 text-xs font-medium text-red-500 transition hover:bg-red-50 ${isRequestBasedPO ? 'hidden' : ''}">Hapus</button>
            </td>
        `;

        container.appendChild(row);
        bindRow(row);

        if (barang) {
            updateRowBarang(row, barang);
        }

        calculateTotals();
        return row;
    }

    function selectedInAnotherRow(barangId, row) {
        return Array.from(container.querySelectorAll('.po-item-row')).some((candidate) => {
            if (candidate === row) return false;
            return candidate.querySelector('.item-barang')?.value === String(barangId);
        });
    }

    function openBarangPicker(row) {
        if (isRequestBasedPO) return;

        const supplierId = supplierSelect.value;
        if (!supplierId) {
            alert('Pilih supplier terlebih dahulu sebelum memilih barang.');
            supplierSelect.focus();
            return;
        }

        activeRow = row;
        searchInput.value = '';
        currentPage = 1;
        supplierLabel.textContent = `Supplier: ${supplierSelect.options[supplierSelect.selectedIndex]?.text || ''}`;
        manageLink.href = supplierManageUrlTemplate.replace('__SUPPLIER__', encodeURIComponent(supplierId));
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
        loadBarangResults(1);
        searchInput.focus();
    }

    function closeBarangPicker() {
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('overflow-hidden');
        activeRow = null;
    }

    function renderResults(items) {
        if (!items.length) {
            resultsElement.innerHTML = `
                <div class="py-12 text-center">
                    <p class="text-sm font-medium text-slate-600">Tidak ada barang yang cocok.</p>
                    <p class="mt-1 text-xs text-slate-400">Pastikan barang sudah didaftarkan pada supplier yang dipilih.</p>
                    <a href="${escapeHtml(manageLink.href)}" target="_blank" rel="noopener" class="mt-3 inline-flex text-xs font-semibold text-teal-700 hover:underline">Kelola daftar barang supplier</a>
                </div>
            `;
            return;
        }

        resultsElement.innerHTML = items.map((barang) => {
            const isAlreadySelected = selectedInAnotherRow(barang.id, activeRow);
            const stockDetails = (barang.stok_by_warehouse || [])
                .map((stock) => `${stock.kode}: ${formatQuantity(stock.qty)}`)
                .join(' • ') || 'Belum ada saldo per gudang';
            const specs = [barang.spesifikasi, barang.merek, barang.satuan]
                .filter(Boolean)
                .join(' • ');

            return `
                <div class="flex flex-col gap-3 border-b border-slate-100 py-4 last:border-b-0 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0 flex-1">
                        <div class="text-xs font-semibold text-[#15235F]">${escapeHtml(barang.kode)}</div>
                        <div class="mt-0.5 text-sm font-semibold text-slate-800">${escapeHtml(barang.nama)}</div>
                        <div class="mt-1 text-xs text-slate-400">${escapeHtml(specs || 'Tidak ada spesifikasi tambahan')}</div>
                        <div class="mt-2 text-[11px] text-slate-500">${escapeHtml(stockDetails)}</div>
                    </div>
                    <div class="flex shrink-0 items-center justify-between gap-4 sm:justify-end">
                        <div class="text-right">
                            <div class="text-xs text-slate-400">Stok total</div>
                            <div class="text-base font-semibold tabular-nums text-slate-800">${formatQuantity(barang.stok_total)} <span class="text-xs font-normal text-slate-500">${escapeHtml(barang.satuan || '')}</span></div>
                            <div class="mt-1 text-[10px] text-slate-400">Harga default: Rp ${formatRupiah(barang.harga_beli_default)}</div>
                        </div>
                        <button type="button" data-select-barang="${Number(barang.id)}" ${isAlreadySelected ? 'disabled' : ''} class="rounded-lg bg-teal-50 px-4 py-2 text-xs font-semibold text-[#15235F] hover:bg-teal-100 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400">${isAlreadySelected ? 'Sudah dipilih' : 'Pilih'}</button>
                    </div>
                </div>
            `;
        }).join('');
    }

    async function loadBarangResults(page = 1) {
        const supplierId = supplierSelect.value;
        if (!supplierId) return;

        const sequence = ++requestSequence;
        currentPage = page;
        resultsElement.innerHTML = '<div class="py-10 text-center text-sm text-slate-400">Memuat daftar barang...</div>';

        const url = new URL(
            supplierBarangUrlTemplate.replace('__SUPPLIER__', encodeURIComponent(supplierId)),
            window.location.origin
        );
        url.searchParams.set('page', String(page));
        if (searchInput.value.trim()) {
            url.searchParams.set('search', searchInput.value.trim());
        }

        try {
            const response = await fetch(url.toString(), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error('Daftar barang supplier gagal dimuat.');
            }

            const payload = await response.json();
            if (sequence !== requestSequence) return;

            (payload.data || []).forEach((barang) => barangCache.set(String(barang.id), barang));
            renderResults(payload.data || []);

            const meta = payload.meta || {};
            currentPage = Number(meta.current_page || 1);
            lastPage = Number(meta.last_page || 1);
            countElement.textContent = `${formatQuantity(meta.total || 0)} barang ditemukan`;
            pageElement.textContent = `Halaman ${currentPage} / ${lastPage}`;
            previousButton.disabled = currentPage <= 1;
            nextButton.disabled = currentPage >= lastPage;
        } catch (error) {
            if (sequence !== requestSequence) return;
            resultsElement.innerHTML = `<div class="py-10 text-center text-sm text-red-600">${escapeHtml(error.message || 'Terjadi kesalahan saat memuat barang.')}</div>`;
            countElement.textContent = 'Gagal memuat data';
            pageElement.textContent = '-';
            previousButton.disabled = true;
            nextButton.disabled = true;
        }
    }

    function chooseBarang(barangId) {
        const barang = findBarang(barangId);
        if (!barang || !activeRow) return;

        if (selectedInAnotherRow(barang.id, activeRow)) {
            alert('Barang yang sama tidak boleh dimasukkan lebih dari satu kali.');
            return;
        }

        updateRowBarang(activeRow, barang);
        activeRow.querySelector('.item-harga').value = Number(barang.harga_beli_default) || 0;
        calculateTotals();
        closeBarangPicker();
    }

    function calculateTotals() {
        let subtotalPO = 0;

        container.querySelectorAll('.po-item-row').forEach((row) => {
            const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
            const harga = parseFloat(row.querySelector('.item-harga').value) || 0;
            const diskon = parseFloat(row.querySelector('.item-diskon').value) || 0;
            const subtotal = Math.max(0, (qty * harga) - diskon);

            row.querySelector('.item-subtotal').textContent = `Rp ${formatRupiah(subtotal)}`;
            subtotalPO += subtotal;
        });

        const diskonPO = parseFloat(document.getElementById('diskon-po')?.value) || 0;
        const dasarPajak = Math.max(0, subtotalPO - diskonPO);
        const pajakPersen = parseFloat(document.getElementById('pajak-persen')?.value) || 0;
        const pajakNominal = dasarPajak * pajakPersen / 100;
        const total = Math.max(0, dasarPajak + pajakNominal);

        document.getElementById('subtotal-display').textContent = `Rp ${formatRupiah(subtotalPO)}`;
        document.getElementById('diskon-display').textContent = `Rp ${formatRupiah(diskonPO)}`;
        document.getElementById('dasar-pajak-display').textContent = `Rp ${formatRupiah(dasarPajak)}`;
        document.getElementById('pajak-persen-display').textContent = `(${formatRupiah(pajakPersen)}%)`;
        document.getElementById('pajak-display').textContent = `Rp ${formatRupiah(pajakNominal)}`;
        document.getElementById('total-display').textContent = `Rp ${formatRupiah(total)}`;
    }

    resultsElement.addEventListener('click', (event) => {
        const button = event.target.closest('[data-select-barang]');
        if (button && !button.disabled) {
            chooseBarang(button.dataset.selectBarang);
        }
    });

    modal.querySelectorAll('[data-barang-picker-close]').forEach((element) => {
        element.addEventListener('click', closeBarangPicker);
    });

    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => loadBarangResults(1), 250);
    });

    previousButton.addEventListener('click', () => {
        if (currentPage > 1) loadBarangResults(currentPage - 1);
    });

    nextButton.addEventListener('click', () => {
        if (currentPage < lastPage) loadBarangResults(currentPage + 1);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeBarangPicker();
        }
    });

    if (addItemButton) {
        addItemButton.disabled = isRequestBasedPO || !supplierSelect.value;
        addItemButton.addEventListener('click', () => {
            if (isRequestBasedPO) return;
            if (!supplierSelect.value) {
                alert('Pilih supplier terlebih dahulu sebelum menambahkan barang.');
                supplierSelect.focus();
                return;
            }

            const blankRow = Array.from(container.querySelectorAll('.po-item-row'))
                .find((row) => !row.querySelector('.item-barang').value);
            const targetRow = blankRow || addItem();
            openBarangPicker(targetRow);
        });
    }

    supplierSelect.addEventListener('change', () => {
        const nextSupplierId = supplierSelect.value;
        const hasSelectedItems = Array.from(container.querySelectorAll('.po-item-row'))
            .some((row) => Boolean(row.querySelector('.item-barang').value));

        if (previousSupplierId && nextSupplierId !== previousSupplierId && hasSelectedItems && !isRequestBasedPO) {
            const confirmed = confirm('Mengganti supplier akan menghapus seluruh barang pada detail PO. Lanjutkan?');
            if (!confirmed) {
                supplierSelect.value = previousSupplierId;
                return;
            }

            container.innerHTML = '';
            addItem();
        }

        previousSupplierId = nextSupplierId;
        if (addItemButton && !isRequestBasedPO) {
            addItemButton.disabled = !nextSupplierId;
        }

        container.querySelectorAll('.item-picker').forEach((button) => {
            button.disabled = isRequestBasedPO;
        });
    });

    document.getElementById('diskon-po')?.addEventListener('input', calculateTotals);
    document.getElementById('pajak-persen')?.addEventListener('input', calculateTotals);

    if (initialItems.length > 0) {
        initialItems.forEach((item) => addItem(item));
    } else {
        addItem();
    }

    calculateTotals();
})();
</script>

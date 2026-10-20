@extends('layouts.app')

@section('title', 'Buat Mutasi Gudang')

@section('content')
<div class="mx-auto max-w-6xl space-y-6">
    <div>
        <a href="{{ route('mutasi-gudang.index') }}" class="mb-3 inline-flex items-center gap-2 text-sm text-slate-500 hover:text-[#15235F]">
            <x-icon name="arrow-left" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> Kembali
        </a>
        <h2 class="text-xl font-semibold text-slate-800">Buat Mutasi Gudang</h2>
        <p class="mt-1 text-sm text-slate-500">Pindahkan stok dari satu gudang ke gudang lainnya.</p>
    </div>

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3">
            <p class="text-sm font-semibold text-red-700">Periksa kembali data yang diinput.</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-xs text-red-600">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="mutasi-gudang-form" action="{{ route('mutasi-gudang.store') }}" method="POST" class="space-y-6">
        @csrf

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-5">
                <h3 class="text-sm font-semibold text-slate-700">Informasi Mutasi</h3>
            </div>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Nomor Mutasi</label>
                    <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5">
                        <span class="text-sm font-semibold text-[#15235F]">{{ $previewNomorMutasi }}</span>
                        <span class="rounded-full bg-teal-50 px-2.5 py-1 text-[10px] font-medium text-[#32AAA6]">Otomatis</span>
                    </div>
                </div>

                <div>
                    <label for="tanggal_mutasi" class="mb-2 block text-sm font-medium text-slate-700">Tanggal Mutasi *</label>
                    <input type="date" id="tanggal_mutasi" name="tanggal_mutasi" value="{{ old('tanggal_mutasi', date('Y-m-d')) }}" required class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100">
                </div>

                <div>
                    <label for="gudang_asal_id" class="mb-2 block text-sm font-medium text-slate-700">Gudang Asal *</label>
                    <select id="gudang_asal_id" name="gudang_asal_id" required class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100">
                        <option value="">Pilih Gudang Asal</option>
                        @foreach($gudangs as $gudang)
                            <option value="{{ $gudang->id }}" {{ (string) old('gudang_asal_id', $selectedGudangId) === (string) $gudang->id ? 'selected' : '' }}>
                                {{ $gudang->kode_gudang }} - {{ $gudang->nama_gudang }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="gudang_tujuan_id" class="mb-2 block text-sm font-medium text-slate-700">Gudang Tujuan *</label>
                    <select id="gudang_tujuan_id" name="gudang_tujuan_id" required class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100">
                        <option value="">Pilih Gudang Tujuan</option>
                        @foreach($gudangs as $gudang)
                            <option value="{{ $gudang->id }}" {{ (string) old('gudang_tujuan_id') === (string) $gudang->id ? 'selected' : '' }}>
                                {{ $gudang->kode_gudang }} - {{ $gudang->nama_gudang }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-4">
                <h3 class="text-sm font-semibold text-slate-700">Cari Barang yang Dipindahkan</h3>
                <p class="mt-1 text-xs text-slate-500">Barang tidak dimuat sekaligus. Cari berdasarkan kode, nama, spesifikasi, atau merek. Hasil hanya dari stok positif di gudang asal.</p>
            </div>

            <div class="space-y-4 p-5">
                <div class="flex flex-col gap-2 sm:flex-row">
                    <div class="relative flex-1">
                        <label for="barang-search" class="sr-only">Cari barang</label>
                        <input id="barang-search" type="search" autocomplete="off" minlength="2" maxlength="100" placeholder="Ketik minimal 2 karakter: kode, nama, spesifikasi, atau merek..." {{ $selectedGudangId ? '' : 'disabled' }} class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100 disabled:bg-slate-100">
                    </div>
                    <button id="barang-search-button" type="button" {{ $selectedGudangId ? '' : 'disabled' }} class="rounded-lg bg-[#15235F] px-5 py-3 text-sm font-medium text-white hover:bg-[#101b4d] disabled:cursor-not-allowed disabled:opacity-40">
                        Cari Barang
                    </button>
                </div>

                <p id="search-help" class="text-xs text-slate-500">
                    {{ $selectedGudangId ? 'Masukkan kata kunci untuk menampilkan maksimal 20 hasil.' : 'Pilih gudang asal terlebih dahulu agar sistem dapat mencari stok yang tersedia.' }}
                </p>

                <div id="search-results" class="hidden overflow-hidden rounded-xl border border-slate-200" aria-live="polite"></div>
            </div>

            <div class="border-t border-slate-200 bg-slate-50 px-5 py-4">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <h4 class="text-sm font-semibold text-slate-700">Daftar Barang Terpilih</h4>
                    <span id="selected-count" class="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-600 ring-1 ring-slate-200">0 barang</span>
                </div>

                <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                    <table class="w-full min-w-[900px] text-sm">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs uppercase tracking-wide text-slate-400">
                                <th class="px-4 py-3 font-medium">Barang</th>
                                <th class="px-4 py-3 text-right font-medium">Stok Tersedia</th>
                                <th class="px-4 py-3 font-medium">Satuan</th>
                                <th class="px-4 py-3 text-right font-medium">Average Cost</th>
                                <th class="px-4 py-3 font-medium">Qty Mutasi</th>
                                <th class="px-4 py-3 text-center font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="selected-items-body" class="divide-y divide-slate-100">
                            <tr id="empty-selected-row">
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">Belum ada barang dipilih. Gunakan pencarian di atas untuk menambahkan barang.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="mt-2 text-xs text-slate-500">Masukkan kuantitas untuk setiap barang yang ingin dipindahkan. Baris dengan kuantitas 0 tidak akan diproses.</p>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <label for="catatan" class="mb-2 block text-sm font-medium text-slate-700">Catatan</label>
            <textarea id="catatan" name="catatan" rows="3" placeholder="Catatan mutasi gudang (opsional)" class="w-full resize-none rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100">{{ old('catatan') }}</textarea>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('mutasi-gudang.index') }}" class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100">Batal</a>
            <button id="submit-mutasi" type="submit" {{ $selectedGudangId ? '' : 'disabled' }} class="rounded-lg bg-[#15235F] px-5 py-2.5 text-sm font-medium text-white hover:bg-[#101b4d] disabled:cursor-not-allowed disabled:opacity-40">Simpan Mutasi</button>
        </div>
    </form>
</div>

<script>
(() => {
    const sourceSelect = document.getElementById('gudang_asal_id');
    const destinationSelect = document.getElementById('gudang_tujuan_id');
    const searchInput = document.getElementById('barang-search');
    const searchButton = document.getElementById('barang-search-button');
    const searchResults = document.getElementById('search-results');
    const searchHelp = document.getElementById('search-help');
    const selectedBody = document.getElementById('selected-items-body');
    const selectedCount = document.getElementById('selected-count');
    const submitButton = document.getElementById('submit-mutasi');
    const form = document.getElementById('mutasi-gudang-form');
    const searchUrl = @json(route('mutasi-gudang.search-barang'));
    const initialItems = @json($selectedItems->values());
    let selectedItems = Array.isArray(initialItems) ? initialItems : [];
    let debounceTimer = null;
    let previousSource = sourceSelect.value;

    const currencyFormatter = new Intl.NumberFormat('id-ID', {
        style: 'currency', currency: 'IDR', minimumFractionDigits: 2, maximumFractionDigits: 2
    });
    const numberFormatter = new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 2, maximumFractionDigits: 2
    });

    function makeCell(className, text) {
        const td = document.createElement('td');
        td.className = className;
        td.textContent = text;
        return td;
    }

    function itemLabel(item) {
        return `${item.kode_barang || ''} - ${item.nama_barang || ''}`.trim();
    }

    function renderSelectedItems() {
        selectedBody.replaceChildren();
        selectedCount.textContent = `${selectedItems.length} barang`;
        submitButton.disabled = !sourceSelect.value || selectedItems.length === 0;

        if (selectedItems.length === 0) {
            const row = document.createElement('tr');
            const cell = makeCell('px-4 py-10 text-center text-sm text-slate-500', 'Belum ada barang dipilih. Gunakan pencarian di atas untuk menambahkan barang.');
            cell.colSpan = 6;
            row.appendChild(cell);
            selectedBody.appendChild(row);
            return;
        }

        selectedItems.forEach((item, index) => {
            const row = document.createElement('tr');
            const nameCell = document.createElement('td');
            nameCell.className = 'px-4 py-4';

            const title = document.createElement('p');
            title.className = 'font-semibold text-slate-700';
            title.textContent = itemLabel(item);
            nameCell.appendChild(title);

            const details = [item.spesifikasi, item.merek].filter(Boolean).join(' - ');
            if (details) {
                const detail = document.createElement('p');
                detail.className = 'mt-1 text-xs text-slate-400';
                detail.textContent = details;
                nameCell.appendChild(detail);
            }

            const hiddenId = document.createElement('input');
            hiddenId.type = 'hidden';
            hiddenId.name = `items[${index}][barang_id]`;
            hiddenId.value = item.barang_id;
            nameCell.appendChild(hiddenId);
            row.appendChild(nameCell);

            row.appendChild(makeCell('px-4 py-4 text-right font-medium text-slate-700', numberFormatter.format(Number(item.qty_available || 0))));
            row.appendChild(makeCell('px-4 py-4 text-slate-600', item.satuan || '-'));
            row.appendChild(makeCell('px-4 py-4 text-right text-slate-600', currencyFormatter.format(Number(item.harga_rata_rata || 0))));

            const qtyCell = document.createElement('td');
            qtyCell.className = 'px-4 py-4';
            const qtyInput = document.createElement('input');
            qtyInput.type = 'number';
            qtyInput.name = `items[${index}][qty]`;
            qtyInput.min = '0';
            qtyInput.max = String(item.qty_available);
            qtyInput.step = '0.01';
            qtyInput.value = String(item.qty ?? 0);
            qtyInput.className = 'w-32 rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100';
            qtyInput.setAttribute('aria-label', `Kuantitas mutasi ${itemLabel(item)}`);
            qtyInput.addEventListener('input', () => {
                item.qty = qtyInput.value === '' ? 0 : Number(qtyInput.value);
                qtyInput.setCustomValidity(Number(item.qty) > Number(item.qty_available) ? 'Kuantitas melebihi stok tersedia.' : '');
            });
            qtyCell.appendChild(qtyInput);
            const maxNote = document.createElement('p');
            maxNote.className = 'mt-1 text-[10px] text-slate-400';
            maxNote.textContent = `Maks. ${numberFormatter.format(Number(item.qty_available || 0))}`;
            qtyCell.appendChild(maxNote);
            row.appendChild(qtyCell);

            const actionCell = document.createElement('td');
            actionCell.className = 'px-4 py-4 text-center';
            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'rounded-lg border border-red-200 px-3 py-2 text-xs font-medium text-red-600 hover:bg-red-50';
            removeButton.textContent = 'Hapus';
            removeButton.addEventListener('click', () => {
                selectedItems = selectedItems.filter((selected) => Number(selected.barang_id) !== Number(item.barang_id));
                renderSelectedItems();
                renderSearchResults(window.lastSearchResults || []);
            });
            actionCell.appendChild(removeButton);
            row.appendChild(actionCell);
            selectedBody.appendChild(row);
        });
    }

    function renderSearchResults(items) {
        window.lastSearchResults = items;
        searchResults.replaceChildren();
        if (!sourceSelect.value) {
            searchResults.classList.add('hidden');
            return;
        }

        if (!searchInput.value.trim()) {
            searchResults.classList.add('hidden');
            return;
        }

        searchResults.classList.remove('hidden');
        if (items.length === 0) {
            const empty = document.createElement('p');
            empty.className = 'px-4 py-5 text-sm text-slate-500';
            empty.textContent = 'Tidak ada barang dengan stok tersedia yang cocok dengan pencarian.';
            searchResults.appendChild(empty);
            return;
        }

        const list = document.createElement('div');
        list.className = 'divide-y divide-slate-100';
        items.forEach((item) => {
            const alreadySelected = selectedItems.some((selected) => Number(selected.barang_id) === Number(item.barang_id));
            const button = document.createElement('button');
            button.type = 'button';
            button.disabled = alreadySelected;
            button.className = 'flex w-full items-center justify-between gap-4 px-4 py-3 text-left hover:bg-slate-50 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:opacity-60';

            const info = document.createElement('div');
            info.className = 'min-w-0 flex-1';
            const title = document.createElement('p');
            title.className = 'truncate text-sm font-semibold text-slate-700';
            title.textContent = itemLabel(item);
            info.appendChild(title);
            const details = [item.spesifikasi, item.merek].filter(Boolean).join(' - ');
            const meta = document.createElement('p');
            meta.className = 'mt-1 text-xs text-slate-500';
            meta.textContent = `${details ? details + ' · ' : ''}Stok: ${numberFormatter.format(Number(item.qty_available || 0))} ${item.satuan || ''} · Average cost: ${currencyFormatter.format(Number(item.harga_rata_rata || 0))}`;
            info.appendChild(meta);
            button.appendChild(info);

            const action = document.createElement('span');
            action.className = 'shrink-0 rounded-lg px-3 py-2 text-xs font-semibold ' + (alreadySelected ? 'bg-slate-100 text-slate-500' : 'bg-teal-50 text-teal-700');
            action.textContent = alreadySelected ? 'Sudah dipilih' : 'Tambah';
            button.appendChild(action);

            button.addEventListener('click', () => {
                if (selectedItems.some((selected) => Number(selected.barang_id) === Number(item.barang_id))) return;
                selectedItems.push({ ...item, qty: 0 });
                renderSelectedItems();
                renderSearchResults(window.lastSearchResults || []);
            });
            list.appendChild(button);
        });
        searchResults.appendChild(list);
    }

    async function searchBarang() {
        const query = searchInput.value.trim();
        if (!sourceSelect.value) {
            searchHelp.textContent = 'Pilih gudang asal terlebih dahulu.';
            return;
        }
        if (query.length < 2) {
            searchResults.replaceChildren();
            searchResults.classList.add('hidden');
            searchHelp.textContent = 'Masukkan minimal 2 karakter untuk mencari barang.';
            return;
        }

        searchButton.disabled = true;
        searchButton.textContent = 'Mencari...';
        searchHelp.textContent = 'Sedang mencari barang di gudang asal...';

        try {
            const url = new URL(searchUrl, window.location.origin);
            url.searchParams.set('gudang_asal_id', sourceSelect.value);
            url.searchParams.set('q', query);
            const response = await fetch(url.toString(), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || 'Pencarian barang gagal.');
            renderSearchResults(payload.data || []);
            searchHelp.textContent = `${(payload.data || []).length} hasil ditampilkan (maksimal 20). Gunakan kata kunci yang lebih spesifik jika barang belum ditemukan.`;
        } catch (error) {
            searchResults.replaceChildren();
            searchResults.classList.remove('hidden');
            const message = document.createElement('p');
            message.className = 'px-4 py-5 text-sm text-red-600';
            message.textContent = error.message || 'Terjadi kesalahan saat mencari barang.';
            searchResults.appendChild(message);
            searchHelp.textContent = 'Pencarian tidak berhasil.';
        } finally {
            searchButton.disabled = !sourceSelect.value;
            searchButton.textContent = 'Cari Barang';
        }
    }

    searchButton.addEventListener('click', searchBarang);
    searchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        if (searchInput.value.trim().length < 2) {
            searchResults.replaceChildren();
            searchResults.classList.add('hidden');
            searchHelp.textContent = sourceSelect.value ? 'Masukkan minimal 2 karakter untuk mencari barang.' : 'Pilih gudang asal terlebih dahulu.';
            return;
        }
        debounceTimer = setTimeout(searchBarang, 300);
    });
    searchInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            searchBarang();
        }
    });

    sourceSelect.addEventListener('change', function () {
        if (selectedItems.length > 0 && !window.confirm('Mengganti gudang asal akan menghapus daftar barang yang sudah dipilih. Lanjutkan?')) {
            this.value = previousSource;
            return;
        }
        const url = new URL(window.location.href);
        if (this.value) url.searchParams.set('gudang_asal_id', this.value);
        else url.searchParams.delete('gudang_asal_id');
        window.location.href = url.toString();
    });

    destinationSelect.addEventListener('change', function () {
        if (this.value && this.value === sourceSelect.value) {
            alert('Gudang tujuan harus berbeda dengan gudang asal.');
            this.value = '';
        }
    });

    form.addEventListener('submit', (event) => {
        const hasPositiveQty = selectedItems.some((item) => Number(item.qty) > 0);
        if (!hasPositiveQty) {
            event.preventDefault();
            alert('Pilih minimal satu barang dan isi kuantitas mutasi lebih dari 0.');
            return;
        }
        const exceedsStock = selectedItems.some((item) => Number(item.qty) > Number(item.qty_available));
        if (exceedsStock) {
            event.preventDefault();
            alert('Ada kuantitas mutasi yang melebihi stok tersedia. Periksa kembali daftar barang.');
        }
    });

    renderSelectedItems();
})();
</script>
@endsection

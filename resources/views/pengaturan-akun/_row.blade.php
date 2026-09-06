<div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between py-2">
    <div class="max-w-md">
        <label class="block text-sm font-semibold text-[#15235F]">
            {{ $item->nama_pengaturan }}
        </label>
        <p class="text-xs text-slate-400">
            {{ $item->keterangan }}
        </p>
        <span class="inline-block mt-1 font-mono text-[11px] text-slate-400 bg-slate-100 px-2 py-0.5 rounded">
            {{ $item->kunci_pengaturan }}
        </span>
    </div>

    <div class="w-full sm:w-72">
        <select
            name="mappings[{{ $item->id }}]"
            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
        >
            <option value="">-- Pilih Akun COA --</option>
            @foreach($akuns as $a)
                <option value="{{ $a->id }}" {{ $item->akun_id == $a->id ? 'selected' : '' }}>
                    {{ $a->kode_akun }} - {{ $a->nama_akun }} ({{ $a->sub_kategori ?: $a->kategori_akun }})
                </option>
            @endforeach
        </select>
    </div>
</div>

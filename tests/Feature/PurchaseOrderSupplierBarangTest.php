<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\KategoriBarang;
use App\Models\Satuan;
use App\Models\StokGudang;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderSupplierBarangTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_order_barang_lookup_only_returns_active_barang_registered_for_selected_supplier_with_stock_by_warehouse(): void
    {
        $user = User::factory()->create(['role' => 'Pembelian']);
        $supplier = $this->createSupplier();
        $registeredBarang = $this->createBarang('BRG-REGISTERED', 'Barang Supplier');
        $otherBarang = $this->createBarang('BRG-OTHER', 'Barang Supplier Lain');
        $inactiveBarang = $this->createBarang('BRG-INACTIVE', 'Barang Nonaktif', false);

        $supplier->barangs()->attach($registeredBarang->id);
        $supplier->barangs()->attach($inactiveBarang->id);

        $gudang = Gudang::query()->create([
            'kode_gudang' => 'AMB26',
            'nama_gudang' => 'Gudang Ambaja',
            'is_active' => true,
        ]);

        StokGudang::query()->create([
            'barang_id' => $registeredBarang->id,
            'gudang_id' => $gudang->id,
            'qty' => 12,
            'harga_rata_rata' => 15000,
            'nilai_persediaan' => 180000,
        ]);

        $response = $this->actingAs($user)->getJson(
            route('purchase-order.supplier-barangs', $supplier) . '?search=Barang'
        );

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $registeredBarang->id)
            ->assertJsonPath('data.0.stok_total', 12)
            ->assertJsonPath('data.0.stok_by_warehouse.0.kode', 'AMB26')
            ->assertJsonPath('data.0.stok_by_warehouse.0.qty', 12);

        $this->assertNotSame($registeredBarang->id, $otherBarang->id);
    }

    public function test_purchase_order_store_rejects_barang_not_registered_for_selected_supplier(): void
    {
        $user = User::factory()->create(['role' => 'Pembelian']);
        $supplier = $this->createSupplier();
        $barang = $this->createBarang('BRG-NOT-MAPPED', 'Barang Belum Terdaftar');

        $this->actingAs($user)
            ->from(route('purchase-order.create'))
            ->post(route('purchase-order.store'), $this->purchaseOrderPayload($supplier, $barang))
            ->assertSessionHasErrors('items');

        $this->assertDatabaseMissing('purchase_orders', [
            'supplier_id' => $supplier->id,
        ]);
    }

    public function test_purchase_order_store_accepts_barang_registered_for_selected_supplier_without_changing_stock(): void
    {
        $user = User::factory()->create(['role' => 'Pembelian']);
        $supplier = $this->createSupplier();
        $barang = $this->createBarang('BRG-MAPPED', 'Barang Supplier Terdaftar');
        $supplier->barangs()->attach($barang->id);

        $gudang = Gudang::query()->create([
            'kode_gudang' => 'GDG-PO-TEST',
            'nama_gudang' => 'Gudang PO Test',
            'is_active' => true,
        ]);
        StokGudang::query()->create([
            'barang_id' => $barang->id,
            'gudang_id' => $gudang->id,
            'qty' => 7,
            'harga_rata_rata' => 10000,
            'nilai_persediaan' => 70000,
        ]);

        $response = $this->actingAs($user)->post(
            route('purchase-order.store'),
            $this->purchaseOrderPayload($supplier, $barang)
        );

        $purchaseOrder = \App\Models\PurchaseOrder::query()->firstOrFail();
        $response->assertRedirect(route('purchase-order.show', $purchaseOrder));
        $this->assertDatabaseHas('purchase_order_items', [
            'purchase_order_id' => $purchaseOrder->id,
            'barang_id' => $barang->id,
            'qty' => 3,
        ]);
        $this->assertEquals(7.0, (float) StokGudang::query()
            ->where('barang_id', $barang->id)
            ->where('gudang_id', $gudang->id)
            ->value('qty'));
    }

    public function test_existing_draft_purchase_order_can_be_updated_without_retroactively_mapping_its_existing_barang(): void
    {
        $user = User::factory()->create(['role' => 'Pembelian']);
        $supplier = $this->createSupplier();
        $barang = $this->createBarang('BRG-LEGACY-DRAFT', 'Barang PO Lama');

        $purchaseOrder = \App\Models\PurchaseOrder::query()->create([
            'nomor_po' => 'PO-LEGACY-' . strtoupper(bin2hex(random_bytes(3))),
            'supplier_id' => $supplier->id,
            'tanggal_po' => today()->toDateString(),
            'subtotal' => 20000,
            'diskon' => 0,
            'pajak_persen' => 0,
            'pajak' => 0,
            'total' => 20000,
            'status' => 'Draft',
            'created_by' => $user->id,
        ]);

        $purchaseOrder->items()->create([
            'barang_id' => $barang->id,
            'qty' => 2,
            'harga_satuan' => 10000,
            'diskon' => 0,
            'subtotal' => 20000,
            'qty_diterima' => 0,
            'keterangan' => 'Item PO sebelum relasi supplier-barang',
        ]);

        $response = $this->actingAs($user)->put(
            route('purchase-order.update', $purchaseOrder),
            $this->purchaseOrderPayload($supplier, $barang)
        );

        $response->assertRedirect(route('purchase-order.show', $purchaseOrder));
        $this->assertDatabaseHas('purchase_order_items', [
            'purchase_order_id' => $purchaseOrder->id,
            'barang_id' => $barang->id,
            'qty' => 3,
        ]);
    }

    public function test_supplier_can_add_and_remove_barang_from_its_master_list(): void
    {
        $user = User::factory()->create(['role' => 'Admin']);
        $supplier = $this->createSupplier();
        $barang = $this->createBarang('BRG-MANAGED', 'Barang Kelola Supplier');

        $this->actingAs($user)
            ->post(route('supplier.barang.attach', [$supplier, $barang]))
            ->assertRedirect();

        $this->assertDatabaseHas('barang_supplier', [
            'supplier_id' => $supplier->id,
            'barang_id' => $barang->id,
        ]);

        $this->actingAs($user)
            ->delete(route('supplier.barang.detach', [$supplier, $barang]))
            ->assertRedirect();

        $this->assertDatabaseMissing('barang_supplier', [
            'supplier_id' => $supplier->id,
            'barang_id' => $barang->id,
        ]);
    }

    private function createSupplier(): Supplier
    {
        return Supplier::query()->create([
            'kode_supplier' => 'SUP-' . strtoupper(bin2hex(random_bytes(3))),
            'nama_supplier' => 'Supplier Uji',
            'is_active' => true,
        ]);
    }

    private function createBarang(string $code, string $name, bool $active = true): Barang
    {
        $kategori = KategoriBarang::query()->create([
            'kode_kategori' => 'KAT-' . strtoupper(bin2hex(random_bytes(3))),
            'nama_kategori' => 'Kategori Uji',
            'is_active' => true,
        ]);
        $satuan = Satuan::query()->create([
            'kode_satuan' => 'SAT-' . strtoupper(bin2hex(random_bytes(3))),
            'nama_satuan' => 'Unit',
            'is_active' => true,
        ]);

        return Barang::query()->create([
            'kode_barang' => $code,
            'nama_barang' => $name,
            'kategori_barang_id' => $kategori->id,
            'satuan_id' => $satuan->id,
            'harga_beli_default' => 10000,
            'is_active' => $active,
        ]);
    }

    private function purchaseOrderPayload(Supplier $supplier, Barang $barang): array
    {
        return [
            'supplier_id' => $supplier->id,
            'tanggal_po' => today()->toDateString(),
            'items' => [[
                'barang_id' => $barang->id,
                'qty' => 3,
                'harga_satuan' => 10000,
                'diskon' => 0,
                'keterangan' => 'Uji supplier barang',
            ]],
        ];
    }
}

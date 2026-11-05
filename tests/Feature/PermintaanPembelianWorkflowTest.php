<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\KategoriBarang;
use App\Models\PermintaanPembelian;
use App\Models\PermintaanPembelianDetail;
use App\Models\Satuan;
use App\Models\StokGudang;
use App\Models\Supplier;
use App\Models\User;
use App\Notifications\PermintaanPembelianNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermintaanPembelianWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_gudang_can_submit_low_stock_request_and_purchasing_receives_database_notification(): void
    {
        $gudangUser = User::factory()->create(['role' => 'Gudang']);
        $purchasingUser = User::factory()->create(['role' => 'Pembelian']);
        $barang = $this->createLowStockBarang(5, 10);

        $response = $this->actingAs($gudangUser)->post(route('permintaan-pembelian.store'), [
            'items' => [
                $barang->id => [
                    'barang_id' => $barang->id,
                    'selected' => '1',
                    'qty_diminta' => 5,
                ],
            ],
            'catatan' => 'Kebutuhan operasional gudang.',
        ]);

        $permintaan = PermintaanPembelian::query()->firstOrFail();

        $response->assertRedirect(route('permintaan-pembelian.show', $permintaan));
        $this->assertSame(PermintaanPembelian::STATUS_DIAJUKAN, $permintaan->status);
        $this->assertSame(1, $permintaan->details()->count());
        $this->assertEquals(5.00, (float) $permintaan->details()->firstOrFail()->stok_saat_pengajuan);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $purchasingUser->id,
            'notifiable_type' => User::class,
            'type' => PermintaanPembelianNotification::class,
        ]);

        // Submitting a request must not change inventory balances.
        $this->assertEquals(5.00, (float) StokGudang::query()->where('barang_id', $barang->id)->sum('qty'));
    }

    public function test_gudang_form_shows_only_ten_most_critical_items_in_priority_order(): void
    {
        $gudangUser = User::factory()->create(['role' => 'Gudang']);

        // The first item is out of stock; the remaining items have increasing stock ratios.
        $items = [];
        $items[] = $this->createLowStockBarang(0, 100);
        for ($qty = 1; $qty <= 11; $qty++) {
            $items[] = $this->createLowStockBarang((float) $qty, 100);
        }

        $response = $this->actingAs($gudangUser)
            ->get(route('permintaan-pembelian.create'));

        $response->assertOk();
        $response->assertViewHas('barangs', function ($barangs) use ($items): bool {
            return $barangs->count() === 10
                && (int) $barangs->first()->id === (int) $items[0]->id
                && (int) $barangs->last()->id === (int) $items[9]->id;
        });
    }

    public function test_gudang_cannot_submit_more_than_ten_items_in_one_request(): void
    {
        $gudangUser = User::factory()->create(['role' => 'Gudang']);
        $items = [];

        for ($i = 0; $i < 11; $i++) {
            $barang = $this->createLowStockBarang(1, 10);
            $items[$barang->id] = [
                'barang_id' => $barang->id,
                'selected' => '1',
                'qty_diminta' => 9,
            ];
        }

        $this->actingAs($gudangUser)
            ->from(route('permintaan-pembelian.create'))
            ->post(route('permintaan-pembelian.store'), ['items' => $items])
            ->assertSessionHasErrors('items');

        $this->assertSame(0, PermintaanPembelian::query()->count());
    }

    public function test_same_barang_cannot_have_two_active_purchase_requests(): void
    {
        $gudangUser = User::factory()->create(['role' => 'Gudang']);
        $barang = $this->createLowStockBarang(2, 10);

        $payload = [
            'items' => [
                $barang->id => [
                    'barang_id' => $barang->id,
                    'selected' => '1',
                    'qty_diminta' => 8,
                ],
            ],
        ];

        $this->actingAs($gudangUser)
            ->post(route('permintaan-pembelian.store'), $payload)
            ->assertRedirect();

        $this->actingAs($gudangUser)
            ->from(route('permintaan-pembelian.create'))
            ->post(route('permintaan-pembelian.store'), $payload)
            ->assertSessionHasErrors('items');

        $this->assertSame(1, PermintaanPembelian::query()->count());
    }

    public function test_purchasing_can_approve_request_and_create_po_only_for_approved_quantities(): void
    {
        $gudangUser = User::factory()->create(['role' => 'Gudang']);
        $purchasingUser = User::factory()->create(['role' => 'Pembelian']);
        $barang = $this->createLowStockBarang(3, 10);

        $this->actingAs($gudangUser)->post(route('permintaan-pembelian.store'), [
            'items' => [
                $barang->id => [
                    'barang_id' => $barang->id,
                    'selected' => '1',
                    'qty_diminta' => 7,
                ],
            ],
        ])->assertRedirect();

        $permintaan = PermintaanPembelian::query()->with('details')->firstOrFail();
        $detail = $permintaan->details->firstOrFail();

        $this->actingAs($purchasingUser)->patch(route('permintaan-pembelian.approve', $permintaan), [
            'items' => [
                $detail->id => ['qty_disetujui' => 6],
            ],
        ])->assertRedirect(route('permintaan-pembelian.show', $permintaan));

        $this->assertSame(PermintaanPembelian::STATUS_DISETUJUI, $permintaan->fresh()->status);
        $this->assertEquals(6.00, (float) $detail->fresh()->qty_disetujui);

        $supplier = Supplier::query()->create([
            'kode_supplier' => 'SUP-TEST-01',
            'nama_supplier' => 'Supplier Uji',
            'is_active' => true,
        ]);

        $this->actingAs($purchasingUser)->post(route('purchase-order.store'), [
            'permintaan_pembelian_id' => $permintaan->id,
            'supplier_id' => $supplier->id,
            'tanggal_po' => today()->toDateString(),
            'items' => [[
                'barang_id' => $barang->id,
                'qty' => 6,
                'harga_satuan' => 10000,
                'diskon' => 0,
                'keterangan' => 'Dari permintaan gudang',
            ]],
        ])->assertRedirect();

        $permintaan->refresh();
        $this->assertSame(PermintaanPembelian::STATUS_PO_DIBUAT, $permintaan->status);
        $this->assertNotNull($permintaan->purchase_order_id);
        $poItem = \App\Models\PurchaseOrderItem::query()
            ->where('purchase_order_id', $permintaan->purchase_order_id)
            ->where('barang_id', $barang->id)
            ->firstOrFail();
        $this->assertEqualsWithDelta(6.00, (float) $poItem->qty, 0.001);

        // Creating a PO is not a goods receipt and must not add stock.
        $this->assertEquals(3.00, (float) StokGudang::query()->where('barang_id', $barang->id)->sum('qty'));
    }

    public function test_sales_role_cannot_open_purchase_request_inbox_and_gudang_cannot_approve(): void
    {
        $salesUser = User::factory()->create(['role' => 'Penjualan']);
        $gudangUser = User::factory()->create(['role' => 'Gudang']);

        $this->actingAs($salesUser)
            ->get(route('permintaan-pembelian.index'))
            ->assertForbidden();

        $permintaan = PermintaanPembelian::query()->create([
            'nomor_permintaan' => 'PP-TEST-000001',
            'tanggal_permintaan' => today(),
            'status' => PermintaanPembelian::STATUS_DIAJUKAN,
            'created_by' => $gudangUser->id,
        ]);

        $this->actingAs($gudangUser)
            ->patch(route('permintaan-pembelian.approve', $permintaan), ['items' => []])
            ->assertForbidden();
    }

    private function createLowStockBarang(float $qty, float $minimum): Barang
    {
        $kategori = KategoriBarang::query()->create([
            'kode_kategori' => 'KAT-' . uniqid(),
            'nama_kategori' => 'Material Uji',
            'is_active' => true,
        ]);

        $satuan = Satuan::query()->create([
            'kode_satuan' => 'SAT-' . uniqid(),
            'nama_satuan' => 'Batang',
            'is_active' => true,
        ]);

        $gudang = Gudang::query()->create([
            'kode_gudang' => 'GDG-' . uniqid(),
            'nama_gudang' => 'Gudang Uji',
            'is_active' => true,
        ]);

        $barang = Barang::query()->create([
            'kode_barang' => 'BRG-' . uniqid(),
            'nama_barang' => 'Baja Uji',
            'kategori_barang_id' => $kategori->id,
            'satuan_id' => $satuan->id,
            'minimum_stok' => $minimum,
            'harga_beli_default' => 10000,
            'is_active' => true,
        ]);

        StokGudang::query()->create([
            'barang_id' => $barang->id,
            'gudang_id' => $gudang->id,
            'qty' => $qty,
            'harga_rata_rata' => 10000,
            'nilai_persediaan' => $qty * 10000,
        ]);

        return $barang;
    }
}

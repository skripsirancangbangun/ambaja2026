<?php

namespace Tests\Unit;

use App\Support\RolePermissions;
use Tests\TestCase;

class RolePermissionsTest extends TestCase
{
    public function test_sales_role_only_gets_sales_and_receivables_modules(): void
    {
        $this->assertTrue(RolePermissions::canAccessRoute('Penjualan', 'penjualan.index'));
        $this->assertTrue(RolePermissions::canAccessRoute('Penjualan', 'customer.index'));
        $this->assertTrue(RolePermissions::canAccessRoute('Penjualan', 'piutang.penerimaan.index'));
        $this->assertTrue(RolePermissions::canAccessRoute('Penjualan', 'laporan.penjualan.pdf'));
        $this->assertTrue(RolePermissions::canAccessRoute('Penjualan', 'laporan.piutang'));

        $this->assertFalse(RolePermissions::canAccessRoute('Penjualan', 'purchase-order.index'));
        $this->assertFalse(RolePermissions::canAccessRoute('Penjualan', 'supplier.index'));
        $this->assertFalse(RolePermissions::canAccessRoute('Penjualan', 'stok-barang.index'));
        $this->assertFalse(RolePermissions::canAccessRoute('Penjualan', 'uang-masuk.index'));
        $this->assertFalse(RolePermissions::canAccessRoute('Penjualan', 'jurnal-umum.index'));
        $this->assertFalse(RolePermissions::allowsModule('Penjualan', 'master-data'));
    }

    public function test_purchase_role_only_gets_purchase_and_payables_modules(): void
    {
        $this->assertTrue(RolePermissions::canAccessRoute('Pembelian', 'purchase-order.index'));
        $this->assertTrue(RolePermissions::canAccessRoute('Pembelian', 'supplier.index'));
        $this->assertTrue(RolePermissions::canAccessRoute('Pembelian', 'utang.kartu'));
        $this->assertTrue(RolePermissions::canAccessRoute('Pembelian', 'laporan.pembelian.excel'));

        $this->assertFalse(RolePermissions::canAccessRoute('Pembelian', 'penjualan.index'));
        $this->assertFalse(RolePermissions::canAccessRoute('Pembelian', 'customer.index'));
        $this->assertFalse(RolePermissions::canAccessRoute('Pembelian', 'stok-barang.index'));
        $this->assertFalse(RolePermissions::canAccessRoute('Pembelian', 'uang-keluar.index'));
        $this->assertFalse(RolePermissions::canAccessRoute('Pembelian', 'coa.index'));
    }

    public function test_warehouse_role_only_gets_inventory_modules(): void
    {
        $this->assertTrue(RolePermissions::canAccessRoute('Gudang', 'barang.index'));
        $this->assertTrue(RolePermissions::canAccessRoute('Gudang', 'stok-barang.index'));
        $this->assertTrue(RolePermissions::canAccessRoute('Gudang', 'stock-opname.index'));
        $this->assertTrue(RolePermissions::canAccessRoute('Gudang', 'laporan.persediaan.mutasi.pdf'));

        $this->assertFalse(RolePermissions::canAccessRoute('Gudang', 'penjualan.index'));
        $this->assertFalse(RolePermissions::canAccessRoute('Gudang', 'purchase-order.index'));
        $this->assertFalse(RolePermissions::canAccessRoute('Gudang', 'penerimaan-barang.index'));
        $this->assertFalse(RolePermissions::canAccessRoute('Gudang', 'rekening-kas-bank.index'));
        $this->assertFalse(RolePermissions::canAccessRoute('Gudang', 'jurnal-umum.index'));
    }

    public function test_accounting_role_gets_finance_and_reporting_modules_without_operational_transactions(): void
    {
        $this->assertTrue(RolePermissions::canAccessRoute('Accounting', 'uang-masuk.index'));
        $this->assertTrue(RolePermissions::canAccessRoute('Accounting', 'rekonsiliasi-bank.index'));
        $this->assertTrue(RolePermissions::canAccessRoute('Accounting', 'coa.index'));
        $this->assertTrue(RolePermissions::canAccessRoute('Accounting', 'jurnal-umum.index'));
        $this->assertTrue(RolePermissions::canAccessRoute('Accounting', 'piutang.index'));
        $this->assertTrue(RolePermissions::canAccessRoute('Accounting', 'utang.index'));
        $this->assertTrue(RolePermissions::canAccessRoute('Accounting', 'laporan.laba-rugi'));
        $this->assertTrue(RolePermissions::canAccessRoute('Accounting', 'laporan.pembelian'));

        $this->assertFalse(RolePermissions::canAccessRoute('Accounting', 'penjualan.store'));
        $this->assertFalse(RolePermissions::canAccessRoute('Accounting', 'purchase-order.store'));
        $this->assertFalse(RolePermissions::canAccessRoute('Accounting', 'stock-opname.store'));
    }

    public function test_owner_and_admin_can_access_all_modules(): void
    {
        foreach (['Owner', 'Admin'] as $role) {
            $this->assertTrue(RolePermissions::canAccessRoute($role, 'penjualan.index'));
            $this->assertTrue(RolePermissions::canAccessRoute($role, 'purchase-order.index'));
            $this->assertTrue(RolePermissions::canAccessRoute($role, 'stok-barang.index'));
            $this->assertTrue(RolePermissions::canAccessRoute($role, 'jurnal-umum.index'));
            $this->assertTrue(RolePermissions::allowsModule($role, 'master-data'));
        }
    }

    public function test_unknown_routes_are_denied_to_operational_roles(): void
    {
        $this->assertFalse(RolePermissions::canAccessRoute('Penjualan', 'route.tidak.terdaftar'));
        $this->assertFalse(RolePermissions::canAccessRoute('Gudang', null));
    }

    public function test_admin_can_bootstrap_the_first_owner_role_only_when_allowed(): void
    {
        $this->assertNotContains('Owner', RolePermissions::rolesAvailableTo('Admin'));
        $this->assertContains('Owner', RolePermissions::rolesAvailableTo('Admin', true));
        $this->assertContains('Owner', RolePermissions::rolesAvailableTo('Owner'));
    }
}

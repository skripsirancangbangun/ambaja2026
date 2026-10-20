<?php

namespace App\Support;

/**
 * Central role-to-module access map for AMBAJA.
 *
 * Route access is enforced server-side by EnsureModuleAccess.
 * The same map is used by the sidebar and dashboard.
 */
final class RolePermissions
{
    public const ROLES = [
        'Owner',
        'Admin',
        'Penjualan',
        'Pembelian',
        'Gudang',
        'Accounting',
    ];

    /** @var array<string, list<string>> */
    private const ROLE_MODULES = [
        'Penjualan' => [
            'dashboard',
            'profile',
            'sales',
            'customers',
            'receivables',
            'reports.sales',
            'reports.receivables',
        ],
        'Pembelian' => [
            'dashboard',
            'profile',
            'purchases',
            'suppliers',
            'payables',
            'reports.purchases',
            'reports.payables',
        ],
        'Gudang' => [
            'dashboard',
            'profile',
            'inventory',
            'reports.inventory',
        ],
        'Accounting' => [
            'dashboard',
            'profile',
            'receivables',
            'payables',
            'cashbank',
            'accounting',
            'accounting.settings',
            'accounting.period',
            'reports.sales',
            'reports.purchases',
            'reports.inventory',
            'reports.receivables',
            'reports.payables',
            'reports.financial',
        ],
    ];

    /** @var array<string, string> Route-name prefixes mapped to modules. */
    private const ROUTE_MODULES = [
        'dashboard' => 'dashboard',
        'profile.' => 'profile',
        'users.' => 'users',

        'profil-perusahaan.' => 'company.settings',
        'pengaturan-penomoran.' => 'company.settings',

        'laporan.pembelian' => 'reports.purchases',
        'laporan.penjualan' => 'reports.sales',
        'laporan.persediaan' => 'reports.inventory',
        'laporan.piutang' => 'reports.receivables',
        'laporan.utang' => 'reports.payables',
        'laporan.laba-rugi' => 'reports.financial',
        'laporan.neraca' => 'reports.financial',
        'laporan.arus-kas' => 'reports.financial',
        'laporan.perubahan-ekuitas' => 'reports.financial',
        'laporan.neraca-saldo' => 'reports.financial',

        'kategori-barang.' => 'inventory',
        'satuan.' => 'inventory',
        'gudang.' => 'inventory',
        'barang.' => 'inventory',
        'conversions.' => 'inventory',
        'stok-barang.' => 'inventory',
        'kartu-stok.' => 'inventory',
        'mutasi-gudang.' => 'inventory',
        'stock-opname.' => 'inventory',
        'penyesuaian-stok.' => 'inventory',

        'customer.' => 'customers',
        'supplier.' => 'suppliers',

        'penjualan.' => 'sales',
        'invoice-penjualan.' => 'sales',
        'pembayaran-penjualan.' => 'sales',
        'retur-penjualan.' => 'sales',
        'piutang.' => 'receivables',

        'purchase-order.' => 'purchases',
        'purchase-order-item.' => 'purchases',
        'penerimaan-barang.' => 'purchases',
        'invoice-supplier.' => 'purchases',
        'pembayaran-supplier.' => 'purchases',
        'pembayaran-po-supplier.' => 'purchases',
        'retur-pembelian.' => 'purchases',
        'utang.' => 'payables',

        'rekening-kas-bank.' => 'cashbank',
        'uang-masuk.' => 'cashbank',
        'uang-keluar.' => 'cashbank',
        'transfer-kas-bank.' => 'cashbank',
        'mutasi-kas-bank.' => 'cashbank',
        'rekening-koran-import.' => 'cashbank',
        'rekonsiliasi-bank.' => 'cashbank',
        'transaksi-belum-teridentifikasi.' => 'cashbank',

        'coa.' => 'accounting',
        'jurnal-umum.' => 'accounting',
        'buku-besar.' => 'accounting',
        'pengaturan-akun.' => 'accounting.settings',
        'penutupan-periode.' => 'accounting.period',
    ];

    public static function allowsModule(
        ?string $role,
        string $module
    ): bool {
        // Owner dan Admin memiliki akses ke seluruh modul,
        // termasuk User Management.
        if (in_array($role, ['Owner', 'Admin'], true)) {
            return true;
        }

        $modules = self::ROLE_MODULES[$role ?? ''] ?? [];

        if ($module === 'reports') {
            foreach ($modules as $allowedModule) {
                if (str_starts_with($allowedModule, 'reports.')) {
                    return true;
                }
            }

            return false;
        }

        if ($module === 'master-data') {
            foreach ([
                'inventory',
                'customers',
                'suppliers',
                'cashbank',
                'accounting',
                'users',
                'company.settings',
            ] as $candidate) {
                if (self::allowsModule($role, $candidate)) {
                    return true;
                }
            }

            return false;
        }

        return in_array($module, $modules, true);
    }

    public static function moduleForRouteName(
        ?string $routeName
    ): ?string {
        if (!$routeName) {
            return null;
        }

        if ($routeName === 'dashboard') {
            return 'dashboard';
        }

        foreach (self::ROUTE_MODULES as $prefix => $module) {
            if (
                $prefix !== 'dashboard'
                && str_starts_with($routeName, $prefix)
            ) {
                return $module;
            }
        }

        return null;
    }

    public static function canAccessRoute(
        ?string $role,
        ?string $routeName
    ): bool {
        // Owner dan Admin diizinkan mengakses seluruh route
        // yang dilindungi module.access.
        if (in_array($role, ['Owner', 'Admin'], true)) {
            return true;
        }

        $module = self::moduleForRouteName($routeName);

        if (
            $module === null
            || !self::allowsModule($role, $module)
        ) {
            return false;
        }

        return true;
    }

    /** @return list<string> */
    public static function rolesAvailableTo(
        ?string $actorRole
    ): array {
        if ($actorRole === 'Owner') {
            return self::ROLES;
        }

        return array_values(array_filter(
            self::ROLES,
            static fn (string $role): bool => $role !== 'Owner'
        ));
    }
}

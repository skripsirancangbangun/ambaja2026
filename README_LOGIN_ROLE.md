# AMBAJA 2026 — Login, Dashboard, dan Role Access

Patch ini menambahkan pengelolaan pengguna dan pembatasan akses berdasarkan role tanpa menambah package atau mengubah skema database. Kolom `users.role` dan `users.is_active` sudah tersedia di project asal.

## Perubahan

- Login diberi tampilan bermerek AMBAJA dan bahasa Indonesia.
- Login menolak akun nonaktif. Sesi pengguna yang kemudian dinonaktifkan akan dihentikan saat request berikutnya.
- Pendaftaran publik `/register` dinonaktifkan agar orang luar tidak bisa membuat akun sendiri.
- User Management: daftar, cari/filter, buat, edit, ganti role, ubah password, aktifkan/nonaktifkan pengguna.
- Admin tidak dapat melihat atau mengelola akun Owner dan tidak dapat membuat role Owner. Hanya Owner yang dapat menetapkan role Owner.
- Akun dinonaktifkan, bukan dihapus, agar referensi `created_by` dan jejak transaksi akuntansi tetap terjaga.
- Akses role diterapkan di backend melalui middleware, bukan hanya menyembunyikan menu.
- Dashboard memakai data database aktual untuk penjualan, piutang, utang, persediaan, stok minimum, dan transaksi bank yang belum teridentifikasi. Angka dummy dashboard lama dihapus.
- Penghapusan akun sendiri dari halaman profil dinonaktifkan untuk menjaga audit trail.
- `User` model sekarang mengizinkan assignment `role` dan `is_active`; sebelumnya kedua kolom ini tidak ada dalam daftar fillable, sehingga role yang ditetapkan seeder berpotensi tidak tersimpan.
- Password bootstrap dari `DatabaseSeeder` dapat dikonfigurasi melalui environment variable dan tidak memakai password default saat environment production.

## Role yang diterapkan

| Role | Akses utama |
|---|---|
| Owner | Semua modul dan seluruh pengelolaan role |
| Admin | Semua modul operasional; tidak dapat melihat/mengubah Owner atau menetapkan role Owner |
| Penjualan | Penjualan, customer, piutang, laporan penjualan/piutang |
| Pembelian | Pembelian, supplier, utang, laporan pembelian/utang |
| Gudang | Master barang, persediaan, mutasi, stock opname, laporan persediaan |
| Accounting | Kas/bank, jurnal, buku besar, pengaturan akuntansi, piutang/utang, dan laporan keuangan |

Role saat ini adalah matriks tetap di `app/Support/RolePermissions.php`; belum ada UI untuk membuat role khusus atau mengubah izin per aksi CRUD.

## Cara memasang di Windows/Laragon

1. Backup folder project dan database terlebih dahulu.
2. Ekstrak isi ZIP patch ke `C:\laragon\www\ambaja2026-baru` dan izinkan file patch menimpa file yang sama. File audit lokal yang tidak ada di patch tidak akan dihapus.
3. Dari terminal project jalankan:

   ```bash
   php artisan optimize:clear
   php artisan route:list --path=users
   php artisan route:list --path=register
   ```

   Route `users.*` harus muncul. Route `register` memang tidak lagi tersedia.
4. Jalankan pengujian di lingkungan PHP Laragon yang memiliki ekstensi PHPUnit/Laravel yang diperlukan:

   ```bash
   php artisan test
   ```

5. Tidak ada migration baru yang diperlukan. Jangan menjalankan `migrate:fresh`.

## Catatan database dan keamanan

- Periksa role pengguna yang sudah ada setelah patch, terutama akun Owner yang pernah dibuat sebelum perbaikan fillable. Perbaikan kode tidak otomatis mengubah role pada baris database lama.
- Jangan menjalankan `php artisan db:seed` pada database operasional tanpa memahami seluruh seeder. Seeder memperbarui akun bootstrap dan menjalankan seeder master data lain.
- Untuk seeding pada production, isi `AMBAJA_ADMIN_PASSWORD` dan `AMBAJA_OWNER_PASSWORD` pada environment server terlebih dahulu. Jangan menyimpan password tersebut di Git.
- Jika akun demo bootstrap sudah digunakan, ganti password-nya dengan password unik sebelum aplikasi dipakai operasional.
- Patch ini tidak menyertakan `.env`, `.git`, `vendor`, atau `node_modules`.

## Validasi yang dilakukan saat penyusunan patch

- PHP syntax lint untuk file PHP project berhasil.
- Route list berhasil dibangun; route User Management terdaftar dan route registrasi publik tidak terdaftar.
- Blade template yang diubah berhasil dikompilasi oleh Blade compiler.
- Uji integrasi PHPUnit belum dapat dijalankan di lingkungan penyusunan patch karena ekstensi PHP seperti DOM, mbstring, XMLWriter, dan driver database SQLite tidak tersedia. Jalankan `php artisan test` di Laragon untuk validasi penuh.

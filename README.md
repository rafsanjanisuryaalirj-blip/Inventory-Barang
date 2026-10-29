# Inventaris Barang

Aplikasi inventaris barang untuk PHP Native 8+, PDO, MySQL, Bootstrap 5, dan JavaScript. Aplikasi memakai login session, proteksi CSRF, validasi server, serta upload foto yang divalidasi MIME dan ukurannya.

## Instalasi XAMPP

1. Letakkan folder proyek di `C:\xampp\htdocs\inventory`.
2. Jalankan Apache dan MySQL dari XAMPP Control Panel.
3. Buka phpMyAdmin, pilih **Import**, lalu impor `database.sql`. SQL membuat database `database`, tabel `users`, `kategori`, `barang`, serta kategori awal. Tidak ada akun bawaan; akun pertama dibuat melalui halaman registrasi. Tabel `barang` menyimpan `id_user`, sehingga data setiap akun terpisah. Jika database lama sudah memiliki tabel `barang` tanpa kolom `id_user`, hapus tabel `barang` lama lalu impor ulang `database.sql`.
4. Periksa [config/database.php](./config/database.php). Nilai default XAMPP: host `localhost`, database `database`, user `root`, password kosong, port `3306`, charset `utf8mb4`. Nilai dapat dioverride melalui environment variable `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, dan `DB_PORT`.
5. Nama database default aplikasi adalah `database`. Jika memakai nama lain, set environment variable `DB_NAME` dan sesuaikan `USE` pada `database.sql`.
6. Pastikan PHP mengaktifkan `PDO_MYSQL` dan `fileinfo`. Untuk pesan teknis koneksi hanya di komputer pengembangan, set environment `APP_ENV=development`. Jangan aktifkan mode ini pada server publik. Default aman tidak menampilkan detail exception ke pengguna.
7. Pastikan Apache memiliki izin tulis ke `img/barang/`. Foto maksimal 2 MB; `upload_max_filesize` dan `post_max_size` pada `php.ini` harus mencukupi. Restart Apache setelah mengubah PHP configuration.
8. Akses `http://localhost/inventory/`. Bootstrap, Bootstrap Icons, dan Inter dimuat dari CDN.

### Jika koneksi database gagal

- Pastikan MySQL berstatus aktif di XAMPP.
- Pastikan `DB_NAME` sama dengan database tempat tabel diimpor; nama database default aplikasi adalah `database`.
- Cocokkan host, port (`3306`), username, dan password dengan konfigurasi MySQL setempat.
- Pastikan `PDO_MYSQL` aktif, lalu restart Apache setelah mengubah `php.ini`.
- Pastikan database berisi tabel `users`, `kategori`, dan `barang`; impor `database.sql` jika belum.
- Di mode development, pesan error menambahkan detail PDO untuk membantu diagnosis. Pada mode normal, pengguna hanya melihat pesan umum dan detail tercatat di error log PHP.

## Akun pengguna

Aplikasi tidak menyimpan akun atau kredensial bawaan di kode, SQL, maupun antarmuka. Buat akun pertama melalui halaman **Daftar** (`auth/register.php`). Password disimpan sebagai hash dari `password_hash()` dengan algoritma default, bukan teks biasa.

## Alur aplikasi untuk uji kompetensi

1. Pengguna membuka aplikasi; belum login otomatis diarahkan ke halaman login.
2. Pengguna mendaftar melalui halaman registrasi, lalu masuk dengan username/password dan token CSRF. Server mencari akun secara prepared statement, memverifikasi `password_verify()`, membatasi lima percobaan gagal selama lima menit, lalu memperbarui ID session setelah autentikasi berhasil.
3. Pengguna masuk ke dashboard yang menampilkan jumlah barang, total stok, total nilai, barang stok menipis, serta data terbaru.
4. Dari menu Data Barang, pengguna dapat mencari, memfilter, melihat grid/tabel dan pagination, lalu membuka detail, menambah atau mengedit barang. Foto baru diperiksa tipe, MIME, ukuran, dan validitas gambarnya sebelum disimpan.
5. Menu Laporan menampilkan daftar barang beserta total stok dan total nilai inventaris, dapat difilter per kategori, dan dicetak menjadi PDF (library FPDF) melalui tombol **Cetak PDF**.
6. Penghapusan hanya memakai POST dan token CSRF, serta meminta konfirmasi. Sesi diakhiri melalui tombol Logout yang juga memakai POST dan token CSRF.

## Fitur

- Registrasi akun, login session, proteksi CSRF, dan pembatasan percobaan login.
- Isolasi data per akun: setiap pengguna hanya melihat, mengubah, dan menghapus barang miliknya (`barang.id_user`). Akun baru selalu mulai dengan data kosong.
- Sidebar responsif, dashboard, breadcrumb, notifikasi toast, avatar pengguna, dan halaman login satu kolom.
- CRUD barang dengan validasi server, kode unik, stok/harga nonnegatif, kategori, dan foto.
- Grid/tabel responsif, pencarian nama/kode, filter kategori yang diterapkan saat kategori dipilih, pagination, status stok, dan empty state.
- Laporan barang dengan total stok dan total nilai, filter kategori, serta cetak PDF memakai library FPDF.
- Foto JPG/JPEG, PNG, atau WEBP maksimal 2 MB. Foto lama dipertahankan ketika edit tanpa upload baru; file yang tidak lagi dipakai dibersihkan.
- Semua halaman aplikasi dan barang dilindungi login. Password tidak pernah disimpan dalam teks biasa.

## Checklist pengujian

- [ ] Impor `database.sql`; pastikan tabel `users`, `kategori`, `barang` dan kategori awal tersedia.
- [ ] Daftar akun baru; pastikan username duplikat, password pendek, konfirmasi tidak sama, dan field kosong ditolak.
- [ ] Daftar dua akun berbeda; pastikan akun kedua tidak melihat barang akun pertama, dan akun baru mulai dengan dashboard/laporan kosong.
- [ ] Login dengan akun yang dibuat; pastikan masuk ke dashboard.
- [ ] Uji username salah dan password salah; pesan tetap sama, lalu uji penguncian setelah lima percobaan dan pemulihan setelah lima menit.
- [ ] Buka `dashboard.php`, `laporan.php`, dan setiap halaman `barang/*` tanpa login; pastikan diarahkan ke login.
- [ ] Pastikan session ID berubah setelah login dan token CSRF kosong/salah menolak login/logout/hapus.
- [ ] Logout melalui konfirmasi; pastikan halaman internal kembali meminta login.
- [ ] Tambah barang dengan foto valid; uji field kosong, kode duplikat, stok/harga negatif, foto bukan gambar, MIME/ekstensi berbeda, dan ukuran di atas 2 MB.
- [ ] Edit barang tanpa mengganti foto dan dengan foto baru; pastikan preview dan file tersimpan benar.
- [ ] Cari berdasarkan sebagian nama/kode, pastikan pilihan kategori langsung memfilter tanpa tombol Filter, gunakan Reset, dan uji pagination dengan lebih dari delapan barang.
- [ ] Buka Laporan, uji filter kategori, dan tekan **Cetak PDF**; pastikan PDF berisi tabel, total stok, dan total nilai.
- [ ] Uji tampilan grid/tabel, detail, modal konfirmasi hapus, fallback foto hilang, serta layout mobile.
- [ ] Uji database tidak tersedia di mode normal dan `APP_ENV=development`; detail teknis hanya terlihat di mode development.

## Struktur utama

```text
inventory/
├── auth/ (login.php, register.php, logout.php)
├── config/ (database.php)
├── includes/ (auth.php, header.php, sidebar.php, footer.php, functions.php, barang-form-fields.php)
├── barang/ (index.php, detail.php, tambah.php, edit.php, hapus.php)
├── lib/ (fpdf/fpdf.php)
├── assets/ (css/style.css, js/script.js)
├── img/ (no-image.png, barang/)
├── dashboard.php
├── laporan.php
├── laporan-cetak.php
├── index.php
└── database.sql
```

<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/barang.php';
requireLogin();
$userId = currentUserId();

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$barang = null;
$loadError = null;
if ($id !== false && $id !== null && $id > 0) {
    try {
        require dirname(__DIR__) . '/config/database.php';
        $barang = detailBarang($pdo, $id, $userId);
    } catch (PDOException $exception) {
        error_log('Gagal memuat detail barang: ' . $exception->getMessage());
        $loadError = databaseErrorMessage($exception, 'Detail barang gagal dimuat. Periksa koneksi database.');
    }
}

$pageTitle = 'Detail Barang';
$currentPage = 'detail';
require dirname(__DIR__) . '/includes/header.php';
?>
<?php if ($loadError !== null): ?>
    <div class="alert alert-danger"><?= e($loadError) ?></div>
<?php elseif ($barang === null): ?>
    <div class="alert alert-warning">Barang tidak ditemukan atau ID tidak valid.</div>
<?php else: ?>
    <div class="detail-layout">
        <div class="detail-image-panel">
                <img class="detail-photo" src="<?= e(imageUrl($barang['foto'])) ?>" alt="Foto <?= e($barang['nama_barang']) ?>">
        </div>
        <div class="detail-info-panel">
            <span class="category-badge"><?= e($barang['nama_kategori']) ?></span>
            <h2><?= e($barang['nama_barang']) ?></h2>
            <span class="detail-code"><?= e($barang['kode_barang']) ?></span>
            <strong class="detail-price"><?= e(formatRupiah($barang['harga'])) ?></strong>
            <dl class="detail-list">
                <div><dt>Stok tersedia</dt><dd><?= e($barang['stok']) ?> <?= e($barang['satuan']) ?></dd></div>
                <div><dt>Satuan</dt><dd><?= e($barang['satuan']) ?></dd></div>
                <div><dt>Kategori</dt><dd><?= e($barang['nama_kategori']) ?></dd></div>
                <div><dt>Dibuat</dt><dd><?= e($barang['created_at']) ?></dd></div>
                <div><dt>Terakhir diubah</dt><dd><?= e($barang['updated_at']) ?></dd></div>
                <div class="detail-description"><dt>Deskripsi</dt><dd class="description-text"><?= $barang['deskripsi'] !== null && $barang['deskripsi'] !== '' ? e($barang['deskripsi']) : 'Tidak ada deskripsi.' ?></dd></div>
            </dl>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-primary" href="<?= e(appUrl('/barang/edit.php?id=' . $barang['id_barang'])) ?>"><i class="bi bi-pencil-square"></i>Edit Barang</a>
                <form method="post" action="<?= e(appUrl('/barang/hapus.php')) ?>" data-confirm="Hapus barang <?= e($barang['nama_barang']) ?>? Tindakan ini tidak dapat dibatalkan.">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="id_barang" value="<?= e($barang['id_barang']) ?>">
                    <button class="btn btn-danger" type="submit"><i class="bi bi-trash3"></i>Hapus</button>
                </form>
                <a class="btn btn-secondary" href="<?= e(appUrl('/barang/index.php')) ?>">Kembali</a>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>

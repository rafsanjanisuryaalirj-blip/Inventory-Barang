<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/barang.php';
requireLogin();

$pageTitle = 'Laporan';
$currentPage = 'report';
$barangList = [];
$categories = [];
$categoryId = inputText($_GET['kategori'] ?? null);
$totals = ['jumlah_baris' => 0, 'total_stok' => 0, 'total_nilai' => 0.0];
$loadError = null;

try {
    require __DIR__ . '/config/database.php';
    $categories = daftarKategori($pdo);
    $idKategori = $categoryId !== '' && ctype_digit($categoryId) && (int) $categoryId > 0 ? (int) $categoryId : null;
    $barangList = ambilSemuaBarang($pdo, $idKategori);
    $totals = hitungTotalBarang($barangList);
} catch (PDOException $exception) {
    error_log('Gagal memuat laporan: ' . $exception->getMessage());
    $loadError = databaseErrorMessage($exception, 'Laporan gagal dimuat. Periksa konfigurasi dan tabel database.');
}

$pdfUrl = appUrl('/laporan-cetak.php');
if ($categoryId !== '' && ctype_digit($categoryId) && (int) $categoryId > 0) {
    $pdfUrl .= '?kategori=' . (int) $categoryId;
}

require __DIR__ . '/includes/header.php';
?>
<?php if ($loadError !== null): ?>
    <div class="alert alert-danger"><?= e($loadError) ?></div>
<?php else: ?>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <form class="filter-panel" method="get" action="<?= e(appUrl('/laporan.php')) ?>">
            <select class="form-select category-select" id="kategori" name="kategori" aria-label="Filter kategori" data-auto-submit>
                <option value="">Semua kategori</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= e($category['id_kategori']) ?>" <?= $categoryId === (string) $category['id_kategori'] ? 'selected' : '' ?>><?= e($category['nama_kategori']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($categoryId !== ''): ?><a class="btn btn-secondary btn-reset" href="<?= e(appUrl('/laporan.php')) ?>">Reset</a><?php endif; ?>
        </form>
        <a class="btn btn-primary" href="<?= e($pdfUrl) ?>" target="_blank" rel="noopener"><i class="bi bi-file-earmark-pdf"></i>Cetak PDF</a>
    </div>
    <section class="stats-grid" aria-label="Ringkasan laporan">
        <article class="stat-card"><strong><?= number_format($totals['jumlah_baris']) ?></strong><span class="stat-label">Jumlah Barang</span></article>
        <article class="stat-card"><strong><?= number_format($totals['total_stok']) ?></strong><span class="stat-label">Total Stok</span></article>
        <article class="stat-card"><strong><?= e(formatRupiah($totals['total_nilai'])) ?></strong><span class="stat-label">Total Nilai Inventaris</span></article>
    </section>
    <section class="panel-card">
        <div class="panel-heading"><h2>Daftar barang</h2><span><?= number_format($totals['jumlah_baris']) ?> baris</span></div>
        <?php if ($barangList === []): ?>
            <p class="panel-empty">Belum ada barang untuk dilaporkan.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table app-table align-middle mb-0">
                    <thead><tr><th>No</th><th>Kode</th><th>Nama Barang</th><th>Kategori</th><th>Stok</th><th>Harga</th><th>Subtotal</th></tr></thead>
                    <tbody>
                        <?php foreach ($barangList as $index => $barang): ?>
                            <?php $subtotal = (int) $barang['stok'] * (float) $barang['harga']; ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= e($barang['kode_barang']) ?></td>
                                <td><?= e($barang['nama_barang']) ?></td>
                                <td><?= e($barang['nama_kategori']) ?></td>
                                <td><?= e($barang['stok']) ?> <?= e($barang['satuan']) ?></td>
                                <td><?= e(formatRupiah($barang['harga'])) ?></td>
                                <td class="fw-semibold"><?= e(formatRupiah($subtotal)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4">Total</th>
                            <th><?= e($totals['total_stok']) ?></th>
                            <th></th>
                            <th><?= e(formatRupiah($totals['total_nilai'])) ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>

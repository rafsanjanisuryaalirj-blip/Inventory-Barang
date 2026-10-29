<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
requireLogin();

$pageTitle = 'Dashboard';
$currentPage = 'dashboard';
$stats = ['items' => 0, 'stock' => 0, 'value' => 0.0, 'low' => 0];
$recentItems = [];
$lowStockItems = [];
$loadError = null;

try {
    require __DIR__ . '/config/database.php';
    $stats['items'] = (int) $pdo->query('SELECT COUNT(*) FROM barang')->fetchColumn();
    $stats['stock'] = (int) $pdo->query('SELECT COALESCE(SUM(stok), 0) FROM barang')->fetchColumn();
    $stats['value'] = (float) $pdo->query('SELECT COALESCE(SUM(stok * harga), 0) FROM barang')->fetchColumn();
    $stats['low'] = (int) $pdo->query('SELECT COUNT(*) FROM barang WHERE stok <= 5')->fetchColumn();
    $recentItems = $pdo->query(
        'SELECT b.id_barang, b.kode_barang, b.nama_barang, b.stok, b.satuan, b.harga, b.foto, k.nama_kategori
         FROM barang b INNER JOIN kategori k ON k.id_kategori = b.id_kategori
         ORDER BY b.created_at DESC, b.id_barang DESC LIMIT 5'
    )->fetchAll();
    $lowStockItems = $pdo->query(
        'SELECT id_barang, kode_barang, nama_barang, stok, satuan
         FROM barang WHERE stok <= 5 ORDER BY stok ASC, nama_barang ASC LIMIT 6'
    )->fetchAll();
} catch (PDOException $exception) {
    error_log('Gagal memuat dashboard: ' . $exception->getMessage());
    $loadError = databaseErrorMessage($exception, 'Dashboard gagal dimuat. Periksa konfigurasi dan tabel database.');
}

require __DIR__ . '/includes/header.php';
?>
<?php if ($loadError !== null): ?><div class="alert alert-danger"><?= e($loadError) ?></div><?php else: ?>
<section class="stats-grid" aria-label="Statistik inventaris">
    <article class="stat-card"><strong><?= number_format($stats['items']) ?></strong><span class="stat-label">Total Barang</span></article>
    <article class="stat-card"><strong><?= number_format($stats['stock']) ?></strong><span class="stat-label">Total Stok</span></article>
    <article class="stat-card"><strong><?= e(formatRupiah($stats['value'])) ?></strong><span class="stat-label">Total Nilai</span></article>
    <article class="stat-card"><strong><?= number_format($stats['low']) ?></strong><span class="stat-label">Stok Menipis (≤ 5)</span></article>
</section>
<div class="dashboard-grid">
    <section class="panel-card recent-panel">
        <div class="panel-heading"><h2>Barang terbaru</h2><a class="text-link" href="<?= e(appUrl('/barang/index.php')) ?>">Lihat semua</a></div>
        <?php if ($recentItems === []): ?>
            <p class="panel-empty">Belum ada barang yang ditambahkan.</p>
        <?php else: ?>
            <div class="table-responsive"><table class="table app-table align-middle mb-0"><thead><tr><th>Barang</th><th>Kategori</th><th>Stok</th><th>Harga</th></tr></thead><tbody>
                <?php foreach ($recentItems as $item): ?>
                    <tr><td><a class="table-product" href="<?= e(appUrl('/barang/detail.php?id=' . $item['id_barang'])) ?>"><img src="<?= e(imageUrl($item['foto'])) ?>" alt=""><span><?= e($item['nama_barang']) ?></span></a></td><td><?= e($item['nama_kategori']) ?></td><td><?= e($item['stok']) ?> <?= e($item['satuan']) ?></td><td class="fw-semibold"><?= e(formatRupiah($item['harga'])) ?></td></tr>
                <?php endforeach; ?>
            </tbody></table></div>
        <?php endif; ?>
    </section>
    <section class="panel-card low-stock-panel">
        <div class="panel-heading"><h2>Stok menipis</h2><span><?= count($lowStockItems) ?> barang</span></div>
        <?php if ($lowStockItems === []): ?>
            <p class="panel-empty">Tidak ada stok menipis.</p>
        <?php else: ?>
            <div class="table-responsive"><table class="table app-table align-middle mb-0"><thead><tr><th>Barang</th><th>Kode</th><th>Stok</th></tr></thead><tbody>
                <?php foreach ($lowStockItems as $item): ?>
                    <tr><td><a href="<?= e(appUrl('/barang/detail.php?id=' . $item['id_barang'])) ?>"><?= e($item['nama_barang']) ?></a></td><td><?= e($item['kode_barang']) ?></td><td><span class="stock-badge <?= (int) $item['stok'] === 0 ? 'stock-empty' : 'stock-low' ?>"><?= e($item['stok']) ?> <?= e($item['satuan']) ?></span></td></tr>
                <?php endforeach; ?>
            </tbody></table></div>
        <?php endif; ?>
    </section>
</div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>

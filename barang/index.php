<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
requireLogin();
$userId = currentUserId();

$pageTitle = 'Data Barang';
$currentPage = 'list';
$itemsPerPage = 8;
$search = inputText($_GET['q'] ?? null);
$categoryId = inputText($_GET['kategori'] ?? null);
$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT);
$page = $page !== false && $page > 0 ? $page : 1;
$barangList = [];
$categories = [];
$totalPages = 1;
$totalItems = 0;
$loadError = null;

try {
    require dirname(__DIR__) . '/config/database.php';
    $categories = $pdo->query('SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori')->fetchAll();

    $conditions = ['b.id_user = :user'];
    $parameters = [':user' => $userId];
    if ($search !== '') {
        $conditions[] = '(b.nama_barang LIKE :search_nama OR b.kode_barang LIKE :search_kode)';
        $parameters[':search_nama'] = '%' . $search . '%';
        $parameters[':search_kode'] = '%' . $search . '%';
    }
    if ($categoryId !== '' && ctype_digit($categoryId) && (int) $categoryId > 0) {
        $conditions[] = 'b.id_kategori = :kategori';
        $parameters[':kategori'] = (int) $categoryId;
    }
    $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';

    $countStatement = $pdo->prepare('SELECT COUNT(*) FROM barang b' . $where);
    $countStatement->execute($parameters);
    $totalItems = (int) $countStatement->fetchColumn();
    $totalPages = max(1, (int) ceil($totalItems / $itemsPerPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $itemsPerPage;

    $sql = 'SELECT b.id_barang, b.kode_barang, b.nama_barang, b.stok, b.satuan, b.harga, b.foto, k.nama_kategori
            FROM barang b INNER JOIN kategori k ON k.id_kategori = b.id_kategori' . $where . '
            ORDER BY b.created_at DESC, b.id_barang DESC LIMIT :limit OFFSET :offset';
    $statement = $pdo->prepare($sql);
    foreach ($parameters as $key => $value) {
        $statement->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $statement->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
    $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
    $statement->execute();
    $barangList = $statement->fetchAll();
} catch (PDOException $exception) {
    error_log('Gagal memuat daftar barang: ' . $exception->getMessage());
    $loadError = databaseErrorMessage($exception, 'Data gagal dimuat. Pastikan database sudah terpasang dan konfigurasi koneksi benar.');
}

require dirname(__DIR__) . '/includes/header.php';
?>
<?php if ($loadError !== null): ?>
    <div class="alert alert-danger"><?= e($loadError) ?></div>
<?php else: ?>
    <div class="inventory-toolbar">
    <form class="filter-panel" method="get" action="<?= e(appUrl('/barang/index.php')) ?>">
        <div class="search-field">
            <i class="bi bi-search"></i>
            <input class="form-control" id="q" name="q" type="search" value="<?= e($search) ?>" placeholder="Cari nama atau kode barang..." aria-label="Cari nama atau kode barang">
        </div>
        <select class="form-select category-select" id="kategori" name="kategori" aria-label="Filter kategori" data-auto-submit>
            <option value="">Semua kategori</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= e($category['id_kategori']) ?>" <?= $categoryId === (string) $category['id_kategori'] ? 'selected' : '' ?>><?= e($category['nama_kategori']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ($search !== '' || $categoryId !== ''): ?><a class="btn btn-secondary btn-reset" href="<?= e(appUrl('/barang/index.php')) ?>">Reset</a><?php endif; ?>
    </form>
    </div>

    <?php if ($barangList === []): ?>
        <section class="empty-state">
            <i class="bi bi-box-seam empty-icon" aria-hidden="true"></i>
            <h2><?= $totalItems === 0 && $search === '' && $categoryId === '' ? 'Belum ada barang' : 'Barang tidak ditemukan' ?></h2>
            <p><?= $search !== '' || $categoryId !== '' ? 'Coba ubah kata kunci atau filter kategori.' : 'Gunakan menu Tambah Barang untuk mulai mencatat persediaan.' ?></p>
            <?php if ($search !== '' || $categoryId !== ''): ?><a class="btn btn-secondary" href="<?= e(appUrl('/barang/index.php')) ?>">Hapus filter</a><?php endif; ?>
        </section>
    <?php else: ?>
        <div class="result-caption">Menampilkan <strong><?= count($barangList) ?></strong> dari <strong><?= $totalItems ?></strong> barang</div>
        <section class="inventory-grid" data-inventory-grid>
            <?php foreach ($barangList as $barang): ?>
                <?php $stockClass = (int) $barang['stok'] === 0 ? 'stock-empty' : ((int) $barang['stok'] <= 5 ? 'stock-low' : 'stock-safe'); ?>
                <article class="inventory-card">
                    <a class="inventory-card-image" href="<?= e(appUrl('/barang/detail.php?id=' . $barang['id_barang'])) ?>"><img src="<?= e(imageUrl($barang['foto'])) ?>" alt="Foto <?= e($barang['nama_barang']) ?>"></a>
                    <div class="inventory-card-body">
                        <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                            <span class="category-badge"><?= e($barang['nama_kategori']) ?></span>
                            <span class="stock-badge <?= $stockClass ?>"><?= (int) $barang['stok'] === 0 ? 'Habis' : ((int) $barang['stok'] <= 5 ? 'Menipis' : 'Tersedia') ?></span>
                        </div>
                        <h2 class="inventory-name"><a href="<?= e(appUrl('/barang/detail.php?id=' . $barang['id_barang'])) ?>"><?= e($barang['nama_barang']) ?></a></h2>
                        <span class="inventory-code"><?= e($barang['kode_barang']) ?></span>
                        <div class="inventory-card-meta"><strong><?= e(formatRupiah($barang['harga'])) ?></strong><span>Stok: <?= e($barang['stok']) ?> <?= e($barang['satuan']) ?></span></div>
                        <div class="card-actions">
                            <a class="btn btn-secondary btn-sm" href="<?= e(appUrl('/barang/detail.php?id=' . $barang['id_barang'])) ?>" aria-label="Detail <?= e($barang['nama_barang']) ?>"><i class="bi bi-eye"></i>Detail</a>
                            <a class="btn btn-secondary btn-sm" href="<?= e(appUrl('/barang/edit.php?id=' . $barang['id_barang'])) ?>" aria-label="Edit <?= e($barang['nama_barang']) ?>"><i class="bi bi-pencil-square"></i>Edit</a>
                            <form method="post" action="<?= e(appUrl('/barang/hapus.php')) ?>" data-confirm="Hapus barang <?= e($barang['nama_barang']) ?>? Tindakan ini tidak dapat dibatalkan.">
                                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                <input type="hidden" name="id_barang" value="<?= e($barang['id_barang']) ?>">
                                <button class="btn btn-danger btn-sm" type="submit" aria-label="Hapus <?= e($barang['nama_barang']) ?>"><i class="bi bi-trash3"></i>Hapus</button>
                            </form>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
        <?php if ($totalPages > 1): ?>
            <nav class="mt-4" aria-label="Navigasi halaman"><ul class="pagination app-pagination justify-content-center flex-wrap">
                <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?>
                    <li class="page-item <?= $pageNumber === $page ? 'active' : '' ?>"><a class="page-link" href="?<?= e(http_build_query(['q' => $search, 'kategori' => $categoryId, 'page' => $pageNumber])) ?>"><?= $pageNumber ?></a></li>
                <?php endfor; ?>
            </ul></nav>
        <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>

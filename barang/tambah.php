<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/barang.php';
requireLogin();

$pageTitle = 'Tambah Barang';
$currentPage = 'add';
$categories = [];
$databaseError = null;
$pdo = null;
try {
    require dirname(__DIR__) . '/config/database.php';
    $categories = daftarKategori($pdo);
} catch (PDOException $exception) {
    error_log('Gagal menyiapkan form barang: ' . $exception->getMessage());
    $databaseError = databaseErrorMessage($exception, 'Form tidak dapat digunakan karena koneksi database bermasalah.');
}
$values = barangValues([]);
$errors = [];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $values = barangValues($_POST);
    if (!$pdo instanceof PDO) {
        $errors['form'] = $databaseError ?? 'Koneksi database tidak tersedia.';
    } elseif (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Sesi formulir tidak valid. Muat ulang halaman dan coba kembali.';
    } else {
        try {
            $errors = validateBarang($_POST, $pdo);
        } catch (PDOException $exception) {
            error_log('Gagal memvalidasi barang baru: ' . $exception->getMessage());
            $errors['form'] = databaseErrorMessage($exception, 'Validasi gagal karena terjadi kesalahan database. Silakan coba kembali.');
        }
        if (!isset($_FILES['foto']) || ($_FILES['foto']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $errors['foto'] = 'Foto wajib diunggah saat menambahkan barang.';
        }
    }

    if ($errors === []) {
        $newPhoto = null;
        try {
            $newPhoto = uploadFoto($_FILES['foto']);
            tambahBarang($pdo, $values, $newPhoto);
            setFlash('success', 'Barang berhasil ditambahkan.');
            redirect(appUrl('/barang/index.php'));
        } catch (RuntimeException $exception) {
            $errors['foto'] = $exception->getMessage();
        } catch (PDOException $exception) {
            if ($newPhoto !== null) {
                removeFoto($newPhoto);
            }
            error_log('Gagal menambahkan barang: ' . $exception->getMessage());
            $errors['form'] = $exception->getCode() === '23000'
                ? 'Kode barang sudah digunakan.'
                : databaseErrorMessage($exception, 'Barang gagal disimpan karena terjadi kesalahan database.');
        }
    }
}

require dirname(__DIR__) . '/includes/header.php';
?>
<?php if ($databaseError !== null): ?><div class="alert alert-danger"><?= e($databaseError) ?><p class="mb-0 mt-2 small">Periksa MySQL, nama database, port 3306, kredensial, dan pastikan database.sql sudah diimpor.</p></div><?php endif; ?>
<?php if (isset($errors['form'])): ?><div class="alert alert-danger"><?= e($errors['form']) ?></div><?php endif; ?>
<?php if ($pdo instanceof PDO): ?>
<form class="form-panel" method="post" enctype="multipart/form-data" data-loading-form>
    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
    <?php require dirname(__DIR__) . '/includes/barang-form-fields.php'; ?>
    <div class="form-actions">
        <button class="btn btn-primary" type="submit"><span data-button-label>Simpan</span></button>
        <a class="btn btn-secondary" href="<?= e(appUrl('/barang/index.php')) ?>">Batal</a>
    </div>
</form>
<?php endif; ?>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>

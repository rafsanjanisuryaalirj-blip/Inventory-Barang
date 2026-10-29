<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/barang.php';
requireLogin();
$userId = currentUserId();
$id = filter_var($_GET['id'] ?? $_POST['id_barang'] ?? null, FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id < 1) {
    setFlash('warning', 'ID barang tidak valid.');
    redirect(appUrl('/barang/index.php'));
}

$pdo = null;
$barang = null;
$categories = [];
$databaseError = null;
try {
    require dirname(__DIR__) . '/config/database.php';
    $barang = cariBarang($pdo, $id, $userId);
    $categories = daftarKategori($pdo);
} catch (PDOException $exception) {
    error_log('Gagal menyiapkan edit barang: ' . $exception->getMessage());
    $databaseError = databaseErrorMessage($exception, 'Data tidak dapat dimuat karena koneksi database bermasalah.');
}
if (!$barang && $databaseError === null) {
    setFlash('warning', 'Barang yang akan diedit tidak ditemukan.');
    redirect(appUrl('/barang/index.php'));
}

$pageTitle = 'Edit Barang';
$values = barangValues($barang ?? []);
$errors = [];
$currentPhoto = $barang['foto'] ?? null;
$isEdit = true;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $barang !== null && $pdo instanceof PDO) {
    $values = barangValues($_POST);
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Sesi formulir tidak valid. Muat ulang halaman dan coba kembali.';
    } else {
        try {
            $errors = validateBarang($_POST, $pdo, $userId, (int) $id);
        } catch (PDOException $exception) {
            error_log('Gagal memvalidasi perubahan barang: ' . $exception->getMessage());
            $errors['form'] = databaseErrorMessage($exception, 'Validasi gagal karena terjadi kesalahan database. Silakan coba kembali.');
        }
    }

    $hasNewPhoto = isset($_FILES['foto']) && ($_FILES['foto']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    if ($errors === []) {
        $newPhoto = null;
        try {
            if ($hasNewPhoto) {
                $newPhoto = uploadFoto($_FILES['foto']);
            }
            ubahBarang($pdo, $id, $userId, $values, $newPhoto ?? $currentPhoto);
            if ($newPhoto !== null && !removeFoto($currentPhoto)) {
                setFlash('warning', 'Data berhasil diperbarui, tetapi foto lama gagal dihapus. Hapus secara manual dari img/barang/.');
            } else {
                setFlash('success', 'Data barang berhasil diperbarui.');
            }
            redirect(appUrl('/barang/index.php'));
        } catch (RuntimeException $exception) {
            $errors['foto'] = $exception->getMessage();
        } catch (PDOException $exception) {
            if ($newPhoto !== null) {
                removeFoto($newPhoto);
            }
            error_log('Gagal memperbarui barang: ' . $exception->getMessage());
            $errors['form'] = $exception->getCode() === '23000'
                ? 'Kode barang sudah digunakan.'
                : databaseErrorMessage($exception, 'Data barang gagal diperbarui karena terjadi kesalahan database.');
        }
    }
}

require dirname(__DIR__) . '/includes/header.php';
?>
<?php if ($databaseError !== null): ?><div class="alert alert-danger"><?= e($databaseError) ?></div><?php endif; ?>
<?php if ($barang !== null): ?>
<?php if (isset($errors['form'])): ?><div class="alert alert-danger"><?= e($errors['form']) ?></div><?php endif; ?>
<form class="form-panel" method="post" enctype="multipart/form-data" data-loading-form>
    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
    <input type="hidden" name="id_barang" value="<?= e($id) ?>">
    <?php require dirname(__DIR__) . '/includes/barang-form-fields.php'; ?>
    <div class="form-actions">
        <button class="btn btn-primary" type="submit"><span data-button-label>Simpan</span></button>
        <a class="btn btn-secondary" href="<?= e(appUrl('/barang/index.php')) ?>">Batal</a>
    </div>
</form>
<?php endif; ?>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>

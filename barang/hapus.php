<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/barang.php';
requireLogin();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Metode tidak diizinkan.');
}
if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('danger', 'Token keamanan tidak valid. Barang tidak dihapus.');
    redirect(appUrl('/barang/index.php'));
}

$id = filter_var($_POST['id_barang'] ?? null, FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id < 1) {
    setFlash('warning', 'ID barang tidak valid.');
    redirect(appUrl('/barang/index.php'));
}

try {
    require dirname(__DIR__) . '/config/database.php';
    $foto = hapusBarang($pdo, $id);
    if ($foto === null) {
        setFlash('warning', 'Barang tidak ditemukan.');
    } elseif (!removeFoto($foto)) {
        setFlash('warning', 'Data barang terhapus, tetapi file foto gagal dihapus. Hapus foto secara manual dari img/barang/.');
    } else {
        setFlash('success', 'Barang dan foto berhasil dihapus.');
    }
} catch (PDOException $exception) {
    error_log('Gagal menghapus barang: ' . $exception->getMessage());
    setFlash('danger', databaseErrorMessage($exception, 'Barang gagal dihapus karena terjadi kesalahan database.'));
}

redirect(appUrl('/barang/index.php'));

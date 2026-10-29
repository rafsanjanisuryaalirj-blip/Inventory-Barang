<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (!defined('INVENTORY_APP')) {
    define('INVENTORY_APP', true);
}

function e(mixed $value): string
{
    return htmlspecialchars(is_scalar($value) || $value === null ? (string) $value : '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function inputText(mixed $value): string
{
    return is_scalar($value) ? trim((string) $value) : '';
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function appUrl(string $path): string
{
    $scriptDirectory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    if (in_array(basename($scriptDirectory), ['barang', 'auth'], true)) {
        $lastSlash = strrpos($scriptDirectory, '/');
        $scriptDirectory = $lastSlash === false ? '' : substr($scriptDirectory, 0, $lastSlash);
    }
    $basePath = rtrim($scriptDirectory, '/');

    return $basePath . '/' . ltrim($path, '/');
}

function databaseErrorMessage(PDOException $exception, string $publicMessage): string
{
    if (getenv('APP_ENV') === 'development') {
        return $publicMessage . ' Detail developer: ' . $exception->getMessage();
    }

    return $publicMessage;
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return is_array($flash) ? $flash : null;
}

function csrfToken(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(?string $token): bool
{
    return isset($_SESSION['csrf_token'])
        && is_string($token)
        && hash_equals($_SESSION['csrf_token'], $token);
}

function formatRupiah(string|int|float|null $amount): string
{
    return 'Rp ' . number_format((float) $amount, 0, ',', '.');
}

function hitungSubtotal(int|float|string $stok, int|float|string $harga): float
{
    return round((int) $stok * (float) $harga, 2);
}

function hitungTotalBarang(array $daftarBarang): array
{
    $totalStok = 0;
    $totalNilai = 0.0;
    foreach ($daftarBarang as $barang) {
        $totalStok += (int) ($barang['stok'] ?? 0);
        $totalNilai += hitungSubtotal($barang['stok'] ?? 0, $barang['harga'] ?? 0);
    }

    return [
        'jumlah_baris' => count($daftarBarang),
        'total_stok' => $totalStok,
        'total_nilai' => $totalNilai,
    ];
}

function potongTeks(string $teks, int $maksimal): string
{
    return mb_strlen($teks) > $maksimal ? rtrim(mb_substr($teks, 0, $maksimal - 3)) . '...' : $teks;
}

function teksLatin(?string $teks): string
{
    $teks = (string) $teks;
    return function_exists('iconv') ? (string) iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $teks) : $teks;
}

function imageUrl(?string $filename): string
{
    $placeholder = appUrl('/img/no-image.png');
    if (!$filename || basename($filename) !== $filename) {
        return $placeholder;
    }

    $filePath = dirname(__DIR__) . '/img/barang/' . $filename;
    return is_file($filePath) ? appUrl('/img/barang/' . rawurlencode($filename)) : $placeholder;
}

function uploadFoto(array $file): string
{
    if (!isset($file['error'], $file['tmp_name']) || is_array($file['error'])) {
        throw new RuntimeException('File foto tidak valid.');
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $messages = [
            UPLOAD_ERR_INI_SIZE => 'Ukuran foto melebihi batas konfigurasi server.',
            UPLOAD_ERR_FORM_SIZE => 'Ukuran foto melebihi batas 2 MB.',
            UPLOAD_ERR_PARTIAL => 'Foto hanya terunggah sebagian. Silakan coba lagi.',
            UPLOAD_ERR_NO_FILE => 'Foto wajib diunggah.',
        ];
        throw new RuntimeException($messages[$file['error']] ?? 'Foto gagal diunggah.');
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Sumber file foto tidak valid.');
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('Ukuran foto maksimal 2 MB.');
    }

    $allowedTypes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
    ];
    $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!isset($allowedTypes[$extension]) || $allowedTypes[$extension] !== $mimeType) {
        throw new RuntimeException('Format foto harus JPG, JPEG, PNG, atau WEBP yang valid.');
    }
    if (@getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException('File yang dipilih bukan gambar yang valid.');
    }

    $directory = dirname(__DIR__) . '/img/barang';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('Folder penyimpanan foto tidak dapat dibuat.');
    }

    $filename = 'brg_' . date('Ymd') . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
    if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $filename)) {
        throw new RuntimeException('Foto gagal disimpan ke folder barang.');
    }

    return $filename;
}

function removeFoto(?string $filename): bool
{
    if (!$filename || basename($filename) !== $filename) {
        return true;
    }

    $path = dirname(__DIR__) . '/img/barang/' . $filename;
    return !is_file($path) || unlink($path);
}

function validateBarang(array $input, PDO $pdo, int $userId, ?int $barangId = null): array
{
    $errors = [];
    $kode = inputText($input['kode_barang'] ?? null);
    $nama = inputText($input['nama_barang'] ?? null);
    $kategori = filter_var($input['id_kategori'] ?? null, FILTER_VALIDATE_INT);
    $stokRaw = inputText($input['stok'] ?? null);
    $satuan = inputText($input['satuan'] ?? null);
    $hargaRaw = inputText($input['harga'] ?? null);
    $deskripsi = inputText($input['deskripsi'] ?? null);

    if ($kode === '' || strlen($kode) > 20) {
        $errors['kode_barang'] = 'Kode barang wajib diisi (maksimal 20 karakter).';
    } elseif (!preg_match('/^[A-Za-z0-9._-]+$/', $kode)) {
        $errors['kode_barang'] = 'Kode hanya boleh berisi huruf, angka, titik, garis bawah, dan tanda hubung.';
    } else {
        $sql = 'SELECT COUNT(*) FROM barang WHERE kode_barang = :kode AND id_user = :user';
        $params = [':kode' => $kode, ':user' => $userId];
        if ($barangId !== null) {
            $sql .= ' AND id_barang <> :id';
            $params[':id'] = $barangId;
        }
        $statement = $pdo->prepare($sql);
        $statement->execute($params);
        if ((int) $statement->fetchColumn() > 0) {
            $errors['kode_barang'] = 'Kode barang sudah digunakan.';
        }
    }

    if ($nama === '' || mb_strlen($nama) > 150) {
        $errors['nama_barang'] = 'Nama barang wajib diisi (maksimal 150 karakter).';
    }
    if ($kategori === false || $kategori < 1) {
        $errors['id_kategori'] = 'Silakan pilih kategori.';
    } else {
        $statement = $pdo->prepare('SELECT COUNT(*) FROM kategori WHERE id_kategori = ?');
        $statement->execute([$kategori]);
        if ((int) $statement->fetchColumn() === 0) {
            $errors['id_kategori'] = 'Kategori yang dipilih tidak tersedia.';
        }
    }
    if ($stokRaw === '' || filter_var($stokRaw, FILTER_VALIDATE_INT) === false || (int) $stokRaw < 0 || (float) $stokRaw > 2147483647) {
        $errors['stok'] = 'Stok harus berupa bilangan bulat nol atau lebih.';
    }
    if ($satuan === '' || mb_strlen($satuan) > 20) {
        $errors['satuan'] = 'Satuan wajib diisi (maksimal 20 karakter).';
    }
    if ($hargaRaw === '' || !preg_match('/^\d+(?:\.\d{1,2})?$/', $hargaRaw)) {
        $errors['harga'] = 'Harga harus berupa angka nol atau lebih.';
    } elseif (strlen(ltrim(explode('.', $hargaRaw, 2)[0], '0')) > 10) {
        $errors['harga'] = 'Harga melebihi batas maksimum.';
    } elseif ((float) $hargaRaw > 9999999999.99) {
        $errors['harga'] = 'Harga melebihi batas maksimum.';
    }
    if (mb_strlen($deskripsi) > 5000) {
        $errors['deskripsi'] = 'Deskripsi maksimal 5.000 karakter.';
    }

    return $errors;
}

function barangValues(array $input): array
{
    return [
        'kode_barang' => inputText($input['kode_barang'] ?? null),
        'nama_barang' => inputText($input['nama_barang'] ?? null),
        'id_kategori' => inputText($input['id_kategori'] ?? null),
        'stok' => inputText($input['stok'] ?? '0'),
        'satuan' => inputText($input['satuan'] ?? null),
        'harga' => inputText($input['harga'] ?? null),
        'deskripsi' => inputText($input['deskripsi'] ?? null),
    ];
}

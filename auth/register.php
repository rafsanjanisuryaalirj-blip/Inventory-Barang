<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
redirectAuthenticatedUser();

$values = ['nama_lengkap' => '', 'username' => ''];
$errors = [];
$databaseError = null;
$pdo = null;

try {
    require dirname(__DIR__) . '/config/database.php';
} catch (PDOException $exception) {
    error_log('Registrasi gagal terhubung database: ' . $exception->getMessage());
    $databaseError = databaseErrorMessage($exception, 'Pendaftaran tidak dapat diproses karena koneksi database bermasalah.');
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $values['nama_lengkap'] = inputText($_POST['nama_lengkap'] ?? null);
    $values['username'] = inputText($_POST['username'] ?? null);
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $passwordConfirm = is_string($_POST['password_confirm'] ?? null) ? $_POST['password_confirm'] : '';

    if (!$pdo instanceof PDO) {
        $errors['form'] = $databaseError ?? 'Koneksi database tidak tersedia.';
    } elseif (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Sesi formulir tidak valid. Muat ulang halaman dan coba kembali.';
    } else {
        if ($values['nama_lengkap'] === '' || mb_strlen($values['nama_lengkap']) > 100) {
            $errors['nama_lengkap'] = 'Nama lengkap wajib diisi (maksimal 100 karakter).';
        }
        if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $values['username'])) {
            $errors['username'] = 'Username 3-50 karakter, hanya huruf, angka, titik, garis bawah, dan tanda hubung.';
        }
        if (strlen($password) < 8) {
            $errors['password'] = 'Password minimal 8 karakter.';
        } elseif (strlen($password) > 72) {
            $errors['password'] = 'Password maksimal 72 karakter.';
        }
        if ($passwordConfirm !== $password) {
            $errors['password_confirm'] = 'Konfirmasi password tidak sama.';
        }

        if ($errors === []) {
            try {
                $statement = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = :username');
                $statement->execute([':username' => $values['username']]);
                if ((int) $statement->fetchColumn() > 0) {
                    $errors['username'] = 'Username sudah digunakan.';
                }
            } catch (PDOException $exception) {
                error_log('Gagal memeriksa username: ' . $exception->getMessage());
                $errors['form'] = databaseErrorMessage($exception, 'Pendaftaran gagal karena terjadi kesalahan database.');
            }
        }

        if ($errors === []) {
            try {
                $statement = $pdo->prepare(
                    'INSERT INTO users (username, password, nama_lengkap) VALUES (:username, :password, :nama)'
                );
                $statement->execute([
                    ':username' => $values['username'],
                    ':password' => password_hash($password, PASSWORD_DEFAULT),
                    ':nama' => $values['nama_lengkap'],
                ]);
                setFlash('success', 'Akun berhasil dibuat. Silakan masuk.');
                redirect(appUrl('/auth/login.php'));
            } catch (PDOException $exception) {
                error_log('Gagal menyimpan akun baru: ' . $exception->getMessage());
                $errors['form'] = $exception->getCode() === '23000'
                    ? 'Username sudah digunakan.'
                    : databaseErrorMessage($exception, 'Pendaftaran gagal karena terjadi kesalahan database.');
            }
        }
    }
}

$pageTitle = 'Daftar';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | Inventaris</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(appUrl('/assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body class="login-page">
<main class="login-layout">
    <section class="login-card">
        <a class="login-brand" href="<?= e(appUrl('/')) ?>">
            <span class="brand-mark"><i class="bi bi-box-seam"></i></span>
            <span>Inventaris</span>
        </a>
        <div class="login-heading">
            <h1>Daftar</h1>
            <p>Buat akun untuk mulai mengelola inventaris.</p>
        </div>
        <?php if (isset($errors['form'])): ?>
            <div class="alert alert-danger login-alert" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i><?= e($errors['form']) ?>
            </div>
        <?php endif; ?>
        <form method="post" class="login-form" data-loading-form>
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <div class="login-field">
                <label class="form-label" for="nama_lengkap">Nama lengkap</label>
                <div class="input-icon-wrap">
                    <i class="bi bi-person-badge"></i>
                    <input class="form-control <?= isset($errors['nama_lengkap']) ? 'is-invalid' : '' ?>" id="nama_lengkap" name="nama_lengkap" type="text" maxlength="100" value="<?= e($values['nama_lengkap']) ?>" autocomplete="name" required autofocus>
                </div>
                <?php if (isset($errors['nama_lengkap'])): ?><div class="invalid-feedback d-block"><?= e($errors['nama_lengkap']) ?></div><?php endif; ?>
            </div>
            <div class="login-field">
                <label class="form-label" for="username">Username</label>
                <div class="input-icon-wrap">
                    <i class="bi bi-person"></i>
                    <input class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>" id="username" name="username" type="text" maxlength="50" value="<?= e($values['username']) ?>" autocomplete="username" required>
                </div>
                <?php if (isset($errors['username'])): ?><div class="invalid-feedback d-block"><?= e($errors['username']) ?></div><?php endif; ?>
            </div>
            <div class="login-field">
                <label class="form-label" for="password">Password</label>
                <div class="input-icon-wrap password-input-wrap">
                    <i class="bi bi-lock"></i>
                    <input class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" id="password" name="password" type="password" autocomplete="new-password" required>
                    <button class="password-toggle" type="button" data-toggle-password aria-label="Tampilkan password"><i class="bi bi-eye"></i></button>
                </div>
                <?php if (isset($errors['password'])): ?><div class="invalid-feedback d-block"><?= e($errors['password']) ?></div><?php endif; ?>
            </div>
            <div class="login-field">
                <label class="form-label" for="password_confirm">Konfirmasi password</label>
                <div class="input-icon-wrap password-input-wrap">
                    <i class="bi bi-lock-fill"></i>
                    <input class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>" id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" required>
                    <button class="password-toggle" type="button" data-toggle-password aria-label="Tampilkan password"><i class="bi bi-eye"></i></button>
                </div>
                <?php if (isset($errors['password_confirm'])): ?><div class="invalid-feedback d-block"><?= e($errors['password_confirm']) ?></div><?php endif; ?>
            </div>
            <button class="btn btn-primary btn-login" type="submit">
                <span data-button-label>Daftar</span>
            </button>
        </form>
        <p class="login-alt">Sudah punya akun? <a href="<?= e(appUrl('/auth/login.php')) ?>">Masuk</a></p>
    </section>
</main>
<script src="<?= e(appUrl('/assets/js/script.js')) ?>"></script>
</body>
</html>

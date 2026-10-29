<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
redirectAuthenticatedUser();

$error = null;
$databaseDetails = null;
$flash = getFlash();
$lockedUntil = (int) ($_SESSION['login_locked_until'] ?? 0);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if ($lockedUntil > time()) {
        $error = 'Terlalu banyak percobaan. Coba lagi dalam ' . (int) ceil(($lockedUntil - time()) / 60) . ' menit.';
    } elseif (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi formulir tidak valid. Muat ulang halaman dan coba kembali.';
    } else {
        try {
            require dirname(__DIR__) . '/config/database.php';
            $statement = $pdo->prepare(
                'SELECT id_user, username, password, nama_lengkap, role
                 FROM users WHERE username = :username LIMIT 1'
            );
            $statement->execute([':username' => inputText($_POST['username'] ?? null)]);
            $user = $statement->fetch();
            $passwordInput = $_POST['password'] ?? null;
            $password = is_string($passwordInput) ? $passwordInput : '';
            $passwordHash = is_string($user['password'] ?? null) ? $user['password'] : null;

            if ($user && $passwordHash !== null && password_verify($password, $passwordHash)) {
                session_regenerate_id(true);
                unset($_SESSION['csrf_token']);
                csrfToken();
                $_SESSION['user'] = [
                    'id_user' => (int) $user['id_user'],
                    'username' => $user['username'],
                    'nama_lengkap' => $user['nama_lengkap'],
                    'role' => $user['role'],
                ];
                unset($_SESSION['login_attempts'], $_SESSION['login_locked_until']);
                redirect(appUrl('/dashboard.php'));
            }

            $attempts = (int) ($_SESSION['login_attempts'] ?? 0) + 1;
            if ($attempts >= 5) {
                $_SESSION['login_attempts'] = 0;
                $_SESSION['login_locked_until'] = time() + 300;
                $error = 'Terlalu banyak percobaan. Coba lagi dalam 5 menit.';
            } else {
                $_SESSION['login_attempts'] = $attempts;
                $error = 'Username atau password salah.';
            }
        } catch (PDOException $exception) {
            $error = 'Login tidak dapat diproses karena koneksi database bermasalah.';
            if (getenv('APP_ENV') === 'development') {
                error_log('Login gagal karena database: ' . $exception->getMessage());
                $databaseDetails = $exception->getMessage();
            } else {
                error_log('Login gagal karena database: ' . $exception->getMessage());
            }
        }
    }
}

$pageTitle = 'Masuk';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | Inventory</title>
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
            <h1>Masuk</h1>
            <p>Masuk dengan akun inventaris Anda.</p>
        </div>
        <?php if ($flash !== null): ?>
            <div class="alert alert-<?= e($flash['type']) ?>" role="status">
                <i class="bi bi-check-circle me-2"></i><?= e($flash['message']) ?>
            </div>
        <?php endif; ?>
        <?php if ($error !== null): ?>
            <div class="alert alert-danger login-alert" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i><?= e($error) ?>
                <?php if ($databaseDetails !== null && getenv('APP_ENV') === 'development'): ?>
                    <div class="login-developer-details"><?= e($databaseDetails) ?></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <form method="post" class="login-form" data-loading-form>
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <div class="login-field">
                <label class="form-label" for="username">Username</label>
                <div class="input-icon-wrap">
                    <i class="bi bi-person"></i>
                    <input class="form-control" id="username" name="username" type="text" autocomplete="username" maxlength="50" required autofocus>
                </div>
            </div>
            <div class="login-field">
                <label class="form-label" for="password">Password</label>
                <div class="input-icon-wrap password-input-wrap">
                    <i class="bi bi-lock"></i>
                    <input class="form-control" id="password" name="password" type="password" autocomplete="current-password" required>
                    <button class="password-toggle" type="button" data-toggle-password aria-label="Tampilkan password"><i class="bi bi-eye"></i></button>
                </div>
            </div>
            <button class="btn btn-primary btn-login" type="submit">
                <span data-button-label>Masuk</span>
            </button>
        </form>
        <p class="login-alt">Belum punya akun? <a href="<?= e(appUrl('/auth/register.php')) ?>">Daftar</a></p>
    </section>
</main>
<script src="<?= e(appUrl('/assets/js/script.js')) ?>"></script>
</body>
</html>

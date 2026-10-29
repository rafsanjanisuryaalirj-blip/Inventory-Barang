<?php
declare(strict_types=1);

$sidebarUser = currentUser() ?? [];
$userName = (string) ($sidebarUser['nama_lengkap'] ?? 'Pengguna');
$userInitial = function_exists('mb_substr') ? mb_strtoupper(mb_substr($userName, 0, 1)) : strtoupper(substr($userName, 0, 1));
?>
<aside class="app-sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="appSidebar" aria-label="Navigasi utama">
    <div class="sidebar-inner">
        <div class="sidebar-brand-row">
            <a class="brand-lockup" href="<?= e(appUrl('/dashboard.php')) ?>">
                <span class="brand-mark"><i class="bi bi-box-seam"></i></span>
                <span>Inventaris</span>
            </a>
            <button type="button" class="btn-close d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#appSidebar" aria-label="Tutup menu"></button>
        </div>
        <nav class="sidebar-nav">
            <a class="sidebar-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>" href="<?= e(appUrl('/dashboard.php')) ?>">
                <i class="bi bi-grid-1x2"></i><span>Dashboard</span>
            </a>
            <a class="sidebar-link <?= $currentPage === 'list' || $currentPage === 'detail' || $currentPage === 'edit' ? 'active' : '' ?>" href="<?= e(appUrl('/barang/index.php')) ?>">
                <i class="bi bi-box-seam"></i><span>Data Barang</span>
            </a>
            <a class="sidebar-link <?= $currentPage === 'add' ? 'active' : '' ?>" href="<?= e(appUrl('/barang/tambah.php')) ?>">
                <i class="bi bi-plus-square"></i><span>Tambah Barang</span>
            </a>
            <a class="sidebar-link <?= $currentPage === 'report' ? 'active' : '' ?>" href="<?= e(appUrl('/laporan.php')) ?>">
                <i class="bi bi-file-earmark-text"></i><span>Laporan</span>
            </a>
        </nav>
        <div class="sidebar-bottom">
            <div class="sidebar-profile">
                <span class="user-avatar"><?= e($userInitial) ?></span>
                <span class="profile-copy"><strong><?= e($userName) ?></strong><small><?= e(ucfirst((string) ($sidebarUser['role'] ?? 'staff'))) ?></small></span>
            </div>
            <form method="post" action="<?= e(appUrl('/auth/logout.php')) ?>" data-confirm="Keluar dari aplikasi sekarang?">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <button class="sidebar-link sidebar-logout" type="submit"><i class="bi bi-box-arrow-right"></i><span>Logout</span></button>
            </form>
        </div>
    </div>
</aside>

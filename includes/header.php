<?php
declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Dashboard';
$currentPage = $currentPage ?? '';
$flash = getFlash();
$breadcrumbLabel = [
    'dashboard' => 'Dashboard',
    'list' => 'Data Barang',
    'add' => 'Tambah Barang',
    'edit' => 'Edit Barang',
    'detail' => 'Detail Barang',
    'report' => 'Laporan',
][$currentPage] ?? $pageTitle;
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
<body class="app-body">
<div class="app-shell">
    <?php require __DIR__ . '/sidebar.php'; ?>
    <div class="app-workspace">
        <header class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn sidebar-toggle d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#appSidebar" aria-controls="appSidebar" aria-label="Buka menu">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <h1 class="topbar-title"><?= e($pageTitle) ?></h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb app-breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="<?= e(appUrl('/dashboard.php')) ?>">Beranda</a></li>
                            <?php if ($currentPage !== 'dashboard'): ?><li class="breadcrumb-item active" aria-current="page"><?= e($breadcrumbLabel) ?></li><?php endif; ?>
                        </ol>
                    </nav>
                </div>
            </div>
        </header>
        <main class="app-content">
            <?php if ($flash !== null): ?>
                <div class="toast-message toast-<?= e($flash['type']) ?>" role="status" data-toast>
                    <span class="toast-icon"><i class="bi <?= $flash['type'] === 'success' ? 'bi-check-circle-fill' : ($flash['type'] === 'warning' ? 'bi-exclamation-triangle-fill' : 'bi-x-circle-fill') ?>"></i></span>
                    <span><?= e($flash['message']) ?></span>
                    <button class="toast-close" type="button" data-dismiss-toast aria-label="Tutup notifikasi"><i class="bi bi-x-lg"></i></button>
                </div>
            <?php endif; ?>

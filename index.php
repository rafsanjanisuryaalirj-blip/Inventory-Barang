<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (currentUser() !== null) {
    redirect(appUrl('/dashboard.php'));
}

redirect(appUrl('/auth/login.php'));

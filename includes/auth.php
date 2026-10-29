<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function currentUser(): ?array
{
    $user = $_SESSION['user'] ?? null;
    return is_array($user) ? $user : null;
}

function currentUserId(): int
{
    $user = currentUser();
    return (int) ($user['id_user'] ?? 0);
}

function requireLogin(): void
{
    if (currentUser() === null) {
        redirect(appUrl('/auth/login.php'));
    }
}

function redirectAuthenticatedUser(): void
{
    if (currentUser() !== null) {
        redirect(appUrl('/dashboard.php'));
    }
}

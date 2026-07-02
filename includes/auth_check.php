<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

function currentUserRole(): string
{
    return $_SESSION['role'] ?? 'staff';
}

function isAdmin(): bool
{
    return currentUserRole() === 'admin';
}

function isStaff(): bool
{
    return currentUserRole() === 'staff';
}

function requireAdmin(): void
{
    if (!isAdmin()) {
        http_response_code(403);
        echo "Forbidden";
        exit();
    }
}
?>

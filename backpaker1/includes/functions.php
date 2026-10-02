<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function clean($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function flash($key, $value = null)
{
    if ($value === null) {
        $message = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $message;
    }

    $_SESSION['flash'][$key] = $value;
    return $value;
}

function redirect($path): void
{
    header('Location: ' . $path, true, 303);
    exit;
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

function is_admin(): bool
{
    return is_logged_in() && ($_SESSION['user_role'] ?? 'user') === 'admin';
}

function require_login(): void
{
    if (!is_logged_in()) {
        flash('error', 'Please log in to continue.');
        redirect('login.php');
    }
}

function require_admin(): void
{
    require_login();

    if (!is_admin()) {
        flash('error', 'Access denied. Admin privileges required.');
        redirect('login.php');
    }
}

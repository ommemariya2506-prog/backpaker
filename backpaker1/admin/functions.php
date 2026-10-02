<?php
session_start();

function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function clean($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function flash(string $key, $value = null) {
    if ($value === null) {
        // get
        if (!empty($_SESSION['flash_' . $key])) {
            $v = $_SESSION['flash_' . $key];
            unset($_SESSION['flash_' . $key]);
            return $v;
        }
        return null;
    }
    // set
    $_SESSION['flash_' . $key] = $value;
}
?>
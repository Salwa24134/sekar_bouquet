<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* cek login */
function auth() {
    if (!isset($_SESSION['user'])) {
        header("Location: login.php");
        exit();
    }
}

/* cek role admin */
function adminOnly() {
    auth();

    if ($_SESSION['user']['role'] !== 'admin') {
        header("Location: index.php");
        exit();
    }
}

/* ambil user login */
function user() {
    return $_SESSION['user'] ?? null;
}
?>
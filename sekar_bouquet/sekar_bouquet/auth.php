<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Cek apakah user sudah login
 */
function auth() {
    if (!isset($_SESSION['user'])) {
        header("Location: login.php");
        exit();
    }
}

/**
 * Cek apakah user yang login adalah admin
 */
function adminOnly() {
    auth(); // Pastikan login dulu

    // Menyesuaikan dengan struktur session dari halaman admin sebelumnya
    // di mana role dicek via $_SESSION['role'] atau $_SESSION['user']['role']
    if ((isset($_SESSION['role']) && $_SESSION['role'] !== 'admin') && 
        (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] !== 'admin')) {
        header("Location: index.php");
        exit();
    }
}

/**
 * Mengambil data user yang sedang login
 */
function user() {
    return $_SESSION['user'] ?? null;
}
?>
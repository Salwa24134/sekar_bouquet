<?php
// Memastikan session dimulai sebelum dihancurkan
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Bersihkan semua data di dalam array $_SESSION
$_SESSION = array();

// 2. Hapus cookie session di browser komputer user (opsional, sangat baik untuk keamanan)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Hancurkan session yang ada di server
session_destroy();

// 4. Alihkan halaman kembali ke login.php
header("Location: login.php");
exit();
?>
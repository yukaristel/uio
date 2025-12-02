<?php
/**
 * PROSES AUTENTIKASI (LOGIN & LOGOUT)
 * Step 10/64 (15.6%)
 */

session_start();
require_once 'database.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'login':
        login();
        break;
    case 'logout':
        logout();
        break;
    default:
        header('Location: ../index.php');
        exit;
}

/**
 * Fungsi Login
 */
function login() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../index.php');
        exit;
    }
    
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    // Validasi input
    if (empty($username) || empty($password)) {
        $_SESSION['error'] = 'Username dan password harus diisi!';
        header('Location: ../index.php?page=login');
        exit;
    }
    
    // Cari user berdasarkan username
    $sql = "SELECT * FROM users WHERE username = ? LIMIT 1";
    $user = fetchOne($sql, [$username]);
    
    if (!$user) {
        $_SESSION['error'] = 'Username tidak ditemukan!';
        header('Location: ../index.php?page=login');
        exit;
    }
    
    // Verifikasi password
    // Cek apakah menggunakan password hash atau plain text (untuk development)
    $password_valid = false;
    
    if (password_verify($password, $user['password'])) {
        // Password hash valid
        $password_valid = true;
    } elseif ($password === $user['password']) {
        // Plain text password (untuk development/demo)
        $password_valid = true;
    }
    
    if (!$password_valid) {
        $_SESSION['error'] = 'Password salah!';
        header('Location: ../index.php?page=login');
        exit;
    }
    
    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['login_time'] = time();
    
    // Log aktivitas login (optional)
    logActivity($user['id'], 'login', 'User login ke sistem');
    
    $_SESSION['success'] = 'Selamat datang, ' . $user['nama_lengkap'] . '!';
    header('Location: ../index.php?page=dashboard');
    exit;
}

/**
 * Fungsi Logout
 */
function logout() {
    // Log aktivitas logout (optional)
    if (isset($_SESSION['user_id'])) {
        logActivity($_SESSION['user_id'], 'logout', 'User logout dari sistem');
    }
    
    // Hapus semua session
    session_unset();
    session_destroy();
    
    // Redirect ke halaman login
    session_start();
    $_SESSION['success'] = 'Anda telah berhasil logout.';
    header('Location: ../index.php?page=login');
    exit;
}

/**
 * Fungsi untuk log aktivitas user (optional)
 */
function logActivity($user_id, $action, $description) {
    try {
        $sql = "INSERT INTO user_activity_log (user_id, action, description, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        
        $ip = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        
        execute($sql, [$user_id, $action, $description, $ip, $user_agent]);
    } catch (Exception $e) {
        // Ignore errors untuk activity log
        error_log("Activity Log Error: " . $e->getMessage());
    }
}
?>
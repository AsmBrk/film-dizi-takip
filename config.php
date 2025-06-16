<?php
// Hata gösterimi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// If this file is accessed directly, require JSON response
if (count(debug_backtrace()) === 0) {
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'message' => 'Direct access not allowed']));
}

// Veritabanı bağlantısı
$host = 'localhost';
$dbname = 'watchlist';
$username = 'root';
$password = '';

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    
    if (!$conn || $conn->connect_error) {
        error_log("Database connection failed: " . ($conn->connect_error ?? 'Unknown error'));
        throw new Exception("Veritabanı bağlantı hatası");
    }

    $conn->set_charset("utf8mb4");

} catch (Exception $e) {
    // Log the real error
    error_log("Database error in config.php: " . $e->getMessage());
    
    // If this is an AJAX request, return JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        die(json_encode(['success' => false, 'message' => $e->getMessage()]));
    }
    
    // Otherwise just throw the exception to be handled by the including script
    throw $e;
}

// Session başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Oturum kontrolü
function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
    return $_SESSION['user_id'];
}
?>

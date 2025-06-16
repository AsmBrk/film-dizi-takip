<?php
// Oturum başlat (eğer başlamamışsa)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

// Oturum verilerini temizle
session_unset();
session_destroy();

// Login sayfasına yönlendir
header('Location: login.php');
exit;
?>

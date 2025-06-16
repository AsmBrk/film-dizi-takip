<?php
session_start();
require_once 'config.php';

// Kullanıcı giriş kontrolü
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

if (isset($_GET['id']) && isset($_GET['list'])) {
    $watchlist_id = intval($_GET['id']);
    $list = $_GET['list']; // 'watched' veya 'to-watch'

    try {
        // İlgili listeden kaydı sil
        if ($list === 'watched') {
            $stmt = $conn->prepare("DELETE FROM watched_list WHERE id = ? AND user_id = ?");
        } else {
            $stmt = $conn->prepare("DELETE FROM to_watch_list WHERE id = ? AND user_id = ?");
        }

        $stmt->bind_param("ii", $watchlist_id, $user_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Kayıt silinirken bir hata oluştu");
        }

        $_SESSION['success'] = 'İçerik başarıyla silindi';

    } catch (Exception $e) {
        $_SESSION['error'] = 'Bir hata oluştu: ' . $e->getMessage();
    }

    $stmt->close();
    $conn->close();
    
    header('Location: ' . ($list === 'watched' ? 'watched.php' : 'to-watch.php'));
    exit();
}

header('Location: index.php');
exit();

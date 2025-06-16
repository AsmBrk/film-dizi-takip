<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

// Oturum kontrolü
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Lütfen giriş yapın']));
}

$user_id = $_SESSION['user_id'];

// POST verilerini al
$watchlist_id = intval($_POST['watchlist_id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$type = trim($_POST['type'] ?? '');
$rating = intval($_POST['rating'] ?? 0);
$comment = trim($_POST['comment'] ?? '');

// Zorunlu alan kontrolü
if (empty($watchlist_id) || empty($title) || empty($type) || $rating < 1 || $rating > 10) {
    die(json_encode(['success' => false, 'message' => 'Tüm zorunlu alanlar doldurulmalıdır.']));
}

try {
    // İçeriğe erişim doğrulama ve content_id çekme
    $stmt = $conn->prepare("SELECT content_id FROM watched_list WHERE id = ? AND user_id = ?");
    if (!$stmt) {
        throw new Exception($conn->error);
    }

    $stmt->bind_param("ii", $watchlist_id, $user_id);
    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    $result = $stmt->get_result();
    if ($result->num_rows !== 1) {
        throw new Exception("İçerik bulunamadı veya erişiminiz yok.");
    }

    $row = $result->fetch_assoc();
    $content_id = $row['content_id'];
    $stmt->close();

    // 1. content tablosunu güncelle
    $stmt = $conn->prepare("UPDATE content SET title = ?, type = ? WHERE id = ?");
    if (!$stmt) {
        throw new Exception($conn->error);
    }

    $stmt->bind_param("ssi", $title, $type, $content_id);
    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }
    $stmt->close();

    // 2. watched_list tablosunu güncelle
    $stmt = $conn->prepare("UPDATE watched_list SET rating = ?, comment = ? WHERE id = ? AND user_id = ?");
    if (!$stmt) {
        throw new Exception($conn->error);
    }

    $stmt->bind_param("isii", $rating, $comment, $watchlist_id, $user_id);
    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    echo json_encode(['success' => true, 'message' => 'İçerik başarıyla güncellendi']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
}
?>

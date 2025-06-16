<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

// Oturum kontrolü
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Lütfen giriş yapın']));
}

// POST verilerini al
$title = trim($_POST['title'] ?? '');
$type = trim($_POST['type'] ?? '');

// Zorunlu alanları kontrol et
if (empty($title)) {
    die(json_encode(['success' => false, 'message' => 'Başlık gereklidir']));
}
if (empty($type)) {
    die(json_encode(['success' => false, 'message' => 'Tür seçmelisiniz']));
}

try {    // İçeriği ekle
    $stmt = $conn->prepare("INSERT INTO content (title, type) VALUES (?, ?)");
    if (!$stmt) {
        throw new Exception($conn->error);
    }

    $stmt->bind_param("ss", $title, $type);
    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    $content_id = $stmt->insert_id;
    $stmt->close();    // İzleyeceklerim listesine ekle
    $stmt = $conn->prepare("INSERT INTO to_watch_list (user_id, content_id) VALUES (?, ?)");
    if (!$stmt) {
        throw new Exception($conn->error);
    }

    $stmt->bind_param("ii", $_SESSION['user_id'], $content_id);
    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    echo json_encode(['success' => true, 'message' => 'İçerik başarıyla eklendi']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?>
<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode([
        'success' => false,
        'message' => 'Lütfen önce giriş yapın.'
    ]));
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['content_id'])) {
    die(json_encode([
        'success' => false,
        'message' => 'Geçersiz istek.'
    ]));
}

$content_id = $data['content_id'];
$user_id = $_SESSION['user_id'];

$query = "DELETE FROM user_watchlist WHERE user_id = ? AND content_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('ii', $user_id, $content_id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'İçerik listenizden kaldırıldı.'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Bir hata oluştu: ' . $conn->error
    ]);
}

$conn->close();

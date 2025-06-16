<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Giriş yapılmamış']);
    exit;
}
$user_id = $_SESSION['user_id'];

require_once 'config.php';

// Session kontrolü
$user_id = check_user_session();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $type = isset($_POST['type']) ? trim($_POST['type']) : '';
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $review = isset($_POST['review']) ? trim($_POST['review']) : '';

    if (empty($title) || empty($type)) {
        echo json_encode([
            'success' => false,
            'message' => 'Başlık ve tür alanları zorunludur'
        ]);
        exit;
    }

    try {
        $conn->begin_transaction();

        // İçeriği content tablosuna ekle
        $release_year = date('Y');
$stmt = $conn->prepare("INSERT INTO content (title, type, release_year) VALUES (?, ?, ?)");
$stmt->bind_param("ssi", $title, $type, $release_year);

        $stmt->execute();
        
        // Yeni eklenen içeriğin ID'sini al
        $content_id = $conn->insert_id;

        if (!$content_id) {
            throw new Exception('İçerik eklenemedi');
        }

        // user_watchlist tablosuna ekle
        $stmt = $conn->prepare("INSERT INTO user_watchlist (user_id, content_id, status, rating, review) VALUES (?, ?, 'watched', ?, ?)");
$stmt->bind_param("iiss", $user_id, $content_id, $rating, $review);

        $stmt->execute();

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'İçerik başarıyla eklendi'
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        
        echo json_encode([
            'success' => false,
            'message' => 'Bir hata oluştu: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Geçersiz istek metodu'
    ]);
}
?>

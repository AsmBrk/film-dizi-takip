<?php
require_once 'config.php';

try {
    // Review sütununu user_watchlist tablosuna ekle
    $sql = "ALTER TABLE user_watchlist ADD COLUMN IF NOT EXISTS review TEXT AFTER rating";
    $conn->query($sql);
    echo "Review sütunu başarıyla eklendi veya zaten mevcut.";
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage();
}
?>

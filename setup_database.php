<?php
require_once 'config.php';

// Hata raporlamayı aktifleştir
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    // Veritabanını oluştur
    $sql = "CREATE DATABASE IF NOT EXISTS watchlist CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    if (!$conn->query($sql)) {
        throw new Exception("Veritabanı oluşturulamadı: " . $conn->error);
    }
    $conn->select_db("watchlist");
      // Mevcut tabloları sil
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    $conn->query("DROP TABLE IF EXISTS user_watchlist");
    $conn->query("DROP TABLE IF EXISTS to_watch_list");
    $conn->query("DROP TABLE IF EXISTS watched_list");
    $conn->query("DROP TABLE IF EXISTS content");
    $conn->query("DROP TABLE IF EXISTS users");
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    
    // Users tablosunu oluştur
    $sql = "CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        password VARCHAR(255) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if (!$conn->query($sql)) {
        throw new Exception("Users tablosu oluşturulamadı: " . $conn->error);
    }
      // Content tablosunu oluştur
    $sql = "CREATE TABLE content (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        type VARCHAR(50) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if (!$conn->query($sql)) {
        throw new Exception("Content tablosu oluşturulamadı: " . $conn->error);
    }
      // İzleyeceklerim tablosunu oluştur
    $sql = "CREATE TABLE to_watch_list (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        content_id INT NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if (!$conn->query($sql)) {
        throw new Exception("İzleyeceklerim tablosu oluşturulamadı: " . $conn->error);
    }

    // İzlediklerim tablosunu oluştur
    $sql = "CREATE TABLE watched_list (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        content_id INT NOT NULL,
        comment VARCHAR(255),
        rating INT CHECK (rating >= 0 AND rating <= 10),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if (!$conn->query($sql)) {
        throw new Exception("User_watchlist tablosu oluşturulamadı: " . $conn->error);
    }    // Test kullanıcısı ekle
    $username = "test";
    $password = password_hash("test123", PASSWORD_DEFAULT);

    // Önce mevcut test kullanıcısını sil
    $sql = "DELETE FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->close();

    // Yeni test kullanıcısını ekle
    $sql = "INSERT INTO users (username, password) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Test kullanıcısı hazırlık hatası: " . $conn->error);
    }

    $stmt->bind_param("ss", $username, $password);
    if (!$stmt->execute()) {
        throw new Exception("Test kullanıcısı eklenemedi: " . $stmt->error);
    }
    $stmt->close();

    echo "Test kullanıcısı oluşturuldu: kullanıcı adı: test, şifre: test123\n";
    
    echo "Veritabanı, tablolar ve test kullanıcısı başarıyla oluşturuldu!";
    
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage();
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
<?php
require_once '../config/database.php';

// Yeni admin bilgileri
$username = 'admin';
$password = 'admin123';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    // Önce tabloyu temizleyelim
    $db->query("TRUNCATE TABLE admins");
    
    // Yeni admin hesabını ekleyelim
    $stmt = $db->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
    $stmt->execute([$username, $hashed_password]);
    
    echo "Admin hesabı başarıyla oluşturuldu!<br>";
    echo "Kullanıcı adı: admin<br>";
    echo "Şifre: admin123<br>";
    echo "Bu dosyayı güvenlik için sunucudan silmeyi unutmayın!";
    
} catch(PDOException $e) {
    echo "Hata: " . $e->getMessage();
}
?> 
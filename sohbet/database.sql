USE osmanak1_sohbet;

-- Veritabanı karakter setini ayarla
ALTER DATABASE osmanak1_sohbet CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Tabloları güncelle (varsa silip yeniden oluştur)
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS admins;

-- Tabloları oluşturalım
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nickname VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    is_banned TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_nickname (nickname),
    UNIQUE KEY unique_ip (ip_address)
);

CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Yeni admin hesabı oluşturalım
INSERT INTO admins (username, password) VALUES ('admin', '$2y$10$YlVtxpRGqDu6tZvqBEY7OeVyaG9RBvHM3U1p.xS1YnlF3vOqm4N9y'); 

-- Tabloları güncelle
ALTER TABLE users CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE messages CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE admins CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Mesaj sütununu özellikle güncelle
ALTER TABLE messages MODIFY message TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE users MODIFY nickname VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; 
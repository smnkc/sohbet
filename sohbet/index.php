<?php
session_start();
require_once 'config/database.php';

$ip_address = $_SERVER['REMOTE_ADDR'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nickname']) && isset($_POST['message'])) {
    $nickname = trim($_POST['nickname']);
    $message = trim($_POST['message']);
    
    if (!empty($nickname) && !empty($message)) {
        try {
            $stmt = $db->prepare("SELECT id, is_banned FROM users WHERE nickname = ? OR ip_address = ?");
            $stmt->execute([$nickname, $ip_address]);
            $user = $stmt->fetch();

            if ($user && $user['is_banned']) {
                $error = "Bu hesap engellenmiştir.";
            } else if ($user) {
                $user_id = $user['id'];
            } else {
                $stmt = $db->prepare("INSERT INTO users (nickname, ip_address) VALUES (?, ?)");
                $stmt->execute([$nickname, $ip_address]);
                $user_id = $db->lastInsertId();
            }

            if (!isset($error)) {
                $stmt = $db->prepare("INSERT INTO messages (user_id, message) VALUES (?, ?)");
                $stmt->execute([$user_id, $message]);
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }

            // Kullanıcı mesaj gönderdiğinde last_seen'i güncelle
            if (!isset($error) && isset($user_id)) {
                try {
                    $stmt = $db->prepare("UPDATE users SET last_seen = CURRENT_TIMESTAMP WHERE id = ?");
                    $stmt->execute([$user_id]);
                } catch (Exception $e) {
                    error_log('Error updating last_seen: ' . $e->getMessage());
                }
            }
        } catch(PDOException $e) {
            $error = "Rumuz başka bir kullanıcı tarafından kullanılıyor.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sohbet Panosu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4e54c8;
            --secondary-color: #8f94fb;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
            min-height: 100vh;
        }

        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .container {
            max-width: 1200px;
        }

        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.9);
        }

        .card-header {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 20px 20px 0 0 !important;
            padding: 1rem;
        }

        .message-card {
            transition: all 0.3s ease;
            border: none;
            border-radius: 10px;
            margin-bottom: 1rem;
            background-color: rgba(255, 255, 255, 0.95);
        }

        .message-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .chat-container {
            max-height: 70vh;
            overflow-y: auto;
            padding: 1rem;
        }

        .chat-container::-webkit-scrollbar {
            width: 8px;
        }

        .chat-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .chat-container::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 10px;
        }

        .form-control {
            border-radius: 10px;
            border: 2px solid #eee;
            padding: 0.75rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(78, 84, 200, 0.25);
        }

        .btn-primary {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 10px;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(78, 84, 200, 0.3);
        }

        .admin-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: linear-gradient(45deg, #2b2d42, #8d99ae);
            border: none;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .admin-btn:hover {
            transform: scale(1.1) rotate(360deg);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        .admin-btn i {
            font-size: 1.5rem;
        }

        .message-meta {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
            font-size: 0.9rem;
        }

        .nickname-badge {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert {
            border-radius: 10px;
            border: none;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .chat-container {
                max-height: 60vh;
            }
        }
    </style>
</head>
<body>
    <div class="loading-animation"></div>
    
    <div class="container mt-4">
        <div class="row g-4">
            <div class="col-md-12">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-comments me-2"></i>Sohbet Akışı
                        </h5>
                    </div>
                    <div class="chat-container" id="chatContainer">
                        <?php
                        $stmt = $db->query("SELECT m.*, u.nickname 
                                          FROM messages m 
                                          JOIN users u ON m.user_id = u.id 
                                          ORDER BY m.created_at ASC");
                        while ($row = $stmt->fetch()): ?>
                            <div class="message-card" data-message-id="<?php echo $row['id']; ?>">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <span class="nickname-badge">
                                            <i class="fas fa-user"></i>
                                            <?php echo htmlspecialchars($row['nickname']); ?>
                                        </span>
                                        <div class="message-meta">
                                            <i class="fas fa-clock"></i>
                                            <?php echo date('d.m.Y H:i', strtotime($row['created_at'])); ?>
                                        </div>
                                    </div>
                                    <p class="card-text mb-0">
                                        <?php echo htmlspecialchars($row['message']); ?>
                                    </p>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Yeni mesaj kısmını en alta alalım -->
        <div class="row g-4">
            <div class="col-md-12">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-paper-plane me-2"></i>Yeni Mesaj
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="needs-validation" novalidate id="messageForm">
                            <div class="mb-2">
                                <label for="nickname" class="form-label">
                                    <i class="fas fa-user me-2"></i>Rumuzunuz
                                </label>
                                <input type="text" class="form-control form-control-sm" 
                                       id="nickname" name="nickname" required
                                       placeholder="Bir rumuz girin..."
                                       value="<?php echo isset($_POST['nickname']) ? htmlspecialchars($_POST['nickname']) : ''; ?>">
                            </div>
                            <div class="mb-2">
                                <label for="message" class="form-label">
                                    <i class="fas fa-comment me-2"></i>Mesajınız
                                </label>
                                <textarea class="form-control form-control-sm" 
                                          id="message" 
                                          name="message" 
                                          rows="2" 
                                          required
                                          placeholder="Mesajınızı yazın... (Enter tuşu ile gönder)"
                                          enterkeyhint="send"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="fas fa-paper-plane me-2"></i>Gönder
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <a href="admin/login.php" class="admin-btn">
        <i class="fas fa-lock"></i>
    </a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // En son mesaj ID'sini tanımla
        let lastMessageId = document.querySelector('.message-card') ? 
            parseInt(document.querySelector('.message-card').dataset.messageId) : 0;

        // Otomatik scroll
        const chatContainer = document.querySelector('.chat-container');
        chatContainer.scrollTop = chatContainer.scrollHeight;

        // Enter tuşu ile gönderme
        document.getElementById('message').addEventListener('keydown', function(e) {
            // Shift + Enter ile yeni satır
            if (e.key === 'Enter' && e.shiftKey) {
                return; // Normal davranışına devam et (yeni satır ekle)
            }
            
            // Sadece Enter ile gönder
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault(); // Enter'ın varsayılan davranışını engelle
                sendMessage();
            }
        });

        // Mobil klavye için "gönder" tuşu desteği
        document.getElementById('message').addEventListener('keyup', function(e) {
            // Mobil klavyede "done" veya "go" tuşuna basıldığında
            if (e.key === 'Enter' && !e.shiftKey && /mobile|android|iphone/i.test(navigator.userAgent)) {
                sendMessage();
            }
        });

        // Form gönderme işlemi
        document.getElementById('messageForm').addEventListener('submit', function(e) {
            e.preventDefault();
            sendMessage();
        });

        // AJAX ile mesaj gönderme fonksiyonu
        function sendMessage() {
            const form = document.getElementById('messageForm');
            const message = document.getElementById('message').value.trim();
            const nickname = document.getElementById('nickname').value.trim();
            
            if (message === '' || nickname === '') {
                return;
            }

            // Form verilerini hazırla
            const formData = new FormData(form);

            // AJAX isteği gönder
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.ok) {
                    // Mesaj alanını temizle
                    document.getElementById('message').value = '';
                    
                    // Rumuzu localStorage'a kaydet
                    localStorage.setItem('userNickname', nickname);
                    
                    // Mesaj alanına odaklan
                    document.getElementById('message').focus();
                    
                    // Hemen yeni mesajları kontrol et
                    checkNewMessages();
                }
            })
            .catch(error => console.error('Error:', error));
        }

        // Sayfa yüklendiğinde kaydedilmiş rumuzu kontrol et
        document.addEventListener('DOMContentLoaded', function() {
            const savedNickname = localStorage.getItem('userNickname');
            if (savedNickname) {
                document.getElementById('nickname').value = savedNickname;
            }
            // Mesaj alanına odaklan
            document.getElementById('message').focus();
        });

        // Yeni mesajları kontrol et ve ekle
        function checkNewMessages() {
            fetch('get_messages.php?last_id=' + lastMessageId)
                .then(response => response.json())
                .then(messages => {
                    if (messages.length > 0) {
                        const chatContainer = document.getElementById('chatContainer');
                        
                        messages.forEach(message => {
                            if (parseInt(message.id) > lastMessageId) {
                                const messageHtml = `
                                    <div class="message-card" data-message-id="${message.id}" style="opacity: 0">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <span class="nickname-badge">
                                                    <i class="fas fa-user"></i>
                                                    ${message.nickname}
                                                </span>
                                                <div class="message-meta">
                                                    <i class="fas fa-clock"></i>
                                                    ${new Date(message.created_at).toLocaleString('tr-TR')}
                                                </div>
                                            </div>
                                            <p class="card-text mb-0">${message.message}</p>
                                        </div>
                                    </div>
                                `;
                                
                                chatContainer.insertAdjacentHTML('beforeend', messageHtml);
                                
                                const newMessage = chatContainer.lastElementChild;
                                requestAnimationFrame(() => {
                                    newMessage.style.transition = 'opacity 0.5s ease';
                                    newMessage.style.opacity = '1';
                                });
                                
                                lastMessageId = parseInt(message.id);
                            }
                        });
                        
                        chatContainer.scrollTop = chatContainer.scrollHeight;
                    }
                });
        }

        // Her 3 saniyede bir yeni mesajları kontrol et
        setInterval(checkNewMessages, 3000);
    </script>
</body>
</html> 
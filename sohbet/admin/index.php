<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

if (isset($_POST['toggle_ban'])) {
    $user_id = $_POST['user_id'];
    $stmt = $db->prepare("UPDATE users SET is_banned = NOT is_banned WHERE id = ?");
    $stmt->execute([$user_id]);
}

if (isset($_POST['delete_message'])) {
    $message_id = $_POST['message_id'];
    $stmt = $db->prepare("DELETE FROM messages WHERE id = ?");
    $stmt->execute([$message_id]);
}

if (isset($_POST['send_admin_message'])) {
    $nickname = trim($_POST['admin_nickname']);
    $message = trim($_POST['admin_message']);
    
    if (!empty($nickname) && !empty($message)) {
        try {
            // Önce kullanıcıyı kontrol et veya oluştur
            $stmt = $db->prepare("SELECT id FROM users WHERE nickname = ?");
            $stmt->execute([$nickname]);
            $user = $stmt->fetch();
            
            if (!$user) {
                // Yeni kullanıcı oluştur (admin için özel IP)
                $stmt = $db->prepare("INSERT INTO users (nickname, ip_address, is_banned) VALUES (?, 'admin', 0)");
                $stmt->execute([$nickname]);
                $user_id = $db->lastInsertId();
            } else {
                $user_id = $user['id'];
                // Rumuz varsa güncelle
                $stmt = $db->prepare("UPDATE users SET nickname = ? WHERE id = ?");
                $stmt->execute([$nickname, $user_id]);
            }
            
            // Mesajı kaydet
            $stmt = $db->prepare("INSERT INTO messages (user_id, message) VALUES (?, ?)");
            $stmt->execute([$user_id, $message]);
            
            $success_message = "Mesaj başarıyla gönderildi!";
        } catch(PDOException $e) {
            $error_message = "Mesaj gönderilirken bir hata oluştu!";
        }
    }
}

// Rumuz düzenleme işlemi
if (isset($_POST['edit_nickname'])) {
    $user_id = $_POST['edit_user_id'];
    $new_nickname = trim($_POST['new_nickname']);
    
    if (!empty($new_nickname)) {
        try {
            $stmt = $db->prepare("UPDATE users SET nickname = ? WHERE id = ?");
            $stmt->execute([$new_nickname, $user_id]);
            $success_message = "Rumuz başarıyla güncellendi!";
        } catch(PDOException $e) {
            $error_message = "Rumuz güncellenirken bir hata oluştu!";
        }
    }
}

// Toplu mesaj silme işlemi
if (isset($_POST['delete_selected'])) {
    if (!empty($_POST['selected_messages'])) {
        $ids = implode(',', array_map('intval', $_POST['selected_messages']));
        $stmt = $db->prepare("DELETE FROM messages WHERE id IN ($ids)");
        $stmt->execute();
        $success_message = count($_POST['selected_messages']) . " mesaj başarıyla silindi!";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .nav-link.active {
            background-color: #0d6efd !important;
            color: white !important;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="fas fa-shield-alt me-2"></i>Admin Paneli</a>
            <div class="d-flex">
                <a href="settings.php" class="btn btn-outline-light me-2">
                    <i class="fas fa-cog me-2"></i>Ayarlar
                </a>
                <a href="logout.php" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt me-2"></i>Çıkış
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <ul class="nav nav-pills mb-4" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#users">
                            <i class="fas fa-users me-2"></i>Kullanıcılar
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#messages">
                            <i class="fas fa-comments me-2"></i>Mesajlar
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#send-message">
                            <i class="fas fa-paper-plane me-2"></i>Mesaj Gönder
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="users">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Rumuz</th>
                                                <th>IP Adresi</th>
                                                <th>Durum</th>
                                                <th>İşlem</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $stmt = $db->query("SELECT * FROM users ORDER BY created_at DESC");
                                            while ($row = $stmt->fetch()): ?>
                                                <tr>
                                                    <td><?php echo $row['id']; ?></td>
                                                    <td><?php echo htmlspecialchars($row['nickname']); ?></td>
                                                    <td><?php echo $row['ip_address']; ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $row['is_banned'] ? 'danger' : 'success'; ?>">
                                                            <?php echo $row['is_banned'] ? 'Engelli' : 'Aktif'; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                                            <button type="submit" name="toggle_ban" class="btn btn-<?php echo $row['is_banned'] ? 'success' : 'warning'; ?> btn-sm">
                                                                <i class="fas fa-<?php echo $row['is_banned'] ? 'unlock' : 'ban'; ?> me-1"></i>
                                                                <?php echo $row['is_banned'] ? 'Engeli Kaldır' : 'Engelle'; ?>
                                                            </button>
                                                        </form>
                                                        
                                                        <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#editNickname<?php echo $row['id']; ?>">
                                                            <i class="fas fa-edit me-1"></i>Rumuzu Düzenle
                                                        </button>
                                                        
                                                        <div class="modal fade" id="editNickname<?php echo $row['id']; ?>" tabindex="-1">
                                                            <div class="modal-dialog">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">Rumuz Düzenle</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                    </div>
                                                                    <form method="POST">
                                                                        <div class="modal-body">
                                                                            <input type="hidden" name="edit_user_id" value="<?php echo $row['id']; ?>">
                                                                            <div class="mb-3">
                                                                                <label class="form-label">Yeni Rumuz</label>
                                                                                <input type="text" name="new_nickname" class="form-control" value="<?php echo htmlspecialchars($row['nickname']); ?>" required>
                                                                            </div>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                                                                            <button type="submit" name="edit_nickname" class="btn btn-primary">Kaydet</button>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="messages">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <?php if (isset($success_message)): ?>
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <form method="POST" id="messagesForm">
                                    <div class="d-flex justify-content-between mb-3">
                                        <div>
                                            <button type="button" class="btn btn-secondary btn-sm me-2" onclick="toggleAll()">
                                                <i class="fas fa-check-double me-2"></i>Tümünü Seç/Kaldır
                                            </button>
                                            <button type="submit" name="delete_selected" class="btn btn-danger btn-sm" onclick="return confirmDelete()">
                                                <i class="fas fa-trash me-2"></i>Seçilenleri Sil
                                            </button>
                                        </div>
                                        <span class="badge bg-primary" id="selectedCount">0 mesaj seçili</span>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th width="40">
                                                        <input type="checkbox" class="form-check-input" id="selectAll">
                                                    </th>
                                                    <th>ID</th>
                                                    <th>Kullanıcı</th>
                                                    <th>Mesaj</th>
                                                    <th>Tarih</th>
                                                    <th>İşlem</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $stmt = $db->query("SELECT m.*, u.nickname 
                                                  FROM messages m 
                                                  JOIN users u ON m.user_id = u.id 
                                                  ORDER BY m.created_at DESC");
                                                while ($row = $stmt->fetch()): ?>
                                                    <tr>
                                                        <td>
                                                            <input type="checkbox" class="form-check-input message-checkbox" 
                                                                   name="selected_messages[]" value="<?php echo $row['id']; ?>">
                                                        </td>
                                                        <td><?php echo $row['id']; ?></td>
                                                        <td><?php echo htmlspecialchars($row['nickname']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['message']); ?></td>
                                                        <td><?php echo date('d.m.Y H:i', strtotime($row['created_at'])); ?></td>
                                                        <td>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="message_id" value="<?php echo $row['id']; ?>">
                                                                <button type="submit" name="delete_message" class="btn btn-danger btn-sm" 
                                                                        onclick="return confirm('Bu mesajı silmek istediğinizden emin misiniz?')">
                                                                    <i class="fas fa-trash me-1"></i>Sil
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="send-message">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <?php if (isset($success_message)): ?>
                                    <div class="alert alert-success mb-3">
                                        <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (isset($error_message)): ?>
                                    <div class="alert alert-danger mb-3">
                                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" class="needs-validation" novalidate>
                                    <div class="mb-3">
                                        <label for="admin_nickname" class="form-label">
                                            <i class="fas fa-user me-2"></i>Rumuz
                                        </label>
                                        <input type="text" class="form-control" id="admin_nickname" name="admin_nickname" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="admin_message" class="form-label">
                                            <i class="fas fa-comment me-2"></i>Mesaj
                                        </label>
                                        <textarea class="form-control" id="admin_message" name="admin_message" rows="3" required></textarea>
                                    </div>
                                    <button type="submit" name="send_admin_message" class="btn btn-primary">
                                        <i class="fas fa-paper-plane me-2"></i>Gönder
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Seçili mesaj sayısını güncelle
    function updateSelectedCount() {
        const count = document.querySelectorAll('.message-checkbox:checked').length;
        document.getElementById('selectedCount').textContent = count + ' mesaj seçili';
    }

    // Tüm mesajları seç/kaldır
    function toggleAll() {
        const checkboxes = document.querySelectorAll('.message-checkbox');
        const selectAllCheckbox = document.getElementById('selectAll');
        selectAllCheckbox.checked = !selectAllCheckbox.checked;
        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAllCheckbox.checked;
        });
        updateSelectedCount();
    }

    // Toplu silme onayı
    function confirmDelete() {
        const count = document.querySelectorAll('.message-checkbox:checked').length;
        if (count === 0) {
            alert('Lütfen silinecek mesajları seçin!');
            return false;
        }
        return confirm(count + ' mesajı silmek istediğinizden emin misiniz?');
    }

    // Event listeners
    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.message-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateSelectedCount();
    });

    document.querySelectorAll('.message-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedCount);
    });

    // Sayfa yüklendiğinde seçili sayısını sıfırla
    document.addEventListener('DOMContentLoaded', updateSelectedCount);
    </script>
</body>
</html> 
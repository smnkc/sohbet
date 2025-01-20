<?php
require_once 'config/database.php';

header('Content-Type: application/json');

// Son mesaj ID'sini al
$last_id = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;

// Yeni mesajları getir
$stmt = $db->prepare("SELECT m.*, u.nickname 
                      FROM messages m 
                      JOIN users u ON m.user_id = u.id 
                      WHERE m.id > ?
                      ORDER BY m.created_at ASC");
$stmt->execute([$last_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($messages);
?> 
<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════════
 * REAL-TIME CHAT HANDLER (PRODUCTION GRADE)
 * Handles: Messages, Reactions, Presence, Typing, Calls, Uploads
 * ═══════════════════════════════════════════════════════════════════════════════
 */

require_once '../../config.php';
require_once '../../functions.php';

header('Content-Type: application/json; charset=utf-8');

// Error Logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '../../chat_errors.log');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';

$action = $_REQUEST['action'] ?? null;
$roomId = (int)($_REQUEST['room_id'] ?? 0);

try {
    $skipRoomIdCheck = ['get_rooms', 'edit_message', 'delete_message', 'toggle_reaction'];
    if (!$roomId && !in_array($action, $skipRoomIdCheck)) {
        throw new Exception('Room ID required');
    }
    switch ($action) {
        // ─────────────────────────────────────────────────────────────────────
        // 1. MESSAGES: FETCH
        // ─────────────────────────────────────────────────────────────────────
        case 'load_messages':
            $lastId = (int)($_GET['last_id'] ?? 0);
            $limit = (int)($_GET['limit'] ?? 50);
            
            // Fetch messages with parent details
            // If lastId is 0, we want the LATEST $limit messages. If > 0, we want the NEXT $limit messages.
            if ($lastId == 0) {
                $stmt = $pdo->prepare("
                    SELECT m.*, 
                           COALESCE(u.username, 'User') as username,
                           m.user_id as user_id,
                           p.message_text as parent_text,
                           COALESCE(pu.username, 'User') as parent_username
                    FROM forum_messages m
                    LEFT JOIN users u ON m.user_id = u.id
                    LEFT JOIN forum_messages p ON m.parent_id = p.id
                    LEFT JOIN users pu ON p.user_id = pu.id
                    WHERE m.room_id = ?
                    ORDER BY m.id DESC
                    LIMIT $limit
                ");
                $stmt->execute([$roomId]);
                $messages = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
            } else {
                $stmt = $pdo->prepare("
                    SELECT m.*, 
                           COALESCE(u.username, 'User') as username,
                           m.user_id as user_id,
                           p.message_text as parent_text,
                           COALESCE(pu.username, 'User') as parent_username
                    FROM forum_messages m
                    LEFT JOIN users u ON m.user_id = u.id
                    LEFT JOIN forum_messages p ON m.parent_id = p.id
                    LEFT JOIN users pu ON p.user_id = pu.id
                    WHERE m.room_id = ? AND m.id > ?
                    ORDER BY m.id ASC
                    LIMIT $limit
                ");
                $stmt->execute([$roomId, $lastId]);
                $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            // Pre-fetch reactions for these messages
            $msgIds = array_column($messages, 'id');
            $reactionsByMsg = [];
            if (!empty($msgIds)) {
                $inQuery = implode(',', array_fill(0, count($msgIds), '?'));
                try {
                    $rStmt = $pdo->prepare("SELECT message_id, user_id, emoji FROM forum_reactions WHERE message_id IN ($inQuery)");
                    $rStmt->execute($msgIds);
                    $allReactions = $rStmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($allReactions as $r) {
                        $decoded = base64_decode($r['emoji'], true);
                        if ($decoded !== false && base64_encode($decoded) === $r['emoji']) {
                            $r['emoji'] = $decoded;
                        }
                        $reactionsByMsg[$r['message_id']][] = $r;
                    }
                } catch (Exception $e) { /* Ignore if table doesn't exist yet */ }
            }

            foreach ($messages as &$m) {
                $m['reactions'] = $reactionsByMsg[$m['id']] ?? [];
                $m['time_formatted'] = isset($m['created_at']) ? date('H:i', strtotime($m['created_at'])) : date('H:i');
            }

            echo json_encode(['success' => true, 'messages' => $messages]);
            break;

        // ─────────────────────────────────────────────────────────────────────
        // 2. MESSAGES: SEND / UPLOAD
        // ─────────────────────────────────────────────────────────────────────
        case 'send_message':
            $content = trim($_POST['content'] ?? '');
            $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
            $msgType = $_POST['message_type'] ?? 'text';
            
            $filePath = null;
            $fileName = null;
            $fileSize = 0;

            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['file'];
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $newName = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $uploadDir = '../../uploads/forum/' . $roomId . '/';
                
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                
                if (move_uploaded_file($file['tmp_name'], $uploadDir . $newName)) {
                    $filePath = 'uploads/forum/' . $roomId . '/' . $newName;
                    $fileName = $file['name'];
                    $fileSize = $file['size'];
                    
                    if (strpos($file['type'], 'image/') === 0) $msgType = 'image';
                    elseif (strpos($file['type'], 'audio/') === 0) $msgType = 'audio';
                    else $msgType = 'file';
                }
            }

            if (empty($content) && !$filePath) {
                echo json_encode(['success' => false, 'error' => 'Empty message']);
                exit;
            }

            try {
                $stmt = $pdo->prepare("
                    INSERT INTO forum_messages (room_id, user_id, parent_id, message_text, message_type, file_path, file_name, file_size)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $ok = $stmt->execute([$roomId, $userId, $parentId, $content, $msgType, $filePath, $fileName, $fileSize]);
                
                if (!$ok) {
                    $errorInfo = $stmt->errorInfo();
                    throw new Exception("SQL Error: " . ($errorInfo[2] ?? 'Unknown'));
                }
                
                echo json_encode(['success' => true, 'message_id' => $pdo->lastInsertId()]);
            } catch (PDOException $ex) {
                throw new Exception("PDO Exception: " . $ex->getMessage());
            }
            break;

        // ─────────────────────────────────────────────────────────────────────
        // 2.5. EDIT & DELETE MESSAGES
        // ─────────────────────────────────────────────────────────────────────
        case 'edit_message':
            $msgId = (int)($_POST['message_id'] ?? 0);
            $content = trim($_POST['content'] ?? '');
            if (!$msgId || empty($content)) throw new Exception('Invalid data');

            $stmt = $pdo->prepare("UPDATE forum_messages SET message_text = ? WHERE id = ? AND user_id = ? AND message_type IN ('text', 'audio', 'file', 'image')");
            $stmt->execute([$content, $msgId, $userId]);
            echo json_encode(['success' => true]);
            break;

        case 'delete_message':
            $msgId = (int)($_POST['message_id'] ?? 0);
            if (!$msgId) throw new Exception('Invalid message ID');

            $stmt = $pdo->prepare("SELECT file_path FROM forum_messages WHERE id = ? AND user_id = ?");
            $stmt->execute([$msgId, $userId]);
            $msg = $stmt->fetch();
            if ($msg && $msg['file_path'] && file_exists('../../' . $msg['file_path'])) {
                @unlink('../../' . $msg['file_path']);
            }

            $stmt = $pdo->prepare("DELETE FROM forum_messages WHERE id = ? AND user_id = ?");
            $stmt->execute([$msgId, $userId]);
            
            // Also delete reactions
            try { $pdo->prepare("DELETE FROM forum_reactions WHERE message_id = ?")->execute([$msgId]); } catch (Exception $e) {}
            
            echo json_encode(['success' => true]);
            break;

        // ─────────────────────────────────────────────────────────────────────
        // 3. REACTIONS
        // ─────────────────────────────────────────────────────────────────────
        case 'toggle_reaction':
            $msgId = (int)($_POST['message_id'] ?? 0);
            $rawEmoji = $_POST['emoji'] ?? '';
            if (!$msgId || !$rawEmoji) throw new Exception('Missing data');
            $emoji = base64_encode($rawEmoji);

            try {
                // Check if already reacted
                $check = $pdo->prepare("SELECT id FROM forum_reactions WHERE message_id = ? AND user_id = ? AND emoji = ?");
                $check->execute([$msgId, $userId, $emoji]);
                if ($check->fetch()) {
                    // Remove
                    $pdo->prepare("DELETE FROM forum_reactions WHERE message_id = ? AND user_id = ? AND emoji = ?")->execute([$msgId, $userId, $emoji]);
                } else {
                    // Add
                    $pdo->prepare("INSERT INTO forum_reactions (message_id, user_id, emoji) VALUES (?, ?, ?)")->execute([$msgId, $userId, $emoji]);
                }
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                // Fallback table creation
                $pdo->exec("CREATE TABLE IF NOT EXISTS forum_reactions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    message_id INT NOT NULL,
                    user_id INT NOT NULL,
                    emoji VARCHAR(128) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
                try {
                    $pdo->prepare("INSERT INTO forum_reactions (message_id, user_id, emoji) VALUES (?, ?, ?)")->execute([$msgId, $userId, $emoji]);
                } catch (Exception $e2) {}
                echo json_encode(['success' => true]);
            }
            break;

        // ─────────────────────────────────────────────────────────────────────
        // 4. PRESENCE & TYPING
        // ─────────────────────────────────────────────────────────────────────
        case 'update_presence':
            $isTyping = (int)($_POST['is_typing'] ?? 0);
            
            // Update presence
            $stmt = $pdo->prepare("INSERT INTO user_presence (user_id, status) VALUES (?, 'online') ON DUPLICATE KEY UPDATE last_seen = CURRENT_TIMESTAMP, status = 'online'");
            $stmt->execute([$userId]);

            // Update typing status
            if ($isTyping) {
                $stmt = $pdo->prepare("INSERT INTO typing_status (room_id, user_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE last_typed = CURRENT_TIMESTAMP");
                $stmt->execute([$roomId, $userId]);
            } else {
                $stmt = $pdo->prepare("DELETE FROM typing_status WHERE room_id = ? AND user_id = ?");
                $stmt->execute([$roomId, $userId]);
            }

            // Fetch online users and typing users
            $stmt = $pdo->prepare("
                SELECT u.id, u.username, p.status, 
                       (CASE WHEN ts.last_typed > DATE_SUB(NOW(), INTERVAL 5 SECOND) THEN 1 ELSE 0 END) as is_typing
                FROM user_presence p
                JOIN users u ON p.user_id = u.id
                LEFT JOIN typing_status ts ON (u.id = ts.user_id AND ts.room_id = ?)
                WHERE p.last_seen > DATE_SUB(NOW(), INTERVAL 30 SECOND)
            ");
            $stmt->execute([$roomId]);
            $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'members' => $members]);
            break;

        // ─────────────────────────────────────────────────────────────────────
        // 5. CALLS (STUBS)
        // ─────────────────────────────────────────────────────────────────────
        case 'start_call':
            $type = $_POST['type'] ?? 'voice';
            $stmt = $pdo->prepare("INSERT INTO call_sessions (room_id, caller_id, call_type) VALUES (?, ?, ?)");
            $stmt->execute([$roomId, $userId, $type]);
            echo json_encode(['success' => true, 'call_id' => $pdo->lastInsertId()]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════════
 * FORUM AJAX API HANDLER
 * Handles all real-time chat operations
 * ═══════════════════════════════════════════════════════════════════════════════
 */

require_once '../config.php';

// ═══════════════════════════════════════════════════════════════════════════════
// SECURITY & HEADERS
// ═══════════════════════════════════════════════════════════════════════════════

// session_start() is already called in config.php

header('Content-Type: application/json; charset=utf-8');

// Verify user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';

// ═══════════════════════════════════════════════════════════════════════════════
// HELPER FUNCTIONS
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Validate file upload
 */
function validateFile($file) {
    $maxSize = 10 * 1024 * 1024; // 10 MB
    
    if ($file['size'] > $maxSize) {
        return ['valid' => false, 'error' => 'File too large'];
    }
    
    $allowed = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'audio/mpeg', 'audio/wav', 'audio/ogg',
        'video/mp4', 'video/webm',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/zip',
        'application/x-rar-compressed'
    ];
    
    if (!in_array($file['type'], $allowed)) {
        return ['valid' => false, 'error' => 'File type not allowed'];
    }
    
    return ['valid' => true];
}

/**
 * Generate secure filename
 */
function secureFilename($original) {
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    return time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
}

/**
 * Check spam prevention
 */
function checkSpam($pdo, $userId, $roomId) {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count FROM forum_messages 
            WHERE user_id = ? AND discussion_id = ? 
            AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
        ");
        $stmt->execute([$userId, $roomId]);
        $count = $stmt->fetch()['count'] ?? 0;
        
        return $count < 15; // Max 15 messages per minute
    } catch (Exception $e) {
        return true;
    }
}

// Ensure forum_reactions table exists
try {
    $pdo->query("SELECT 1 FROM forum_reactions LIMIT 1");
} catch (Exception $e) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS forum_reactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            message_id INT NOT NULL,
            user_id INT NOT NULL,
            emoji VARCHAR(10) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_reaction (message_id, user_id, emoji),
            FOREIGN KEY (message_id) REFERENCES forum_messages(id) ON DELETE CASCADE
        )
    ");
}

$action = $_GET['action'] ?? $_POST['action'] ?? null;
$roomId = intval($_GET['room_id'] ?? $_POST['room_id'] ?? 0);

try {
    // ─────────────────────────────────────────────────────────────────────────
    // GET MESSAGES
    // ─────────────────────────────────────────────────────────────────────────
    
    if ($action === 'get_messages') {
        $lastId = intval($_GET['last_id'] ?? 0);
        $limit = intval($_GET['limit'] ?? 50);
        
        $query = "
            SELECT 
                id, discussion_id, user_id, username, message_text, 
                is_edited, created_at, file_path, file_size, 
                is_deleted,
                (SELECT CONCAT('[', GROUP_CONCAT(CONCAT('\"', emoji, ':', user_id, '\"')), ']') 
                 FROM forum_reactions 
                 WHERE message_id = fm.id) as reactions
            FROM forum_messages fm
            WHERE discussion_id = ?
        ";
        
        $params = [$roomId];
        
        if ($lastId > 0) {
            $query .= " AND fm.id > ?";
            $params[] = $lastId;
        }
        
        $query .= " ORDER BY fm.created_at DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Reverse to show chronologically
        $messages = array_reverse($messages);
        
        echo json_encode([
            'success' => true,
            'messages' => $messages
        ]);
    }
    
    // ─────────────────────────────────────────────────────────────────────────
    // SEND MESSAGE
    // ─────────────────────────────────────────────────────────────────────────
    
    elseif ($action === 'send_message') {
        $content = trim($_POST['content'] ?? '');
        
        if (empty($content) && !isset($_FILES['file'])) {
            throw new Exception('Message content required');
        }
        
        if (!checkSpam($pdo, $userId, $roomId)) {
            throw new Exception('Too many messages. Please slow down.');
        }
        
        $filePath = null;
        $fileType = null;
        $fileSize = 0;
        
        // Handle file upload
        if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
            $validation = validateFile($_FILES['file']);
            if (!$validation['valid']) {
                throw new Exception($validation['error']);
            }
            
            $uploadDir = '../uploads/forum/' . $roomId . '/';
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    throw new Exception('Cannot create upload directory');
                }
            }
            
            $newFilename = secureFilename($_FILES['file']['name']);
            $filePath = $uploadDir . $newFilename;
            
            if (!move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
                throw new Exception('File upload failed');
            }
            
            $fileType = $_FILES['file']['type'];
            $fileSize = $_FILES['file']['size'];
            
            // For images, use the path directly; for files, keep the path
            $filePath = 'uploads/forum/' . $roomId . '/' . $newFilename;
        }
        
        // Insert message
        $stmt = $pdo->prepare("
            INSERT INTO forum_messages 
            (discussion_id, user_id, username, message_text, file_path, file_type, file_size)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$roomId, $userId, $username, $content, $filePath, $fileType, $fileSize]);
        
        $messageId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message_id' => $messageId
        ]);
    }
    
    // ─────────────────────────────────────────────────────────────────────────
    // EDIT MESSAGE
    // ─────────────────────────────────────────────────────────────────────────
    
    elseif ($action === 'edit_message') {
        $msgId = intval($_POST['message_id'] ?? 0);
        $newContent = trim($_POST['content'] ?? '');
        
        if (empty($newContent)) {
            throw new Exception('Content cannot be empty');
        }
        
        // Verify ownership
        $stmt = $pdo->prepare("SELECT user_id FROM forum_messages WHERE id = ?");
        $stmt->execute([$msgId]);
        $msg = $stmt->fetch();
        
        if (!$msg || $msg['user_id'] != $userId) {
            throw new Exception('Unauthorized');
        }
        
        // Update message
        $stmt = $pdo->prepare("
            UPDATE forum_messages 
            SET message_text = ?, is_edited = 1, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$newContent, $msgId]);
        
        echo json_encode(['success' => true]);
    }
    
    // ─────────────────────────────────────────────────────────────────────────
    // DELETE MESSAGE
    // ─────────────────────────────────────────────────────────────────────────
    
    elseif ($action === 'delete_message') {
        $msgId = intval($_POST['message_id'] ?? 0);
        
        // Verify ownership
        $stmt = $pdo->prepare("SELECT user_id FROM forum_messages WHERE id = ?");
        $stmt->execute([$msgId]);
        $msg = $stmt->fetch();
        
        if (!$msg || $msg['user_id'] != $userId) {
            throw new Exception('Unauthorized');
        }
        
        // Soft delete
        $stmt = $pdo->prepare("
            UPDATE forum_messages 
            SET is_deleted = 1 
            WHERE id = ?
        ");
        $stmt->execute([$msgId]);
        
        echo json_encode(['success' => true]);
    }
    
    // ─────────────────────────────────────────────────────────────────────────
    // ADD REACTION
    // ─────────────────────────────────────────────────────────────────────────
    
    elseif ($action === 'add_reaction') {
        $msgId = intval($_POST['message_id'] ?? 0);
        $emoji = $_POST['emoji'] ?? '👍';
        
        // Check if already exists (toggle)
        $stmt = $pdo->prepare("
            SELECT id FROM forum_reactions 
            WHERE message_id = ? AND user_id = ? AND emoji = ?
        ");
        $stmt->execute([$msgId, $userId, $emoji]);
        
        if ($stmt->fetch()) {
            // Remove reaction
            $stmt = $pdo->prepare("
                DELETE FROM forum_reactions 
                WHERE message_id = ? AND user_id = ? AND emoji = ?
            ");
            $stmt->execute([$msgId, $userId, $emoji]);
        } else {
            // Add reaction
            $stmt = $pdo->prepare("
                INSERT INTO forum_reactions (message_id, user_id, emoji) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$msgId, $userId, $emoji]);
        }
        
        echo json_encode(['success' => true]);
    }
    
    // ─────────────────────────────────────────────────────────────────────────
    // GET ONLINE USERS
    // ─────────────────────────────────────────────────────────────────────────
    
    elseif ($action === 'get_online') {
        $stmt = $pdo->prepare("
            SELECT DISTINCT user_id, username FROM forum_messages 
            WHERE discussion_id = ? 
            AND created_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)
            GROUP BY user_id
            ORDER BY MAX(created_at) DESC
            LIMIT 20
        ");
        $stmt->execute([$roomId]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'count' => count($users),
            'users' => $users
        ]);
    }
    
    // ─────────────────────────────────────────────────────────────────────────
    // TYPING STATUS
    // ─────────────────────────────────────────────────────────────────────────
    
    elseif ($action === 'typing') {
        $isTyping = $_POST['is_typing'] === '1' ? 1 : 0;
        
        // Update in session or cache (simple approach)
        // In production, use Redis or database
        $_SESSION['typing'][$roomId] = $isTyping;
        
        echo json_encode(['success' => true]);
    }
    
    // ─────────────────────────────────────────────────────────────────────────
    // SET OFFLINE
    // ─────────────────────────────────────────────────────────────────────────
    
    elseif ($action === 'set_offline') {
        // Update last seen
        $_SESSION['last_seen'] = time();
        
        echo json_encode(['success' => true]);
    }
    
    // ─────────────────────────────────────────────────────────────────────────
    // GET FORUM REACTIONS TABLE
    // ─────────────────────────────────────────────────────────────────────────
    
    // (forum_reactions check moved up)
    
    // ─────────────────────────────────────────────────────────────────────────
    // GET TEST QUESTIONS
    // ─────────────────────────────────────────────────────────────────────────
    elseif ($action === 'get_test_questions') {
        $niveau = $_GET['niveau'] ?? '';
        if (!in_array($niveau, ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'])) {
            throw new Exception('Niveau invalide');
        }

        // Get test_id
        $stmt = $pdo->prepare("SELECT id, titre, description FROM niveau_tests WHERE niveau = ?");
        $stmt->execute([$niveau]);
        $test = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$test) {
            throw new Exception('Test non trouvé pour ce niveau');
        }

        // Get questions
        $stmt = $pdo->prepare("SELECT id, question, option_a, option_b, option_c, option_d, bonne_reponse FROM questions_test WHERE test_id = ? ORDER BY ordre ASC");
        $stmt->execute([$test['id']]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'test' => $test,
            'questions' => $questions
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SUBMIT TEST
    // ─────────────────────────────────────────────────────────────────────────
    elseif ($action === 'submit_test') {
        $testId = intval($_POST['test_id'] ?? 0);
        $answers_raw = $_POST['answers'] ?? '[]';
        $answers = json_decode($answers_raw, true);

        if (!$testId || empty($answers)) {
            throw new Exception('Données de test invalides');
        }

        // The answers format from JS is array of {id, ans}
        // Let's convert it to [id => ans] for easier processing
        $formattedAnswers = [];
        foreach ($answers as $a) {
            if (isset($a['id']) && isset($a['ans'])) {
                $formattedAnswers[$a['id']] = $a['ans'];
            }
        }
        $answers = $formattedAnswers;

        // Get correct answers
        $stmt = $pdo->prepare("SELECT id, bonne_reponse FROM questions_test WHERE test_id = ?");
        $stmt->execute([$testId]);
        $correctAnswers = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $totalQuestions = count($correctAnswers);
        $scoreCount = 0;

        foreach ($answers as $qId => $ans) {
            if (isset($correctAnswers[$qId]) && $correctAnswers[$qId] === $ans) {
                $scoreCount++;
            }
        }

        $pourcentage = ($totalQuestions > 0) ? ($scoreCount / $totalQuestions) * 100 : 0;
        $reussi = ($pourcentage >= 70) ? 1 : 0;

        // Save result
        $stmt = $pdo->prepare("INSERT INTO resultats_tests (user_id, test_id, score, total, pourcentage, reussi) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $testId, $scoreCount, $totalQuestions, $pourcentage, $reussi]);

        // If passed, unlock level
        if ($reussi) {
            $stmt = $pdo->prepare("SELECT niveau FROM niveau_tests WHERE id = ?");
            $stmt->execute([$testId]);
            $currentNiveau = $stmt->fetchColumn();

            if ($currentNiveau) {
                // Unlock current level
                $stmt = $pdo->prepare("INSERT IGNORE INTO niveaux_debloques (user_id, niveau) VALUES (?, ?)");
                $stmt->execute([$userId, $currentNiveau]);

                // Also unlock the NEXT level
                $niveaux = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];
                $currentIndex = array_search($currentNiveau, $niveaux);
                if ($currentIndex !== false && $currentIndex < count($niveaux) - 1) {
                    $nextNiveau = $niveaux[$currentIndex + 1];
                    $stmt = $pdo->prepare("INSERT IGNORE INTO niveaux_debloques (user_id, niveau) VALUES (?, ?)");
                    $stmt->execute([$userId, $nextNiveau]);
                    
                    // Update user's niveau_actuel
                    $stmt = $pdo->prepare("UPDATE users SET niveau_actuel = ? WHERE id = ?");
                    $stmt->execute([$nextNiveau, $userId]);
                    $_SESSION['niveau_actuel'] = $nextNiveau;
                }
            }
        }

        if (ob_get_length()) ob_clean();
        echo json_encode([
            'success' => true,
            'score' => $scoreCount,
            'total' => $totalQuestions,
            'pourcentage' => $pourcentage,
            'reussi' => (bool)$reussi
        ]);
    }

    else {
        throw new Exception('Invalid action: ' . $action);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
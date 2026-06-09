<?php
// api/evaluate.php
require_once '../db.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

$action = $data['action'] ?? '';

// Handle final completion
if ($action === 'complete') {
    $scenario_id = (int)($data['scenario_id'] ?? 0);
    $score = (int)($data['score'] ?? 0);
    $user_id = $_SESSION['user_id'];
    
    $last_step = (int)($data['last_step'] ?? 0);
    
    try {
        // Calculate XP (score = % correct, max 50 XP per scenario)
        $xp = round(($score / 100) * 50);
        
        // Save to scenario_user_progress
        $stmt = $pdo->prepare("INSERT INTO scenario_user_progress (user_id, scenario_id, completed, score, xp_earned, last_step, completed_at) 
                               VALUES (?, ?, 1, ?, ?, ?, NOW()) 
                               ON DUPLICATE KEY UPDATE score = GREATEST(score, ?), xp_earned = GREATEST(xp_earned, ?), last_step = GREATEST(last_step, ?), completed_at = NOW()");
        $stmt->execute([$user_id, $scenario_id, $score, $xp, $last_step, $score, $xp, $last_step]);
        
        // Update total XP
        $stmtXp = $pdo->prepare("INSERT INTO user_xp (user_id, total_xp) VALUES (?, ?) 
                                 ON DUPLICATE KEY UPDATE total_xp = total_xp + ?");
        // We only add XP if this wasn't fully maxed before, but for simplicity we add it. 
        // A better approach is to track diff, but for now just add.
        $stmtXp->execute([$user_id, $xp, $xp]);
        
        echo json_encode(['success' => true, 'xp_earned' => $xp]);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// Handle step evaluation
$step_id = (int)($data['step_id'] ?? 0);
$scenario_id = (int)($data['scenario_id'] ?? 0);
$answer = trim($data['answer'] ?? '');
$user_id = $_SESSION['user_id'];

if (!$step_id || !$answer || !$scenario_id) {
    echo json_encode(['correct' => false, 'explanation_ar' => 'إجابة غير صالحة', 'correction' => 'Bitte antworte.']);
    exit;
}

try {
    // 1. Save user's message
    $stmtInsU = $pdo->prepare("INSERT INTO scenario_chat_messages (user_id, scenario_id, sender, message_text) VALUES (?, ?, 'user', ?)");
    $stmtInsU->execute([$user_id, $scenario_id, $answer]);

    $stmt = $pdo->prepare("SELECT expected_keywords, hint FROM scenario_dialogues WHERE id = ?");
    $stmt->execute([$step_id]);
    $step = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$step) {
        echo json_encode(['error' => 'Step not found']);
        exit;
    }
    
    $keywords = json_decode($step['expected_keywords'], true) ?: [];
    
    // Simple evaluation logic: check if ANY keyword is in the answer
    $correct = false;
    $ans_lower = strtolower($answer);
    
    if (empty($keywords)) {
        $correct = strlen($answer) > 3;
    } else {
        foreach ($keywords as $kw) {
            if (strpos($ans_lower, strtolower($kw)) !== false) {
                $correct = true;
                break;
            }
        }
    }
    
    if (strlen($answer) <= 3 && !in_array($ans_lower, array_map('strtolower', $keywords))) {
        $correct = false;
    }
    
    $explanation = $correct ? 'إجابة صحيحة' : ($step['hint'] ?? 'إجابة خاطئة، حاول مرة أخرى.');
    $sysText = ($correct ? '✅ ' : '❌ ') . $explanation;

    // 2. Save system's evaluation response
    $stmtInsS = $pdo->prepare("INSERT INTO scenario_chat_messages (user_id, scenario_id, sender, message_text) VALUES (?, ?, 'system', ?)");
    $stmtInsS->execute([$user_id, $scenario_id, $sysText]);
    
    echo json_encode([
        'correct' => $correct,
        'explanation_ar' => $explanation,
        'correction' => '',
        'xp' => $correct ? 5 : 0
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => 'DB error: ' . $e->getMessage()]);
}

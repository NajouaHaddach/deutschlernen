<?php
// db.php
require_once __DIR__ . '/config.php';

function getDbConnection() {
    global $pdo;
    return $pdo;
}

/**
 * Anti-duplication logic: Inserts a scenario safely
 */
function insertScenarioSafe($pdo, $data) {
    $hash = md5($data['title'] . $data['description']);
    $stmt = $pdo->prepare("INSERT IGNORE INTO scenarios 
        (level_id, level, title, description, image, category, difficulty, content_hash)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    return $stmt->execute([
        $data['level_id'],
        $data['level'],
        $data['title'],
        $data['description'],
        $data['image'],
        $data['category'],
        $data['difficulty'],
        $hash
    ]);
}

/**
 * Tracks seen scenarios in the session to avoid repeating in the same session
 */
function markScenarioSeen($scenario_id) {
    if (!isset($_SESSION['seen_scenarios'])) {
        $_SESSION['seen_scenarios'] = [];
    }
    if (!in_array($scenario_id, $_SESSION['seen_scenarios'])) {
        $_SESSION['seen_scenarios'][] = $scenario_id;
    }
}

function hasSeenScenario($scenario_id) {
    if (!isset($_SESSION['seen_scenarios'])) return false;
    return in_array($scenario_id, $_SESSION['seen_scenarios']);
}

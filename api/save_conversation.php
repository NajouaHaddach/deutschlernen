<?php
session_start();
require_once '../config.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) { http_response_code(403); exit; }

$data = json_decode(file_get_contents('php://input'), true);
$stmt = $pdo->prepare("UPDATE scenario_conversations SET messages=?, exchange_count=?, completed=?, last_updated=NOW() WHERE user_id=? AND scenario_id=?");
$stmt->execute([
    json_encode($data['messages']),
    (int)$data['exchange_count'],
    $data['completed'] ? 1 : 0,
    $_SESSION['user_id'],
    (int)$data['scenario_id']
]);
echo json_encode(['success'=>true]);
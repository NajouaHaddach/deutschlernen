<?php
session_start();
require_once '../config.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) { http_response_code(403); exit; }

$data = json_decode(file_get_contents('php://input'), true);
$courseId = (int)($data['course_id'] ?? 0);
if ($courseId <= 0) { echo json_encode(['error'=>'Invalid course']); exit; }

$stmt = $pdo->prepare("INSERT INTO user_course_progress (user_id, course_id, completed) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE completed = 1");
$stmt->execute([$_SESSION['user_id'], $courseId]);

$stmt = $pdo->prepare("UPDATE users SET videos_vues = videos_vues + 1 WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
echo json_encode(['success'=>true]);
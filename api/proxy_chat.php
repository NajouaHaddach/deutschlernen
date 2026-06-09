<?php
session_start();
require_once '../config.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) { http_response_code(403); echo json_encode(['error'=>'Nicht authentifiziert']); exit; }

$data = json_decode(file_get_contents('php://input'), true);
$messages = $data['messages'] ?? [];
$systemPrompt = $data['system_prompt'] ?? '';

$apiMessages = [['role'=>'system','content'=>$systemPrompt]];
foreach ($messages as $m) $apiMessages[] = ['role'=>$m['role'],'content'=>$m['content']];

$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer '.OPENAI_API_KEY],
    CURLOPT_POSTFIELDS => json_encode(['model'=>OPENAI_MODEL,'messages'=>$apiMessages,'temperature'=>0.7,'max_tokens'=>300])
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    http_response_code(500);
    echo json_encode(['error'=>'API-Fehler','details'=>$response]);
    exit;
}
$result = json_decode($response, true);
$reply = $result['choices'][0]['message']['content'] ?? 'Entschuldigung, das habe ich nicht verstanden.';
echo json_encode(['reply'=>$reply]);
<?php
require_once '../config.php';
require_once '../functions.php';

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

header('Content-Type: application/json');

// Nettoyer tout output précédent (warnings, etc.) pour garantir un JSON valide
if (ob_get_length()) ob_clean();

$input = json_decode(file_get_contents('php://input'), true);

// Action pour effacer l'historique
if (isset($input['action']) && $input['action'] === 'clear_history') {
    $_SESSION['chat_history_api'] = [];
    echo json_encode(['success' => true]);
    exit;
}

$userMessage = $input['message'] ?? '';

if(empty($userMessage)){
    echo json_encode(['error' => 'Message vide']);
    exit;
}

// Récupération du contexte utilisateur
$userLevel = $_SESSION['niveau_actuel'] ?? 'A1';
$userName = $_SESSION['username'] ?? 'Étudiant';

// Garder historique de conversation (max 20 messages)
if(!isset($_SESSION['chat_history_api'])){
    $_SESSION['chat_history_api'] = [];
}
$_SESSION['chat_history_api'][] = ['role' => 'user', 'content' => $userMessage];

if(count($_SESSION['chat_history_api']) > 20){
    array_shift($_SESSION['chat_history_api']);
}

// Configuration OpenRouter
// Utilisation de la clé définie dans config.php
// Retrieve API key: prefer `OPENROUTER_API_KEY`, otherwise try to read from the database via PDO.
$apiKey = '';
if (defined('OPENROUTER_API_KEY') && OPENROUTER_API_KEY) {
    $apiKey = OPENROUTER_API_KEY;
} else {
    // Try common settings tables where an administrator might store the key.
    $candidates = [
        ["table"=>"settings","col_name"=>"name","col_value"=>"value","key_name"=>"openrouter_api_key"],
        ["table"=>"app_settings","col_name"=>"name","col_value"=>"value","key_name"=>"openrouter_api_key"],
        ["table"=>"options","col_name"=>"option_name","col_value"=>"option_value","key_name"=>"openrouter_api_key"],
    ];

    foreach ($candidates as $c) {
        try {
            $sql = "SELECT {$c['col_value']} AS v FROM {$c['table']} WHERE {$c['col_name']} = ? LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$c['key_name']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && !empty($row['v'])) {
                $apiKey = $row['v'];
                break;
            }
        } catch (Exception $e) {
            // Table/column might not exist — ignore and try next candidate
        }
    }

    // As a last resort, check if a per-user API key exists in `users` table (column: `openrouter_api_key`)
    if (empty($apiKey) && isset($_SESSION['user_id'])) {
        try {
            $stmt = $pdo->prepare("SELECT openrouter_api_key FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$_SESSION['user_id']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && !empty($row['openrouter_api_key'])) $apiKey = $row['openrouter_api_key'];
        } catch (Exception $e) {
            // ignore
        }
    }
}

// If still empty, return an error explaining how to provide the key (DB or config)
if (empty($apiKey)) {
    echo json_encode(['error' => 'No API key', 'reply' => "Clé API OpenRouter introuvable. Veuillez définir OPENROUTER_API_KEY dans config.php ou insérer la clé dans la table 'settings' (name='openrouter_api_key')."]);
    exit;
}
// IMPORTANT: Assurez-vous que votre clé API OpenRouter est valide et active.
// Vous pouvez la définir dans config.php: define('OPENROUTER_API_KEY', 'votre_cle_ici');
$url = 'https://openrouter.ai/api/v1/chat/completions';
$model = 'google/gemini-2.0-flash-exp:free'; // Modèle gratuit actuel et stable sur OpenRouter

/**
 * PROMPT SPÉCIFIQUE AU PROJET LINGUAFLOW

 */
$systemPrompt = "Tu es 'DeutschBot', l'expert pédagogique exclusif de la plateforme LinguaFlow (DeutschLernen).
Ton objectif est d'aider $userName (Niveau actuel: $userLevel) à maîtriser l'allemand.

L'utilisateur est actuellement au niveau $userLevel. Adapte tes explications et ton vocabulaire à ce niveau.
RÈGLES DE COMMUNICATION :
1. Réponds principalement en ALLEMAND pour encourager la pratique.
2. Si l'utilisateur dit 'mafhmtch', 'je n'ai pas compris', 'explique en arabe', ou toute phrase similaire indiquant une incompréhension, alors explique le point en ARABE.
3. Corrige systématiquement les fautes de grammaire en allemand de l'utilisateur avant de répondre.
4. Utilise le français pour des explications grammaticales complexes si l'arabe n'est pas demandé.

CONNAISSANCES DU PROJET :
- La plateforme propose des 'Szenarien' (Scénarios) : Aéroport, Hôtel, Supermarché, Médecin, etc. Si l'utilisateur a du mal, suggère-lui de pratiquer un scénario spécifique.
- Il existe des forums fermés par niveau (A1-C2). L'accès se fait par un test de niveau (70% requis).
- Encourage l'utilisateur à aller passer les tests de niveau sur la page 'Tests' pour progresser.";

$messages = [['role' => 'system', 'content' => $systemPrompt]];
foreach($_SESSION['chat_history_api'] as $msg){
    $messages[] = $msg;
}

$data = [
    'model' => $model,
    'messages' => $messages,
    'temperature' => 0.7,
    'max_tokens' => 300
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey,
    'HTTP-Referer: ' . (defined('SITE_URL') ? SITE_URL : 'http://localhost/deutschlernen'),
    'X-Title: DeutschLernen AI'
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4); // Force la résolution en IPv4 (évite souvent le code 6 sur XAMPP)
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Solution pour les erreurs de certificats sur XAMPP
curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Ajoute un timeout de 30 secondes

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch); // Récupère le message d'erreur cURL
$curlErrno = curl_errno($ch); // Récupère le numéro d'erreur cURL
curl_close($ch);

/**
 * Extraire une réponse texte lisible depuis la structure JSON retournée par différents fournisseurs.
 */
function extract_reply_text($result) {
    if (!is_array($result)) return null;

    // OpenAI-like / OpenRouter: choices -> [ { message: { content } } ]
    if (isset($result['choices']) && is_array($result['choices'])) {
        foreach ($result['choices'] as $c) {
            if (isset($c['message']['content']) && is_string($c['message']['content'])) return $c['message']['content'];
            if (isset($c['text']) && is_string($c['text'])) return $c['text'];
            if (isset($c['message']) && is_string($c['message'])) return $c['message'];
        }
    }

    // Some APIs return outputs / results
    if (isset($result['output']) && is_array($result['output'])) {
        foreach ($result['output'] as $out) {
            if (is_string($out)) return $out;
            if (isset($out['content']) && is_string($out['content'])) return $out['content'];
            if (isset($out['text']) && is_string($out['text'])) return $out['text'];
        }
    }

    if (isset($result['message']) && is_string($result['message'])) return $result['message'];

    // Fallback: find the first string anywhere in the array
    $it = new RecursiveIteratorIterator(new RecursiveArrayIterator($result));
    foreach ($it as $val) {
        if (is_string($val) && strlen(trim($val))>0) return $val;
    }

    return null;
}

if ($curlErrno) {
    error_log("cURL Error ($curlErrno): " . $curlError);
    echo json_encode([
        'error' => 'cURL Error',
        'reply' => "Erreur réseau lors de la connexion à l'IA. Code: $curlErrno, Message: $curlError"
    ]);
    exit;
}

// Essayer de décoder la réponse JSON
$result = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("Invalid JSON from OpenRouter (HTTP $httpCode): " . substr($response,0,1000));
    echo json_encode(['error'=>'Invalid Response','reply'=>'Réponse invalide de l’API. Voir logs serveur.']);
    exit;
}

if ($httpCode == 200) {
    $botReply = extract_reply_text($result) ?? "Je n'ai pas compris.";
    $_SESSION['chat_history_api'][] = ['role'=>'assistant', 'content'=>$botReply];
    echo json_encode(['reply' => $botReply]);
    exit;
}

// Erreur HTTP retournée par l'API
error_log("OpenRouter HTTP Error ($httpCode): " . $response);
$errorDetails = is_array($result) ? $result : [];
$errorMessage = $errorDetails['error']['message'] ?? $errorDetails['message'] ?? ($errorDetails['detail'] ?? 'Erreur inconnue de l\'API OpenRouter.');
echo json_encode([
    'error' => 'API Error',
    'reply' => "L'IA a rencontré un problème. Code: $httpCode, Détails: $errorMessage"
]);
?>
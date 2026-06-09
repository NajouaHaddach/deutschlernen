<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════════
 * DEUTSCHLERNEN - FORUM CHAT (REPLIES, REACTIONS, EMOJIS)
 * Sécurisé par système de niveaux — accès contrôlé par test
 * ═══════════════════════════════════════════════════════════════════════════════
 */

require_once '../config.php';
require_once '../functions.php';

if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

$userId   = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';

// ── Niveau demandé ──────────────────────────────────────────────────────────
$niveauxValides = ['A1','A2','B1','B2','C1','C2'];
$niveauChat     = strtoupper(trim($_GET['niveau'] ?? 'A1'));
if (!in_array($niveauChat, $niveauxValides)) {
    $niveauChat = 'A1';
}

// ── Sécurité : vérifier que ce niveau est débloqué pour cet utilisateur ────
// A1 est toujours débloqué par défaut
$pdo->prepare("INSERT IGNORE INTO niveaux_debloques (user_id, niveau) VALUES (?,?)")
    ->execute([$userId, 'A1']);

$stmtCheck = $pdo->prepare("SELECT id FROM niveaux_debloques WHERE user_id=? AND niveau=?");
$stmtCheck->execute([$userId, $niveauChat]);
if (!$stmtCheck->fetch()) {
    // Accès refusé → redirect vers la page des tests
    header('Location: ../tests/tests.php?error=forum_locked&niveau=' . urlencode($niveauChat));
    exit;
}

// ── Fetch ou créer la room pour ce niveau ──────────────────────────────────
$roomId = 0;
try {
    $stmt = $pdo->prepare("SELECT id, name, description FROM forum_rooms WHERE niveau=? OR level=? LIMIT 1");
    $stmt->execute([$niveauChat, $niveauChat]);
    $room = $stmt->fetch();
    if (!$room) {
        $roomName = "Forum $niveauChat";
        $roomDesc = "Discussion et pratique — Niveau $niveauChat";
        $pdo->prepare("INSERT INTO forum_rooms (name, description, niveau, level) VALUES (?,?,?,?)")
            ->execute([$roomName, $roomDesc, $niveauChat, $niveauChat]);
        $roomId = $pdo->lastInsertId();
        $room   = ['id' => $roomId, 'name' => $roomName, 'description' => $roomDesc];
    } else {
        $roomId = $room['id'];
    }
} catch (Exception $e) {
    $roomId = 1;
    $room   = ['id' => 1, 'name' => "Forum $niveauChat", 'description' => "Niveau $niveauChat"];
}

$initials = mb_strtoupper(mb_substr($username, 0, 2));
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['user_language'] ?? 'de' ?>" data-theme="<?= $_SESSION['theme'] ?? 'dark' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - LinguaFlow</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <script src="https://meet.jit.si/external_api.js"></script>
    <style>
        :root {
            --bg-app: #f0f2f5; --bg-chat: #efeae2; --bg-card: #ffffff;
            --sidebar: #ffffff; --sidebar-hover: #f3f4f6;
            --accent: #00a884; --accent-hover: #008f6f;
            --msg-in: #ffffff; --msg-out: #d9fdd3;
            --text-main: #111b21; --text-muted: #667781; --border: #e9edef;
            --shadow: 0 1px 2px rgba(11,20,26,0.1);
        }
        [data-theme="dark"] {
            --bg-app: #111b21; --bg-chat: #0b141a; --bg-card: #202c33;
            --sidebar: #111b21; --sidebar-hover: #202c33;
            --accent: #00a884; --msg-in: #202c33; --msg-out: #005c4b;
            --text-main: #e9edef; --text-muted: #8696a0; --border: #222d34;
        }

        body { font-family: 'Inter', sans-serif; background: #e2e8f0; color: var(--text-main); display: flex; align-items:center; justify-content:center; height: 100vh; overflow: hidden; margin: 0; }
        [data-theme="dark"] body { background: #0a1014; }
        
        .app-container { width: 100%; max-width: 1350px; height: calc(100vh - 40px); display: flex; background: var(--bg-app); box-shadow: 0 10px 30px rgba(0,0,0,0.1); border-radius: 12px; overflow: hidden; position:relative; }
        @media (max-width: 1400px) {
            .app-container { max-width: 100%; height: 100vh; border-radius: 0; }
        }
        
        button { cursor: pointer; border: none; background: transparent; color: inherit; font-family: inherit; }
        
        /* SIDEBAR */
        .sidebar { width: 300px; background: var(--sidebar); border-right: 1px solid var(--border); display: flex; flex-direction: column; z-index: 50; }
        .sidebar-header { height: 60px; background: var(--bg-card); display: flex; align-items: center; padding: 0 16px; border-bottom: 1px solid var(--border); font-weight: 700; font-size: 18px; }
        .nav-items { padding: 12px; display: flex; flex-direction: column; gap: 4px; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 8px; color: var(--text-main); text-decoration: none; font-weight: 500; transition: 0.2s; }
        .nav-item:hover, .nav-item.active { background: var(--sidebar-hover); }
        .nav-item i { font-size: 20px; color: var(--text-muted); }
        .nav-item.active i { color: var(--accent); }

        /* CHAT APP */
        .chat-app { flex: 1; display: flex; flex-direction: column; background: var(--bg-chat); position: relative; }
        
        /* HEADER */
        .chat-header { height: 60px; background: var(--bg-card); display: flex; align-items: center; justify-content: space-between; padding: 0 16px; border-bottom: 1px solid var(--border); z-index: 10; }
        .header-info { display: flex; align-items: center; gap: 12px; cursor: pointer; padding: 6px 12px; border-radius: 8px; transition: 0.2s; margin-left: -12px; }
        .header-info:hover { background: var(--sidebar-hover); }
        .avatar { width: 40px; height: 40px; background: #e2e8f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; color: #475569; }
        .header-text h2 { font-size: 16px; font-weight: 600; margin: 0 0 2px; display: flex; align-items: center; gap: 6px; }
        .presence { font-size: 13px; color: var(--text-muted); }
        
        .header-actions { display: flex; gap: 16px; color: var(--text-muted); }
        .header-actions i { font-size: 20px; cursor: pointer; transition: 0.2s; }
        .header-actions i:hover { color: var(--text-main); }

        /* MESSAGES */
        .chat-body { flex: 1; overflow-y: auto; padding: 20px 5%; display: flex; flex-direction: column; gap: 8px; }
        .msg-row { display: flex; flex-direction: column; align-items: flex-start; max-width: 65%; position: relative; }
        .msg-row.own { align-items: flex-end; align-self: flex-end; }
        
        .bubble { background: var(--msg-in); padding: 8px 12px; border-radius: 8px; box-shadow: var(--shadow); position: relative; min-width: 80px; }
        .msg-row.own .bubble { background: var(--msg-out); }
        
        .msg-user { font-size: 12px; font-weight: 600; color: #ef4444; margin-bottom: 4px; }
        .msg-row.own .msg-user { display: none; }
        
        .msg-content { font-size: 14px; line-height: 1.4; word-break: break-word; }
        .msg-meta { display: flex; justify-content: flex-end; align-items: center; gap: 4px; margin-top: 4px; font-size: 11px; color: var(--text-muted); }
        
        /* QUOTE / REPLY UI */
        .quote-box { background: rgba(0,0,0,0.05); border-left: 4px solid var(--accent); padding: 6px 10px; border-radius: 4px; margin-bottom: 8px; font-size: 13px; cursor: pointer; }
        [data-theme="dark"] .quote-box { background: rgba(255,255,255,0.05); }
        .quote-user { font-weight: 600; color: var(--accent); margin-bottom: 2px; font-size: 12px; }
        
        /* HOVER ACTIONS (Copy, Reply) */
        .msg-actions { position: absolute; top: 0; right: -70px; background: var(--bg-card); border: 1px solid var(--border); border-radius: 8px; box-shadow: var(--shadow); display: none; padding: 4px; gap: 4px; z-index: 10; }
        .msg-row.own .msg-actions { right: auto; left: -70px; }
        .msg-row:hover .msg-actions { display: flex; }
        .action-icon { width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; border-radius: 4px; cursor: pointer; color: var(--text-muted); }
        .action-icon:hover { background: var(--sidebar-hover); color: var(--text-main); }

        /* FOOTER */
        .chat-footer { background: var(--bg-card); padding: 10px 16px; border-top: 1px solid var(--border); display: flex; flex-direction: column; }
        
        #replyBanner { display: none; background: var(--sidebar-hover); padding: 10px 16px; border-left: 4px solid var(--accent); border-radius: 4px 4px 0 0; position: relative; margin-bottom: 10px; }
        .reply-close { position: absolute; right: 10px; top: 10px; cursor: pointer; color: var(--text-muted); }

        .input-row { display: flex; align-items: flex-end; gap: 12px; }
        .icon-btn { color: var(--text-muted); font-size: 24px; padding: 8px; border-radius: 50%; transition: 0.2s; display: flex; }
        .icon-btn:hover { background: var(--sidebar-hover); }
        
        .input-box { flex: 1; background: var(--bg-card); border: 1px solid var(--border); border-radius: 20px; padding: 9px 16px; display: flex; min-height: 40px; }
        .main-input { flex: 1; background: transparent; border: none; font-size: 15px; color: var(--text-main); outline: none; resize: none; max-height: 100px; font-family: inherit; }
        
        .send-btn { background: var(--accent); color: #fff; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; transition: 0.2s; }
        .send-btn:hover { background: var(--accent-hover); }

        /* EMOJI PICKER */
        .emoji-picker { position: absolute; bottom: 80px; left: 16px; background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); width: 300px; display: none; grid-template-columns: repeat(8, 1fr); padding: 10px; gap: 5px; z-index: 100; max-height: 200px; overflow-y: auto; }
        .emoji-item { font-size: 20px; text-align: center; cursor: pointer; padding: 4px; border-radius: 4px; }
        .emoji-item:hover { background: var(--sidebar-hover); }

        /* AUDIO */
        audio { height: 35px; width: 220px; outline: none; }
        .rec-pulse { animation: pulse 1s infinite; color: #ef4444 !important; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.4; } 100% { opacity: 1; } }
        
        /* REACTIONS UI */
        .reactions { display: flex; gap: 4px; margin-top: 4px; flex-wrap: wrap; }
        .reaction { background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; padding: 2px 6px; font-size: 11px; display: flex; align-items: center; gap: 4px; cursor: pointer; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
        .reaction.active { background: var(--accent-soft); border-color: var(--accent); color: var(--accent); }

        /* AUDIO PREVIEW UI */
        #recordingUI { display: none; align-items: center; gap: 12px; flex: 1; background: transparent; padding: 0 16px; min-height: 40px; }
        .rec-wave { display: flex; gap: 3px; align-items: center; }
        .rec-wave span { width: 3px; height: 12px; background: #ef4444; border-radius: 10px; animation: wave 1s infinite ease-in-out; }
        @keyframes wave { 0%, 100% { height: 12px; } 50% { height: 24px; } }
        .rec-time { font-family: 'JetBrains Mono'; font-weight: 600; color: #ef4444; font-size: 14px; }
        
        #audioPreviewUI { display: none; align-items: center; gap: 8px; flex: 1; background: transparent; padding: 0 16px; min-height: 40px; }

        /* CUSTOM AUDIO PLAYER */
        .custom-audio { display: flex; align-items: center; gap: 10px; width: 250px; }
        .ca-play { color: var(--text-muted); font-size: 28px; cursor: pointer; display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; }
        .ca-play:hover { color: var(--accent); }
        .ca-track { position: relative; flex: 1; display: flex; align-items: center; height: 36px; }
        .ca-slider { -webkit-appearance: none; width: 100%; height: 4px; background: rgba(0,0,0,0.1); border-radius: 2px; outline: none; margin: 0 0 6px 0; cursor: pointer; }
        .ca-slider::-webkit-slider-thumb { -webkit-appearance: none; width: 12px; height: 12px; border-radius: 50%; background: var(--accent); cursor: pointer; }
        [data-theme="dark"] .ca-slider { background: rgba(255,255,255,0.1); }
        .ca-time { position: absolute; bottom: 0; left: 0; font-size: 10px; color: var(--text-muted); font-family: 'JetBrains Mono'; }
        
        .audio-speed { font-size: 11px; font-weight: 700; background: rgba(0,0,0,0.1); padding: 4px 8px; border-radius: 12px; cursor: pointer; user-select: none; }
        [data-theme="dark"] .audio-speed { background: rgba(255,255,255,0.1); }

        /* REACTION PICKER */
        .reaction-picker { position: absolute; background: var(--bg-card); border: 1px solid var(--border); border-radius: 20px; box-shadow: var(--shadow); display: none; padding: 6px 10px; gap: 8px; z-index: 50; }
        .reaction-picker span { font-size: 22px; cursor: pointer; transition: 0.1s; display: inline-block; }
        .reaction-picker span:hover { transform: scale(1.3) translateY(-2px); }

        /* IMAGE VIEWER */
        #imageViewer { position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.9); z-index:9999; display:none; align-items:center; justify-content:center; flex-direction:column; }
        #imageViewer:not(.hidden) { display:flex; }
        .viewer-close { position:absolute; top:20px; right:20px; color:#fff; background:rgba(255,255,255,0.2); width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:24px; cursor:pointer; transition:0.2s; }
        .viewer-close:hover { background:rgba(255,255,255,0.4); transform:scale(1.1); }
        #viewerImage { max-width:90%; max-height:85%; object-fit:contain; border-radius:8px; box-shadow:0 10px 25px rgba(0,0,0,0.5); }

        /* INFO SIDEBAR (WHATSAPP STYLE) */
        .info-sidebar { width: 320px; background: var(--bg-app); border-left: 1px solid var(--border); display: flex; flex-direction: column; transition: margin-right 0.3s ease; }
        .info-sidebar.hidden { margin-right: -320px; display: flex !important; }
        .info-header { height: 60px; background: var(--bg-card); display: flex; align-items: center; padding: 0 16px; border-bottom: 1px solid var(--border); font-weight: 600; font-size: 16px; gap:16px; }
        .info-body { flex: 1; overflow-y: auto; display: flex; flex-direction: column; }
        .info-section { background: var(--bg-card); padding: 24px 20px; margin-bottom: 8px; display: flex; flex-direction: column; align-items: center; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
        .info-section.left { align-items: flex-start; padding: 16px 20px; }
        
        .hidden-strict { display: none !important; }
        .hidden { display: none !important; }
    </style>
</head>
<body>

<div class="app-container">

    <!-- SIDEBAR -->
    <nav class="sidebar">
        <div class="sidebar-header">
            <i class="ti ti-language" style="color:var(--accent); margin-right:10px; font-size:24px"></i> LinguaFlow
        </div>
        <div class="nav-items">
            <a href="../dashboard.php" class="nav-item"><i class="ti ti-layout-dashboard"></i> Dashboard</a>
            <a href="index.php" class="nav-item active"><i class="ti ti-messages"></i> Chat Room A1</a>
        </div>
    </nav>

    <!-- IMAGE VIEWER -->
    <div id="imageViewer" class="hidden">
        <div class="viewer-close" onclick="closeImageViewer()"><i class="ti ti-x"></i></div>
        <img id="viewerImage" src="" alt="Viewed Image">
    </div>

    <!-- CALL VIEWER -->
    <div id="callViewer" class="hidden-strict" style="position:fixed; top:0; left:0; width:100vw; height:100vh; background:var(--bg-app); z-index:10000; display:flex; flex-direction:column;">
        <div style="height:60px; background:#111b21; display:flex; align-items:center; justify-content:space-between; padding:0 20px; color:#fff;">
            <div style="font-weight:bold; font-size:18px; display:flex; align-items:center; gap:8px;"><i class="ti ti-phone"></i> LinguaFlow Call</div>
            <button onclick="endCall()" style="background:#ef4444; color:#fff; border:none; padding:8px 16px; border-radius:8px; cursor:pointer; font-weight:bold; transition:0.2s;">Terminer l'appel</button>
        </div>
        <div id="jitsiContainer" style="flex:1; background:#000;"></div>
    </div>

    <!-- MAIN CHAT -->
    <main class="chat-app">
        <header class="chat-header">
            <div class="header-info" onclick="toggleGroupInfo()">
                <div class="avatar">A1</div>
                <div class="header-text" style="width: 250px;">
                    <h2><?= htmlspecialchars($room['name']) ?></h2>
                    <div class="presence" id="memberCount" style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">...</div>
                </div>
            </div>
            <div class="header-actions">
                <i class="ti ti-phone" title="Appel Vocal" onclick="startJitsiCall('voice')"></i>
                <i class="ti ti-video" title="Appel Vidéo" onclick="startJitsiCall('video')"></i>
            </div>
        </header>

        <div class="chat-body" id="chatBody"></div>
        
        <!-- Reaction Picker -->
        <div class="reaction-picker" id="reactPicker">
            <span onclick="pickReaction('👍')">👍</span>
            <span onclick="pickReaction('❤️')">❤️</span>
            <span onclick="pickReaction('😂')">😂</span>
            <span onclick="pickReaction('😮')">😮</span>
            <span onclick="pickReaction('😢')">😢</span>
            <span onclick="pickReaction('🙏')">🙏</span>
        </div>

        <footer class="chat-footer">
            <!-- Reply Banner -->
            <div id="replyBanner">
                <div class="quote-user" id="replyUser">User</div>
                <div id="replyText" style="font-size:13px; color:var(--text-muted); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">Message preview...</div>
                <i class="ti ti-x reply-close" onclick="cancelReply()"></i>
            </div>

            <!-- Emoji Picker -->
            <div class="emoji-picker" id="emojiPicker"></div>

            <div class="input-row">
                <button class="icon-btn" id="emojiBtnToggle" onclick="toggleEmoji()"><i class="ti ti-mood-smile"></i></button>
                <button class="icon-btn" id="attachBtnToggle" onclick="document.getElementById('fileInp').click()"><i class="ti ti-paperclip"></i></button>
                <input type="file" id="fileInp" class="hidden" onchange="handleFile(this)">
                
                <div class="input-box" id="textInputBox">
                    <textarea class="main-input" id="msgInput" placeholder="Type a message" rows="1"></textarea>
                </div>
                
                <div id="recordingUI">
                    <div class="rec-wave"><span></span><span style="animation-delay:0.1s"></span><span style="animation-delay:0.2s"></span></div>
                    <span class="rec-time" id="recTime">0:00</span>
                    <div style="flex:1"></div>
                    <button class="icon-btn" style="color:#ef4444" onclick="stopRecording()"><i class="ti ti-player-stop-filled"></i></button>
                </div>

                <div id="audioPreviewUI" style="display:none;">
                    <button class="icon-btn" style="color:#ef4444" onclick="cancelAudio()"><i class="ti ti-trash"></i></button>
                    <div class="custom-audio" style="flex:1">
                        <div class="ca-play" onclick="toggleAud('previewPlayer', 'prevIcon')"><i class="ti ti-player-play-filled" id="prevIcon"></i></div>
                        <div class="ca-track">
                            <input type="range" class="ca-slider" id="prevSlider" value="0" max="100" oninput="seekAud('previewPlayer', this.value)">
                            <div class="ca-time" id="prevTime">0:00</div>
                        </div>
                        <audio id="previewPlayer" style="display:none;"
                           ontimeupdate="updateAud('previewPlayer', 'prevSlider', 'prevTime')"
                           onloadedmetadata="initAud('previewPlayer', 'prevTime')"
                           onended="resetAud('prevIcon', 'prevSlider', 'prevTime', 'previewPlayer')"></audio>
                    </div>
                    <button class="send-btn" onclick="sendMsg()"><i class="ti ti-send"></i></button>
                </div>
                
                <button class="icon-btn" id="micBtn" onclick="toggleRecording()"><i class="ti ti-microphone"></i></button>
                <button class="send-btn" id="sendBtn" onclick="sendMsg()"><i class="ti ti-send"></i></button>
            </div>
            
            <div id="filePreview" class="hidden" style="margin-top:8px; font-size:12px; color:var(--accent)">
                <i class="ti ti-file"></i> <span id="fileName"></span> <i class="ti ti-x" style="cursor:pointer" onclick="clearFile()"></i>
            </div>
        </footer>
    </main>

    <!-- RIGHT SIDEBAR (INFO) -->
    <aside class="info-sidebar hidden" id="infoSidebar">
        <div class="info-header">
            <i class="ti ti-x" style="cursor:pointer; font-size:20px; color:var(--text-muted);" onclick="toggleGroupInfo()"></i>
            Infos du groupe
        </div>
        <div class="info-body">
            <div class="info-section">
                <div style="width:120px; height:120px; background:var(--accent); color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:48px; font-weight:bold; margin-bottom:16px; box-shadow:0 4px 10px rgba(0,168,132,0.3);">A1</div>
                <h2 style="margin:0 0 4px; font-size:20px; font-weight:600;"><?= htmlspecialchars($room['name']) ?></h2>
                <div style="color:var(--text-muted); font-size:14px; text-align:center;">Groupe • <span id="sidebarMemberCount">0</span> participant(s)</div>
            </div>
            <div class="info-section left">
                <div style="color:var(--text-muted); font-size:13px; font-weight:600; margin-bottom:8px;">Description</div>
                <div style="font-size:14px; line-height:1.4;"><?= htmlspecialchars($room['description']) ?></div>
            </div>
            <div class="info-section left" style="flex:1;">
                <div style="color:var(--text-muted); font-size:13px; font-weight:600; margin-bottom:12px;">Participants en ligne</div>
                <div id="sidebarMembersList" style="width:100%; display:flex; flex-direction:column;">
                    <!-- Members list -->
                </div>
            </div>
        </div>
    </aside>

</div> <!-- End of .app-container -->

    <script>
        const CFG = { roomId: <?= $roomId ?>, userId: <?= $userId ?>, api: 'ajax/chat_handler.php' };
        let lastId = 0, selectedFile = null, replyToId = null, mediaRecorder, audioChunks = [], recInterval;
        
        const EMOJIS = ['😀','😂','🥰','😎','🤔','👍','❤️','🔥','👏','🎉','✨','💯','🙌','🤝','🙏','😢'];

        document.addEventListener('DOMContentLoaded', () => {
            initEmojis();
            loadMessages();
            updatePresence();
            setInterval(loadMessages, 3000);
            setInterval(updatePresence, 10000);

            const inp = document.getElementById('msgInput');
            inp.oninput = () => { 
                inp.style.height = 'auto'; inp.style.height = inp.scrollHeight + 'px'; 
                toggleSendMic(inp.value.trim().length > 0 || selectedFile);
            };
            inp.onkeydown = (e) => { if(e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMsg(); } };
        });

        function initEmojis() {
            const ep = document.getElementById('emojiPicker');
            EMOJIS.forEach(e => {
                const span = document.createElement('span');
                span.className = 'emoji-item';
                span.innerText = e;
                span.onclick = () => {
                    const inp = document.getElementById('msgInput');
                    inp.value += e;
                    toggleEmoji();
                    toggleSendMic(true);
                };
                ep.appendChild(span);
            });
        }

        function toggleEmoji() {
            const ep = document.getElementById('emojiPicker');
            ep.style.display = ep.style.display === 'grid' ? 'none' : 'grid';
        }

        function toggleSendMic(hasContent) {
            const sendBtn = document.getElementById('sendBtn');
            const micBtn = document.getElementById('micBtn');
            if (hasContent) { micBtn.style.display = 'none'; sendBtn.style.display = 'flex'; }
            else { micBtn.style.display = 'flex'; sendBtn.style.display = 'none'; }
        }

        async function loadMessages() {
            try {
                const r = await fetch(`${CFG.api}?action=load_messages&room_id=${CFG.roomId}&last_id=${lastId}`);
                const d = await r.json();
                if (d.success && d.messages) {
                    const body = document.getElementById('chatBody');
                    let scrolled = false;
                    d.messages.forEach(m => {
                        if(!document.getElementById(`msg-${m.id}`)) {
                            appendMsg(m);
                            lastId = Math.max(lastId, m.id);
                            scrolled = true;
                        }
                    });
                    if(scrolled) body.scrollTop = body.scrollHeight;
                }
            } catch (e) { console.error(e); }
        }

        function appendMsg(m) {
            const body = document.getElementById('chatBody');
            const isOwn = m.user_id == CFG.userId;
            const div = document.createElement('div');
            div.id = `msg-${m.id}`;
            div.className = `msg-row ${isOwn ? 'own' : ''}`;
            
            let html = '';
            
            // Quote / Reply UI
            if (m.parent_id && m.parent_text) {
                html += `<div class="quote-box" onclick="document.getElementById('msg-${m.parent_id}').scrollIntoView({behavior:'smooth'})">
                    <div class="quote-user">${esc(m.parent_username || 'User')}</div>
                    <div>${esc(m.parent_text)}</div>
                </div>`;
            }

            // Media
            if (m.message_type === 'image') html += `<img src="../${m.file_path}" style="max-width:250px; border-radius:8px; cursor:pointer; box-shadow:var(--shadow)" onclick="openImageViewer(this.src)">`;
            else if (m.message_type === 'audio') {
                html += `<div style="display:flex; align-items:center; gap:8px;">
                            <div class="custom-audio">
                                <div class="ca-play" onclick="toggleAud('aud-${m.id}', 'ca-icon-${m.id}')"><i class="ti ti-player-play-filled" id="ca-icon-${m.id}"></i></div>
                                <div class="ca-track">
                                    <input type="range" class="ca-slider" id="ca-slider-${m.id}" value="0" max="100" oninput="seekAud('aud-${m.id}', this.value)">
                                    <div class="ca-time" id="ca-time-${m.id}">0:00</div>
                                </div>
                                <div class="audio-speed" onclick="toggleSpeed(this, 'aud-${m.id}')">1x</div>
                                <audio id="aud-${m.id}" src="../${m.file_path}" class="hidden" 
                                   ontimeupdate="updateAud('aud-${m.id}', 'ca-slider-${m.id}', 'ca-time-${m.id}')"
                                   onloadedmetadata="initAud('aud-${m.id}', 'ca-time-${m.id}')"
                                   onended="resetAud('ca-icon-${m.id}', 'ca-slider-${m.id}', 'ca-time-${m.id}', 'aud-${m.id}')"></audio>
                            </div>
                         </div>`;
            }
            else if (m.message_type === 'file') html += `<a href="../${m.file_path}" download style="display:flex; align-items:center; gap:8px; color:var(--accent)"><i class="ti ti-file"></i> ${esc(m.file_name)}</a>`;
            else html += `<div class="msg-content">${esc(m.message_text).replace(/\n/g, '<br>')}</div>`;

            // Hover Actions
            let safeText = (m.message_text || '').replace(/\\/g, "\\\\").replace(/'/g, "\\'").replace(/\n/g, "\\n").replace(/\r/g, "");
            let actions = `
                <div class="msg-actions">
                    <div class="action-icon" title="React" onclick="showReactionPicker(${m.id})"><i class="ti ti-mood-smile"></i></div>
                    <div class="action-icon" title="Reply" onclick="setReply(${m.id}, '${esc(m.username)}', '${safeText || m.message_type}')"><i class="ti ti-arrow-back-up"></i></div>
                    <div class="action-icon" title="Copy" onclick="copyText('${safeText}')"><i class="ti ti-copy"></i></div>
            `;
            if (isOwn) {
                if (m.message_type === 'text') {
                    actions += `<div class="action-icon" title="Modifier" onclick="editMsg(${m.id}, '${safeText}')"><i class="ti ti-pencil"></i></div>`;
                }
                actions += `<div class="action-icon" title="Supprimer" style="color:#ef4444" onclick="deleteMsg(${m.id})"><i class="ti ti-trash"></i></div>`;
            }
            actions += `</div>`;

            // Reactions UI
            let reactHtml = '';
            if (m.reactions && m.reactions.length) {
                let grouped = {};
                m.reactions.forEach(r => { grouped[r.emoji] = (grouped[r.emoji] || 0) + 1; });
                reactHtml = `<div class="reactions">`;
                for (const [emoji, count] of Object.entries(grouped)) {
                    const isMe = m.reactions.some(r => r.emoji === emoji && r.user_id == CFG.userId);
                    reactHtml += `<div class="reaction ${isMe ? 'active' : ''}" onclick="toggleReaction(${m.id}, '${emoji}')">${emoji} ${count}</div>`;
                }
                reactHtml += `</div>`;
            }

            let tick = isOwn ? '<i class="ti ti-checks" style="color:#00a884; font-size:14px; margin-left:4px;"></i>' : '';

            div.innerHTML = `
                <div class="msg-user">${esc(m.username)}</div>
                <div class="bubble">
                    ${actions}
                    ${html}
                    ${reactHtml}
                    <div class="msg-meta">${m.time_formatted}${tick}</div>
                </div>
            `;
            body.appendChild(div);
        }

        async function sendMsg() {
            const inp = document.getElementById('msgInput');
            const txt = inp.value.trim();
            if (!txt && !selectedFile) return;

            const fd = new FormData();
            
            // Check if editing
            if (window.editMsgId) {
                fd.append('action', 'edit_message');
                fd.append('message_id', window.editMsgId);
                fd.append('content', txt);
                window.editMsgId = null;
            } else {
                fd.append('action', 'send_message');
                fd.append('room_id', CFG.roomId);
                fd.append('content', txt);
                if (replyToId) fd.append('parent_id', replyToId);
                if (selectedFile) fd.append('file', selectedFile);
            }

            inp.value = ''; inp.style.height = 'auto'; 
            cancelReply(); clearFile();
            document.getElementById('sendBtn').style.display = 'flex';
            document.getElementById('micBtn').style.display = 'flex';

            try {
                const r = await fetch(CFG.api, { method: 'POST', body: fd });
                const d = await r.json();
                if(d.success) {
                    lastId = 0; document.getElementById('chatBody').innerHTML = ''; loadMessages();
                }
            } catch (e) { alert("Failed to send."); }
        }

        async function deleteMsg(id) {
            if (!confirm("Voulez-vous supprimer ce message pour tout le monde ?")) return;
            try {
                const fd = new FormData();
                fd.append('action', 'delete_message');
                fd.append('message_id', id);
                await fetch(CFG.api, { method: 'POST', body: fd });
                lastId = 0; document.getElementById('chatBody').innerHTML = ''; loadMessages();
            } catch(e){}
        }

        function editMsg(id, txt) {
            window.editMsgId = id;
            const inp = document.getElementById('msgInput');
            inp.value = txt;
            inp.focus();
            toggleSendMic(true);
            
            replyToId = null;
            document.getElementById('replyUser').innerText = "Modifier le message";
            document.getElementById('replyText').innerText = txt;
            document.getElementById('replyBanner').style.display = 'block';
        }

        function setReply(id, user, text) {
            replyToId = id;
            window.editMsgId = null;
            document.getElementById('replyUser').innerText = "Répondre à " + user;
            document.getElementById('replyText').innerText = text;
            document.getElementById('replyBanner').style.display = 'block';
            document.getElementById('msgInput').focus();
        }
        function cancelReply() { 
            let wasEdit = window.editMsgId;
            replyToId = null; 
            window.editMsgId = null;
            document.getElementById('replyBanner').style.display = 'none'; 
            if (wasEdit) document.getElementById('msgInput').value = '';
        }

        function copyText(txt) {
            if(txt && txt !== 'null') {
                navigator.clipboard.writeText(txt);
                alert("Message copied!");
            }
        }

        let currentReactMsgId = null;
        function showReactionPicker(msgId) {
            currentReactMsgId = msgId;
            const msgEl = document.getElementById(`msg-${msgId}`);
            const picker = document.getElementById('reactPicker');
            msgEl.querySelector('.bubble').appendChild(picker);
            picker.style.display = 'flex';
            picker.style.top = '-35px';
            picker.style.left = '50%';
            picker.style.transform = 'translateX(-50%)';
        }

        async function pickReaction(emoji) {
            document.getElementById('reactPicker').style.display = 'none';
            if (currentReactMsgId) await toggleReaction(currentReactMsgId, emoji);
        }

        async function toggleReaction(msgId, emoji) {
            // Optimistic UI Update for instant feedback
            let msgEl = document.getElementById(`msg-${msgId}`);
            if (msgEl) {
                let rDiv = msgEl.querySelector('.reactions');
                if (!rDiv) {
                    rDiv = document.createElement('div');
                    rDiv.className = 'reactions';
                    msgEl.querySelector('.bubble').insertBefore(rDiv, msgEl.querySelector('.msg-meta'));
                }
                
                let existing = Array.from(rDiv.children).find(el => el.innerText.includes(emoji));
                if (existing) {
                    let parts = existing.innerText.split(' ');
                    let count = parseInt(parts[1]) || 1;
                    if (existing.classList.contains('active')) {
                        count--;
                        if (count <= 0) existing.remove();
                        else { existing.innerText = `${emoji} ${count}`; existing.classList.remove('active'); }
                    } else {
                        existing.innerText = `${emoji} ${count + 1}`;
                        existing.classList.add('active');
                    }
                } else {
                    let newR = document.createElement('div');
                    newR.className = 'reaction active';
                    newR.innerText = `${emoji} 1`;
                    newR.onclick = () => toggleReaction(msgId, emoji);
                    rDiv.appendChild(newR);
                }
            }

            try {
                const fd = new FormData();
                fd.append('action', 'toggle_reaction');
                fd.append('message_id', msgId);
                fd.append('emoji', emoji);
                fetch(CFG.api, { method: 'POST', body: fd }); // Background save
            } catch(e){}
        }

        function toggleSpeed(btn, audId) {
            const aud = document.getElementById(audId);
            const speeds = [1, 1.5, 2];
            let cur = speeds.indexOf(aud.playbackRate);
            let next = speeds[(cur + 1) % speeds.length];
            aud.playbackRate = next;
            btn.innerText = next + 'x';
        }

        async function toggleRecording() {
            if (mediaRecorder && mediaRecorder.state === "recording") {
                stopRecording();
            } else {
                startRecording();
            }
        }

        async function startRecording() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                mediaRecorder = new MediaRecorder(stream);
                audioChunks = [];
                mediaRecorder.ondataavailable = e => audioChunks.push(e.data);
                mediaRecorder.onstop = () => {
                    const blob = new Blob(audioChunks, { type: 'audio/webm' });
                    selectedFile = new File([blob], "voice.webm", { type: 'audio/webm' });
                    const pa = document.getElementById('previewPlayer');
                    pa.src = URL.createObjectURL(blob);
                    pa.load();
                    
                    document.getElementById('recordingUI').style.display = 'none';
                    document.getElementById('audioPreviewUI').style.display = 'flex';
                    toggleSendMic(true);
                };
                mediaRecorder.start();
                
                document.getElementById('textInputBox').style.display = 'none';
                document.getElementById('emojiBtnToggle').style.display = 'none';
                document.getElementById('attachBtnToggle').style.display = 'none';
                document.getElementById('recordingUI').style.display = 'flex';
                document.getElementById('micBtn').style.display = 'none';
                document.getElementById('sendBtn').style.display = 'none';
                
                let sec = 0;
                document.getElementById('recTime').innerText = "0:00";
                recInterval = setInterval(() => {
                    sec++;
                    document.getElementById('recTime').innerText = Math.floor(sec/60) + ":" + (sec%60).toString().padStart(2,'0');
                }, 1000);
            } catch (e) { alert("Microphone access denied."); }
        }

        function stopRecording() {
            if (mediaRecorder && mediaRecorder.state === "recording") {
                mediaRecorder.stop();
                clearInterval(recInterval);
            }
        }

        function cancelAudio() {
            selectedFile = null;
            document.getElementById('audioPreviewUI').style.display = 'none';
            document.getElementById('recordingUI').style.display = 'none';
            document.getElementById('textInputBox').style.display = 'flex';
            document.getElementById('emojiBtnToggle').style.display = 'flex';
            document.getElementById('attachBtnToggle').style.display = 'flex';
            document.getElementById('micBtn').style.display = 'flex';
            document.getElementById('sendBtn').style.display = 'flex';
            
            const pa = document.getElementById('previewPlayer');
            pa.pause(); pa.src = '';
            document.getElementById('prevIcon').className = 'ti ti-player-play-filled';
            document.getElementById('prevSlider').value = 0;
            document.getElementById('prevTime').innerText = "0:00";
            
            toggleSendMic(document.getElementById('msgInput').value.trim().length > 0);
        }

        // Custom Audio Logic
        function toggleAud(audId, iconId) {
            const aud = document.getElementById(audId);
            const icon = document.getElementById(iconId);
            if (aud.paused) {
                document.querySelectorAll('audio').forEach(a => { if(a.id !== audId) a.pause(); });
                document.querySelectorAll('.ca-play i').forEach(i => i.className = 'ti ti-player-play-filled');
                aud.play(); icon.className = 'ti ti-player-pause-filled';
            } else {
                aud.pause(); icon.className = 'ti ti-player-play-filled';
            }
        }
        function updateAud(audId, sliderId, timeId) {
            const aud = document.getElementById(audId);
            if(aud.duration && aud.duration !== Infinity) {
                document.getElementById(sliderId).value = (aud.currentTime / aud.duration) * 100;
                document.getElementById(timeId).innerText = formatTime(aud.currentTime);
            } else {
                document.getElementById(timeId).innerText = formatTime(aud.currentTime);
            }
        }
        function initAud(audId, timeId) {
            const aud = document.getElementById(audId);
            if(aud.duration && aud.duration !== Infinity) {
                document.getElementById(timeId).innerText = formatTime(aud.duration);
            }
        }
        function resetAud(iconId, sliderId, timeId, audId) {
            document.getElementById(iconId).className = 'ti ti-player-play-filled';
            document.getElementById(sliderId).value = 0;
            initAud(audId, timeId);
        }
        function seekAud(audId, val) {
            const aud = document.getElementById(audId);
            if(aud.duration && aud.duration !== Infinity) {
                aud.currentTime = (val / 100) * aud.duration;
            }
        }
        function formatTime(s) {
            if(isNaN(s) || s === Infinity) return "0:00";
            return Math.floor(s/60) + ":" + Math.floor(s%60).toString().padStart(2,'0');
        }

        function handleFile(inp) { 
            if(inp.files[0]) { 
                selectedFile = inp.files[0]; 
                document.getElementById('fileName').innerText = selectedFile.name; 
                document.getElementById('filePreview').style.display = 'block'; 
            } 
        }
        function clearFile() { 
            selectedFile = null; 
            document.getElementById('fileInp').value = ''; 
            document.getElementById('filePreview').style.display = 'none'; 
            
            document.getElementById('audioPreviewUI').style.display = 'none';
            document.getElementById('recordingUI').style.display = 'none';
            document.getElementById('textInputBox').style.display = 'flex';
            document.getElementById('emojiBtnToggle').style.display = 'flex';
            document.getElementById('attachBtnToggle').style.display = 'flex';
            document.getElementById('micBtn').style.display = 'flex';
            document.getElementById('sendBtn').style.display = 'flex';
        }

        let currentMembers = [];
        async function updatePresence() {
            try {
                const fd = new FormData(); fd.append('action', 'update_presence'); fd.append('room_id', CFG.roomId);
                const r = await fetch(CFG.api, { method: 'POST', body: fd });
                const d = await r.json();
                if(d.success) {
                    currentMembers = d.members;
                    
                    // WhatsApp style names list in header
                    let namesList = currentMembers.map(m => m.id == CFG.userId ? 'Vous' : m.username).join(', ');
                    document.getElementById('memberCount').innerText = namesList;
                    
                    document.getElementById('sidebarMemberCount').innerText = currentMembers.length;
                    
                    let html = '';
                    currentMembers.forEach(m => {
                        let typing = m.is_typing == 1 ? '<div style="font-size:12px; color:var(--accent); margin-top:2px;"><i class="ti ti-pencil"></i> en train d\'écrire...</div>' : '';
                        let isMe = m.id == CFG.userId ? ' <span style="font-size:12px; color:var(--text-muted); font-weight:normal;">(Vous)</span>' : '';
                        let initials = m.username.substring(0,2).toUpperCase();
                        
                        html += `<div style="display:flex; align-items:center; gap:12px; padding:12px 0; cursor:pointer; border-bottom:1px solid var(--border);">
                                    <div style="width:40px; height:40px; min-width:40px; background:var(--sidebar); border:1px solid var(--border); border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:bold; color:var(--text-main); position:relative;">
                                        ${initials}
                                        <div style="position:absolute; bottom:0; right:0; width:12px; height:12px; background:#22c55e; border-radius:50%; border:2px solid var(--bg-card);"></div>
                                    </div>
                                    <div style="flex:1; overflow:hidden;">
                                        <div style="font-weight:600; font-size:15px; color:var(--text-main); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${esc(m.username)}${isMe}</div>
                                        ${typing}
                                    </div>
                                 </div>`;
                    });
                    document.getElementById('sidebarMembersList').innerHTML = html;
                }
            } catch (e) {}
        }
        
        function toggleGroupInfo() {
            const sb = document.getElementById('infoSidebar');
            if(sb.classList.contains('hidden')) {
                sb.classList.remove('hidden');
                // Remove hidden class but style gives it width
            } else {
                sb.classList.add('hidden');
            }
        }
        
        function openImageViewer(src) {
            document.getElementById('viewerImage').src = src;
            document.getElementById('imageViewer').classList.remove('hidden-strict');
        }
        function closeImageViewer() {
            document.getElementById('imageViewer').classList.add('hidden-strict');
            document.getElementById('viewerImage').src = '';
        }
        
        // JITSI CALLS
        let jitsiApi = null;
        function startJitsiCall(type) {
            document.getElementById('callViewer').classList.remove('hidden-strict');
            document.getElementById('jitsiContainer').innerHTML = '';
            
            const domain = 'meet.jit.si';
            const options = {
                roomName: 'LinguaFlow_Room_' + CFG.roomId + '_V1',
                width: '100%',
                height: '100%',
                parentNode: document.getElementById('jitsiContainer'),
                userInfo: {
                    displayName: '<?= addslashes($username) ?>'
                },
                configOverwrite: {
                    startWithAudioMuted: false,
                    startWithVideoMuted: type === 'voice',
                    prejoinPageEnabled: false
                },
                interfaceConfigOverwrite: {
                    TOOLBAR_BUTTONS: [
                        'microphone', 'camera', 'desktop', 'fullscreen',
                        'fodeviceselection', 'hangup', 'profile', 'chat',
                        'settings', 'raisehand', 'videoquality', 'filmstrip', 'tileview'
                    ]
                }
            };
            jitsiApi = new JitsiMeetExternalAPI(domain, options);
            
            jitsiApi.addEventListener('videoConferenceLeft', () => {
                endCall();
            });
            
            // Broadcast the call start
            const fd = new FormData();
            fd.append('action', 'send_message');
            fd.append('room_id', CFG.roomId);
            fd.append('content', `📞 J'ai lancé un appel ${type === 'voice' ? 'vocal' : 'vidéo'}. Cliquez sur l'icône en haut pour rejoindre !`);
            fetch(CFG.api, { method: 'POST', body: fd }).then(() => loadMessages());
        }

        function endCall() {
            if (jitsiApi) {
                jitsiApi.dispose();
                jitsiApi = null;
            }
            document.getElementById('callViewer').classList.add('hidden-strict');
        }

        function esc(t) { const d = document.createElement('div'); d.textContent = t; return d.innerHTML; }
    </script>
</body>
</html>
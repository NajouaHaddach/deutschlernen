<?php
// chatbot.php
require_once '../config.php';
require_once '../functions.php';

if (!isLoggedIn()) { header('Location: ../auth/login.php'); exit; }
$user_name = $_SESSION['username'];
$level = $_SESSION['niveau_actuel'] ?? 'A1';
?>
<!DOCTYPE html>
<html lang="fr" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Assistant IA – LinguaFlow</title>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
  <style>
    :root { 
        --bg-base: #f8fafc; --bg-surface: #ffffff; --accent: #0d9488; 
        --text-main: #1e293b; --text-muted: #64748b; --border: #e2e8f0; 
    }
    [data-theme="dark"] { --bg-base: #061820; --bg-surface: #0c2530; --text-main: #e0faf6; --border: rgba(45,212,191,0.1); }
    
    * { box-sizing: border-box; margin: 0; padding: 0; }
    html, body { height: 100%; }
    body { font-family: 'Sora', sans-serif; background: var(--bg-base); color: var(--text-main); display: flex; height: 100vh; overflow: hidden; margin: 0; }
    
    .sidebar { width: 260px; background: #0A2030; color: #fff; padding: 24px 16px; display: flex; flex-direction: column; flex-shrink: 0; z-index: 10; }
    .nav-item { padding: 12px 16px; color: #7AACB8; text-decoration: none; border-radius: 10px; margin-bottom: 4px; display: flex; align-items: center; gap: 12px; font-size: 14px; font-weight: 500; transition: 0.2s; }
    .nav-item.active { background: rgba(45,212,191,0.2); color: #fff; }
    .nav-item:hover:not(.active) { background: rgba(255,255,255,0.05); color: #fff; }
    
    .main { flex: 1; display: flex; flex-direction: column; min-width: 0; }
    .header { background: var(--bg-surface); padding: 16px 32px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; }    
    .chat-container { flex: 1; overflow-y: auto; padding: 24px; display: flex; flex-direction: column; gap: 16px; scroll-behavior: smooth; }
    
    .message { max-width: 85%; padding: 12px 16px; border-radius: 18px; font-size: 14px; line-height: 1.5; position: relative; display: flex; align-items: flex-start; gap: 10px; white-space: pre-wrap; word-wrap: break-word; }
    .message.user { align-self: flex-end; background: var(--accent); color: white; border-bottom-right-radius: 4px; }
    .message.bot { align-self: flex-start; background: var(--bg-surface); border: 1px solid var(--border); border-bottom-left-radius: 4px; color: var(--text-main); }
    
    .footer { background: var(--bg-surface); padding: 16px 24px; border-top: 1px solid var(--border); display: flex; gap: 12px; align-items: center; flex-shrink: 0; }
    input { flex: 1; padding: 14px 24px; border: 1px solid var(--border); border-radius: 14px; outline: none; font-family: inherit; font-size: 14px; background: #f1f5f9; transition: 0.2s; }
    input:focus { border-color: var(--accent); background: #fff; }
    
    .send-btn { background: var(--accent); color: white; border: none; width: 48px; height: 48px; border-radius: 14px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.2s; }
    .send-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(13,148,136,0.3); }
    .send-btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

    .clear-btn { background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 13px; font-weight: 700; display: flex; align-items: center; gap: 5px; transition: 0.2s; }
    .clear-btn:hover { color: #ef4444; }
    
    .play-audio-btn { background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 16px; transition: 0.2s; margin-top: 2px; flex-shrink: 0; }
    .play-audio-btn:hover { color: var(--accent); }
    
    .icon-btn { color: var(--text-muted); font-size: 22px; cursor: pointer; border: none; background: none; padding: 8px; border-radius: 50%; }
    .icon-btn.active { color: #ef4444 !important; animation: pulse 1.5s infinite; }
    @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.1); } 100% { transform: scale(1); } }
    
    .status { font-size: 12px; color: var(--accent); font-weight: 600; padding: 0 24px 12px; display: none; }
  </style>
</head>
<body>
  <aside class="sidebar">
    <div style="font-weight:800; margin-bottom:32px; font-size:22px; display:flex; align-items:center; gap:10px;">
      <div style="background:var(--accent); padding:6px; border-radius:8px;"><i class="ti ti-language" style="color:#fff;"></i></div> LinguaFlow
    </div>
    <nav>
      <a href="../dashboard.php" class="nav-item"><i class="ti ti-layout-dashboard"></i> Tableau de bord</a>
      <a href="index.php" class="nav-item active"><i class="ti ti-robot"></i> Assistant IA</a>
      <a href="../forum/index.php" class="nav-item"><i class="ti ti-messages"></i> Forum Communautaire</a>
    </nav>
  </aside>

  <main class="main">
    <header class="header">
      <div>
        <h1 style="font-size:18px; font-weight:800; letter-spacing:-0.5px;">🤖 DeutschBot</h1>
      </div>
      <div style="display:flex; align-items:center; gap:20px;">
        <button class="clear-btn" onclick="clearHistory()"><i class="ti ti-trash"></i> Effacer</button>
        <a href="../auth/logout.php" style="text-decoration:none; color:#ef4444; font-size:13px; font-weight:700;"><i class="ti ti-logout"></i> Déconnexion</a>
      </div>
    </header>

    <div class="chat-container" id="chatMessages">
        <?php
        if(isset($_SESSION['chat_history_api']) && !empty($_SESSION['chat_history_api'])){
          foreach($_SESSION['chat_history_api'] as $msg){
            $class = ($msg['role'] == 'user') ? 'user' : 'bot';
            echo "<div class='message $class'><span>".htmlspecialchars($msg['content'])."</span>";
            if ($class === 'bot') {
                echo "<button class='play-audio-btn' onclick='playBotMessage(this.parentElement.querySelector(\"span\").innerText)'><i class='ti ti-volume'></i></button>";
            }
            echo "</div>";
          }
        } else {
          echo "<div class='message bot'><span>Hallo $user_name! 👋 Je suis ton tuteur d'allemand personnel. Prêt pour une leçon ?</span> <button class='play-audio-btn' onclick='playBotMessage(this.parentElement.querySelector(\"span\").innerText)'><i class='ti ti-volume'></i></button></div>";
        }
        ?>
    </div>

    <div id="status" class="status"><i class="ti ti-dots"></i> DeutschBot réfléchit...</div>

    <div class="footer">
        <button id="micBtn" class="icon-btn" title="Parler (Allemand)"><i class="ti ti-microphone"></i></button>
        <input type="text" id="userInput" placeholder="Écris ton message ici..." autocomplete="off">
        <button id="sendBtn" class="send-btn"><i class="ti ti-send"></i></button>
    </div>
  </main>

<script>
const chatDiv = document.getElementById('chatMessages');
const input = document.getElementById('userInput');
const sendBtn = document.getElementById('sendBtn');
const micBtn = document.getElementById('micBtn');
const statusDiv = document.getElementById('status');

// Fonction pour ajouter un message à l'interface
function addMessage(role, text) {
  const msgDiv = document.createElement('div');
  msgDiv.className = `message ${role}`;
  
  const textSpan = document.createElement('span');
  textSpan.textContent = text;
  msgDiv.appendChild(textSpan);

  if (role === 'bot') {
    const playButton = document.createElement('button');
    playButton.className = 'play-audio-btn';
    playButton.innerHTML = '<i class="ti ti-volume"></i>';
    playButton.onclick = () => playBotMessage(text);
    msgDiv.appendChild(playButton);
  }
  chatDiv.appendChild(msgDiv);
  chatDiv.scrollTop = chatDiv.scrollHeight;
}

// Fonction pour envoyer un message à l'API
async function sendMessage() {
  const userMsg = input.value.trim();
  if(!userMsg || sendBtn.disabled) return;

  addMessage('user', userMsg);
  input.value = '';
  sendBtn.disabled = true;
  statusDiv.style.display = 'block';

  try {
    const response = await fetch('chat_api.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({message: userMsg})
    });
    const data = await response.json();
    if(data.reply) {
      addMessage('bot', data.reply);
    } else {
      addMessage('bot', data.error || "Erreur de réponse.");
    }
  } catch(err) {
    addMessage('bot', "Erreur de connexion au serveur.");
  } finally {
    statusDiv.style.display = 'none';
    sendBtn.disabled = false;
    input.focus();
  }
}

// Fonction pour effacer l'historique
async function clearHistory() {
  if(!confirm("Voulez-vous vraiment effacer l'historique de cette conversation ?")) return;
  
  try {
    const response = await fetch('chat_api.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({action: 'clear_history'})
    });
    const data = await response.json();
    if(data.success) {
      chatDiv.innerHTML = `<div class='message bot'><span>Hallo <?= htmlspecialchars($user_name) ?>! 👋 Historique effacé. Prêt pour une nouvelle leçon ?</span> <button class='play-audio-btn' onclick='playBotMessage(this.parentElement.querySelector("span").innerText)'><i class='ti ti-volume'></i></button></div>`;
    }
  } catch(err) {
    console.error("Erreur lors de l'effacement de l'historique");
  }
}

// Fonction Text-to-Speech (TTS)
let germanVoice = null;
if ('speechSynthesis' in window) {
    speechSynthesis.onvoiceschanged = () => {
        const voices = speechSynthesis.getVoices();
        germanVoice = voices.find(voice => voice.lang === 'de-DE' || voice.lang.startsWith('de-'));
        if (!germanVoice) {
            console.warn("Aucune voix allemande trouvée pour la synthèse vocale, utilisation de la voix par défaut.");
        }
    };
    if (speechSynthesis.getVoices().length > 0) {
        speechSynthesis.onvoiceschanged();
    }
}

function playBotMessage(text) {
    if ('speechSynthesis' in window) {
        speechSynthesis.cancel();
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'de-DE';
        utterance.rate = 0.9;
        if (germanVoice) utterance.voice = germanVoice;
        speechSynthesis.speak(utterance);
    } else {
        alert("Votre navigateur ne supporte pas la synthèse vocale (Text-to-Speech).");
    }
}

// --- Reconnaissance Vocale (Speech-to-Text) ---
let recognition;
let isListening = false;

if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    recognition = new SpeechRecognition();
    recognition.lang = 'de-DE';
    recognition.interimResults = false;

    recognition.onstart = () => {
        isListening = true;
        micBtn.classList.add('active');
        input.placeholder = "J'écoute (Allemand)...";
        console.log("Reconnaissance vocale démarrée...");
    };

    recognition.onresult = (event) => {
        const transcript = event.results[0][0].transcript;
        console.log("Résultat reçu :", transcript);
        input.value = transcript;
        sendMessage();
    };

    recognition.onerror = (e) => {
        console.error("Speech recognition error:", e.error);
        stopMic(); // Always stop mic on error
        
        let errorMessage = "Une erreur inattendue est survenue avec la reconnaissance vocale.";
        if (e.error === 'not-allowed') {
            alert("Accès au microphone refusé. Veuillez vérifier les permissions de votre navigateur.");
        } else if (e.error === 'network') {
            alert("Erreur réseau : La reconnaissance vocale nécessite une connexion internet.");
        }
    };

    recognition.onnomatch = () => stopMic();
    recognition.onend = () => stopMic();
} else {
    micBtn.style.display = 'none';
}

function stopMic() {
    isListening = false;
    micBtn.classList.remove('active');
    input.placeholder = "Écris ton message ici...";
}

micBtn.addEventListener('click', () => {
    if (!recognition) return alert("Votre navigateur ne supporte pas la reconnaissance vocale.");
    if (isListening) {
        recognition.stop();
    } else {
        try {
            recognition.start();
        } catch (err) {
            console.error("Erreur au démarrage du micro :", err);
        }
    }
});

sendBtn.addEventListener('click', sendMessage);
input.addEventListener('keypress', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
});
</script>
</body>
</html>

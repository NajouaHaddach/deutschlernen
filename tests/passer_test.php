<?php
require_once __DIR__ . '/../config/database.php';
redirectIfNotLoggedIn();

$userId = $_SESSION['user_id'];
$niveaux = ['A1','A2','B1','B2','C1','C2'];
$niv = strtoupper(trim($_GET['niveau'] ?? 'A1'));

if (!in_array($niv, $niveaux)) { header('Location: tests.php'); exit; }

// Vérifier que l'utilisateur a ce niveau débloqué
$stmt = $pdo->prepare("SELECT id FROM niveaux_debloques WHERE user_id=? AND niveau=?");
$stmt->execute([$userId, $niv]);
if (!$stmt->fetch()) {
    header('Location: tests.php?error=locked'); exit;
}

// Récupérer le test
$stmt = $pdo->prepare("SELECT * FROM niveau_tests WHERE niveau=?");
$stmt->execute([$niv]);
$test = $stmt->fetch();
if (!$test) { header('Location: tests.php?error=notest'); exit; }

// Récupérer les questions
$stmt = $pdo->prepare("SELECT * FROM questions_test WHERE test_id=? ORDER BY ordre ASC");
$stmt->execute([$test['id']]);
$questions = $stmt->fetchAll();
if (!$questions) { header('Location: tests.php?error=noquestions'); exit; }

$totalQ = count($questions);

// ---------- Traitement du formulaire soumis ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_test'])) {
    $score = 0;
    $reponses = $_POST['reponse'] ?? [];

    foreach ($questions as $q) {
        $rep = $reponses[$q['id']] ?? '';
        if (strtolower($rep) === strtolower($q['bonne_reponse'])) {
            $score++;
        }
    }
    $pourcentage = round(($score / $totalQ) * 100, 2);
    $reussi = $pourcentage >= 70 ? 1 : 0;

    // Enregistrer le résultat
    $stmt = $pdo->prepare("INSERT INTO resultats_tests (user_id, test_id, score, total, pourcentage, reussi) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$userId, $test['id'], $score, $totalQ, $pourcentage, $reussi]);
    $resultatId = $pdo->lastInsertId();

    // Si réussi → débloquer le niveau suivant
    if ($reussi) {
        $idx = array_search($niv, $niveaux);
        if ($idx !== false && isset($niveaux[$idx + 1])) {
            $nextNiv = $niveaux[$idx + 1];
            $pdo->prepare("INSERT IGNORE INTO niveaux_debloques (user_id, niveau) VALUES (?,?)")->execute([$userId, $nextNiv]);
            $pdo->prepare("UPDATE users SET niveau_actuel=? WHERE id=?")->execute([$nextNiv, $userId]);
        }
    }

    header("Location: resultat.php?id=$resultatId");
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Test <?= $niv ?> — DeutschLernen</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root {
    --bg:#0d0d1a; --card:#16163a; --card2:#1e1e45;
    --violet:#7c3aed; --accent:#a855f7; --violet3:#c084fc;
    --green:#22c55e; --red:#ef4444; --gold:#f59e0b;
    --text:#e2e8f0; --muted:#94a3b8;
    --border:rgba(124,58,237,0.3); --glow:0 0 25px rgba(124,58,237,0.5);
}
*{margin:0;padding:0;box-sizing:border-box;}
body{
    font-family:'Inter',sans-serif; background:var(--bg); color:var(--text);
    min-height:100vh; display:flex; flex-direction:column; align-items:center;
    justify-content:center; padding:2rem 1rem;
    background-image:
        radial-gradient(ellipse at 20% 10%, rgba(124,58,237,0.2) 0%, transparent 50%),
        radial-gradient(ellipse at 80% 90%, rgba(168,85,247,0.12) 0%, transparent 50%);
}
/* TOP BAR */
.top-bar{
    position:fixed; top:0; left:0; right:0; z-index:100;
    background:rgba(13,13,26,0.95); backdrop-filter:blur(20px);
    border-bottom:1px solid var(--border);
    padding:0 2rem; height:65px;
    display:flex; align-items:center; justify-content:space-between;
}
.top-brand{font-size:1.1rem;font-weight:800;
    background:linear-gradient(135deg,var(--violet3),var(--accent));
    -webkit-background-clip:text;-webkit-text-fill-color:transparent;}
.top-info{display:flex;align-items:center;gap:1.5rem;}
/* TIMER */
.timer-wrap{display:flex;align-items:center;gap:8px;
    background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);
    padding:6px 16px;border-radius:50px;}
.timer-wrap i{color:var(--red);}
#timer{font-size:1.1rem;font-weight:800;color:var(--red);font-variant-numeric:tabular-nums;min-width:55px;}
.timer-warn{animation:timerPulse 0.8s infinite;}
@keyframes timerPulse{0%,100%{opacity:1;}50%{opacity:0.4;}}
/* Q COUNTER */
.q-counter{font-size:0.9rem;color:var(--muted);font-weight:600;}
.q-counter span{color:var(--violet3);font-weight:800;}

/* WRAPPER */
.quiz-wrapper{width:100%;max-width:740px;margin-top:80px;}

/* TOP PROGRESS */
.quiz-progress{margin-bottom:2rem;}
.quiz-prog-label{display:flex;justify-content:space-between;
    font-size:0.82rem;color:var(--muted);margin-bottom:8px;font-weight:500;}
.quiz-prog-bar{height:8px;background:rgba(255,255,255,0.07);border-radius:50px;overflow:hidden;}
.quiz-prog-fill{height:100%;border-radius:50px;
    background:linear-gradient(90deg,var(--violet),var(--accent));
    box-shadow:0 0 12px rgba(168,85,247,0.5);
    transition:width 0.4s cubic-bezier(.4,0,.2,1);}
.dots-row{display:flex;gap:6px;margin-top:10px;flex-wrap:wrap;}
.dot{width:10px;height:10px;border-radius:50%;
    background:rgba(255,255,255,0.1);
    transition:all 0.3s ease;}
.dot.answered{background:var(--accent);box-shadow:0 0 6px rgba(168,85,247,0.6);}
.dot.current{background:#fff;box-shadow:0 0 8px rgba(255,255,255,0.5);transform:scale(1.3);}

/* QUESTION CARD */
.question-card{
    background:var(--card); border:1px solid var(--border);
    border-radius:24px; padding:2.5rem;
    box-shadow:0 16px 48px rgba(0,0,0,0.4), var(--glow);
    position:relative; overflow:hidden;
    transition:transform 0.3s ease, opacity 0.3s ease;
}
.question-card::before{
    content:'';position:absolute;top:-80px;right:-80px;
    width:200px;height:200px;border-radius:50%;
    background:radial-gradient(circle,rgba(124,58,237,0.12),transparent);
    pointer-events:none;
}
.question-card.slide-out-left{transform:translateX(-60px);opacity:0;}
.question-card.slide-out-right{transform:translateX(60px);opacity:0;}
.question-card.slide-in{animation:slideIn 0.35s ease;}
@keyframes slideIn{from{transform:translateX(40px);opacity:0;}to{transform:translateX(0);opacity:1;}}

.q-num-badge{
    display:inline-flex;align-items:center;gap:8px;
    background:rgba(124,58,237,0.15);border:1px solid var(--border);
    padding:5px 14px;border-radius:50px;
    font-size:0.8rem;font-weight:700;color:var(--violet3);
    margin-bottom:1.2rem;
}
.q-text{font-size:1.15rem;font-weight:700;line-height:1.55;margin-bottom:1.8rem;}

/* OPTIONS */
.options-grid{display:flex;flex-direction:column;gap:0.85rem;}
.option-label{
    display:flex;align-items:center;gap:1rem;
    background:rgba(255,255,255,0.04);
    border:1.5px solid rgba(255,255,255,0.08);
    border-radius:14px;padding:1rem 1.2rem;
    cursor:pointer;transition:all 0.2s ease;
    position:relative;
}
.option-label:hover{background:rgba(124,58,237,0.12);border-color:var(--violet);}
.option-label:has(input:checked){
    background:rgba(124,58,237,0.2);
    border-color:var(--accent);
    box-shadow:0 0 16px rgba(168,85,247,0.25);
}
.option-letter{
    width:36px;height:36px;border-radius:10px;flex-shrink:0;
    background:rgba(255,255,255,0.07);
    display:flex;align-items:center;justify-content:center;
    font-weight:800;font-size:0.9rem;
    transition:all 0.2s;
}
.option-label:has(input:checked) .option-letter{
    background:linear-gradient(135deg,var(--violet),var(--accent));
    color:#fff;box-shadow:0 4px 12px rgba(168,85,247,0.4);
}
.option-text{flex:1;font-size:0.95rem;font-weight:500;line-height:1.4;}
input[type=radio]{position:absolute;opacity:0;width:0;height:0;}

/* NAV BTNS */
.nav-btns{display:flex;justify-content:space-between;align-items:center;margin-top:2rem;gap:1rem;}
.btn{
    display:inline-flex;align-items:center;gap:8px;
    padding:11px 22px;border-radius:13px;
    font-size:0.9rem;font-weight:700;cursor:pointer;
    border:none;font-family:'Inter',sans-serif;transition:all 0.2s;
}
.btn-primary{background:linear-gradient(135deg,var(--violet),var(--accent));color:#fff;
    box-shadow:0 4px 16px rgba(124,58,237,0.4);}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 6px 22px rgba(124,58,237,0.6);}
.btn-ghost{background:rgba(255,255,255,0.06);color:var(--muted);
    border:1px solid rgba(255,255,255,0.1);}
.btn-ghost:hover{background:rgba(255,255,255,0.1);color:var(--text);}
.btn-danger{background:linear-gradient(135deg,#dc2626,var(--red));color:#fff;
    box-shadow:0 4px 16px rgba(239,68,68,0.35);}
.btn-danger:hover{transform:translateY(-2px);}

/* SKIP overlay */
.skip-notice{font-size:0.8rem;color:var(--muted);text-align:center;margin-top:0.8rem;}
.answered-count{
    background:rgba(34,197,94,0.12);border:1px solid rgba(34,197,94,0.3);
    border-radius:50px;padding:5px 14px;font-size:0.82rem;
    color:var(--green);font-weight:700;display:flex;align-items:center;gap:6px;
}

/* SUBMIT CARD */
.submit-card{
    background:var(--card);border:1px solid rgba(34,197,94,0.3);
    border-radius:24px;padding:2.5rem;text-align:center;
    box-shadow:0 0 30px rgba(34,197,94,0.1);
    display:none;
}
.submit-icon{font-size:3.5rem;margin-bottom:1rem;
    background:linear-gradient(135deg,var(--violet),var(--accent));
    -webkit-background-clip:text;-webkit-text-fill-color:transparent;}
.submit-card h2{font-size:1.4rem;font-weight:800;margin-bottom:0.5rem;}
.submit-card p{color:var(--muted);margin-bottom:1.5rem;line-height:1.5;}
.submit-answers-summary{
    display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;margin-bottom:2rem;
}
.sum-item{background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);
    border-radius:12px;padding:0.8rem 1.4rem;text-align:center;}
.sum-item .sv{font-size:1.6rem;font-weight:800;}
.sum-item .sl{font-size:0.75rem;color:var(--muted);margin-top:2px;}
.sum-unanswered{color:var(--gold);}

@media(max-width:600px){
    .question-card{padding:1.5rem;}
    .top-bar{padding:0 1rem;}
    .timer-wrap{display:none;}
}
</style>
</head>
<body>
<!-- TOP BAR -->
<div class="top-bar">
    <div class="top-brand"><i class="fa-solid fa-graduation-cap"></i> DeutschLernen</div>
    <div class="top-info">
        <div class="q-counter">Question <span id="qNum">1</span>/<span><?= $totalQ ?></span></div>
        <div class="timer-wrap">
            <i class="fa-solid fa-hourglass-half"></i>
            <span id="timer">10:00</span>
        </div>
    </div>
</div>

<div class="quiz-wrapper">
    <!-- PROGRESSION -->
    <div class="quiz-progress">
        <div class="quiz-prog-label">
            <span><i class="fa-solid fa-flag"></i> Test niveau <?= $niv ?> — <?= htmlspecialchars($test['titre']) ?></span>
            <span id="progLabel">0 / <?= $totalQ ?> répondu(s)</span>
        </div>
        <div class="quiz-prog-bar">
            <div class="quiz-prog-fill" id="progFill" style="width:0%"></div>
        </div>
        <div class="dots-row" id="dotsRow">
            <?php for($i=0;$i<$totalQ;$i++): ?>
            <div class="dot <?= $i===0 ? 'current' : '' ?>" id="dot-<?= $i ?>"></div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- FORMULAIRE UNIQUE -->
    <form method="POST" id="quizForm">
        <input type="hidden" name="submit_test" value="1">

        <?php foreach ($questions as $i => $q):
            $opts = ['a'=>$q['option_a'],'b'=>$q['option_b'],'c'=>$q['option_c'],'d'=>$q['option_d']];
        ?>
        <div class="question-card <?= $i > 0 ? 'hidden-q' : '' ?>"
             id="qcard-<?= $i ?>"
             style="<?= $i > 0 ? 'display:none;' : '' ?>">

            <div class="q-num-badge">
                <i class="fa-solid fa-circle-question"></i>
                Question <?= $i+1 ?> sur <?= $totalQ ?>
            </div>
            <div class="q-text"><?= htmlspecialchars($q['question']) ?></div>

            <div class="options-grid">
                <?php foreach ($opts as $letter => $text): ?>
                <label class="option-label">
                    <input type="radio" name="reponse[<?= $q['id'] ?>]"
                           value="<?= $letter ?>"
                           onchange="markAnswered(<?= $i ?>)">
                    <div class="option-letter"><?= strtoupper($letter) ?></div>
                    <div class="option-text"><?= htmlspecialchars($text) ?></div>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- SUBMIT CARD (affichée à la dernière étape) -->
        <div class="submit-card" id="submitCard">
            <div class="submit-icon"><i class="fa-solid fa-paper-plane"></i></div>
            <h2>Prêt à soumettre votre test ?</h2>
            <p>Vérifiez vos réponses avant de confirmer. Vous avez besoin de <strong style="color:var(--accent)">70%</strong> pour valider ce niveau.</p>
            <div class="submit-answers-summary">
                <div class="sum-item">
                    <div class="sv" style="color:var(--accent)" id="sumAnswered">0</div>
                    <div class="sl">Répondues</div>
                </div>
                <div class="sum-item">
                    <div class="sv sum-unanswered" id="sumUnanswered">0</div>
                    <div class="sl">Sans réponse</div>
                </div>
                <div class="sum-item">
                    <div class="sv" style="color:var(--violet3)"><?= $totalQ ?></div>
                    <div class="sl">Total</div>
                </div>
            </div>
            <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
                <button type="button" class="btn btn-ghost" onclick="goTo(currentQ)">
                    <i class="fa-solid fa-arrow-left"></i> Retour
                </button>
                <button type="submit" class="btn btn-primary" id="finalSubmit">
                    <i class="fa-solid fa-check-double"></i> Soumettre le test
                </button>
            </div>
        </div>

        <!-- NAVIGATION -->
        <div class="nav-btns" id="navBtns">
            <button type="button" class="btn btn-ghost" id="btnPrev" onclick="navigate(-1)" style="display:none;">
                <i class="fa-solid fa-arrow-left"></i> Précédent
            </button>
            <div class="answered-count" id="answeredCount">
                <i class="fa-solid fa-circle-check"></i>
                <span id="answeredNum">0</span> répondu(s)
            </div>
            <button type="button" class="btn btn-primary" id="btnNext" onclick="navigate(1)">
                Suivant <i class="fa-solid fa-arrow-right"></i>
            </button>
        </div>
        <div class="skip-notice" id="skipNotice">Vous pouvez passer une question et y revenir.</div>
    </form>
</div>

<script>
const TOTAL = <?= $totalQ ?>;
let currentQ = 0;
let answered = new Set();
let timerSecs = 10 * 60;
let timerEl = document.getElementById('timer');

// ---------- TIMER ----------
function updateTimer() {
    if (timerSecs <= 0) {
        document.getElementById('quizForm').submit();
        return;
    }
    timerSecs--;
    let m = Math.floor(timerSecs / 60);
    let s = timerSecs % 60;
    timerEl.textContent = m + ':' + s.toString().padStart(2,'0');
    if (timerSecs <= 60) timerEl.classList.add('timer-warn');
    else timerEl.classList.remove('timer-warn');
}
setInterval(updateTimer, 1000);

// ---------- NAVIGATION ----------
function goTo(idx) {
    // Hide all cards and submit
    document.querySelectorAll('.question-card').forEach(c => c.style.display='none');
    document.getElementById('submitCard').style.display = 'none';
    document.getElementById('navBtns').style.display = 'flex';
    document.getElementById('skipNotice').style.display = 'block';

    if (idx >= TOTAL) {
        // Show submit card
        updateSubmitSummary();
        document.getElementById('submitCard').style.display = 'block';
        document.getElementById('navBtns').style.display = 'none';
        document.getElementById('skipNotice').style.display = 'none';
        currentQ = TOTAL;
        updateUI();
        return;
    }

    currentQ = idx;
    const card = document.getElementById('qcard-' + idx);
    card.style.display = 'block';
    card.classList.add('slide-in');
    setTimeout(() => card.classList.remove('slide-in'), 400);
    updateUI();
}

function navigate(dir) {
    goTo(currentQ + dir);
}

function updateUI() {
    document.getElementById('qNum').textContent = Math.min(currentQ + 1, TOTAL);
    // Dots
    document.querySelectorAll('.dot').forEach((d,i) => {
        d.classList.remove('current');
        d.classList.toggle('answered', answered.has(i));
        if (i === currentQ) d.classList.add('current');
    });
    // Prev button
    document.getElementById('btnPrev').style.display = currentQ > 0 ? 'inline-flex' : 'none';
    // Next vs Submit label
    let btnNext = document.getElementById('btnNext');
    if (currentQ >= TOTAL - 1) {
        btnNext.innerHTML = '<i class="fa-solid fa-flag-checkered"></i> Terminer';
    } else {
        btnNext.innerHTML = 'Suivant <i class="fa-solid fa-arrow-right"></i>';
    }
    // Progress
    let pct = (answered.size / TOTAL) * 100;
    document.getElementById('progFill').style.width = pct + '%';
    document.getElementById('progLabel').textContent = answered.size + ' / ' + TOTAL + ' répondu(s)';
    document.getElementById('answeredNum').textContent = answered.size;
}

function markAnswered(idx) {
    answered.add(idx);
    updateUI();
}

function updateSubmitSummary() {
    document.getElementById('sumAnswered').textContent = answered.size;
    document.getElementById('sumUnanswered').textContent = TOTAL - answered.size;
}

// Init
updateUI();

// Prevent accidental navigation
window.onbeforeunload = function() {
    return "Votre test est en cours. Voulez-vous vraiment quitter ?";
};
document.getElementById('quizForm').addEventListener('submit', function() {
    window.onbeforeunload = null;
    document.getElementById('finalSubmit').disabled = true;
    document.getElementById('finalSubmit').innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Calcul en cours...';
});
</script>
</body>
</html>

<?php
require_once __DIR__ . '/../config/database.php';
redirectIfNotLoggedIn();

$userId = $_SESSION['user_id'];
$niveaux = ['A1','A2','B1','B2','C1','C2'];

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: tests.php'); exit; }

// Récupérer le résultat (appartient bien à cet user)
$stmt = $pdo->prepare("
    SELECT rt.*, nt.niveau, nt.titre, nt.description
    FROM resultats_tests rt
    JOIN niveau_tests nt ON nt.id = rt.test_id
    WHERE rt.id=? AND rt.user_id=?
");
$stmt->execute([$id, $userId]);
$res = $stmt->fetch();
if (!$res) { header('Location: tests.php'); exit; }

$niv = $res['niveau'];
$reussi = (bool)$res['reussi'];
$pct = round($res['pourcentage']);
$score = (int)$res['score'];
$total = (int)$res['total'];

// Niveau suivant
$idx = array_search($niv, $niveaux);
$nextNiv = ($idx !== false && isset($niveaux[$idx+1])) ? $niveaux[$idx+1] : null;

// Le niveau suivant est-il maintenant débloqué ?
$nextUnlocked = false;
if ($reussi && $nextNiv) {
    $stmt = $pdo->prepare("SELECT id FROM niveaux_debloques WHERE user_id=? AND niveau=?");
    $stmt->execute([$userId, $nextNiv]);
    $nextUnlocked = (bool)$stmt->fetch();
}

// Dernier niveau ?
$isFinal = ($niv === 'C2');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Résultat Test <?= $niv ?> — DeutschLernen</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{
    --bg:#0d0d1a;--card:#16163a;--card2:#1e1e45;
    --violet:#7c3aed;--accent:#a855f7;--violet3:#c084fc;
    --green:#22c55e;--red:#ef4444;--gold:#f59e0b;
    --text:#e2e8f0;--muted:#94a3b8;
    --border:rgba(124,58,237,0.3);
}
*{margin:0;padding:0;box-sizing:border-box;}
body{
    font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);
    min-height:100vh;display:flex;flex-direction:column;align-items:center;
    justify-content:center;padding:2rem 1rem;
    overflow-x:hidden;
    background-image:
        radial-gradient(ellipse at 20% 10%,rgba(124,58,237,0.2) 0%,transparent 50%),
        radial-gradient(ellipse at 80% 90%,rgba(168,85,247,0.12) 0%,transparent 50%);
}

/* CANVAS CONFETTI */
#confettiCanvas{position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:0;}

.result-container{
    width:100%;max-width:620px;position:relative;z-index:1;
    animation:popIn 0.6s cubic-bezier(.34,1.56,.64,1);
}
@keyframes popIn{from{opacity:0;transform:scale(0.8);}to{opacity:1;transform:scale(1);}}

/* RESULT CARD */
.result-card{
    background:var(--card);
    border:2px solid <?= $reussi ? 'rgba(34,197,94,0.5)' : 'rgba(239,68,68,0.4)' ?>;
    border-radius:28px;padding:3rem 2.5rem;text-align:center;
    box-shadow:0 0 60px <?= $reussi ? 'rgba(34,197,94,0.15)' : 'rgba(239,68,68,0.12)' ?>,
               0 24px 64px rgba(0,0,0,0.5);
    margin-bottom:1.5rem;
    position:relative;overflow:hidden;
}
.result-card::before{
    content:'';position:absolute;top:-100px;left:50%;transform:translateX(-50%);
    width:400px;height:400px;border-radius:50%;
    background:radial-gradient(circle,<?= $reussi ? 'rgba(34,197,94,0.08)' : 'rgba(239,68,68,0.06)' ?>,transparent 70%);
    pointer-events:none;
}
/* STATUS ICON */
.status-icon{
    width:100px;height:100px;border-radius:50%;margin:0 auto 1.5rem;
    display:flex;align-items:center;justify-content:center;
    font-size:2.8rem;
    background:<?= $reussi ? 'linear-gradient(135deg,#16a34a,#22c55e)' : 'linear-gradient(135deg,#dc2626,#ef4444)' ?>;
    box-shadow:0 0 40px <?= $reussi ? 'rgba(34,197,94,0.5)' : 'rgba(239,68,68,0.4)' ?>;
    animation:iconPop 0.8s 0.3s cubic-bezier(.34,1.56,.64,1) both;
}
@keyframes iconPop{from{transform:scale(0) rotate(-180deg);}to{transform:scale(1) rotate(0);}}

.status-title{
    font-size:clamp(1.6rem,4vw,2.2rem);font-weight:900;margin-bottom:0.4rem;
    color:<?= $reussi ? 'var(--green)' : 'var(--red)' ?>;
}
.status-sub{font-size:0.95rem;color:var(--muted);margin-bottom:2rem;line-height:1.5;}

/* SCORE RING */
.score-ring-wrap{margin:0 auto 2rem;width:180px;height:180px;position:relative;}
.score-ring-wrap svg{width:100%;height:100%;}
.ring-bg{stroke:rgba(255,255,255,0.06);}
.ring-fill{
    stroke:<?= $reussi ? '#22c55e' : '#ef4444' ?>;
    stroke-linecap:round;
    transform-origin:center;transform:rotate(-90deg);
    transition:stroke-dasharray 2s cubic-bezier(.4,0,.2,1);
    filter:drop-shadow(0 0 8px <?= $reussi ? 'rgba(34,197,94,0.7)' : 'rgba(239,68,68,0.6)' ?>);
}
.ring-center{
    position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);
    text-align:center;
}
.ring-pct{font-size:2.6rem;font-weight:900;color:<?= $reussi ? 'var(--green)' : 'var(--red)' ?>;line-height:1;}
.ring-label{font-size:0.78rem;color:var(--muted);margin-top:2px;}

/* STATS ROW */
.stats-row{display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;margin-bottom:2rem;}
.stat-box{
    background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);
    border-radius:14px;padding:1rem 1.5rem;min-width:110px;text-align:center;
}
.stat-box .sv{font-size:1.7rem;font-weight:800;}
.stat-box .sl{font-size:0.75rem;color:var(--muted);margin-top:3px;}
.sv-green{color:var(--green);}
.sv-red{color:var(--red);}
.sv-violet{color:var(--violet3);}

/* THRESHOLD BAR */
.threshold-bar-wrap{margin-bottom:1.5rem;}
.threshold-label{display:flex;justify-content:space-between;font-size:0.8rem;color:var(--muted);margin-bottom:6px;}
.threshold-bar{height:10px;background:rgba(255,255,255,0.06);border-radius:50px;position:relative;overflow:visible;}
.threshold-fill{
    height:100%;border-radius:50px;
    background:<?= $reussi ? 'linear-gradient(90deg,#16a34a,#22c55e)' : 'linear-gradient(90deg,#dc2626,#ef4444)' ?>;
    box-shadow:0 0 12px <?= $reussi ? 'rgba(34,197,94,0.5)' : 'rgba(239,68,68,0.4)' ?>;
    transition:width 1.5s cubic-bezier(.4,0,.2,1);
    width:0%;
}
.threshold-marker{
    position:absolute;top:-4px;left:70%;
    width:3px;height:18px;background:var(--gold);border-radius:2px;
    box-shadow:0 0 8px rgba(245,158,11,0.6);
}
.threshold-marker::after{
    content:'70%';position:absolute;top:-18px;left:50%;transform:translateX(-50%);
    font-size:0.7rem;font-weight:700;color:var(--gold);white-space:nowrap;
}

/* MESSAGE */
.result-message{
    background:<?= $reussi ? 'rgba(34,197,94,0.1)' : 'rgba(239,68,68,0.08)' ?>;
    border:1px solid <?= $reussi ? 'rgba(34,197,94,0.3)' : 'rgba(239,68,68,0.25)' ?>;
    border-radius:14px;padding:1rem 1.4rem;
    font-size:0.9rem;line-height:1.5;color:var(--text);
    margin-bottom:2rem;text-align:left;
}
.result-message strong{color:<?= $reussi ? 'var(--green)' : 'var(--red)' ?>;}

/* UNLOCK CARD */
.unlock-card{
    background:linear-gradient(135deg,rgba(124,58,237,0.2),rgba(168,85,247,0.15));
    border:2px solid rgba(168,85,247,0.5);
    border-radius:20px;padding:1.5rem 2rem;margin-bottom:1.5rem;
    display:flex;align-items:center;gap:1.2rem;
    box-shadow:0 0 30px rgba(124,58,237,0.25);
    animation:unlockReveal 0.8s 1s cubic-bezier(.34,1.56,.64,1) both;
}
@keyframes unlockReveal{
    from{opacity:0;transform:scale(0.7) rotate(-5deg);}
    to{opacity:1;transform:scale(1) rotate(0);}
}
.unlock-icon-wrap{
    width:64px;height:64px;border-radius:16px;flex-shrink:0;
    background:linear-gradient(135deg,var(--violet),var(--accent));
    display:flex;align-items:center;justify-content:center;
    font-size:1.8rem;box-shadow:0 0 20px rgba(168,85,247,0.5);
    animation:iconFloat 2s 1.5s ease-in-out infinite alternate;
}
@keyframes iconFloat{from{transform:translateY(0);}to{transform:translateY(-6px);}}
.unlock-text h3{font-size:1rem;font-weight:800;color:var(--violet3);margin-bottom:4px;}
.unlock-text p{font-size:0.82rem;color:var(--muted);line-height:1.4;}

/* FINAL BADGE (C2) */
.final-badge{
    background:linear-gradient(135deg,#b45309,var(--gold),#d97706);
    border-radius:20px;padding:1.5rem;text-align:center;margin-bottom:1.5rem;
    animation:unlockReveal 0.8s 1s both;
    box-shadow:0 0 40px rgba(245,158,11,0.3);
}
.final-badge .trophy{font-size:3rem;margin-bottom:0.5rem;}
.final-badge h3{font-size:1.2rem;font-weight:900;color:#fff;}
.final-badge p{font-size:0.85rem;color:rgba(255,255,255,0.8);margin-top:4px;}

/* ACTIONS */
.action-btns{display:flex;flex-direction:column;gap:0.8rem;}
.btn{
    display:inline-flex;align-items:center;justify-content:center;gap:10px;
    padding:14px 24px;border-radius:14px;
    font-size:1rem;font-weight:700;cursor:pointer;
    border:none;font-family:'Inter',sans-serif;transition:all 0.25s;
    text-decoration:none;width:100%;
}
.btn-primary{background:linear-gradient(135deg,var(--violet),var(--accent));color:#fff;
    box-shadow:0 4px 20px rgba(124,58,237,0.45);}
.btn-primary:hover{transform:translateY(-3px);box-shadow:0 8px 28px rgba(124,58,237,0.65);}
.btn-green{background:linear-gradient(135deg,#16a34a,#22c55e);color:#fff;
    box-shadow:0 4px 20px rgba(34,197,94,0.4);}
.btn-green:hover{transform:translateY(-3px);box-shadow:0 8px 28px rgba(34,197,94,0.6);}
.btn-ghost{background:rgba(255,255,255,0.06);color:var(--muted);
    border:1px solid rgba(255,255,255,0.1);}
.btn-ghost:hover{background:rgba(255,255,255,0.1);color:var(--text);}

.redirect-notice{
    text-align:center;font-size:0.8rem;color:var(--muted);margin-top:0.5rem;
    display:<?= ($reussi && $nextNiv) ? 'block' : 'none' ?>;
}
#countdown{color:var(--accent);font-weight:700;}
</style>
</head>
<body>
<canvas id="confettiCanvas"></canvas>

<div class="result-container">
    <!-- RESULT CARD -->
    <div class="result-card">
        <div class="status-icon">
            <i class="fa-solid <?= $reussi ? 'fa-trophy' : 'fa-xmark' ?>"></i>
        </div>

        <div class="status-title"><?= $reussi ? '🎉 Félicitations !' : '😔 Pas encore…' ?></div>
        <div class="status-sub">
            Test <strong style="color:var(--violet3)"><?= $niv ?></strong> — <?= htmlspecialchars($res['titre']) ?>
        </div>

        <!-- SCORE RING -->
        <div class="score-ring-wrap">
            <svg viewBox="0 0 180 180">
                <circle class="ring-bg" cx="90" cy="90" r="76" fill="none" stroke-width="12"/>
                <circle class="ring-fill" id="ringFill" cx="90" cy="90" r="76" fill="none"
                    stroke-width="12"
                    stroke-dasharray="0 477"
                    stroke-dashoffset="0"/>
            </svg>
            <div class="ring-center">
                <div class="ring-pct" id="pctNum">0%</div>
                <div class="ring-label"><?= $score ?>/<?= $total ?> correctes</div>
            </div>
        </div>

        <!-- STATS -->
        <div class="stats-row">
            <div class="stat-box">
                <div class="sv sv-green"><?= $score ?></div>
                <div class="sl">Bonnes réponses</div>
            </div>
            <div class="stat-box">
                <div class="sv sv-red"><?= $total - $score ?></div>
                <div class="sl">Mauvaises</div>
            </div>
            <div class="stat-box">
                <div class="sv sv-violet"><?= $pct ?>%</div>
                <div class="sl">Score final</div>
            </div>
        </div>

        <!-- THRESHOLD BAR -->
        <div class="threshold-bar-wrap">
            <div class="threshold-label">
                <span>Votre score</span>
                <span style="color:<?= $reussi ? 'var(--green)' : 'var(--red)' ?>;font-weight:700;">
                    <?= $reussi ? '✓ Seuil atteint' : '✗ Seuil non atteint' ?>
                </span>
            </div>
            <div class="threshold-bar">
                <div class="threshold-fill" id="threshBar"></div>
                <div class="threshold-marker"></div>
            </div>
        </div>

        <!-- MESSAGE -->
        <div class="result-message">
            <?php if ($reussi): ?>
                <strong><i class="fa-solid fa-circle-check"></i> Excellent !</strong>
                Vous avez obtenu <strong><?= $pct ?>%</strong> et validé le niveau <?= $niv ?>.
                <?php if ($nextNiv): ?>
                    Le forum et le test <strong><?= $nextNiv ?></strong> sont maintenant débloqués !
                <?php elseif ($isFinal): ?>
                    Vous avez atteint le niveau <strong>C2</strong> — la maîtrise complète de l'allemand !
                <?php endif; ?>
            <?php else: ?>
                <strong><i class="fa-solid fa-circle-info"></i> Continuez vos efforts !</strong>
                Vous avez obtenu <strong><?= $pct ?>%</strong>.
                Vous devez obtenir au moins <strong>70%</strong> pour débloquer le niveau suivant.
                Révisez et réessayez !
            <?php endif; ?>
        </div>

        <!-- ACTIONS -->
        <div class="action-btns">
            <?php if ($reussi && $nextNiv): ?>
                <a href="../forum/chat.php?niveau=<?= urlencode($nextNiv) ?>" class="btn btn-green" id="forumBtn">
                    <i class="fa-solid fa-comments"></i> Accéder au Forum <?= $nextNiv ?>
                </a>
                <a href="passer_test.php?niveau=<?= urlencode($nextNiv) ?>" class="btn btn-primary">
                    <i class="fa-solid fa-play"></i> Passer le test <?= $nextNiv ?>
                </a>
            <?php elseif ($isFinal && $reussi): ?>
                <a href="../forum/chat.php?niveau=C2" class="btn btn-green">
                    <i class="fa-solid fa-crown"></i> Forum C2 — Niveau Maître
                </a>
            <?php else: ?>
                <a href="passer_test.php?niveau=<?= urlencode($niv) ?>" class="btn btn-primary">
                    <i class="fa-solid fa-rotate-right"></i> Réessayer le test <?= $niv ?>
                </a>
            <?php endif; ?>
            <a href="../forum/chat.php?niveau=<?= urlencode($niv) ?>" class="btn btn-ghost">
                <i class="fa-solid fa-comments"></i> Forum <?= $niv ?>
            </a>
            <a href="tests.php" class="btn btn-ghost">
                <i class="fa-solid fa-list"></i> Retour aux tests
            </a>
        </div>

        <div class="redirect-notice">
            Redirection automatique vers le forum <?= $nextNiv ?> dans <span id="countdown">5</span>s…
        </div>
    </div>

    <!-- UNLOCK CARD -->
    <?php if ($reussi && $nextNiv): ?>
    <div class="unlock-card">
        <div class="unlock-icon-wrap"><i class="fa-solid fa-unlock-keyhole"></i></div>
        <div class="unlock-text">
            <h3><i class="fa-solid fa-star"></i> Niveau <?= $nextNiv ?> Débloqué !</h3>
            <p>Le forum et le test de niveau <strong><?= $nextNiv ?></strong> sont maintenant accessibles. Continuez votre apprentissage !</p>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($isFinal && $reussi): ?>
    <div class="final-badge">
        <div class="trophy">🏆</div>
        <h3>Maîtrise Complète Atteinte !</h3>
        <p>Vous avez validé tous les niveaux A1 → C2. Vous êtes un expert de l'allemand !</p>
    </div>
    <?php endif; ?>
</div>

<script>
// ---- ANIMATE RING ----
const circumference = 2 * Math.PI * 76; // ≈ 477.5
const pct = <?= $pct ?>;
const reussi = <?= $reussi ? 'true' : 'false' ?>;

setTimeout(() => {
    const fill = pct / 100 * circumference;
    document.getElementById('ringFill').style.strokeDasharray = fill + ' ' + circumference;

    // Count-up number
    let cur = 0;
    const target = pct;
    const step = Math.ceil(target / 60);
    const interval = setInterval(() => {
        cur = Math.min(cur + step, target);
        document.getElementById('pctNum').textContent = cur + '%';
        if (cur >= target) clearInterval(interval);
    }, 20);

    // Threshold bar
    document.getElementById('threshBar').style.width = Math.min(pct, 100) + '%';
}, 300);

// ---- CONFETTI (success only) ----
<?php if ($reussi): ?>
(function(){
    const canvas = document.getElementById('confettiCanvas');
    const ctx = canvas.getContext('2d');
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;

    const colors = ['#7c3aed','#a855f7','#c084fc','#22c55e','#f59e0b','#ec4899','#fff'];
    const particles = Array.from({length:120}, () => ({
        x: Math.random() * canvas.width,
        y: Math.random() * -canvas.height,
        vx: (Math.random() - 0.5) * 3,
        vy: Math.random() * 4 + 2,
        size: Math.random() * 8 + 4,
        color: colors[Math.floor(Math.random() * colors.length)],
        rotation: Math.random() * 360,
        rotVel: (Math.random() - 0.5) * 6,
        shape: Math.random() > 0.5 ? 'rect' : 'circle'
    }));

    let frame = 0;
    function animate() {
        if (frame > 300) { ctx.clearRect(0,0,canvas.width,canvas.height); return; }
        ctx.clearRect(0,0,canvas.width,canvas.height);
        particles.forEach(p => {
            p.x += p.vx; p.y += p.vy; p.rotation += p.rotVel;
            if (p.y > canvas.height) { p.y = -10; p.x = Math.random() * canvas.width; }
            ctx.save();
            ctx.translate(p.x, p.y);
            ctx.rotate(p.rotation * Math.PI / 180);
            ctx.fillStyle = p.color;
            ctx.globalAlpha = Math.max(0, 1 - frame/250);
            if (p.shape === 'rect') ctx.fillRect(-p.size/2, -p.size/4, p.size, p.size/2);
            else { ctx.beginPath(); ctx.arc(0,0,p.size/2,0,Math.PI*2); ctx.fill(); }
            ctx.restore();
        });
        frame++;
        requestAnimationFrame(animate);
    }
    setTimeout(animate, 600);
})();
<?php endif; ?>

// ---- SUCCESS SOUND (Web Audio API) ----
<?php if ($reussi): ?>
window.addEventListener('load', () => {
    try {
        const ac = new (window.AudioContext || window.webkitAudioContext)();
        const notes = [523.25, 659.25, 783.99, 1046.5];
        notes.forEach((freq, i) => {
            const osc = ac.createOscillator();
            const gain = ac.createGain();
            osc.connect(gain); gain.connect(ac.destination);
            osc.frequency.value = freq;
            osc.type = 'sine';
            gain.gain.setValueAtTime(0.15, ac.currentTime + i * 0.12);
            gain.gain.exponentialRampToValueAtTime(0.001, ac.currentTime + i * 0.12 + 0.4);
            osc.start(ac.currentTime + i * 0.12);
            osc.stop(ac.currentTime + i * 0.12 + 0.4);
        });
    } catch(e) {}
});
<?php endif; ?>

// ---- AUTO-REDIRECT (if success + next level) ----
<?php if ($reussi && $nextNiv): ?>
let secs = 5;
const cdEl = document.getElementById('countdown');
const cdInterval = setInterval(() => {
    secs--;
    cdEl.textContent = secs;
    if (secs <= 0) {
        clearInterval(cdInterval);
        window.location.href = '../forum/chat.php?niveau=<?= urlencode($nextNiv) ?>';
    }
}, 1000);
// Cancel redirect if user clicks any button
document.querySelectorAll('.btn').forEach(b => b.addEventListener('click', () => clearInterval(cdInterval)));
<?php endif; ?>
</script>
</body>
</html>

<?php
require_once __DIR__ . '/../config/database.php';
redirectIfNotLoggedIn();

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';

// Niveaux dans l'ordre
$niveaux = ['A1','A2','B1','B2','C1','C2'];
$suivant = ['A1'=>'A2','A2'=>'B1','B1'=>'B2','B2'=>'C1','C1'=>'C2','C2'=>null];

// S'assurer que A1 est débloqué pour tous
$pdo->prepare("INSERT IGNORE INTO niveaux_debloques (user_id, niveau) VALUES (?,?)")->execute([$userId,'A1']);

// Récupérer niveaux débloqués
$stmt = $pdo->prepare("SELECT niveau FROM niveaux_debloques WHERE user_id=?");
$stmt->execute([$userId]);
$debloques = array_column($stmt->fetchAll(), 'niveau');

// Récupérer meilleurs scores par niveau
$stmt = $pdo->prepare("
    SELECT nt.niveau, MAX(rt.pourcentage) as meilleur, MAX(rt.reussi) as reussi
    FROM resultats_tests rt
    JOIN niveau_tests nt ON nt.id = rt.test_id
    WHERE rt.user_id = ?
    GROUP BY nt.niveau
");
$stmt->execute([$userId]);
$scores = [];
foreach ($stmt->fetchAll() as $row) {
    $scores[$row['niveau']] = $row;
}

// Tests infos
$tests = $pdo->query("SELECT * FROM niveau_tests ORDER BY FIELD(niveau,'A1','A2','B1','B2','C1','C2')")->fetchAll();
$testsMap = array_column($tests, null, 'niveau');

// Historique récent
$stmt = $pdo->prepare("
    SELECT rt.*, nt.niveau, nt.titre
    FROM resultats_tests rt
    JOIN niveau_tests nt ON nt.id = rt.test_id
    WHERE rt.user_id = ?
    ORDER BY rt.date_passage DESC
    LIMIT 5
");
$stmt->execute([$userId]);
$historique = $stmt->fetchAll();

// Calcul progression globale
$totalNiveaux = count($niveaux);
$niveauxReussis = count(array_filter($scores, fn($s) => $s['reussi'] == 1));
$progressionPct = round(($niveauxReussis / $totalNiveaux) * 100);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tests de Niveau — DeutschLernen</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root {
    --bg: #0d0d1a;
    --bg2: #12122a;
    --card: #16163a;
    --card2: #1e1e45;
    --violet: #7c3aed;
    --violet2: #9d5bf0;
    --violet3: #c084fc;
    --accent: #a855f7;
    --green: #22c55e;
    --red: #ef4444;
    --gold: #f59e0b;
    --text: #e2e8f0;
    --muted: #94a3b8;
    --border: rgba(124,58,237,0.25);
    --glow: 0 0 20px rgba(124,58,237,0.4);
    --glow2: 0 0 40px rgba(124,58,237,0.2);
    --shadow: 0 8px 32px rgba(0,0,0,0.4);
}
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family: 'Inter', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    background-image:
        radial-gradient(ellipse at 20% 20%, rgba(124,58,237,0.15) 0%, transparent 60%),
        radial-gradient(ellipse at 80% 80%, rgba(168,85,247,0.1) 0%, transparent 60%);
}
/* NAV */
.navbar {
    background: rgba(18,18,42,0.95);
    backdrop-filter: blur(20px);
    border-bottom: 1px solid var(--border);
    padding: 0 2rem;
    height: 65px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky; top: 0; z-index: 100;
}
.nav-brand {
    display: flex; align-items: center; gap: 10px;
    font-size: 1.3rem; font-weight: 800;
    background: linear-gradient(135deg, var(--violet2), var(--violet3));
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    text-decoration: none;
}
.nav-links { display:flex; gap:1rem; align-items:center; }
.nav-link {
    color: var(--muted); text-decoration: none; font-size: 0.9rem;
    padding: 6px 14px; border-radius: 8px; transition: all 0.2s;
}
.nav-link:hover, .nav-link.active {
    color: var(--text); background: rgba(124,58,237,0.2);
}
.nav-user {
    display:flex; align-items:center; gap:10px;
    background: var(--card2); padding:6px 14px; border-radius:10px;
    font-size:0.9rem; font-weight:500; color: var(--violet3);
}
/* CONTAINER */
.container { max-width: 1200px; margin: 0 auto; padding: 2.5rem 1.5rem; }
/* HERO */
.hero {
    text-align: center; margin-bottom: 3rem;
    animation: fadeInDown 0.6s ease;
}
@keyframes fadeInDown {
    from { opacity:0; transform:translateY(-20px); }
    to { opacity:1; transform:translateY(0); }
}
.hero h1 {
    font-size: clamp(1.8rem, 4vw, 3rem); font-weight: 800; margin-bottom: 0.5rem;
    background: linear-gradient(135deg, #fff 0%, var(--violet3) 60%, var(--accent) 100%);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
}
.hero p { color: var(--muted); font-size: 1.05rem; max-width: 550px; margin: 0 auto 2rem; }
/* PROGRESS GLOBAL */
.global-progress {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 1.5rem 2rem;
    margin-bottom: 3rem;
    box-shadow: var(--shadow);
    display: flex; align-items: center; gap: 2rem;
    flex-wrap: wrap;
    animation: fadeIn 0.8s ease;
}
@keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
.progress-info { flex: 1; min-width: 200px; }
.progress-info h3 { font-size: 1rem; font-weight: 600; margin-bottom: 8px; color: var(--violet3); }
.progress-bar-wrap {
    background: rgba(255,255,255,0.07);
    border-radius: 50px; height: 10px; overflow: hidden; position: relative;
}
.progress-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--violet), var(--accent));
    border-radius: 50px;
    box-shadow: 0 0 12px rgba(168,85,247,0.6);
    transition: width 1.2s cubic-bezier(.4,0,.2,1);
}
.progress-stats { display:flex; gap:1.5rem; flex-wrap:wrap; }
.stat-badge {
    background: rgba(124,58,237,0.15);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 0.8rem 1.2rem;
    text-align: center; min-width: 100px;
}
.stat-badge .val { font-size: 1.6rem; font-weight: 800; color: var(--accent); }
.stat-badge .lbl { font-size: 0.75rem; color: var(--muted); margin-top: 2px; }
/* GRID NIVEAUX */
.levels-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
}
/* CARD */
.level-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 1.6rem;
    position: relative;
    overflow: hidden;
    transition: all 0.35s cubic-bezier(.4,0,.2,1);
    animation: slideUp 0.5s ease both;
}
@keyframes slideUp {
    from { opacity:0; transform:translateY(30px); }
    to { opacity:1; transform:translateY(0); }
}
.level-card:nth-child(1) { animation-delay:0.1s; }
.level-card:nth-child(2) { animation-delay:0.15s; }
.level-card:nth-child(3) { animation-delay:0.2s; }
.level-card:nth-child(4) { animation-delay:0.25s; }
.level-card:nth-child(5) { animation-delay:0.3s; }
.level-card:nth-child(6) { animation-delay:0.35s; }

.level-card::before {
    content: '';
    position: absolute;
    top: -50%; left: -50%;
    width: 200%; height: 200%;
    background: radial-gradient(circle, rgba(124,58,237,0.07) 0%, transparent 70%);
    pointer-events: none;
}
.level-card.unlocked {
    border-color: rgba(124,58,237,0.5);
    box-shadow: var(--glow2);
}
.level-card.unlocked:hover {
    transform: translateY(-6px);
    border-color: var(--violet);
    box-shadow: var(--glow), var(--shadow);
}
.level-card.locked {
    border-color: rgba(255,255,255,0.06);
    opacity: 0.75;
    filter: saturate(0.4);
}
.level-card.passed {
    border-color: rgba(34,197,94,0.4);
    box-shadow: 0 0 20px rgba(34,197,94,0.15);
}
.level-card.passed:hover { transform: translateY(-6px); }
/* Card header */
.card-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom: 1rem; }
.level-badge {
    width: 56px; height: 56px;
    border-radius: 14px;
    display: flex; align-items:center; justify-content:center;
    font-size: 1.2rem; font-weight: 800;
    background: linear-gradient(135deg, var(--violet), var(--accent));
    box-shadow: 0 0 16px rgba(168,85,247,0.4);
    color: #fff;
    flex-shrink: 0;
}
.level-card.locked .level-badge {
    background: rgba(255,255,255,0.07);
    box-shadow: none;
}
.level-card.passed .level-badge {
    background: linear-gradient(135deg, #16a34a, #22c55e);
    box-shadow: 0 0 16px rgba(34,197,94,0.4);
}
.status-chip {
    display: flex; align-items:center; gap: 6px;
    padding: 5px 12px; border-radius: 50px;
    font-size: 0.75rem; font-weight: 700; letter-spacing: 0.5px;
}
.status-chip.unlocked { background: rgba(124,58,237,0.2); color: var(--violet3); border:1px solid rgba(124,58,237,0.4); }
.status-chip.locked { background: rgba(255,255,255,0.05); color: var(--muted); border:1px solid rgba(255,255,255,0.1); }
.status-chip.passed { background: rgba(34,197,94,0.15); color: var(--green); border:1px solid rgba(34,197,94,0.4); }

.card-title { font-size: 1.15rem; font-weight: 700; margin-bottom: 0.3rem; }
.card-desc { font-size: 0.85rem; color: var(--muted); line-height: 1.5; margin-bottom: 1.2rem; }

.card-score {
    background: rgba(255,255,255,0.04);
    border-radius: 10px;
    padding: 0.8rem 1rem;
    margin-bottom: 1.2rem;
    display:flex; align-items:center; gap:1rem;
}
.score-ring { position:relative; width:44px; height:44px; flex-shrink:0; }
.score-ring svg { position:absolute; top:0; left:0; }
.score-ring .ring-text {
    position:absolute; top:50%; left:50%; transform:translate(-50%,-50%);
    font-size: 0.7rem; font-weight: 700;
}
.score-info { flex:1; }
.score-info .score-label { font-size:0.75rem; color:var(--muted); margin-bottom:3px; }
.score-info .score-val { font-size:1.1rem; font-weight:700; }
.score-passed { color: var(--green); }
.score-failed { color: var(--red); }

/* MINI PROGRESS */
.mini-prog-wrap { margin-bottom: 1.4rem; }
.mini-prog-label { display:flex; justify-content:space-between; font-size:0.78rem; color:var(--muted); margin-bottom:5px; }
.mini-prog-bar { height:5px; background:rgba(255,255,255,0.07); border-radius:50px; overflow:hidden; }
.mini-prog-fill { height:100%; border-radius:50px; transition: width 1s ease; }
.fill-violet { background: linear-gradient(90deg, var(--violet), var(--accent)); box-shadow: 0 0 8px rgba(168,85,247,0.5); }
.fill-green { background: linear-gradient(90deg, #16a34a, var(--green)); box-shadow: 0 0 8px rgba(34,197,94,0.4); }

/* CARD ACTIONS */
.card-actions { display:flex; gap:0.75rem; flex-wrap:wrap; }
.btn {
    display: inline-flex; align-items:center; gap:8px;
    padding: 10px 18px; border-radius: 12px;
    font-size: 0.88rem; font-weight: 600; cursor: pointer;
    text-decoration: none; transition: all 0.25s; border: none;
    font-family: 'Inter', sans-serif;
}
.btn-primary {
    background: linear-gradient(135deg, var(--violet), var(--accent));
    color: #fff;
    box-shadow: 0 4px 15px rgba(124,58,237,0.4);
}
.btn-primary:hover { transform:translateY(-2px); box-shadow: 0 6px 20px rgba(124,58,237,0.6); }
.btn-ghost {
    background: rgba(255,255,255,0.06);
    color: var(--muted);
    border: 1px solid rgba(255,255,255,0.1);
}
.btn-ghost:hover { background: rgba(255,255,255,0.1); color: var(--text); }
.btn-green { background: linear-gradient(135deg, #16a34a, #22c55e); color:#fff; box-shadow: 0 4px 15px rgba(34,197,94,0.3); }
.btn-green:hover { transform:translateY(-2px); box-shadow: 0 6px 20px rgba(34,197,94,0.5); }
.btn-disabled { background:rgba(255,255,255,0.04); color:rgba(255,255,255,0.2); cursor:not-allowed; border:1px solid rgba(255,255,255,0.06); }

/* HISTORY */
.section-title {
    font-size: 1.15rem; font-weight: 700; margin-bottom: 1.2rem;
    display:flex; align-items:center; gap:10px;
    color: var(--violet3);
}
.history-list { display:flex; flex-direction:column; gap:0.75rem; }
.history-item {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 1rem 1.4rem;
    display: flex; align-items:center; gap:1rem;
    transition: all 0.2s;
    animation: fadeIn 0.5s ease;
}
.history-item:hover { border-color: var(--violet); background: var(--card2); }
.hist-niveau {
    background: linear-gradient(135deg, var(--violet), var(--accent));
    color:#fff; font-weight:800; width:42px; height:42px;
    border-radius:10px; display:flex; align-items:center; justify-content:center;
    font-size:0.85rem; flex-shrink:0;
}
.hist-info { flex:1; }
.hist-info .hist-titre { font-weight:600; font-size:0.95rem; margin-bottom:2px; }
.hist-info .hist-date { font-size:0.78rem; color:var(--muted); }
.hist-score {
    font-size:1.1rem; font-weight:800;
    padding: 4px 12px; border-radius:50px;
}
.hist-score.pass { background:rgba(34,197,94,0.15); color:var(--green); }
.hist-score.fail { background:rgba(239,68,68,0.12); color:var(--red); }
.hist-badge { font-size:0.75rem; font-weight:600; margin-left:0.5rem; }

.lock-overlay {
    position:absolute; inset:0;
    display:flex; align-items:center; justify-content:center;
    flex-direction:column; gap:8px;
    background: rgba(13,13,26,0.6);
    backdrop-filter: blur(2px);
    border-radius:20px;
    pointer-events:none;
}
.lock-overlay i { font-size:2rem; color: rgba(255,255,255,0.15); }
.lock-overlay span { font-size:0.78rem; color:rgba(255,255,255,0.25); font-weight:600; }

@media (max-width:768px) {
    .levels-grid { grid-template-columns: 1fr; }
    .global-progress { flex-direction:column; }
    .navbar { padding:0 1rem; }
}
</style>
</head>
<body>
<nav class="navbar">
    <a href="../dashboard.php" class="nav-brand">
        <i class="fa-solid fa-graduation-cap"></i> DeutschLernen
    </a>
    <div class="nav-links">
        <a href="../dashboard.php" class="nav-link"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
        <a href="tests.php" class="nav-link active"><i class="fa-solid fa-clipboard-question"></i> Tests</a>
        <a href="../forum/index.php" class="nav-link"><i class="fa-solid fa-comments"></i> Forums</a>
    </div>
    <div class="nav-user">
        <i class="fa-solid fa-circle-user"></i>
        <?= htmlspecialchars($username) ?>
    </div>
</nav>

<div class="container">
    <!-- HERO -->
    <div class="hero">
        <h1><i class="fa-solid fa-trophy" style="font-size:0.85em;"></i> Vos Tests de Niveau</h1>
        <p>Réussissez chaque test avec 70% minimum pour débloquer le niveau suivant et accéder à son forum.</p>
    </div>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'forum_locked'): ?>
    <div style="background:rgba(239,68,68,0.12);border:1px solid rgba(239,68,68,0.35);border-radius:14px;
                padding:1rem 1.5rem;margin-bottom:2rem;display:flex;align-items:center;gap:12px;
                animation:fadeIn 0.5s ease;">
        <i class="fa-solid fa-triangle-exclamation" style="color:#ef4444;font-size:1.3rem;flex-shrink:0;"></i>
        <div>
            <strong style="color:#ef4444;">Accès refusé</strong> —
            Le forum <strong><?= htmlspecialchars(strtoupper($_GET['niveau'] ?? '')) ?></strong> est verrouillé.
            Réussissez d'abord le test du niveau précédent avec au moins 70%.
        </div>
    </div>
    <?php endif; ?>

    <!-- PROGRESSION GLOBALE -->
    <div class="global-progress">
        <div class="progress-info" style="flex:2; min-width:250px;">
            <h3><i class="fa-solid fa-chart-bar"></i> Progression Globale</h3>
            <div style="display:flex; justify-content:space-between; font-size:0.8rem; color:var(--muted); margin-bottom:6px;">
                <span><?= $niveauxReussis ?>/<?= $totalNiveaux ?> niveaux validés</span>
                <span style="color:var(--accent); font-weight:700;"><?= $progressionPct ?>%</span>
            </div>
            <div class="progress-bar-wrap">
                <div class="progress-bar-fill" id="globalBar" style="width:0%"></div>
            </div>
            <script>setTimeout(()=>{ document.getElementById('globalBar').style.width='<?= $progressionPct ?>%'; },200);</script>
        </div>
        <div class="progress-stats">
            <div class="stat-badge">
                <div class="val"><?= count($debloques) ?></div>
                <div class="lbl">Débloqués</div>
            </div>
            <div class="stat-badge">
                <div class="val"><?= $niveauxReussis ?></div>
                <div class="lbl">Réussis</div>
            </div>
            <div class="stat-badge">
                <div class="val"><?= count($historique) ?>+</div>
                <div class="lbl">Passages</div>
            </div>
        </div>
    </div>

    <!-- CARTES NIVEAUX -->
    <div class="levels-grid">
    <?php foreach ($niveaux as $i => $niv):
        $isUnlocked = in_array($niv, $debloques);
        $hasPassed = isset($scores[$niv]) && $scores[$niv]['reussi'] == 1;
        $scoreData = $scores[$niv] ?? null;
        $testInfo = $testsMap[$niv] ?? null;
        $cardClass = $hasPassed ? 'passed' : ($isUnlocked ? 'unlocked' : 'locked');
        $pct = $scoreData ? round($scoreData['meilleur']) : 0;
        $prevNiv = $i > 0 ? $niveaux[$i-1] : null;
    ?>
    <div class="level-card <?= $cardClass ?>">
        <?php if (!$isUnlocked): ?>
        <div class="lock-overlay">
            <i class="fa-solid fa-lock"></i>
            <span>Réussir le test <?= htmlspecialchars($prevNiv) ?> pour débloquer</span>
        </div>
        <?php endif; ?>

        <div class="card-header">
            <div class="level-badge"><?= $niv ?></div>
            <?php if ($hasPassed): ?>
                <div class="status-chip passed"><i class="fa-solid fa-circle-check"></i> Réussi</div>
            <?php elseif ($isUnlocked): ?>
                <div class="status-chip unlocked"><i class="fa-solid fa-unlock"></i> Débloqué</div>
            <?php else: ?>
                <div class="status-chip locked"><i class="fa-solid fa-lock"></i> Verrouillé</div>
            <?php endif; ?>
        </div>

        <div class="card-title"><?= htmlspecialchars($testInfo['titre'] ?? "Niveau $niv") ?></div>
        <div class="card-desc"><?= htmlspecialchars($testInfo['description'] ?? '') ?></div>

        <!-- Score si déjà tenté -->
        <?php if ($scoreData): ?>
        <div class="card-score">
            <div class="score-ring">
                <svg width="44" height="44" viewBox="0 0 44 44">
                    <circle cx="22" cy="22" r="18" fill="none" stroke="rgba(255,255,255,0.07)" stroke-width="4"/>
                    <circle cx="22" cy="22" r="18" fill="none"
                        stroke="<?= $hasPassed ? '#22c55e' : '#ef4444' ?>"
                        stroke-width="4"
                        stroke-dasharray="<?= round(113 * $pct / 100) ?> 113"
                        stroke-linecap="round"
                        transform="rotate(-90 22 22)"/>
                </svg>
                <div class="ring-text" style="color:<?= $hasPassed ? '#22c55e' : '#ef4444' ?>"><?= $pct ?>%</div>
            </div>
            <div class="score-info">
                <div class="score-label">Meilleur score</div>
                <div class="score-val <?= $hasPassed ? 'score-passed' : 'score-failed' ?>">
                    <?= $pct ?>% — <?= $hasPassed ? 'Réussi ✓' : 'Non validé' ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Mini barre progrès -->
        <div class="mini-prog-wrap">
            <div class="mini-prog-label">
                <span><i class="fa-solid fa-signal" style="font-size:0.7rem;"></i> Progression</span>
                <span><?= $pct ?>% / 70% requis</span>
            </div>
            <div class="mini-prog-bar">
                <div class="mini-prog-fill <?= $hasPassed ? 'fill-green' : 'fill-violet' ?>"
                     style="width:<?= min($pct,100) ?>%"
                     data-target="<?= min($pct,100) ?>"></div>
            </div>
        </div>

        <!-- Actions -->
        <div class="card-actions">
            <?php if ($isUnlocked): ?>
                <a href="passer_test.php?niveau=<?= urlencode($niv) ?>" class="btn btn-primary">
                    <i class="fa-solid fa-play"></i>
                    <?= $hasPassed ? 'Repasser le test' : 'Passer le test' ?>
                </a>
                <a href="../forum/chat.php?niveau=<?= urlencode($niv) ?>" class="btn btn-ghost">
                    <i class="fa-solid fa-comments"></i> Forum <?= $niv ?>
                </a>
            <?php else: ?>
                <span class="btn btn-disabled"><i class="fa-solid fa-lock"></i> Test verrouillé</span>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    </div>

    <!-- HISTORIQUE -->
    <?php if ($historique): ?>
    <div>
        <div class="section-title">
            <i class="fa-solid fa-clock-rotate-left"></i> Historique Récent
        </div>
        <div class="history-list">
        <?php foreach ($historique as $h): ?>
        <div class="history-item">
            <div class="hist-niveau"><?= htmlspecialchars($h['niveau']) ?></div>
            <div class="hist-info">
                <div class="hist-titre"><?= htmlspecialchars($h['titre']) ?></div>
                <div class="hist-date"><i class="fa-regular fa-calendar"></i> <?= date('d/m/Y à H:i', strtotime($h['date_passage'])) ?></div>
            </div>
            <div class="hist-score <?= $h['reussi'] ? 'pass' : 'fail' ?>">
                <?= round($h['pourcentage']) ?>%
                <span class="hist-badge"><?= $h['reussi'] ? '✓' : '✗' ?></span>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Animate progress bars
document.querySelectorAll('.mini-prog-fill').forEach(el => {
    const target = el.dataset.target;
    el.style.width = '0%';
    setTimeout(() => { el.style.width = target + '%'; }, 400);
});
</script>
</body>
</html>

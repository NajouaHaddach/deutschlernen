<?php
// index.php
$currentPage = 'dashboard';
require_once 'db.php';
require_once 'config.php';

if (!isLoggedIn()) {
    header("Location: auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user progress stats
$stmtProg = $pdo->prepare("SELECT COUNT(*) as total_completed, AVG(score) as avg_score FROM scenario_user_progress WHERE user_id = ? AND completed = 1");
$stmtProg->execute([$user_id]);
$stats = $stmtProg->fetch(PDO::FETCH_ASSOC);
$totalCompleted = (int)$stats['total_completed'];
$avgScore = round((float)$stats['avg_score']);

// Get user XP
$stmtXp = $pdo->prepare("SELECT total_xp, level_unlocked FROM user_xp WHERE user_id = ?");
$stmtXp->execute([$user_id]);
$xpData = $stmtXp->fetch(PDO::FETCH_ASSOC);
$totalXp = $xpData ? (int)$xpData['total_xp'] : 0;
$unlocked = $xpData ? json_decode($xpData['level_unlocked'], true) : ['A1'=>true];

// Get levels
$stmtLevels = $pdo->query("SELECT * FROM levels ORDER BY sort_order ASC");
$levels = $stmtLevels->fetchAll(PDO::FETCH_ASSOC);

// Calculate level completion
$levelProgress = [];
foreach ($levels as $lvl) {
    $stmt = $pdo->prepare("
        SELECT COUNT(s.id) as total_scenarios, 
               SUM(CASE WHEN up.completed = 1 THEN 1 ELSE 0 END) as completed_scenarios,
               AVG(CASE WHEN up.completed = 1 THEN up.score ELSE 0 END) as level_score
        FROM scenarios s
        LEFT JOIN scenario_user_progress up ON s.id = up.scenario_id AND up.user_id = ?
        WHERE s.level_id = ?
    ");
    $stmt->execute([$user_id, $lvl['id']]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    $total = (int)$res['total_scenarios'];
    $comp = (int)$res['completed_scenarios'];
    $pct = $total > 0 ? round(($comp / $total) * 100) : 0;
    
    $levelProgress[$lvl['name']] = [
        'total' => $total,
        'completed' => $comp,
        'pct' => $pct,
        'avg_score' => round((float)$res['level_score']),
        'unlocked' => !empty($unlocked[$lvl['name']])
    ];
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mein Fortschritt – DeutschWelt</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<style>
:root {
    --bg: #080E1A;
    --panel: #0E1726;
    --surface: #141F32;
    --border: rgba(255,255,255,.07);
    --text: #EEF2FF;
    --muted: #64748B;
    --accent: #6366F1;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body { background: var(--bg); color: var(--text); font-family: 'DM Sans', sans-serif; display: flex; min-height: 100vh; }

.main { flex: 1; padding: 40px 40px 60px; overflow-y: auto; }

.main-header { margin-bottom: 36px; }
.main-header h1 { font-family: 'Syne', sans-serif; font-size: 34px; font-weight: 800; letter-spacing: -1px; margin-bottom: 6px; }
.main-header p { color: var(--muted); font-size: 15px; }

/* Stats grid */
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 40px; }
.stat-card {
    background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 24px;
    display: flex; align-items: center; gap: 20px;
}
.stat-icon {
    width: 60px; height: 60px; border-radius: 14px; background: rgba(99,102,241,.15); color: var(--accent);
    display: flex; align-items: center; justify-content: center; font-size: 28px;
}
.stat-val { font-family: 'Syne', sans-serif; font-size: 32px; font-weight: 800; line-height: 1.1; margin-bottom: 4px; }
.stat-lbl { color: var(--muted); font-size: 13px; font-weight: 500; text-transform: uppercase; letter-spacing: 1px; }

/* Levels progress */
.levels-grid { display: grid; grid-template-columns: 1fr; gap: 20px; }
.lvl-card {
    background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 24px;
    display: flex; align-items: center; gap: 30px; position: relative; overflow: hidden;
}
.lvl-card.locked { opacity: 0.6; pointer-events: none; }
.lvl-card.locked::after {
    content: "\eb02"; font-family: "tabler-icons"; position: absolute; inset: 0; background: rgba(8,14,26,.6);
    display: flex; align-items: center; justify-content: center; font-size: 48px; color: white;
}

.lvl-badge {
    width: 80px; height: 80px; border-radius: 20px; display: flex; flex-direction: column; align-items: center; justify-content: center;
    font-family: 'Syne', sans-serif; font-weight: 800; font-size: 24px; flex-shrink: 0;
}
.lvl-info { flex: 1; }
.lvl-name { font-family: 'Syne', sans-serif; font-size: 20px; font-weight: 700; margin-bottom: 6px; }
.lvl-desc { color: var(--muted); font-size: 14px; margin-bottom: 16px; }

.lvl-prog-wrap { width: 100%; height: 8px; background: rgba(255,255,255,.08); border-radius: 4px; overflow: hidden; margin-bottom: 8px; }
.lvl-prog-fill { height: 100%; border-radius: 4px; transition: width .5s; }

.lvl-stats { display: flex; gap: 20px; font-size: 13px; color: var(--text); }
.lvl-stats span { display: flex; align-items: center; gap: 6px; }

@media (max-width: 768px) {
    .lvl-card { flex-direction: column; align-items: flex-start; gap: 20px; }
    .lvl-badge { width: 60px; height: 60px; font-size: 20px; }
}
</style>
</head>
<body>

<?php require_once 'includes/sidebar.php'; ?>

<main class="main">
    <div class="main-header">
        <h1>Mein Fortschritt</h1>
        <p>Verfolge deine Lernkurve und schalte neue Level frei.</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(251,191,36,.15);color:#FBBF24;"><i class="ti ti-star-filled"></i></div>
            <div>
                <div class="stat-val"><?= $totalXp ?></div>
                <div class="stat-lbl">Gesamt XP</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(34,197,94,.15);color:#22C55E;"><i class="ti ti-check"></i></div>
            <div>
                <div class="stat-val"><?= $totalCompleted ?></div>
                <div class="stat-lbl">Szenarien</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(56,189,248,.15);color:#38BDF8;"><i class="ti ti-target"></i></div>
            <div>
                <div class="stat-val"><?= $avgScore ?>%</div>
                <div class="stat-lbl">Durchschnitt</div>
            </div>
        </div>
    </div>

    <div class="levels-grid">
        <?php foreach ($levels as $lvl): 
            $prog = $levelProgress[$lvl['name']];
            $isLocked = !$prog['unlocked'];
        ?>
        <div class="lvl-card <?= $isLocked ? 'locked' : '' ?>">
            <div class="lvl-badge" style="background:<?= $lvl['color_hex'] ?>22; color:<?= $lvl['color_hex'] ?>; border: 1px solid <?= $lvl['color_hex'] ?>44;">
                <?= $lvl['name'] ?>
            </div>
            <div class="lvl-info">
                <div class="lvl-name"><?= htmlspecialchars($lvl['icon'] . ' ' . $lvl['label']) ?></div>
                <div class="lvl-desc"><?= htmlspecialchars($lvl['description']) ?></div>
                <div class="lvl-prog-wrap">
                    <div class="lvl-prog-fill" style="width:<?= $prog['pct'] ?>%; background:<?= $lvl['color_hex'] ?>;"></div>
                </div>
                <div class="lvl-stats">
                    <span style="color:var(--muted);"><i class="ti ti-check"></i> <?= $prog['completed'] ?> / <?= $prog['total'] ?> abgeschlossen</span>
                    <?php if($prog['completed'] > 0): ?>
                    <span style="color:#22C55E;"><i class="ti ti-trophy"></i> <?= $prog['avg_score'] ?>% Ø Score</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

</main>

</body>
</html>

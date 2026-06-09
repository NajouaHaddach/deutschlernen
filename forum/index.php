<?php
require_once '../config.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../auth/login.php'); exit; }

$userId   = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';
$initials = mb_strtoupper(mb_substr($username, 0, 2));

$niveaux = ['A1','A2','B1','B2','C1','C2'];

// A1 toujours débloqué
try { $pdo->prepare("INSERT IGNORE INTO niveaux_debloques (user_id, niveau) VALUES (?,?)")->execute([$userId,'A1']); } catch(Exception $e){}

// Niveaux débloqués pour cet user
$stmt = $pdo->prepare("SELECT niveau FROM niveaux_debloques WHERE user_id=?");
$stmt->execute([$userId]);
$debloques = array_column($stmt->fetchAll(), 'niveau');

// Meilleur score réussi par niveau (test_id via niveau_tests)
$stmt = $pdo->prepare("
    SELECT nt.niveau, MAX(rt.pourcentage) as pct, MAX(rt.reussi) as reussi
    FROM resultats_tests rt
    JOIN niveau_tests nt ON nt.id = rt.test_id
    WHERE rt.user_id = ?
    GROUP BY nt.niveau
");
$stmt->execute([$userId]);
$scores = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), null, 'niveau');

// Nbre de messages par forum
$msgCounts = [];
try {
    $stmt = $pdo->query("SELECT niveau, COUNT(*) as nb FROM forum_rooms fr LEFT JOIN forum_messages fm ON fm.room_id=fr.id GROUP BY fr.niveau");
    foreach($stmt->fetchAll() as $r) $msgCounts[$r['niveau']] = $r['nb'];
} catch(Exception $e){}

$niveauInfos = [
    'A1' => ['icon'=>'🌱','name'=>'Anfänger',        'color'=>'#22c55e','bg'=>'#dcfce7','prev'=>null],
    'A2' => ['icon'=>'📚','name'=>'Grundlagen',       'color'=>'#0ea5e9','bg'=>'#e0f2fe','prev'=>'A1'],
    'B1' => ['icon'=>'⚡','name'=>'Mittelstufe',      'color'=>'#f59e0b','bg'=>'#fef3c7','prev'=>'A2'],
    'B2' => ['icon'=>'🔥','name'=>'Fortgeschrittene', 'color'=>'#f97316','bg'=>'#ffedd5','prev'=>'B1'],
    'C1' => ['icon'=>'💎','name'=>'Kompetent',        'color'=>'#8b5cf6','bg'=>'#ede9fe','prev'=>'B2'],
    'C2' => ['icon'=>'👑','name'=>'Meisterschaft',    'color'=>'#ec4899','bg'=>'#fce7f3','prev'=>'C1'],
];

$currentPage = 'forum';
?>
<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forum — DeutschLernen</title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
a{text-decoration:none;color:inherit;}
button{font-family:inherit;cursor:pointer;border:none;background:none;}

:root{
    --bg-base:#E8FAF7;--bg-surface:#FFF;--bg-muted:#D0F4EE;
    --sidebar-bg:#0A2030;--sidebar-hover:rgba(45,212,191,.10);--sidebar-active:rgba(45,212,191,.20);
    --sidebar-text:#7AACB8;--sidebar-head:#3A7080;
    --text-primary:#062030;--text-secondary:#234858;--text-muted:#6A9AA8;
    --accent:#0D9488;--accent-light:#CCFBF1;--accent-text:#0F766E;
    --border:rgba(13,148,136,.12);--border-strong:rgba(13,148,136,.22);
    --radius-sm:8px;--radius-md:12px;--radius-lg:16px;--radius-xl:20px;
    --shadow:0 1px 4px rgba(13,148,136,.08),0 4px 14px rgba(13,148,136,.06);
    --shadow-h:0 8px 28px rgba(13,148,136,.20);
    --dur:.24s cubic-bezier(.4,0,.2,1);--sw:252px;--sm:64px;
    --rose:#E11D48;--rose-light:#FFE4E6;
    --violet:#7C3AED;--violet-light:#EDE9FE;
}
[data-theme="dark"]{
    --bg-base:#061820;--bg-surface:#0C2530;--bg-muted:#102E3A;
    --sidebar-bg:#040E14;--text-primary:#E0FAF6;--text-secondary:#90C4C0;
    --text-muted:#3A6870;--accent:#2DD4BF;--accent-light:#0A2828;--accent-text:#5EEAD4;
    --border:rgba(45,212,191,.10);--border-strong:rgba(45,212,191,.20);
    --shadow:0 1px 4px rgba(0,0,0,.5),0 4px 14px rgba(0,0,0,.4);
    --shadow-h:0 8px 28px rgba(13,148,136,.35);
    --rose-light:#2A0812;--violet-light:#160A30;
}

html,body{height:100%;overflow:hidden;}
body{font-family:'Sora',sans-serif;background:var(--bg-base);color:var(--text-primary);display:flex;transition:background var(--dur),color var(--dur);}

/* SIDEBAR */
.sidebar{width:var(--sw);min-width:var(--sw);background:var(--sidebar-bg);display:flex;flex-direction:column;padding:22px 0 18px;transition:width var(--dur),min-width var(--dur);z-index:20;overflow:hidden;}
.sidebar.mini{width:var(--sm);min-width:var(--sm);}
.sb-logo{display:flex;align-items:center;gap:11px;padding:0 18px 24px;white-space:nowrap;}
.sidebar.mini .sb-logo{justify-content:center;padding:0 0 24px;}
.logo-mark{width:36px;height:36px;min-width:36px;border-radius:10px;background:linear-gradient(135deg,#0D9488,#0891B2);display:flex;align-items:center;justify-content:center;color:#fff;font-size:18px;}
.logo-text{font-size:15px;font-weight:800;color:#fff;letter-spacing:-.4px;transition:opacity var(--dur),transform var(--dur);}
.sidebar.mini .logo-text{opacity:0;pointer-events:none;transform:translateX(-6px);}
.sb-divider{height:1px;background:rgba(255,255,255,.06);margin:0 14px 12px;}
.sb-label{font-size:10px;font-weight:700;letter-spacing:1.4px;text-transform:uppercase;color:var(--sidebar-head);padding:0 20px 6px;white-space:nowrap;transition:opacity var(--dur);}
.sidebar.mini .sb-label{opacity:0;}
.nav-item{display:flex;align-items:center;gap:11px;padding:9px 12px;margin:2px 8px;border-radius:var(--radius-sm);color:var(--sidebar-text);font-size:13px;font-weight:500;cursor:pointer;white-space:nowrap;position:relative;transition:background var(--dur),color var(--dur);}
.sidebar.mini .nav-item{justify-content:center;padding:9px;}
.nav-item:hover{background:var(--sidebar-hover);color:#cbd5e1;}
.nav-item.active{background:var(--sidebar-active);color:#fff;}
.nav-item .ni{font-size:18px;min-width:20px;color:#4a5568;transition:color var(--dur);}
.nav-item:hover .ni,.nav-item.active .ni{color:#2DD4BF;}
.nav-item.active::before{content:'';position:absolute;left:0;top:22%;bottom:22%;width:3px;border-radius:0 3px 3px 0;background:#2DD4BF;}
.nav-lbl{transition:opacity var(--dur),transform var(--dur);}
.sidebar.mini .nav-lbl{opacity:0;pointer-events:none;transform:translateX(-4px);}
.nav-tip{position:absolute;left:calc(var(--sm) - 2px);background:#0A2030;color:#E0FAF6;font-size:12px;font-weight:600;padding:5px 11px;border-radius:var(--radius-sm);white-space:nowrap;pointer-events:none;opacity:0;transform:translateX(-5px);transition:opacity .16s,transform .16s;box-shadow:0 4px 14px rgba(0,0,0,.3);z-index:99;}
.sidebar.mini .nav-item:hover .nav-tip{opacity:1;transform:translateX(0);}
.sb-spacer{flex:1;}
.sb-toggle{width:30px;height:30px;border-radius:7px;background:rgba(255,255,255,.06);display:flex;align-items:center;justify-content:center;color:#8892A4;font-size:15px;margin:10px auto 0;transition:background var(--dur),color var(--dur);}
.sb-toggle:hover{background:rgba(255,255,255,.13);color:#fff;}

/* MAIN */
.main{flex:1;display:flex;flex-direction:column;overflow:hidden;min-width:0;}
.topbar{display:flex;align-items:center;justify-content:space-between;padding:13px 28px;background:var(--bg-surface);border-bottom:1px solid var(--border);transition:background var(--dur),border-color var(--dur);gap:12px;}
.tb-title{font-size:17px;font-weight:800;letter-spacing:-.4px;}
.tb-sub{font-size:12px;color:var(--text-muted);margin-top:2px;display:flex;align-items:center;gap:5px;}
.tb-right{display:flex;align-items:center;gap:8px;flex-shrink:0;}
.theme-btn{display:flex;align-items:center;gap:6px;background:var(--bg-muted);border:1px solid var(--border-strong);border-radius:20px;padding:6px 13px;font-size:12px;font-weight:600;color:var(--text-secondary);transition:all var(--dur);}
.theme-btn:hover{border-color:var(--accent);color:var(--accent);}
.avatar{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#0D9488,#0891B2);display:flex;align-items:center;justify-content:center;color:#fff;font-size:12px;font-weight:800;cursor:pointer;flex-shrink:0;}
.btn-logout{display:flex;align-items:center;gap:6px;border:1px solid var(--rose);border-radius:var(--radius-sm);padding:6px 13px;font-size:12px;font-weight:600;color:var(--rose);transition:all var(--dur);}
.btn-logout:hover{background:var(--rose-light);}

/* CONTENT */
.content{flex:1;overflow-y:auto;padding:26px 28px;background:var(--bg-base);transition:background var(--dur);}
.content::-webkit-scrollbar{width:4px;}
.content::-webkit-scrollbar-thumb{background:var(--border-strong);border-radius:4px;}

/* PAGE TITLE */
.page-header{margin-bottom:24px;}
.page-header h1{font-size:22px;font-weight:800;letter-spacing:-.5px;margin-bottom:4px;}
.page-header p{font-size:13px;color:var(--text-muted);}

/* FORUM GRID */
.forum-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;}

/* FORUM CARD */
.forum-card{background:var(--bg-surface);border:1.5px solid var(--border);border-radius:var(--radius-xl);padding:22px;position:relative;overflow:hidden;transition:all var(--dur);box-shadow:var(--shadow);animation:rise .4s ease both;}
.forum-card:nth-child(1){animation-delay:.05s;}
.forum-card:nth-child(2){animation-delay:.10s;}
.forum-card:nth-child(3){animation-delay:.15s;}
.forum-card:nth-child(4){animation-delay:.20s;}
.forum-card:nth-child(5){animation-delay:.25s;}
.forum-card:nth-child(6){animation-delay:.30s;}
.forum-card.open{border-color:var(--border-strong);}
.forum-card.open:hover{transform:translateY(-4px);box-shadow:var(--shadow-h);}
.forum-card.locked{opacity:.65;}
.forum-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--fc-color,var(--accent));border-radius:var(--radius-xl) var(--radius-xl) 0 0;}

.card-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;}
.card-icon-wrap{width:48px;height:48px;border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;font-size:22px;background:var(--fc-bg,var(--accent-light));}
.badge{display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:50px;font-size:11px;font-weight:700;}
.badge-open{background:#dcfce7;color:#16a34a;border:1px solid #bbf7d0;}
.badge-locked{background:var(--bg-muted);color:var(--text-muted);border:1px solid var(--border);}
[data-theme="dark"] .badge-open{background:rgba(34,197,94,.15);color:#4ade80;border-color:rgba(34,197,94,.3);}

.card-level{font-size:13px;font-weight:700;color:var(--text-muted);margin-bottom:2px;}
.card-name{font-size:16px;font-weight:800;letter-spacing:-.3px;margin-bottom:8px;}
.card-msg-count{font-size:12px;color:var(--text-muted);margin-bottom:12px;display:flex;align-items:center;gap:5px;}

/* LOCKED INFO */
.lock-info{background:var(--bg-muted);border-radius:var(--radius-md);padding:10px 14px;margin-bottom:14px;font-size:12px;line-height:1.5;color:var(--text-secondary);}
.lock-info strong{color:var(--text-primary);}

/* SCORE BAR */
.score-bar-wrap{margin-bottom:14px;}
.score-bar-label{display:flex;justify-content:space-between;font-size:11px;color:var(--text-muted);margin-bottom:4px;}
.score-bar{height:6px;background:var(--bg-muted);border-radius:50px;overflow:hidden;}
.score-bar-fill{height:100%;border-radius:50px;transition:width 1s ease;background:var(--fc-color,var(--accent));}

/* BUTTONS */
.btn-join{display:flex;align-items:center;justify-content:center;gap:8px;padding:10px 18px;border-radius:var(--radius-md);font-size:13px;font-weight:700;transition:all var(--dur);cursor:pointer;border:none;font-family:'Sora',sans-serif;width:100%;}
.btn-join.primary{background:var(--fc-color,var(--accent));color:#fff;box-shadow:0 4px 12px rgba(13,148,136,.25);}
.btn-join.primary:hover{filter:brightness(1.1);transform:translateY(-1px);}
.btn-join.outline{background:transparent;color:var(--fc-color,var(--accent));border:1.5px solid var(--fc-color,var(--accent));}
.btn-join.outline:hover{background:var(--fc-bg,var(--accent-light));}
.btn-join.disabled{background:var(--bg-muted);color:var(--text-muted);cursor:not-allowed;border:1.5px solid var(--border);}
.btn-row{display:flex;gap:8px;margin-top:4px;}

/* MY LEVEL INDICATOR */
.my-level-tag{position:absolute;top:14px;right:14px;font-size:10px;font-weight:700;background:var(--accent-light);color:var(--accent-text);padding:3px 9px;border-radius:50px;}

@keyframes rise{from{opacity:0;transform:translateY(14px);}to{opacity:1;transform:translateY(0);}}

/* MOBILE */
.mob-overlay{display:none;position:fixed;inset:0;background:rgba(6,30,40,.5);z-index:18;backdrop-filter:blur(2px);}
.mob-overlay.show{display:block;}
.mob-menu-btn{display:none;width:36px;height:36px;border-radius:var(--radius-sm);background:var(--bg-muted);border:1px solid var(--border-strong);align-items:center;justify-content:center;color:var(--text-secondary);font-size:20px;flex-shrink:0;}

@media(max-width:720px){
    .sidebar{position:fixed;top:0;left:0;bottom:0;transform:translateX(-100%);transition:transform var(--dur);z-index:19;width:var(--sw)!important;min-width:var(--sw)!important;}
    .sidebar.mob-open{transform:translateX(0);}
    .mob-menu-btn{display:flex;}
    .sb-toggle{display:none;}
    .topbar{padding:10px 14px;}
    .content{padding:14px;}
    .forum-grid{grid-template-columns:1fr;}
}

/* TEST MODAL */
.test-modal{position:fixed;inset:0;background:rgba(6,30,40,.8);z-index:100;display:none;align-items:center;justify-content:center;backdrop-filter:blur(8px);padding:20px;}
.test-modal.show{display:flex;}
.test-container{background:var(--bg-surface);width:100%;max-width:600px;border-radius:var(--radius-xl);box-shadow:var(--shadow-h);position:relative;overflow:hidden;animation:modalIn .3s cubic-bezier(.34,1.56,.64,1) both;}
@keyframes modalIn{from{opacity:0;transform:scale(.9) translateY(20px);}to{opacity:1;transform:scale(1) translateY(0);}}

.test-header{padding:20px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:var(--bg-muted);}
.test-title{font-weight:800;font-size:16px;display:flex;align-items:center;gap:10px;}
.test-close{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:20px;transition:all .2s;}
.test-close:hover{background:rgba(225,29,72,.1);color:var(--rose);}

.test-body{padding:28px;min-height:340px;}
.test-progress{height:6px;background:var(--bg-muted);border-radius:50px;margin-bottom:24px;overflow:hidden;}
.test-progress-bar{height:100%;background:var(--accent);width:0%;transition:width .4s ease;}

.q-box{display:none;}
.q-box.active{display:block;animation:fadeIn .4s ease;}
@keyframes fadeIn{from{opacity:0;transform:translateY(10px);}to{opacity:1;transform:translateY(0);}}

.q-text{font-size:18px;font-weight:700;margin-bottom:20px;line-height:1.4;}
.options-grid{display:grid;gap:12px;}
.opt-card{border:2px solid var(--border);border-radius:var(--radius-md);padding:14px 18px;cursor:pointer;transition:all .2s;display:flex;align-items:center;gap:12px;font-weight:600;font-size:14px;background:var(--bg-surface);}
.opt-card:hover{border-color:var(--accent);background:var(--accent-light);}
.opt-card.selected { border-color: var(--accent); background: var(--accent-light); }
.opt-card.correct { border-color: #22c55e !important; background: #f0fdf4 !important; color: #166534 !important; }
.opt-card.wrong { border-color: #ef4444 !important; background: #fef2f2 !important; color: #991b1b !important; }
.opt-card.correct .opt-letter { background: #22c55e !important; color: #fff !important; }
.opt-card.wrong .opt-letter { background: #ef4444 !important; color: #fff !important; }

.test-footer{padding:18px 24px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;gap:12px;}
.btn-test{padding:10px 22px;border-radius:var(--radius-md);font-weight:700;font-size:14px;transition:all .2s;display:flex;align-items:center;gap:8px;}
.btn-test-prev{color:var(--text-muted);background:var(--bg-muted);}
.btn-test-next{background:var(--accent);color:#fff;}
.btn-test:disabled{opacity:.5;cursor:not-allowed;}

/* RESULTS */
.result-box{text-align:center;padding:10px 0;}
.result-icon{font-size:64px;margin-bottom:16px;display:inline-block;}
.result-score{font-size:48px;font-weight:800;margin-bottom:4px;color:var(--accent);}
.result-status{font-size:18px;font-weight:700;margin-bottom:20px;}
.result-status.pass{color:var(--success);}
.result-status.fail{color:var(--rose);}
</style>
</head>
<body>
<div class="mob-overlay" id="mobOverlay" onclick="closeMobileMenu()"></div>

<!-- SIDEBAR -->
<nav class="sidebar" id="sidebar">
    <div class="sb-logo">
        <div class="logo-mark"><i class="ti ti-language"></i></div>
        <span class="logo-text">DeutschLernen</span>
    </div>
    <div class="sb-divider"></div>
    <span class="sb-label">Navigation</span>
    <a href="../dashboard.php" class="nav-item">
        <i class="ti ti-layout-dashboard ni"></i>
        <span class="nav-lbl">Tableau de bord</span>
        <span class="nav-tip">Tableau de bord</span>
    </a>
    <a href="../tests/tests.php" class="nav-item">
        <i class="ti ti-clipboard-check ni"></i>
        <span class="nav-lbl">Tests de niveau</span>
        <span class="nav-tip">Tests</span>
    </a>
    <a href="index.php" class="nav-item active">
        <i class="ti ti-messages ni"></i>
        <span class="nav-lbl">Forum</span>
        <span class="nav-tip">Forum</span>
    </a>
    <div class="sb-spacer"></div>
    <div class="sb-divider" style="margin-bottom:10px"></div>
    <span class="sb-label">Compte</span>
    <a href="../auth/logout.php" class="nav-item">
        <i class="ti ti-logout ni"></i>
        <span class="nav-lbl">Déconnexion</span>
        <span class="nav-tip">Déconnexion</span>
    </a>
    <button class="sb-toggle" id="sidebarToggle" onclick="toggleSidebar()">
        <i class="ti ti-chevrons-left" id="toggleIcon"></i>
    </button>
</nav>

<!-- MAIN -->
<div class="main">
    <header class="topbar">
        <div style="display:flex;align-items:center;gap:12px">
            <button class="mob-menu-btn" onclick="openMobileMenu()"><i class="ti ti-menu-2"></i></button>
            <div>
                <div class="tb-title"><i class="ti ti-messages" style="font-size:16px;vertical-align:-2px;margin-right:6px;color:var(--accent)"></i>Forum</div>
                <div class="tb-sub"><i class="ti ti-info-circle"></i> Choisissez votre niveau pour rejoindre une discussion</div>
            </div>
        </div>
        <div class="tb-right">
            <button class="theme-btn" onclick="toggleTheme()" id="themeBtn">
                <i class="ti ti-moon" id="themeIcon"></i>
                <span id="themeLabel">Sombre</span>
            </button>
            <div class="avatar"><?= htmlspecialchars($initials) ?></div>
            <a href="../auth/logout.php" class="btn-logout">
                <i class="ti ti-logout"></i><span>Déconnexion</span>
            </a>
        </div>
    </header>

    <main class="content">
        <div class="page-header">
            <h1><i class="ti ti-messages" style="color:var(--accent);vertical-align:-3px"></i> Forums par Niveau</h1>
            <p>Réussissez chaque test avec 70%+ pour débloquer le forum du niveau suivant.</p>
        </div>

        <div class="forum-grid">
        <?php foreach ($niveaux as $i => $niv):
            $info     = $niveauInfos[$niv];
            $prevNiv  = $info['prev'];
            $isOpen   = in_array($niv, $debloques);
            $score    = $scores[$niv] ?? null;
            $prevScore = $prevNiv ? ($scores[$prevNiv] ?? null) : null;
            $hasPassed = $score && $score['reussi'] == 1;
            $msgs     = $msgCounts[$niv] ?? 0;
            // Mon niveau actuel ?
            $userNiveau = $_SESSION['niveau_actuel'] ?? 'A1';
        ?>
        <div class="forum-card <?= $isOpen ? 'open' : 'locked' ?>"
             style="--fc-color:<?= $info['color'] ?>;--fc-bg:<?= $info['bg'] ?>">

            <?php if ($niv === $userNiveau): ?>
            <div class="my-level-tag">⭐ Ton niveau actuel</div>
            <?php endif; ?>

            <div class="card-top">
                <div class="card-icon-wrap"><?= $info['icon'] ?></div>
                <?php if ($isOpen): ?>
                    <span class="badge badge-open"><i class="ti ti-lock-open" style="font-size:11px"></i> Débloqué</span>
                <?php else: ?>
                    <span class="badge badge-locked"><i class="ti ti-lock" style="font-size:11px"></i> Verrouillé</span>
                <?php endif; ?>
            </div>

            <div class="card-level"><?= $niv ?></div>
            <div class="card-name"><?= $niv ?> · <?= $info['name'] ?></div>
            <div class="card-msg-count">
                <i class="ti ti-message-circle" style="font-size:13px"></i>
                <?= $msgs ?> message<?= $msgs !== 1 ? 's' : '' ?>
            </div>

            <?php if (!$isOpen && $prevNiv): ?>
            <div class="lock-info">
                ⚡ Pour accéder à ce forum, passe le test
                <strong><?= $prevNiv ?> → <?= $niv ?></strong> avec au moins <strong>70%</strong>.
                <?php if ($prevScore): ?>
                    <br>Ton meilleur score <?= $prevNiv ?> : <strong><?= round($prevScore['pct']) ?>%</strong>
                <?php endif; ?>
            </div>
            <?php elseif ($isOpen && $score): ?>
            <div class="score-bar-wrap">
                <div class="score-bar-label">
                    <span>Score test <?= $niv ?></span>
                    <span style="font-weight:700;color:<?= $hasPassed ? '#16a34a' : $info['color'] ?>"><?= round($score['pct']) ?>% <?= $hasPassed ? '✓' : '' ?></span>
                </div>
                <div class="score-bar">
                    <div class="score-bar-fill" data-w="<?= min(round($score['pct']),100) ?>" style="width:0%"></div>
                </div>
            </div>
            <?php endif; ?>

            <div class="btn-row">
                <?php if ($isOpen): ?>
                    <a href="chat.php?niveau=<?= urlencode($niv) ?>" class="btn-join primary" style="flex:2">
                        Rejoindre <i class="ti ti-arrow-right"></i>
                    </a>
                    <a href="test.php?niveau=<?= urlencode($niv) ?>" class="btn-join outline" style="flex:1;font-size:11px">
                        Test <i class="ti ti-clipboard-check"></i>
                    </a>
                <?php else: ?>
                    <?php if ($prevNiv): ?>
                    <a href="test.php?niveau=<?= urlencode($prevNiv) ?>" class="btn-join outline" style="border-color:<?= $info['color'] ?>;color:<?= $info['color'] ?>">
                        Passer le test <?= $prevNiv ?> <i class="ti ti-arrow-right"></i>
                    </a>
                    <?php else: ?>
                    <button class="btn-join disabled" disabled>
                        <i class="ti ti-lock"></i> Verrouillé
                    </button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <?php if (!$isOpen && $prevNiv): ?>
            <p style="font-size:11px;color:var(--text-muted);margin-top:8px;text-align:center">
                <i class="ti ti-lock"></i> Finissez <?= $prevNiv ?> d'abord
            </p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        </div>
    </main>
</div>

</div>
<!-- Fin Forum Grid -->

<script>
// Animate score bars
document.querySelectorAll('.score-bar-fill').forEach(el => {
    setTimeout(() => { el.style.width = el.dataset.w + '%'; }, 400);
});

// Theme
function toggleTheme() {
    const html = document.documentElement;
    const dark = html.getAttribute('data-theme') === 'dark';
    html.setAttribute('data-theme', dark ? 'light' : 'dark');
    document.getElementById('themeIcon').className = dark ? 'ti ti-moon' : 'ti ti-sun';
    document.getElementById('themeLabel').textContent = dark ? 'Sombre' : 'Clair';
    localStorage.setItem('theme', dark ? 'light' : 'dark');
}
// Restore theme
(function(){
    const t = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', t);
    if (t === 'dark') {
        document.getElementById('themeIcon').className = 'ti ti-sun';
        document.getElementById('themeLabel').textContent = 'Clair';
    }
})();

// Sidebar toggle
function toggleSidebar() {
    const sb = document.getElementById('sidebar');
    const ic = document.getElementById('toggleIcon');
    sb.classList.toggle('mini');
    ic.className = sb.classList.contains('mini') ? 'ti ti-chevrons-right' : 'ti ti-chevrons-left';
}
function openMobileMenu() {
    document.getElementById('sidebar').classList.add('mob-open');
    document.getElementById('mobOverlay').classList.add('show');
}
function closeMobileMenu() {
    document.getElementById('sidebar').classList.remove('mob-open');
    document.getElementById('mobOverlay').classList.remove('show');
}

</script>
</body>
</html>
<?php
require_once '../config.php';

// ── Fetch scenarios grouped by level ──────────────────────────────────────────
try {
    $stmt = $pdo->query("SELECT * FROM scenarios ORDER BY level ASC, id ASC");
    $all  = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("DB Error: " . $e->getMessage());
}

// Group & limit to 10 per level
$levels = ['A1','A2','B1','B2'];
$grouped = [];
foreach ($levels as $lv) {
    $grouped[$lv] = array_values(array_filter($all, fn($s) => ($s['level'] ?? 'A1') === $lv));
    // if DB has more than 10, cap it
    if (count($grouped[$lv]) > 10) $grouped[$lv] = array_slice($grouped[$lv], 0, 10);
}

$levelMeta = [
    'A1' => ['label'=>'A1','name'=>'Beginner',      'color'=>'#34D399','shadow'=>'rgba(52,211,153,.25)'],
    'A2' => ['label'=>'A2','name'=>'Elementary',     'color'=>'#38BDF8','shadow'=>'rgba(56,189,248,.25)'],
    'B1' => ['label'=>'B1','name'=>'Intermediate',   'color'=>'#FBBF24','shadow'=>'rgba(251,191,36,.25)'],
    'B2' => ['label'=>'B2','name'=>'Upper-Intermediate','color'=>'#F87171','shadow'=>'rgba(248,113,113,.25)'],
];

// Unsplash fallback images per scenario theme (keyword → photo)
$fallbackImages = [
    'restaurant' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=600&q=80',
    'cafe'       => 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?w=600&q=80',
    'arzt'       => 'https://images.unsplash.com/photo-1579684385127-1ef15d508118?w=600&q=80',
    'bahnhof'    => 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?w=600&q=80',
    'supermarkt' => 'https://images.unsplash.com/photo-1534723452862-4c874018d66d?w=600&q=80',
    'hotel'      => 'https://images.unsplash.com/photo-1445019980597-93fa8acb246c?w=600&q=80',
    'bank'       => 'https://images.unsplash.com/photo-1601597111158-2fceff292cdc?w=600&q=80',
    'schule'     => 'https://images.unsplash.com/photo-1580582932707-520aed937b7b?w=600&q=80',
    'job'        => 'https://images.unsplash.com/photo-1521737711867-e3b97375f902?w=600&q=80',
    'wohnung'    => 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=600&q=80',
    'default'    => 'https://images.unsplash.com/photo-1467533003447-e295ff1b0435?w=600&q=80',
];
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>DeutschWelt – Scenarios</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<style>
/* ── Reset & Tokens ───────────────────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --ink-900: #0A0F1E;
    --ink-800: #0F1629;
    --ink-700: #151D35;
    --ink-600: #1C2540;
    --ink-400: #2E3C5A;
    --ink-300: #3D4F6E;
    --text-1:  #F0F4FF;
    --text-2:  #8896B3;
    --text-3:  #5C6B88;
    --border:  rgba(255,255,255,.06);
    --border2: rgba(255,255,255,.10);
    --radius:  14px;
    --sidebar: 220px;
}

html { height: 100%; }
body {
    height: 100%; background: var(--ink-900);
    color: var(--text-1); font-family: 'Outfit', sans-serif;
    display: flex; overflow: hidden;
}

/* ── Sidebar ─────────────────────────────────────────────────────────────── */
.sidebar {
    width: var(--sidebar); flex-shrink: 0;
    background: var(--ink-800);
    border-right: 1px solid var(--border);
    display: flex; flex-direction: column;
    height: 100vh; overflow: hidden;
    padding: 0 0 24px;
}

.sidebar-logo {
    padding: 26px 20px 22px;
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; gap: 11px;
}
.logo-mark {
    width: 34px; height: 34px; border-radius: 10px; flex-shrink: 0;
    background: linear-gradient(135deg,#6366F1,#8B5CF6);
    display: flex; align-items: center; justify-content: center; font-size: 15px;
}
.logo-text { font-size: 17px; font-weight: 800; letter-spacing: -.3px; }
.logo-text span { color: #8B8CF8; }

.nav { padding: 20px 12px; flex: 1; display: flex; flex-direction: column; gap: 2px; }
.nav-item {
    display: flex; align-items: center; gap: 11px;
    padding: 10px 12px; border-radius: 10px; cursor: pointer;
    font-size: 14px; font-weight: 500; color: var(--text-2);
    text-decoration: none; transition: all .15s; border: none; background: none; width: 100%;
    font-family: 'Outfit', sans-serif;
}
.nav-item:hover { background: rgba(255,255,255,.04); color: var(--text-1); }
.nav-item.active {
    background: rgba(99,102,241,.12);
    color: #818CF8;
}
.nav-item.active .nav-icon { color: #818CF8; }
.nav-icon { font-size: 18px; width: 20px; flex-shrink: 0; display: flex; align-items: center; }

.sidebar-bottom {
    padding: 0 12px;
    display: flex; flex-direction: column; gap: 2px;
}
.user-chip {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 12px; border-radius: 10px;
    background: rgba(255,255,255,.03); border: 1px solid var(--border);
    margin: 0 0 0; cursor: pointer; transition: .15s;
}
.user-chip:hover { background: rgba(255,255,255,.06); }
.user-avatar {
    width: 30px; height: 30px; border-radius: 8px; flex-shrink: 0;
    background: linear-gradient(135deg,#6366F1,#C084FC);
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 700;
}
.user-name { font-size: 13px; font-weight: 600; }
.user-role { font-size: 11px; color: var(--text-3); }

/* ── Main ─────────────────────────────────────────────────────────────────── */
.main {
    flex: 1; min-width: 0; display: flex; flex-direction: column;
    height: 100vh; overflow: hidden;
}

/* Header */
.header {
    padding: 20px 32px;
    border-bottom: 1px solid var(--border);
    background: var(--ink-800);
    display: flex; align-items: center; justify-content: space-between;
    flex-shrink: 0;
}
.header-left h1 {
    font-size: 22px; font-weight: 800; letter-spacing: -.4px; margin-bottom: 2px;
}
.header-left p { font-size: 13px; color: var(--text-2); }
.header-right { display: flex; align-items: center; gap: 10px; }
.hdr-pill {
    display: flex; align-items: center; gap: 6px;
    background: rgba(255,255,255,.04); border: 1px solid var(--border2);
    padding: 8px 14px; border-radius: 9px; font-size: 13px; color: var(--text-2);
    cursor: pointer; transition: .15s; font-family: 'Outfit', sans-serif;
}
.hdr-pill:hover { color: var(--text-1); background: rgba(255,255,255,.07); }
.search-wrap {
    position: relative; display: flex; align-items: center;
}
.search-wrap .ti { position: absolute; left: 11px; color: var(--text-3); font-size: 15px; }
.search-inp {
    background: rgba(255,255,255,.04); border: 1px solid var(--border2);
    color: var(--text-1); padding: 8px 14px 8px 33px; border-radius: 9px;
    font-size: 13px; outline: none; width: 200px; transition: .2s;
    font-family: 'Outfit', sans-serif;
}
.search-inp:focus { border-color: rgba(99,102,241,.4); background: rgba(255,255,255,.06); width: 240px; }
.search-inp::placeholder { color: var(--text-3); }

/* Scrollable content */
.content { flex: 1; overflow-y: auto; padding: 28px 32px 48px; }
.content::-webkit-scrollbar { width: 5px; }
.content::-webkit-scrollbar-thumb { background: var(--ink-400); border-radius: 4px; }

/* ── Level Tabs ───────────────────────────────────────────────────────────── */
.tabs-row {
    display: flex; align-items: center; gap: 8px; margin-bottom: 28px;
    flex-wrap: wrap;
}
.tab-btn {
    display: flex; align-items: center; gap: 8px;
    padding: 9px 20px; border-radius: 10px; cursor: pointer;
    font-size: 14px; font-weight: 600; border: 1px solid var(--border2);
    background: var(--ink-700); color: var(--text-2);
    transition: all .2s; font-family: 'Outfit', sans-serif;
    position: relative; overflow: hidden;
}
.tab-btn::before {
    content: ''; position: absolute; inset: 0; opacity: 0; transition: opacity .2s;
}
.tab-btn:hover { color: var(--text-1); border-color: rgba(255,255,255,.12); }
.tab-btn.active {
    color: #fff; border-color: transparent;
}
.tab-lvl {
    font-family: 'DM Mono', monospace; font-size: 13px; font-weight: 500;
    padding: 2px 7px; border-radius: 5px; background: rgba(0,0,0,.25);
}
.tab-sub { font-size: 12px; font-weight: 400; opacity: .75; }

/* Colors per level */
.tab-btn[data-lv="A1"].active { background: linear-gradient(135deg,#059669,#34D399); }
.tab-btn[data-lv="A2"].active { background: linear-gradient(135deg,#0284C7,#38BDF8); }
.tab-btn[data-lv="B1"].active { background: linear-gradient(135deg,#D97706,#FBBF24); color: #1a1000; }
.tab-btn[data-lv="B2"].active { background: linear-gradient(135deg,#DC2626,#F87171); }

/* Section header */
.section-bar {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 20px;
}
.section-bar-left { display: flex; align-items: center; gap: 12px; }
.section-lvl-badge {
    font-family: 'DM Mono', monospace; font-size: 12px; font-weight: 500;
    padding: 4px 10px; border-radius: 7px;
}
.section-title { font-size: 18px; font-weight: 700; letter-spacing: -.3px; }
.section-count { font-size: 13px; color: var(--text-3); }

/* ── Cards Grid ───────────────────────────────────────────────────────────── */
.cards-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
}
@media (max-width: 1200px) { .cards-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 900px)  { .cards-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 580px)  { .cards-grid { grid-template-columns: 1fr; } }

.card {
    background: var(--ink-700); border: 1px solid var(--border);
    border-radius: var(--radius); overflow: hidden;
    display: flex; flex-direction: column;
    text-decoration: none; color: inherit;
    transition: transform .22s cubic-bezier(.22,.68,0,1.2), border-color .2s, box-shadow .2s;
    cursor: pointer;
}
.card:hover {
    transform: translateY(-4px);
    border-color: rgba(255,255,255,.1);
    box-shadow: 0 16px 40px rgba(0,0,0,.4);
}

/* Image */
.card-thumb {
    position: relative; height: 140px; overflow: hidden;
    background: var(--ink-600);
}
.card-thumb img {
    width: 100%; height: 100%; object-fit: cover;
    transition: transform .4s ease;
    display: block;
}
.card:hover .card-thumb img { transform: scale(1.05); }
.card-thumb .fallback-icon {
    display: none; position: absolute; inset: 0;
    align-items: center; justify-content: center;
    font-size: 36px; background: var(--ink-600);
}
.card-thumb img.errored { display: none; }
.card-thumb img.errored + .fallback-icon { display: flex; }
.thumb-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(to bottom, transparent 50%, rgba(15,22,41,.8) 100%);
}

/* Category chip on image */
.card-cat {
    position: absolute; top: 10px; left: 10px;
    padding: 3px 9px; border-radius: 6px;
    font-size: 10px; font-weight: 700; letter-spacing: .4px; text-transform: uppercase;
    backdrop-filter: blur(6px);
}

/* Body */
.card-body { padding: 14px; display: flex; flex-direction: column; flex: 1; gap: 10px; }
.card-title { font-size: 14px; font-weight: 700; line-height: 1.35; letter-spacing: -.1px; }
.card-meta { display: flex; align-items: center; gap: 6px; }
.diff-dots { display: flex; gap: 3px; }
.diff-dot {
    width: 6px; height: 6px; border-radius: 50%;
    background: rgba(255,255,255,.15);
}
.diff-label { font-size: 11px; color: var(--text-3); }

/* CTA */
.card-cta {
    display: flex; align-items: center; justify-content: center; gap: 6px;
    padding: 9px; border-radius: 9px; margin-top: auto;
    font-size: 13px; font-weight: 600;
    border: 1px solid rgba(255,255,255,.08);
    background: rgba(255,255,255,.04);
    color: var(--text-2); transition: all .2s;
}
.card:hover .card-cta {
    background: rgba(99,102,241,.18); border-color: rgba(99,102,241,.35);
    color: #a5b4fc;
}

/* ── Level panels ─────────────────────────────────────────────────────────── */
.level-panel { display: none; }
.level-panel.active { display: block; }

/* ── Responsive sidebar ───────────────────────────────────────────────────── */
@media (max-width: 768px) {
    .sidebar { display: none; }
    .main { height: auto; overflow: visible; }
    body { overflow: auto; }
    .content { padding: 20px 16px 40px; }
    .header { padding: 16px; }
}

/* ── Animations ───────────────────────────────────────────────────────────── */
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
}
.cards-grid .card {
    animation: fadeUp .3s ease both;
}
<?php for ($i=1;$i<=10;$i++): ?>
.cards-grid .card:nth-child(<?=$i?>) { animation-delay: <?=($i-1)*.04?>s; }
<?php endfor; ?>
</style>
</head>
<body>

<!-- ── Sidebar ────────────────────────────────────────────────────────────── -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-mark">🗺</div>
        <span class="logo-text">Deutsch<span>Welt</span></span>
    </div>

    <nav class="nav">
        <a href="#" class="nav-item">
            <span class="nav-icon"><i class="ti ti-layout-dashboard"></i></span> Dashboard
        </a>
        <a href="index.php" class="nav-item active">
            <span class="nav-icon"><i class="ti ti-messages"></i></span> Scenarios
        </a>
        <a href="#" class="nav-item">
            <span class="nav-icon"><i class="ti ti-chart-bar"></i></span> Progress
        </a>
        <a href="#" class="nav-item">
            <span class="nav-icon"><i class="ti ti-settings"></i></span> Settings
        </a>
    </nav>

    <div class="sidebar-bottom">
        <div class="user-chip">
            <div class="user-avatar">U</div>
            <div>
                <div class="user-name">Student</div>
                <div class="user-role">Free Plan</div>
            </div>
        </div>
    </div>
</aside>

<!-- ── Main ───────────────────────────────────────────────────────────────── -->
<div class="main">

    <!-- Header -->
    <header class="header">
        <div class="header-left">
            <h1>Scenarios</h1>
            <p>Choose a scenario and practice real-life German conversations</p>
        </div>
        <div class="header-right">
            <div class="search-wrap">
                <i class="ti ti-search"></i>
                <input type="text" class="search-inp" id="search-inp" placeholder="Search scenarios…">
            </div>
            <button class="hdr-pill"><i class="ti ti-filter"></i> Filter</button>
        </div>
    </header>

    <!-- Scrollable content -->
    <div class="content">

        <!-- Level Tabs -->
        <div class="tabs-row" id="tabs-row">
            <?php foreach ($levelMeta as $lv => $m): ?>
            <button class="tab-btn <?= $lv==='A1'?'active':'' ?>" data-lv="<?= $lv ?>" onclick="switchLevel('<?= $lv ?>', this)">
                <span class="tab-lvl"><?= $lv ?></span>
                <span class="tab-sub"><?= $m['name'] ?></span>
            </button>
            <?php endforeach; ?>
        </div>

        <!-- Level Panels -->
        <?php foreach ($levelMeta as $lv => $m):
            $scenarios = $grouped[$lv] ?? [];
        ?>
        <div class="level-panel <?= $lv==='A1'?'active':'' ?>" id="panel-<?= $lv ?>">

            <div class="section-bar">
                <div class="section-bar-left">
                    <span class="section-lvl-badge"
                          style="background:<?= $m['color'] ?>22;color:<?= $m['color'] ?>;border:1px solid <?= $m['color'] ?>44;">
                        <?= $lv ?>
                    </span>
                    <span class="section-title"><?= $m['name'] ?></span>
                </div>
                <span class="section-count"><?= count($scenarios) ?> of 10 scenarios</span>
            </div>

            <div class="cards-grid" id="grid-<?= $lv ?>">
                <?php if ($scenarios): ?>
                    <?php foreach ($scenarios as $i => $s):
                        // Pick image: use DB image or find a fallback
                        $img = !empty($s['image']) ? htmlspecialchars($s['image']) : $fallbackImages['default'];
                        // Category & difficulty from DB or smart defaults
                        $cat   = htmlspecialchars($s['category']   ?? 'Conversation');
                        $diff  = (int)($s['difficulty'] ?? ($lv==='A1'?1:($lv==='A2'?2:($lv==='B1'?3:4))));
                        $maxD  = 5;
                    ?>
                    <a href="play.php?id=<?= (int)$s['id'] ?>" class="card">
                        <div class="card-thumb">
                            <img src="<?= $img ?>" alt="" loading="lazy"
                                 onerror="this.classList.add('errored')">
                            <div class="fallback-icon">🗣️</div>
                            <div class="thumb-overlay"></div>
                            <span class="card-cat"
                                  style="background:<?= $m['color'] ?>33;color:<?= $m['color'] ?>;border:1px solid <?= $m['color'] ?>55;">
                                <?= $cat ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="card-title"><?= htmlspecialchars($s['title']) ?></div>
                            <div class="card-meta">
                                <div class="diff-dots">
                                    <?php for ($d=1;$d<=$maxD;$d++): ?>
                                    <div class="diff-dot"
                                         style="<?= $d<=$diff?'background:'.$m['color'].';':''; ?>">
                                    </div>
                                    <?php endfor; ?>
                                </div>
                                <span class="diff-label">
                                    <?= ['','Easy','Easy','Medium','Hard','Expert'][$diff] ?? 'Medium' ?>
                                </span>
                            </div>
                            <div class="card-cta">
                                Start <i class="ti ti-arrow-right"></i>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>

                    <?php // Fill empty slots up to 10
                          $empty = 10 - count($scenarios);
                          for ($e=0;$e<$empty;$e++): ?>
                    <div class="card" style="opacity:.35;pointer-events:none;">
                        <div class="card-thumb" style="background:var(--ink-600);">
                            <div class="fallback-icon" style="display:flex;font-size:28px;color:var(--text-3);">
                                <i class="ti ti-lock"></i>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="card-title" style="color:var(--text-3);">Coming soon</div>
                            <div class="card-cta" style="opacity:.4;">Locked</div>
                        </div>
                    </div>
                    <?php endfor; ?>

                <?php else: ?>
                    <div style="grid-column:1/-1;padding:60px;text-align:center;color:var(--text-3);font-size:15px;">
                        <i class="ti ti-database-off" style="font-size:32px;display:block;margin-bottom:12px;"></i>
                        No scenarios found for level <?= $lv ?>.<br>
                        <span style="font-size:13px;">Add them in your database.</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>

    </div><!-- /content -->
</div><!-- /main -->

<script>
function switchLevel(lv, btn) {
    // Tabs
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    // Panels
    document.querySelectorAll('.level-panel').forEach(p => p.classList.remove('active'));
    const panel = document.getElementById('panel-' + lv);
    panel.classList.add('active');
    // Re-trigger card animations
    panel.querySelectorAll('.card').forEach((c, i) => {
        c.style.animation = 'none';
        c.offsetHeight; // reflow
        c.style.animation = '';
        c.style.animationDelay = (i * 0.04) + 's';
    });
}

// Search filter
document.getElementById('search-inp').addEventListener('input', function() {
    const q = this.value.toLowerCase().trim();
    document.querySelectorAll('.card[href]').forEach(card => {
        const title = card.querySelector('.card-title')?.textContent.toLowerCase() || '';
        card.style.display = (!q || title.includes(q)) ? '' : 'none';
    });
});
</script>
</body>
</html>
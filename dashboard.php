<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

$userId   = $_SESSION['user_id'];
$username = $_SESSION['username'];

$stmt = $pdo->prepare("SELECT level, level_up_this_month, study_time_hours, study_weekly_increase,
                              words_learned, words_today, success_rate, success_rate_change,
                              lessons_completed
                       FROM user_stats WHERE user_id = ?");
$stmt->execute([$userId]);
$stats = $stmt->fetch();

if (!$stats) {
    $insert = $pdo->prepare("INSERT INTO user_stats (user_id, level, level_up_this_month, study_time_hours, study_weekly_increase, words_learned, words_today, success_rate, success_rate_change, lessons_completed)
                              VALUES (?, 'B1', 2, 48, 3, 312, 18, 87, -2, 0)");
    $insert->execute([$userId]);
    $stmt->execute([$userId]);
    $stats = $stmt->fetch();
}

$level            = $stats['level'];
$levelUp          = $stats['level_up_this_month'];
$studyTime        = $stats['study_time_hours'];
$studyWeekly      = $stats['study_weekly_increase'];
$words            = $stats['words_learned'];
$wordsToday       = $stats['words_today'];
$success          = $stats['success_rate'];
$successChange    = $stats['success_rate_change'];
$lessonsCompleted = $stats['lessons_completed'] ?? 0;
$initials         = mb_strtoupper(mb_substr($username, 0, 2));
?>
<!DOCTYPE html>
<html lang="de" data-theme="light" data-lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard – LinguaFlow</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        a { text-decoration: none; color: inherit; }
        button { font-family: inherit; cursor: pointer; border: none; background: none; }

        /* ══ LIGHT MODE ══ */
        :root {
            --bg-base:        #E8FAF7;
            --bg-surface:     #FFFFFF;
            --bg-muted:       #D0F4EE;

            --sidebar-bg:     #0A2030;
            --sidebar-hover:  rgba(45,212,191,.10);
            --sidebar-active: rgba(45,212,191,.20);
            --sidebar-text:   #7AACB8;
            --sidebar-head:   #3A7080;

            --text-primary:   #062030;
            --text-secondary: #234858;
            --text-muted:     #6A9AA8;

            --accent:        #0D9488;
            --accent-light:  #CCFBF1;
            --accent-text:   #0F766E;

            --teal:          #0D9488;
            --teal-light:    #CCFBF1;
            --emerald:       #059669;
            --emerald-light: #D1FAE5;
            --violet:        #7C3AED;
            --violet-light:  #EDE9FE;
            --rose:          #E11D48;
            --rose-light:    #FFE4E6;
            --amber:         #D97706;
            --amber-light:   #FEF3C7;

            --border:        rgba(13,148,136,.12);
            --border-strong: rgba(13,148,136,.22);

            --radius-sm:   8px;
            --radius-md:   12px;
            --radius-lg:   16px;
            --radius-xl:   20px;

            --shadow:      0 1px 4px rgba(13,148,136,.08), 0 4px 14px rgba(13,148,136,.06);
            --shadow-h:    0 8px 28px rgba(13,148,136,.20);

            --dur:         .24s cubic-bezier(.4,0,.2,1);
            --sw:          252px;
            --sm:          64px;
        }

        /* ══ DARK MODE ══ */
        [data-theme="dark"] {
            --bg-base:       #061820;
            --bg-surface:    #0C2530;
            --bg-muted:      #102E3A;
            --sidebar-bg:    #040E14;
            --text-primary:  #E0FAF6;
            --text-secondary:#90C4C0;
            --text-muted:    #3A6870;
            --accent:        #2DD4BF;
            --accent-light:  #0A2828;
            --accent-text:   #5EEAD4;
            --teal-light:    #061E2A;
            --emerald-light: #061A14;
            --violet-light:  #160A30;
            --rose-light:    #2A0812;
            --amber-light:   #221200;
            --border:        rgba(45,212,191,.10);
            --border-strong: rgba(45,212,191,.20);
            --shadow:      0 1px 4px rgba(0,0,0,.5), 0 4px 14px rgba(0,0,0,.4);
            --shadow-h:    0 8px 28px rgba(13,148,136,.35);
        }

        /* ── BASE ── */
        html, body { height: 100%; overflow: hidden; }
        body {
            font-family: 'Sora', sans-serif;
            background: var(--bg-base);
            color: var(--text-primary);
            display: flex;
            transition: background var(--dur), color var(--dur);
        }

        /* ══ SIDEBAR ══ */
        .sidebar {
            width: var(--sw); min-width: var(--sw);
            background: var(--sidebar-bg);
            display: flex; flex-direction: column;
            padding: 22px 0 18px;
            transition: width var(--dur), min-width var(--dur);
            z-index: 20; overflow: hidden;
        }
        .sidebar.mini { width: var(--sm); min-width: var(--sm); }

        .sb-logo {
            display: flex; align-items: center; gap: 11px;
            padding: 0 18px 24px; white-space: nowrap;
        }
        .sidebar.mini .sb-logo { justify-content: center; padding: 0 0 24px; }

        .logo-mark {
            width: 36px; height: 36px; min-width: 36px;
            border-radius: 10px;
            background: linear-gradient(135deg, #0D9488, #0891B2);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 18px;
        }
        .logo-text {
            font-size: 15px; font-weight: 800; color: #fff;
            letter-spacing: -.4px;
            transition: opacity var(--dur), transform var(--dur);
        }
        .sidebar.mini .logo-text { opacity: 0; pointer-events: none; transform: translateX(-6px); }

        .sb-divider { height: 1px; background: rgba(255,255,255,.06); margin: 0 14px 12px; }

        .sb-label {
            font-size: 10px; font-weight: 700; letter-spacing: 1.4px;
            text-transform: uppercase; color: var(--sidebar-head);
            padding: 0 20px 6px; white-space: nowrap;
            transition: opacity var(--dur);
        }
        .sidebar.mini .sb-label { opacity: 0; }

        .nav-item {
            display: flex; align-items: center; gap: 11px;
            padding: 9px 12px; margin: 2px 8px;
            border-radius: var(--radius-sm);
            color: var(--sidebar-text); font-size: 13px; font-weight: 500;
            cursor: pointer; white-space: nowrap; position: relative;
            transition: background var(--dur), color var(--dur);
        }
        .sidebar.mini .nav-item { justify-content: center; padding: 9px; }
        .nav-item:hover  { background: var(--sidebar-hover); color: #cbd5e1; }
        .nav-item.active { background: var(--sidebar-active); color: #fff; }
        .nav-item .ni    { font-size: 18px; min-width: 20px; color: #4a5568; transition: color var(--dur); }
        .nav-item:hover .ni,
        .nav-item.active .ni { color: #2DD4BF; }
        .nav-item.active::before {
            content: ''; position: absolute; left: 0; top: 22%; bottom: 22%;
            width: 3px; border-radius: 0 3px 3px 0; background: #2DD4BF;
        }
        .nav-lbl { transition: opacity var(--dur), transform var(--dur); }
        .sidebar.mini .nav-lbl { opacity: 0; pointer-events: none; transform: translateX(-4px); }

        .nav-tip {
            position: absolute; left: calc(var(--sm) - 2px);
            background: #0A2030; color: #E0FAF6;
            font-size: 12px; font-weight: 600;
            padding: 5px 11px; border-radius: var(--radius-sm);
            white-space: nowrap; pointer-events: none;
            opacity: 0; transform: translateX(-5px);
            transition: opacity .16s, transform .16s;
            box-shadow: 0 4px 14px rgba(0,0,0,.3); z-index: 99;
        }
        .sidebar.mini .nav-item:hover .nav-tip { opacity: 1; transform: translateX(0); }

        .sb-spacer { flex: 1; }
        .sb-toggle {
            width: 30px; height: 30px; border-radius: 7px;
            background: rgba(255,255,255,.06);
            display: flex; align-items: center; justify-content: center;
            color: #8892A4; font-size: 15px; margin: 10px auto 0;
            transition: background var(--dur), color var(--dur);
        }
        .sb-toggle:hover { background: rgba(255,255,255,.13); color: #fff; }

        /* ══ TOPBAR ══ */
        .main { flex: 1; display: flex; flex-direction: column; overflow: hidden; min-width: 0; }
        .topbar {
            display: flex; align-items: center; justify-content: space-between;
            padding: 13px 28px;
            background: var(--bg-surface); border-bottom: 1px solid var(--border);
            transition: background var(--dur), border-color var(--dur);
            gap: 12px;
        }
        .tb-title   { font-size: 17px; font-weight: 800; letter-spacing: -.4px; }
        .tb-sub     { font-size: 12px; color: var(--text-muted); margin-top: 2px; display: flex; align-items: center; gap: 5px; }
        .tb-right   { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }

        /* Language switcher */
        .lang-switcher {
            display: flex; align-items: center; gap: 2px;
            background: var(--bg-muted); border: 1px solid var(--border-strong);
            border-radius: 20px; padding: 3px;
        }
        .lang-btn {
            padding: 4px 10px; border-radius: 16px;
            font-size: 11.5px; font-weight: 700;
            color: var(--text-muted);
            transition: all var(--dur); cursor: pointer;
            letter-spacing: .3px;
        }
        .lang-btn.active {
            background: var(--accent);
            color: #fff;
        }
        .lang-btn:not(.active):hover { color: var(--text-primary); }

        /* Theme toggle */
        .theme-btn {
            display: flex; align-items: center; gap: 6px;
            background: var(--bg-muted); border: 1px solid var(--border-strong);
            border-radius: 20px; padding: 6px 13px;
            font-size: 12px; font-weight: 600; color: var(--text-secondary);
            transition: all var(--dur);
        }
        .theme-btn:hover { border-color: var(--accent); color: var(--accent); }
        .theme-btn i { font-size: 15px; }

        .avatar {
            width: 34px; height: 34px; border-radius: 50%;
            background: linear-gradient(135deg, #0D9488, #0891B2);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 12px; font-weight: 800;
            cursor: pointer; flex-shrink: 0;
        }
        .btn-logout {
            display: flex; align-items: center; gap: 6px;
            border: 1px solid var(--rose); border-radius: var(--radius-sm);
            padding: 6px 13px; font-size: 12px; font-weight: 600; color: var(--rose);
            transition: all var(--dur);
        }
        .btn-logout:hover { background: var(--rose-light); }

        /* ══ CONTENT ══ */
        .content {
            flex: 1; overflow-y: auto; padding: 26px 28px;
            background: var(--bg-base); transition: background var(--dur);
        }
        .content::-webkit-scrollbar { width: 4px; }
        .content::-webkit-scrollbar-thumb { background: var(--border-strong); border-radius: 4px; }

        /* ── Stat Cards ── */
        .stats-grid {
            display: grid; grid-template-columns: repeat(4,1fr);
            gap: 14px; margin-bottom: 18px;
        }
        .stat-card {
            background: var(--bg-surface); border: 1px solid var(--border);
            border-radius: var(--radius-lg); padding: 20px;
            position: relative; overflow: hidden;
            box-shadow: var(--shadow);
            transition: all var(--dur);
            animation: rise .5s ease both;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-h); border-color: var(--card-color); }
        .stat-card::after {
            content: ''; position: absolute;
            top: 0; left: 0; right: 0; height: 3px;
            background: var(--card-color, var(--accent));
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
            transform: scaleX(0); transform-origin: left;
            transition: transform var(--dur);
        }
        .stat-card:hover::after { transform: scaleX(1); }
        .stat-card:nth-child(1) { --card-color:var(--accent);   animation-delay:.04s; }
        .stat-card:nth-child(2) { --card-color:var(--teal);     animation-delay:.09s; }
        .stat-card:nth-child(3) { --card-color:var(--emerald);  animation-delay:.14s; }
        .stat-card:nth-child(4) { --card-color:var(--violet);   animation-delay:.19s; }

        .card-icon {
            width: 40px; height: 40px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 19px; margin-bottom: 13px;
        }
        .card-label {
            font-size: 10.5px; font-weight: 700; letter-spacing: 1px;
            text-transform: uppercase; color: var(--text-muted); margin-bottom: 4px;
        }
        .card-value {
            font-size: 28px; font-weight: 800; letter-spacing: -1.2px;
            color: var(--text-primary); font-variant-numeric: tabular-nums;
            font-family: 'JetBrains Mono', monospace;
        }
        .card-trend {
            display: flex; align-items: center; gap: 4px;
            font-size: 11px; font-weight: 600; margin-top: 5px;
        }
        .card-trend.up   { color: var(--emerald); }
        .card-trend.down { color: var(--rose); }

        /* ── Level Bar ── */
        .level-bar {
            background: var(--bg-surface); border: 1px solid var(--border);
            border-radius: var(--radius-lg); padding: 15px 22px;
            margin-bottom: 18px;
            display: flex; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: 10px;
            box-shadow: var(--shadow);
            animation: rise .5s .26s ease both;
            transition: background var(--dur), border-color var(--dur);
        }
        .level-left { display: flex; align-items: center; gap: 9px; font-size: 14px; font-weight: 700; }
        .level-badge {
            background: var(--accent-light); color: var(--accent);
            font-weight: 800; padding: 3px 14px; border-radius: 20px;
            font-size: 13px; font-family: 'JetBrains Mono', monospace; letter-spacing: .5px;
        }
        .lessons-count { display: flex; align-items: center; gap: 6px; font-size: 13px; color: var(--text-secondary); }
        .lessons-count strong { color: var(--text-primary); font-size: 15px; font-family: 'JetBrains Mono', monospace; }

        /* ── Feature Cards ── */
        .features-grid {
            display: grid; grid-template-columns: repeat(3,1fr); gap: 14px;
        }
        .feature-card {
            background: var(--bg-surface); border: 1px solid var(--border);
            border-radius: var(--radius-xl); padding: 24px;
            display: flex; flex-direction: column; gap: 11px;
            box-shadow: var(--shadow);
            transition: all var(--dur);
            animation: rise .5s ease both;
        }
        .feature-card:nth-child(1) { animation-delay:.10s; }
        .feature-card:nth-child(2) { animation-delay:.17s; }
        .feature-card:nth-child(3) { animation-delay:.24s; }
        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 32px rgba(0,0,0,.11);
            border-color: var(--fc-color, var(--accent));
        }
        .fc-icon {
            width: 48px; height: 48px; border-radius: var(--radius-md);
            display: flex; align-items: center; justify-content: center; font-size: 22px;
        }
        .fc-title       { font-size: 15px; font-weight: 800; letter-spacing: -.2px; }
        .fc-description { font-size: 12.5px; color: var(--text-secondary); line-height: 1.65; flex: 1; }
        .fc-btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 9px 16px; border-radius: var(--radius-sm);
            font-size: 12.5px; font-weight: 700; color: #fff;
            background: var(--fc-color, var(--accent));
            transition: all var(--dur); align-self: flex-start;
        }
        .fc-btn:hover { filter: brightness(1.1); transform: translateX(2px); }

        /* ── Footer ── */
        .page-footer {
            margin-top: 32px; text-align: center;
            font-size: 11.5px; color: var(--text-muted);
            display: flex; align-items: center; justify-content: center; gap: 6px;
            animation: rise .5s .32s ease both;
        }

        /* ══ ANIMATIONS ══ */
        @keyframes rise {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ══ MOBILE OVERLAY ══ */
        .mob-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(6,30,40,.5); z-index: 18;
            backdrop-filter: blur(2px);
        }
        .mob-overlay.show { display: block; }

        .mob-menu-btn {
            display: none; width: 36px; height: 36px;
            border-radius: var(--radius-sm);
            background: var(--bg-muted); border: 1px solid var(--border-strong);
            align-items: center; justify-content: center;
            color: var(--text-secondary); font-size: 20px;
            flex-shrink: 0;
        }

        @media (max-width: 960px) {
            .stats-grid    { grid-template-columns: repeat(2,1fr); }
            .features-grid { grid-template-columns: repeat(2,1fr); }
        }

        @media (max-width: 720px) {
            /* Sidebar slides in as drawer on mobile */
            .sidebar {
                position: fixed; top: 0; left: 0; bottom: 0;
                transform: translateX(-100%);
                transition: transform var(--dur);
                z-index: 19; width: var(--sw) !important; min-width: var(--sw) !important;
            }
            .sidebar.mob-open { transform: translateX(0); }

            /* Main takes full width */
            .main { width: 100%; }

            .mob-menu-btn { display: flex; }
            .sb-toggle    { display: none; }

            .topbar   { padding: 10px 14px; }
            .content  { padding: 14px; }
            .tb-right { gap: 6px; }

            .stats-grid    { grid-template-columns: repeat(2,1fr); gap: 10px; }
            .features-grid { grid-template-columns: 1fr; }

            .btn-logout span    { display: none; }
            .theme-btn span     { display: none; }
        }

        @media (max-width: 480px) {
            .lang-switcher   { display: none; }
            .stats-grid      { grid-template-columns: 1fr 1fr; gap: 8px; }
            .card-value      { font-size: 22px; }
            .stat-card       { padding: 14px; }
            .feature-card    { padding: 16px; }
        }
    </style>
</head>
<body>

<!-- Mobile overlay -->
<div class="mob-overlay" id="mobOverlay" onclick="closeMobileMenu()"></div>

<!-- ══ SIDEBAR ══ -->
<nav class="sidebar" id="sidebar" aria-label="Navigation">
    <div class="sb-logo">
        <div class="logo-mark"><i class="ti ti-language" aria-hidden="true"></i></div>
        <span class="logo-text">LinguaFlow</span>
    </div>

    <div class="sb-divider"></div>
    <span class="sb-label" data-i18n="nav_section">Navigation</span>

    <div class="nav-item active" onclick="setActive(this)" role="button" tabindex="0">
        <i class="ti ti-layout-dashboard ni" aria-hidden="true"></i>
        <span class="nav-lbl" data-i18n="nav_overview">Übersicht</span>
        <span class="nav-tip" data-i18n="nav_overview">Übersicht</span>
    </div>
    <a href="chatbot/index.php" class="nav-item" onclick="setActive(this)" role="button" tabindex="0">
        <i class="ti ti-robot ni" aria-hidden="true"></i>
        <span class="nav-lbl" data-i18n="nav_chatbot">KI-Chatbot</span>
        <span class="nav-tip" data-i18n="nav_chatbot">KI-Chatbot</span>
    </div>
    <div class="nav-item" onclick="setActive(this)" role="button" tabindex="0">
        <i class="ti ti-book-2 ni" aria-hidden="true"></i>
        <span class="nav-lbl" data-i18n="nav_lessons">Lektionen</span>
        <span class="nav-tip" data-i18n="nav_lessons">Lektionen</span>
    </div>
    <a href="forum/index.php" class="nav-item" onclick="setActive(this)" role="button" tabindex="0">
        <i class="ti ti-messages ni" aria-hidden="true"></i>
        <span class="nav-lbl" data-i18n="nav_forum">Forum</span>
        <span class="nav-tip" data-i18n="nav_forum">Forum</span>
    </a>
   <a href="scenarios/index.php" class="nav-item" onclick="setActive(this)" role="button" tabindex="0">
        <i class="ti ti-world ni" aria-hidden="true"></i>
        <span class="nav-lbl" data-i18n="nav_scenarios">Szenarien</span>
        <span class="nav-tip" data-i18n="nav_scenarios">Szenarien</span>
    </a>
    <div class="nav-item" onclick="setActive(this)" role="button" tabindex="0">
        <i class="ti ti-chart-bar ni" aria-hidden="true"></i>
        <span class="nav-lbl" data-i18n="nav_stats">Statistiken</span>
        <span class="nav-tip" data-i18n="nav_stats">Statistiken</span>
    </div>

    <div class="sb-spacer"></div>
    <div class="sb-divider" style="margin-bottom:10px"></div>
    <span class="sb-label" data-i18n="nav_account">Konto</span>

    <div class="nav-item" onclick="setActive(this)" role="button" tabindex="0">
        <i class="ti ti-settings ni" aria-hidden="true"></i>
        <span class="nav-lbl" data-i18n="nav_settings">Einstellungen</span>
        <span class="nav-tip" data-i18n="nav_settings">Einstellungen</span>
    </div>

    <button class="sb-toggle" id="sidebarToggle" onclick="toggleSidebar()" aria-label="Toggle sidebar">
        <i class="ti ti-chevrons-left" id="toggleIcon" aria-hidden="true"></i>
    </button>
</nav>

<!-- ══ MAIN AREA ══ -->
<div class="main">

    <!-- Topbar -->
    <header class="topbar">
        <div style="display:flex;align-items:center;gap:12px">
            <button class="mob-menu-btn" onclick="openMobileMenu()" aria-label="Menu">
                <i class="ti ti-menu-2" aria-hidden="true"></i>
            </button>
            <div>
            <div class="tb-title">
                <i class="ti ti-layout-dashboard" style="font-size:16px;vertical-align:-2px;margin-right:6px;color:var(--accent)"></i>
                <span data-i18n="page_title">Übersicht</span>
            </div>
            <div class="tb-sub">
                <i class="ti ti-sun" aria-hidden="true"></i>
                <span data-i18n="greeting">Guten Morgen</span>, <strong><?= htmlspecialchars($username) ?></strong>
            </div>
            </div>
        </div>
        <div class="tb-right">

            <!-- Language Switcher -->
            <div class="lang-switcher" role="group" aria-label="Language">
                <button class="lang-btn active" data-lang-target="de" onclick="setLang('de')">DE</button>
                <button class="lang-btn" data-lang-target="fr" onclick="setLang('fr')">FR</button>
                <button class="lang-btn" data-lang-target="en" onclick="setLang('en')">EN</button>
            </div>

            <!-- Theme Toggle -->
            <button class="theme-btn" onclick="toggleTheme()" id="themeBtn" aria-label="Toggle theme">
                <i class="ti ti-moon" id="themeIcon" aria-hidden="true"></i>
                <span id="themeLabel" data-i18n="theme_dark">Dunkelmodus</span>
            </button>

            <div class="avatar" title="<?= htmlspecialchars($username) ?>">
                <?= htmlspecialchars($initials) ?>
            </div>

            <a href="auth/logout.php" class="btn-logout">
                <i class="ti ti-logout" aria-hidden="true"></i>
                <span data-i18n="logout">Abmelden</span>
            </a>
        </div>
    </header>

    <!-- Content -->
    <main class="content">

        <!-- ── Stat Cards ── -->
        <div class="stats-grid">

            <div class="stat-card">
                <div class="card-icon" style="background:var(--accent-light)">
                    <i class="ti ti-chart-line" style="color:var(--accent)" aria-hidden="true"></i>
                </div>
                <div class="card-label" data-i18n="stat_level">Aktuelles Niveau</div>
                <div class="card-value"><?= htmlspecialchars($level) ?></div>
                <div class="card-trend up">
                    <i class="ti ti-arrow-up" aria-hidden="true"></i>
                    +<?= (int)$levelUp ?> <span data-i18n="stat_level_trend">diesen Monat</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="card-icon" style="background:var(--teal-light)">
                    <i class="ti ti-clock" style="color:var(--teal)" aria-hidden="true"></i>
                </div>
                <div class="card-label" data-i18n="stat_time">Lernzeit</div>
                <div class="card-value"><?= (int)$studyTime ?>h</div>
                <div class="card-trend up">
                    <i class="ti ti-arrow-up" aria-hidden="true"></i>
                    +<?= (int)$studyWeekly ?>h <span data-i18n="stat_time_trend">diese Woche</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="card-icon" style="background:var(--emerald-light)">
                    <i class="ti ti-book-open" style="color:var(--emerald)" aria-hidden="true"></i>
                </div>
                <div class="card-label" data-i18n="stat_words">Gelernte Wörter</div>
                <div class="card-value"><?= number_format((int)$words) ?></div>
                <div class="card-trend up">
                    <i class="ti ti-arrow-up" aria-hidden="true"></i>
                    +<?= (int)$wordsToday ?> <span data-i18n="stat_words_trend">heute</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="card-icon" style="background:var(--violet-light)">
                    <i class="ti ti-percentage" style="color:var(--violet)" aria-hidden="true"></i>
                </div>
                <div class="card-label" data-i18n="stat_success">Erfolgsquote</div>
                <div class="card-value"><?= (int)$success ?>%</div>
                <?php if ($successChange >= 0): ?>
                    <div class="card-trend up">
                        <i class="ti ti-arrow-up" aria-hidden="true"></i>
                        +<?= (int)$successChange ?>% <span data-i18n="stat_success_vs">vs. letzte Woche</span>
                    </div>
                <?php else: ?>
                    <div class="card-trend down">
                        <i class="ti ti-arrow-down" aria-hidden="true"></i>
                        <?= abs((int)$successChange) ?>% <span data-i18n="stat_success_vs">vs. letzte Woche</span>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- ── Level Bar ── -->
        <div class="level-bar">
            <div class="level-left">
                <i class="ti ti-school" style="font-size:20px;color:var(--accent)" aria-hidden="true"></i>
                <span data-i18n="level_current">Dein aktuelles Niveau</span>:
                <span class="level-badge"><?= htmlspecialchars($level) ?></span>
            </div>
            <div class="lessons-count">
                <i class="ti ti-books" aria-hidden="true"></i>
                <strong><?= (int)$lessonsCompleted ?></strong>
                <span data-i18n="lessons_done">abgeschlossene Lektionen</span>
            </div>
        </div>

        <!-- ── Feature Cards ── -->
        <div class="features-grid">

            <div class="feature-card" style="--fc-color:var(--accent)">
                <div class="fc-icon" style="background:var(--accent-light)">
                    <i class="ti ti-robot" style="color:var(--accent)" aria-hidden="true"></i>
                </div>
                <div class="fc-title" data-i18n="feat_chat_title">🤖 KI-Chatbot</div>
                <div class="fc-description" data-i18n="feat_chat_desc">
                    Übe das Sprechen, korrigiere deine Grammatik und übersetze Sätze mit unserem intelligenten Assistenten — rund um die Uhr verfügbar.
                </div>
                <a href="chatbot/index.php" class="fc-btn">
                    <span data-i18n="feat_chat_btn">Mit dem Assistenten sprechen</span>
                    <i class="ti ti-arrow-right" aria-hidden="true"></i>
                </a>
            </div>

            <div class="feature-card" style="--fc-color:var(--teal)">
                <div class="fc-icon" style="background:var(--teal-light)">
                    <i class="ti ti-messages" style="color:var(--teal)" aria-hidden="true"></i>
                </div>
                <div class="fc-title" data-i18n="feat_forum_title">💬 Community-Forum</div>
                <div class="fc-description" data-i18n="feat_forum_desc">
                    Stelle Fragen, teile deinen Fortschritt und erhalte Tipps von anderen Lernenden und Muttersprachlern.
                </div>
                <a href="#" class="fc-btn">
                    <span data-i18n="feat_forum_btn">Forum beitreten</span>
                    <i class="ti ti-arrow-right" aria-hidden="true"></i>
                </a>
            </div>

            <div class="feature-card" style="--fc-color:var(--violet)">
                <div class="fc-icon" style="background:var(--violet-light)">
                    <i class="ti ti-world" style="color:var(--violet)" aria-hidden="true"></i>
                </div>
                <div class="fc-title" data-i18n="feat_scenarios_title">🎭 Alltagsszenarien</div>
                <div class="fc-description" data-i18n="feat_scenarios_desc">
                    Simuliere Alltagssituationen: Café, Arzt, Supermarkt oder Vorstellungsgespräch — praxisnah und interaktiv.
                </div>
                <a href="#" class="fc-btn">
                    <span data-i18n="feat_scenarios_btn">Szenarien entdecken</span>
                    <i class="ti ti-arrow-right" aria-hidden="true"></i>
                </a>
            </div>

        </div>

        <div class="page-footer">
            <i class="ti ti-shield-lock" aria-hidden="true"></i>
            <span data-i18n="footer_note">Personalisierte Daten — nur für dich sichtbar.</span>
        </div>

    </main>
</div>

<script>
/* ══════════════════════════════════════
   TRANSLATIONS
══════════════════════════════════════ */
const i18n = {
    de: {
        nav_section:         'Navigation',
        nav_overview:        'Übersicht',
        nav_chatbot:         'KI-Chatbot',
        nav_lessons:         'Lektionen',
        nav_forum:           'Forum',
        nav_scenarios:       'Szenarien',
        nav_stats:           'Statistiken',
        nav_account:         'Konto',
        nav_settings:        'Einstellungen',
        page_title:          'Übersicht',
        greeting:            'Guten Morgen',
        theme_dark:          'Dunkelmodus',
        theme_light:         'Hellmodus',
        logout:              'Abmelden',
        stat_level:          'Aktuelles Niveau',
        stat_level_trend:    'diesen Monat',
        stat_time:           'Lernzeit',
        stat_time_trend:     'diese Woche',
        stat_words:          'Gelernte Wörter',
        stat_words_trend:    'heute',
        stat_success:        'Erfolgsquote',
        stat_success_vs:     'vs. letzte Woche',
        level_current:       'Dein aktuelles Niveau',
        lessons_done:        'abgeschlossene Lektionen',
        feat_chat_title:     '🤖 KI-Chatbot',
        feat_chat_desc:      'Übe das Sprechen, korrigiere deine Grammatik und übersetze Sätze mit unserem intelligenten Assistenten — rund um die Uhr verfügbar.',
        feat_chat_btn:       'Mit dem Assistenten sprechen',
        feat_forum_title:    '💬 Community-Forum',
        feat_forum_desc:     'Stelle Fragen, teile deinen Fortschritt und erhalte Tipps von anderen Lernenden und Muttersprachlern.',
        feat_forum_btn:      'Forum beitreten',
        feat_scenarios_title:'🎭 Alltagsszenarien',
        feat_scenarios_desc: 'Simuliere Alltagssituationen: Café, Arzt, Supermarkt oder Vorstellungsgespräch — praxisnah und interaktiv.',
        feat_scenarios_btn:  'Szenarien entdecken',
        footer_note:         'Personalisierte Daten — nur für dich sichtbar.',
    },
    fr: {
        nav_section:         'Navigation',
        nav_overview:        'Tableau de bord',
        nav_chatbot:         'Assistant IA',
        nav_lessons:         'Leçons',
        nav_forum:           'Forum',
        nav_scenarios:       'Scénarios',
        nav_stats:           'Statistiques',
        nav_account:         'Compte',
        nav_settings:        'Paramètres',
        page_title:          'Tableau de bord',
        greeting:            'Bonjour',
        theme_dark:          'Mode sombre',
        theme_light:         'Mode clair',
        logout:              'Déconnexion',
        stat_level:          'Niveau actuel',
        stat_level_trend:    'ce mois-ci',
        stat_time:           'Temps d\'apprentissage',
        stat_time_trend:     'cette semaine',
        stat_words:          'Mots appris',
        stat_words_trend:    'aujourd\'hui',
        stat_success:        'Taux de réussite',
        stat_success_vs:     'vs. semaine dernière',
        level_current:       'Votre niveau actuel',
        lessons_done:        'leçons complétées',
        feat_chat_title:     '🤖 Assistant IA',
        feat_chat_desc:      'Pratiquez l\'expression orale, corrigez votre grammaire et traduisez des phrases avec notre assistant intelligent — disponible 24h/24.',
        feat_chat_btn:       'Parler à l\'assistant',
        feat_forum_title:    '💬 Forum communautaire',
        feat_forum_desc:     'Posez vos questions, partagez vos progrès et recevez des conseils d\'autres apprenants et de locuteurs natifs.',
        feat_forum_btn:      'Rejoindre le forum',
        feat_scenarios_title:'🎭 Scénarios du quotidien',
        feat_scenarios_desc: 'Simulez des situations réelles : café, médecin, supermarché ou entretien d\'embauche — pratique et interactif.',
        feat_scenarios_btn:  'Découvrir les scénarios',
        footer_note:         'Données personnalisées — visibles uniquement par vous.',
    },
    en: {
        nav_section:         'Navigation',
        nav_overview:        'Overview',
        nav_chatbot:         'AI Chatbot',
        nav_lessons:         'Lessons',
        nav_forum:           'Forum',
        nav_scenarios:       'Scenarios',
        nav_stats:           'Statistics',
        nav_account:         'Account',
        nav_settings:        'Settings',
        page_title:          'Overview',
        greeting:            'Good morning',
        theme_dark:          'Dark mode',
        theme_light:         'Light mode',
        logout:              'Log out',
        stat_level:          'Current Level',
        stat_level_trend:    'this month',
        stat_time:           'Study Time',
        stat_time_trend:     'this week',
        stat_words:          'Words Learned',
        stat_words_trend:    'today',
        stat_success:        'Success Rate',
        stat_success_vs:     'vs. last week',
        level_current:       'Your current level',
        lessons_done:        'completed lessons',
        feat_chat_title:     '🤖 AI Chatbot',
        feat_chat_desc:      'Practice speaking, correct your grammar, and translate sentences with our intelligent assistant — available around the clock.',
        feat_chat_btn:       'Talk to the assistant',
        feat_forum_title:    '💬 Community Forum',
        feat_forum_desc:     'Ask questions, share your progress, and get advice from other learners and native speakers.',
        feat_forum_btn:      'Join the forum',
        feat_scenarios_title:'🎭 Everyday Scenarios',
        feat_scenarios_desc: 'Simulate real-life situations: café, doctor, supermarket, or job interview — practical and interactive.',
        feat_scenarios_btn:  'Explore scenarios',
        footer_note:         'Personalized data — visible only to you.',
    }
};

/* ── Language ── */
let currentLang = localStorage.getItem('lang') || 'de';

function setLang(lang) {
    currentLang = lang;
    localStorage.setItem('lang', lang);
    document.documentElement.setAttribute('data-lang', lang);
    document.documentElement.setAttribute('lang', lang);

    document.querySelectorAll('[data-lang-target]').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.langTarget === lang);
    });

    const t = i18n[lang];
    document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.dataset.i18n;
        if (t[key] !== undefined) el.textContent = t[key];
    });

    // Update theme label separately (depends on current dark/light state)
    updateThemeLabel();
}

/* ── Sidebar (desktop) ── */
const sidebar    = document.getElementById('sidebar');
const toggleIcon = document.getElementById('toggleIcon');
const overlay    = document.getElementById('mobOverlay');
let isMini = localStorage.getItem('sb_mini') === '1';

function applySidebar() {
    sidebar.classList.toggle('mini', isMini);
    toggleIcon.className = isMini ? 'ti ti-chevrons-right' : 'ti ti-chevrons-left';
}
function toggleSidebar() {
    isMini = !isMini;
    localStorage.setItem('sb_mini', isMini ? '1' : '0');
    applySidebar();
}

/* ── Mobile menu ── */
function openMobileMenu() {
    sidebar.classList.add('mob-open');
    overlay.classList.add('show');
    document.body.style.overflow = 'hidden';
}
function closeMobileMenu() {
    sidebar.classList.remove('mob-open');
    overlay.classList.remove('show');
    document.body.style.overflow = '';
}

applySidebar();

/* ── Active nav ── */
function setActive(el) {
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    el.classList.add('active');
    if (window.innerWidth <= 720) closeMobileMenu();
}

/* ── Theme ── */
const html       = document.documentElement;
const themeIcon  = document.getElementById('themeIcon');
const themeLabel = document.getElementById('themeLabel');
let isDark = localStorage.getItem('theme') === 'dark';

function updateThemeLabel() {
    const t = i18n[currentLang];
    if (isDark) {
        themeIcon.className    = 'ti ti-sun';
        themeLabel.textContent = t.theme_light;
    } else {
        themeIcon.className    = 'ti ti-moon';
        themeLabel.textContent = t.theme_dark;
    }
}
function applyTheme() {
    html.setAttribute('data-theme', isDark ? 'dark' : 'light');
    updateThemeLabel();
}
function toggleTheme() {
    isDark = !isDark;
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
    applyTheme();
}

/* ── Init ── */
applyTheme();
setLang(currentLang);
</script>
</body>
</html>
<?php
if (!isset($currentPage)) $currentPage = 'dashboard';
// Ensure SITE_URL is defined (from config.php)
if (!defined('SITE_URL')) {
    define('SITE_URL', '/deutschlernen/');
}
?>
<nav class="sidebar" id="sidebar" aria-label="Navigation">
    <div class="sb-logo">
        <div class="logo-mark"><i class="ti ti-language" aria-hidden="true"></i></div>
        <span class="logo-text">DeutschLernen</span>
    </div>
    
    <div class="sb-divider"></div>
    <span class="sb-label">Navigation</span>
    
    <a href="<?= SITE_URL ?>dashboard.php" class="nav-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
        <i class="ti ti-layout-dashboard ni" aria-hidden="true"></i>
        <span class="nav-lbl">Dashboard</span>
    </a>
    
    <a href="<?= SITE_URL ?>scenarios/index.php" class="nav-item <?= $currentPage === 'scenarios' ? 'active' : '' ?>">
        <i class="ti ti-world ni" aria-hidden="true"></i>
        <span class="nav-lbl">Scenarios</span>
    </a>
    
    <a href="<?= SITE_URL ?>scenarios/progress.php" class="nav-item <?= $currentPage === 'progress' ? 'active' : '' ?>">
        <i class="ti ti-chart-bar ni" aria-hidden="true"></i>
        <span class="nav-lbl">My Progress</span>
    </a>

    <a href="<?= SITE_URL ?>forum/index.php" class="nav-item <?= $currentPage === 'forum' ? 'active' : '' ?>">
        <i class="ti ti-messages ni" aria-hidden="true"></i>
        <span class="nav-lbl">Forum</span>
    </a>
    
    <div class="sb-spacer" style="flex-grow: 1;"></div>
    <div class="sb-divider"></div>
    <span class="sb-label">Account</span>
    
    <a href="#" class="nav-item <?= $currentPage === 'settings' ? 'active' : '' ?>">
        <i class="ti ti-settings ni" aria-hidden="true"></i>
        <span class="nav-lbl">Settings</span>
    </a>
    
    <button class="sb-toggle" id="sidebarToggle" onclick="document.getElementById('sidebar').classList.toggle('collapsed')" aria-label="Toggle sidebar" style="margin-top: 15px; background: none; border: none; color: inherit; cursor: pointer; width: 100%; text-align: center; padding: 10px;">
        <i class="ti ti-chevrons-left" id="toggleIcon" aria-hidden="true"></i>
    </button>
</nav>

<style>
/* Sidebar basic styles to ensure it works anywhere it is included */
.sidebar {
    width: 260px;
    flex-shrink: 0;
    background: #0E1726; /* Dark panel color */
    border-right: 1px solid rgba(255,255,255,0.07);
    display: flex;
    flex-direction: column;
    position: sticky;
    top: 0;
    height: 100vh;
    overflow-y: auto;
    color: #EEF2FF;
    transition: width 0.3s ease;
    padding-bottom: 20px;
}
.sidebar.collapsed {
    width: 80px;
}
.sidebar.collapsed .logo-text,
.sidebar.collapsed .sb-label,
.sidebar.collapsed .nav-lbl {
    display: none;
}
.sidebar.collapsed .sb-logo {
    justify-content: center;
    padding-left: 0;
    padding-right: 0;
}
.sidebar.collapsed #toggleIcon {
    transform: rotate(180deg);
}

.sb-logo {
    padding: 28px 24px 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 20px;
    font-weight: 800;
}
.logo-mark {
    width: 34px; height: 34px; border-radius: 9px;
    background: #6366F1; display: flex; align-items: center; justify-content: center;
}
.sb-divider {
    height: 1px;
    background: rgba(255,255,255,0.07);
    margin: 10px 20px;
}
.sb-label {
    padding: 10px 24px;
    font-size: 11px;
    color: #64748B;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 24px;
    color: #94A3B8;
    text-decoration: none;
    font-size: 15px;
    transition: all 0.2s;
}
.nav-item:hover {
    background: rgba(255,255,255,0.04);
    color: #EEF2FF;
}
.nav-item.active {
    background: rgba(99,102,241,0.1);
    color: #818CF8;
    border-right: 3px solid #6366F1;
}
.ni {
    font-size: 20px;
}

@media (max-width: 900px) {
    .sidebar {
        width: 100%;
        height: auto;
        position: static;
        flex-direction: row;
        flex-wrap: wrap;
        border-right: none;
        border-bottom: 1px solid rgba(255,255,255,0.07);
    }
    .sb-divider, .sb-label, .sb-spacer, .sb-toggle {
        display: none;
    }
    .nav-item {
        padding: 15px;
        flex: 1;
        justify-content: center;
    }
    .nav-item .nav-lbl {
        display: none;
    }
}
</style>
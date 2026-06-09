<?php
require_once '../config.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare('SELECT * FROM scenarios WHERE id = ?');
$stmt->execute([$id]);
$scenario = $stmt->fetch();
if (!$scenario) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare('SELECT * FROM scenario_dialogues WHERE scenario_id = ? ORDER BY step_number ASC');
$stmt->execute([$id]);
$steps = $stmt->fetchAll();
if (!$steps) { header('Location: index.php'); exit; }

foreach ($steps as &$s) {
    $s['expected_keywords'] = json_decode($s['expected_keywords'], true) ?: [];
    $s['vocabulary']        = json_decode($s['vocabulary'] ?? '[]', true) ?: [];
}
unset($s);

$totalSteps = count($steps);
$lvl = $scenario['level'] ?? 'A1';

$levelConfig = [
    'A1' => ['color'=>'#22D3A5','bg'=>'rgba(34,211,165,.15)',  'border'=>'rgba(34,211,165,.3)', 'glow'=>'rgba(34,211,165,.4)'],
    'A2' => ['color'=>'#38BDF8','bg'=>'rgba(56,189,248,.15)',  'border'=>'rgba(56,189,248,.3)', 'glow'=>'rgba(56,189,248,.4)'],
    'B1' => ['color'=>'#FBBF24','bg'=>'rgba(251,191,36,.15)',  'border'=>'rgba(251,191,36,.3)', 'glow'=>'rgba(251,191,36,.4)'],
    'B2' => ['color'=>'#F87171','bg'=>'rgba(248,113,113,.15)','border'=>'rgba(248,113,113,.3)', 'glow'=>'rgba(248,113,113,.4)'],
];
$cfg = $levelConfig[$lvl] ?? $levelConfig['A1'];

$npcAvatars = [
    'Gastronomie' => 'https://images.unsplash.com/photo-1577219491135-ce391730fb2c?w=200&q=80',
    'Reisen'      => 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=200&q=80',
    'Alltag'      => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=200&q=80',
    'Gesundheit'  => 'https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?w=200&q=80',
    'Beruf'       => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=200&q=80',
    'Einkaufen'   => 'https://images.unsplash.com/photo-1580489944761-15a19d654956?w=200&q=80',
    'Soziales'    => 'https://images.unsplash.com/photo-1529156069898-49953e39b3ac?w=200&q=80',
    'Wohnen'      => 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=200&q=80',
    'Verkehr'     => 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?w=200&q=80',
    'Finanzen'    => 'https://images.unsplash.com/photo-1601597111158-2fceff292cdc?w=200&q=80',
    'Bildung'     => 'https://images.unsplash.com/photo-1580582932707-520aed937b7b?w=200&q=80',
    'Recht'       => 'https://images.unsplash.com/photo-1589829545856-d10d557cf95f?w=200&q=80',
    'Kultur'      => 'https://images.unsplash.com/photo-1526778548025-fa2f459cd5c1?w=200&q=80',
    'Sport'       => 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=200&q=80',
    'Freizeit'    => 'https://images.unsplash.com/photo-1529156069898-49953e39b3ac?w=200&q=80',
    'Behörden'    => 'https://images.unsplash.com/photo-1507679799987-c73779587ccf?w=200&q=80',
    'Gesellschaft'=> 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=200&q=80',
];
$npcAvatar = $npcAvatars[$scenario['category'] ?? ''] ?? 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=200&q=80';
$scenarioImg = htmlspecialchars($scenario['image'] ?? '');
$scenarioId  = (int)$scenario['id'];
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($scenario['title']) ?> – DeutschWelt</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<style>
:root {
    --bg:      #080E1A;
    --panel:   #0E1726;
    --surface: #141F32;
    --card:    #1A2640;
    --border:  rgba(255,255,255,.07);
    --border2: rgba(255,255,255,.13);
    --text:    #EEF2FF;
    --muted:   #5C7080;
    --accent:  #6366F1;
    --lc:      <?= $cfg['color'] ?>;
    --lbg:     <?= $cfg['bg'] ?>;
    --lborder: <?= $cfg['border'] ?>;
    --lglow:   <?= $cfg['glow'] ?>;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html,body{height:100%;overflow:hidden;}
body{background:var(--bg);color:var(--text);font-family:'Outfit',sans-serif;display:flex;flex-direction:column;}

/* ══ TOP BAR ══ */
.topbar{
    height:54px;flex-shrink:0;
    background:var(--panel);border-bottom:1px solid var(--border);
    display:flex;align-items:center;gap:14px;padding:0 20px;z-index:20;
}
.back-btn{display:flex;align-items:center;gap:6px;color:var(--muted);text-decoration:none;
    font-size:14px;font-weight:500;transition:color .15s;flex-shrink:0;}
.back-btn:hover{color:var(--text);}
.topbar-title{font-size:15px;font-weight:700;letter-spacing:-.2px;
    white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:260px;}
.topbar-lvl{padding:3px 10px;border-radius:6px;font-size:12px;font-weight:700;
    font-family:'DM Mono',monospace;
    background:var(--lbg);color:var(--lc);border:1px solid var(--lborder);flex-shrink:0;}
.topbar-right{margin-left:auto;display:flex;align-items:center;gap:14px;}
.prog-wrap{display:flex;align-items:center;gap:10px;}
.prog-track{width:140px;height:5px;background:rgba(255,255,255,.08);border-radius:3px;overflow:hidden;}
.prog-fill{height:100%;background:var(--lc);width:0%;transition:width .5s ease;border-radius:3px;}
.prog-label{font-size:12px;color:var(--muted);white-space:nowrap;}
.score-chip{display:flex;align-items:center;gap:6px;background:var(--surface);
    border:1px solid var(--border2);padding:5px 13px;border-radius:8px;font-size:13px;font-weight:600;}
.score-val{color:var(--lc);}

/* ══ SCENE ══ */
.scene{flex:1;display:flex;overflow:hidden;}

/* ── LEFT PANEL: background image only ── */
.left-col{
    width:360px;flex-shrink:0;position:relative;overflow:hidden;
}
/* Full-bleed scenario image — set via JS to avoid CSS URL escaping issues */
.scene-bg{
    position:absolute;inset:0;
    background-size:cover;background-position:center;
    background-color:#141F32;
    transition:opacity .6s ease;
}
/* Gradient overlay */
.scene-bg::after{
    content:'';position:absolute;inset:0;
    background:linear-gradient(to bottom,
        rgba(8,14,26,.25) 0%,
        rgba(8,14,26,.08) 30%,
        rgba(8,14,26,.60) 60%,
        rgba(8,14,26,.96) 100%);
}

/* NPC chip — top left, over image */
.npc-chip{
    position:absolute;top:14px;left:14px;z-index:3;
    display:flex;align-items:center;gap:10px;
}
.npc-avatar{
    width:50px;height:50px;border-radius:50%;
    border:2px solid var(--lc);object-fit:cover;flex-shrink:0;
    box-shadow:0 0 18px var(--lglow);
}
.npc-info .npc-name{font-size:13px;font-weight:700;color:#fff;
    text-shadow:0 1px 8px rgba(0,0,0,.8);}
.npc-info .npc-cat{font-size:11px;color:rgba(255,255,255,.55);}

/* Conversation history over image — scrollable, between npc-chip and waveform */
.left-conv{
    position:absolute;top:80px;bottom:160px;left:0;right:0;
    overflow-y:auto;overflow-x:hidden;
    display:flex;flex-direction:column;justify-content:flex-end;
    padding:10px 14px;gap:8px;
    scrollbar-width:none;
    z-index:2;
}
.left-conv::-webkit-scrollbar{display:none;}

/* NPC line in left panel */
.lc-npc{
    display:flex;align-items:flex-end;gap:8px;
    animation:fadeUp .3s ease both;
}
.lc-npc-av{
    width:32px;height:32px;border-radius:50%;object-fit:cover;flex-shrink:0;
    border:1.5px solid var(--lc);
}
.lc-npc-bbl{
    background:rgba(14,23,38,.88);backdrop-filter:blur(12px);
    border:1px solid rgba(255,255,255,.12);border-radius:4px 14px 14px 14px;
    padding:10px 13px;font-size:13px;line-height:1.55;max-width:220px;
    color:#EEF2FF;
}
.lc-npc-tr{
    font-size:11px;color:rgba(255,255,255,.45);font-style:italic;
    direction:rtl;text-align:right;margin-top:5px;
    padding-top:5px;border-top:1px solid rgba(255,255,255,.08);
    display:none;
}
.lc-npc-tr.show{display:block;}

/* User line in left panel */
.lc-usr{
    display:flex;justify-content:flex-end;
    animation:fadeUp .3s ease both;
}
.lc-usr-bbl{
    background:rgba(99,102,241,.8);backdrop-filter:blur(12px);
    border-radius:14px 4px 14px 14px;
    padding:10px 13px;font-size:13px;line-height:1.55;max-width:220px;color:#fff;
}

/* System line in left panel */
.lc-sys{
    display:flex;justify-content:center;
    animation:fadeUp .3s ease both;
}
.lc-sys-bbl{
    font-size:12px;padding:6px 12px;border-radius:8px;
    background:rgba(34,197,94,.15);border:1px solid rgba(34,197,94,.25);color:#86EFAC;
}
.lc-sys-bbl.err{
    background:rgba(239,68,68,.12);border-color:rgba(239,68,68,.22);color:#FCA5A5;
}

/* Waveform — bottom of left col, above image */
.waveform-zone{
    position:absolute;bottom:14px;left:14px;right:14px;z-index:3;
}
.waveform-box{
    background:rgba(8,14,26,.82);backdrop-filter:blur(14px);
    border:1px solid var(--border2);border-radius:14px;padding:13px 16px;
}
.wv-top{display:flex;align-items:center;gap:8px;margin-bottom:9px;}
.rec-dot{width:8px;height:8px;border-radius:50%;background:#EF4444;
    display:none;flex-shrink:0;}
.rec-dot.on{display:block;animation:blink 1s infinite;}
@keyframes blink{0%,100%{opacity:1}50%{opacity:0}}
.wv-status{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;}
.waveform{display:flex;align-items:center;justify-content:center;gap:3px;height:38px;}
.wv-bar{width:3px;border-radius:2px;background:var(--lc);
    opacity:.2;transform:scaleY(.18);transform-origin:center;transition:.07s;}
.wv-bar.live{opacity:.9;}
.wv-hint{font-size:11px;color:var(--lc);text-align:center;margin-top:8px;}

/* ── CENTER: Chat panel ── */
.center-col{flex:1;display:flex;flex-direction:column;overflow:hidden;min-width:0;}

/* Chat header with NPC mini info */
.chat-topbar{
    display:flex;align-items:center;gap:10px;
    padding:10px 16px;border-bottom:1px solid var(--border);flex-shrink:0;
    background:var(--panel);
}
.ct-avatar{width:34px;height:34px;border-radius:50%;object-fit:cover;
    border:1.5px solid var(--lc);flex-shrink:0;}
.ct-name{font-size:14px;font-weight:700;}
.ct-status{font-size:11px;color:var(--lc);display:flex;align-items:center;gap:5px;}
.online-dot{width:7px;height:7px;border-radius:50%;background:var(--lc);
    animation:pulse-dot 2s infinite;}
@keyframes pulse-dot{0%,100%{opacity:1}50%{opacity:.4}}
.ct-step{margin-left:auto;font-size:12px;color:var(--muted);}

/* Chat feed */
.chat-feed{flex:1;overflow-y:auto;padding:14px 16px;
    display:flex;flex-direction:column;gap:10px;}
.chat-feed::-webkit-scrollbar{width:4px;}
.chat-feed::-webkit-scrollbar-thumb{background:var(--surface);border-radius:2px;}

.msg{display:flex;align-items:flex-end;gap:8px;}
.msg.npc{justify-content:flex-start;}
.msg.usr{justify-content:flex-end;}
.msg.sys{justify-content:center;}

/* NPC avatar in chat */
.msg-av{width:28px;height:28px;border-radius:50%;object-fit:cover;flex-shrink:0;border:1px solid var(--lborder);}

.bbl{max-width:76%;padding:11px 15px;font-size:14px;line-height:1.58;
    border-radius:16px;animation:fadeUp .25s ease both;}
@keyframes fadeUp{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}
.bbl-npc{background:var(--surface);border:1px solid var(--border);
    border-radius:4px 16px 16px 16px;}
.bbl-usr{background:linear-gradient(135deg,#6366F1,#4F46E5);color:#fff;
    border-radius:16px 4px 16px 16px;}
.bbl-ok{background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.28);
    color:#86EFAC;font-size:13px;padding:8px 16px;border-radius:10px;}
.bbl-err{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.22);
    color:#FCA5A5;font-size:13px;padding:10px 16px;border-radius:10px;max-width:88%;}

/* Translation under NPC bubble */
.bbl-tr{font-size:12px;color:var(--muted);font-style:italic;
    margin-top:6px;padding-top:6px;border-top:1px solid var(--border);
    direction:rtl;text-align:right;display:none;}
.bbl-tr.show{display:block;}

/* Input zone */
.input-zone{background:var(--panel);border-top:1px solid var(--border);
    padding:14px 16px;flex-shrink:0;}
.input-row{display:flex;gap:10px;align-items:center;}
.mic-btn{
    width:46px;height:46px;border-radius:50%;
    background:var(--lbg);border:2px solid var(--lborder);
    color:var(--lc);font-size:20px;
    display:flex;align-items:center;justify-content:center;
    cursor:pointer;transition:.2s;flex-shrink:0;position:relative;
}
.mic-btn.rec{
    background:rgba(239,68,68,.2);border-color:#EF4444;color:#EF4444;
    animation:mic-pulse 1.2s infinite;
}
@keyframes mic-pulse{0%,100%{box-shadow:0 0 0 0 rgba(239,68,68,.4)}60%{box-shadow:0 0 0 10px rgba(239,68,68,0)}}
.mic-no-support{font-size:10px;position:absolute;bottom:-18px;left:50%;transform:translateX(-50%);
    white-space:nowrap;color:#EF4444;display:none;}
.chat-inp{
    flex:1;background:var(--surface);border:1px solid var(--border2);
    color:var(--text);padding:12px 16px;border-radius:10px;
    font-size:14px;outline:none;transition:.2s;font-family:'Outfit',sans-serif;
}
.chat-inp:focus{border-color:rgba(99,102,241,.5);background:var(--card);}
.chat-inp::placeholder{color:var(--muted);}
.send-btn{
    height:46px;padding:0 22px;border-radius:10px;
    background:var(--accent);border:none;color:#fff;
    font-size:14px;font-weight:600;cursor:pointer;
    display:flex;align-items:center;gap:7px;
    font-family:'Outfit',sans-serif;transition:.2s;flex-shrink:0;
}
.send-btn:hover{background:#4F46E5;}
.send-btn:disabled{opacity:.4;cursor:not-allowed;}

.action-bar{display:flex;gap:8px;margin-top:10px;}
.act-btn{
    flex:1;display:flex;align-items:center;justify-content:center;gap:6px;
    padding:9px;border-radius:9px;font-size:12px;font-weight:500;
    background:var(--surface);border:1px solid var(--border);color:var(--muted);
    cursor:pointer;transition:.2s;font-family:'Outfit',sans-serif;
}
.act-btn:hover{border-color:var(--border2);color:var(--text);}
.act-btn.active{background:var(--lbg);border-color:var(--lborder);color:var(--lc);}
.end-btn{background:rgba(239,68,68,.08);border-color:rgba(239,68,68,.2);color:#F87171;}
.end-btn:hover{background:rgba(239,68,68,.16);}

/* ── RIGHT SIDEBAR ── */
.right-col{
    width:238px;flex-shrink:0;border-left:1px solid var(--border);
    display:flex;flex-direction:column;overflow-y:auto;
    padding:12px;gap:10px;
    scrollbar-width:thin;scrollbar-color:var(--surface) transparent;
}
.rs-card{background:var(--surface);border:1px solid var(--border);
    border-radius:12px;padding:13px;}
.rs-title{font-size:11px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;
    margin-bottom:10px;display:flex;align-items:center;gap:7px;color:var(--muted);}

/* Progress ring */
.prog-ring-wrap{display:flex;align-items:center;gap:11px;}
.prog-ring{position:relative;width:56px;height:56px;flex-shrink:0;}
.prog-ring svg{transform:rotate(-90deg);}
.ring-bg{fill:none;stroke:rgba(255,255,255,.07);stroke-width:5;}
.ring-arc{fill:none;stroke:var(--lc);stroke-width:5;stroke-linecap:round;
    stroke-dasharray:138.2;stroke-dashoffset:138.2;transition:stroke-dashoffset .5s ease;}
.ring-val{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;
    font-family:'DM Mono',monospace;font-size:13px;font-weight:700;color:var(--lc);}
.ring-info .ring-label{font-size:13px;font-weight:600;margin-bottom:3px;}
.ring-info .ring-sub{font-size:11px;color:var(--muted);line-height:1.5;}
.stars-row{display:flex;gap:4px;margin-top:8px;}
.star{font-size:15px;filter:grayscale(1) opacity(.2);transition:.3s;}
.star.on{filter:none;}

/* Qdots */
.qdots-wrap{display:flex;flex-wrap:wrap;gap:4px;margin-top:2px;}
.qdot{width:10px;height:10px;border-radius:3px;
    background:rgba(255,255,255,.1);transition:.3s;}
.qdot.done{background:var(--lc);}
.qdot.wrong{background:#EF4444;}
.qdot.cur{background:rgba(255,255,255,.4);animation:qdot-pulse 1.5s infinite;}
@keyframes qdot-pulse{0%,100%{opacity:1}50%{opacity:.4}}

/* Vocab */
.vocab-item{padding:7px 0;border-bottom:1px solid var(--border);display:flex;flex-direction:column;gap:2px;}
.vocab-item:last-child{border-bottom:none;}
.vocab-row{display:flex;align-items:center;justify-content:space-between;}
.vocab-de{font-size:13px;font-weight:600;}
.vocab-ar{font-size:12px;color:var(--muted);direction:rtl;text-align:right;}
.vocab-spk{background:none;border:none;color:var(--muted);cursor:pointer;
    font-size:14px;padding:2px 5px;border-radius:4px;transition:.15s;}
.vocab-spk:hover{color:var(--lc);}
.tip-body{font-size:12px;color:var(--muted);line-height:1.65;}

/* ══ COMPLETION OVERLAY ══ */
.comp-overlay{
    display:none;position:fixed;inset:0;
    background:rgba(8,14,26,.97);backdrop-filter:blur(10px);
    z-index:100;flex-direction:column;align-items:center;
    justify-content:center;text-align:center;padding:40px;
}
.comp-overlay.show{display:flex;}
.comp-emoji{font-size:72px;margin-bottom:16px;animation:pop .5s cubic-bezier(.22,.68,0,1.5);}
@keyframes pop{from{transform:scale(.4);opacity:0}to{transform:scale(1);opacity:1}}
.comp-title{font-size:36px;font-weight:800;letter-spacing:-.5px;margin-bottom:6px;}
.comp-sub{color:var(--muted);font-size:15px;margin-bottom:20px;}
.comp-score{font-family:'DM Mono',monospace;font-size:80px;font-weight:700;
    color:var(--lc);line-height:1;}
.comp-stars{font-size:30px;letter-spacing:8px;margin:12px 0 28px;}
.comp-btns{display:flex;gap:12px;flex-wrap:wrap;justify-content:center;}
.cbtn{padding:12px 26px;border-radius:10px;font-size:14px;font-weight:600;
    cursor:pointer;transition:.2s;font-family:'Outfit',sans-serif;
    display:flex;align-items:center;gap:8px;}
.cbtn-ghost{background:var(--surface);border:1px solid var(--border);color:var(--text);}
.cbtn-ghost:hover{border-color:var(--accent);}
.cbtn-primary{background:var(--accent);color:#fff;border:none;text-decoration:none;}
.cbtn-primary:hover{background:#4F46E5;}

@media(max-width:900px){.right-col{display:none;}}
@media(max-width:640px){.left-col{display:none;}.topbar-title{max-width:130px;}}
</style>
</head>
<body>

<!-- TOP BAR -->
<div class="topbar">
    <a href="index.php" class="back-btn"><i class="ti ti-arrow-left"></i> Zurück</a>
    <div class="topbar-title"><?= htmlspecialchars($scenario['title']) ?></div>
    <span class="topbar-lvl"><?= htmlspecialchars($lvl) ?></span>
    <div class="topbar-right">
        <div class="prog-wrap">
            <div class="prog-track"><div class="prog-fill" id="prog-fill"></div></div>
            <span class="prog-label"><span id="prog-num">0</span>/<?= $totalSteps ?></span>
        </div>
        <div class="score-chip"><i class="ti ti-star"></i>&nbsp;<span class="score-val" id="score-disp">100</span>%</div>
    </div>
</div>

<!-- SCENE -->
<div class="scene">

<!-- LEFT: scenario background image + conversation overlay + waveform -->
<div class="left-col">
    <div class="scene-bg" id="scene-bg"></div>

    <!-- NPC chip over image -->
    <div class="npc-chip">
        <img src="<?= htmlspecialchars($npcAvatar) ?>" class="npc-avatar" alt=""
             onerror="this.src='https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=200&q=80'">
        <div class="npc-info">
            <div class="npc-name"><?= htmlspecialchars($scenario['category'] ?? 'NPC') ?></div>
            <div class="npc-cat"><?= htmlspecialchars($lvl) ?> – <?= htmlspecialchars($scenario['title']) ?></div>
        </div>
    </div>

    <!-- Conversation history overlay (scrollable, over the image) -->
    <div class="left-conv" id="left-conv"></div>

    <!-- Waveform zone -->
    <div class="waveform-zone">
        <div class="waveform-box">
            <div class="wv-top">
                <span class="rec-dot" id="rec-dot"></span>
                <span class="wv-status" id="wv-status">Bereit zum Sprechen</span>
            </div>
            <div class="waveform" id="waveform">
                <?php for($i=0;$i<30;$i++): ?>
                <div class="wv-bar" style="height:<?= rand(10,34) ?>px;"></div>
                <?php endfor; ?>
            </div>
            <div class="wv-hint" id="wv-hint">🎙 Drücke das Mikrofon zum Sprechen</div>
        </div>
    </div>
</div>

<!-- CENTER: Chat -->
<div class="center-col">
    <!-- Chat topbar with NPC info -->
    <div class="chat-topbar">
        <img src="<?= htmlspecialchars($npcAvatar) ?>" class="ct-avatar" alt=""
             onerror="this.src='https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=200&q=80'">
        <div>
            <div class="ct-name"><?= htmlspecialchars($scenario['category'] ?? 'Gesprächspartner') ?></div>
            <div class="ct-status"><span class="online-dot"></span> Online – bereit zu sprechen</div>
        </div>
        <div class="ct-step" id="ct-step">Frage 1/<?= $totalSteps ?></div>
    </div>

    <div class="chat-feed" id="chat-feed"></div>

    <div class="input-zone">
        <div class="input-row">
            <button class="mic-btn" id="mic-btn" title="Mikrofon">
                <i class="ti ti-microphone" id="mic-icon"></i>
                <span class="mic-no-support" id="mic-no-sup">Nur Chrome</span>
            </button>
            <input type="text" class="chat-inp" id="chat-inp"
                   placeholder="Schreib deine Antwort auf Deutsch…" autocomplete="off">
            <button class="send-btn" id="send-btn">
                Senden <i class="ti ti-send"></i>
            </button>
        </div>
        <div class="action-bar">
            <button class="act-btn" id="btn-repeat"><i class="ti ti-volume"></i> Hören</button>
            <button class="act-btn" id="btn-tr"><i class="ti ti-language"></i> Übersetzen</button>
            <button class="act-btn" id="btn-hint"><i class="ti ti-bulb"></i> Hinweis</button>
            <button class="act-btn end-btn" id="btn-end"><i class="ti ti-phone-off"></i> Beenden</button>
        </div>
    </div>
</div>

<!-- RIGHT SIDEBAR -->
<div class="right-col">
    <div class="rs-card">
        <div class="rs-title"><i class="ti ti-target"></i> Fortschritt</div>
        <div class="prog-ring-wrap">
            <div class="prog-ring">
                <svg width="56" height="56" viewBox="0 0 56 56">
                    <circle class="ring-bg" cx="28" cy="28" r="22"/>
                    <circle class="ring-arc" cx="28" cy="28" r="22" id="ring-arc"/>
                </svg>
                <div class="ring-val" id="ring-val">0%</div>
            </div>
            <div class="ring-info">
                <div class="ring-label" id="ring-label">Los gehts!</div>
                <div class="ring-sub" id="ring-sub">Frage 0/<?= $totalSteps ?></div>
            </div>
        </div>
        <div class="stars-row">
            <span class="star on" id="s1">⭐</span>
            <span class="star on" id="s2">⭐</span>
            <span class="star on" id="s3">⭐</span>
        </div>
    </div>

    <div class="rs-card">
        <div class="rs-title"><i class="ti ti-list-check"></i> Fragen (<?= $totalSteps ?>)</div>
        <div class="qdots-wrap" id="qdots">
            <?php for($q=0;$q<$totalSteps;$q++): ?>
            <div class="qdot<?= $q===0?' cur':'' ?>" id="qd<?= $q ?>"></div>
            <?php endfor; ?>
        </div>
    </div>

    <div class="rs-card">
        <div class="rs-title" style="color:var(--lc);"><i class="ti ti-book"></i> Vokabular</div>
        <div id="vocab-list"><div style="font-size:12px;color:var(--muted);">Erscheint pro Frage…</div></div>
    </div>

    <div class="rs-card">
        <div class="rs-title" style="color:#FBBF24;"><i class="ti ti-bulb"></i> Tipp</div>
        <p class="tip-body" id="tip-body">Höre gut zu und antworte vollständig!</p>
    </div>
</div>
</div>

<!-- COMPLETION OVERLAY -->
<div class="comp-overlay" id="comp-ov">
    <div class="comp-emoji" id="comp-emoji">🎉</div>
    <div class="comp-title">Fantastisch!</div>
    <div class="comp-sub">Du hast <strong><?= htmlspecialchars($scenario['title']) ?></strong> abgeschlossen!</div>
    <div class="comp-score" id="comp-score">100%</div>
    <div class="comp-stars" id="comp-stars">⭐⭐⭐</div>
    <div class="comp-btns">
        <button class="cbtn cbtn-ghost" onclick="restartScenario()">
            <i class="ti ti-refresh"></i> Nochmal
        </button>
        <a href="index.php" class="cbtn cbtn-primary">
            <i class="ti ti-layout-grid"></i> Weitere Szenarien
        </a>
    </div>
</div>

<script>
/* ═══════════════════════════════════════════════
   DATA
═══════════════════════════════════════════════ */
const STEPS     = <?= json_encode(array_values($steps), JSON_UNESCAPED_UNICODE) ?>;
const TOTAL     = STEPS.length;
const SCENARIO_ID = <?= $scenarioId ?>;
const SESSION_KEY = 'dw_session_' + SCENARIO_ID;

// Set background image safely via JS (avoids CSS URL escaping issues)
document.getElementById('scene-bg').style.backgroundImage =
    'url(' + JSON.stringify(<?= json_encode($scenarioImg) ?>) + ')';

/* ═══════════════════════════════════════════════
   STATE — restore from sessionStorage if exists
═══════════════════════════════════════════════ */
function loadState() {
    try {
        const raw = sessionStorage.getItem(SESSION_KEY);
        if (raw) return JSON.parse(raw);
    } catch(e) {}
    return null;
}
function saveState() {
    try {
        sessionStorage.setItem(SESSION_KEY, JSON.stringify({
            idx, mistakes,
            qdotStates,
            chatLog   // [{type,text}]
        }));
    } catch(e) {}
}
function clearState() {
    try { sessionStorage.removeItem(SESSION_KEY); } catch(e) {}
}

let savedState = loadState();
let idx         = savedState ? savedState.idx         : 0;
let mistakes    = savedState ? savedState.mistakes    : 0;
let qdotStates  = savedState ? savedState.qdotStates  : new Array(TOTAL).fill('');
let chatLog     = savedState ? savedState.chatLog     : [];
let busy        = false;
let hintUsed    = false;
let translationShown = false;

/* ═══════════════════════════════════════════════
   DOM REFS
═══════════════════════════════════════════════ */
const chatFeed  = document.getElementById('chat-feed');
const leftConv  = document.getElementById('left-conv');
const chatInp   = document.getElementById('chat-inp');
const sendBtn   = document.getElementById('send-btn');
const progFill  = document.getElementById('prog-fill');
const progNum   = document.getElementById('prog-num');
const ringArc   = document.getElementById('ring-arc');
const ringVal   = document.getElementById('ring-val');
const ringLabel = document.getElementById('ring-label');
const ringSub   = document.getElementById('ring-sub');
const vocabList = document.getElementById('vocab-list');
const tipBody   = document.getElementById('tip-body');
const recDot    = document.getElementById('rec-dot');
const wvStatus  = document.getElementById('wv-status');
const wvHint    = document.getElementById('wv-hint');
const scoreDisp = document.getElementById('score-disp');
const ctStep    = document.getElementById('ct-step');
const wvBars    = document.querySelectorAll('.wv-bar');
const RING_FULL = 138.2;
const PROG_MSGS = ['Los gehts!','Gut gemacht!','Weiter so!','Fast fertig!','Ausgezeichnet!'];

/* ═══════════════════════════════════════════════
   HELPERS
═══════════════════════════════════════════════ */
function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;')
                    .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function calcScore() { return Math.max(0, 100 - mistakes * 5); }

function updateUI() {
    const pct = idx >= TOTAL ? 100 : Math.round((idx / TOTAL) * 100);
    progFill.style.width  = pct + '%';
    progNum.textContent   = idx;
    ringArc.style.strokeDashoffset = RING_FULL - (RING_FULL * pct / 100);
    ringVal.textContent   = pct + '%';
    ringLabel.textContent = PROG_MSGS[Math.min(Math.floor(pct/25), 4)];
    ringSub.textContent   = `Frage ${idx}/${TOTAL}`;
    ctStep.textContent    = `Frage ${Math.min(idx+1,TOTAL)}/${TOTAL}`;
    const sc = calcScore();
    scoreDisp.textContent = sc;
    document.getElementById('s1').className = 'star' + (sc >= 30 ? ' on':'');
    document.getElementById('s2').className = 'star' + (sc >= 60 ? ' on':'');
    document.getElementById('s3').className = 'star' + (sc >= 85 ? ' on':'');
}

function setQdot(i, state) {
    qdotStates[i] = state;
    const el = document.getElementById('qd' + i);
    if (el) el.className = 'qdot' + (state ? ' '+state : '');
}

/* ═══════════════════════════════════════════════
   SPEECH SYNTHESIS
═══════════════════════════════════════════════ */
function speak(text) {
    if (!('speechSynthesis' in window)) return;
    speechSynthesis.cancel();
    const u = new SpeechSynthesisUtterance(text);
    u.lang = 'de-DE'; u.rate = 0.88; u.pitch = 1;
    // Try to pick a German voice
    const voices = speechSynthesis.getVoices();
    const deVoice = voices.find(v => v.lang.startsWith('de'));
    if (deVoice) u.voice = deVoice;
    speechSynthesis.speak(u);
}
// Pre-load voices
if ('speechSynthesis' in window) {
    speechSynthesis.getVoices();
    speechSynthesis.onvoiceschanged = () => speechSynthesis.getVoices();
}

/* ═══════════════════════════════════════════════
   WAVEFORM ANIMATION
═══════════════════════════════════════════════ */
let wvFrame = null;
function startWaveform() {
    function frame() {
        wvBars.forEach(b => {
            const s = 0.15 + Math.random() * 0.85;
            b.style.transform = `scaleY(${s})`;
            b.classList.add('live');
        });
        wvFrame = requestAnimationFrame(frame);
    }
    frame();
}
function stopWaveform() {
    if (wvFrame) { cancelAnimationFrame(wvFrame); wvFrame = null; }
    wvBars.forEach(b => { b.style.transform = 'scaleY(.18)'; b.classList.remove('live'); });
}

/* ═══════════════════════════════════════════════
   CHAT MESSAGES
═══════════════════════════════════════════════ */
// npcAvatarUrl for chat bubbles
const NPC_AV = document.querySelector('.ct-avatar').src;

function addMsg(type, text, arText, skipSave) {
    // ── RIGHT chat feed ──
    const row = document.createElement('div');
    row.className = 'msg ' + type;

    if (type === 'npc') {
        const av = document.createElement('img');
        av.src = NPC_AV; av.className = 'msg-av'; av.alt = '';
        av.onerror = () => { av.style.display='none'; };

        const bubble = document.createElement('div');
        bubble.className = 'bbl bbl-npc';
        bubble.innerHTML = esc(text);
        if (arText) {
            const tr = document.createElement('div');
            tr.className = 'bbl-tr';
            tr.setAttribute('data-ar', arText);
            tr.textContent = arText;
            bubble.appendChild(tr);
            bubble._trEl = tr;
        }
        row._bubble = bubble;
        row.appendChild(av);
        row.appendChild(bubble);
    } else if (type === 'usr') {
        const bubble = document.createElement('div');
        bubble.className = 'bbl bbl-usr';
        bubble.textContent = text;
        row.appendChild(bubble);
    } else {
        const bubble = document.createElement('div');
        bubble.className = text.startsWith('✅') ? 'bbl bbl-ok' : 'bbl bbl-err';
        bubble.textContent = text;
        row.appendChild(bubble);
    }

    chatFeed.appendChild(row);
    chatFeed.scrollTop = chatFeed.scrollHeight;

    // ── LEFT conv mirror ──
    addLeftMsg(type, text, arText);

    if (!skipSave) {
        chatLog.push({ type, text, arText: arText || null });
        saveState();
    }
    return row;
}

// Mirror a message into the left panel
function addLeftMsg(type, text, arText) {
    const wrap = document.createElement('div');
    if (type === 'npc') {
        wrap.className = 'lc-npc';
        const av = document.createElement('img');
        av.src = NPC_AV; av.className = 'lc-npc-av'; av.alt = '';
        av.onerror = () => { av.style.display='none'; };
        const bbl = document.createElement('div');
        bbl.className = 'lc-npc-bbl';
        bbl.innerHTML = esc(text);
        if (arText) {
            const tr = document.createElement('div');
            tr.className = 'lc-npc-tr';
            tr.textContent = arText;
            bbl._trEl = tr;
            bbl.appendChild(tr);
            // expose for translation toggle
            wrap._trEl = tr;
        }
        wrap._bbl = bbl;
        wrap.appendChild(av);
        wrap.appendChild(bbl);
    } else if (type === 'usr') {
        wrap.className = 'lc-usr';
        const bbl = document.createElement('div');
        bbl.className = 'lc-usr-bbl';
        bbl.textContent = text;
        wrap.appendChild(bbl);
    } else {
        wrap.className = 'lc-sys';
        const bbl = document.createElement('div');
        bbl.className = 'lc-sys-bbl' + (text.startsWith('✅') ? '' : ' err');
        bbl.textContent = text;
        wrap.appendChild(bbl);
    }
    leftConv.appendChild(wrap);
    leftConv.scrollTop = leftConv.scrollHeight;
    // keep reference for translation
    if (type === 'npc') lastLeftNpcRow = wrap;
}

let lastLeftNpcRow = null;

// Translation toggle — shows arabic on the LAST npc bubble
let lastNpcRow = null;

function showTranslation() {
    if (!lastNpcRow || translationShown) return;
    const trEl = lastNpcRow.querySelector('.bbl-tr');
    if (trEl) trEl.classList.add('show');
    // Also show on left panel
    if (lastLeftNpcRow) {
        const ltrEl = lastLeftNpcRow.querySelector('.lc-npc-tr');
        if (ltrEl) ltrEl.classList.add('show');
    }
    translationShown = true;
    document.getElementById('btn-tr').classList.add('active');
}

/* ═══════════════════════════════════════════════
   RENDER STEP
═══════════════════════════════════════════════ */
function renderStep(autoSpeak) {
    if (idx >= TOTAL) { showComplete(); return; }
    const s = STEPS[idx];
    busy = false; hintUsed = false; translationShown = false;
    sendBtn.disabled = false;
    document.getElementById('btn-tr').classList.remove('active');
    updateUI();
    // Qdots
    for (let i = 0; i < TOTAL; i++) {
        const el = document.getElementById('qd' + i);
        if (el) el.className = 'qdot' + (qdotStates[i] ? ' '+qdotStates[i] : '') + (i===idx?' cur':'');
    }
    setQdot(idx, 'cur');

    // Vocab
    if (s.vocabulary && s.vocabulary.length > 0) {
        vocabList.innerHTML = s.vocabulary.map(v => `
            <div class="vocab-item">
                <div class="vocab-row">
                    <span class="vocab-de">${esc(v.de)}</span>
                    <button class="vocab-spk" onclick="speak('${v.de.replace(/'/g,"\\'")}')" title="Aussprechen">
                        <i class="ti ti-volume"></i>
                    </button>
                </div>
                <div class="vocab-ar">${esc(v.ar)}</div>
            </div>`).join('');
    } else {
        vocabList.innerHTML = '<div style="font-size:12px;color:var(--muted);">—</div>';
    }

    // Tip
    if (s.hint) tipBody.textContent = s.hint;

    // Add NPC message to chat
    const row = addMsg('npc', s.german_text, s.arabic_translation);
    lastNpcRow = row;

    // Auto speak (only if autoSpeak flag set, not on restore)
    if (autoSpeak !== false) {
        setTimeout(() => speak(s.german_text), 350);
    }
}

/* ═══════════════════════════════════════════════
   HANDLE ANSWER
═══════════════════════════════════════════════ */
function handleAnswer() {
    if (busy) return;
    const ans = chatInp.value.trim();
    if (!ans) return;
    chatInp.value = '';
    busy = true; sendBtn.disabled = true;
    addMsg('usr', ans);

    const s    = STEPS[idx];
    const low  = ans.toLowerCase();
    const keys = (s.expected_keywords || []).map(k => k.toLowerCase());
    const hits  = keys.filter(k => low.includes(k)).length;
    const ok   = keys.length === 0 || hits > 0 || ans.length >= 5;

    setTimeout(() => {
        if (ok) {
            setQdot(idx, 'done');
            addMsg('sys', '✅ Sehr gut! Weiter so.');
            idx++;
            saveState();
            setTimeout(() => renderStep(true), 900);
        } else {
            mistakes++;
            addMsg('sys', '❌ Nicht ganz – versuche es nochmal!');
            updateUI();
            saveState();
            setTimeout(() => {
                setQdot(idx, 'cur');
                busy = false; sendBtn.disabled = false;
            }, 300);
        }
    }, 400);
}

sendBtn.onclick = handleAnswer;
chatInp.addEventListener('keypress', e => { if (e.key==='Enter') handleAnswer(); });

/* ═══════════════════════════════════════════════
   ACTION BUTTONS
═══════════════════════════════════════════════ */
document.getElementById('btn-repeat').onclick = () => { if (idx < TOTAL) speak(STEPS[idx].german_text); };
document.getElementById('btn-tr').onclick     = showTranslation;
document.getElementById('btn-hint').onclick   = () => {
    if (idx >= TOTAL || hintUsed) return;
    addMsg('sys', '💡 Tipp: ' + (STEPS[idx].hint || 'Antworte auf Deutsch!'));
    hintUsed = true;
};
document.getElementById('btn-end').onclick = () => {
    if (confirm('Gespräch wirklich beenden?')) { clearState(); showComplete(); }
};

/* ═══════════════════════════════════════════════
   MICROPHONE
   Supports: Chrome (desktop + Android), Edge.
   Localhost HTTP is allowed by Chrome for mic.
═══════════════════════════════════════════════ */
const micBtn  = document.getElementById('mic-btn');
const micIcon = document.getElementById('mic-icon');
const micNoSup= document.getElementById('mic-no-sup');
const hasSR   = ('webkitSpeechRecognition' in window) || ('SpeechRecognition' in window);
let recog     = null;
let micActive = false;

if (!hasSR) {
    // Show "nur Chrome" hint under button
    micNoSup.style.display = 'block';
    micBtn.style.opacity   = '.5';
    micBtn.onclick = () => {
        alert('Spracherkennung funktioniert nur in Chrome oder Edge.\nBitte öffne die Seite in Chrome.');
    };
} else {
    const SR  = window.SpeechRecognition || window.webkitSpeechRecognition;
    recog     = new SR();
    recog.lang            = 'de-DE';
    recog.interimResults  = false;
    recog.maxAlternatives = 1;
    recog.continuous      = false;

    recog.onstart = () => {
        micBtn.classList.add('rec');
        micIcon.className = 'ti ti-microphone-off';
        recDot.classList.add('on');
        wvStatus.textContent = 'Aufnahme läuft…';
        wvHint.textContent   = '🔴 Sprich jetzt auf Deutsch…';
        startWaveform();
    };
    recog.onresult = e => {
        const t = e.results[0][0].transcript;
        chatInp.value = t;
        // Auto-send if confident enough
        // (just fill, let user confirm with Senden or Enter)
    };
    recog.onend = () => {
        micBtn.classList.remove('rec');
        micIcon.className  = 'ti ti-microphone';
        recDot.classList.remove('on');
        wvStatus.textContent = 'Bereit zum Sprechen';
        wvHint.textContent   = '🎙 Drücke das Mikrofon zum Sprechen';
        stopWaveform();
        micActive = false;
    };
    recog.onerror = e => {
        console.warn('Speech error:', e.error);
        recog.onend();
        if (e.error === 'not-allowed') {
            wvHint.textContent = '⚠️ Mikrofon-Zugriff verweigert – bitte erlauben!';
            wvHint.style.color = '#F87171';
        }
    };

    micBtn.onclick = () => {
        if (micActive) {
            recog.stop();
            micActive = false;
        } else {
            try {
                recog.start();
                micActive = true;
            } catch(e) {
                // already started; stop then restart
                recog.stop();
                setTimeout(() => { recog.start(); micActive = true; }, 200);
            }
        }
    };
}

/* ═══════════════════════════════════════════════
   COMPLETION
═══════════════════════════════════════════════ */
function showComplete() {
    const sc = calcScore();
    document.getElementById('comp-emoji').textContent  = sc >= 85 ? '🎉' : sc >= 60 ? '👍' : '💪';
    document.getElementById('comp-score').textContent  = sc + '%';
    document.getElementById('comp-stars').textContent  = sc >= 90 ? '⭐⭐⭐' : sc >= 65 ? '⭐⭐' : sc >= 40 ? '⭐' : '💪';
    // Save XP
    const xp = parseInt(localStorage.getItem('dw_xp') || '0');
    localStorage.setItem('dw_xp', xp + sc);
    clearState(); // clear session so next visit starts fresh
    document.getElementById('comp-ov').classList.add('show');
}

function restartScenario() {
    clearState();
    location.reload();
}

/* ═══════════════════════════════════════════════
   RESTORE OR START
═══════════════════════════════════════════════ */
if (savedState && chatLog.length > 0) {
    // Restore previous chat messages (no auto-speak, no re-save)
    chatLog.forEach(m => addMsg(m.type, m.text, m.arText, true));
    // Restore qdots
    qdotStates.forEach((s, i) => {
        const el = document.getElementById('qd' + i);
        if (el) el.className = 'qdot' + (s ? ' '+s : '') + (i===idx?' cur':'');
    });
    updateUI();
    // Restore vocab+tip for current step
    if (idx < TOTAL) {
        const s = STEPS[idx];
        if (s.vocabulary && s.vocabulary.length > 0) {
            vocabList.innerHTML = s.vocabulary.map(v => `
                <div class="vocab-item">
                    <div class="vocab-row">
                        <span class="vocab-de">${esc(v.de)}</span>
                        <button class="vocab-spk" onclick="speak('${v.de.replace(/'/g,"\\'")}')" title="Aussprechen">
                            <i class="ti ti-volume"></i>
                        </button>
                    </div>
                    <div class="vocab-ar">${esc(v.ar)}</div>
                </div>`).join('');
        }
        if (s.hint) tipBody.textContent = s.hint;
        // Set lastNpcRow to last npc message in right chat
        const npcRows = [...chatFeed.querySelectorAll('.msg.npc')];
        lastNpcRow = npcRows[npcRows.length - 1] || null;
        // Set lastLeftNpcRow to last npc in left conv
        const leftNpcRows = [...leftConv.querySelectorAll('.lc-npc')];
        lastLeftNpcRow = leftNpcRows[leftNpcRows.length - 1] || null;
    } else {
        showComplete();
    }
} else {
    // Fresh start
    idx = 0; mistakes = 0;
    qdotStates = new Array(TOTAL).fill('');
    chatLog    = [];
    clearState();
    updateUI();
    renderStep(true);
}
</script>
</body>
</html>
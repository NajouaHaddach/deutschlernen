

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LinguaFlow — Niveaux</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --sidebar:220px;--bg:#F9F9F9;--white:#FFFFFF;--border:#E5E5E5;
  --text:#1A1A1A;--muted:#6B6B6B;--black:#1A1A1A;
  --red:#CC0000;--red-bg:#FFF0F0;
  --gold:#FFCC00;--gold-bg:#FFFBE6;--gold-dark:#B8900A;
  --font:'Inter',sans-serif;
}
html,body{height:100%;background:var(--bg);color:var(--text);font-family:var(--font);font-size:14px;line-height:1.5}
a{text-decoration:none;color:inherit}
.app{display:flex;min-height:100vh}

/* SIDEBAR */
.sidebar{width:var(--sidebar);background:var(--black);position:fixed;top:0;left:0;bottom:0;display:flex;flex-direction:column}
.logo{display:flex;align-items:center;gap:10px;padding:1.4rem 1.25rem;border-bottom:1px solid rgba(255,255,255,0.08)}
.logo-mark{width:32px;height:32px;border-radius:7px;background:var(--gold);display:flex;align-items:center;justify-content:center;color:var(--black);font-weight:700;font-size:13px}
.logo-name{font-weight:600;font-size:15px;color:#fff}
.nav{padding:1rem 0.75rem;flex:1}
.nav-item{display:flex;align-items:center;gap:10px;padding:0.5rem 0.75rem;border-radius:7px;font-size:13.5px;color:rgba(255,255,255,0.5);cursor:pointer;margin-bottom:2px;transition:background 0.1s,color 0.1s}
.nav-item:hover{background:rgba(255,255,255,0.06);color:rgba(255,255,255,0.85)}
.nav-item.active{background:rgba(255,204,0,0.12);color:var(--gold);font-weight:500}
.nav-item svg{width:16px;height:16px;flex-shrink:0}
.nav-badge{margin-left:auto;background:var(--red);color:#fff;font-size:10px;font-weight:600;padding:1px 7px;border-radius:20px}
.nav-divider{height:1px;background:rgba(255,255,255,0.07);margin:0.5rem 0.75rem}
.sidebar-user{padding:1rem 1.25rem;border-top:1px solid rgba(255,255,255,0.08);display:flex;align-items:center;gap:10px}
.user-av{width:32px;height:32px;border-radius:50%;background:rgba(255,204,0,0.15);color:var(--gold);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;flex-shrink:0}
.user-name{font-size:13px;font-weight:500;color:#fff}
.user-sub{font-size:11px;color:rgba(255,255,255,0.4)}

/* MAIN */
.main{margin-left:var(--sidebar);flex:1;padding:2rem 2.5rem}
.page-header{margin-bottom:2rem}
.page-title{font-size:20px;font-weight:600;margin-bottom:3px}
.page-sub{font-size:13px;color:var(--muted)}
.flag-strip{display:flex;height:4px;border-radius:3px;overflow:hidden;width:100px;margin-top:8px}
.flag-strip span{flex:1}

/* PROGRESS BAR GLOBAL */
.global-progress{
  background:var(--white);border:1px solid var(--border);border-radius:10px;
  padding:1.1rem 1.5rem;margin-bottom:2rem;
  display:flex;align-items:center;gap:1.5rem;
}
.gp-label{font-size:13px;color:var(--muted);white-space:nowrap}
.gp-bar{flex:1;height:6px;background:#EBEBEB;border-radius:6px}
.gp-fill{height:6px;border-radius:6px;background:linear-gradient(90deg,var(--black),var(--red),var(--gold))}
.gp-pct{font-size:13px;font-weight:600;white-space:nowrap}

/* SECTION TITLE */
.section-label{font-size:11px;font-weight:600;letter-spacing:0.8px;text-transform:uppercase;color:var(--muted);margin-bottom:0.9rem;margin-top:1.75rem}
.section-label:first-of-type{margin-top:0}

/* LEVEL CARDS GRID */
.levels-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem}

/* LEVEL CARD */
.level-card{
  background:var(--white);border:1px solid var(--border);
  border-radius:12px;padding:1.25rem;
  cursor:pointer;transition:box-shadow 0.15s,border-color 0.15s;
  position:relative;overflow:hidden;
}
.level-card:hover{border-color:#ccc;box-shadow:0 2px 12px rgba(0,0,0,0.06)}
.level-card.locked{opacity:0.55;cursor:not-allowed}
.level-card.locked:hover{box-shadow:none;border-color:var(--border)}

/* top accent line */
.level-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px}
.level-card.done::before{background:var(--black)}
.level-card.active::before{background:linear-gradient(90deg,var(--black),var(--red),var(--gold))}
.level-card.locked::before{background:#E0E0E0}

/* icon circle */
.lc-icon{
  width:48px;height:48px;border-radius:12px;
  display:flex;align-items:center;justify-content:center;
  font-size:22px;margin-bottom:0.9rem;
}
.done .lc-icon{background:#F3F3F3}
.active .lc-icon{background:var(--gold-bg)}
.locked .lc-icon{background:#F5F5F5}

.lc-tag{
  display:inline-block;font-size:10px;font-weight:700;
  padding:2px 8px;border-radius:5px;margin-bottom:0.5rem;
  letter-spacing:0.3px;
}
.done .lc-tag{background:#F0F0F0;color:var(--black)}
.active .lc-tag{background:var(--gold-bg);color:var(--gold-dark);border:1px solid var(--gold)}
.locked .lc-tag{background:#F5F5F5;color:#B0B0B0}

.lc-title{font-size:14px;font-weight:600;margin-bottom:3px}
.lc-desc{font-size:12px;color:var(--muted);margin-bottom:0.9rem}

/* progress */
.lc-bar-bg{height:4px;background:#EBEBEB;border-radius:4px;margin-bottom:4px}
.lc-bar-fill{height:4px;border-radius:4px}
.done .lc-bar-fill{background:var(--black)}
.active .lc-bar-fill{background:var(--gold)}
.locked .lc-bar-fill{background:#E0E0E0}
.lc-bar-row{display:flex;justify-content:space-between;font-size:11px;color:var(--muted)}

/* badge done */
.lc-done-badge{
  position:absolute;top:12px;right:12px;
  width:22px;height:22px;border-radius:50%;
  background:var(--black);
  display:flex;align-items:center;justify-content:center;
}
.lc-done-badge svg{width:12px;height:12px;stroke:#fff}

/* lock icon */
.lc-lock{
  position:absolute;top:12px;right:12px;
  color:#C0C0C0;
}
.lc-lock svg{width:14px;height:14px}

/* CTA button */
.lc-btn{
  display:inline-block;margin-top:0.75rem;
  font-size:12px;font-weight:600;padding:5px 14px;
  border-radius:6px;border:none;cursor:pointer;
  font-family:var(--font);
}
.active .lc-btn{background:var(--black);color:#fff}
.done .lc-btn{background:#F0F0F0;color:var(--black)}
</style>
</head>
<body>
<div class="app">

<aside class="sidebar">
  <div class="logo">
    <div class="logo-mark">LF</div>
    <span class="logo-name">LinguaFlow</span>
  </div>
  <nav class="nav">
    <a href="dashboard.php" class="nav-item">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      Tableau de bord
    </a>
    <a href="courses.php" class="nav-item active">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 20V10M18 20V4M6 20v-4"/></svg>
      Niveaux
    </a>
    <a href="chatbot.php" class="nav-item">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4-3.6 7-8 7a9.9 9.9 0 0 1-3-.5L3 20l1.3-3.5C3.5 15.3 3 13.7 3 12c0-4 3.6-7 8-7s9 3 9 7Z"/></svg>
      Chatbot IA
      <span class="nav-badge">Pro</span>
    </a>
    <a href="scenarios.php" class="nav-item">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
      Scénarios
    </a>
    <a href="forum.php" class="nav-item">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M17 8h2a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2h-2v4l-4-4H9a2 2 0 0 1-2-2v-1"/><path d="M15 3H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2v4l4-4h4a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2Z"/></svg>
      Forum
      <span class="nav-badge">3</span>
    </a>
    <div class="nav-divider"></div>
    <a href="progression.php" class="nav-item">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
      Progression
    </a>
    <a href="parametres.php" class="nav-item">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/></svg>
      Paramètres
    </a>
  </nav>
  <div class="sidebar-user">
    <div class="user-av">YA</div>
    <div>
      <div class="user-name">Youssef Amrani</div>
      <div class="user-sub">Niveau B1 · 1 240 XP</div>
    </div>
  </div>
</aside>

<main class="main">
  <div class="page-header">
    <div class="page-title">Niveaux du cours</div>
    <div class="page-sub">Progressez de A1 à C2 à votre rythme</div>
    <div class="flag-strip" style="margin-top:8px">
      <span style="background:#1A1A1A"></span>
      <span style="background:#CC0000"></span>
      <span style="background:#FFCC00"></span>
    </div>
  </div>

  <!-- GLOBAL PROGRESS -->
  <div class="global-progress">
    <span class="gp-label">Progression globale</span>
    <div class="gp-bar"><div class="gp-fill" style="width:38%"></div></div>
    <span class="gp-pct">38% — Niveau B1</span>
  </div>

  <!-- A1 -->
  <div class="section-label">A1 — Débutant</div>
  <div class="levels-grid">

    <div class="level-card done">
      <div class="lc-done-badge"><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></div>
      <div class="lc-icon">🔤</div>
      <span class="lc-tag">A1.1</span>
      <div class="lc-title">Débutant absolu 1</div>
      <div class="lc-desc">Alphabet, salutations, chiffres et couleurs</div>
      <div class="lc-bar-bg"><div class="lc-bar-fill" style="width:100%"></div></div>
      <div class="lc-bar-row"><span>12/12 leçons</span><span>100%</span></div>
      <button class="lc-btn">Revoir</button>
    </div>

    <div class="level-card done">
      <div class="lc-done-badge"><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></div>
      <div class="lc-icon">👋</div>
      <span class="lc-tag">A1.2</span>
      <div class="lc-title">Débutant absolu 2</div>
      <div class="lc-desc">Se présenter, famille, objets du quotidien</div>
      <div class="lc-bar-bg"><div class="lc-bar-fill" style="width:100%"></div></div>
      <div class="lc-bar-row"><span>10/10 leçons</span><span>100%</span></div>
      <button class="lc-btn">Revoir</button>
    </div>

  </div>

  <!-- A2 -->
  <div class="section-label">A2 — Élémentaire</div>
  <div class="levels-grid">

    <div class="level-card done">
      <div class="lc-done-badge"><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></div>
      <div class="lc-icon">🏠</div>
      <span class="lc-tag">A2.1</span>
      <div class="lc-title">Élémentaire 1</div>
      <div class="lc-desc">La maison, les courses, les directions</div>
      <div class="lc-bar-bg"><div class="lc-bar-fill" style="width:100%"></div></div>
      <div class="lc-bar-row"><span>14/14 leçons</span><span>100%</span></div>
      <button class="lc-btn">Revoir</button>
    </div>

    <div class="level-card done">
      <div class="lc-done-badge"><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></div>
      <div class="lc-icon">🕐</div>
      <span class="lc-tag">A2.2</span>
      <div class="lc-title">Élémentaire 2</div>
      <div class="lc-desc">L'heure, les transports, les habitudes</div>
      <div class="lc-bar-bg"><div class="lc-bar-fill" style="width:100%"></div></div>
      <div class="lc-bar-row"><span>11/11 leçons</span><span>100%</span></div>
      <button class="lc-btn">Revoir</button>
    </div>

  </div>

  <!-- B1 -->
  <div class="section-label">B1 — Intermédiaire</div>
  <div class="levels-grid">

    <div class="level-card active">
      <div class="lc-icon">💬</div>
      <span class="lc-tag">B1.1</span>
      <div class="lc-title">Intermédiaire 1</div>
      <div class="lc-desc">Conversations courantes, opinions, loisirs</div>
      <div class="lc-bar-bg"><div class="lc-bar-fill" style="width:62%"></div></div>
      <div class="lc-bar-row"><span>8/13 leçons</span><span>62%</span></div>
      <button class="lc-btn">Continuer</button>
    </div>

    <div class="level-card locked">
      <div class="lc-lock"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></div>
      <div class="lc-icon">📰</div>
      <span class="lc-tag">B1.2</span>
      <div class="lc-title">Intermédiaire 2</div>
      <div class="lc-desc">Actualités, expressions idiomatiques</div>
      <div class="lc-bar-bg"><div class="lc-bar-fill" style="width:0%"></div></div>
      <div class="lc-bar-row"><span>0/12 leçons</span><span>Verrouillé</span></div>
    </div>

  </div>

  <!-- B2 -->
  <div class="section-label">B2 — Avancé</div>
  <div class="levels-grid">

    <div class="level-card locked">
      <div class="lc-lock"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></div>
      <div class="lc-icon">🗣️</div>
      <span class="lc-tag">B2.1</span>
      <div class="lc-title">Avancé 1</div>
      <div class="lc-desc">Débats, argumentation, nuances</div>
      <div class="lc-bar-bg"><div class="lc-bar-fill" style="width:0%"></div></div>
      <div class="lc-bar-row"><span>0/15 leçons</span><span>Verrouillé</span></div>
    </div>

    <div class="level-card locked">
      <div class="lc-lock"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></div>
      <div class="lc-icon">📖</div>
      <span class="lc-tag">B2.2</span>
      <div class="lc-title">Avancé 2</div>
      <div class="lc-desc">Littérature, presse, culture allemande</div>
      <div class="lc-bar-bg"><div class="lc-bar-fill" style="width:0%"></div></div>
      <div class="lc-bar-row"><span>0/14 leçons</span><span>Verrouillé</span></div>
    </div>

  </div>

  <!-- C1/C2 -->
  <div class="section-label">C1 / C2 — Maîtrise</div>
  <div class="levels-grid">

    <div class="level-card locked">
      <div class="lc-lock"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></div>
      <div class="lc-icon">🎓</div>
      <span class="lc-tag">C1</span>
      <div class="lc-title">Courant</div>
      <div class="lc-desc">Langue professionnelle et académique</div>
      <div class="lc-bar-bg"><div class="lc-bar-fill" style="width:0%"></div></div>
      <div class="lc-bar-row"><span>0/18 leçons</span><span>Verrouillé</span></div>
    </div>

    <div class="level-card locked">
      <div class="lc-lock"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></div>
      <div class="lc-icon">🏆</div>
      <span class="lc-tag">C2</span>
      <div class="lc-title">Maîtrise totale</div>
      <div class="lc-desc">Niveau natif, certifications officielles</div>
      <div class="lc-bar-bg"><div class="lc-bar-fill" style="width:0%"></div></div>
      <div class="lc-bar-row"><span>0/20 leçons</span><span>Verrouillé</span></div>
    </div>

  </div>

</main>
</div>
</body>
</html>
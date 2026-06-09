<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════════
 * DEUTSCHLERNEN - PAGE DE TEST DÉDIÉE
 * Système de test complet avec feedback instantané
 * ═══════════════════════════════════════════════════════════════════════════════
 */

require_once '../config.php';
require_once '../functions.php';

if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

$niveau = $_GET['niveau'] ?? 'A1';
if (!in_array($niveau, ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'])) {
    header('Location: index.php');
    exit;
}

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="fr" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de Niveau <?= $niveau ?> — DeutschLernen</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        :root {
            --bg-base: #f8fafc;
            --bg-card: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --accent: #0d9488;
            --accent-light: #f0fdfa;
            --border: #e2e8f0;
            --radius: 16px;
            --shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.1);
        }

        [data-theme="dark"] {
            --bg-base: #0f172a;
            --bg-card: #1e293b;
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --accent: #2dd4bf;
            --accent-light: rgba(45, 212, 191, 0.1);
            --border: #334155;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Sora', sans-serif; background: var(--bg-base); color: var(--text-main); line-height: 1.6; min-height: 100vh; display: flex; flex-direction: column; }
        
        .header { background: var(--bg-card); padding: 15px 30px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 100; }
        .logo { font-weight: 800; font-size: 20px; color: var(--accent); display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .btn-exit { display: flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none; color: var(--text-muted); border: 1px solid var(--border); transition: 0.2s; }
        .btn-exit:hover { background: #fee2e2; color: #ef4444; border-color: #fecaca; }

        .main-container { flex: 1; display: flex; align-items: center; justify-content: center; padding: 40px 20px; }
        .test-wrapper { background: var(--bg-card); width: 100%; max-width: 800px; border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; }

        .progress-section { padding: 25px 40px 0; }
        .progress-header { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 14px; font-weight: 700; color: var(--text-muted); }
        .progress-bar-bg { height: 8px; background: var(--bg-base); border-radius: 10px; overflow: hidden; }
        .progress-bar-fill { height: 100%; background: var(--accent); width: 0%; transition: width 0.4s ease; border-radius: 10px; }

        .content-section { padding: 40px; min-height: 400px; }
        .loader-box { text-align: center; padding: 60px 0; }
        .spinner { width: 50px; height: 50px; border: 5px solid var(--bg-base); border-top-color: var(--accent); border-radius: 50%; animation: spin 1s infinite linear; margin: 0 auto 20px; }
        @keyframes spin { to { transform: rotate(360deg); } }

        .q-box { display: none; }
        .q-box.active { display: block; animation: fadeIn 0.4s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .q-text { font-size: 22px; font-weight: 800; margin-bottom: 30px; color: var(--text-main); }
        .options-grid { display: grid; gap: 15px; }
        .opt-card { border: 2px solid var(--border); border-radius: 12px; padding: 18px 22px; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 15px; font-weight: 600; font-size: 16px; position: relative; background: var(--bg-card); }
        .opt-card:hover { border-color: var(--accent); background: var(--accent-light); }
        .opt-card.correct { border-color: #22c55e !important; background: #f0fdf4 !important; color: #166534 !important; }
        .opt-card.wrong { border-color: #ef4444 !important; background: #fef2f2 !important; color: #991b1b !important; }
        .opt-card .opt-letter { width: 30px; height: 30px; border-radius: 8px; background: var(--bg-base); display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 800; color: var(--text-muted); transition: 0.2s; }
        .opt-card.correct .opt-letter { background: #22c55e !important; color: #fff !important; }
        .opt-card.wrong .opt-letter { background: #ef4444 !important; color: #fff !important; }

        .footer-section { padding: 25px 40px; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: var(--bg-card); }
        .btn-nav { padding: 12px 25px; border-radius: 10px; font-weight: 700; border: none; cursor: pointer; transition: 0.2s; display: flex; align-items: center; gap: 10px; font-family: inherit; }
        .btn-prev { background: var(--bg-base); color: var(--text-muted); }
        .btn-prev:hover:not(:disabled) { background: var(--border); }
        .btn-next { background: var(--accent); color: #fff; box-shadow: 0 4px 12px rgba(13, 148, 136, 0.2); }
        .btn-next:hover:not(:disabled) { filter: brightness(1.1); transform: translateY(-1px); }
        .btn-nav:disabled { opacity: 0.5; cursor: not-allowed; }

        /* Results Screen */
        .result-screen { text-align: center; animation: slideUp 0.5s ease both; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .res-icon { font-size: 80px; margin-bottom: 20px; }
        .res-score { font-size: 56px; font-weight: 800; color: var(--accent); margin-bottom: 10px; }
        .res-status { font-size: 24px; font-weight: 700; margin-bottom: 30px; }
        .res-status.pass { color: #166534; }
        .res-status.fail { color: #991b1b; }
        .btn-finish { display: inline-flex; align-items: center; gap: 10px; background: var(--accent); color: #fff; padding: 15px 40px; border-radius: 12px; font-weight: 700; text-decoration: none; transition: 0.3s; }
        .btn-finish:hover { filter: brightness(1.1); transform: scale(1.05); }

        @media (max-width: 600px) {
            .test-wrapper { border-radius: 0; box-shadow: none; }
            .content-section { padding: 25px 20px; }
            .header { padding: 15px 20px; }
            .q-text { font-size: 18px; }
        }
    </style>
</head>
<body>

    <header class="header">
        <a href="index.php" class="logo">
            <i class="ti ti-language"></i> DeutschLernen
        </a>
        <div style="font-weight: 700; font-size: 14px; background: var(--accent-light); color: var(--accent); padding: 5px 12px; border-radius: 20px;">
            Niveau <?= $niveau ?>
        </div>
        <a href="index.php" class="btn-exit" onclick="return confirm('Voulez-vous vraiment quitter le test ? Votre progression sera perdue.')">
            <i class="ti ti-x"></i> Quitter
        </a>
    </header>

    <main class="main-container">
        <div class="test-wrapper">
            <div id="testUI">
                <div class="progress-section">
                    <div class="progress-header">
                        <span>Progression</span>
                        <span id="questionCounter">... / ...</span>
                    </div>
                    <div class="progress-bar-bg">
                        <div class="progress-bar-fill" id="progressBar"></div>
                    </div>
                </div>

                <div class="content-section" id="testContent">
                    <div class="loader-box" id="loader">
                        <div class="spinner"></div>
                        <p style="font-weight: 700;">Chargement du test...</p>
                    </div>
                    <div id="questionsContainer"></div>
                </div>

                <div class="footer-section" id="testFooter" style="display: none;">
                    <button class="btn-nav btn-prev" id="btnPrev" onclick="prevQuestion()" disabled>
                        <i class="ti ti-arrow-left"></i> Précédent
                    </button>
                    <button class="btn-nav btn-next" id="btnNext" onclick="nextQuestion()" disabled>
                        Suivant <i class="ti ti-arrow-right"></i>
                    </button>
                </div>
            </div>

            <div id="resultUI" style="display: none; padding: 60px 40px;">
                <!-- Result content injected here -->
            </div>
        </div>
    </main>

    <script>
        const CFG = { niveau: '<?= $niveau ?>', api: 'ajax.php' };
        let questions = [], userAnswers = {}, currentIdx = 0, testId = null;

        document.addEventListener('DOMContentLoaded', loadTest);

        async function loadTest() {
            try {
                const res = await fetch(`${CFG.api}?action=get_test_questions&niveau=${CFG.niveau}`);
                const data = await res.json();
                
                if (!data.success) throw new Error(data.error);
                if (!data.questions || data.questions.length === 0) throw new Error("Aucune question trouvée.");

                questions = data.questions;
                testId = data.test.id;
                
                renderQuestions();
                showQuestion(0);
                
                document.getElementById('loader').style.display = 'none';
                document.getElementById('testFooter').style.display = 'flex';
                
            } catch (err) {
                document.getElementById('testContent').innerHTML = `
                    <div style="text-align:center; padding:40px;">
                        <i class="ti ti-alert-circle" style="font-size:48px; color:#ef4444;"></i>
                        <p style="margin-top:20px; font-weight:700;">${err.message}</p>
                        <a href="index.php" class="btn-finish" style="margin-top:20px;">Retour au Forum</a>
                    </div>
                `;
            }
        }

        function renderQuestions() {
            const container = document.getElementById('questionsContainer');
            container.innerHTML = '';
            
            questions.forEach((q, i) => {
                const div = document.createElement('div');
                div.className = 'q-box';
                div.id = `q-box-${i}`;
                div.innerHTML = `
                    <div class="q-text">${q.question}</div>
                    <div class="options-grid">
                        ${['a','b','c','d'].map(letter => `
                            <div class="opt-card" onclick="selectOption(${i}, '${letter}')">
                                <span class="opt-letter">${letter.toUpperCase()}</span> ${q['option_'+letter]}
                            </div>
                        `).join('')}
                    </div>
                `;
                container.appendChild(div);
            });
        }

        function showQuestion(idx) {
            document.querySelectorAll('.q-box').forEach(b => b.classList.remove('active'));
            document.getElementById(`q-box-${idx}`).classList.add('active');
            currentIdx = idx;
            
            document.getElementById('questionCounter').textContent = `${idx + 1} / ${questions.length}`;
            document.getElementById('progressBar').style.width = `${((idx + 1) / questions.length) * 100}%`;
            
            document.getElementById('btnPrev').disabled = idx === 0;
            const nextBtn = document.getElementById('btnNext');
            nextBtn.innerHTML = (idx === questions.length - 1) ? 'Voir les résultats <i class="ti ti-check"></i>' : 'Suivant <i class="ti ti-arrow-right"></i>';
            nextBtn.disabled = userAnswers[questions[idx].id] === undefined;
        }

        function selectOption(qIdx, opt) {
            const q = questions[qIdx];
            if (userAnswers[q.id] !== undefined) return;
            
            userAnswers[q.id] = opt;
            const correctOpt = q.bonne_reponse.toLowerCase();
            
            const qBox = document.getElementById(`q-box-${qIdx}`);
            const cards = qBox.querySelectorAll('.opt-card');
            const letters = ['a','b','c','d'];
            const selectedIdx = letters.indexOf(opt);
            const correctIdx = letters.indexOf(correctOpt);
            
            cards[selectedIdx].classList.add(opt === correctOpt ? 'correct' : 'wrong');
            if (opt !== correctOpt) cards[correctIdx].classList.add('correct');
            
            cards.forEach(c => c.style.pointerEvents = 'none');
            document.getElementById('btnNext').disabled = false;
        }

        function nextQuestion() {
            if (currentIdx < questions.length - 1) showQuestion(currentIdx + 1);
            else submitTest();
        }

        function prevQuestion() {
            if (currentIdx > 0) showQuestion(currentIdx - 1);
        }

        async function submitTest() {
            const ui = document.getElementById('testUI');
            const resUI = document.getElementById('resultUI');
            
            ui.style.opacity = '0.5';
            ui.style.pointerEvents = 'none';
            
            try {
                const fd = new FormData();
                fd.append('action', 'submit_test');
                fd.append('test_id', testId);
                
                const answersArray = Object.entries(userAnswers).map(([id, ans]) => ({ id: parseInt(id), ans }));
                fd.append('answers', JSON.stringify(answersArray));
                
                const res = await fetch(CFG.api, { method: 'POST', body: fd });
                const data = await res.json();
                
                if (!data.success) throw new Error(data.error);
                
                // Final score calculation safety
                const finalPassed = data.reussi;
                
                ui.style.display = 'none';
                resUI.style.display = 'block';
                
                resUI.innerHTML = `
                    <div class="result-screen">
                        <div class="res-icon">${finalPassed ? '🎉' : '❌'}</div>
                        <div class="res-score">${Math.round(data.pourcentage)}%</div>
                        <div class="res-status ${finalPassed ? 'pass' : 'fail'}">
                            ${finalPassed ? 'Félicitations ! Test Réussi.' : 'Échec. Continuez à réviser !'}
                        </div>
                        <p style="color:var(--text-muted); margin-bottom:40px;">
                            Vous avez obtenu <strong>${data.score}</strong> bonnes réponses sur <strong>${data.total}</strong>.
                        </p>
                        <div style="display:flex; gap:15px; justify-content:center;">
                            ${finalPassed ? 
                                `<a href="index.php" class="btn-finish">Accéder aux Salons <i class="ti ti-arrow-right"></i></a>` :
                                `<button onclick="location.reload()" class="btn-finish" style="background:var(--text-muted)">Réessayer <i class="ti ti-rotate"></i></button>
                                 <a href="index.php" class="btn-finish outline" style="background:transparent; color:var(--text-muted); border:1px solid var(--border)">Retour</a>`
                            }
                        </div>
                    </div>
                `;
                
            } catch (err) {
                alert("Erreur de soumission : " + err.message);
                location.reload();
            }
        }
    </script>
</body>
</html>

<?php
session_start();
require_once __DIR__ . '/../config/db.php';
requireLogin();

$studentId = $_SESSION['student_id'];
$student   = currentStudent();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Recommendations — CollabIQ</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dashboard.css">
</head>
<body>
    <nav class="nav">
        <div class="nav-brand">🧠 Collab<span class="gradient-text">IQ</span></div>
        <div class="nav-user">
            <img src="<?= generateAvatar($student['name']) ?>" class="nav-avatar" alt="">
            <span class="nav-name"><?= sanitize($student['name']) ?></span>
            <a href="<?= BASE_URL ?>/auth/logout.php" class="btn btn-ghost btn-sm">Sign Out</a>
        </div>
    </nav>

    <div class="app-layout">
        <aside class="sidebar">
            <div class="sidebar-section">
                <div class="sidebar-label">Main</div>
                <a href="<?= BASE_URL ?>/student/dashboard.php" class="sidebar-link"><span class="icon">🏠</span> Dashboard</a>
                <a href="<?= BASE_URL ?>/student/profile.php" class="sidebar-link"><span class="icon">👤</span> My Profile</a>
                <a href="<?= BASE_URL ?>/student/recommendations.php" class="sidebar-link active"><span class="icon">🤖</span> AI Recommendations</a>
            </div>
            <div class="sidebar-section">
                <div class="sidebar-label">Projects</div>
                <a href="<?= BASE_URL ?>/project/create.php" class="sidebar-link"><span class="icon">➕</span> New Project</a>
            </div>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <h1>🤖 AI Team Recommendations</h1>
                <p>Our algorithm analyzes skills and interests to find your ideal teammates.</p>
            </div>

            <!-- Algorithm Explainer -->
            <div class="data-card" style="margin-bottom:1.5rem;">
                <div class="data-card-body" style="display:flex;gap:2rem;flex-wrap:wrap;align-items:center;">
                    <div style="flex:1;min-width:200px;">
                        <h3 style="margin-bottom:.5rem;">How the AI works</h3>
                        <p class="text-sm text-muted">The matching engine scores each student using a weighted formula:</p>
                    </div>
                    <div style="display:flex;gap:1.5rem;flex-wrap:wrap;">
                        <div style="text-align:center;">
                            <div style="font-size:1.5rem;font-weight:800;color:#6ee7b7;">×2</div>
                            <div class="text-xs text-muted">Shared Skills</div>
                        </div>
                        <div style="text-align:center;font-size:1.25rem;color:var(--text-faint);line-height:2.5;">+</div>
                        <div style="text-align:center;">
                            <div style="font-size:1.5rem;font-weight:800;color:#67e8f9;">×1</div>
                            <div class="text-xs text-muted">New Skills They Bring</div>
                        </div>
                        <div style="text-align:center;font-size:1.25rem;color:var(--text-faint);line-height:2.5;">+</div>
                        <div style="text-align:center;">
                            <div style="font-size:1.5rem;font-weight:800;color:#c4b5fd;">×1.5</div>
                            <div class="text-xs text-muted">Shared Interests</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Legend -->
            <div class="rec-legend" style="margin-bottom:1.25rem;font-size:.8rem;">
                <span><span class="legend-dot legend-green" style="display:inline-block;margin-right:.35rem;"></span> Shared skills (you both have)</span>
                <span><span class="legend-dot legend-cyan" style="display:inline-block;margin-right:.35rem;"></span> Complementary skills (they bring new expertise)</span>
            </div>

            <!-- Recommendation Cards (loaded via JS) -->
            <div id="rec-loading" style="text-align:center;padding:4rem 0;">
                <div style="font-size:3rem;animation:spin 1.5s linear infinite;display:inline-block;">⚙️</div>
                <p style="margin-top:1rem;color:var(--text-muted);">Analyzing student profiles...</p>
            </div>
            <div id="rec-grid" class="rec-grid" style="display:none;"></div>
            <div id="rec-empty" style="display:none;" class="empty-state">
                <div class="empty-state-icon">🔍</div>
                <h3>Not enough data</h3>
                <p>Add more skills and interests to your profile to get recommendations.</p>
                <a href="<?= BASE_URL ?>/student/edit_profile.php" class="btn btn-primary">Edit Profile</a>
            </div>
        </main>
    </div>

    <script>window.APP_BASE = '<?= BASE_URL ?>';</script>
    <script src="<?= BASE_URL ?>/assets/js/main.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/recommendations.js"></script>
    <script>
    const BASE = '<?= BASE_URL ?>';

    function buildStars(rating, max=5) {
        let s = '';
        for(let i=1; i<=max; i++) s += `<span style="color:${i<=rating?'#fbbf24':'#484f58'}">★</span>`;
        return s;
    }

    function buildCard(rec, rank) {
        const shared = rec.shared_skills.map(s => `<span class="skill-badge match-highlight">${s.icon} ${s.name}</span>`).join('');
        const comp   = rec.comp_skills.map(s => `<span class="skill-badge complement-highlight">${s.icon} ${s.name}</span>`).join('');
        const allSkills = shared + comp;

        return `
        <div class="rec-card" style="opacity:0;transform:translateY(20px);">
            <span class="rec-rank">#${rank}</span>
            <div class="rec-card-header">
                <img src="${rec.avatar}" class="avatar" alt="${rec.name}">
                <div>
                    <div class="rec-name">${rec.name}</div>
                    <div class="rec-meta">${rec.department || 'N/A'} · ${rec.year || ''} Year</div>
                    <div class="stars" style="margin-top:.3rem;font-size:1.1rem;">${buildStars(rec.stars)}</div>
                </div>
            </div>
            ${rec.bio ? `<p class="text-sm text-muted" style="margin-bottom:.75rem;line-height:1.5;">"${rec.bio}"</p>` : ''}
            <div class="rec-score-bar">
                <div class="rec-score-label">
                    <span class="text-xs text-muted">Match Score</span>
                    <span class="text-xs fw-600" style="color:#a5b4fc;">${rec.score}%</span>
                </div>
                <div class="progress-wrap">
                    <div class="progress-fill rec-score-fill" data-score="${rec.score}" style="width:0%"></div>
                </div>
            </div>
            ${allSkills ? `<div class="rec-skills" style="display:flex;flex-wrap:wrap;gap:.3rem;">${allSkills}</div>` : ''}
            <div style="display:flex;gap:.5rem;justify-content:space-between;align-items:center;margin-top:1rem;flex-wrap:wrap;">
                <span class="text-xs text-muted">🎯 ${rec.interest_match} shared interests · 📁 ${rec.project_count} projects</span>
                <a href="${BASE}/project/create.php?invite=${rec.id}" class="btn btn-secondary btn-sm">+ Invite to Project</a>
            </div>
        </div>`;
    }

    fetch('<?= BASE_URL ?>/api/recommend.php?n=5')
        .then(r => r.json())
        .then(data => {
            document.getElementById('rec-loading').style.display = 'none';
            if (!data.success || !data.recommendations.length) {
                document.getElementById('rec-empty').style.display = 'block';
                return;
            }
            const grid = document.getElementById('rec-grid');
            grid.innerHTML = data.recommendations.map((r, i) => buildCard(r, i+1)).join('');
            grid.style.display = 'grid';
            animateCards();
            animateScoreBars();
        })
        .catch(() => {
            document.getElementById('rec-loading').innerHTML = '<div class="alert alert-error">❌ Failed to load recommendations. Please refresh.</div>';
        });

    // Spin animation
    const recStyle = document.createElement('style');
    recStyle.textContent = '@keyframes spin { to { transform: rotate(360deg); } }';
    document.head.appendChild(recStyle);
    </script>
</body>
</html>

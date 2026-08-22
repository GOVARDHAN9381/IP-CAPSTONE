<?php
session_start();
require_once __DIR__ . '/config/db.php';

// Route to correct dashboard based on session
if (isFacultyLoggedIn()) {
    header('Location: ' . BASE_URL . '/faculty/dashboard.php');
    exit;
}
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/student/dashboard.php');
    exit;
}
// Not logged in → landing page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CollabIQ — AI-Powered Student Collaboration Platform</title>
    <meta name="description" content="CollabIQ uses AI to match students into the perfect project teams based on skills and interests. Register, collaborate, and build amazing projects together.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
    <style>
        /* Landing-specific overrides */
        .hero { min-height: 100vh; display:flex; flex-direction:column; justify-content:center; align-items:center; text-align:center; padding:2rem; position:relative; z-index:1; }
        .hero-badge { display:inline-flex; align-items:center; gap:.5rem; background:rgba(99,102,241,.15); border:1px solid rgba(99,102,241,.4); color:#a5b4fc; padding:.4rem 1rem; border-radius:99px; font-size:.8rem; font-weight:600; letter-spacing:.05em; margin-bottom:2rem; }
        .hero-title { font-size:clamp(2.5rem,7vw,5.5rem); font-weight:900; line-height:1.1; margin-bottom:1.5rem; }
        .hero-sub { font-size:clamp(1rem,2.5vw,1.3rem); color:var(--text-muted); max-width:600px; margin:0 auto 3rem; line-height:1.7; }
        .hero-actions { display:flex; gap:1rem; flex-wrap:wrap; justify-content:center; margin-bottom:5rem; }
        .features { display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:1.5rem; max-width:1100px; margin:0 auto; padding:0 1.5rem 5rem; }
        .feature-card { background:rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.08); border-radius:1.25rem; padding:2rem; text-align:center; transition:all .3s; }
        .feature-card:hover { transform:translateY(-5px); border-color:rgba(99,102,241,.4); background:rgba(99,102,241,.07); }
        .feature-icon { font-size:3rem; margin-bottom:1rem; }
        .feature-title { font-size:1.1rem; font-weight:700; margin-bottom:.75rem; }
        .feature-desc { color:var(--text-muted); font-size:.9rem; line-height:1.6; }
        .stats { display:flex; gap:3rem; justify-content:center; flex-wrap:wrap; margin-bottom:3rem; }
        .stat { text-align:center; }
        .stat-num { font-size:2.5rem; font-weight:800; background:var(--gradient-primary); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
        .stat-label { color:var(--text-muted); font-size:.85rem; }
    </style>
</head>
<body>
    <div class="auth-bg">
        <div class="auth-orb orb-1"></div>
        <div class="auth-orb orb-2"></div>
        <div class="auth-orb orb-3"></div>
    </div>

    <nav class="nav">
        <div class="nav-brand">🧠 Collab<span class="gradient-text">IQ</span></div>
        <div class="nav-actions">
            <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-ghost">Sign In</a>
            <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-primary">Get Started →</a>
        </div>
    </nav>

    <main>
        <section class="hero">
            <div class="hero-badge">✨ AI-Powered Team Matching</div>
            <h1 class="hero-title">
                Build Better Teams.<br>
                <span class="gradient-text">Ship Faster.</span>
            </h1>
            <p class="hero-sub">
                CollabIQ uses intelligent skill-matching AI to connect you with the perfect project teammates — based on your skills, interests, and collaboration style.
            </p>
            <div class="stats">
                <div class="stat"><div class="stat-num">AI</div><div class="stat-label">Team Matching</div></div>
                <div class="stat"><div class="stat-num">3</div><div class="stat-label">Powerful Modules</div></div>
                <div class="stat"><div class="stat-num">24</div><div class="stat-label">Skills Tracked</div></div>
                <div class="stat"><div class="stat-num">∞</div><div class="stat-label">Possibilities</div></div>
            </div>
            <div class="hero-actions">
                <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-primary btn-lg">🚀 Create Free Account</a>
                <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-ghost btn-lg">Sign In →</a>
            </div>
        </section>

        <section class="features">
            <div class="feature-card">
                <div class="feature-icon">🤖</div>
                <h3 class="feature-title">AI Team Recommendation</h3>
                <p class="feature-desc">Our algorithm analyzes your skills and interests to suggest the most compatible teammates, with star ratings and match scores.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📋</div>
                <h3 class="feature-title">Project & Task Management</h3>
                <p class="feature-desc">Create projects, assign tasks with priorities, track progress in real-time, and collaborate through threaded discussions.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3 class="feature-title">Faculty Analytics Dashboard</h3>
                <p class="feature-desc">Faculty get powerful insights into student participation, collaboration scores, team performance, and AI-generated recommendations.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔒</div>
                <h3 class="feature-title">Secure & Private</h3>
                <p class="feature-desc">Built with bcrypt password hashing, PDO prepared statements, and session-based authentication for maximum security.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📁</div>
                <h3 class="feature-title">File Sharing</h3>
                <p class="feature-desc">Upload and share project files, documents, and resources with your team members directly within the project workspace.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📈</div>
                <h3 class="feature-title">Progress Tracking</h3>
                <p class="feature-desc">Visual progress bars, task status cards, and project timelines keep your entire team aligned and on schedule.</p>
            </div>
        </section>
    </main>

    <footer style="text-align:center;padding:2rem;color:var(--text-muted);font-size:.85rem;border-top:1px solid rgba(255,255,255,.06);">
        <p>© 2026 CollabIQ · AI-Powered Student Collaboration Intelligence Platform · Built with PHP + MySQL</p>
    </footer>
</body>
</html>

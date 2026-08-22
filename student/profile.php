<?php
session_start();
require_once __DIR__ . '/../config/db.php';
requireLogin();

$db        = getDB();
$studentId = $_SESSION['student_id'];

// Fetch full student data
$stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$studentId]);
$student = $stmt->fetch();
$_SESSION['student'] = $student; // refresh

// Skills
$skills = $db->prepare("SELECT s.* FROM skills s JOIN student_skills ss ON ss.skill_id = s.id WHERE ss.student_id = ? ORDER BY s.category");
$skills->execute([$studentId]);
$mySkills = $skills->fetchAll();

// Interests
$ints = $db->prepare("SELECT i.* FROM interests i JOIN student_interests si ON si.interest_id = i.id WHERE si.student_id = ?");
$ints->execute([$studentId]);
$myInterests = $ints->fetchAll();

// Projects
$projs = $db->prepare("SELECT p.*, pm.role FROM projects p JOIN project_members pm ON pm.project_id = p.id WHERE pm.student_id = ? ORDER BY p.created_at DESC");
$projs->execute([$studentId]);
$projects = $projs->fetchAll();

// Stats
$done     = $db->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status = 'completed'"); $done->execute([$studentId]); $doneCnt = $done->fetchColumn();
$comments = $db->prepare("SELECT COUNT(*) FROM comments WHERE student_id = ?"); $comments->execute([$studentId]); $comCnt  = $comments->fetchColumn();
$uploads  = $db->prepare("SELECT COUNT(*) FROM uploads WHERE student_id = ?");  $uploads->execute([$studentId]);  $upCnt   = $uploads->fetchColumn();
$collabScore = min(100, $doneCnt*10 + $comCnt*3 + $upCnt*5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile — CollabIQ</title>
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
                <a href="<?= BASE_URL ?>/student/profile.php" class="sidebar-link active"><span class="icon">👤</span> My Profile</a>
                <a href="<?= BASE_URL ?>/student/recommendations.php" class="sidebar-link"><span class="icon">🤖</span> AI Recommendations</a>
            </div>
            <div class="sidebar-section">
                <div class="sidebar-label">Projects</div>
                <a href="<?= BASE_URL ?>/project/create.php" class="sidebar-link"><span class="icon">➕</span> New Project</a>
            </div>
        </aside>

        <main class="main-content">
            <!-- Profile Hero -->
            <div class="profile-hero">
                <img src="<?= generateAvatar($student['name']) ?>" class="avatar-lg" alt="">
                <div class="profile-hero-info" style="flex:1;">
                    <h2><?= sanitize($student['name']) ?></h2>
                    <p><?= sanitize($student['department'] ?? 'Department not set') ?> · <?= $student['year'] ?> Year</p>
                    <?php if ($student['bio']): ?>
                    <p style="margin-top:.5rem;color:rgba(255,255,255,.75);font-size:.9rem;"><?= sanitize($student['bio']) ?></p>
                    <?php endif; ?>
                    <div style="display:flex;gap:.75rem;margin-top:1rem;flex-wrap:wrap;">
                        <?php if ($student['github_url']): ?>
                        <a href="<?= sanitize($student['github_url']) ?>" target="_blank" class="btn" style="background:rgba(255,255,255,.2);color:#fff;border:none;font-size:.8rem;">🐙 GitHub</a>
                        <?php endif; ?>
                        <?php if ($student['linkedin_url']): ?>
                        <a href="<?= sanitize($student['linkedin_url']) ?>" target="_blank" class="btn" style="background:rgba(255,255,255,.2);color:#fff;border:none;font-size:.8rem;">💼 LinkedIn</a>
                        <?php endif; ?>
                        <a href="<?= BASE_URL ?>/student/edit_profile.php" class="btn" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);font-size:.8rem;">✏️ Edit Profile</a>
                    </div>
                </div>
                <!-- Collab Score Ring -->
                <div style="text-align:center;">
                    <div style="position:relative;width:100px;height:100px;">
                        <svg viewBox="0 0 100 100" style="transform:rotate(-90deg);width:100%;height:100%;">
                            <circle cx="50" cy="50" r="42" fill="none" stroke="rgba(255,255,255,.15)" stroke-width="8"/>
                            <circle cx="50" cy="50" r="42" fill="none" stroke="#fff" stroke-width="8"
                                stroke-dasharray="<?= round(264 * $collabScore / 100) ?> 264"
                                stroke-linecap="round"/>
                        </svg>
                        <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;flex-direction:column;">
                            <span style="font-size:1.4rem;font-weight:800;color:#fff;"><?= $collabScore ?></span>
                            <span style="font-size:.55rem;color:rgba(255,255,255,.7);text-transform:uppercase;letter-spacing:.05em;">Score</span>
                        </div>
                    </div>
                    <p style="font-size:.75rem;color:rgba(255,255,255,.7);margin-top:.35rem;">Collaboration Score</p>
                </div>
            </div>

            <div class="grid-2">
                <!-- Stats -->
                <div class="data-card">
                    <div class="data-card-header"><h3 class="data-card-title">📊 Activity Stats</h3></div>
                    <div class="data-card-body">
                        <div class="stats-grid" style="grid-template-columns:1fr 1fr;">
                            <div class="stat-card">
                                <div class="stat-card-icon">📁</div>
                                <div class="stat-card-value"><?= count($projects) ?></div>
                                <div class="stat-card-label">Projects</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-card-icon">✅</div>
                                <div class="stat-card-value"><?= $doneCnt ?></div>
                                <div class="stat-card-label">Tasks Done</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-card-icon">💬</div>
                                <div class="stat-card-value"><?= $comCnt ?></div>
                                <div class="stat-card-label">Comments</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-card-icon">📁</div>
                                <div class="stat-card-value"><?= $upCnt ?></div>
                                <div class="stat-card-label">Files Uploaded</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Skills & Interests -->
                <div class="data-card">
                    <div class="data-card-header"><h3 class="data-card-title">⚡ Skills & Interests</h3>
                        <a href="<?= BASE_URL ?>/student/edit_profile.php" class="btn btn-secondary btn-sm">Edit</a>
                    </div>
                    <div class="data-card-body">
                        <p class="text-xs text-muted fw-600" style="margin-bottom:.5rem;text-transform:uppercase;letter-spacing:.07em;">Skills</p>
                        <div style="display:flex;flex-wrap:wrap;gap:.3rem;margin-bottom:1.25rem;">
                            <?php foreach ($mySkills as $s): ?>
                            <span class="skill-badge"><?= $s['icon'] ?> <?= sanitize($s['name']) ?></span>
                            <?php endforeach; ?>
                            <?php if (empty($mySkills)): ?><p class="text-muted text-sm">No skills added.</p><?php endif; ?>
                        </div>
                        <p class="text-xs text-muted fw-600" style="margin-bottom:.5rem;text-transform:uppercase;letter-spacing:.07em;">Interests</p>
                        <div style="display:flex;flex-wrap:wrap;gap:.3rem;">
                            <?php foreach ($myInterests as $i): ?>
                            <span class="interest-badge"><?= $i['icon'] ?> <?= sanitize($i['name']) ?></span>
                            <?php endforeach; ?>
                            <?php if (empty($myInterests)): ?><p class="text-muted text-sm">No interests added.</p><?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Projects -->
            <div class="data-card" style="margin-top:1.5rem;">
                <div class="data-card-header"><h3 class="data-card-title">📁 My Projects</h3></div>
                <div class="data-card-body" style="padding:0 1.5rem 1.5rem;">
                    <?php if (empty($projects)): ?>
                    <div class="empty-state"><p>No projects yet.</p></div>
                    <?php else: ?>
                    <div style="margin-top:1rem;">
                        <?php foreach ($projects as $p): ?>
                        <div class="task-row">
                            <span style="font-size:1.25rem;">📁</span>
                            <div style="flex:1;">
                                <div class="task-title"><a href="<?= BASE_URL ?>/project/view.php?id=<?= $p['id'] ?>"><?= sanitize($p['name']) ?></a></div>
                                <div class="task-meta">Status: <?= ucfirst($p['status']) ?> · Role: <?= ucfirst($p['role']) ?></div>
                            </div>
                            <?php if ($p['deadline']): ?><span class="text-xs text-muted">📅 <?= date('M d, Y', strtotime($p['deadline'])) ?></span><?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>window.APP_BASE = '<?= BASE_URL ?>';</script>
    <script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>

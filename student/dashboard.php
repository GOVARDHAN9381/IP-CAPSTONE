<?php
session_start();
require_once __DIR__ . '/../config/db.php';
requireLogin();

$db        = getDB();
$student   = currentStudent();
$studentId = $_SESSION['student_id'];

// Fetch student's skills
$skillsStmt = $db->prepare("
    SELECT s.name, s.icon, s.category FROM skills s
    JOIN student_skills ss ON ss.skill_id = s.id
    WHERE ss.student_id = ?
    ORDER BY s.category, s.name
");
$skillsStmt->execute([$studentId]);
$mySkills = $skillsStmt->fetchAll();

// Fetch student's projects
$projStmt = $db->prepare("
    SELECT p.*, pm.role,
           (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id) AS total_tasks,
           (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id AND t.status = 'completed') AS done_tasks,
           (SELECT COUNT(*) FROM project_members pm2 WHERE pm2.project_id = p.id) AS member_count
    FROM projects p
    JOIN project_members pm ON pm.project_id = p.id
    WHERE pm.student_id = ?
    ORDER BY p.created_at DESC
");
$projStmt->execute([$studentId]);
$myProjects = $projStmt->fetchAll();

// My pending tasks
$taskStmt = $db->prepare("
    SELECT t.*, p.name AS project_name FROM tasks t
    JOIN projects p ON p.id = t.project_id
    WHERE t.assigned_to = ? AND t.status != 'completed'
    ORDER BY t.due_date ASC
    LIMIT 5
");
$taskStmt->execute([$studentId]);
$myTasks = $taskStmt->fetchAll();

// Stats
$totalDone = $db->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status = 'completed'");
$totalDone->execute([$studentId]);
$doneCnt = $totalDone->fetchColumn();

$totalComments = $db->prepare("SELECT COUNT(*) FROM comments WHERE student_id = ?");
$totalComments->execute([$studentId]);
$commentCnt = $totalComments->fetchColumn();

$collabScore = min(100, ($doneCnt * 10) + ($commentCnt * 3));

$welcome = isset($_GET['welcome']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — CollabIQ</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dashboard.css">
</head>
<body>
    <!-- Nav -->
    <nav class="nav">
        <div class="nav-brand">🧠 Collab<span class="gradient-text">IQ</span></div>
        <div class="nav-user">
            <!-- Notification Bell -->
            <div class="notif-bell-wrap" id="notif-wrap" style="position:relative;">
                <button class="btn btn-ghost btn-sm" id="notif-btn" onclick="toggleNotifPanel()" style="position:relative;padding:.4rem .6rem;font-size:1.1rem;">
                    🔔
                    <span class="notif-badge" id="notif-badge" style="display:none;">0</span>
                </button>
                <div class="notif-panel" id="notif-panel" style="display:none;">
                    <div class="notif-panel-header">
                        <span style="font-weight:700;">🔔 Notifications</span>
                        <button onclick="markAllRead()" class="btn btn-ghost btn-sm" style="font-size:.75rem;padding:.2rem .5rem;">Mark all read</button>
                    </div>
                    <div id="notif-list"><div style="padding:1.5rem;text-align:center;color:#8b949e;font-size:.85rem;">Loading…</div></div>
                </div>
            </div>
            <img src="<?= generateAvatar($student['name']) ?>" class="nav-avatar" alt="<?= sanitize($student['name']) ?>">
            <span class="nav-name"><?= sanitize($student['name']) ?></span>
            <a href="<?= BASE_URL ?>/auth/logout.php" class="btn btn-ghost btn-sm">Sign Out</a>
        </div>
    </nav>

    <div class="app-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-section">
                <div class="sidebar-label">Main</div>
                <a href="<?= BASE_URL ?>/student/dashboard.php" class="sidebar-link active">
                    <span class="icon">🏠</span> Dashboard
                </a>
                <a href="<?= BASE_URL ?>/student/profile.php" class="sidebar-link">
                    <span class="icon">👤</span> My Profile
                </a>
                <a href="<?= BASE_URL ?>/student/recommendations.php" class="sidebar-link">
                    <span class="icon">🤖</span> AI Recommendations
                </a>
                <a href="<?= BASE_URL ?>/student/activity.php" class="sidebar-link">
                    <span class="icon">📊</span> Activity Feed
                </a>
                <a href="<?= BASE_URL ?>/ideas/index.php" class="sidebar-link">
                    <span class="icon">💡</span> Idea Board
                </a>
            </div>
            <div class="sidebar-section">
                <div class="sidebar-label">Projects</div>
                <a href="<?= BASE_URL ?>/project/create.php" class="sidebar-link">
                    <span class="icon">➕</span> New Project
                </a>
                <?php foreach(array_slice($myProjects, 0, 4) as $p): ?>
                <a href="<?= BASE_URL ?>/project/view.php?id=<?= $p['id'] ?>" class="sidebar-link">
                    <span class="icon">📁</span> <?= sanitize(mb_strimwidth($p['name'],0,20,'…')) ?>
                </a>
                <?php endforeach; ?>
            </div>
        </aside>

        <!-- Main -->
        <main class="main-content">
            <?php if ($welcome): ?>
            <div class="alert alert-success" style="margin-bottom:1.5rem;">
                🎉 Welcome to CollabIQ, <?= sanitize(explode(' ',$student['name'])[0]) ?>! Your profile is set up. Check out your AI-recommended teammates below.
            </div>
            <?php endif; ?>

            <!-- Welcome Banner -->
            <div class="welcome-banner">
                <div>
                    <h2>👋 Welcome back, <?= sanitize(explode(' ',$student['name'])[0]) ?>!</h2>
                    <p>Track your projects, tasks and discover your ideal teammates.</p>
                </div>
                <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
                    <a href="<?= BASE_URL ?>/student/recommendations.php" class="btn" style="background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.3);">🤖 View Recommendations</a>
                    <a href="<?= BASE_URL ?>/project/create.php" class="btn" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.25);">➕ New Project</a>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-icon">📁</div>
                    <div class="stat-card-value" data-target="<?= count($myProjects) ?>"><?= count($myProjects) ?></div>
                    <div class="stat-card-label">Active Projects</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon">✅</div>
                    <div class="stat-card-value" data-target="<?= $doneCnt ?>"><?= $doneCnt ?></div>
                    <div class="stat-card-label">Tasks Completed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon">💬</div>
                    <div class="stat-card-value" data-target="<?= $commentCnt ?>"><?= $commentCnt ?></div>
                    <div class="stat-card-label">Comments Posted</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon">⭐</div>
                    <div class="stat-card-value" data-target="<?= $collabScore ?>"><?= $collabScore ?></div>
                    <div class="stat-card-label">Collaboration Score</div>
                </div>
            </div>

            <div class="grid-2" style="gap:1.5rem;">
                <!-- My Tasks -->
                <div class="data-card">
                    <div class="data-card-header">
                        <h3 class="data-card-title">📋 My Pending Tasks</h3>
                        <span class="badge badge-amber"><?= count($myTasks) ?> pending</span>
                    </div>
                    <div class="data-card-body" style="padding:0 1.5rem 1.5rem;">
                        <?php if (empty($myTasks)): ?>
                        <div class="empty-state" style="padding:2rem 0;">
                            <div class="empty-state-icon">🎉</div>
                            <p>All caught up! No pending tasks.</p>
                        </div>
                        <?php else: ?>
                        <?php foreach ($myTasks as $task): ?>
                        <div class="task-row">
                            <div style="flex:1;">
                                <div class="task-title"><?= sanitize($task['title']) ?></div>
                                <div class="task-meta">📁 <?= sanitize($task['project_name']) ?> · 📅 <?= $task['due_date'] ? date('M d', strtotime($task['due_date'])) : 'No deadline' ?></div>
                            </div>
                            <span class="badge status-<?= $task['status'] ?>"><?= ucfirst(str_replace('_',' ',$task['status'])) ?></span>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- My Skills -->
                <div class="data-card">
                    <div class="data-card-header">
                        <h3 class="data-card-title">⚡ My Skills</h3>
                        <a href="<?= BASE_URL ?>/student/edit_profile.php" class="btn btn-secondary btn-sm">Edit</a>
                    </div>
                    <div class="data-card-body">
                        <?php if (empty($mySkills)): ?>
                        <div class="empty-state" style="padding:2rem 0;">
                            <p>No skills added yet. <a href="<?= BASE_URL ?>/student/edit_profile.php">Add skills →</a></p>
                        </div>
                        <?php else: ?>
                        <div style="display:flex;flex-wrap:wrap;gap:.3rem;">
                            <?php foreach ($mySkills as $s): ?>
                            <span class="skill-badge"><?= $s['icon'] ?> <?= sanitize($s['name']) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <div style="margin-top:1.5rem;">
                            <div class="flex-center justify-between mb-1">
                                <span class="text-sm fw-600">Collaboration Score</span>
                                <span class="text-sm text-muted"><?= $collabScore ?>/100</span>
                            </div>
                            <div class="progress-wrap">
                                <div class="progress-fill" style="width:<?= $collabScore ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- My Projects -->
            <div class="data-card" style="margin-top:1.5rem;">
                <div class="data-card-header">
                    <h3 class="data-card-title">📁 My Projects</h3>
                    <a href="<?= BASE_URL ?>/project/create.php" class="btn btn-primary btn-sm">➕ New Project</a>
                </div>
                <div class="data-card-body" style="padding:0 1.5rem 1.5rem;">
                    <?php if (empty($myProjects)): ?>
                    <div class="empty-state" style="padding:3rem 0;">
                        <div class="empty-state-icon">🚀</div>
                        <h3>No projects yet</h3>
                        <p>Create your first project or wait to be added to one.</p>
                        <a href="<?= BASE_URL ?>/project/create.php" class="btn btn-primary">Create Project</a>
                    </div>
                    <?php else: ?>
                    <div class="projects-grid" style="padding-top:1rem;">
                        <?php foreach ($myProjects as $proj):
                            $pct = $proj['total_tasks'] > 0 ? round($proj['done_tasks']/$proj['total_tasks']*100) : 0;
                            $statusColors = ['planning'=>'badge-violet','active'=>'badge-cyan','completed'=>'badge-emerald'];
                        ?>
                        <a href="<?= BASE_URL ?>/project/view.php?id=<?= $proj['id'] ?>" style="text-decoration:none;">
                            <div class="project-card">
                                <div class="project-card-header">
                                    <div>
                                        <div class="project-name"><?= sanitize($proj['name']) ?></div>
                                        <span class="badge <?= $statusColors[$proj['status']] ?? 'badge-gray' ?>"><?= ucfirst($proj['status']) ?></span>
                                    </div>
                                    <?php if ($proj['role'] === 'leader'): ?>
                                    <span class="badge badge-amber">👑 Leader</span>
                                    <?php endif; ?>
                                </div>
                                <p class="project-desc"><?= sanitize($proj['description'] ?? '') ?></p>
                                <div style="margin-bottom:.75rem;">
                                    <div class="flex-center justify-between mb-1">
                                        <span class="text-xs text-muted">Progress</span>
                                        <span class="text-xs fw-600"><?= $pct ?>%</span>
                                    </div>
                                    <div class="progress-wrap" style="height:6px;">
                                        <div class="progress-fill" style="width:<?= $pct ?>%"></div>
                                    </div>
                                </div>
                                <div class="project-footer">
                                    <span class="text-xs text-muted">👥 <?= $proj['member_count'] ?> members · <?= $proj['done_tasks'] ?>/<?= $proj['total_tasks'] ?> tasks</span>
                                    <?php if ($proj['deadline']): ?>
                                    <span class="text-xs text-muted">📅 <?= date('M d, Y', strtotime($proj['deadline'])) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>window.APP_BASE = '<?= BASE_URL ?>';</script>
    <script src="<?= BASE_URL ?>/assets/js/main.js"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/chatbot.css">
    <script>
    window.CHATBOT_CONTEXT = {
        name:        '<?= sanitize(explode(' ',$student['name'])[0]) ?>',
        page:        'dashboard',
        projects:    <?= count($myProjects) ?>,
        pending:     <?= count($myTasks) ?>,
        done:        <?= $doneCnt ?>,
        score:       <?= $collabScore ?>,
        skills:      <?= count($mySkills) ?>
    };
    </script>
    <script src="<?= BASE_URL ?>/assets/js/chatbot.js"></script>
    <script>
    // ── Notification Bell ─────────────────────────────────────
    let notifOpen = false;
    function toggleNotifPanel() {
        const panel = document.getElementById('notif-panel');
        notifOpen = !notifOpen;
        panel.style.display = notifOpen ? 'block' : 'none';
        if (notifOpen) loadNotifications();
    }
    document.addEventListener('click', function(e) {
        if (!document.getElementById('notif-wrap')?.contains(e.target)) {
            document.getElementById('notif-panel').style.display = 'none';
            notifOpen = false;
        }
    });
    function loadNotifications() {
        fetch(APP_BASE + '/api/notifications.php?all=1')
            .then(r => r.json()).then(d => {
                const list = document.getElementById('notif-list');
                if (!d.notifications || !d.notifications.length) {
                    list.innerHTML = '<div style="padding:1.5rem;text-align:center;color:#8b949e;font-size:.85rem;">🎉 All caught up! No notifications.</div>';
                    return;
                }
                list.innerHTML = d.notifications.map(n => `
                    <a href="${n.link || '#'}" class="notif-item ${n.is_read == 0 ? 'unread' : ''}">
                        <div class="notif-msg">${n.message}</div>
                        <div class="notif-time">${n.created_at}</div>
                    </a>`).join('');
            }).catch(() => {});
    }
    function markAllRead() {
        fetch(APP_BASE + '/api/notifications.php?mark_read=1').then(() => {
            document.getElementById('notif-badge').style.display = 'none';
            loadNotifications();
        });
    }
    function pollUnread() {
        fetch(APP_BASE + '/api/notifications.php?unread_only=1')
            .then(r => r.json()).then(d => {
                const badge = document.getElementById('notif-badge');
                if (d.unread > 0) {
                    badge.textContent = d.unread > 9 ? '9+' : d.unread;
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                }
            }).catch(() => {});
    }
    pollUnread();
    setInterval(pollUnread, 60000); // poll every 60 seconds
    </script>
</body>
</html>

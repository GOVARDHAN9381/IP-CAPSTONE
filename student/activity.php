<?php
session_start();
require_once __DIR__ . '/../config/db.php';
requireLogin();

$db        = getDB();
$studentId = $_SESSION['student_id'];
$student   = currentStudent();

// Get activity for all projects I am in
$actStmt = $db->prepare("
    SELECT al.*, s.name AS actor_name,
           p.name AS project_name
    FROM activity_log al
    LEFT JOIN students s ON s.id = al.student_id
    LEFT JOIN projects p ON p.id  = al.project_id
    WHERE al.student_id = ?
       OR al.project_id IN (SELECT project_id FROM project_members WHERE student_id = ?)
    ORDER BY al.created_at DESC
    LIMIT 80
");
$actStmt->execute([$studentId, $studentId]);
$activities = $actStmt->fetchAll();

// Personal stats
$totalAct = count($activities);
$myAct    = array_filter($activities, fn($a) => $a['student_id'] == $studentId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Feed — CollabIQ</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dashboard.css">
    <style>
        .activity-hero{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-bottom:2rem;}
        .activity-hero h2{font-size:1.6rem;font-weight:800;}
        .timeline{position:relative;padding-left:2.5rem;}
        .timeline::before{content:'';position:absolute;left:.85rem;top:0;bottom:0;width:2px;background:var(--border-default);}
        .timeline-item{position:relative;margin-bottom:1.25rem;}
        .timeline-dot{position:absolute;left:-1.75rem;top:.15rem;width:28px;height:28px;border-radius:50%;background:var(--bg-tertiary);border:2px solid var(--border-default);display:flex;align-items:center;justify-content:center;font-size:.85rem;z-index:1;}
        .timeline-card{background:var(--bg-card);border:1px solid var(--border-subtle);border-radius:var(--radius-md);padding:1rem 1.25rem;transition:var(--transition);}
        .timeline-card:hover{border-color:rgba(99,102,241,.3);}
        .timeline-actor{font-weight:600;font-size:.9rem;color:var(--text-primary);}
        .timeline-detail{color:var(--text-secondary);font-size:.875rem;margin:.2rem 0;}
        .timeline-meta{font-size:.75rem;color:var(--text-muted);display:flex;align-items:center;gap:.75rem;margin-top:.4rem;}
        .timeline-project{display:inline-flex;align-items:center;gap:.3rem;padding:.1rem .5rem;border-radius:99px;background:rgba(99,102,241,.12);color:#a5b4fc;font-size:.75rem;font-weight:600;}
        .day-divider{font-size:.8rem;font-weight:700;color:var(--text-muted);letter-spacing:.06em;text-transform:uppercase;padding:.5rem 0 .75rem;display:flex;align-items:center;gap:.75rem;}
        .day-divider::after{content:'';flex:1;height:1px;background:var(--border-subtle);}
        .empty-activity{text-align:center;padding:4rem;color:var(--text-muted);}
    </style>
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
                <a href="<?= BASE_URL ?>/student/recommendations.php" class="sidebar-link"><span class="icon">🤖</span> AI Recommendations</a>
                <a href="<?= BASE_URL ?>/student/activity.php" class="sidebar-link active"><span class="icon">📊</span> Activity Feed</a>
                <a href="<?= BASE_URL ?>/ideas/index.php" class="sidebar-link"><span class="icon">💡</span> Idea Board</a>
            </div>
            <div class="sidebar-section">
                <div class="sidebar-label">Projects</div>
                <a href="<?= BASE_URL ?>/project/create.php" class="sidebar-link"><span class="icon">➕</span> New Project</a>
            </div>
        </aside>

        <main class="main-content">
            <div class="activity-hero">
                <div>
                    <h2>📊 Activity Feed</h2>
                    <p style="color:var(--text-muted);margin:0;">Everything happening across your projects.</p>
                </div>
                <div style="display:flex;gap:1rem;">
                    <div class="stat-card" style="min-width:0;padding:.75rem 1.25rem;">
                        <div class="stat-card-icon" style="font-size:1.25rem;">⚡</div>
                        <div class="stat-card-value" style="font-size:1.5rem;" data-target="<?= count($myAct) ?>"><?= count($myAct) ?></div>
                        <div class="stat-card-label">My Actions</div>
                    </div>
                    <div class="stat-card" style="min-width:0;padding:.75rem 1.25rem;">
                        <div class="stat-card-icon" style="font-size:1.25rem;">🌐</div>
                        <div class="stat-card-value" style="font-size:1.5rem;" data-target="<?= $totalAct ?>"><?= $totalAct ?></div>
                        <div class="stat-card-label">Total Events</div>
                    </div>
                </div>
            </div>

            <?php if(empty($activities)): ?>
            <div class="empty-activity">
                <div style="font-size:4rem;margin-bottom:1rem;">🌱</div>
                <h3 style="color:var(--text-primary);">No activity yet</h3>
                <p>Start working on projects, completing tasks, and posting comments to see your activity here.</p>
                <a href="<?= BASE_URL ?>/project/create.php" class="btn btn-primary" style="margin-top:1rem;">Create a Project</a>
            </div>
            <?php else:
                $currentDay = null;
            ?>
            <div class="timeline">
                <?php foreach($activities as $act):
                    $day = date('Y-m-d', strtotime($act['created_at']));
                    if ($day !== $currentDay):
                        $currentDay = $day;
                        $dayLabel = date('l, F j', strtotime($act['created_at']));
                        if ($day === date('Y-m-d')) $dayLabel = 'Today';
                        elseif ($day === date('Y-m-d', strtotime('-1 day'))) $dayLabel = 'Yesterday';
                ?>
                <div class="day-divider"><?= $dayLabel ?></div>
                <?php endif; ?>
                <div class="timeline-item">
                    <div class="timeline-dot"><?= $act['icon'] ?? '📌' ?></div>
                    <div class="timeline-card">
                        <div class="timeline-actor"><?= sanitize($act['actor_name'] ?? 'System') ?></div>
                        <div class="timeline-detail"><?= sanitize($act['detail']) ?></div>
                        <div class="timeline-meta">
                            <span>⏰ <?= date('g:i A', strtotime($act['created_at'])) ?></span>
                            <?php if($act['project_name']): ?>
                            <span class="timeline-project">📁 <?= sanitize($act['project_name']) ?></span>
                            <?php endif; ?>
                            <?php if($act['student_id'] == $studentId): ?>
                            <span style="color:#6366f1;font-weight:600;">You</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <script>window.APP_BASE = '<?= BASE_URL ?>';</script>
    <script src="<?= BASE_URL ?>/assets/js/main.js"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/chatbot.css">
    <script>window.CHATBOT_CONTEXT={name:'<?= sanitize(explode(' ',$student['name'])[0]) ?>',page:'activity'};</script>
    <script src="<?= BASE_URL ?>/assets/js/chatbot.js"></script>
</body>
</html>

<?php
session_start();
require_once __DIR__ . '/../config/db.php';
requireFaculty();

$db      = getDB();
$faculty = currentFaculty();

// Show single student detail if ?id= is set
$viewId  = (int)($_GET['id'] ?? 0);
$student = null;
$studentSkills    = [];
$studentInterests = [];
$studentProjects  = [];
$studentTasks     = [];

if ($viewId) {
    $stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$viewId]);
    $student = $stmt->fetch();

    if ($student) {
        $sk = $db->prepare("SELECT s.name, s.icon FROM skills s JOIN student_skills ss ON ss.skill_id = s.id WHERE ss.student_id = ?");
        $sk->execute([$viewId]);
        $studentSkills = $sk->fetchAll();

        $in = $db->prepare("SELECT i.name, i.icon FROM interests i JOIN student_interests si ON si.interest_id = i.id WHERE si.student_id = ?");
        $in->execute([$viewId]);
        $studentInterests = $in->fetchAll();

        $pr = $db->prepare("SELECT p.*, pm.role FROM projects p JOIN project_members pm ON pm.project_id = p.id WHERE pm.student_id = ? ORDER BY p.created_at DESC");
        $pr->execute([$viewId]);
        $studentProjects = $pr->fetchAll();

        $tk = $db->prepare("SELECT t.*, p.name AS project_name FROM tasks t JOIN projects p ON p.id = t.project_id WHERE t.assigned_to = ? ORDER BY t.status, t.due_date");
        $tk->execute([$viewId]);
        $studentTasks = $tk->fetchAll();
    }
}

// All students list
$allStudents = $db->query("
    SELECT s.id, s.name, s.department, s.year, s.created_at,
           (SELECT COUNT(*) FROM tasks t WHERE t.assigned_to = s.id AND t.status = 'completed') AS done,
           (SELECT COUNT(*) FROM comments c WHERE c.student_id = s.id) AS comments,
           (SELECT COUNT(*) FROM uploads u WHERE u.student_id = s.id) AS uploads,
           (SELECT COUNT(*) FROM project_members pm WHERE pm.student_id = s.id) AS project_count
    FROM students s ORDER BY s.name
")->fetchAll();

foreach ($allStudents as &$s) {
    $s['score'] = min(100, ($s['done']*10) + ($s['comments']*3) + ($s['uploads']*5));
    $s['score_class'] = $s['score'] >= 60 ? 'score-high' : ($s['score'] >= 30 ? 'score-medium' : 'score-low');
}
unset($s);

$viewStudent = $student; // alias
$totalTaskCnt= count($studentTasks);
$doneCount   = count(array_filter($studentTasks, fn($t) => $t['status'] === 'completed'));
$doneCnt     = $doneCount; // fixed: was incorrectly computing via array_sum on strings
$viewScore   = 0; // will be calculated properly below if student is set

if ($viewStudent) {
    $cmt = $db->prepare("SELECT COUNT(*) FROM comments WHERE student_id = ?"); $cmt->execute([$viewId]); $cmtCnt = $cmt->fetchColumn();
    $upl = $db->prepare("SELECT COUNT(*) FROM uploads WHERE student_id = ?");  $upl->execute([$viewId]); $uplCnt = $upl->fetchColumn();
    $viewScore = min(100, $doneCount*10 + $cmtCnt*3 + $uplCnt*5);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Reports — CollabIQ</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dashboard.css">
</head>
<body>
    <nav class="nav">
        <div class="nav-brand">🧠 Collab<span class="gradient-text">IQ</span></div>
        <div class="nav-user">
            <span style="font-size:.85rem;color:var(--text-muted);">Faculty</span>
            <span class="nav-name"><?= sanitize($faculty['name']) ?></span>
            <a href="<?= BASE_URL ?>/auth/logout.php" class="btn btn-ghost btn-sm">Sign Out</a>
        </div>
    </nav>

    <div class="app-layout">
        <aside class="sidebar">
            <div class="sidebar-section">
                <div class="sidebar-label">Faculty Panel</div>
                <a href="<?= BASE_URL ?>/faculty/dashboard.php" class="sidebar-link"><span class="icon">📊</span> Analytics Dashboard</a>
                <a href="<?= BASE_URL ?>/faculty/students.php" class="sidebar-link active"><span class="icon">👥</span> Student Reports</a>
                <a href="<?= BASE_URL ?>/faculty/report.php" class="sidebar-link"><span class="icon">🖨️</span> Print Report</a>
            </div>
        </aside>

        <main class="main-content">
            <?php if ($viewStudent): ?>
            <!-- Single Student View -->
            <div style="display:flex;align-items:center;gap:1rem;margin-bottom:2rem;">
                <a href="<?= BASE_URL ?>/faculty/students.php" class="btn btn-ghost btn-sm">← Back to All Students</a>
            </div>

            <div class="profile-hero">
                <img src="<?= generateAvatar($viewStudent['name']) ?>" class="avatar-lg" alt="">
                <div style="flex:1;">
                    <h2><?= sanitize($viewStudent['name']) ?></h2>
                    <p><?= sanitize($viewStudent['department']??'') ?> · <?= $viewStudent['year'] ?> Year</p>
                    <?php if ($viewStudent['bio']): ?>
                    <p style="margin-top:.4rem;color:rgba(255,255,255,.75);font-size:.85rem;"><?= sanitize($viewStudent['bio']) ?></p>
                    <?php endif; ?>
                </div>
                <div style="text-align:center;">
                    <div style="font-size:2.5rem;font-weight:900;color:#fff;"><?= $viewScore ?></div>
                    <div style="font-size:.75rem;color:rgba(255,255,255,.7);">Collaboration Score</div>
                    <div style="margin-top:.5rem;">
                        <span class="badge <?= $viewScore>=60?'badge-emerald':($viewScore>=30?'badge-amber':'badge-rose') ?>">
                            <?= $viewScore>=60?'High':($viewScore>=30?'Medium':'Low') ?> Participation
                        </span>
                    </div>
                </div>
            </div>

            <div class="grid-2">
                <div class="data-card">
                    <div class="data-card-header"><h3 class="data-card-title">⚡ Skills</h3></div>
                    <div class="data-card-body">
                        <?php foreach ($studentSkills as $s): ?><span class="skill-badge"><?= $s['icon'] ?> <?= sanitize($s['name']) ?></span><?php endforeach; ?>
                        <?php if (empty($studentSkills)): ?><p class="text-muted text-sm">No skills added.</p><?php endif; ?>
                    </div>
                </div>
                <div class="data-card">
                    <div class="data-card-header"><h3 class="data-card-title">🎯 Interests</h3></div>
                    <div class="data-card-body">
                        <?php foreach ($studentInterests as $i): ?><span class="interest-badge"><?= $i['icon'] ?> <?= sanitize($i['name']) ?></span><?php endforeach; ?>
                        <?php if (empty($studentInterests)): ?><p class="text-muted text-sm">No interests added.</p><?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="data-card" style="margin-top:1.5rem;">
                <div class="data-card-header"><h3 class="data-card-title">📋 Assigned Tasks</h3></div>
                <div class="data-card-body" style="padding:0;">
                    <?php if (empty($studentTasks)): ?>
                    <div class="empty-state"><p>No tasks assigned.</p></div>
                    <?php else: ?>
                    <div class="table-wrapper">
                        <table>
                            <thead><tr><th>Task</th><th>Project</th><th>Status</th><th>Due Date</th></tr></thead>
                            <tbody>
                                <?php foreach ($studentTasks as $t): ?>
                                <tr>
                                    <td class="fw-600 text-sm"><?= sanitize($t['title']) ?></td>
                                    <td class="text-sm text-muted"><?= sanitize($t['project_name']) ?></td>
                                    <td><span class="badge status-<?= $t['status'] ?>"><?= ucfirst(str_replace('_',' ',$t['status'])) ?></span></td>
                                    <td class="text-sm text-muted"><?= $t['due_date'] ? date('M d, Y', strtotime($t['due_date'])) : '—' ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php else: ?>
            <!-- All Students List -->
            <div class="page-header">
                <h1>👥 Student Reports</h1>
                <p>Detailed participation metrics for each student. Click a student to view their full report.</p>
            </div>

            <div class="data-card">
                <div class="data-card-body" style="padding:0;">
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr><th>Student</th><th>Dept / Year</th><th>Projects</th><th>Tasks Done</th><th>Comments</th><th>Uploads</th><th>Collab Score</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allStudents as $s): ?>
                                <tr style="cursor:pointer;" onclick="location.href='<?= BASE_URL ?>/faculty/students.php?id=<?= $s['id'] ?>'">
                                    <td>
                                        <div style="display:flex;align-items:center;gap:.75rem;">
                                            <img src="<?= generateAvatar($s['name']) ?>" class="avatar avatar-sm" alt="">
                                            <span class="fw-600"><?= sanitize($s['name']) ?></span>
                                        </div>
                                    </td>
                                    <td class="text-sm text-muted"><?= sanitize($s['department']??'N/A') ?> · <?= $s['year'] ?></td>
                                    <td><span class="badge badge-indigo"><?= $s['project_count'] ?></span></td>
                                    <td><span class="badge badge-emerald"><?= $s['done'] ?></span></td>
                                    <td><span class="badge badge-cyan"><?= $s['comments'] ?></span></td>
                                    <td><span class="badge badge-violet"><?= $s['uploads'] ?></span></td>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:.75rem;">
                                            <div class="progress-wrap" style="flex:1;height:6px;">
                                                <div class="progress-fill" style="width:<?= $s['score'] ?>%"></div>
                                            </div>
                                            <span class="collab-score <?= $s['score_class'] ?>"><?= $s['score'] ?></span>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
    <script>window.APP_BASE = '<?= BASE_URL ?>';</script>
    <script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>

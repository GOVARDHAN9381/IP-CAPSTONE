<?php
session_start();
require_once __DIR__ . '/../config/db.php';
requireFaculty();

$db      = getDB();
$faculty = currentFaculty();

// Summary stats
$totalStudents = $db->query("SELECT COUNT(*) FROM students")->fetchColumn();
$totalProjects = $db->query("SELECT COUNT(*) FROM projects")->fetchColumn();
$activeProjects = $db->query("SELECT COUNT(*) FROM projects WHERE status = 'active'")->fetchColumn();
$totalTasks = $db->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
$doneTasks  = $db->query("SELECT COUNT(*) FROM tasks WHERE status = 'completed'")->fetchColumn();
$taskCompletionRate = $totalTasks > 0 ? round($doneTasks / $totalTasks * 100) : 0;
$inProgressTasks = $db->query("SELECT COUNT(*) FROM tasks WHERE status = 'in_progress'")->fetchColumn();
$pendingTasks    = $db->query("SELECT COUNT(*) FROM tasks WHERE status = 'pending'")->fetchColumn();

// Students with collaboration scores
$studentsData = $db->query("
    SELECT s.id, s.name, s.department, s.year, s.created_at,
           (SELECT COUNT(*) FROM tasks t WHERE t.assigned_to = s.id AND t.status = 'completed') AS done,
           (SELECT COUNT(*) FROM comments c WHERE c.student_id = s.id) AS comments,
           (SELECT COUNT(*) FROM uploads u WHERE u.student_id = s.id) AS uploads,
           (SELECT COUNT(*) FROM project_members pm WHERE pm.student_id = s.id) AS project_count
    FROM students s
    ORDER BY s.name
")->fetchAll();

foreach ($studentsData as &$s) {
    $s['score'] = min(100, ($s['done'] * 10) + ($s['comments'] * 3) + ($s['uploads'] * 5));
    $s['score_class'] = $s['score'] >= 60 ? 'score-high' : ($s['score'] >= 30 ? 'score-medium' : 'score-low');
}
unset($s);

// Sort by score for chart
usort($studentsData, fn($a,$b) => $b['score'] <=> $a['score']);
$chartNames  = json_encode(array_column($studentsData, 'name'));
$chartScores = json_encode(array_column($studentsData, 'score'));

// Projects with their stats
$projects = $db->query("
    SELECT p.*, s.name AS leader_name,
           (SELECT COUNT(*) FROM project_members pm WHERE pm.project_id = p.id) AS member_count,
           (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id) AS total_tasks,
           (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id AND t.status = 'completed') AS done_tasks
    FROM projects p
    LEFT JOIN students s ON s.id = p.leader_id
    ORDER BY p.created_at DESC
")->fetchAll();

$avgScore = count($studentsData) > 0 ? round(array_sum(array_column($studentsData,'score')) / count($studentsData)) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard — CollabIQ</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
                <a href="<?= BASE_URL ?>/faculty/dashboard.php" class="sidebar-link active"><span class="icon">📊</span> Analytics Dashboard</a>
                <a href="<?= BASE_URL ?>/faculty/students.php" class="sidebar-link"><span class="icon">👥</span> Student Reports</a>
                <a href="<?= BASE_URL ?>/faculty/report.php" class="sidebar-link"><span class="icon">🖨️</span> Print Report</a>
            </div>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <h1>📊 Faculty Analytics Dashboard</h1>
                <p>Overview of student participation, project health, and collaboration intelligence.</p>
            </div>

            <!-- Summary Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-icon">🎓</div>
                    <div class="stat-card-value" data-target="<?= $totalStudents ?>"><?= $totalStudents ?></div>
                    <div class="stat-card-label">Registered Students</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon">📁</div>
                    <div class="stat-card-value" data-target="<?= $activeProjects ?>"><?= $activeProjects ?></div>
                    <div class="stat-card-label">Active Projects</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon">✅</div>
                    <div class="stat-card-value" data-target="<?= $taskCompletionRate ?>"><?= $taskCompletionRate ?></div>
                    <div class="stat-card-label">Task Completion %</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon">⭐</div>
                    <div class="stat-card-value" data-target="<?= $avgScore ?>"><?= $avgScore ?></div>
                    <div class="stat-card-label">Avg Collaboration Score</div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="analytics-grid">
                <div class="chart-card">
                    <h3 class="chart-title">📈 Student Collaboration Scores</h3>
                    <div class="chart-container">
                        <canvas id="collab-bar"></canvas>
                    </div>
                </div>
                <div class="chart-card">
                    <h3 class="chart-title">📊 Task Status Distribution</h3>
                    <div class="chart-container">
                        <canvas id="task-doughnut"></canvas>
                    </div>
                </div>
            </div>

            <!-- AI Insights -->
            <div class="data-card" style="margin-bottom:1.5rem;">
                <div class="data-card-header"><h3 class="data-card-title">🤖 AI Faculty Insights</h3></div>
                <div class="data-card-body" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;">
                    <?php
                    // Top performer
                    $topPerformer = $studentsData[0] ?? null;
                    // Struggling students (score < 30)
                    $struggling = array_filter($studentsData, fn($s) => $s['score'] < 30);
                    // Most active project
                    $mostActive = null; $maxComments = 0;
                    foreach ($projects as $p) {
                        $cnt = $db->prepare("SELECT COUNT(*) FROM comments WHERE project_id = ?"); $cnt->execute([$p['id']]); $c = $cnt->fetchColumn();
                        if ($c > $maxComments) { $maxComments = $c; $mostActive = $p; }
                    }
                    ?>
                    <div style="background:rgba(16,185,129,.08);border:1px solid rgba(16,185,129,.2);border-radius:var(--radius-md);padding:1.25rem;">
                        <div style="font-size:1.5rem;margin-bottom:.5rem;">🏆</div>
                        <h4 style="color:#6ee7b7;margin-bottom:.25rem;">Top Performer</h4>
                        <p class="text-sm"><?= $topPerformer ? sanitize($topPerformer['name']) . ' — Score: ' . $topPerformer['score'] : 'N/A' ?></p>
                    </div>
                    <div style="background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.2);border-radius:var(--radius-md);padding:1.25rem;">
                        <div style="font-size:1.5rem;margin-bottom:.5rem;">⚠️</div>
                        <h4 style="color:#fcd34d;margin-bottom:.25rem;">Needs Attention</h4>
                        <p class="text-sm"><?= count($struggling) ?> student(s) have a collaboration score below 30.</p>
                    </div>
                    <div style="background:rgba(6,182,212,.08);border:1px solid rgba(6,182,212,.2);border-radius:var(--radius-md);padding:1.25rem;">
                        <div style="font-size:1.5rem;margin-bottom:.5rem;">💬</div>
                        <h4 style="color:#67e8f9;margin-bottom:.25rem;">Most Active Project</h4>
                        <p class="text-sm"><?= $mostActive ? sanitize($mostActive['name']) . " ($maxComments messages)" : 'No activity yet.' ?></p>
                    </div>
                    <div style="background:rgba(99,102,241,.08);border:1px solid rgba(99,102,241,.2);border-radius:var(--radius-md);padding:1.25rem;">
                        <div style="font-size:1.5rem;margin-bottom:.5rem;">📋</div>
                        <h4 style="color:#a5b4fc;margin-bottom:.25rem;">Task Completion</h4>
                        <p class="text-sm"><?= $doneTasks ?>/<?= $totalTasks ?> tasks done (<?= $taskCompletionRate ?>%) across all projects.</p>
                    </div>
                </div>
            </div>

            <!-- Student Table -->
            <div class="data-card" style="margin-bottom:1.5rem;">
                <div class="data-card-header">
                    <h3 class="data-card-title">🎓 Student Performance Overview</h3>
                    <a href="<?= BASE_URL ?>/faculty/report.php" class="btn btn-secondary btn-sm no-print">🖨️ Print Report</a>
                </div>
                <div class="data-card-body" style="padding:0;">
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr><th>Student</th><th>Dept / Year</th><th>Projects</th><th>Tasks Done</th><th>Comments</th><th>Uploads</th><th>Collab Score</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($studentsData as $s): ?>
                                <tr>
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
                                        <span class="collab-score <?= $s['score_class'] ?>"><?= $s['score'] ?>/100</span>
                                    </td>
                                    <td>
                                        <a href="<?= BASE_URL ?>/faculty/students.php?id=<?= $s['id'] ?>" class="btn btn-ghost btn-sm">View →</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Projects Table -->
            <div class="data-card">
                <div class="data-card-header"><h3 class="data-card-title">📁 All Projects</h3></div>
                <div class="data-card-body" style="padding:0;">
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr><th>Project</th><th>Leader</th><th>Members</th><th>Progress</th><th>Status</th><th>Deadline</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($projects as $p):
                                    $pct = $p['total_tasks'] > 0 ? round($p['done_tasks']/$p['total_tasks']*100) : 0;
                                    $sc  = ['planning'=>'badge-violet','active'=>'badge-cyan','completed'=>'badge-emerald'];
                                ?>
                                <tr>
                                    <td class="fw-600"><?= sanitize($p['name']) ?></td>
                                    <td class="text-sm"><?= sanitize($p['leader_name'] ?? '—') ?></td>
                                    <td><span class="badge badge-indigo"><?= $p['member_count'] ?></span></td>
                                    <td style="min-width:120px;">
                                        <div style="display:flex;align-items:center;gap:.5rem;">
                                            <div class="progress-wrap" style="flex:1;height:6px;">
                                                <div class="progress-fill" style="width:<?= $pct ?>%"></div>
                                            </div>
                                            <span class="text-xs text-muted"><?= $pct ?>%</span>
                                        </div>
                                    </td>
                                    <td><span class="badge <?= $sc[$p['status']] ?? 'badge-gray' ?>"><?= ucfirst($p['status']) ?></span></td>
                                    <td class="text-sm text-muted"><?= $p['deadline'] ? date('M d, Y', strtotime($p['deadline'])) : '—' ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>window.APP_BASE = '<?= BASE_URL ?>';</script>
    <script src="<?= BASE_URL ?>/assets/js/main.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/charts.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        initCollabBarChart('collab-bar', <?= $chartNames ?>, <?= $chartScores ?>);
        initTaskDoughnut('task-doughnut', <?= $doneTasks ?>, <?= $inProgressTasks ?>, <?= $pendingTasks ?>);
    });
    </script>
</body>
</html>

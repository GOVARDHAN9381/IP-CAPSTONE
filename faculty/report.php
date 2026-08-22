<?php
session_start();
require_once __DIR__ . '/../config/db.php';
requireFaculty();

$db      = getDB();
$faculty = currentFaculty();

$totalStudents = $db->query("SELECT COUNT(*) FROM students")->fetchColumn();
$totalProjects = $db->query("SELECT COUNT(*) FROM projects")->fetchColumn();
$totalTasks    = $db->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
$doneTasks     = $db->query("SELECT COUNT(*) FROM tasks WHERE status = 'completed'")->fetchColumn();
$completionRate = $totalTasks > 0 ? round($doneTasks / $totalTasks * 100) : 0;
$generatedDate = date('F j, Y \a\t H:i');

$studentsData = $db->query("
    SELECT s.id, s.name, s.department, s.year, s.created_at,
           (SELECT COUNT(*) FROM tasks t WHERE t.assigned_to = s.id AND t.status = 'completed') AS done,
           (SELECT COUNT(*) FROM comments c WHERE c.student_id = s.id) AS comments,
           (SELECT COUNT(*) FROM uploads u WHERE u.student_id = s.id) AS uploads,
           (SELECT COUNT(*) FROM project_members pm WHERE pm.student_id = s.id) AS project_count
    FROM students s ORDER BY s.name
")->fetchAll();

foreach ($studentsData as &$s) {
    $s['score'] = min(100, ($s['done']*10) + ($s['comments']*3) + ($s['uploads']*5));
}
unset($s);

$projects = $db->query("
    SELECT p.*, st.name AS leader_name,
           (SELECT COUNT(*) FROM project_members pm WHERE pm.project_id = p.id) AS member_count,
           (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id) AS total_tasks,
           (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id AND t.status = 'completed') AS done_tasks
    FROM projects p LEFT JOIN students st ON st.id = p.leader_id ORDER BY p.name
")->fetchAll();

$avgScore = count($studentsData) > 0 ? round(array_sum(array_column($studentsData,'score')) / count($studentsData)) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Printable Report — CollabIQ</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #f8f9fa; color: #1a1a2e; line-height: 1.6; }
        .screen-only { padding: 2rem; }
        .report { max-width: 900px; margin: 0 auto; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 8px 32px rgba(0,0,0,.12); }
        .report-header { background: linear-gradient(135deg, #6366f1, #06b6d4); color: #fff; padding: 3rem; text-align: center; }
        .report-header h1 { font-size: 2rem; font-weight: 900; margin-bottom: .5rem; }
        .report-header p { opacity: .85; font-size: .9rem; }
        .report-body { padding: 2.5rem; }
        h2 { font-size: 1.2rem; font-weight: 700; color: #1a1a2e; border-left: 4px solid #6366f1; padding-left: .875rem; margin-bottom: 1.25rem; margin-top: 2rem; }
        .summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 2rem; }
        .summary-box { background: #f1f3f9; border-radius: 12px; padding: 1.25rem; text-align: center; }
        .summary-box .num { font-size: 2rem; font-weight: 800; color: #6366f1; }
        .summary-box .lbl { font-size: .75rem; color: #666; margin-top: .2rem; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 1.5rem; font-size: .875rem; }
        thead th { background: #6366f1; color: #fff; padding: .75rem 1rem; text-align: left; font-weight: 600; font-size: .75rem; text-transform: uppercase; letter-spacing: .07em; }
        tbody td { padding: .75rem 1rem; border-bottom: 1px solid #e8eaed; color: #333; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:nth-child(even) { background: #f8f9fc; }
        .badge { display: inline-block; padding: .2rem .6rem; border-radius: 99px; font-size: .72rem; font-weight: 700; }
        .badge-green  { background: #d1fae5; color: #065f46; }
        .badge-blue   { background: #dbeafe; color: #1e40af; }
        .badge-yellow { background: #fef3c7; color: #92400e; }
        .badge-red    { background: #fee2e2; color: #991b1b; }
        .formula-box { background: #f1f3f9; border-radius: 12px; padding: 1.5rem; margin: 1rem 0; font-size: .875rem; }
        .formula-box code { font-size: .85rem; background: #e0e7ff; padding: .2rem .5rem; border-radius: 4px; color: #3730a3; }
        .btn-print { display: inline-flex; align-items: center; gap: .5rem; padding: .75rem 1.5rem; background: linear-gradient(135deg, #6366f1, #06b6d4); color: #fff; border: none; border-radius: 8px; font-size: .9rem; font-weight: 600; cursor: pointer; text-decoration: none; margin-bottom: 1.5rem; }
        .footer { text-align: center; padding: 1.5rem; color: #999; font-size: .78rem; border-top: 1px solid #e8eaed; }

        @media print {
            .screen-only .btn-print { display: none !important; }
            .report { box-shadow: none; border-radius: 0; }
            body { background: #fff; }
        }
    </style>
</head>
<body>
    <div class="screen-only">
        <div style="text-align:center;margin-bottom:1.5rem;">
            <button class="btn-print" onclick="window.print()">🖨️ Print / Save as PDF</button>
            &nbsp;
            <a href="<?= BASE_URL ?>/faculty/dashboard.php" class="btn-print" style="background:#666;">← Back to Dashboard</a>
        </div>

        <div class="report">
            <div class="report-header">
                <h1>🧠 CollabIQ — Faculty Report</h1>
                <p>AI-Powered Student Collaboration Intelligence Platform</p>
                <p style="margin-top:.5rem;font-size:.8rem;opacity:.7;">Generated by <?= sanitize($faculty['name']) ?> · <?= $generatedDate ?></p>
            </div>

            <div class="report-body">
                <!-- Summary -->
                <h2>📊 Platform Summary</h2>
                <div class="summary-grid">
                    <div class="summary-box"><div class="num"><?= $totalStudents ?></div><div class="lbl">Students</div></div>
                    <div class="summary-box"><div class="num"><?= $totalProjects ?></div><div class="lbl">Projects</div></div>
                    <div class="summary-box"><div class="num"><?= $completionRate ?>%</div><div class="lbl">Task Completion</div></div>
                    <div class="summary-box"><div class="num"><?= $avgScore ?></div><div class="lbl">Avg Collab Score</div></div>
                </div>

                <!-- Scoring Formula -->
                <h2>🤖 Collaboration Score Formula</h2>
                <div class="formula-box">
                    <p><strong>Score</strong> = <code>Tasks Completed × 10</code> + <code>Comments Posted × 3</code> + <code>Files Uploaded × 5</code></p>
                    <p style="margin-top:.5rem;color:#666;font-size:.8rem;">Score is capped at 100. High ≥ 60, Medium ≥ 30, Low &lt; 30.</p>
                </div>

                <!-- Student Performance -->
                <h2>🎓 Student Performance</h2>
                <table>
                    <thead>
                        <tr><th>#</th><th>Student Name</th><th>Department</th><th>Year</th><th>Projects</th><th>Tasks Done</th><th>Comments</th><th>Score</th><th>Level</th></tr>
                    </thead>
                    <tbody>
                        <?php usort($studentsData, fn($a,$b) => $b['score'] <=> $a['score']); ?>
                        <?php foreach ($studentsData as $i => $s): ?>
                        <tr>
                            <td><?= $i+1 ?></td>
                            <td><strong><?= sanitize($s['name']) ?></strong></td>
                            <td><?= sanitize($s['department']??'N/A') ?></td>
                            <td><?= $s['year'] ?></td>
                            <td><?= $s['project_count'] ?></td>
                            <td><?= $s['done'] ?></td>
                            <td><?= $s['comments'] ?></td>
                            <td><strong><?= $s['score'] ?>/100</strong></td>
                            <td>
                                <?php if ($s['score'] >= 60): ?>
                                <span class="badge badge-green">High</span>
                                <?php elseif ($s['score'] >= 30): ?>
                                <span class="badge badge-yellow">Medium</span>
                                <?php else: ?>
                                <span class="badge badge-red">Low</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Projects -->
                <h2>📁 Project Status Report</h2>
                <table>
                    <thead>
                        <tr><th>Project Name</th><th>Leader</th><th>Team Size</th><th>Tasks</th><th>Progress</th><th>Status</th><th>Deadline</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects as $p):
                            $pct = $p['total_tasks'] > 0 ? round($p['done_tasks']/$p['total_tasks']*100) : 0;
                            $sc = ['planning'=>'badge-blue','active'=>'badge-blue','completed'=>'badge-green'];
                        ?>
                        <tr>
                            <td><strong><?= sanitize($p['name']) ?></strong></td>
                            <td><?= sanitize($p['leader_name'] ?? '—') ?></td>
                            <td><?= $p['member_count'] ?></td>
                            <td><?= $p['done_tasks'] ?>/<?= $p['total_tasks'] ?></td>
                            <td><?= $pct ?>%</td>
                            <td><span class="badge <?= $sc[$p['status']] ?? '' ?>"><?= ucfirst($p['status']) ?></span></td>
                            <td><?= $p['deadline'] ? date('M d, Y', strtotime($p['deadline'])) : '—' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- AI Suggestions -->
                <h2>🤖 AI Suggestions for Faculty</h2>
                <div class="formula-box">
                    <?php
                    $lowScorers = array_filter($studentsData, fn($s) => $s['score'] < 30);
                    $inactive   = array_filter($studentsData, fn($s) => $s['project_count'] === 0 || $s['project_count'] === '0');
                    ?>
                    <ul style="list-style:none;display:flex;flex-direction:column;gap:.75rem;">
                        <li>📌 <strong><?= count($lowScorers) ?> student(s)</strong> have a collaboration score below 30 — consider scheduling 1:1 mentoring sessions.</li>
                        <li>📌 <strong><?= count($inactive) ?> student(s)</strong> have not joined any project yet — encourage team formation.</li>
                        <li>📌 Overall task completion rate is <strong><?= $completionRate ?>%</strong>. <?= $completionRate < 60 ? 'Consider reviewing deadlines or task complexity.' : 'Great progress!' ?></li>
                        <li>📌 Average collaboration score is <strong><?= $avgScore ?>/100</strong>. Encourage students to comment more on discussion threads to increase engagement.</li>
                    </ul>
                </div>
            </div>

            <div class="footer">
                <p>© 2026 CollabIQ · AI-Powered Student Collaboration Intelligence Platform · Report generated on <?= $generatedDate ?></p>
            </div>
        </div>
    </div>
</body>
</html>

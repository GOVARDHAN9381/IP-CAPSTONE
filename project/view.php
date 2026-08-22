<?php
session_start();
require_once __DIR__ . '/../config/db.php';
requireLogin();

$db        = getDB();
$studentId = $_SESSION['student_id'];
$student   = currentStudent();
$projId    = (int)($_GET['id'] ?? 0);

if (!$projId) { header('Location: ' . BASE_URL . '/student/dashboard.php'); exit; }

// Verify membership
$memChk = $db->prepare("SELECT role FROM project_members WHERE project_id = ? AND student_id = ?");
$memChk->execute([$projId, $studentId]);
$myRole = $memChk->fetchColumn();
if (!$myRole) { header('Location: ' . BASE_URL . '/student/dashboard.php'); exit; }

// Project data
$proj = $db->prepare("SELECT p.*, f.name AS faculty_name FROM projects p LEFT JOIN faculty f ON f.id = p.faculty_id WHERE p.id = ?");
$proj->execute([$projId]);
$project = $proj->fetch();
if (!$project) { header('Location: ' . BASE_URL . '/student/dashboard.php'); exit; }

// Members
$membersStmt = $db->prepare("
    SELECT s.id, s.name, s.department, s.year, pm.role
    FROM students s JOIN project_members pm ON pm.student_id = s.id
    WHERE pm.project_id = ? ORDER BY pm.role DESC, s.name
");
$membersStmt->execute([$projId]);
$members = $membersStmt->fetchAll();

// Tasks — replaced FIELD() with CASE WHEN for broader MySQL compatibility
$tasksStmt = $db->prepare("
    SELECT t.*, s.name AS assignee_name FROM tasks t
    LEFT JOIN students s ON s.id = t.assigned_to
    WHERE t.project_id = ?
    ORDER BY
        CASE t.status WHEN 'in_progress' THEN 1 WHEN 'pending' THEN 2 WHEN 'completed' THEN 3 ELSE 4 END,
        CASE t.priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 ELSE 4 END
");
$tasksStmt->execute([$projId]);
$tasks = $tasksStmt->fetchAll();

// Progress
$totalTasks = count($tasks);
$doneTasks  = count(array_filter($tasks, fn($t) => $t['status'] === 'completed'));
$progress   = $totalTasks > 0 ? round($doneTasks / $totalTasks * 100) : 0;

// Comments
$comStmt = $db->prepare("
    SELECT c.*, s.name AS student_name FROM comments c
    JOIN students s ON s.id = c.student_id
    WHERE c.project_id = ?
    ORDER BY c.created_at ASC
");
$comStmt->execute([$projId]);
$comments = $comStmt->fetchAll();

// Uploads
$upStmt = $db->prepare("
    SELECT u.*, s.name AS uploader FROM uploads u
    JOIN students s ON s.id = u.student_id
    WHERE u.project_id = ?
    ORDER BY u.uploaded_at DESC
");
$upStmt->execute([$projId]);
$uploads = $upStmt->fetchAll();

$isLeader = ($myRole === 'leader');
$created  = isset($_GET['created']);

// Status label map
$statusColors = ['planning'=>'badge-violet','active'=>'badge-cyan','completed'=>'badge-emerald'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($project['name']) ?> — CollabIQ</title>
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
                <a href="<?= BASE_URL ?>/student/recommendations.php" class="sidebar-link"><span class="icon">🤖</span> AI Recommendations</a>
            </div>
            <div class="sidebar-section">
                <div class="sidebar-label">Projects</div>
                <a href="<?= BASE_URL ?>/project/create.php" class="sidebar-link"><span class="icon">➕</span> New Project</a>
                <a href="<?= BASE_URL ?>/project/view.php?id=<?= $projId ?>" class="sidebar-link active">
                    <span class="icon">📁</span> <?= sanitize(mb_strimwidth($project['name'],0,20,'…')) ?>
                </a>
            </div>
        </aside>

        <main class="main-content">
            <input type="hidden" id="project-id-hidden" value="<?= $projId ?>">

            <?php if ($created): ?>
            <div class="alert alert-success">🎉 Project created successfully! Start by assigning tasks to your team.</div>
            <?php endif; ?>

            <!-- Project Header -->
            <div style="background:var(--bg-card);border:1px solid var(--border-subtle);border-radius:var(--radius-xl);padding:2rem;margin-bottom:2rem;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;">
                    <div>
                        <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.5rem;">
                            <h1 style="font-size:1.75rem;"><?= sanitize($project['name']) ?></h1>
                            <span class="badge project-status-badge <?= $statusColors[$project['status']] ?? 'badge-gray' ?>"><?= ucfirst($project['status']) ?></span>
                            <?php if ($isLeader): ?><span class="badge badge-amber">👑 You are Leader</span><?php endif; ?>
                        </div>
                        <?php if ($project['description']): ?>
                        <p style="color:var(--text-muted);font-size:.9rem;max-width:600px;line-height:1.6;"><?= sanitize($project['description']) ?></p>
                        <?php endif; ?>
                        <div style="display:flex;gap:1.5rem;margin-top:.75rem;flex-wrap:wrap;">
                            <?php if ($project['faculty_name']): ?>
                            <span class="text-sm text-muted">👨‍🏫 <?= sanitize($project['faculty_name']) ?></span>
                            <?php endif; ?>
                            <?php if ($project['deadline']): ?>
                            <span class="text-sm text-muted">📅 Deadline: <?= date('M d, Y', strtotime($project['deadline'])) ?></span>
                            <?php endif; ?>
                            <span class="text-sm text-muted">👥 <?= count($members) ?> members</span>
                        </div>
                    </div>
                    <!-- Member Avatars -->
                    <div>
                        <div class="avatar-stack" style="margin-bottom:.5rem;">
                            <?php foreach(array_slice($members,0,5) as $m): ?>
                            <img src="<?= generateAvatar($m['name']) ?>" class="avatar" title="<?= sanitize($m['name']) ?>" alt="">
                            <?php endforeach; ?>
                        </div>
                        <?php if ($isLeader): ?>
                        <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:.5rem;">
                            <a href="<?= BASE_URL ?>/project/tasks.php?id=<?= $projId ?>" class="btn btn-primary btn-sm">➕ Assign Task</a>
                            <!-- Project Status Update -->
                            <select id="project-status-select" class="btn btn-ghost btn-sm" style="cursor:pointer;" onchange="updateProjectStatus(<?= $projId ?>, this.value)">
                                <option value="planning" <?= $project['status']==='planning'?'selected':'' ?>>📋 Planning</option>
                                <option value="active" <?= $project['status']==='active'?'selected':'' ?>>⚡ Active</option>
                                <option value="completed" <?= $project['status']==='completed'?'selected':'' ?>>✅ Completed</option>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div style="margin-top:1.5rem;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem;">
                        <span class="text-sm fw-600">Overall Progress</span>
                        <span class="text-sm text-muted" id="project-progress-pct"><?= $progress ?>%</span>
                    </div>
                    <div class="progress-wrap" style="height:12px;">
                        <div class="progress-fill" id="project-progress" style="width:<?= $progress ?>%"></div>
                    </div>
                    <div style="display:flex;justify-content:space-between;margin-top:.4rem;">
                        <span class="text-xs text-muted"><?= $doneTasks ?> of <?= $totalTasks ?> tasks completed</span>
                        <span class="text-xs text-muted"><?= $totalTasks - $doneTasks ?> remaining</span>
                    </div>
                </div>
            </div>

            <div class="grid-2" style="gap:1.5rem;">
                <!-- Tasks Table -->
                <div class="data-card" style="grid-column:1/-1;">
                    <div class="data-card-header">
                        <h3 class="data-card-title">📋 Tasks</h3>
                        <?php if ($isLeader): ?>
                        <a href="<?= BASE_URL ?>/project/tasks.php?id=<?= $projId ?>" class="btn btn-primary btn-sm">➕ Add Task</a>
                        <?php endif; ?>
                    </div>
                    <div class="data-card-body" style="padding:0 1.5rem 1.5rem;">
                        <?php if (empty($tasks)): ?>
                        <div class="empty-state" style="padding:2rem 0;">
                            <div class="empty-state-icon">📋</div>
                            <p>No tasks yet. <?= $isLeader ? '<a href="' . BASE_URL . '/project/tasks.php?id=' . $projId . '">Add the first task →</a>' : 'Waiting for leader to assign tasks.' ?></p>
                        </div>
                        <?php else: ?>
                        <div class="table-wrapper">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Task</th>
                                        <th>Assigned To</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Due Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tasks as $task): ?>
                                    <tr>
                                        <td>
                                            <div class="task-title"><?= sanitize($task['title']) ?></div>
                                            <?php if ($task['description']): ?>
                                            <div class="task-meta"><?= sanitize(mb_strimwidth($task['description'],0,60,'…')) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($task['assignee_name']): ?>
                                            <div style="display:flex;align-items:center;gap:.5rem;">
                                                <img src="<?= generateAvatar($task['assignee_name']) ?>" class="avatar avatar-sm" alt="">
                                                <span class="text-sm"><?= sanitize($task['assignee_name']) ?></span>
                                            </div>
                                            <?php else: ?><span class="text-muted text-sm">Unassigned</span><?php endif; ?>
                                        </td>
                                        <td><span class="badge priority-<?= $task['priority'] ?>"><?= ucfirst($task['priority']) ?></span></td>
                                        <td><span class="badge task-status-badge status-<?= $task['status'] ?>"><?= ucfirst(str_replace('_',' ',$task['status'])) ?></span></td>
                                        <td class="text-sm text-muted"><?= $task['due_date'] ? date('M d, Y', strtotime($task['due_date'])) : '—' ?></td>
                                        <td>
                                            <?php if ($task['assigned_to'] == $studentId || $isLeader): ?>
                                            <div class="task-actions">
                                                <?php if ($task['status'] !== 'in_progress'): ?>
                                                <button class="btn btn-secondary btn-sm" onclick="updateTaskStatus(<?= $task['id'] ?>,'in_progress',this)">▶ Start</button>
                                                <?php endif; ?>
                                                <?php if ($task['status'] !== 'completed'): ?>
                                                <button class="btn btn-success btn-sm" onclick="updateTaskStatus(<?= $task['id'] ?>,'completed',this)">✓ Done</button>
                                                <?php endif; ?>
                                            </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="grid-2" style="gap:1.5rem;margin-top:1.5rem;">
                <!-- Discussion -->
                <div class="data-card">
                    <div class="data-card-header"><h3 class="data-card-title">💬 Team Discussion</h3></div>
                    <div class="data-card-body">
                        <div class="comment-list" id="comment-list">
                            <?php if (empty($comments)): ?>
                            <div class="empty-state" style="padding:1.5rem 0;">
                                <p class="text-muted text-sm">No messages yet. Start the conversation!</p>
                            </div>
                            <?php else: ?>
                            <?php foreach ($comments as $c): ?>
                            <div class="comment-item">
                                <img src="<?= generateAvatar($c['student_name']) ?>" class="avatar avatar-sm" alt="">
                                <div class="comment-body">
                                    <div class="comment-header">
                                        <span class="comment-name"><?= sanitize($c['student_name']) ?></span>
                                        <span class="comment-time"><?= date('M d, H:i', strtotime($c['created_at'])) ?></span>
                                    </div>
                                    <p class="comment-text"><?= sanitize($c['message']) ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <div class="comment-form">
                            <div class="input-wrapper" style="flex:1;">
                                <textarea id="comment-text" placeholder="Write a message to your team..." rows="2"></textarea>
                            </div>
                            <button id="comment-btn" class="btn btn-primary" onclick="postComment(<?= $projId ?>)">💬 Post</button>
                        </div>
                    </div>
                </div>

                <!-- File Uploads -->
                <div class="data-card">
                    <div class="data-card-header"><h3 class="data-card-title">📁 Files</h3></div>
                    <div class="data-card-body">
                        <div class="upload-zone" id="upload-zone">
                            <div class="upload-zone-icon">📤</div>
                            <p class="fw-600">Drop files here or click to upload</p>
                            <p class="text-xs text-muted">Max 10MB per file</p>
                            <input type="file" id="file-input" style="display:none;">
                        </div>

                        <?php if (!empty($uploads)): ?>
                        <div class="upload-list">
                            <?php foreach ($uploads as $u): ?>
                            <div class="upload-item">
                                <span class="upload-icon">📄</span>
                                <div>
                                    <div class="upload-name"><?= sanitize($u['original_name']) ?></div>
                                    <div class="text-xs text-muted">by <?= sanitize($u['uploader']) ?> · <?= round($u['filesize']/1024,1) ?> KB</div>
                                </div>
                                <a href="<?= BASE_URL ?>/<?= sanitize($u['filepath']) ?>" class="btn btn-ghost btn-sm" download>⬇</a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p class="text-muted text-sm" style="margin-top:1rem;text-align:center;">No files uploaded yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="<?= BASE_URL ?>/assets/js/main.js"></script>
    <script>
    // Inject base URL for JS AJAX calls
    window.APP_BASE = '<?= BASE_URL ?>';

    function updateProjectStatus(projectId, status) {
        fetch(window.APP_BASE + '/api/project_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `project_id=${projectId}&status=${status}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Update the status badge in the header
                const badge = document.querySelector('.project-status-badge');
                if (badge) {
                    const cls = { planning: 'badge-violet', active: 'badge-cyan', completed: 'badge-emerald' };
                    badge.className = 'badge project-status-badge ' + (cls[data.status] || 'badge-gray');
                    badge.textContent = data.status.charAt(0).toUpperCase() + data.status.slice(1);
                }
                showToast('✅ Project status updated!', 'success');
            } else {
                showToast('❌ ' + (data.error || 'Failed'), 'error');
            }
        })
        .catch(() => showToast('❌ Network error', 'error'));
    }
    </script>
</body>
</html>

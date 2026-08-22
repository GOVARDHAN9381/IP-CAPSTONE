<?php
session_start();
require_once __DIR__ . '/../config/db.php';
requireLogin();

$db        = getDB();
$studentId = $_SESSION['student_id'];
$student   = currentStudent();
$projId    = (int)($_GET['id'] ?? 0);
$errors    = [];
$success   = '';

// Verify membership and role
$memChk = $db->prepare("SELECT role FROM project_members WHERE project_id = ? AND student_id = ?");
$memChk->execute([$projId, $studentId]);
$myRole = $memChk->fetchColumn();
if (!$myRole) { header('Location: ' . BASE_URL . '/student/dashboard.php'); exit; }

// Project info
$projStmt = $db->prepare("SELECT * FROM projects WHERE id = ?");
$projStmt->execute([$projId]);
$project = $projStmt->fetch();
if (!$project) { header('Location: ' . BASE_URL . '/student/dashboard.php'); exit; }

$isLeader = ($myRole === 'leader');

// Members for assignment dropdown
$membersStmt = $db->prepare("SELECT s.id, s.name FROM students s JOIN project_members pm ON pm.student_id = s.id WHERE pm.project_id = ?");
$membersStmt->execute([$projId]);
$members = $membersStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_task' && $isLeader) {
        $title    = trim($_POST['title'] ?? '');
        $desc     = trim($_POST['description'] ?? '');
        $assignTo = (int)($_POST['assigned_to'] ?? 0) ?: null;
        $priority = $_POST['priority'] ?? 'medium';
        $dueDate  = $_POST['due_date'] ?? '' ?: null;

        if (strlen($title) < 2) { $errors[] = 'Task title too short.'; }
        if (empty($errors)) {
            $db->prepare("INSERT INTO tasks (project_id, title, description, assigned_to, priority, due_date) VALUES (?,?,?,?,?,?)")
               ->execute([$projId, $title, $desc, $assignTo, $priority, $dueDate]);
            $success = 'Task added successfully!';
        }
    } elseif ($action === 'mark_all_done' && $isLeader) {
        $db->prepare("UPDATE tasks SET status = 'completed', updated_at = CURRENT_TIMESTAMP WHERE project_id = ?")
           ->execute([$projId]);
        $success = 'All tasks marked as completed!';
    }
}

// Current tasks
$tasks = $db->prepare("SELECT t.*, s.name AS assignee FROM tasks t LEFT JOIN students s ON s.id = t.assigned_to WHERE t.project_id = ? ORDER BY t.created_at DESC");
$tasks->execute([$projId]);
$taskList = $tasks->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks — <?= sanitize($project['name']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/auth.css">
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
                <div class="sidebar-label">Navigation</div>
                <a href="<?= BASE_URL ?>/student/dashboard.php" class="sidebar-link"><span class="icon">🏠</span> Dashboard</a>
                <a href="<?= BASE_URL ?>/project/view.php?id=<?= $projId ?>" class="sidebar-link"><span class="icon">📁</span> Project Overview</a>
                <a href="<?= BASE_URL ?>/project/tasks.php?id=<?= $projId ?>" class="sidebar-link active"><span class="icon">📋</span> Tasks</a>
            </div>
        </aside>
        <main class="main-content">
            <div class="page-header">
                <h1>📋 Task Management</h1>
                <p><?= sanitize($project['name']) ?></p>
            </div>

            <?php if ($success): ?><div class="alert alert-success">✅ <?= sanitize($success) ?></div><?php endif; ?>
            <?php if (!empty($errors)): ?><div class="alert alert-error"><?php foreach($errors as $e) echo "<p>⚠️ ".sanitize($e)."</p>"; ?></div><?php endif; ?>

            <?php if ($isLeader): ?>
            <!-- Add Task Form -->
            <div class="data-card" style="margin-bottom:1.5rem;">
                <div class="data-card-header"><h3 class="data-card-title">➕ Add New Task</h3></div>
                <div class="data-card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="add_task">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Task Title *</label>
                                <div class="input-wrapper">
                                    <span class="input-icon">📋</span>
                                    <input type="text" name="title" placeholder="e.g. Design Login Page" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Assign To</label>
                                <div class="input-wrapper">
                                    <span class="input-icon">👤</span>
                                    <select name="assigned_to">
                                        <option value="">— Unassigned —</option>
                                        <?php foreach ($members as $m): ?>
                                        <option value="<?= $m['id'] ?>"><?= sanitize($m['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <div class="input-wrapper">
                                <textarea name="description" rows="2" placeholder="Describe what needs to be done..."></textarea>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Priority</label>
                                <div class="input-wrapper">
                                    <span class="input-icon">⚡</span>
                                    <select name="priority">
                                        <option value="low">Low</option>
                                        <option value="medium" selected>Medium</option>
                                        <option value="high">High</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Due Date</label>
                                <div class="input-wrapper">
                                    <span class="input-icon">📅</span>
                                    <input type="date" name="due_date">
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">➕ Add Task</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Task List -->
            <div class="data-card">
                <div class="data-card-header">
                    <h3 class="data-card-title">All Tasks (<?= count($taskList) ?>)</h3>
                    <?php if ($isLeader && !empty($taskList)): ?>
                    <form method="POST" style="display:inline;">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="mark_all_done">
                        <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Mark ALL tasks as completed?')">✅ Mark All Done</button>
                    </form>
                    <?php endif; ?>
                </div>
                <div class="data-card-body" style="padding:0 1.5rem 1.5rem;">
                    <?php if (empty($taskList)): ?>
                    <div class="empty-state" style="padding:2.5rem 0;"><p>No tasks assigned yet.</p></div>
                    <?php else: ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr><th>Task</th><th>Assignee</th><th>Priority</th><th>Status</th><th>Due Date</th><th>Update</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($taskList as $t): ?>
                                <tr>
                                    <td>
                                        <div class="fw-600 text-sm"><?= sanitize($t['title']) ?></div>
                                        <?php if ($t['description']): ?>
                                        <div class="text-xs text-muted"><?= sanitize(mb_strimwidth($t['description'],0,80,'…')) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($t['assignee']): ?>
                                        <div style="display:flex;align-items:center;gap:.4rem;">
                                            <img src="<?= generateAvatar($t['assignee']) ?>" class="avatar-sm avatar" alt="">
                                            <span class="text-sm"><?= sanitize($t['assignee']) ?></span>
                                        </div>
                                        <?php else: ?><span class="text-muted text-sm">—</span><?php endif; ?>
                                    </td>
                                    <td><span class="badge priority-<?= $t['priority'] ?>"><?= ucfirst($t['priority']) ?></span></td>
                                    <td><span class="badge task-status-badge status-<?= $t['status'] ?>"><?= ucfirst(str_replace('_',' ',$t['status'])) ?></span></td>
                                    <td class="text-sm text-muted"><?= $t['due_date'] ? date('M d, Y', strtotime($t['due_date'])) : '—' ?></td>
                                    <td>
                                        <?php if ($t['assigned_to'] == $studentId || $isLeader): ?>
                                        <div style="display:flex;gap:.4rem;flex-wrap:wrap;">
                                            <?php if ($t['status'] !== 'in_progress'): ?><button class="btn btn-secondary btn-sm" onclick="updateTaskStatus(<?= $t['id'] ?>,'in_progress',this)">▶</button><?php endif; ?>
                                            <?php if ($t['status'] !== 'completed'): ?><button class="btn btn-success btn-sm" onclick="updateTaskStatus(<?= $t['id'] ?>,'completed',this)">✓</button><?php endif; ?>
                                            <?php if ($t['status'] !== 'pending'): ?><button class="btn btn-danger btn-sm" onclick="updateTaskStatus(<?= $t['id'] ?>,'pending',this)">↺</button><?php endif; ?>
                                            <?php if ($isLeader): ?>
                                            <button class="btn btn-danger btn-sm" title="Delete task" onclick="deleteTask(<?= $t['id'] ?>, this)">🗑</button>
                                            <?php endif; ?>
                                        </div>
                                        <?php else: ?><span class="text-muted text-xs">—</span><?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    <script src="<?= BASE_URL ?>/assets/js/main.js"></script>
    <script>
    window.APP_BASE = '<?= BASE_URL ?>';
    function deleteTask(taskId, btn) {
        if (!confirm('Delete this task? This cannot be undone.')) return;
        btn.disabled = true;
        btn.textContent = '⏳';
        fetch(window.APP_BASE + '/api/task_delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `task_id=${taskId}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Remove the table row
                btn.closest('tr')?.remove();
                showToast('🗑 Task deleted', 'success');
            } else {
                showToast('❌ ' + (data.error || 'Failed'), 'error');
                btn.disabled = false;
                btn.textContent = '🗑';
            }
        })
        .catch(() => { showToast('❌ Network error', 'error'); btn.disabled = false; btn.textContent = '🗑'; });
    }
    </script>
</body>
</html>

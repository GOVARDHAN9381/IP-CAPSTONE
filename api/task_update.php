<?php
session_start();
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

if (!isLoggedIn()) { echo json_encode(['error' => 'Unauthorized']); exit; }

$studentId = $_SESSION['student_id'];
$taskId    = (int)($_POST['task_id'] ?? 0);
$status    = $_POST['status'] ?? '';
$allowed   = ['pending', 'in_progress', 'completed'];

if (!$taskId || !in_array($status, $allowed)) {
    echo json_encode(['error' => 'Invalid input']); exit;
}

$db = getDB();

// Verify the student is assigned to this task or is the project leader
$task = $db->prepare("
    SELECT t.*, pm.role FROM tasks t
    JOIN project_members pm ON pm.project_id = t.project_id AND pm.student_id = ?
    WHERE t.id = ?
");
$task->execute([$studentId, $taskId]);
$taskData = $task->fetch();

if (!$taskData) { echo json_encode(['error' => 'Not authorized']); exit; }
if ($taskData['assigned_to'] != $studentId && $taskData['role'] !== 'leader') {
    echo json_encode(['error' => 'Not authorized']); exit;
}

// Update status
$db->prepare("UPDATE tasks SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")
   ->execute([$status, $taskId]);

// ── Activity Logging ─────────────────────────────────────────
$icons  = ['pending' => '🔁', 'in_progress' => '⚡', 'completed' => '✅'];
$labels = ['pending' => 'Pending', 'in_progress' => 'In Progress', 'completed' => 'Completed'];
$detail = "Task \"{$taskData['title']}\" marked as {$labels[$status]}";
try {
    $db->prepare("INSERT INTO activity_log (student_id, project_id, action_type, detail, icon) VALUES (?,?,?,?,?)")
       ->execute([$studentId, $taskData['project_id'], 'task_status_changed', $detail, $icons[$status]]);
} catch (\Throwable $e) { /* silently skip if table not migrated yet */ }

// ── Notify project leader if a team member completed a task ──
if ($status === 'completed' && $taskData['role'] !== 'leader') {
    try {
        $me = $db->prepare("SELECT name FROM students WHERE id=?")->execute([$studentId]) ? null : null;
        $meName = $_SESSION['student']['name'] ?? 'A team member';
        $leader = $db->prepare("SELECT student_id FROM project_members WHERE project_id=? AND role='leader'");
        $leader->execute([$taskData['project_id']]);
        $leaderId = $leader->fetchColumn();
        if ($leaderId && $leaderId != $studentId) {
            $msg = "✅ {$meName} completed task \"{$taskData['title']}\"";
            $db->prepare("INSERT INTO notifications (student_id,type,message,link) VALUES (?,?,?,?)")
               ->execute([$leaderId, 'task_completed', $msg, BASE_URL.'/project/view.php?id='.$taskData['project_id']]);
        }
    } catch (\Throwable $e) { /* silently skip */ }
}

// Recalculate progress
$projectId = $taskData['project_id'];
$total = $db->prepare("SELECT COUNT(*) FROM tasks WHERE project_id = ?");
$total->execute([$projectId]);
$totalCnt = (int)$total->fetchColumn();

$done = $db->prepare("SELECT COUNT(*) FROM tasks WHERE project_id = ? AND status = 'completed'");
$done->execute([$projectId]);
$doneCnt = (int)$done->fetchColumn();

$progress = $totalCnt > 0 ? round($doneCnt / $totalCnt * 100) : 0;

echo json_encode([
    'success'  => true,
    'status'   => $status,
    'progress' => $progress,
    'task_id'  => $taskId,
]);

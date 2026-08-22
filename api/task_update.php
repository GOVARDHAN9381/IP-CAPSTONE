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

// Update status — use CURRENT_TIMESTAMP for cross-DB compatibility
$db->prepare("UPDATE tasks SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")
   ->execute([$status, $taskId]);

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

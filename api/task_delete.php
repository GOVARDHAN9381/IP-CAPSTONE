<?php
/**
 * API: Delete a task (leader only)
 */
session_start();
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

if (!isLoggedIn()) { echo json_encode(['error' => 'Unauthorized']); exit; }

$studentId = $_SESSION['student_id'];
$taskId    = (int)($_POST['task_id'] ?? 0);

if (!$taskId) { echo json_encode(['error' => 'Invalid task ID']); exit; }

$db = getDB();

// Must be project leader to delete tasks
$check = $db->prepare("
    SELECT t.project_id FROM tasks t
    JOIN project_members pm ON pm.project_id = t.project_id AND pm.student_id = ?
    WHERE t.id = ? AND pm.role = 'leader'
");
$check->execute([$studentId, $taskId]);
$row = $check->fetch();

if (!$row) { echo json_encode(['error' => 'Not authorized — only the project leader can delete tasks']); exit; }

$projectId = $row['project_id'];
$db->prepare("DELETE FROM tasks WHERE id = ?")->execute([$taskId]);

// Recalculate progress after deletion
$total = $db->prepare("SELECT COUNT(*) FROM tasks WHERE project_id = ?");
$total->execute([$projectId]);
$totalCnt = (int)$total->fetchColumn();

$done = $db->prepare("SELECT COUNT(*) FROM tasks WHERE project_id = ? AND status = 'completed'");
$done->execute([$projectId]);
$doneCnt = (int)$done->fetchColumn();

$progress = $totalCnt > 0 ? round($doneCnt / $totalCnt * 100) : 0;

echo json_encode(['success' => true, 'progress' => $progress]);

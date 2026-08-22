<?php
/**
 * API: Update project status (leader only)
 * POST: project_id, status (planning|active|completed)
 */
session_start();
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

if (!isLoggedIn()) { echo json_encode(['error' => 'Unauthorized']); exit; }

$studentId = $_SESSION['student_id'];
$projectId = (int)($_POST['project_id'] ?? 0);
$status    = $_POST['status'] ?? '';
$allowed   = ['planning', 'active', 'completed'];

if (!$projectId || !in_array($status, $allowed)) {
    echo json_encode(['error' => 'Invalid input']); exit;
}

$db = getDB();

// Must be project leader
$check = $db->prepare("SELECT role FROM project_members WHERE project_id = ? AND student_id = ?");
$check->execute([$projectId, $studentId]);
$role = $check->fetchColumn();

if ($role !== 'leader') {
    echo json_encode(['error' => 'Only the project leader can change project status']); exit;
}

$db->prepare("UPDATE projects SET status = ? WHERE id = ?")->execute([$status, $projectId]);

echo json_encode(['success' => true, 'status' => $status]);

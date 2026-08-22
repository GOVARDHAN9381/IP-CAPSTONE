<?php
session_start();
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

if (!isLoggedIn()) { echo json_encode(['error'=>'Unauthorized']); exit; }

$studentId = $_SESSION['student_id'];
$projectId = (int)($_POST['project_id'] ?? 0);
$message   = trim($_POST['message'] ?? '');

if (!$projectId || strlen($message) < 1 || strlen($message) > 2000) {
    echo json_encode(['error'=>'Invalid input']); exit;
}

$db = getDB();

// Verify membership
$mem = $db->prepare("SELECT 1 FROM project_members WHERE project_id = ? AND student_id = ?");
$mem->execute([$projectId, $studentId]);
if (!$mem->fetch()) { echo json_encode(['error'=>'Not a project member']); exit; }

// Insert comment
$db->prepare("INSERT INTO comments (project_id, student_id, message) VALUES (?,?,?)")
   ->execute([$projectId, $studentId, $message]);

$student = currentStudent();

echo json_encode([
    'success' => true,
    'name'    => $student['name'],
    'avatar'  => generateAvatar($student['name']),
]);

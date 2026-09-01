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

// ── Activity Logging ─────────────────────────────────────────
try {
    $proj = $db->prepare("SELECT name FROM projects WHERE id=?");
    $proj->execute([$projectId]);
    $projName = $proj->fetchColumn() ?: 'a project';
    $db->prepare("INSERT INTO activity_log (student_id, project_id, action_type, detail, icon) VALUES (?,?,?,?,?)")
       ->execute([$studentId, $projectId, 'comment_posted', "Commented in \"{$projName}\"", '💬']);
} catch (\Throwable $e) { /* silently skip */ }

// ── Notify other project members ──────────────────────────────
try {
    $mems = $db->prepare("SELECT student_id FROM project_members WHERE project_id=? AND student_id != ?");
    $mems->execute([$projectId, $studentId]);
    $proj = $db->prepare("SELECT name FROM projects WHERE id=?");
    $proj->execute([$projectId]);
    $projName = $proj->fetchColumn() ?: 'a project';
    $msg = "💬 {$student['name']} commented in \"{$projName}\"";
    foreach ($mems->fetchAll() as $m) {
        $db->prepare("INSERT INTO notifications (student_id,type,message,link) VALUES (?,?,?,?)")
           ->execute([$m['student_id'], 'comment', $msg, BASE_URL.'/project/view.php?id='.$projectId]);
    }
} catch (\Throwable $e) { /* silently skip */ }

echo json_encode([
    'success' => true,
    'name'    => $student['name'],
    'avatar'  => generateAvatar($student['name']),
]);

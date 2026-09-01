<?php
/**
 * api/idea_comment.php — AJAX handler for posting comments on an idea
 * POST: idea_id, comment
 */
session_start();
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

if (!isLoggedIn()) { echo json_encode(['error' => 'Unauthorized']); exit; }
verifyCsrf();

$studentId = $_SESSION['student_id'];
$db        = getDB();
$ideaId    = (int)($_POST['idea_id'] ?? 0);
$comment   = trim($_POST['comment'] ?? '');

if (!$ideaId || strlen($comment) < 2) {
    echo json_encode(['error' => 'Comment too short']); exit;
}

$idea = $db->prepare("SELECT * FROM ideas WHERE id=?");
$idea->execute([$ideaId]);
$ideaRow = $idea->fetch();
if (!$ideaRow) { echo json_encode(['error' => 'Not found']); exit; }

$db->prepare("INSERT INTO idea_comments (idea_id, student_id, comment) VALUES (?,?,?)")
   ->execute([$ideaId, $studentId, $comment]);

$me = $db->prepare("SELECT name FROM students WHERE id=?");
$me->execute([$studentId]);
$myName = $me->fetchColumn();

// Notify idea author if different
if ($ideaRow['submitted_by'] != $studentId) {
    $msg = "💬 {$myName} commented on your idea \"{$ideaRow['title']}\".";
    $db->prepare("INSERT INTO notifications (student_id,type,message,link) VALUES (?,?,?,?)")
       ->execute([$ideaRow['submitted_by'], 'idea_comment', $msg, BASE_URL.'/ideas/view.php?id='.$ideaId]);
}

// Log activity
$db->prepare("INSERT INTO activity_log (student_id, action_type, detail, icon) VALUES (?,?,?,?)")
   ->execute([$studentId, 'idea_comment', "Commented on idea: {$ideaRow['title']}", '💬']);

echo json_encode(['success' => true, 'name' => $myName, 'comment' => sanitize($comment), 'time' => date('M d, g:i A')]);

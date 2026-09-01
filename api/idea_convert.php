<?php
/**
 * api/idea_convert.php
 * Converts an approved idea into a real project (leader = submitter)
 * POST: idea_id
 */
session_start();
require_once __DIR__ . '/../config/db.php';
if (!isLoggedIn()) { header('Location: '.BASE_URL.'/auth/login.php'); exit; }
verifyCsrf();

$studentId = $_SESSION['student_id'];
$db        = getDB();
$ideaId    = (int)($_POST['idea_id'] ?? 0);

$idea = $db->prepare("SELECT * FROM ideas WHERE id=? AND submitted_by=?");
$idea->execute([$ideaId, $studentId]);
$ideaRow = $idea->fetch();

if (!$ideaRow) {
    header('Location: '.BASE_URL.'/ideas/index.php?error=not_found'); exit;
}

// Create project from idea
$db->prepare("INSERT INTO projects (name, description, leader_id, status) VALUES (?,?,?,'planning')")
   ->execute([$ideaRow['title'], $ideaRow['description'], $studentId]);
$projId = $db->lastInsertId();

// Add creator as leader in project_members
$db->prepare("INSERT INTO project_members (project_id, student_id, role) VALUES (?,?,'leader')")
   ->execute([$projId, $studentId]);

// Mark idea as converted
$db->prepare("UPDATE ideas SET status='converted' WHERE id=?")->execute([$ideaId]);

// Log activity
$db->prepare("INSERT INTO activity_log (student_id, project_id, action_type, detail, icon) VALUES (?,?,?,?,?)")
   ->execute([$studentId, $projId, 'project_created', "Created project from idea: {$ideaRow['title']}", '🚀']);

header('Location: '.BASE_URL.'/project/view.php?id='.$projId.'&created=1'); exit;

<?php
/**
 * api/milestone_update.php — AJAX: update milestone status or add new milestone
 * POST action=add:    project_id, title, description, target_date
 * POST action=update: milestone_id, status
 * POST action=delete: milestone_id
 */
session_start();
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

if (!isLoggedIn()) { echo json_encode(['error' => 'Unauthorized']); exit; }
verifyCsrf();

$studentId = $_SESSION['student_id'];
$db        = getDB();
$action    = $_POST['action'] ?? '';

if ($action === 'add') {
    $projId = (int)($_POST['project_id'] ?? 0);
    $title  = trim($_POST['title'] ?? '');
    $desc   = trim($_POST['description'] ?? '');
    $date   = $_POST['target_date'] ?? null;

    if (!$projId || !$title) { echo json_encode(['error' => 'Missing fields']); exit; }

    // Verify leader
    $chk = $db->prepare("SELECT role FROM project_members WHERE project_id=? AND student_id=?");
    $chk->execute([$projId, $studentId]);
    if ($chk->fetchColumn() !== 'leader') { echo json_encode(['error' => 'Only leaders can add milestones']); exit; }

    $db->prepare("INSERT INTO milestones (project_id, title, description, target_date) VALUES (?,?,?,?)")
       ->execute([$projId, $title, $desc ?: null, $date ?: null]);
    $mid = $db->lastInsertId();

    $db->prepare("INSERT INTO activity_log (student_id, project_id, action_type, detail, icon) VALUES (?,?,?,?,?)")
       ->execute([$studentId, $projId, 'milestone_added', "Added milestone: {$title}", '🏆']);

    echo json_encode(['success' => true, 'id' => $mid, 'title' => sanitize($title), 'status' => 'upcoming', 'target_date' => $date]);

} elseif ($action === 'update') {
    $mid    = (int)($_POST['milestone_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    if (!$mid || !in_array($status, ['upcoming','in_progress','completed'])) {
        echo json_encode(['error' => 'Invalid']); exit;
    }
    $db->prepare("UPDATE milestones SET status=? WHERE id=?")->execute([$status, $mid]);

    // If completed, notify project members
    if ($status === 'completed') {
        $ms = $db->prepare("SELECT m.title, m.project_id, p.name AS pname FROM milestones m JOIN projects p ON p.id=m.project_id WHERE m.id=?");
        $ms->execute([$mid]);
        $msRow = $ms->fetch();
        if ($msRow) {
            $mems = $db->prepare("SELECT student_id FROM project_members WHERE project_id=?");
            $mems->execute([$msRow['project_id']]);
            $msg = "🏆 Milestone \"{$msRow['title']}\" completed in project \"{$msRow['pname']}\"!";
            foreach ($mems->fetchAll() as $m) {
                if ($m['student_id'] != $studentId) {
                    $db->prepare("INSERT INTO notifications (student_id,type,message,link) VALUES (?,?,?,?)")
                       ->execute([$m['student_id'], 'milestone_done', $msg, BASE_URL.'/project/view.php?id='.$msRow['project_id']]);
                }
            }
            $db->prepare("INSERT INTO activity_log (student_id, project_id, action_type, detail, icon) VALUES (?,?,?,?,?)")
               ->execute([$studentId, $msRow['project_id'], 'milestone_completed', "Completed milestone: {$msRow['title']}", '✅']);
        }
    }
    echo json_encode(['success' => true, 'status' => $status]);

} elseif ($action === 'delete') {
    $mid = (int)($_POST['milestone_id'] ?? 0);
    $db->prepare("DELETE FROM milestones WHERE id=?")->execute([$mid]);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Unknown action']);
}

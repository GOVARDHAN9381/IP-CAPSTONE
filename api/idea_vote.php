<?php
/**
 * api/idea_vote.php  — AJAX handler for idea up/down votes
 * POST: idea_id, vote (up|down)
 */
session_start();
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

if (!isLoggedIn()) { echo json_encode(['error' => 'Unauthorized']); exit; }
verifyCsrf();

$studentId = $_SESSION['student_id'];
$db        = getDB();
$ideaId    = (int)($_POST['idea_id'] ?? 0);
$vote      = $_POST['vote'] ?? '';

if (!$ideaId || !in_array($vote, ['up', 'down'])) {
    echo json_encode(['error' => 'Invalid request']); exit;
}

// Fetch idea
$idea = $db->prepare("SELECT * FROM ideas WHERE id=?");
$idea->execute([$ideaId]);
$ideaRow = $idea->fetch();
if (!$ideaRow) { echo json_encode(['error' => 'Idea not found']); exit; }

// Check existing vote
$existing = $db->prepare("SELECT vote FROM idea_votes WHERE idea_id=? AND student_id=?");
$existing->execute([$ideaId, $studentId]);
$existingVote = $existing->fetchColumn();

if ($existingVote === $vote) {
    // Toggle off — remove vote
    $db->prepare("DELETE FROM idea_votes WHERE idea_id=? AND student_id=?")->execute([$ideaId, $studentId]);
} else {
    // Insert or update
    $db->prepare("INSERT INTO idea_votes (idea_id, student_id, vote) VALUES (?,?,?)
                  ON DUPLICATE KEY UPDATE vote=VALUES(vote)")
       ->execute([$ideaId, $studentId, $vote]);

    // Notify idea author
    if ($ideaRow['submitted_by'] != $studentId) {
        $me = $db->prepare("SELECT name FROM students WHERE id=?");
        $me->execute([$studentId]);
        $myName = $me->fetchColumn();
        $msg = ($vote === 'up') ? "👍 {$myName} upvoted your idea \"{$ideaRow['title']}\"!"
                                : "👎 {$myName} downvoted your idea \"{$ideaRow['title']}\".";
        $db->prepare("INSERT INTO notifications (student_id,type,message,link) VALUES (?,?,?,?)")
           ->execute([$ideaRow['submitted_by'], 'idea_voted', $msg, BASE_URL.'/ideas/view.php?id='.$ideaId]);
    }
}

// Re-count
$ups   = $db->prepare("SELECT COUNT(*) FROM idea_votes WHERE idea_id=? AND vote='up'");   $ups->execute([$ideaId]);
$downs = $db->prepare("SELECT COUNT(*) FROM idea_votes WHERE idea_id=? AND vote='down'"); $downs->execute([$ideaId]);

echo json_encode(['success' => true, 'upvotes' => (int)$ups->fetchColumn(), 'downvotes' => (int)$downs->fetchColumn()]);

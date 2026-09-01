<?php
/**
 * api/notifications.php
 * Returns JSON list of notifications for the logged-in user (student OR faculty).
 *
 * GET ?unread_only=1        → { unread: N }
 * GET ?limit=8              → { success, unread_count, notifications:[...] }
 * GET ?all=1                → same as limit=30
 * GET ?mark_read=1          → marks all as read (legacy, prefer notify_read.php)
 */
session_start();
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

// Accept both student and faculty sessions
$studentId = $_SESSION['student_id'] ?? null;
if (!$studentId) {
    // Faculty don't have a personal notification inbox (yet) – return empty
    echo json_encode(['success' => true, 'unread' => 0, 'unread_count' => 0, 'notifications' => []]);
    exit;
}
$studentId = (int)$studentId;
$db        = getDB();

// Legacy: mark-read via GET (deprecated – use notify_read.php POST instead)
if (!empty($_GET['mark_read'])) {
    $db->prepare("UPDATE notifications SET is_read=1 WHERE student_id=?")->execute([$studentId]);
    echo json_encode(['success' => true]); exit;
}

// Unread count
$uStmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE student_id=? AND is_read=0");
$uStmt->execute([$studentId]);
$unread = (int)$uStmt->fetchColumn();

if (!empty($_GET['unread_only'])) {
    echo json_encode(['success' => true, 'unread' => $unread, 'unread_count' => $unread]);
    exit;
}

// Full list
$limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 30;
$stmt  = $db->prepare(
    "SELECT id, type, message, link, is_read, created_at
     FROM notifications
     WHERE student_id = ?
     ORDER BY created_at DESC
     LIMIT $limit"
);
$stmt->execute([$studentId]);
$notes = $stmt->fetchAll();

echo json_encode([
    'success'      => true,
    'unread'       => $unread,
    'unread_count' => $unread,
    'notifications' => $notes
]);

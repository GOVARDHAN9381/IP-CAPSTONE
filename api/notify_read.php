<?php
/**
 * api/notify_read.php
 * Mark a notification (or all) as read.
 *
 * POST params:
 *   id    (int|'all')  – specific notification id, or 'all'
 *   type  (string)     – 'single' | 'all'   (default: single)
 */
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['student_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$studentId = (int)$_SESSION['student_id'];
$type      = $_POST['type'] ?? 'single';

try {
    $db = getDB();

    if ($type === 'all') {
        // Mark every unread notification for this student as read
        $stmt = $db->prepare(
            "UPDATE notifications SET is_read = 1
             WHERE student_id = ? AND is_read = 0"
        );
        $stmt->execute([$studentId]);
        $affected = $stmt->rowCount();

        echo json_encode([
            'success' => true,
            'message' => "Marked $affected notification(s) as read"
        ]);
    } else {
        // Mark a single notification
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid notification id']);
            exit;
        }

        // Ensure the notification belongs to this student
        $stmt = $db->prepare(
            "UPDATE notifications SET is_read = 1
             WHERE id = ? AND student_id = ?"
        );
        $stmt->execute([$id, $studentId]);

        if ($stmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'Notification not found']);
        } else {
            echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

<?php
session_start();
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

if (!isLoggedIn()) { echo json_encode(['error'=>'Unauthorized']); exit; }

$studentId = $_SESSION['student_id'];
$projectId = (int)($_POST['project_id'] ?? 0);

if (!$projectId || !isset($_FILES['file'])) {
    echo json_encode(['error'=>'Missing data']); exit;
}

$db = getDB();
$mem = $db->prepare("SELECT 1 FROM project_members WHERE project_id = ? AND student_id = ?");
$mem->execute([$projectId, $studentId]);
if (!$mem->fetch()) { echo json_encode(['error'=>'Not a member']); exit; }

$file     = $_FILES['file'];
$origName = basename($file['name']);
$size     = $file['size'];

if ($size > MAX_FILE_SIZE) { echo json_encode(['error'=>'File exceeds 10 MB limit']); exit; }

$allowedTypes = ['image/','application/pdf','text/','application/zip','application/msword',
                 'application/vnd.openxmlformats','application/vnd.ms-'];
$mime  = mime_content_type($file['tmp_name']);
$valid = false;
foreach ($allowedTypes as $t) { if (str_starts_with($mime, $t)) { $valid = true; break; } }
if (!$valid) { echo json_encode(['error'=>'File type not allowed']); exit; }

// Save file
$ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
$newName  = uniqid('proj_') . '.' . $ext;
$destDir  = __DIR__ . '/../assets/uploads/';
if (!is_dir($destDir)) mkdir($destDir, 0755, true);
$destPath = $destDir . $newName;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    echo json_encode(['error'=>'Failed to save file']); exit;
}

$relPath = 'assets/uploads/' . $newName;
$db->prepare("INSERT INTO uploads (project_id, student_id, original_name, filepath, filesize) VALUES (?,?,?,?,?)")
   ->execute([$projectId, $studentId, $origName, $relPath, $size]);

echo json_encode(['success'=>true,'filename'=>$origName]);

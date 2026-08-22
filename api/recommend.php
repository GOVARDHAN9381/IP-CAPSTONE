<?php
/**
 * AI Team Recommendation Engine
 * Returns JSON with top-N recommended teammates
 * 
 * Algorithm:
 *  shared_skills     = |skills(me) ∩ skills(other)|
 *  complementary     = |skills(other) - skills(me)|  ← new skills they bring
 *  interest_overlap  = |interests(me) ∩ interests(other)|
 *  
 *  raw_score = (shared * 2) + (complementary * 1) + (interest_overlap * 1.5)
 *  max_score = (|my_skills| * 2) + (|all_skills| * 1) + (|my_interests| * 1.5)
 *  pct_score = (raw_score / max_possible) * 100   (capped at 100)
 *  stars     = max(1, ceil(pct_score / 20))        (1–5 stars)
 */

session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']); exit;
}

$studentId = $_SESSION['student_id'];
$db        = getDB();
$topN      = (int)($_GET['n'] ?? 5);
$topN      = min(10, max(1, $topN));

// My skills and interests
$mySkillStmt = $db->prepare("SELECT skill_id FROM student_skills WHERE student_id = ?");
$mySkillStmt->execute([$studentId]);
$mySkills = array_column($mySkillStmt->fetchAll(), 'skill_id');

$myIntStmt = $db->prepare("SELECT interest_id FROM student_interests WHERE student_id = ?");
$myIntStmt->execute([$studentId]);
$myInterests = array_column($myIntStmt->fetchAll(), 'interest_id');

// All other students
$othersStmt = $db->prepare("SELECT * FROM students WHERE id != ?");
$othersStmt->execute([$studentId]);
$others = $othersStmt->fetchAll();

$results = [];

foreach ($others as $other) {
    $oid = $other['id'];

    // Their skills
    $theirSkillStmt = $db->prepare("SELECT skill_id FROM student_skills WHERE student_id = ?");
    $theirSkillStmt->execute([$oid]);
    $theirSkills = array_column($theirSkillStmt->fetchAll(), 'skill_id');

    // Their interests
    $theirIntStmt = $db->prepare("SELECT interest_id FROM student_interests WHERE student_id = ?");
    $theirIntStmt->execute([$oid]);
    $theirInterests = array_column($theirIntStmt->fetchAll(), 'interest_id');

    // Score components
    $sharedSkills     = count(array_intersect($mySkills, $theirSkills));
    $complementary    = count(array_diff($theirSkills, $mySkills));   // skills I don't have
    $interestOverlap  = count(array_intersect($myInterests, $theirInterests));

    $rawScore = ($sharedSkills * 2) + ($complementary * 1) + ($interestOverlap * 1.5);

    // Normalise: max possible score if they had all your skills + a full complement + all your interests
    $mySkillCount  = max(1, count($mySkills));
    $myIntCount    = max(1, count($myInterests));
    $totalSkillsCt = 24; // total skills in DB
    $maxPossible   = ($mySkillCount * 2) + ($totalSkillsCt * 1) + ($myIntCount * 1.5);

    $pctScore = (int)round(min(100, ($rawScore / $maxPossible) * 100));
    $stars    = max(1, (int)ceil($pctScore / 20));

    // Shared skill names
    $sharedNames = [];
    if (!empty(array_intersect($mySkills, $theirSkills))) {
        $placeholders = implode(',', array_fill(0, count(array_intersect($mySkills, $theirSkills)), '?'));
        $ids = array_intersect($mySkills, $theirSkills);
        $nameStmt = $db->prepare("SELECT name, icon FROM skills WHERE id IN ($placeholders)");
        $nameStmt->execute(array_values($ids));
        $sharedNames = $nameStmt->fetchAll();
    }

    // Complementary skill names
    $compNames = [];
    if (!empty(array_diff($theirSkills, $mySkills))) {
        $compIds = array_diff($theirSkills, $mySkills);
        $placeholders = implode(',', array_fill(0, count($compIds), '?'));
        $cStmt = $db->prepare("SELECT name, icon FROM skills WHERE id IN ($placeholders)");
        $cStmt->execute(array_values($compIds));
        $compNames = $cStmt->fetchAll();
    }

    // Recent project count
    $projCnt = $db->prepare("SELECT COUNT(*) FROM project_members WHERE student_id = ?");
    $projCnt->execute([$oid]);
    $projCount = $projCnt->fetchColumn();

    $results[] = [
        'id'             => $oid,
        'name'           => $other['name'],
        'department'     => $other['department'] ?? '',
        'year'           => $other['year'] ?? '',
        'bio'            => $other['bio'] ?? '',
        'avatar'         => generateAvatar($other['name']),
        'score'          => $pctScore,
        'stars'          => $stars,
        'shared_skills'  => $sharedNames,
        'comp_skills'    => $compNames,
        'interest_match' => $interestOverlap,
        'project_count'  => $projCount,
    ];
}

// Sort by score descending
usort($results, fn($a, $b) => $b['score'] <=> $a['score']);

echo json_encode([
    'success'         => true,
    'my_skill_count'  => count($mySkills),
    'total_students'  => count($others),
    'recommendations' => array_slice($results, 0, $topN),
]);

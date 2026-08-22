<?php
session_start();
require_once __DIR__ . '/../config/db.php';
requireLogin();

$db        = getDB();
$studentId = $_SESSION['student_id'];
$student   = currentStudent();
$errors    = [];
$success   = '';

// Fetch all skills and interests
$allSkills    = $db->query("SELECT * FROM skills ORDER BY category, name")->fetchAll();
$allInterests = $db->query("SELECT * FROM interests ORDER BY name")->fetchAll();

// Current selections
$mySkillIds    = $db->prepare("SELECT skill_id FROM student_skills WHERE student_id = ?");
$mySkillIds->execute([$studentId]);
$mySkillIds = array_column($mySkillIds->fetchAll(), 'skill_id');

$myIntIds = $db->prepare("SELECT interest_id FROM student_interests WHERE student_id = ?");
$myIntIds->execute([$studentId]);
$myIntIds = array_column($myIntIds->fetchAll(), 'interest_id');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = trim($_POST['name'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $year       = $_POST['year'] ?? '1st';
    $bio        = trim($_POST['bio'] ?? '');
    $github     = trim($_POST['github_url'] ?? '');
    $linkedin   = trim($_POST['linkedin_url'] ?? '');
    $skills     = $_POST['skills'] ?? [];
    $interests  = $_POST['interests'] ?? [];

    if (strlen($name) < 2) $errors[] = 'Name must be at least 2 characters.';

    if (empty($errors)) {
        // Validate URLs if provided
        if ($github && !filter_var($github, FILTER_VALIDATE_URL)) $errors[] = 'GitHub URL is not valid.';
        if ($linkedin && !filter_var($linkedin, FILTER_VALIDATE_URL)) $errors[] = 'LinkedIn URL is not valid.';
        // Strip to safe prefixes
        if ($github && !str_starts_with($github, 'https://github.com/')) $errors[] = 'GitHub URL must start with https://github.com/';
        if ($linkedin && !str_starts_with($linkedin, 'https://linkedin.com/') && !str_starts_with($linkedin, 'https://www.linkedin.com/')) $errors[] = 'LinkedIn URL must start with https://linkedin.com/';
    }

    if (empty($errors)) {
        $db->prepare("UPDATE students SET name=?, department=?, year=?, bio=?, github_url=?, linkedin_url=? WHERE id=?")
           ->execute([$name, $department, $year, $bio, $github, $linkedin, $studentId]);

        $db->prepare("DELETE FROM student_skills WHERE student_id = ?")->execute([$studentId]);
        foreach ($skills as $sid) {
            $db->prepare("INSERT IGNORE INTO student_skills (student_id, skill_id) VALUES (?,?)")->execute([$studentId, (int)$sid]);
        }

        $db->prepare("DELETE FROM student_interests WHERE student_id = ?")->execute([$studentId]);
        foreach ($interests as $iid) {
            $db->prepare("INSERT IGNORE INTO student_interests (student_id, interest_id) VALUES (?,?)")->execute([$studentId, (int)$iid]);
        }

        // Refresh session
        $updated = $db->prepare("SELECT * FROM students WHERE id = ?");
        $updated->execute([$studentId]);
        $_SESSION['student'] = $updated->fetch();
        $student = $_SESSION['student'];
        $mySkillIds = array_map('intval', $skills);
        $myIntIds   = array_map('intval', $interests);
        $success = 'Profile updated successfully!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile — CollabIQ</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/auth.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dashboard.css">
</head>
<body>
    <nav class="nav">
        <div class="nav-brand">🧠 Collab<span class="gradient-text">IQ</span></div>
        <div class="nav-user">
            <img src="<?= generateAvatar($student['name']) ?>" class="nav-avatar" alt="">
            <span class="nav-name"><?= sanitize($student['name']) ?></span>
            <a href="<?= BASE_URL ?>/auth/logout.php" class="btn btn-ghost btn-sm">Sign Out</a>
        </div>
    </nav>

    <div class="app-layout">
        <aside class="sidebar">
            <div class="sidebar-section">
                <div class="sidebar-label">Main</div>
                <a href="<?= BASE_URL ?>/student/dashboard.php" class="sidebar-link"><span class="icon">🏠</span> Dashboard</a>
                <a href="<?= BASE_URL ?>/student/profile.php" class="sidebar-link active"><span class="icon">👤</span> My Profile</a>
                <a href="<?= BASE_URL ?>/student/recommendations.php" class="sidebar-link"><span class="icon">🤖</span> AI Recommendations</a>
            </div>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <h1>✏️ Edit Profile</h1>
                <p>Update your information, skills, and interests to get better AI recommendations.</p>
            </div>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-error"><?php foreach($errors as $e) echo "<p>⚠️ ".sanitize($e)."</p>"; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
            <div class="alert alert-success">✅ <?= sanitize($success) ?></div>
            <?php endif; ?>

            <form method="POST">
                <?= csrfField() ?>
                <!-- Basic Info -->
                <div class="data-card" style="margin-bottom:1.5rem;">
                    <div class="data-card-header"><h3 class="data-card-title">👤 Basic Information</h3></div>
                    <div class="data-card-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Full Name *</label>
                                <div class="input-wrapper">
                                    <span class="input-icon">👤</span>
                                    <input type="text" name="name" required value="<?= sanitize($student['name']) ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Department</label>
                                <div class="input-wrapper">
                                    <span class="input-icon">🏫</span>
                                    <input type="text" name="department" value="<?= sanitize($student['department']??'') ?>">
                                </div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Year of Study</label>
                                <div class="input-wrapper">
                                    <span class="input-icon">📅</span>
                                    <select name="year">
                                        <?php foreach(['1st','2nd','3rd','4th'] as $y): ?>
                                        <option value="<?=$y?>" <?= ($student['year'] ?? '')===$y?'selected':'' ?>><?=$y?> Year</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>GitHub URL</label>
                                <div class="input-wrapper">
                                    <span class="input-icon">🐙</span>
                                    <input type="url" name="github_url" placeholder="https://github.com/..." value="<?= sanitize($student['github_url']??'') ?>">
                                </div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>LinkedIn URL</label>
                                <div class="input-wrapper">
                                    <span class="input-icon">💼</span>
                                    <input type="url" name="linkedin_url" placeholder="https://linkedin.com/in/..." value="<?= sanitize($student['linkedin_url']??'') ?>">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Bio</label>
                            <div class="input-wrapper">
                                <textarea name="bio" rows="3" placeholder="Tell your team about yourself..."><?= sanitize($student['bio']??'') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Skills -->
                <div class="data-card" style="margin-bottom:1.5rem;">
                    <div class="data-card-header"><h3 class="data-card-title">⚡ Technical Skills</h3></div>
                    <div class="data-card-body">
                        <div class="skills-grid">
                            <?php
                            $currentCat = '';
                            foreach ($allSkills as $skill):
                                if ($skill['category'] !== $currentCat) {
                                    if ($currentCat !== '') echo '</div>'; // close previous category
                                    echo '<div class="skill-category"><h4 class="category-label">' . sanitize($skill['category']) . '</h4>';
                                    $currentCat = $skill['category'];
                                }
                                $checked = in_array($skill['id'], $mySkillIds);
                            ?>
                            <label class="skill-chip <?= $checked?'checked':'' ?>">
                                <input type="checkbox" name="skills[]" value="<?= $skill['id'] ?>" <?= $checked?'checked':'' ?>>
                                <span><?= $skill['icon'] ?> <?= sanitize($skill['name']) ?></span>
                            </label>
                            <?php endforeach; if ($currentCat !== '') echo '</div>'; ?>
                        </div>
                    </div>
                </div>

                <!-- Interests -->
                <div class="data-card" style="margin-bottom:1.5rem;">
                    <div class="data-card-header"><h3 class="data-card-title">🎯 Interests</h3></div>
                    <div class="data-card-body">
                        <div class="interests-grid">
                            <?php foreach ($allInterests as $int):
                                $checked = in_array($int['id'], $myIntIds); ?>
                            <label class="interest-chip <?= $checked?'checked':'' ?>">
                                <input type="checkbox" name="interests[]" value="<?= $int['id'] ?>" <?= $checked?'checked':'' ?>>
                                <span><?= $int['icon'] ?> <?= sanitize($int['name']) ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div style="display:flex;gap:1rem;justify-content:flex-end;">
                    <a href="<?= BASE_URL ?>/student/profile.php" class="btn btn-ghost">Cancel</a>
                    <button type="submit" class="btn btn-primary">💾 Save Changes</button>
                </div>
            </form>
        </main>
    </div>

    <script>
    document.querySelectorAll('.skill-chip input, .interest-chip input').forEach(cb => {
        cb.addEventListener('change', function() {
            this.closest('label').classList.toggle('checked', this.checked);
        });
    });
    </script>
    <script>window.APP_BASE = '<?= BASE_URL ?>';</script>
    <script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>

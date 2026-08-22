<?php
session_start();
require_once __DIR__ . '/../config/db.php';
requireLogin();

$db        = getDB();
$studentId = $_SESSION['student_id'];
$student   = currentStudent();
$errors    = [];

// Pre-fill invite if coming from recommendations page
$preInvite = (int)($_GET['invite'] ?? 0);

// All students (for member picker)
$allStudents = $db->prepare("SELECT id, name, department, year FROM students WHERE id != ? ORDER BY name");
$allStudents->execute([$studentId]);
$allStudents = $allStudents->fetchAll();

// All faculty
$allFaculty = $db->query("SELECT id, name FROM faculty ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $deadline    = $_POST['deadline'] ?? '';
    $facultyId   = (int)($_POST['faculty_id'] ?? 0) ?: null;
    $members     = $_POST['members'] ?? [];

    if (strlen($name) < 3) $errors[] = 'Project name must be at least 3 characters.';

    if (empty($errors)) {
        $db->prepare("INSERT INTO projects (name, description, leader_id, faculty_id, deadline, status) VALUES (?,?,?,?,?,?)")
           ->execute([$name, $description, $studentId, $facultyId, $deadline ?: null, 'active']);
        $projId = $db->lastInsertId();

        // Add leader
        $db->prepare("INSERT INTO project_members (project_id, student_id, role) VALUES (?,?,'leader')")->execute([$projId, $studentId]);

        // Add selected members
        foreach ($members as $mid) {
            $mid = (int)$mid;
            if ($mid !== $studentId) {
                $db->prepare("INSERT IGNORE INTO project_members (project_id, student_id, role) VALUES (?,?,'member')")->execute([$projId, $mid]);
            }
        }

        header('Location: ' . BASE_URL . '/project/view.php?id=' . $projId . '&created=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Project — CollabIQ</title>
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
                <a href="<?= BASE_URL ?>/student/profile.php" class="sidebar-link"><span class="icon">👤</span> My Profile</a>
                <a href="<?= BASE_URL ?>/student/recommendations.php" class="sidebar-link"><span class="icon">🤖</span> AI Recommendations</a>
            </div>
            <div class="sidebar-section">
                <div class="sidebar-label">Projects</div>
                <a href="<?= BASE_URL ?>/project/create.php" class="sidebar-link active"><span class="icon">➕</span> New Project</a>
            </div>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <h1>🚀 Create New Project</h1>
                <p>Set up your project and invite team members.</p>
            </div>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-error"><?php foreach($errors as $e) echo "<p>⚠️ ".sanitize($e)."</p>"; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="data-card" style="margin-bottom:1.5rem;">
                    <div class="data-card-header"><h3 class="data-card-title">📋 Project Details</h3></div>
                    <div class="data-card-body">
                        <div class="form-group">
                            <label>Project Name *</label>
                            <div class="input-wrapper">
                                <span class="input-icon">📁</span>
                                <input type="text" name="name" placeholder="Smart Campus Navigation App" required value="<?= sanitize($_POST['name']??'') ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <div class="input-wrapper">
                                <textarea name="description" rows="4" placeholder="Describe your project goals and what you'll build together..."><?= sanitize($_POST['description']??'') ?></textarea>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Deadline (optional)</label>
                                <div class="input-wrapper">
                                    <span class="input-icon">📅</span>
                                    <input type="date" name="deadline" value="<?= sanitize($_POST['deadline']??'') ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Faculty Supervisor (optional)</label>
                                <div class="input-wrapper">
                                    <span class="input-icon">👨‍🏫</span>
                                    <select name="faculty_id">
                                        <option value="">— No supervisor —</option>
                                        <?php foreach ($allFaculty as $f): ?>
                                        <option value="<?= $f['id'] ?>"><?= sanitize($f['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Team Members -->
                <div class="data-card" style="margin-bottom:1.5rem;">
                    <div class="data-card-header">
                        <h3 class="data-card-title">👥 Add Team Members</h3>
                        <a href="<?= BASE_URL ?>/student/recommendations.php" class="btn btn-secondary btn-sm">🤖 View AI Picks</a>
                    </div>
                    <div class="data-card-body">
                        <p class="text-sm text-muted" style="margin-bottom:1rem;">You are automatically added as the team leader. Select additional members:</p>

                        <!-- Search filter -->
                        <div class="form-group">
                            <div class="input-wrapper">
                                <span class="input-icon">🔍</span>
                                <input type="text" id="member-search" placeholder="Search students by name..." style="padding-left:2.5rem;">
                            </div>
                        </div>

                        <div id="member-list" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:.75rem;max-height:360px;overflow-y:auto;">
                            <?php foreach ($allStudents as $s):
                                $preChecked = ($preInvite === $s['id']);
                            ?>
                            <label class="member-card <?= $preChecked?'selected':'' ?>" data-name="<?= strtolower($s['name']) ?>">
                                <input type="checkbox" name="members[]" value="<?= $s['id'] ?>" <?= $preChecked?'checked':'' ?>>
                                <img src="<?= generateAvatar($s['name']) ?>" class="avatar avatar-sm" alt="">
                                <div>
                                    <div style="font-weight:600;font-size:.875rem;"><?= sanitize($s['name']) ?></div>
                                    <div class="text-xs text-muted"><?= sanitize($s['department']??'') ?> · <?= $s['year'] ?></div>
                                </div>
                                <span class="check-mark">✓</span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div style="display:flex;gap:1rem;justify-content:flex-end;">
                    <a href="<?= BASE_URL ?>/student/dashboard.php" class="btn btn-ghost">Cancel</a>
                    <button type="submit" class="btn btn-primary">🚀 Create Project</button>
                </div>
            </form>
        </main>
    </div>

    <style>
    .member-card {
        display:flex;align-items:center;gap:.75rem;
        padding:.75rem 1rem;
        background:rgba(255,255,255,.04);
        border:1px solid var(--border-default);
        border-radius:var(--radius-md);
        cursor:pointer;
        transition:all .2s;
        position:relative;
    }
    .member-card input { display:none; }
    .member-card:hover { border-color:var(--accent-indigo);background:rgba(99,102,241,.08); }
    .member-card.selected { border-color:var(--accent-indigo);background:rgba(99,102,241,.15); }
    .check-mark { display:none;position:absolute;right:.75rem;top:50%;transform:translateY(-50%);color:var(--accent-emerald);font-weight:700; }
    .member-card.selected .check-mark { display:block; }
    </style>
    <script>
    document.querySelectorAll('.member-card').forEach(card => {
        card.addEventListener('click', () => {
            const cb = card.querySelector('input');
            cb.checked = !cb.checked;
            card.classList.toggle('selected', cb.checked);
        });
    });
    document.getElementById('member-search').addEventListener('input', function() {
        const q = this.value.toLowerCase();
        document.querySelectorAll('.member-card').forEach(card => {
            card.style.display = card.dataset.name.includes(q) ? '' : 'none';
        });
    });
    </script>
    <script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>

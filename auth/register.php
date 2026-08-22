<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/student/dashboard.php');
    exit;
}

$db     = getDB();
$errors = [];
$step   = (int)($_POST['step'] ?? 1);
$formData = $_SESSION['reg_form'] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 1) {
        $name       = trim($_POST['name'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $password   = $_POST['password'] ?? '';
        $confirm    = $_POST['confirm'] ?? '';
        $department = trim($_POST['department'] ?? '');
        $year       = $_POST['year'] ?? '1st';
        $bio        = trim($_POST['bio'] ?? '');

        if (strlen($name) < 2) $errors[] = 'Name must be at least 2 characters.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';
        if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
        if ($password !== $confirm) $errors[] = 'Passwords do not match.';

        if (empty($errors)) {
            $chk = $db->prepare("SELECT id FROM students WHERE email = ?");
            $chk->execute([$email]);
            if ($chk->fetch()) $errors[] = 'This email is already registered.';
        }

        if (empty($errors)) {
            $_SESSION['reg_form'] = compact('name','email','password','department','year','bio');
            $step = 2;
        }
    } elseif ($step === 2) {
        $_SESSION['reg_form']['skills'] = $_POST['skills'] ?? [];
        $step = 3;
    } elseif ($step === 3) {
        $interests = $_POST['interests'] ?? [];
        $_SESSION['reg_form']['interests'] = $interests;

        // Save to DB
        $reg = $_SESSION['reg_form'];
        $hash = password_hash($reg['password'], PASSWORD_BCRYPT);

        $ins = $db->prepare("INSERT INTO students (name, email, password, department, year, bio) VALUES (?,?,?,?,?,?)");
        $ins->execute([$reg['name'],$reg['email'],$hash,$reg['department'],$reg['year'],$reg['bio']]);
        $studentId = $db->lastInsertId();

        foreach ($reg['skills'] as $skillId) {
            $db->prepare("INSERT IGNORE INTO student_skills (student_id, skill_id) VALUES (?,?)")->execute([$studentId, (int)$skillId]);
        }
        foreach ($reg['interests'] as $intId) {
            $db->prepare("INSERT IGNORE INTO student_interests (student_id, interest_id) VALUES (?,?)")->execute([$studentId, (int)$intId]);
        }

        $student = $db->prepare("SELECT * FROM students WHERE id = ?");
        $student->execute([$studentId]);
        $studentData = $student->fetch();

        $_SESSION['student_id'] = $studentId;
        $_SESSION['student']    = $studentData;
        unset($_SESSION['reg_form']);

        header('Location: ' . BASE_URL . '/student/dashboard.php?welcome=1');
        exit;
    }
}

// Fetch skills and interests for step 2 & 3
$allSkills    = $db->query("SELECT * FROM skills ORDER BY category, name")->fetchAll();
$allInterests = $db->query("SELECT * FROM interests ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — CollabIQ</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/auth.css">
</head>
<body class="auth-body">
    <div class="auth-bg">
        <div class="auth-orb orb-1"></div>
        <div class="auth-orb orb-2"></div>
        <div class="auth-orb orb-3"></div>
    </div>

    <div class="auth-container auth-container--wide">
        <div class="auth-card glass-card">
            <!-- Logo -->
            <div class="auth-logo">
                <div class="logo-icon">🧠</div>
                <h1 class="logo-text">Collab<span class="gradient-text">IQ</span></h1>
            </div>

            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step <?= $step>=1?'active':'' ?> <?= $step>1?'done':'' ?>">
                    <div class="step-circle"><?= $step>1?'✓':'1' ?></div>
                    <span>Basic Info</span>
                </div>
                <div class="step-line"></div>
                <div class="step <?= $step>=2?'active':'' ?> <?= $step>2?'done':'' ?>">
                    <div class="step-circle"><?= $step>2?'✓':'2' ?></div>
                    <span>Skills</span>
                </div>
                <div class="step-line"></div>
                <div class="step <?= $step>=3?'active':'' ?>">
                    <div class="step-circle">3</div>
                    <span>Interests</span>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach($errors as $e): ?>
                    <p>⚠️ <?= sanitize($e) ?></p>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- STEP 1: Basic Info -->
            <?php if ($step === 1): ?>
            <form method="POST" class="auth-form">
                <?= csrfField() ?>
                <input type="hidden" name="step" value="1">
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name</label>
                        <div class="input-wrapper">
                            <span class="input-icon">👤</span>
                            <input type="text" name="name" placeholder="Govardhan N" required value="<?= sanitize($formData['name']??'') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <div class="input-wrapper">
                            <span class="input-icon">📧</span>
                            <input type="email" name="email" placeholder="you@student.edu" required value="<?= sanitize($formData['email']??'') ?>">
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Department</label>
                        <div class="input-wrapper">
                            <span class="input-icon">🏫</span>
                            <input type="text" name="department" placeholder="Computer Science" value="<?= sanitize($formData['department']??'') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Year of Study</label>
                        <div class="input-wrapper">
                            <span class="input-icon">📅</span>
                            <select name="year">
                                <?php foreach(['1st','2nd','3rd','4th'] as $y): ?>
                                <option value="<?=$y?>" <?= ($formData['year']??'1st')===$y?'selected':'' ?>><?=$y?> Year</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Bio (optional)</label>
                    <div class="input-wrapper">
                        <textarea name="bio" placeholder="Tell your team a bit about yourself..." rows="3"><?= sanitize($formData['bio']??'') ?></textarea>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Password</label>
                        <div class="input-wrapper">
                            <span class="input-icon">🔒</span>
                            <input type="password" name="password" id="reg-password" placeholder="Min 6 characters" required>
                        </div>
                        <div id="strength-bar" style="margin-top:.4rem;height:4px;border-radius:99px;background:var(--border-default);overflow:hidden;">
                            <div id="strength-fill" style="height:100%;width:0%;transition:width .3s,background .3s;"></div>
                        </div>
                        <div id="strength-label" class="text-xs text-muted" style="margin-top:.25rem;"></div>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <div class="input-wrapper">
                            <span class="input-icon">🔒</span>
                            <input type="password" name="confirm" id="reg-confirm" placeholder="Repeat password" required>
                        </div>
                        <div id="match-label" class="text-xs" style="margin-top:.25rem;"></div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-full">Next: Add Skills →</button>
            </form>
            <?php endif; ?>

            <!-- STEP 2: Skills -->
            <?php if ($step === 2): ?>
            <form method="POST" class="auth-form">
                <input type="hidden" name="step" value="2">
                <p class="step-hint">Select your technical skills below, then click Next:</p>
                <div class="skills-grid">
                    <?php
                    $currentCat = '';
                    foreach ($allSkills as $skill):
                        if ($skill['category'] !== $currentCat) {
                            if ($currentCat !== '') echo '</div>'; // close prev category
                            echo '<div class="skill-category"><h4 class="category-label">' . sanitize($skill['category']) . '</h4>';
                            $currentCat = $skill['category'];
                        }
                    ?>
                    <label class="skill-chip <?= in_array($skill['id'], $formData['skills']??[]) ? 'checked':'' ?>">
                        <input type="checkbox" name="skills[]" value="<?= $skill['id'] ?>"
                               <?= in_array($skill['id'], $formData['skills']??[]) ? 'checked':'' ?>>
                        <span><?= $skill['icon'] ?> <?= sanitize($skill['name']) ?></span>
                    </label>
                    <?php endforeach; if ($currentCat !== '') echo '</div>'; ?>
                    </div>
                </div>

                <div style="margin-top:1.5rem; display:flex; flex-direction:column; gap:.75rem;">
                    <button type="submit" class="btn btn-primary btn-full" style="padding:.85rem 1.5rem; font-size:1rem; font-weight:700;">
                        Next: Select Interests →
                    </button>
                </div>
            </form>
            <?php endif; ?>

            <!-- STEP 3: Interests -->
            <?php if ($step === 3): ?>
            <form method="POST" class="auth-form">
                <input type="hidden" name="step" value="3">
                <p class="step-hint">What areas excite you the most? (Select all that apply):</p>
                <div class="interests-grid">
                    <?php foreach ($allInterests as $int): ?>
                    <label class="interest-chip">
                        <input type="checkbox" name="interests[]" value="<?= $int['id'] ?>">
                        <span><?= $int['icon'] ?> <?= sanitize($int['name']) ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
                <div style="margin-top:1.5rem;">
                    <button type="submit" class="btn btn-primary btn-full" style="padding:.85rem 1.5rem; font-size:1rem; font-weight:700;">
                        🚀 Complete Registration
                    </button>
                </div>
            </form>
            <?php endif; ?>

            <div class="auth-footer">
                <p>Already have an account? <a href="<?= BASE_URL ?>/auth/login.php">Sign In →</a></p>
            </div>
        </div>
    </div>

    <script>
        // Skill chip toggle animation
        document.querySelectorAll('.skill-chip input, .interest-chip input').forEach(cb => {
            cb.addEventListener('change', function() {
                this.closest('label').classList.toggle('checked', this.checked);
            });
        });

        // Password strength meter
        const pwdInput = document.getElementById('reg-password');
        const confirmInput = document.getElementById('reg-confirm');
        if (pwdInput) {
            pwdInput.addEventListener('input', function() {
                const v = this.value;
                const fill = document.getElementById('strength-fill');
                const label = document.getElementById('strength-label');
                let strength = 0;
                if (v.length >= 6) strength++;
                if (v.length >= 10) strength++;
                if (/[A-Z]/.test(v)) strength++;
                if (/[0-9]/.test(v)) strength++;
                if (/[^A-Za-z0-9]/.test(v)) strength++;
                const levels = [
                    { pct: '20%', color: '#f43f5e', text: '🔴 Very Weak' },
                    { pct: '40%', color: '#f97316', text: '🟠 Weak' },
                    { pct: '60%', color: '#eab308', text: '🟡 Fair' },
                    { pct: '80%', color: '#22c55e', text: '🟢 Strong' },
                    { pct: '100%', color: '#10b981', text: '✅ Very Strong' },
                ];
                const lvl = levels[Math.min(strength, 4)];
                fill.style.width = v.length ? lvl.pct : '0%';
                fill.style.background = lvl.color;
                label.textContent = v.length ? lvl.text : '';
            });
        }
        if (confirmInput) {
            confirmInput.addEventListener('input', function() {
                const matchLabel = document.getElementById('match-label');
                if (!matchLabel) return;
                if (this.value === pwdInput.value) {
                    matchLabel.textContent = '✅ Passwords match';
                    matchLabel.style.color = '#10b981';
                } else {
                    matchLabel.textContent = '❌ Passwords do not match';
                    matchLabel.style.color = '#f43f5e';
                }
            });
        }
    </script>
</body>
</html>

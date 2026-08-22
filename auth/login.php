<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/student/dashboard.php');
    exit;
}
if (isFacultyLoggedIn()) {
    header('Location: ' . BASE_URL . '/faculty/dashboard.php');
    exit;
}

$error = '';
$role  = $_GET['role'] ?? 'student';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? 'student';
    $db       = getDB();

    if ($role === 'faculty') {
        $stmt = $db->prepare("SELECT * FROM faculty WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['faculty_id'] = $user['id'];
            $_SESSION['faculty']    = $user;
            header('Location: ' . BASE_URL . '/faculty/dashboard.php');
            exit;
        }
    } else {
        $stmt = $db->prepare("SELECT * FROM students WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['student_id'] = $user['id'];
            $_SESSION['student']    = $user;
            header('Location: ' . BASE_URL . '/student/dashboard.php');
            exit;
        }
    }
    $error = 'Invalid email or password. Please try again.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — CollabIQ</title>
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

    <div class="auth-container">
        <div class="auth-card glass-card">
            <!-- Logo -->
            <div class="auth-logo">
                <div class="logo-icon">🧠</div>
                <h1 class="logo-text">Collab<span class="gradient-text">IQ</span></h1>
                <p class="logo-sub">AI-Powered Student Collaboration Platform</p>
            </div>

            <!-- Role Tabs -->
            <div class="role-tabs">
                <button class="role-tab <?= $role==='student'?'active':'' ?>" onclick="switchRole('student')" id="tab-student">
                    🎓 Student
                </button>
                <button class="role-tab <?= $role==='faculty'?'active':'' ?>" onclick="switchRole('faculty')" id="tab-faculty">
                    👨‍🏫 Faculty
                </button>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-error">
                <span>⚠️</span> <?= sanitize($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="" id="login-form" class="auth-form">
                <input type="hidden" name="role" id="role-input" value="<?= sanitize($role) ?>">

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-wrapper">
                        <span class="input-icon">📧</span>
                        <input type="email" id="email" name="email" placeholder="you@student.edu" required
                               value="<?= sanitize($_POST['email'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">🔒</span>
                        <input type="password" id="password" name="password" placeholder="••••••••" required>
                        <button type="button" class="toggle-pass" onclick="togglePassword()">👁️</button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-full">
                    <span>Sign In</span>
                    <span class="btn-arrow">→</span>
                </button>
            </form>

            <div class="auth-footer" id="register-link">
                <p>New here? <a href="<?= BASE_URL ?>/auth/register.php">Create your account →</a></p>
            </div>

            <!-- Demo credentials -->
            <div class="demo-creds">
                <p class="demo-title">🔑 Demo Credentials</p>
                <p>Student: <code>govardhan@student.edu</code> / <code>student123</code></p>
                <p>Faculty: <code>faculty@ipcapstone.edu</code> / <code>faculty123</code></p>
            </div>
        </div>
    </div>

    <script>
        function switchRole(role) {
            document.getElementById('role-input').value = role;
            document.querySelectorAll('.role-tab').forEach(t => t.classList.remove('active'));
            document.getElementById('tab-' + role).classList.add('active');
            const regLink = document.getElementById('register-link');
            regLink.style.display = role === 'faculty' ? 'none' : 'block';
        }
        function togglePassword() {
            const p = document.getElementById('password');
            p.type = p.type === 'password' ? 'text' : 'password';
        }
        switchRole('<?= $role ?>');
    </script>
</body>
</html>

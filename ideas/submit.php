<?php
session_start();
require_once __DIR__ . '/../config/db.php';
requireLogin();

$db        = getDB();
$studentId = $_SESSION['student_id'];
$student   = currentStudent();
$error     = '';
$success   = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $title    = trim($_POST['title'] ?? '');
    $desc     = trim($_POST['description'] ?? '');
    $category = $_POST['category'] ?? 'General';
    $tags     = trim($_POST['tags'] ?? '');
    $allowed  = ['Web','Mobile','AI/ML','Game','Data','DevOps','General'];

    if (!in_array($category, $allowed)) $category = 'General';
    if (strlen($title) < 5)  $error = 'Title must be at least 5 characters.';
    elseif (strlen($desc) < 20) $error = 'Description must be at least 20 characters.';
    else {
        $db->prepare("INSERT INTO ideas (title, description, category, tags, submitted_by) VALUES (?,?,?,?,?)")
           ->execute([$title, $desc, $category, $tags, $studentId]);
        $newId = $db->lastInsertId();
        // Log activity
        try {
            $db->prepare("INSERT INTO activity_log (student_id, action_type, detail, icon) VALUES (?,?,?,?)")
               ->execute([$studentId, 'idea_submitted', "Submitted idea: {$title}", '💡']);
        } catch(\Throwable $e){}
        header('Location: '.BASE_URL.'/ideas/view.php?id='.$newId.'&new=1'); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Idea — CollabIQ</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/auth.css">
    <style>
        .submit-wrap{max-width:680px;margin:3rem auto;padding:0 1.5rem;}
        .form-header{text-align:center;margin-bottom:2rem;}
        .form-header h1{font-size:2rem;font-weight:800;margin-bottom:.5rem;}
        .tags-hint{font-size:.8rem;color:var(--text-muted);margin-top:.3rem;}
        .category-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:.6rem;}
        .cat-radio{display:none;}
        .cat-label{display:flex;flex-direction:column;align-items:center;gap:.3rem;padding:.7rem .5rem;border:1px solid var(--border-default);border-radius:var(--radius-md);cursor:pointer;font-size:.82rem;font-weight:500;color:var(--text-muted);transition:var(--transition);}
        .cat-label:hover{border-color:var(--accent-indigo);color:var(--text-primary);}
        .cat-radio:checked + .cat-label{background:rgba(99,102,241,.15);border-color:var(--accent-indigo);color:var(--accent-indigo);}
        .cat-icon{font-size:1.5rem;}
        .char-counter{font-size:.75rem;color:var(--text-muted);text-align:right;margin-top:.3rem;}
    </style>
</head>
<body>
    <div class="auth-bg">
        <div class="auth-orb orb-1"></div>
        <div class="auth-orb orb-2"></div>
    </div>
    <nav class="nav">
        <a href="<?= BASE_URL ?>/ideas/index.php" class="nav-brand" style="text-decoration:none;">🧠 Collab<span class="gradient-text">IQ</span></a>
        <a href="<?= BASE_URL ?>/ideas/index.php" class="btn btn-ghost btn-sm">← Back to Ideas</a>
    </nav>

    <div class="submit-wrap">
        <div class="form-header">
            <div style="font-size:3rem;margin-bottom:.75rem;">💡</div>
            <h1>Pitch Your Idea</h1>
            <p style="color:var(--text-muted);">Got a great project concept? Share it with the community, get votes, and bring it to life.</p>
        </div>

        <?php if($error): ?>
        <div class="alert alert-error" style="margin-bottom:1.5rem;">⚠️ <?= sanitize($error) ?></div>
        <?php endif; ?>

        <div class="glass-card" style="padding:2rem;border-radius:var(--radius-xl);">
            <form method="post">
                <?= csrfField() ?>

                <div class="form-group">
                    <label class="form-label">💡 Idea Title <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="title" class="form-input" maxlength="200"
                           placeholder="e.g. AI-Powered Study Buddy for Students"
                           value="<?= sanitize($_POST['title'] ?? '') ?>" required
                           oninput="document.getElementById('title-len').textContent=this.value.length">
                    <div class="char-counter"><span id="title-len"><?= strlen($_POST['title'] ?? '') ?></span>/200</div>
                </div>

                <div class="form-group">
                    <label class="form-label">📝 Description <span style="color:#ef4444;">*</span></label>
                    <textarea name="description" class="form-input" rows="6" maxlength="2000"
                              placeholder="Describe your idea in detail. What problem does it solve? Who is it for? What technologies might be used?"
                              required oninput="document.getElementById('desc-len').textContent=this.value.length"><?= sanitize($_POST['description'] ?? '') ?></textarea>
                    <div class="char-counter"><span id="desc-len"><?= strlen($_POST['description'] ?? '') ?></span>/2000</div>
                </div>

                <div class="form-group">
                    <label class="form-label">🏷️ Category</label>
                    <div class="category-grid">
                        <?php
                        $cats = [
                            'Web'     => ['icon' => '🌐', 'label' => 'Web'],
                            'Mobile'  => ['icon' => '📱', 'label' => 'Mobile'],
                            'AI/ML'   => ['icon' => '🤖', 'label' => 'AI/ML'],
                            'Game'    => ['icon' => '🎮', 'label' => 'Game Dev'],
                            'Data'    => ['icon' => '📊', 'label' => 'Data'],
                            'DevOps'  => ['icon' => '🐳', 'label' => 'DevOps'],
                            'General' => ['icon' => '💡', 'label' => 'General'],
                        ];
                        $selCat = $_POST['category'] ?? 'General';
                        foreach($cats as $val => $c):
                        ?>
                        <input type="radio" name="category" id="cat-<?= $val ?>" value="<?= $val ?>" class="cat-radio"
                               <?= $selCat===$val?'checked':'' ?>>
                        <label for="cat-<?= $val ?>" class="cat-label">
                            <span class="cat-icon"><?= $c['icon'] ?></span>
                            <?= $c['label'] ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">🔖 Tags <span style="color:var(--text-muted);font-weight:400;">(optional)</span></label>
                    <input type="text" name="tags" class="form-input"
                           placeholder="e.g. ai, react, students, open-source"
                           value="<?= sanitize($_POST['tags'] ?? '') ?>">
                    <div class="tags-hint">Comma-separated tags to help others discover your idea.</div>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;padding:.85rem;font-size:1rem;margin-top:.5rem;">
                    🚀 Submit Idea
                </button>
            </form>
        </div>
    </div>

    <script>window.APP_BASE = '<?= BASE_URL ?>';</script>
    <script src="<?= BASE_URL ?>/assets/js/main.js"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/chatbot.css">
    <script>window.CHATBOT_CONTEXT={name:'<?= sanitize(explode(' ',$student['name'])[0]) ?>',page:'submit_idea'};</script>
    <script src="<?= BASE_URL ?>/assets/js/chatbot.js"></script>
</body>
</html>

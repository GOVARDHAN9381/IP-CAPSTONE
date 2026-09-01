<?php
session_start();
require_once __DIR__ . '/../config/db.php';
requireLogin();

$db        = getDB();
$studentId = $_SESSION['student_id'];
$student   = currentStudent();
$ideaId    = (int)($_GET['id'] ?? 0);
if (!$ideaId) { header('Location: '.BASE_URL.'/ideas/index.php'); exit; }

$stmt = $db->prepare("
    SELECT i.*, s.name AS author_name,
           (SELECT COUNT(*) FROM idea_votes v WHERE v.idea_id=i.id AND v.vote='up')   AS upvotes,
           (SELECT COUNT(*) FROM idea_votes v WHERE v.idea_id=i.id AND v.vote='down') AS downvotes,
           (SELECT vote FROM idea_votes WHERE idea_id=i.id AND student_id=?)          AS my_vote
    FROM ideas i JOIN students s ON s.id=i.submitted_by
    WHERE i.id=?
");
$stmt->execute([$studentId, $ideaId]);
$idea = $stmt->fetch();
if (!$idea) { header('Location: '.BASE_URL.'/ideas/index.php'); exit; }

$comments = $db->prepare("
    SELECT ic.*, s.name AS author_name FROM idea_comments ic
    JOIN students s ON s.id=ic.student_id
    WHERE ic.idea_id=? ORDER BY ic.created_at ASC
");
$comments->execute([$ideaId]);
$comments = $comments->fetchAll();

$isNew = !empty($_GET['new']);
$tags = $idea['tags'] ? array_filter(array_map('trim', explode(',', $idea['tags']))) : [];
$catIcons = ['Web'=>'🌐','Mobile'=>'📱','AI/ML'=>'🤖','Game'=>'🎮','Data'=>'📊','DevOps'=>'🐳','General'=>'💡'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($idea['title']) ?> — CollabIQ Ideas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dashboard.css">
    <style>
        .idea-view-wrap{max-width:780px;margin:2rem auto;padding:0 1.5rem 4rem;}
        .idea-header-card{background:linear-gradient(135deg,rgba(99,102,241,.12),rgba(6,182,212,.08));border:1px solid rgba(99,102,241,.25);border-radius:var(--radius-xl);padding:2rem;margin-bottom:1.5rem;}
        .idea-big-title{font-size:1.75rem;font-weight:800;line-height:1.3;margin:1rem 0;}
        .idea-desc-full{color:var(--text-secondary);line-height:1.8;white-space:pre-wrap;}
        .vote-section{display:flex;align-items:center;gap:1rem;padding:1.25rem 0;border-top:1px solid var(--border-subtle);border-bottom:1px solid var(--border-subtle);margin:1.5rem 0;}
        .big-vote-btn{display:flex;align-items:center;gap:.5rem;padding:.6rem 1.4rem;border-radius:99px;border:1.5px solid var(--border-default);background:transparent;color:var(--text-secondary);font-size:1rem;font-weight:700;cursor:pointer;transition:var(--transition);}
        .big-vote-btn:hover{border-color:var(--accent-indigo);}
        .big-vote-btn.voted-up{background:rgba(16,185,129,.15);border-color:#10b981;color:#10b981;}
        .big-vote-btn.voted-down{background:rgba(239,68,68,.15);border-color:#ef4444;color:#ef4444;}
        .vote-score{font-size:1.1rem;font-weight:800;color:var(--text-primary);}
        .comment-card{background:rgba(255,255,255,.03);border:1px solid var(--border-subtle);border-radius:var(--radius-md);padding:1rem 1.25rem;display:flex;gap:1rem;}
        .comment-avatar{width:36px;height:36px;border-radius:50%;flex-shrink:0;}
        .comment-name{font-weight:600;font-size:.9rem;}
        .comment-time{font-size:.75rem;color:var(--text-muted);}
        .comment-body{color:var(--text-secondary);font-size:.9rem;margin-top:.3rem;line-height:1.6;}
        .comment-input-wrap{display:flex;gap:.75rem;margin-top:1rem;}
        .comment-input{flex:1;background:var(--bg-tertiary);border:1px solid var(--border-default);border-radius:var(--radius-md);padding:.75rem 1rem;color:var(--text-primary);font-family:inherit;font-size:.9rem;resize:none;}
        .comment-input:focus{outline:none;border-color:var(--accent-indigo);}
        .convert-banner{background:linear-gradient(135deg,rgba(16,185,129,.15),rgba(6,182,212,.1));border:1px solid rgba(16,185,129,.3);border-radius:var(--radius-lg);padding:1.25rem 1.5rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem;}
    </style>
</head>
<body>
    <nav class="nav">
        <a href="<?= BASE_URL ?>/ideas/index.php" class="nav-brand" style="text-decoration:none;">🧠 Collab<span class="gradient-text">IQ</span></a>
        <div class="nav-user">
            <img src="<?= generateAvatar($student['name']) ?>" class="nav-avatar" alt="">
            <a href="<?= BASE_URL ?>/ideas/index.php" class="btn btn-ghost btn-sm">← Idea Board</a>
        </div>
    </nav>

    <div class="idea-view-wrap">
        <?php if($isNew): ?>
        <div class="alert alert-success" style="margin-bottom:1.5rem;">🎉 Your idea has been submitted! Share it with friends to get votes.</div>
        <?php endif; ?>

        <?php if($idea['submitted_by']==$studentId && $idea['status']==='open'): ?>
        <div class="convert-banner">
            <div>
                <div style="font-weight:700;color:var(--text-primary);">🚀 Ready to make it real?</div>
                <div style="color:var(--text-muted);font-size:.875rem;">Convert this idea into an actual project you can work on.</div>
            </div>
            <form method="post" action="<?= BASE_URL ?>/api/idea_convert.php" onsubmit="return confirm('This will create a new project from your idea. Continue?')">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="idea_id" value="<?= $idea['id'] ?>">
                <button type="submit" class="btn btn-primary">🚀 Convert to Project</button>
            </form>
        </div>
        <?php endif; ?>

        <div class="idea-header-card">
            <div style="display:flex;align-items:center;gap:.75rem;">
                <span style="font-size:1.5rem;"><?= $catIcons[$idea['category']] ?? '💡' ?></span>
                <span style="font-size:.8rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#a5b4fc;"><?= sanitize($idea['category']) ?></span>
                <?php if($idea['status']==='converted'): ?>
                <span class="badge badge-emerald" style="margin-left:auto;">🚀 Converted to Project</span>
                <?php endif; ?>
            </div>
            <h1 class="idea-big-title"><?= sanitize($idea['title']) ?></h1>
            <div class="idea-author" style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem;">
                <img src="<?= generateAvatar($idea['author_name']) ?>" style="width:32px;height:32px;border-radius:50%;" alt="">
                <span style="color:var(--text-muted);font-size:.875rem;">By <strong style="color:var(--text-primary);"><?= sanitize($idea['author_name']) ?></strong> · <?= date('F j, Y', strtotime($idea['created_at'])) ?></span>
            </div>
            <p class="idea-desc-full"><?= sanitize($idea['description']) ?></p>
            <?php if(!empty($tags)): ?>
            <div style="display:flex;flex-wrap:wrap;gap:.35rem;margin-top:1rem;">
                <?php foreach($tags as $tag): ?>
                <span style="font-size:.75rem;padding:.2rem .6rem;border-radius:99px;background:rgba(255,255,255,.06);border:1px solid var(--border-subtle);color:var(--text-muted);">#<?= sanitize($tag) ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Voting -->
        <div class="vote-section">
            <button class="big-vote-btn <?= $idea['my_vote']==='up'?'voted-up':'' ?>" id="up-btn" onclick="castVote('up')">
                👍 <span id="up-count"><?= $idea['upvotes'] ?></span>
            </button>
            <button class="big-vote-btn <?= $idea['my_vote']==='down'?'voted-down':'' ?>" id="down-btn" onclick="castVote('down')">
                👎 <span id="down-count"><?= $idea['downvotes'] ?></span>
            </button>
            <span class="vote-score" style="margin-left:auto;">
                Score: <span id="net-score" style="color:<?= ($idea['upvotes']-$idea['downvotes'])>=0?'#10b981':'#ef4444' ?>">
                    <?= ($idea['upvotes'] - $idea['downvotes']) >= 0 ? '+' : '' ?><?= $idea['upvotes'] - $idea['downvotes'] ?>
                </span>
            </span>
        </div>

        <!-- Comments -->
        <div class="data-card">
            <div class="data-card-header">
                <h3 class="data-card-title">💬 Discussion <span class="badge badge-cyan" style="margin-left:.5rem;"><?= count($comments) ?></span></h3>
            </div>
            <div class="data-card-body" id="comments-list">
                <?php if(empty($comments)): ?>
                <div style="text-align:center;padding:2rem;color:var(--text-muted);">Be the first to comment on this idea!</div>
                <?php endif; ?>
                <?php foreach($comments as $c): ?>
                <div class="comment-card" style="margin-bottom:.75rem;">
                    <img src="<?= generateAvatar($c['author_name']) ?>" class="comment-avatar" alt="">
                    <div style="flex:1;">
                        <div style="display:flex;align-items:center;gap:.75rem;">
                            <span class="comment-name"><?= sanitize($c['author_name']) ?></span>
                            <span class="comment-time"><?= date('M d, g:i A', strtotime($c['created_at'])) ?></span>
                        </div>
                        <div class="comment-body"><?= nl2br(sanitize($c['comment'])) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div style="padding:0 1.5rem 1.5rem;">
                <div class="comment-input-wrap">
                    <img src="<?= generateAvatar($student['name']) ?>" class="comment-avatar" alt="">
                    <textarea id="new-comment" class="comment-input" rows="2" placeholder="Share your thoughts on this idea..."></textarea>
                    <button class="btn btn-primary" onclick="postComment()">Send</button>
                </div>
            </div>
        </div>
    </div>

    <script>window.APP_BASE='<?= BASE_URL ?>';</script>
    <script src="<?= BASE_URL ?>/assets/js/main.js"></script>
    <script>
    const IDEA_ID = <?= $ideaId ?>;
    const CSRF    = '<?= csrfToken() ?>';

    function castVote(vote) {
        const fd = new FormData();
        fd.append('idea_id', IDEA_ID);
        fd.append('vote', vote);
        fd.append('csrf_token', CSRF);
        fetch(APP_BASE + '/api/idea_vote.php', {method:'POST',body:fd})
            .then(r => r.json()).then(d => {
                if (d.success) {
                    document.getElementById('up-count').textContent   = d.upvotes;
                    document.getElementById('down-count').textContent = d.downvotes;
                    const net = d.upvotes - d.downvotes;
                    const netEl = document.getElementById('net-score');
                    netEl.textContent = (net >= 0 ? '+' : '') + net;
                    netEl.style.color = net >= 0 ? '#10b981' : '#ef4444';
                    setTimeout(() => location.reload(), 400);
                }
            });
    }

    function postComment() {
        const txt = document.getElementById('new-comment').value.trim();
        if (txt.length < 2) return;
        const fd = new FormData();
        fd.append('idea_id', IDEA_ID);
        fd.append('comment', txt);
        fd.append('csrf_token', CSRF);
        fetch(APP_BASE + '/api/idea_comment.php', {method:'POST',body:fd})
            .then(r => r.json()).then(d => {
                if (d.success) {
                    const list = document.getElementById('comments-list');
                    const el = document.createElement('div');
                    el.className = 'comment-card';
                    el.style.marginBottom = '.75rem';
                    el.innerHTML = `<img src="<?= generateAvatar($student['name']) ?>" class="comment-avatar" alt="">
                    <div style="flex:1">
                        <div style="display:flex;align-items:center;gap:.75rem">
                            <span class="comment-name">${d.name}</span>
                            <span class="comment-time">Just now</span>
                        </div>
                        <div class="comment-body">${d.comment.replace(/\n/g,'<br>')}</div>
                    </div>`;
                    list.appendChild(el);
                    document.getElementById('new-comment').value = '';
                }
            });
    }
    </script>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/chatbot.css">
    <script>window.CHATBOT_CONTEXT={name:'<?= sanitize(explode(' ',$student['name'])[0]) ?>',page:'idea_view'};</script>
    <script src="<?= BASE_URL ?>/assets/js/chatbot.js"></script>
</body>
</html>

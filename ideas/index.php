<?php
session_start();
require_once __DIR__ . '/../config/db.php';
requireLogin();

$db        = getDB();
$studentId = $_SESSION['student_id'];
$student   = currentStudent();

$filter = $_GET['filter'] ?? 'all';

// Build query based on filter
$baseQuery = "
    SELECT i.*,
           s.name AS author_name,
           (SELECT COUNT(*) FROM idea_votes v WHERE v.idea_id=i.id AND v.vote='up')   AS upvotes,
           (SELECT COUNT(*) FROM idea_votes v WHERE v.idea_id=i.id AND v.vote='down') AS downvotes,
           (SELECT COUNT(*) FROM idea_comments c WHERE c.idea_id=i.id)                AS comment_count,
           (SELECT vote FROM idea_votes WHERE idea_id=i.id AND student_id=?)          AS my_vote
    FROM ideas i
    JOIN students s ON s.id = i.submitted_by
";

if ($filter === 'mine') {
    $stmt = $db->prepare($baseQuery . " WHERE i.submitted_by=? ORDER BY i.created_at DESC");
    $stmt->execute([$studentId, $studentId]);
} elseif ($filter === 'trending') {
    $stmt = $db->prepare($baseQuery . " ORDER BY (upvotes - downvotes) DESC, i.created_at DESC");
    $stmt->execute([$studentId]);
} else {
    $stmt = $db->prepare($baseQuery . " ORDER BY i.created_at DESC");
    $stmt->execute([$studentId]);
}
$ideas = $stmt->fetchAll();

$categories = ['All', 'Web', 'Mobile', 'AI/ML', 'Game', 'Data', 'DevOps', 'General'];
$catFilter  = $_GET['cat'] ?? 'All';
if ($catFilter !== 'All') {
    $ideas = array_filter($ideas, fn($i) => $i['category'] === $catFilter);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idea Board — CollabIQ</title>
    <meta name="description" content="Browse, vote and submit project ideas on CollabIQ's Idea Board.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dashboard.css">
    <style>
        .ideas-hero{background:linear-gradient(135deg,rgba(99,102,241,.15),rgba(6,182,212,.1));border:1px solid rgba(99,102,241,.2);border-radius:var(--radius-lg);padding:2rem;margin-bottom:2rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;}
        .ideas-hero h2{font-size:1.6rem;font-weight:800;margin-bottom:.25rem;}
        .filter-tabs{display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.5rem;}
        .filter-tab{padding:.45rem 1.1rem;border-radius:99px;border:1px solid var(--border-default);color:var(--text-muted);font-size:.85rem;font-weight:500;cursor:pointer;text-decoration:none;transition:var(--transition);}
        .filter-tab:hover,.filter-tab.active{background:var(--accent-indigo);border-color:var(--accent-indigo);color:#fff;}
        .ideas-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:1.25rem;}
        .idea-card{background:var(--bg-card);border:1px solid var(--border-subtle);border-radius:var(--radius-lg);padding:1.5rem;transition:var(--transition);display:flex;flex-direction:column;gap:1rem;}
        .idea-card:hover{border-color:rgba(99,102,241,.4);transform:translateY(-3px);box-shadow:var(--shadow-md);}
        .idea-cat{display:inline-flex;align-items:center;gap:.35rem;font-size:.75rem;font-weight:600;letter-spacing:.04em;text-transform:uppercase;padding:.2rem .65rem;border-radius:99px;background:rgba(99,102,241,.15);color:#a5b4fc;width:fit-content;}
        .idea-title{font-size:1.05rem;font-weight:700;color:var(--text-primary);line-height:1.4;}
        .idea-desc{color:var(--text-muted);font-size:.875rem;line-height:1.6;flex:1;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;}
        .idea-author{display:flex;align-items:center;gap:.6rem;font-size:.8rem;color:var(--text-muted);}
        .idea-author img{width:24px;height:24px;border-radius:50%;}
        .idea-actions{display:flex;align-items:center;gap:.75rem;padding-top:.75rem;border-top:1px solid var(--border-subtle);}
        .vote-btn{display:flex;align-items:center;gap:.35rem;padding:.35rem .8rem;border-radius:99px;border:1px solid var(--border-default);background:transparent;color:var(--text-muted);font-size:.85rem;font-weight:600;cursor:pointer;transition:var(--transition);}
        .vote-btn:hover{border-color:var(--accent-indigo);color:var(--accent-indigo);}
        .vote-btn.voted-up{background:rgba(16,185,129,.15);border-color:#10b981;color:#10b981;}
        .vote-btn.voted-down{background:rgba(239,68,68,.15);border-color:#ef4444;color:#ef4444;}
        .idea-tags{display:flex;flex-wrap:wrap;gap:.3rem;}
        .idea-tag{font-size:.72rem;padding:.15rem .5rem;border-radius:99px;background:rgba(255,255,255,.05);border:1px solid var(--border-subtle);color:var(--text-muted);}
        .idea-status-converted{opacity:.6;}
        .empty-ideas{text-align:center;padding:4rem 2rem;color:var(--text-muted);}
        .empty-ideas .big-emoji{font-size:4rem;margin-bottom:1rem;}
    </style>
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
                <a href="<?= BASE_URL ?>/student/activity.php" class="sidebar-link"><span class="icon">📊</span> Activity Feed</a>
                <a href="<?= BASE_URL ?>/ideas/index.php" class="sidebar-link active"><span class="icon">💡</span> Idea Board</a>
            </div>
            <div class="sidebar-section">
                <div class="sidebar-label">Projects</div>
                <a href="<?= BASE_URL ?>/project/create.php" class="sidebar-link"><span class="icon">➕</span> New Project</a>
            </div>
        </aside>

        <main class="main-content">
            <div class="ideas-hero">
                <div>
                    <h2>💡 Idea Board</h2>
                    <p style="color:var(--text-muted);margin:0;">Pitch ideas, vote on them, and turn the best ones into real projects.</p>
                </div>
                <a href="<?= BASE_URL ?>/ideas/submit.php" class="btn btn-primary">✨ Submit Idea</a>
            </div>

            <!-- Filters -->
            <div class="filter-tabs">
                <a href="?filter=all" class="filter-tab <?= $filter==='all'?'active':'' ?>">🌐 All Ideas</a>
                <a href="?filter=trending" class="filter-tab <?= $filter==='trending'?'active':'' ?>">🔥 Trending</a>
                <a href="?filter=mine" class="filter-tab <?= $filter==='mine'?'active':'' ?>">👤 My Ideas</a>
            </div>
            <div class="filter-tabs" style="margin-top:-.75rem;">
                <?php foreach($categories as $cat): ?>
                <a href="?filter=<?= $filter ?>&cat=<?= urlencode($cat) ?>"
                   class="filter-tab <?= $catFilter===$cat?'active':'' ?>"><?= $cat ?></a>
                <?php endforeach; ?>
            </div>

            <!-- Ideas Grid -->
            <?php if(empty($ideas)): ?>
            <div class="empty-ideas">
                <div class="big-emoji">💡</div>
                <h3 style="color:var(--text-primary);margin-bottom:.5rem;">No ideas yet</h3>
                <p>Be the first to pitch a project idea!</p>
                <a href="<?= BASE_URL ?>/ideas/submit.php" class="btn btn-primary" style="margin-top:1rem;">Submit First Idea</a>
            </div>
            <?php else: ?>
            <div class="ideas-grid">
                <?php foreach($ideas as $idea):
                    $tags = $idea['tags'] ? array_filter(array_map('trim', explode(',', $idea['tags']))) : [];
                    $isConverted = $idea['status'] === 'converted';
                ?>
                <div class="idea-card <?= $isConverted ? 'idea-status-converted' : '' ?>" id="idea-<?= $idea['id'] ?>">
                    <div>
                        <div class="idea-cat">
                            <?php $catIcons = ['Web'=>'🌐','Mobile'=>'📱','AI/ML'=>'🤖','Game'=>'🎮','Data'=>'📊','DevOps'=>'🐳','General'=>'💡'];
                            echo ($catIcons[$idea['category']] ?? '💡') . ' ' . sanitize($idea['category']); ?>
                        </div>
                    </div>
                    <div>
                        <a href="<?= BASE_URL ?>/ideas/view.php?id=<?= $idea['id'] ?>" style="text-decoration:none;">
                            <div class="idea-title"><?= sanitize($idea['title']) ?></div>
                        </a>
                        <p class="idea-desc" style="margin-top:.4rem;"><?= sanitize($idea['description']) ?></p>
                    </div>
                    <?php if(!empty($tags)): ?>
                    <div class="idea-tags">
                        <?php foreach(array_slice($tags,0,4) as $tag): ?>
                        <span class="idea-tag">#<?= sanitize($tag) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <div class="idea-author">
                        <img src="<?= generateAvatar($idea['author_name']) ?>" alt="">
                        <span><?= sanitize($idea['author_name']) ?> · <?= date('M d', strtotime($idea['created_at'])) ?></span>
                        <?php if($isConverted): ?>
                        <span class="badge badge-emerald" style="margin-left:auto;">🚀 Converted</span>
                        <?php endif; ?>
                    </div>
                    <div class="idea-actions">
                        <button class="vote-btn <?= $idea['my_vote']==='up'?'voted-up':'' ?>"
                                onclick="voteIdea(<?= $idea['id'] ?>,'up',this)"
                                data-up="<?= $idea['upvotes'] ?>" data-down="<?= $idea['downvotes'] ?>">
                            👍 <span class="up-count"><?= $idea['upvotes'] ?></span>
                        </button>
                        <button class="vote-btn <?= $idea['my_vote']==='down'?'voted-down':'' ?>"
                                onclick="voteIdea(<?= $idea['id'] ?>,'down',this)">
                            👎 <span class="down-count"><?= $idea['downvotes'] ?></span>
                        </button>
                        <a href="<?= BASE_URL ?>/ideas/view.php?id=<?= $idea['id'] ?>" class="vote-btn" style="text-decoration:none;">
                            💬 <?= $idea['comment_count'] ?>
                        </a>
                        <?php if($idea['submitted_by'] == $studentId && !$isConverted): ?>
                        <form method="post" action="<?= BASE_URL ?>/api/idea_convert.php" style="margin-left:auto;" onsubmit="return confirm('Convert this idea into a real project?')">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="idea_id" value="<?= $idea['id'] ?>">
                            <button type="submit" class="btn btn-primary btn-sm">🚀 Launch</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <script>window.APP_BASE = '<?= BASE_URL ?>';</script>
    <script src="<?= BASE_URL ?>/assets/js/main.js"></script>
    <script>
    const CSRF = '<?= csrfToken() ?>';
    function voteIdea(ideaId, vote, btn) {
        const card = document.getElementById('idea-' + ideaId);
        const upBtn = card.querySelector('.vote-btn:first-child');
        const downBtn = card.querySelector('.vote-btn:nth-child(2)');
        const fd = new FormData();
        fd.append('idea_id', ideaId);
        fd.append('vote', vote);
        fd.append('csrf_token', CSRF);
        fetch(APP_BASE + '/api/idea_vote.php', { method:'POST', body: fd })
            .then(r => r.json()).then(d => {
                if (d.success) {
                    card.querySelector('.up-count').textContent = d.upvotes;
                    card.querySelector('.down-count').textContent = d.downvotes;
                    upBtn.classList.toggle('voted-up', vote === 'up' && upBtn.classList.contains('voted-up') ? false : vote === 'up');
                    downBtn.classList.toggle('voted-down', vote === 'down' && downBtn.classList.contains('voted-down') ? false : vote === 'down');
                    // Reload to properly reflect toggle state
                    setTimeout(() => location.reload(), 300);
                }
            });
    }
    </script>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/chatbot.css">
    <script>window.CHATBOT_CONTEXT={name:'<?= sanitize(explode(' ',$student['name'])[0]) ?>',page:'ideas'};</script>
    <script src="<?= BASE_URL ?>/assets/js/chatbot.js"></script>
</body>
</html>

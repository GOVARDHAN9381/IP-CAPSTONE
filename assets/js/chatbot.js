/* ============================================================
   CollabIQ — CollabBot (AI Chatbot)
   Rule-based context-aware assistant
   ============================================================ */
(function() {
    'use strict';

    const ctx  = window.CHATBOT_CONTEXT || {};
    const BASE = window.APP_BASE || '';
    const name = ctx.name || 'there';

    // ── Knowledge Base ─────────────────────────────────────────
    const KB = [
        // Greetings
        {
            patterns: ['hi','hello','hey','sup','what\'s up','greetings','hola'],
            responses: [
                `Hey ${name}! 👋 I'm CollabBot, your AI assistant on CollabIQ. How can I help you today?`,
                `Hello ${name}! 🤖 What can I do for you? Type *help* to see what I know.`,
            ]
        },
        // Help
        {
            patterns: ['help','what can you do','commands','options','menu'],
            response: `Here's what I can help with:\n\n📋 **Projects** — "my projects", "create project"\n✅ **Tasks** — "my tasks", "pending tasks"\n🤖 **Recommendations** — "find teammates", "ai match"\n💡 **Ideas** — "idea board", "submit idea"\n📊 **Activity** — "activity feed"\n🔔 **Notifications** — "my notifications"\n👤 **Profile** — "my profile", "my score"\n\nJust type naturally — I'll understand!`
        },
        // Projects
        {
            patterns: ['my project','projects','what project','show project'],
            response: `Your projects are on your <a href="${BASE}/student/dashboard.php">Dashboard</a>. You can also <a href="${BASE}/project/create.php">create a new project</a> any time! 🚀`,
        },
        {
            patterns: ['create project','new project','start project','make project'],
            response: `Ready to start something new? 🚀 Go to <a href="${BASE}/project/create.php">Create Project</a> — you can add teammates, set a deadline, and assign tasks right away!`
        },
        // Tasks
        {
            patterns: ['my task','pending task','tasks due','what task','task list'],
            response: `Check your pending tasks on your <a href="${BASE}/student/dashboard.php">Dashboard</a> — they're sorted by due date. Complete tasks to boost your Collaboration Score! ✅`
        },
        {
            patterns: ['complete task','mark done','finish task'],
            response: `To mark a task complete: open the project → click the status button on the task row → select "Completed". Your score will update automatically! 🎉`
        },
        // AI Recommendations
        {
            patterns: ['recommend','teammate','find team','ai match','team match','suggestion','who should i work with'],
            response: `Our AI recommendation engine analyzes your skills and interests to suggest the best teammates! Visit <a href="${BASE}/student/recommendations.php">AI Recommendations</a> to see your top matches with match scores and star ratings. ⭐`
        },
        {
            patterns: ['how does recommendation work','algorithm','how ai works','scoring'],
            response: `The AI scoring formula:\n\n🎯 **Shared Skills** → ×2 weight\n🔀 **Complementary Skills** → ×1 weight\n❤️ **Shared Interests** → ×1.5 weight\n\nFinal score is a % (0-100), converted to 1-5 stars. Higher = better match!`
        },
        // Idea Board
        {
            patterns: ['idea','idea board','ideas','submit idea','pitch idea','project idea'],
            response: `The 💡 <a href="${BASE}/ideas/index.php">Idea Board</a> is where students pitch project concepts. You can:\n\n- Browse all ideas\n- Vote up/down on ideas\n- Comment and discuss\n- Convert your own idea into a real project!\n\nGo <a href="${BASE}/ideas/submit.php">submit an idea</a> now!`
        },
        {
            patterns: ['convert idea','launch idea','idea to project'],
            response: `To convert an idea to a project: go to the <a href="${BASE}/ideas/index.php">Idea Board</a> → open your idea → click the "🚀 Convert to Project" button. You'll become the project leader automatically!`
        },
        // Activity Feed
        {
            patterns: ['activity','feed','timeline','what happened','recent action','log'],
            response: `The 📊 <a href="${BASE}/student/activity.php">Activity Feed</a> shows everything happening across your projects — task completions, comments, file uploads, milestones, and more. It's your project heartbeat!`
        },
        // Notifications
        {
            patterns: ['notification','alert','bell','unread'],
            response: `You'll see a 🔔 bell icon in the navbar showing unread notifications. Click it to see alerts for: task completions, new comments, idea votes, and milestone achievements!`
        },
        // Profile & Score
        {
            patterns: ['my profile','profile','about me','my info'],
            response: `View your profile at <a href="${BASE}/student/profile.php">My Profile</a>. You can see your skills, interests, GitHub/LinkedIn links, and collaboration score ring. Edit anytime at <a href="${BASE}/student/edit_profile.php">Edit Profile</a>!`
        },
        {
            patterns: ['collaboration score','my score','score','collab score','how score works'],
            response: `Your Collaboration Score (0-100) is calculated as:\n\n✅ Completed Tasks × 10\n💬 Comments Posted × 3\n📁 Files Uploaded × 5\n\nMax: 100. Faculty can see your score on their analytics dashboard!`
        },
        {
            patterns: ['edit profile','update profile','change skill','add skill'],
            response: `You can update your skills, interests, bio, GitHub, and LinkedIn at <a href="${BASE}/student/edit_profile.php">Edit Profile</a>. Better skills = better AI recommendations! ⚡`
        },
        // Milestones
        {
            patterns: ['milestone','checkpoint','goal','project goal'],
            response: `Project Milestones are key checkpoints in your project timeline! Open any project → go to the **Milestones** tab → add milestones with a target date. Mark them complete to notify your whole team! 🏆`
        },
        // File upload
        {
            patterns: ['upload','file','share file','attach'],
            response: `Upload project files in the project workspace → **Files** tab. Drag & drop or click to browse. Max size: 10 MB per file. Your team can download them anytime! 📁`
        },
        // Chat/Discussion
        {
            patterns: ['chat','discussion','comment','message team','talk to team'],
            response: `Use the **Discussion** tab inside any project to chat with your team. Messages are posted instantly via AJAX — no page reload needed! 💬`
        },
        // Faculty
        {
            patterns: ['faculty','teacher','professor','analytics','report'],
            response: `Faculty have a separate dashboard with:\n- 📊 Bar chart of all student collaboration scores\n- 🍩 Task distribution doughnut chart\n- 🤖 AI insights (top performer, struggling students)\n- 📄 Printable student reports\n\nFaculty log in with their own credentials.`
        },
        // Logout
        {
            patterns: ['logout','sign out','log out'],
            response: `You can sign out from the top-right navigation bar. See you next time, ${name}! 👋`
        },
        // Goodbye
        {
            patterns: ['bye','goodbye','see you','thanks','thank you','thx'],
            responses: [
                `Glad I could help, ${name}! 🚀 Good luck with your projects!`,
                `Anytime, ${name}! Keep collaborating! 💪`,
                `You're welcome! Come back if you need anything. 🤖`
            ]
        },
        // Who are you
        {
            patterns: ['who are you','what are you','your name','about you','are you ai','are you a bot'],
            response: `I'm **CollabBot** 🤖 — your AI assistant built into CollabIQ!\n\nI know about your projects, tasks, and all platform features. I'm rule-based (no external API needed), but quite smart about CollabIQ! Type *help* to see what I can do.`
        },
        // Jokes
        {
            patterns: ['tell me a joke','joke','funny'],
            responses: [
                `Why do programmers prefer dark mode? 🌙\n\nBecause **light attracts bugs!** 🐛`,
                `A SQL query walks into a bar, walks up to two tables and asks... "Can I join you?" 😄`,
                `Why did the developer go broke? 💸\n\nBecause they used up all their **cache**! 🗄️`
            ]
        },
    ];

    const FALLBACKS = [
        `I'm not sure about that, ${name}. Try typing *help* to see what I can do! 🤖`,
        `Hmm, I didn't quite catch that. Type *help* for a list of things I can help with!`,
        `I'm still learning! For now, try asking about projects, tasks, teammates, or ideas. 💡`,
    ];

    const QUICK_REPLIES = ['My tasks 📋', 'Find teammates 🤖', 'Idea board 💡', 'My score ⭐', 'Help ❓'];

    // ── DOM Builder ────────────────────────────────────────────
    function buildUI() {
        // Bubble
        const bubble = document.createElement('button');
        bubble.className = 'chatbot-bubble';
        bubble.id = 'cb-bubble';
        bubble.setAttribute('aria-label', 'Open CollabBot');
        bubble.innerHTML = `🤖<span class="cb-notif" id="cb-notif"></span>`;
        bubble.onclick = toggleChat;
        document.body.appendChild(bubble);

        // Window
        const win = document.createElement('div');
        win.className = 'chatbot-window';
        win.id = 'cb-window';
        win.innerHTML = `
        <div class="cb-header">
            <div class="cb-avatar">🤖</div>
            <div>
                <div class="cb-title">CollabBot</div>
                <div class="cb-subtitle">AI Assistant · CollabIQ</div>
            </div>
            <div class="cb-status-dot"></div>
            <button class="cb-close" onclick="document.getElementById('cb-window').classList.remove('open')" aria-label="Close">✕</button>
        </div>
        <div class="cb-messages" id="cb-messages"></div>
        <div class="cb-quick-replies" id="cb-quick"></div>
        <div class="cb-input-wrap">
            <textarea class="cb-input" id="cb-input" placeholder="Ask me anything…" rows="1"
                onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendChat();}"
                oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,100)+'px'"></textarea>
            <button class="cb-send" onclick="sendChat()" aria-label="Send">➤</button>
        </div>`;
        document.body.appendChild(win);

        // Quick replies
        renderQuickReplies();

        // Welcome message after short delay
        setTimeout(() => addBotMessage(`Hey ${name}! 👋 I'm **CollabBot**, your AI assistant. How can I help you today? Type *help* to see what I know!`), 800);
    }

    function renderQuickReplies() {
        const qc = document.getElementById('cb-quick');
        if (!qc) return;
        qc.innerHTML = '';
        QUICK_REPLIES.forEach(q => {
            const btn = document.createElement('button');
            btn.className = 'cb-quick-btn';
            btn.textContent = q;
            btn.onclick = () => handleInput(q);
            qc.appendChild(btn);
        });
    }

    // ── Chat Logic ─────────────────────────────────────────────
    function toggleChat() {
        const win = document.getElementById('cb-window');
        win.classList.toggle('open');
        if (win.classList.contains('open')) {
            setTimeout(() => document.getElementById('cb-input')?.focus(), 300);
            document.getElementById('cb-notif').style.display = 'none';
        }
    }

    window.sendChat = function() {
        const input = document.getElementById('cb-input');
        const txt = input.value.trim();
        if (!txt) return;
        input.value = '';
        input.style.height = 'auto';
        handleInput(txt);
    };

    function handleInput(txt) {
        addUserMessage(txt);
        showTyping();
        setTimeout(() => {
            removeTyping();
            const response = getResponse(txt);
            addBotMessage(response);
        }, 600 + Math.random() * 400);
    }

    function getResponse(input) {
        const lower = input.toLowerCase().trim();
        for (const rule of KB) {
            const pats = rule.patterns;
            if (pats.some(p => lower.includes(p))) {
                if (rule.responses) return rule.responses[Math.floor(Math.random() * rule.responses.length)];
                return rule.response;
            }
        }
        return FALLBACKS[Math.floor(Math.random() * FALLBACKS.length)];
    }

    function addUserMessage(txt) {
        const msgs = document.getElementById('cb-messages');
        const el = document.createElement('div');
        el.className = 'cb-msg user';
        el.innerHTML = `
            <div class="cb-msg-avatar">👤</div>
            <div class="cb-bubble">${escapeHtml(txt)}</div>`;
        msgs.appendChild(el);
        scrollBottom();
    }

    function addBotMessage(txt) {
        const msgs = document.getElementById('cb-messages');
        const el = document.createElement('div');
        el.className = 'cb-msg';
        el.innerHTML = `
            <div class="cb-msg-avatar">🤖</div>
            <div class="cb-bubble">${formatMarkdown(txt)}</div>`;
        msgs.appendChild(el);
        scrollBottom();
    }

    function showTyping() {
        const msgs = document.getElementById('cb-messages');
        const el = document.createElement('div');
        el.className = 'cb-msg';
        el.id = 'cb-typing-indicator';
        el.innerHTML = `<div class="cb-msg-avatar">🤖</div>
            <div class="cb-bubble cb-typing"><span></span><span></span><span></span></div>`;
        msgs.appendChild(el);
        scrollBottom();
    }

    function removeTyping() {
        document.getElementById('cb-typing-indicator')?.remove();
    }

    function scrollBottom() {
        const msgs = document.getElementById('cb-messages');
        if (msgs) msgs.scrollTop = msgs.scrollHeight;
    }

    // ── Markdown-lite formatter ─────────────────────────────────
    function formatMarkdown(txt) {
        return txt
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/`(.*?)`/g, '<code style="background:rgba(255,255,255,.1);padding:.1rem .3rem;border-radius:.25rem;">$1</code>')
            .replace(/\n/g, '<br>')
            .replace(/^- (.+)$/gm, '<li>$1</li>')
            .replace(/<li>/g, '<ul><li>').replace(/<\/li>/g, '</li></ul>');
    }

    function escapeHtml(s) {
        return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');
    }

    // ── Init ───────────────────────────────────────────────────
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', buildUI);
    } else {
        buildUI();
    }
})();

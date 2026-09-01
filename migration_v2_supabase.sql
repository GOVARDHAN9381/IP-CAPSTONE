-- ============================================================
-- CollabIQ v2 — Supabase PostgreSQL Migration
-- Run this in: Supabase Dashboard → SQL Editor → New Query
-- Safe to re-run: uses CREATE TABLE IF NOT EXISTS
-- ============================================================

-- ── activity_log ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS activity_log (
    id          SERIAL PRIMARY KEY,
    student_id  INT,
    project_id  INT,
    action_type VARCHAR(60)  NOT NULL,
    detail      TEXT,
    icon        VARCHAR(10)  DEFAULT '📋',
    created_at  TIMESTAMPTZ  DEFAULT NOW(),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id)  ON DELETE CASCADE
);

-- ── notifications ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS notifications (
    id          SERIAL PRIMARY KEY,
    student_id  INT          NOT NULL,
    type        VARCHAR(60)  NOT NULL,
    message     TEXT,
    link        VARCHAR(300),
    is_read     SMALLINT     DEFAULT 0,
    created_at  TIMESTAMPTZ  DEFAULT NOW(),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- ── ideas ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS ideas (
    id           SERIAL PRIMARY KEY,
    title        VARCHAR(200) NOT NULL,
    description  TEXT,
    category     VARCHAR(100),
    submitted_by INT,
    status       TEXT         DEFAULT 'open',   -- open | approved | rejected | converted
    upvotes      INT          DEFAULT 0,
    downvotes    INT          DEFAULT 0,
    created_at   TIMESTAMPTZ  DEFAULT NOW(),
    FOREIGN KEY (submitted_by) REFERENCES students(id) ON DELETE SET NULL
);

-- ── idea_votes ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS idea_votes (
    idea_id    INT  NOT NULL,
    student_id INT  NOT NULL,
    vote       TEXT NOT NULL,    -- 'up' | 'down'
    PRIMARY KEY (idea_id, student_id),
    FOREIGN KEY (idea_id)    REFERENCES ideas(id)    ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- ── idea_comments ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS idea_comments (
    id         SERIAL PRIMARY KEY,
    idea_id    INT  NOT NULL,
    student_id INT  NOT NULL,
    comment    TEXT NOT NULL,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    FOREIGN KEY (idea_id)    REFERENCES ideas(id)    ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- ── milestones ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS milestones (
    id          SERIAL PRIMARY KEY,
    project_id  INT          NOT NULL,
    title       VARCHAR(200) NOT NULL,
    description TEXT,
    target_date DATE,
    status      TEXT         DEFAULT 'upcoming',  -- upcoming | in_progress | completed
    created_at  TIMESTAMPTZ  DEFAULT NOW(),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- ── Verify ───────────────────────────────────────────────────
SELECT table_name
FROM information_schema.tables
WHERE table_schema = 'public'
  AND table_name IN ('activity_log','notifications','ideas','idea_votes','idea_comments','milestones')
ORDER BY table_name;

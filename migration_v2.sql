-- ============================================================
-- CollabIQ v2 — Feature Migration
-- Run this AFTER the original database.sql
-- Safe to re-run: all statements use IF NOT EXISTS / IGNORE
-- ============================================================

-- ============================================================
-- TABLE: activity_log
-- Tracks all significant actions across the platform
-- ============================================================
CREATE TABLE IF NOT EXISTS activity_log (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    student_id  INT,
    project_id  INT,
    action_type VARCHAR(60)  NOT NULL,   -- e.g. task_completed, comment_posted, file_uploaded
    detail      TEXT,
    icon        VARCHAR(10)  DEFAULT '📌',
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id)  ON DELETE CASCADE
);

-- ============================================================
-- TABLE: notifications
-- Per-student notification inbox
-- ============================================================
CREATE TABLE IF NOT EXISTS notifications (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    student_id  INT NOT NULL,
    type        VARCHAR(60)  NOT NULL,  -- task_assigned, comment, idea_voted, milestone_done
    message     TEXT         NOT NULL,
    link        VARCHAR(400),
    is_read     TINYINT(1)   DEFAULT 0,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- ============================================================
-- TABLE: ideas
-- Student-submitted project idea proposals
-- ============================================================
CREATE TABLE IF NOT EXISTS ideas (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    title        VARCHAR(200) NOT NULL,
    description  TEXT         NOT NULL,
    category     VARCHAR(100) DEFAULT 'General',
    tags         VARCHAR(300),
    submitted_by INT          NOT NULL,
    status       ENUM('open','approved','rejected','converted') DEFAULT 'open',
    created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (submitted_by) REFERENCES students(id) ON DELETE CASCADE
);

-- ============================================================
-- TABLE: idea_votes
-- One vote (up/down) per student per idea
-- ============================================================
CREATE TABLE IF NOT EXISTS idea_votes (
    idea_id    INT  NOT NULL,
    student_id INT  NOT NULL,
    vote       ENUM('up','down') NOT NULL,
    voted_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (idea_id, student_id),
    FOREIGN KEY (idea_id)    REFERENCES ideas(id)    ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- ============================================================
-- TABLE: idea_comments
-- Discussion on a specific idea
-- ============================================================
CREATE TABLE IF NOT EXISTS idea_comments (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    idea_id    INT  NOT NULL,
    student_id INT  NOT NULL,
    comment    TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (idea_id)    REFERENCES ideas(id)    ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- ============================================================
-- TABLE: milestones
-- Project-level milestone checkpoints with visual timeline
-- ============================================================
CREATE TABLE IF NOT EXISTS milestones (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    project_id  INT          NOT NULL,
    title       VARCHAR(200) NOT NULL,
    description TEXT,
    target_date DATE,
    status      ENUM('upcoming','in_progress','completed') DEFAULT 'upcoming',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- ============================================================
-- SEED: Sample ideas (optional demo data)
-- ============================================================
INSERT IGNORE INTO ideas (id, title, description, category, tags, submitted_by, status) VALUES
(1, 'AI Resume Builder for Students', 'A tool that auto-generates ATS-friendly resumes from a student skill profile and GitHub projects.', 'AI/ML', 'ai,resume,students', 1, 'open'),
(2, 'Campus Event Finder App', 'Mobile app that aggregates all campus events, club activities and deadlines in one feed.', 'Mobile', 'mobile,campus,events', 2, 'open'),
(3, 'Peer Code Review Platform', 'Platform for students to submit code and get structured peer reviews with rubrics.', 'Web', 'web,code-review,peer', 1, 'open');

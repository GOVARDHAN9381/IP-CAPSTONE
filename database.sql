-- ============================================================
-- AI-Powered Student Collaboration Intelligence Platform
-- Database Schema & Seed Data
-- Import this file into phpMyAdmin or run: mysql -u root < database.sql
-- NOTE: All INSERTs use INSERT IGNORE for safe re-running
-- ============================================================

CREATE DATABASE IF NOT EXISTS ipcapstone_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ipcapstone_db;

-- ============================================================
-- TABLE: faculty
-- ============================================================
CREATE TABLE IF NOT EXISTS faculty (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    department VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- TABLE: students
-- ============================================================
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    department VARCHAR(100),
    year ENUM('1st','2nd','3rd','4th') DEFAULT '1st',
    bio TEXT,
    github_url VARCHAR(255),
    linkedin_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- TABLE: skills
-- ============================================================
CREATE TABLE IF NOT EXISTS skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(100),
    icon VARCHAR(10) DEFAULT '⚙️'
);

-- ============================================================
-- TABLE: interests
-- ============================================================
CREATE TABLE IF NOT EXISTS interests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(10) DEFAULT '🎯'
);

-- ============================================================
-- TABLE: student_skills
-- ============================================================
CREATE TABLE IF NOT EXISTS student_skills (
    student_id INT NOT NULL,
    skill_id INT NOT NULL,
    PRIMARY KEY (student_id, skill_id),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
);

-- ============================================================
-- TABLE: student_interests
-- ============================================================
CREATE TABLE IF NOT EXISTS student_interests (
    student_id INT NOT NULL,
    interest_id INT NOT NULL,
    PRIMARY KEY (student_id, interest_id),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (interest_id) REFERENCES interests(id) ON DELETE CASCADE
);

-- ============================================================
-- TABLE: projects
-- ============================================================
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    leader_id INT,
    faculty_id INT,
    deadline DATE,
    status ENUM('planning','active','completed') DEFAULT 'planning',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (leader_id) REFERENCES students(id) ON DELETE SET NULL,
    FOREIGN KEY (faculty_id) REFERENCES faculty(id) ON DELETE SET NULL
);

-- ============================================================
-- TABLE: project_members
-- ============================================================
CREATE TABLE IF NOT EXISTS project_members (
    project_id INT NOT NULL,
    student_id INT NOT NULL,
    role ENUM('leader','member') DEFAULT 'member',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (project_id, student_id),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- ============================================================
-- TABLE: tasks
-- ============================================================
CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    assigned_to INT,
    status ENUM('pending','in_progress','completed') DEFAULT 'pending',
    priority ENUM('low','medium','high') DEFAULT 'medium',
    due_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES students(id) ON DELETE SET NULL
);

-- ============================================================
-- TABLE: comments
-- ============================================================
CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    student_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- ============================================================
-- TABLE: uploads
-- ============================================================
CREATE TABLE IF NOT EXISTS uploads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    student_id INT NOT NULL,
    original_name VARCHAR(255),
    filepath VARCHAR(500),
    filesize INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- ============================================================
-- SEED DATA: Skills
-- ============================================================
INSERT IGNORE INTO skills (name, category, icon) VALUES
('Python', 'Programming', '🐍'),
('Java', 'Programming', '☕'),
('C++', 'Programming', '⚡'),
('JavaScript', 'Web', '🌐'),
('HTML', 'Web', '🏗️'),
('CSS', 'Web', '🎨'),
('React', 'Web Framework', '⚛️'),
('Node.js', 'Backend', '🟢'),
('PHP', 'Backend', '🐘'),
('SQL / MySQL', 'Database', '🗄️'),
('MongoDB', 'Database', '🍃'),
('Machine Learning', 'AI/ML', '🤖'),
('Deep Learning', 'AI/ML', '🧠'),
('Data Science', 'Data', '📊'),
('Android Dev', 'Mobile', '📱'),
('iOS Dev', 'Mobile', '🍎'),
('Flutter', 'Mobile', '💙'),
('Docker', 'DevOps', '🐳'),
('Git / GitHub', 'Tools', '🔧'),
('UI/UX Design', 'Design', '🎭'),
('Cybersecurity', 'Security', '🔒'),
('Cloud (AWS/GCP)', 'Cloud', '☁️'),
('Blockchain', 'Emerging Tech', '⛓️'),
('IoT', 'Embedded', '📡');

-- ============================================================
-- SEED DATA: Interests
-- ============================================================
INSERT IGNORE INTO interests (name, icon) VALUES
('Web Development', '🌐'),
('Mobile App Development', '📱'),
('Artificial Intelligence', '🤖'),
('Data Science & Analytics', '📊'),
('Cybersecurity', '🔒'),
('Game Development', '🎮'),
('Open Source Contribution', '🤝'),
('Competitive Programming', '🏆'),
('UI/UX Design', '🎨'),
('Cloud Computing', '☁️'),
('Blockchain Technology', '⛓️'),
('Robotics & IoT', '🤖'),
('Research & Innovation', '🔬'),
('Entrepreneurship & Startups', '🚀');

-- ============================================================
-- SEED DATA: Faculty (password: faculty123)
-- ============================================================
INSERT IGNORE INTO faculty (name, email, password, department) VALUES
('Dr. A. Sharma', 'faculty@ipcapstone.edu', '$2y$10$AuCvlfKn8ji2Erf69fWtx.kM7obyJHAGl6KY9xjz0AQzdHhhWhFoy', 'Computer Science');

-- ============================================================
-- SEED DATA: Students (password: student123 for all)
-- ============================================================
INSERT IGNORE INTO students (name, email, password, department, year, bio) VALUES
('Govardhan N', 'govardhan@student.edu', '$2y$10$xZahuotSdSJLs1Ee734Vm.ny5elkjg71X4wUbsZpIEt35sz4.ddJK', 'Computer Science', '3rd', 'Passionate about full-stack development and AI.'),
('Rahul K', 'rahul@student.edu', '$2y$10$xZahuotSdSJLs1Ee734Vm.ny5elkjg71X4wUbsZpIEt35sz4.ddJK', 'Computer Science', '3rd', 'Frontend wizard with a love for beautiful UIs.'),
('Kiran M', 'kiran@student.edu', '$2y$10$xZahuotSdSJLs1Ee734Vm.ny5elkjg71X4wUbsZpIEt35sz4.ddJK', 'Information Technology', '3rd', 'Backend developer interested in cloud and DevOps.'),
('Ajay P', 'ajay@student.edu', '$2y$10$xZahuotSdSJLs1Ee734Vm.ny5elkjg71X4wUbsZpIEt35sz4.ddJK', 'Computer Science', '2nd', 'ML enthusiast and data science practitioner.'),
('Priya S', 'priya@student.edu', '$2y$10$xZahuotSdSJLs1Ee734Vm.ny5elkjg71X4wUbsZpIEt35sz4.ddJK', 'Electronics', '3rd', 'IoT and embedded systems developer.');

-- ============================================================
-- SEED DATA: Student Skills
-- Student 1 (Govardhan): Java, HTML, CSS, JavaScript
-- Student 2 (Rahul): HTML, CSS, JavaScript, React
-- Student 3 (Kiran): Python, SQL/MySQL, Docker, Git
-- Student 4 (Ajay): Python, Machine Learning, Data Science, Deep Learning
-- Student 5 (Priya): Python, IoT, C++, Git
-- ============================================================
INSERT IGNORE INTO student_skills (student_id, skill_id) VALUES
(1,2),(1,5),(1,6),(1,4),   -- Govardhan: Java, HTML, CSS, JavaScript
(2,5),(2,6),(2,4),(2,7),   -- Rahul: HTML, CSS, JavaScript, React
(3,1),(3,10),(3,18),(3,19),(3,9), -- Kiran: Python, SQL, Docker, Git, PHP
(4,1),(4,12),(4,14),(4,13), -- Ajay: Python, ML, Data Science, Deep Learning
(5,1),(5,24),(5,3),(5,19); -- Priya: Python, IoT, C++, Git

-- ============================================================
-- SEED DATA: Student Interests
-- ============================================================
INSERT IGNORE INTO student_interests (student_id, interest_id) VALUES
(1,1),(1,2),(1,3),  -- Govardhan: Web Dev, Mobile, AI
(2,1),(2,9),(2,7),  -- Rahul: Web Dev, UI/UX, Open Source
(3,10),(3,2),(3,4), -- Kiran: Cloud, Mobile, Data Science
(4,3),(4,4),(4,13), -- Ajay: AI, Data Science, Research
(5,12),(5,3),(5,8); -- Priya: Robotics, AI, Competitive Programming

-- ============================================================
-- SEED DATA: Sample Project
-- ============================================================
INSERT IGNORE INTO projects (name, description, leader_id, faculty_id, deadline, status) VALUES
('Smart Campus Navigation App', 'A mobile application that uses AI to help students navigate the campus, find classrooms, and discover events.', 1, 1, '2026-12-15', 'active');

INSERT IGNORE INTO project_members (project_id, student_id, role) VALUES
(1, 1, 'leader'),
(1, 2, 'member'),
(1, 3, 'member');

INSERT IGNORE INTO tasks (project_id, title, description, assigned_to, status, priority, due_date) VALUES
(1, 'Login Page UI', 'Design and implement the login/register screens', 1, 'completed', 'high', '2026-08-15'),
(1, 'Database Schema', 'Design MySQL schema for users and locations', 3, 'in_progress', 'high', '2026-08-20'),
(1, 'Map Integration API', 'Integrate Google Maps SDK for campus navigation', 2, 'in_progress', 'medium', '2026-09-01'),
(1, 'AI Path Recommendation', 'Implement shortest path algorithm with AI suggestions', 4, 'pending', 'high', '2026-09-15'),
(1, 'Unit Testing', 'Write unit tests for core modules', 3, 'pending', 'medium', '2026-10-01');

INSERT IGNORE INTO comments (project_id, student_id, message) VALUES
(1, 1, 'Great progress team! Login page is ready for review.'),
(1, 2, 'Map integration is working well. Should we add real-time updates?'),
(1, 3, 'Database schema finalized. I''ll share the ERD diagram shortly.');

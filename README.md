# CollabIQ — Setup & Run Guide

## Prerequisites
- **XAMPP** (or WAMP / Laragon) with PHP 8.0+ and MySQL
- A web browser

---

## Step 1 — Copy Files

Copy the entire `IPCAPSTONE` folder to your XAMPP htdocs:

```
C:\xampp\htdocs\ipcapstone\
```

So the structure looks like:
```
C:\xampp\htdocs\ipcapstone\index.php
C:\xampp\htdocs\ipcapstone\database.sql
C:\xampp\htdocs\ipcapstone\config\db.php
...
```

---

## Step 2 — Import Database

1. Start **Apache** and **MySQL** in XAMPP Control Panel
2. Open **phpMyAdmin**: http://localhost/phpmyadmin
3. Click **Import** → Choose `database.sql` → Click **Go**

Or via MySQL CLI:
```bash
mysql -u root -p < C:\IPCAPSTONE\database.sql
```

---

## Step 3 — Configure DB (if needed)

Edit `config/db.php` if your MySQL credentials differ:

```php
define('DB_USER', 'root');  // Your MySQL username
define('DB_PASS', '');       // Your MySQL password
define('BASE_URL', '/ipcapstone');
```

---

## Step 4 — Run the App

**Option A (Fastest - One Click):**
Double-click **`START_APP.bat`** in the project folder.

**Option B (Command Line):**
```bash
php -S localhost:8000
```
Open your browser: **[http://localhost:8000](http://localhost:8000)**

---

## Demo Credentials

| Role    | Email                          | Password    |
|---------|--------------------------------|-------------|
| Student | govardhan@student.edu          | student123  |
| Student | rahul@student.edu              | student123  |
| Student | kiran@student.edu              | student123  |
| Faculty | faculty@ipcapstone.edu         | faculty123  |

---

## Features by Module

### Module 1 — Student Profile & AI Recommendations
- Register → 3-step wizard (Basic Info → Skills → Interests)
- View your profile with collaboration score ring
- AI Team Recommendations with match score, star ratings, shared/complementary skills

### Module 2 — Project Collaboration
- Create Project → Add team members (searchable card picker)
- Project Dashboard: task table, progress bar, team discussion (AJAX), file uploads (drag & drop)
- Task Management: assign, update status (Pending / In Progress / Completed)

### Module 3 — Faculty Analytics
- Login as Faculty → Analytics Dashboard
- Chart.js bar chart (collaboration scores) + doughnut (task distribution)
- AI Insights: top performer, struggling students, active project
- Student detail reports
- Printable HTML report (click Print Report → Ctrl+P)

---

## Folder Structure

```
IPCAPSTONE/
├── index.php           ← Landing page
├── database.sql        ← Import this first!
├── config/db.php       ← DB connection
├── auth/               ← Login / Register / Logout
├── student/            ← Module 1: Profile + AI Recommendations
├── project/            ← Module 2: Projects + Tasks + Chat
├── faculty/            ← Module 3: Analytics Dashboard
├── api/                ← AJAX endpoints (recommend, task update, comment)
└── assets/
    ├── css/            ← main.css, auth.css, dashboard.css
    ├── js/             ← main.js, recommendations.js, charts.js
    └── uploads/        ← Uploaded project files
```

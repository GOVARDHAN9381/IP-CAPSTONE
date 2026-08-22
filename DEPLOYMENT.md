# 🌐 CollabIQ — Cloud Deployment Guide

CollabIQ is packaged and pre-configured for **1-Click Cloud Deployment** (Docker + Supabase PostgreSQL) on **Render**, **Railway**, or any Docker host.

---

## ⚡ Option 1: Deploy to Render (Recommended — Free Tier Available)

### Step 1: Push Code to GitHub
Open PowerShell or your terminal in `c:\IPCAPSTONE` and run:
```bash
# 1. Create a new empty repository on GitHub (e.g. "collabiq")
# 2. Link and push:
git remote add origin https://github.com/YOUR_USERNAME/YOUR_REPO_NAME.git
git branch -M main
git push -u origin main
```

### Step 2: 1-Click Deploy on Render
1. Go to **[render.com](https://dashboard.render.com)** and sign in with GitHub.
2. Click **New +** $\rightarrow$ **Blueprint** (or **Web Service** $\rightarrow$ **Deploy from Git repository**).
3. Select your `collabiq` repository.
4. Render will automatically detect [`render.yaml`](file:///c:/IPCAPSTONE/render.yaml) and [`Dockerfile`](file:///c:/IPCAPSTONE/Dockerfile).
5. Click **Apply** or **Create Web Service**.

*Render will build the Docker container with PHP 8.2 + Apache + PostgreSQL drivers and launch your live site (e.g., `https://collabiq-platform.onrender.com`).*

---

## 🚂 Option 2: Deploy to Railway

1. Go to **[railway.app](https://railway.app)** and click **New Project**.
2. Select **Deploy from GitHub repo** and pick your `collabiq` repository.
3. Add the following **Environment Variables** in Railway Dashboard:
   - `DB_HOST`: `db.sbzecviaqezsbouymecf.supabase.co`
   - `DB_PORT`: `5432`
   - `DB_NAME`: `postgres`
   - `DB_USER`: `postgres`
   - `DB_PASS`: `Govardhan@26`
   - `BASE_URL`: `""`
4. Click **Deploy**. Railway will generate your public `*.up.railway.app` URL.

---

## ☁️ Option 3: Deploy with Docker CLI (Self-Hosted / VPS)

You can run the container on any Linux / Windows / Cloud server:
```bash
# Build the Docker image
docker build -t collabiq .

# Run container connected to Supabase PostgreSQL
docker run -d -p 80:80 \
  -e DB_HOST="db.sbzecviaqezsbouymecf.supabase.co" \
  -e DB_PORT="5432" \
  -e DB_NAME="postgres" \
  -e DB_USER="postgres" \
  -e DB_PASS="Govardhan@26" \
  -e BASE_URL="" \
  --name collabiq-app collabiq
```

---

## 💻 Option 4: Run Locally (XAMPP)

Double-click:
```bat
c:\IPCAPSTONE\START_APP.bat
```
Browser opens at: **`http://localhost:8080/ipcapstone/`**

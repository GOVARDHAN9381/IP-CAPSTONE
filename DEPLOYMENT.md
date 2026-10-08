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

### Method A: Connect GitHub Repository (Automatic Deployments)
1. Go to **[railway.com / railway.app](https://railway.app)** and log in.
2. Select your project (the one with domain `ip-capstone-production.up.railway.app`).
3. If no service is created yet, or if you see **"Application not found (404)"**:
   - Click **+ Create / Deploy** $\rightarrow$ **GitHub Repo**.
   - Select **`GOVARDHAN9381/IP-CAPSTONE`** (branch: `main`).
4. In your Service $\rightarrow$ **Settings** $\rightarrow$ **Networking**:
   - Under **Public Networking**, ensure the domain `ip-capstone-production.up.railway.app` is linked to this service.
5. In your Service $\rightarrow$ **Variables**, add:
   - `DB_HOST`: `db.sbzecviaqezsbouymecf.supabase.co`
   - `DB_PORT`: `5432`
   - `DB_NAME`: `postgres`
   - `DB_USER`: `postgres`
   - `DB_PASS`: `Govardhan@26`
   - `BASE_URL`: `""`
   - `GROQ_API_KEY`: *(Optional: your Groq API key for AI features)*
6. Click **Deploy** (or trigger a new redeployment). Once the build finishes, `https://ip-capstone-production.up.railway.app/` will be live.

### Method B: Deploy from Terminal via Railway CLI
If you prefer deploying straight from this folder:
```powershell
# 1. Log in to Railway in your browser
npx @railway/cli login

# 2. Link to your existing Railway project
npx @railway/cli link

# 3. Deploy the application
npx @railway/cli up
```

### 🔍 Why did `ip-capstone-production.up.railway.app` return 404?
Railway returns `{"status":"error","code":404,"message":"Application not found"}` when:
- The domain was generated, but no active deployment has succeeded on that service yet.
- The service build is still running or waiting for GitHub connection.
- Linking the GitHub repository `GOVARDHAN9381/IP-CAPSTONE` and completing the first deploy immediately activates the domain.

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

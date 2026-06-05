# Portfolio Update — Deploy Instructions

These files update the **Projects page** at [nofiupelumi.github.io/projects](https://nofiupelumi.github.io/projects).

---

## Files changed

| File | Action |
|------|--------|
| `src/shared/opensource/projects.json` | **Replace entirely** with `portfolio-update/src/shared/opensource/projects.json` |
| `src/portfolio.js` | **Replace only `projectsHeader`** using the snippet in `portfolio_projectsHeader_patch.js` |

---

## Step-by-step

### 1 — Copy the updated `projects.json`

```bash
# From the root of nofiupelumi-portfolio-dev-files
cp <path-to>/portfolio-update/src/shared/opensource/projects.json \
   src/shared/opensource/projects.json
```

### 2 — Update `projectsHeader` in `src/portfolio.js`

Find this block (around line 815):

```js
const projectsHeader = {
  title: "Projects",
  description:
    "My projects makes use of vast variety of latest technology tools. My best experience is to create Data Science projects and deploy them to web applications using cloud infrastructure.",
  avatar_image_path: "projects_image.svg",
};
```

Replace the `description` value with:

```js
const projectsHeader = {
  title: "Projects",
  description:
    "A showcase of 12 hand-picked projects spanning Data Science, AI/LLM chatbots, enterprise automation, full-stack web applications, and cloud engineering. Each project solves a real-world problem and reflects end-to-end ownership—from data collection and model building to production deployment.",
  avatar_image_path: "projects_image.svg",
};
```

### 3 — Build the React app

```bash
cd nofiupelumi-portfolio-dev-files
npm install           # only if node_modules not present
npm run build         # outputs to build/
```

### 4 — Deploy to nofiupelumi.github.io

**Option A — Using the gh-pages script** (if GitHub credentials are configured):
```bash
npm run deploy        # pushes build/ to gh-pages branch
```

**Option B — Manual copy** (your current workflow):
```bash
# Copy all files from build/ into your nofiupelumi.github.io clone
cp -r build/* ../nofiupelumi.github.io/

# Commit and push
cd ../nofiupelumi.github.io
git add .
git commit -m "Update projects section with 12 curated projects"
git push origin main
```

---

## What changed in projects.json

The 6 basic projects were replaced/enriched with **12 showcase projects**:

| # | Project | Why it's compelling |
|---|---------|-------------------|
| 1 | **Nigeria Security Risk Index Dashboard** | ML + 25.9K incidents analysed, live dashboard, predictive forecasting |
| 2 | **AWS Serverless FAQ Chatbot** | Groq LLM + Lambda + zero-server architecture |
| 3 | **HR Dashboard + CV Screening** | GitHub Actions as AI matching engine — real automation |
| 4 | **Groq AI Chatbot (Laravel)** | Full-stack LLM chatbot, production deployed |
| 5 | **Miss Unity Nigeria Website** | Live Laravel app with database, cPanel deployed |
| 6 | **SharePoint IT Dept Intranet** | Enterprise Power Automate multi-step approval |
| 7 | **Disney Analytics** | K-means clustering + Tableau — Boston University |
| 8 | **Daily Attendance Bot** | Security org automation — Node.js portal |
| 9 | **SSL Cert Expiration Notifier** | DevOps + SharePoint integration |
| 10 | **Multi-source News Scraper** | Parallel scraping + email digest automation |
| 11 | **Cyclistic Bike-Share Capstone** | Google Data Analytics Certificate, R + ggplot2 |
| 12 | **Power BI Capstone — Dubizzle** | Interactive sales analytics dashboard |

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Nofiu Moruf Pelumi — Portfolio</title>
    <meta name="description" content="Portfolio of Nofiu Moruf Pelumi — Data Scientist, Full-Stack Developer & AI Engineer. Projects in ML, automation, cloud, and web development." />
    <meta property="og:title" content="Nofiu Moruf Pelumi — Portfolio" />
    <meta property="og:description" content="Data Scientist, Full-Stack Developer & AI Engineer based in Nigeria. View my projects." />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg-base:      #0a0e1a;
            --bg-card:      #111827;
            --bg-card-hover:#161f30;
            --border:       rgba(255,255,255,.08);
            --border-hover: rgba(255,255,255,.18);
            --text-primary: #f1f5f9;
            --text-muted:   #94a3b8;
            --text-subtle:  #64748b;
            --gold:         #f59e0b;
            --gold-light:   #fcd34d;
            --navy:         #3b82f6;
            --navy-light:   #93c5fd;
            --green:        #10b981;
            --purple:       #8b5cf6;
            --red:          #ef4444;
            --cyan:         #06b6d4;
            --radius:       12px;
            --shadow-card:  0 4px 24px rgba(0,0,0,.45);
            --transition:   .22s cubic-bezier(.4,0,.2,1);
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
            background: var(--bg-base);
            color: var(--text-primary);
            min-height: 100vh;
            line-height: 1.6;
        }

        /* ─── NAV ─── */
        .nav {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(10,14,26,.85);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border);
        }
        .nav-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .nav-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: var(--text-primary);
        }
        .nav-brand-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--gold), var(--navy));
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 15px;
            color: #fff;
            flex-shrink: 0;
        }
        .nav-brand-name { font-weight: 700; font-size: 16px; }
        .nav-links { display: flex; gap: 8px; align-items: center; }
        .nav-link {
            padding: 6px 14px;
            border-radius: 8px;
            text-decoration: none;
            color: var(--text-muted);
            font-size: 14px;
            font-weight: 500;
            transition: color var(--transition), background var(--transition);
        }
        .nav-link:hover, .nav-link.active {
            color: var(--text-primary);
            background: rgba(255,255,255,.07);
        }
        .nav-link.cta {
            background: var(--gold);
            color: #0a0e1a;
        }
        .nav-link.cta:hover { background: var(--gold-light); }

        /* ─── HERO ─── */
        .hero {
            max-width: 1200px;
            margin: 0 auto;
            padding: 80px 24px 60px;
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 48px;
            align-items: center;
        }
        @media (max-width: 700px) {
            .hero { grid-template-columns: 1fr; }
            .hero-visual { display: none; }
        }
        .hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(245,158,11,.1);
            border: 1px solid rgba(245,158,11,.25);
            color: var(--gold);
            padding: 4px 14px;
            border-radius: 100px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .hero-title {
            font-size: clamp(2rem, 5vw, 3.4rem);
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -.02em;
            margin-bottom: 20px;
        }
        .hero-title .accent { color: var(--gold); }
        .hero-desc {
            font-size: 17px;
            color: var(--text-muted);
            max-width: 600px;
            margin-bottom: 32px;
            line-height: 1.75;
        }
        .hero-stats {
            display: flex;
            gap: 32px;
            flex-wrap: wrap;
            margin-bottom: 36px;
        }
        .stat { display: flex; flex-direction: column; }
        .stat-num {
            font-size: 28px;
            font-weight: 800;
            color: var(--text-primary);
            line-height: 1;
        }
        .stat-label { font-size: 13px; color: var(--text-subtle); margin-top: 4px; }
        .hero-actions { display: flex; gap: 12px; flex-wrap: wrap; }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 22px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            cursor: pointer;
            border: none;
        }
        .btn-primary { background: var(--gold); color: #0a0e1a; }
        .btn-primary:hover { background: var(--gold-light); transform: translateY(-1px); }
        .btn-secondary {
            background: rgba(255,255,255,.06);
            color: var(--text-primary);
            border: 1px solid var(--border);
        }
        .btn-secondary:hover {
            background: rgba(255,255,255,.1);
            border-color: var(--border-hover);
        }
        .hero-visual {
            position: relative;
            width: 280px;
            height: 280px;
            flex-shrink: 0;
        }
        .hero-ring {
            position: absolute;
            inset: 0;
            border-radius: 50%;
            border: 1px solid rgba(245,158,11,.15);
            animation: spin 20s linear infinite;
        }
        .hero-ring::before {
            content: '';
            position: absolute;
            top: -4px;
            left: 50%;
            transform: translateX(-50%);
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--gold);
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .hero-avatar {
            position: absolute;
            inset: 20px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(245,158,11,.15), rgba(59,130,246,.15));
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 96px;
            color: rgba(255,255,255,.15);
        }

        /* ─── SKILLS STRIP ─── */
        .skills-strip {
            background: rgba(255,255,255,.03);
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            padding: 20px 24px;
            overflow: hidden;
        }
        .skills-track {
            display: flex;
            gap: 32px;
            animation: scroll 30s linear infinite;
            width: max-content;
        }
        @keyframes scroll { to { transform: translateX(-50%); } }
        .skill-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
            color: var(--text-subtle);
            font-size: 13px;
            font-weight: 500;
        }
        .skill-chip i { color: var(--gold); font-size: 14px; }

        /* ─── SECTION ─── */
        .section { max-width: 1200px; margin: 0 auto; padding: 72px 24px; }
        .section-header { margin-bottom: 48px; }
        .section-eyebrow {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 10px;
        }
        .section-title {
            font-size: clamp(1.5rem, 3vw, 2.2rem);
            font-weight: 800;
            letter-spacing: -.02em;
        }
        .section-subtitle { color: var(--text-muted); margin-top: 8px; font-size: 16px; }

        /* ─── FILTER TABS ─── */
        .filter-tabs {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 36px;
        }
        .filter-tab {
            padding: 7px 16px;
            border-radius: 100px;
            border: 1px solid var(--border);
            background: transparent;
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            font-family: inherit;
        }
        .filter-tab:hover { border-color: var(--border-hover); color: var(--text-primary); }
        .filter-tab.active {
            background: var(--gold);
            border-color: var(--gold);
            color: #0a0e1a;
        }

        /* ─── PROJECT GRID ─── */
        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 24px;
        }
        @media (max-width: 500px) {
            .projects-grid { grid-template-columns: 1fr; }
        }

        /* ─── PROJECT CARD ─── */
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 28px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            transition: transform var(--transition), border-color var(--transition), box-shadow var(--transition);
            box-shadow: var(--shadow-card);
            position: relative;
            overflow: hidden;
        }
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--accent, var(--gold)), transparent);
            opacity: 0;
            transition: opacity var(--transition);
        }
        .card:hover {
            transform: translateY(-4px);
            border-color: var(--border-hover);
            box-shadow: 0 12px 40px rgba(0,0,0,.6);
            background: var(--bg-card-hover);
        }
        .card:hover::before { opacity: 1; }

        .card-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
        .card-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }
        .card-badges { display: flex; gap: 6px; flex-wrap: wrap; justify-content: flex-end; }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 100px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .03em;
        }

        .badge-ml  { background: rgba(139,92,246,.15); color: #a78bfa; }
        .badge-ai  { background: rgba(16,185,129,.15); color: #34d399; }
        .badge-web { background: rgba(59,130,246,.15); color: #93c5fd; }
        .badge-auto { background: rgba(245,158,11,.15); color: #fcd34d; }
        .badge-cloud { background: rgba(6,182,212,.15); color: #67e8f9; }
        .badge-data { background: rgba(244,114,182,.15); color: #f9a8d4; }
        .badge-devops { background: rgba(239,68,68,.15); color: #fca5a5; }
        .badge-enterprise { background: rgba(99,102,241,.15); color: #a5b4fc; }
        .badge-featured { background: rgba(245,158,11,.2); color: var(--gold); }

        .card-title {
            font-size: 17px;
            font-weight: 700;
            line-height: 1.3;
        }
        .card-desc {
            font-size: 14px;
            color: var(--text-muted);
            line-height: 1.7;
            flex: 1;
        }

        .card-metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
            gap: 8px;
        }
        .metric {
            background: rgba(255,255,255,.04);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 8px 10px;
            text-align: center;
        }
        .metric-val {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-primary);
            display: block;
        }
        .metric-key {
            font-size: 10px;
            color: var(--text-subtle);
            text-transform: uppercase;
            letter-spacing: .08em;
            display: block;
            margin-top: 2px;
        }

        .card-stack {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        .tag {
            display: inline-flex;
            align-items: center;
            padding: 3px 9px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 500;
            background: rgba(255,255,255,.05);
            color: var(--text-subtle);
            border: 1px solid var(--border);
            font-family: 'Fira Code', monospace;
        }

        .card-highlights {
            border-left: 2px solid rgba(245,158,11,.3);
            padding-left: 12px;
        }
        .card-highlights li {
            font-size: 13px;
            color: var(--text-muted);
            list-style: none;
            padding: 2px 0;
        }
        .card-highlights li::before {
            content: '→ ';
            color: var(--gold);
            font-weight: 600;
        }

        .card-footer {
            display: flex;
            gap: 8px;
            align-items: center;
            padding-top: 4px;
            border-top: 1px solid var(--border);
        }
        .card-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
        }
        .card-link-github {
            background: rgba(255,255,255,.06);
            color: var(--text-muted);
            border: 1px solid var(--border);
        }
        .card-link-github:hover {
            background: rgba(255,255,255,.1);
            color: var(--text-primary);
            border-color: var(--border-hover);
        }
        .card-link-demo {
            background: rgba(245,158,11,.12);
            color: var(--gold);
            border: 1px solid rgba(245,158,11,.25);
        }
        .card-link-demo:hover {
            background: rgba(245,158,11,.2);
        }

        /* Featured span full width */
        .card-featured { grid-column: 1 / -1; }
        .card-featured .card-inner {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 32px;
            align-items: start;
        }
        @media (max-width: 700px) {
            .card-featured .card-inner { grid-template-columns: 1fr; }
        }

        /* ─── CONTACT STRIP ─── */
        .contact-strip {
            background: linear-gradient(135deg, rgba(245,158,11,.08), rgba(59,130,246,.08));
            border: 1px solid rgba(245,158,11,.15);
            border-radius: 20px;
            padding: 48px 40px;
            text-align: center;
            margin: 0 24px 80px;
            max-width: 1152px;
            margin-left: auto;
            margin-right: auto;
        }
        .contact-strip h2 {
            font-size: clamp(1.4rem, 3vw, 2rem);
            font-weight: 800;
            margin-bottom: 12px;
        }
        .contact-strip p { color: var(--text-muted); margin-bottom: 28px; }
        .contact-links { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }

        /* ─── FOOTER ─── */
        footer {
            border-top: 1px solid var(--border);
            padding: 24px;
            text-align: center;
            color: var(--text-subtle);
            font-size: 13px;
        }
        footer a { color: var(--gold); text-decoration: none; }

        /* ─── HIDDEN/visible helper ─── */
        .hidden { display: none !important; }
    </style>
</head>
<body>

<!-- ─── NAV ─── -->
<nav class="nav">
    <div class="nav-inner">
        <a href="{{ url('/') }}" class="nav-brand">
            <div class="nav-brand-avatar">NP</div>
            <span class="nav-brand-name">Nofiu Moruf Pelumi</span>
        </a>
        <div class="nav-links">
            <a href="#projects" class="nav-link active">Projects</a>
            <a href="https://nofiupelumi.github.io/" target="_blank" class="nav-link">Full Portfolio</a>
            <a href="https://github.com/nofiupelumi" target="_blank" class="nav-link cta">
                <i class="fa-brands fa-github"></i> GitHub
            </a>
        </div>
    </div>
</nav>

<!-- ─── HERO ─── -->
<header>
    <div class="hero">
        <div>
            <div class="hero-eyebrow">
                <i class="fa-solid fa-circle-dot fa-beat" style="color:var(--green)"></i>
                Open to New Opportunities
            </div>
            <h1 class="hero-title">
                Hi, I'm <span class="accent">Nofiu Moruf Pelumi</span>
            </h1>
            <p class="hero-desc">
                Data Scientist · Full-Stack Developer · AI/Cloud Engineer<br/>
                I build production-grade data pipelines, AI-powered web apps, and automation systems
                that solve real business problems — from ML dashboards to serverless chatbots.
            </p>
            <div class="hero-stats">
                <div class="stat">
                    <span class="stat-num">12+</span>
                    <span class="stat-label">Projects Delivered</span>
                </div>
                <div class="stat">
                    <span class="stat-num">61</span>
                    <span class="stat-label">GitHub Repos</span>
                </div>
                <div class="stat">
                    <span class="stat-num">5+</span>
                    <span class="stat-label">Tech Stacks</span>
                </div>
            </div>
            <div class="hero-actions">
                <a href="#projects" class="btn btn-primary">
                    <i class="fa-solid fa-folder-open"></i> View Projects
                </a>
                <a href="https://github.com/nofiupelumi" target="_blank" class="btn btn-secondary">
                    <i class="fa-brands fa-github"></i> GitHub Profile
                </a>
            </div>
        </div>
        <div class="hero-visual" aria-hidden="true">
            <div class="hero-ring"></div>
            <div class="hero-avatar"><i class="fa-solid fa-laptop-code"></i></div>
        </div>
    </div>
</header>

<!-- ─── SKILLS MARQUEE ─── -->
<div class="skills-strip" aria-hidden="true">
    <div class="skills-track" id="skillsTrack">
        <span class="skill-chip"><i class="fa-brands fa-python"></i> Python</span>
        <span class="skill-chip"><i class="fa-solid fa-brain"></i> Machine Learning</span>
        <span class="skill-chip"><i class="fa-brands fa-laravel"></i> Laravel / PHP</span>
        <span class="skill-chip"><i class="fa-brands fa-aws"></i> AWS (Lambda · Kendra · IAM)</span>
        <span class="skill-chip"><i class="fa-solid fa-chart-bar"></i> Data Visualization</span>
        <span class="skill-chip"><i class="fa-brands fa-js"></i> JavaScript</span>
        <span class="skill-chip"><i class="fa-brands fa-react"></i> React</span>
        <span class="skill-chip"><i class="fa-solid fa-database"></i> MySQL / SQL</span>
        <span class="skill-chip"><i class="fa-solid fa-robot"></i> Groq AI / LLMs</span>
        <span class="skill-chip"><i class="fa-brands fa-github"></i> GitHub Actions CI/CD</span>
        <span class="skill-chip"><i class="fa-solid fa-file-pdf"></i> PDF Extraction / OCR</span>
        <span class="skill-chip"><i class="fa-solid fa-screwdriver-wrench"></i> Power Automate</span>
        <span class="skill-chip"><i class="fa-brands fa-microsoft"></i> SharePoint</span>
        <span class="skill-chip"><i class="fa-solid fa-chart-line"></i> Tableau</span>
        <span class="skill-chip"><i class="fa-solid fa-r"></i> R</span>
        <!-- duplicate for seamless loop -->
        <span class="skill-chip"><i class="fa-brands fa-python"></i> Python</span>
        <span class="skill-chip"><i class="fa-solid fa-brain"></i> Machine Learning</span>
        <span class="skill-chip"><i class="fa-brands fa-laravel"></i> Laravel / PHP</span>
        <span class="skill-chip"><i class="fa-brands fa-aws"></i> AWS (Lambda · Kendra · IAM)</span>
        <span class="skill-chip"><i class="fa-solid fa-chart-bar"></i> Data Visualization</span>
        <span class="skill-chip"><i class="fa-brands fa-js"></i> JavaScript</span>
        <span class="skill-chip"><i class="fa-brands fa-react"></i> React</span>
        <span class="skill-chip"><i class="fa-solid fa-database"></i> MySQL / SQL</span>
        <span class="skill-chip"><i class="fa-solid fa-robot"></i> Groq AI / LLMs</span>
        <span class="skill-chip"><i class="fa-brands fa-github"></i> GitHub Actions CI/CD</span>
        <span class="skill-chip"><i class="fa-solid fa-file-pdf"></i> PDF Extraction / OCR</span>
        <span class="skill-chip"><i class="fa-solid fa-screwdriver-wrench"></i> Power Automate</span>
        <span class="skill-chip"><i class="fa-brands fa-microsoft"></i> SharePoint</span>
        <span class="skill-chip"><i class="fa-solid fa-chart-line"></i> Tableau</span>
        <span class="skill-chip"><i class="fa-solid fa-r"></i> R</span>
    </div>
</div>

<!-- ─── PROJECTS SECTION ─── -->
<main>
<section class="section" id="projects">
    <div class="section-header">
        <p class="section-eyebrow">Portfolio</p>
        <h2 class="section-title">Projects that Deliver Real Value</h2>
        <p class="section-subtitle">
            From AI-powered chatbots and ML dashboards to enterprise automation — each project solves a real problem.
        </p>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs" role="tablist">
        <button class="filter-tab active" data-filter="all" role="tab">All Projects</button>
        <button class="filter-tab" data-filter="ml"       role="tab">ML &amp; Data Science</button>
        <button class="filter-tab" data-filter="ai"       role="tab">AI &amp; Chatbots</button>
        <button class="filter-tab" data-filter="web"      role="tab">Web Apps</button>
        <button class="filter-tab" data-filter="auto"     role="tab">Automation</button>
        <button class="filter-tab" data-filter="cloud"    role="tab">Cloud</button>
    </div>

    <div class="projects-grid" id="projectsGrid">

        <!-- ══════════════════════════════
             1. PELDARG EXTRACTION PLATFORM
             ══════════════════════════════ -->
        <article class="card card-featured" data-cats="web auto" style="--accent: var(--gold);">
            <div class="card-inner">
                <div style="display:flex;flex-direction:column;gap:16px;">
                    <div class="card-header">
                        <div class="card-icon" style="background:rgba(245,158,11,.15);color:var(--gold);">
                            <i class="fa-solid fa-file-export"></i>
                        </div>
                        <div class="card-badges">
                            <span class="badge badge-featured"><i class="fa-solid fa-star"></i> Featured</span>
                            <span class="badge badge-web">Laravel</span>
                            <span class="badge badge-auto">CI/CD</span>
                        </div>
                    </div>
                    <h2 class="card-title">Peldarg Consulting — Document Extraction Platform</h2>
                    <p class="card-desc">
                        An enterprise-grade PDF extraction pipeline for Peldarg Consulting. Operators upload Convocation PDFs
                        via a secure web dashboard; GitHub Actions parallelises extraction across page-range chunks using
                        Gemini AI + Tesseract OCR, then aggregates results into CSV / XLSX / DOCX and pushes them back
                        to the app — authenticated with HMAC signatures and signed download URLs.
                    </p>
                </div>
                <div style="display:flex;flex-direction:column;gap:16px;">
                    <div class="card-metrics">
                        <div class="metric">
                            <span class="metric-val">3</span>
                            <span class="metric-key">API Tiers</span>
                        </div>
                        <div class="metric">
                            <span class="metric-val">10</span>
                            <span class="metric-key">Pages/Chunk</span>
                        </div>
                        <div class="metric">
                            <span class="metric-val">24h</span>
                            <span class="metric-key">Signed URLs</span>
                        </div>
                        <div class="metric">
                            <span class="metric-val">3</span>
                            <span class="metric-key">Output Formats</span>
                        </div>
                    </div>
                    <ul class="card-highlights">
                        <li>Parallel chunked processing scales to very large PDFs</li>
                        <li>HMAC-signed callbacks — zero unauthorized result injection</li>
                        <li>Credit ledger with hard caps per user/tier</li>
                        <li>Gemini AI multimodal OCR for image-heavy documents</li>
                    </ul>
                    <div class="card-stack">
                        <span class="tag">Laravel 11</span>
                        <span class="tag">PHP 8.2</span>
                        <span class="tag">MySQL</span>
                        <span class="tag">GitHub Actions</span>
                        <span class="tag">Python</span>
                        <span class="tag">Gemini AI</span>
                        <span class="tag">Tesseract OCR</span>
                        <span class="tag">Vite / Tailwind</span>
                    </div>
                    <div class="card-footer">
                        <a href="https://github.com/nofiupelumi/peldarg_consulting_ext" target="_blank" rel="noopener noreferrer" class="card-link card-link-github">
                            <i class="fa-brands fa-github"></i> Source Code
                        </a>
                    </div>
                </div>
            </div>
        </article>

        <!-- ══════════════════════
             2. SECURITY DASHBOARD
             ══════════════════════ -->
        <article class="card" data-cats="ml data" style="--accent: var(--purple);">
            <div class="card-header">
                <div class="card-icon" style="background:rgba(139,92,246,.15);color:var(--purple);">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div class="card-badges">
                    <span class="badge badge-ml">ML</span>
                    <span class="badge badge-data">Analytics</span>
                </div>
            </div>
            <h2 class="card-title">Nigeria Security Risk Index Dashboard</h2>
            <p class="card-desc">
                Interactive intelligence dashboard built on a dataset of <strong style="color:var(--text-primary)">25,945 security incidents</strong>
                from 2018–2024 across all 36 Nigerian states. Provides stakeholders with regional risk scores, incident
                trend analysis, perpetrator tracking, and scenario-based risk projections through 2026.
            </p>
            <div class="card-metrics">
                <div class="metric">
                    <span class="metric-val">25.9K</span>
                    <span class="metric-key">Incidents</span>
                </div>
                <div class="metric">
                    <span class="metric-val">73.3K</span>
                    <span class="metric-key">Fatalities</span>
                </div>
                <div class="metric">
                    <span class="metric-val">161%</span>
                    <span class="metric-key">Incident Rise</span>
                </div>
            </div>
            <ul class="card-highlights">
                <li>6 geopolitical zones analysed with interactive maps</li>
                <li>Predictive risk forecasting 2024–2026</li>
                <li>PDF export for offline policy briefings</li>
            </ul>
            <div class="card-stack">
                <span class="tag">Python</span>
                <span class="tag">Jupyter</span>
                <span class="tag">Chart.js</span>
                <span class="tag">HTML5</span>
                <span class="tag">Tailwind CSS</span>
            </div>
            <div class="card-footer">
                <a href="https://github.com/nofiupelumi/ML-Web-deployment-nigeria-security-risk-index" target="_blank" rel="noopener noreferrer" class="card-link card-link-github">
                    <i class="fa-brands fa-github"></i> Source Code
                </a>
                <a href="https://ddftrtwy.gensparkspace.com/" target="_blank" rel="noopener noreferrer" class="card-link card-link-demo">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i> Live Demo
                </a>
            </div>
        </article>

        <!-- ══════════════════════════
             3. HR DASHBOARD & CV SCREENING
             ══════════════════════════ -->
        <article class="card" data-cats="web auto ai" style="--accent: var(--green);">
            <div class="card-header">
                <div class="card-icon" style="background:rgba(16,185,129,.15);color:var(--green);">
                    <i class="fa-solid fa-users-gear"></i>
                </div>
                <div class="card-badges">
                    <span class="badge badge-web">Full-Stack</span>
                    <span class="badge badge-auto">Automation</span>
                </div>
            </div>
            <h2 class="card-title">HR Dashboard — Automated CV Screening</h2>
            <p class="card-desc">
                A recruitment intelligence platform where HR teams post positions with keyword profiles and
                applicants submit CVs online. A GitHub Actions workflow processes each submission in the background,
                scores candidates against role requirements, and surfaces ranked results in the dashboard — all in real time.
            </p>
            <ul class="card-highlights">
                <li>Background queue processing via Laravel jobs</li>
                <li>GitHub Actions as distributed keyword-matching engine</li>
                <li>Recruiters see ranked candidates without manual screening</li>
            </ul>
            <div class="card-stack">
                <span class="tag">Laravel</span>
                <span class="tag">PHP</span>
                <span class="tag">MySQL</span>
                <span class="tag">GitHub Actions</span>
                <span class="tag">Queue Workers</span>
            </div>
            <div class="card-footer">
                <a href="https://github.com/nofiupelumi/Hr_Dashboard_Automated_CV_Screening" target="_blank" rel="noopener noreferrer" class="card-link card-link-github">
                    <i class="fa-brands fa-github"></i> Source Code
                </a>
            </div>
        </article>

        <!-- ══════════════════════
             4. AWS CHATBOT
             ══════════════════════ -->
        <article class="card" data-cats="ai cloud" style="--accent: var(--cyan);">
            <div class="card-header">
                <div class="card-icon" style="background:rgba(6,182,212,.15);color:var(--cyan);">
                    <i class="fa-brands fa-aws"></i>
                </div>
                <div class="card-badges">
                    <span class="badge badge-ai">AI</span>
                    <span class="badge badge-cloud">Serverless</span>
                </div>
            </div>
            <h2 class="card-title">AWS Serverless FAQ Chatbot (Centria University)</h2>
            <p class="card-desc">
                Fully serverless FAQ chatbot deployed for Centria University of Applied Sciences. Uses <strong style="color:var(--text-primary)">AWS Lambda</strong>
                for the backend, <strong style="color:var(--text-primary)">Groq API (LLM)</strong> for AI-powered fallback answers, fuzzy FAQ
                matching with Python difflib, and a <strong style="color:var(--text-primary)">Gradio</strong> web UI — all secured via AWS IAM.
            </p>
            <ul class="card-highlights">
                <li>Zero-server infrastructure — scales automatically with demand</li>
                <li>Prioritises structured FAQ responses over LLM hallucination</li>
                <li>Deployed with AWS API Gateway + Lambda URL integration</li>
                <li>Optional AWS Kendra integration for document-level search</li>
            </ul>
            <div class="card-stack">
                <span class="tag">AWS Lambda</span>
                <span class="tag">Groq API</span>
                <span class="tag">Gradio</span>
                <span class="tag">Python</span>
                <span class="tag">AWS IAM</span>
                <span class="tag">API Gateway</span>
            </div>
            <div class="card-footer">
                <a href="https://github.com/nofiupelumi/AWS-CHATBOT" target="_blank" rel="noopener noreferrer" class="card-link card-link-github">
                    <i class="fa-brands fa-github"></i> Source Code
                </a>
            </div>
        </article>

        <!-- ══════════════════════
             5. GROQ CHATBOT LARAVEL
             ══════════════════════ -->
        <article class="card" data-cats="ai web" style="--accent: var(--navy);">
            <div class="card-header">
                <div class="card-icon" style="background:rgba(59,130,246,.15);color:var(--navy-light);">
                    <i class="fa-solid fa-comments"></i>
                </div>
                <div class="card-badges">
                    <span class="badge badge-ai">AI / LLM</span>
                    <span class="badge badge-web">Laravel</span>
                </div>
            </div>
            <h2 class="card-title">Groq AI Chatbot — Laravel Full-Stack</h2>
            <p class="card-desc">
                Production-deployed conversational AI application built end-to-end with Laravel (backend &amp; frontend)
                and Groq's ultra-fast LLM inference API. Delivers sub-second responses via streaming, with a polished
                chat UI and cPanel deployment guide included.
            </p>
            <ul class="card-highlights">
                <li>Groq's LPU hardware enables ~500 tokens/second responses</li>
                <li>Laravel handles routing, sessions, and API proxy securely</li>
                <li>Deployed on shared hosting with cPanel step-by-step guide</li>
            </ul>
            <div class="card-stack">
                <span class="tag">Laravel</span>
                <span class="tag">Groq API</span>
                <span class="tag">PHP</span>
                <span class="tag">CSS</span>
                <span class="tag">Blade</span>
            </div>
            <div class="card-footer">
                <a href="https://github.com/nofiupelumi/chatbot-groq-laravel" target="_blank" rel="noopener noreferrer" class="card-link card-link-github">
                    <i class="fa-brands fa-github"></i> Source Code
                </a>
            </div>
        </article>

        <!-- ══════════════════════════
             6. MISS UNITY NIGERIA WEBSITE
             ══════════════════════════ -->
        <article class="card" data-cats="web" style="--accent: #ec4899;">
            <div class="card-header">
                <div class="card-icon" style="background:rgba(236,72,153,.15);color:#f9a8d4;">
                    <i class="fa-solid fa-crown"></i>
                </div>
                <div class="card-badges">
                    <span class="badge badge-web">Full-Stack</span>
                    <span class="badge badge-enterprise">Deployed</span>
                </div>
            </div>
            <h2 class="card-title">Miss Unity Nigeria — Pageant Web Platform</h2>
            <p class="card-desc">
                End-to-end pageant management website for Miss Unity Nigeria (<code style="color:var(--gold)">missunity.com.ng</code>).
                Features contestant registration, event management, public pages, and an admin panel —
                fully deployed on cPanel shared hosting with a custom database and production optimisations.
            </p>
            <ul class="card-highlights">
                <li>Live on production domain with SSL + .htaccess rewriting</li>
                <li>Custom database schema for pageant events &amp; candidates</li>
                <li>Responsive design for mobile-first audience engagement</li>
            </ul>
            <div class="card-stack">
                <span class="tag">Laravel</span>
                <span class="tag">MySQL</span>
                <span class="tag">JavaScript</span>
                <span class="tag">cPanel</span>
                <span class="tag">CSS</span>
            </div>
            <div class="card-footer">
                <a href="https://github.com/nofiupelumi/Miss-Unity-Nigeria-Website" target="_blank" rel="noopener noreferrer" class="card-link card-link-github">
                    <i class="fa-brands fa-github"></i> Source Code
                </a>
            </div>
        </article>

        <!-- ══════════════════════════
             7. SHAREPOINT IT DEPARTMENT
             ══════════════════════════ -->
        <article class="card" data-cats="enterprise auto" style="--accent: var(--navy-light);">
            <div class="card-header">
                <div class="card-icon" style="background:rgba(99,102,241,.15);color:#a5b4fc;">
                    <i class="fa-brands fa-microsoft"></i>
                </div>
                <div class="card-badges">
                    <span class="badge badge-enterprise">Enterprise</span>
                    <span class="badge badge-auto">Automation</span>
                </div>
            </div>
            <h2 class="card-title">IT Department SharePoint Intranet &amp; Automation</h2>
            <p class="card-desc">
                Complete enterprise intranet built on Microsoft SharePoint for an IT Department at Risk Control Service.
                Includes Team Directory, Knowledge Base, Help Desk (Microsoft Forms integration), and a Money Request
                portal with a multi-step Power Automate approval workflow (Accountant → Director → Finance).
            </p>
            <ul class="card-highlights">
                <li>Fully automated approval loop — zero manual routing overhead</li>
                <li>Help Desk form routes queries directly to support queue</li>
                <li>Structured Knowledge Base reduces repeat IT support tickets</li>
            </ul>
            <div class="card-stack">
                <span class="tag">SharePoint</span>
                <span class="tag">Power Automate</span>
                <span class="tag">Microsoft Forms</span>
                <span class="tag">Power Platform</span>
            </div>
            <div class="card-footer">
                <a href="https://github.com/nofiupelumi/Sharepoint-site-for-IT-Department" target="_blank" rel="noopener noreferrer" class="card-link card-link-github">
                    <i class="fa-brands fa-github"></i> Documentation
                </a>
            </div>
        </article>

        <!-- ══════════════════════════
             8. DISNEY ANALYTICS PROJECT
             ══════════════════════════ -->
        <article class="card" data-cats="ml data" style="--accent: #f472b6;">
            <div class="card-header">
                <div class="card-icon" style="background:rgba(244,114,182,.15);color:#f9a8d4;">
                    <i class="fa-solid fa-chart-pie"></i>
                </div>
                <div class="card-badges">
                    <span class="badge badge-ml">ML</span>
                    <span class="badge badge-data">Marketing Analytics</span>
                </div>
            </div>
            <h2 class="card-title">Disney Corporation — Marketing Analytics &amp; Segmentation</h2>
            <p class="card-desc">
                University-grade marketing analytics project (Boston University AD654) advising Disney on strategy
                through data. Performed EDA on the Disney movies dataset, built Tableau dashboards with 4–6 visualisations
                on commercial trends, and ran k-means / hierarchical clustering to identify customer family segments
                with tailored targeting strategies.
            </p>
            <ul class="card-highlights">
                <li>K-means clustering reveals distinct family audience archetypes</li>
                <li>Tableau dashboard highlights genre trends &amp; box-office drivers</li>
                <li>Actionable targeting strategies per identified segment</li>
            </ul>
            <div class="card-stack">
                <span class="tag">Python</span>
                <span class="tag">Jupyter</span>
                <span class="tag">Tableau</span>
                <span class="tag">K-Means</span>
                <span class="tag">Pandas</span>
                <span class="tag">Sklearn</span>
            </div>
            <div class="card-footer">
                <a href="https://github.com/nofiupelumi/Disney-Analytics-Project" target="_blank" rel="noopener noreferrer" class="card-link card-link-github">
                    <i class="fa-brands fa-github"></i> Source Code
                </a>
            </div>
        </article>

        <!-- ══════════════════════════
             9. WEBSCRAPER AUTOMATION
             ══════════════════════════ -->
        <article class="card" data-cats="auto" style="--accent: var(--gold);">
            <div class="card-header">
                <div class="card-icon" style="background:rgba(245,158,11,.15);color:var(--gold);">
                    <i class="fa-solid fa-newspaper"></i>
                </div>
                <div class="card-badges">
                    <span class="badge badge-auto">Automation</span>
                    <span class="badge badge-ml">Python</span>
                </div>
            </div>
            <h2 class="card-title">Multi-Source News Scraper &amp; Email Digest Automation</h2>
            <p class="card-desc">
                Automated intelligence pipeline that concurrently scrapes news from multiple Nigerian and international
                news portals (including EONS Intelligence &amp; Ripples Nigeria), deduplicates headlines, and dispatches
                formatted email digests to subscribers — fully scheduled and hands-off.
            </p>
            <ul class="card-highlights">
                <li>Scrapes multiple live news sources in parallel</li>
                <li>Automatic deduplication and priority ranking</li>
                <li>Scheduled delivery — no manual intervention needed</li>
            </ul>
            <div class="card-stack">
                <span class="tag">Python</span>
                <span class="tag">BeautifulSoup</span>
                <span class="tag">Requests</span>
                <span class="tag">SMTP</span>
                <span class="tag">Cron</span>
            </div>
            <div class="card-footer">
                <a href="https://github.com/nofiupelumi/Multiple-Webscraper-Automation" target="_blank" rel="noopener noreferrer" class="card-link card-link-github">
                    <i class="fa-brands fa-github"></i> Source Code
                </a>
            </div>
        </article>

        <!-- ══════════════════════════
             10. SSL CERT NOTIFIER
             ══════════════════════════ -->
        <article class="card" data-cats="auto devops" style="--accent: var(--red);">
            <div class="card-header">
                <div class="card-icon" style="background:rgba(239,68,68,.15);color:#fca5a5;">
                    <i class="fa-solid fa-lock"></i>
                </div>
                <div class="card-badges">
                    <span class="badge badge-devops">DevOps</span>
                    <span class="badge badge-auto">Automation</span>
                </div>
            </div>
            <h2 class="card-title">SSL Certificate Expiration Notifier</h2>
            <p class="card-desc">
                Python-based monitoring tool that probes a list of domain certificates, calculates days-to-expiry,
                writes status back to a SharePoint-connected Excel sheet, and fires alert emails to the team when
                any certificate is within the danger window — preventing surprise outages.
            </p>
            <ul class="card-highlights">
                <li>Connects to live SharePoint list for centralised tracking</li>
                <li>Configurable alert thresholds (e.g., 30 / 14 / 7 days)</li>
                <li>Prevents costly SSL-related service outages proactively</li>
            </ul>
            <div class="card-stack">
                <span class="tag">Python</span>
                <span class="tag">SharePoint API</span>
                <span class="tag">OpenSSL</span>
                <span class="tag">Excel / openpyxl</span>
                <span class="tag">SMTP</span>
            </div>
            <div class="card-footer">
                <a href="https://github.com/nofiupelumi/sslcert_expiration_notifier" target="_blank" rel="noopener noreferrer" class="card-link card-link-github">
                    <i class="fa-brands fa-github"></i> Source Code
                </a>
            </div>
        </article>

        <!-- ══════════════════════════
             11. ATTENDANCE BOT
             ══════════════════════════ -->
        <article class="card" data-cats="auto web" style="--accent: var(--green);">
            <div class="card-header">
                <div class="card-icon" style="background:rgba(16,185,129,.15);color:var(--green);">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
                <div class="card-badges">
                    <span class="badge badge-auto">Automation</span>
                    <span class="badge badge-web">Portal</span>
                </div>
            </div>
            <h2 class="card-title">Daily Attendance Bot — Security Portal</h2>
            <p class="card-desc">
                Web-based attendance automation portal for a security organisation. Employees check in/out through
                the portal; a Node.js bot processes records, enforces roster rules, flags anomalies, and generates
                daily summary reports — eliminating paper-based attendance logs.
            </p>
            <ul class="card-highlights">
                <li>Eliminates manual attendance sheets for field security staff</li>
                <li>Daily automated report generation &amp; anomaly alerts</li>
                <li>Role-based access for supervisors and admin</li>
            </ul>
            <div class="card-stack">
                <span class="tag">JavaScript</span>
                <span class="tag">Node.js</span>
                <span class="tag">HTML</span>
                <span class="tag">CSS</span>
            </div>
            <div class="card-footer">
                <a href="https://github.com/nofiupelumi/Daily-attendance-bot-portal4security" target="_blank" rel="noopener noreferrer" class="card-link card-link-github">
                    <i class="fa-brands fa-github"></i> Source Code
                </a>
            </div>
        </article>

        <!-- ══════════════════════════
             12. GOOGLE DATA ANALYTICS CAPSTONE
             ══════════════════════════ -->
        <article class="card" data-cats="ml data" style="--accent: var(--navy);">
            <div class="card-header">
                <div class="card-icon" style="background:rgba(59,130,246,.15);color:var(--navy-light);">
                    <i class="fa-solid fa-bicycle"></i>
                </div>
                <div class="card-badges">
                    <span class="badge badge-data">Data Analysis</span>
                    <span class="badge badge-ml">R</span>
                </div>
            </div>
            <h2 class="card-title">Cyclistic Bike-Share — Google Data Analytics Capstone</h2>
            <p class="card-desc">
                Google Professional Certificate capstone analysing Cyclistic, a fictional bike-share company in Chicago.
                Full data lifecycle: cleaning → transformation → exploration → visualisation in R with ggplot2 — culminating
                in three data-driven marketing recommendations to convert casual riders into annual members.
            </p>
            <ul class="card-highlights">
                <li>12 months of trip data cleaned &amp; wrangled in R</li>
                <li>ggplot2 visualisations highlight rider behaviour differences</li>
                <li>Actionable marketing strategy backed by statistical evidence</li>
            </ul>
            <div class="card-stack">
                <span class="tag">R</span>
                <span class="tag">ggplot2</span>
                <span class="tag">tidyverse</span>
                <span class="tag">dplyr</span>
                <span class="tag">RMarkdown</span>
            </div>
            <div class="card-footer">
                <a href="https://github.com/nofiupelumi/Google-Data-Analytics-Capstone-Project" target="_blank" rel="noopener noreferrer" class="card-link card-link-github">
                    <i class="fa-brands fa-github"></i> Source Code
                </a>
            </div>
        </article>

    </div><!-- /projects-grid -->
</section>

<!-- ─── CONTACT STRIP ─── -->
<section style="max-width:1200px;margin:0 auto;padding:0 24px 80px;">
    <div class="contact-strip">
        <h2>Let's Build Something That Matters</h2>
        <p>Open to full-time roles, freelance projects, and collaboration opportunities in data science, AI engineering, and full-stack development.</p>
        <div class="contact-links">
            <a href="mailto:nofiumoruf17@gmail.com" class="btn btn-primary">
                <i class="fa-solid fa-envelope"></i> nofiumoruf17@gmail.com
            </a>
            <a href="https://github.com/nofiupelumi" target="_blank" rel="noopener noreferrer" class="btn btn-secondary">
                <i class="fa-brands fa-github"></i> github.com/nofiupelumi
            </a>
            <a href="https://nofiupelumi.github.io/" target="_blank" rel="noopener noreferrer" class="btn btn-secondary">
                <i class="fa-solid fa-globe"></i> Full Portfolio
            </a>
        </div>
    </div>
</section>
</main>

<!-- ─── FOOTER ─── -->
<footer>
    <p>© {{ date('Y') }} Nofiu Moruf Pelumi · Built with <i class="fa-solid fa-heart" style="color:var(--red)"></i> ·
    <a href="https://nofiupelumi.github.io/" target="_blank" rel="noopener noreferrer">Portfolio</a> ·
    <a href="https://github.com/nofiupelumi" target="_blank" rel="noopener noreferrer">GitHub</a></p>
</footer>

<script>
    // ─── FILTER LOGIC ───
    const tabs = document.querySelectorAll('.filter-tab');
    const cards = document.querySelectorAll('#projectsGrid article.card');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');

            const filter = tab.dataset.filter;
            cards.forEach(card => {
                if (filter === 'all') {
                    card.classList.remove('hidden');
                    return;
                }
                const cats = (card.dataset.cats || '').split(' ');
                card.classList.toggle('hidden', !cats.includes(filter));
            });
        });
    });
</script>
</body>
</html>

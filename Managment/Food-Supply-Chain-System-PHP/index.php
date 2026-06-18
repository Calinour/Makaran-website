<?php
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/session.php';
if (is_logged_in()) {
    header('Location: ' . BASE_URL . 'dashboard/index.php');
    exit();
}
$pageTitle = 'SahanFresh – Farm-Fresh Food Supply Chain';
include __DIR__ . '/includes/header.php';
?>
<style>
/* ============================================================
   LANDING PAGE – FUTURISTIC REDESIGN
   ============================================================ */

/* ---------- Canvas / Grid Background ---------- */
#hero-canvas {
    position: fixed;
    inset: 0;
    z-index: 0;
    pointer-events: none;
}

/* ---------- Noise overlay ---------- */
body::before {
    content: '';
    position: fixed;
    inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");
    z-index: 0;
    pointer-events: none;
    opacity: .5;
}

/* Radial spotlight blobs */
.blob {
    position: fixed;
    border-radius: 50%;
    filter: blur(120px);
    pointer-events: none;
    z-index: 0;
    animation: blobFloat 12s ease-in-out infinite;
}
.blob-1 { width: 600px; height: 600px; background: radial-gradient(circle, rgba(16,185,129,.13) 0%, transparent 70%); top: -150px; left: -100px; animation-delay: 0s; }
.blob-2 { width: 500px; height: 500px; background: radial-gradient(circle, rgba(99,102,241,.12) 0%, transparent 70%); bottom: -150px; right: -100px; animation-delay: -6s; }
.blob-3 { width: 400px; height: 400px; background: radial-gradient(circle, rgba(16,185,129,.07) 0%, transparent 70%); top: 40%; left: 50%; animation-delay: -3s; }

@keyframes blobFloat {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33%       { transform: translate(30px, -30px) scale(1.05); }
    66%       { transform: translate(-20px, 20px) scale(.95); }
}

/* ---------- All landing sections sit above canvas ---------- */
.landing-wrap { position: relative; z-index: 1; }

/* ============================================================
   NAVBAR OVERRIDE
   ============================================================ */
header {
    background: rgba(11, 15, 25, 0.6) !important;
    backdrop-filter: blur(24px) saturate(180%) !important;
    border-bottom: 1px solid rgba(255,255,255,.06) !important;
}
.logo {
    font-size: 1.4rem;
    letter-spacing: -.02em;
}
.logo span {
    background: linear-gradient(90deg,#10b981,#6366f1);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    text-shadow: none;
}
.nav-links a { font-size: .9rem; letter-spacing: .02em; }

/* ============================================================
   HERO SECTION
   ============================================================ */
.hero-section {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 8rem 1.5rem 5rem;
    position: relative;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    padding: .4rem 1.2rem;
    border-radius: 999px;
    font-size: .8rem;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    background: rgba(16,185,129,.08);
    border: 1px solid rgba(16,185,129,.25);
    color: #10b981;
    margin-bottom: 2rem;
    animation: fadeSlideDown .8s ease both;
    position: relative;
    overflow: hidden;
}
.hero-badge::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, transparent, rgba(16,185,129,.15), transparent);
    animation: shimmer 2.5s infinite;
}
@keyframes shimmer {
    0%   { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}
.badge-dot {
    width: 6px; height: 6px;
    border-radius: 50%;
    background: #10b981;
    box-shadow: 0 0 8px #10b981;
    animation: pulse 1.5s ease-in-out infinite;
}
@keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50%       { opacity: .5; transform: scale(1.4); }
}

.hero-title {
    font-size: clamp(3rem, 6vw, 6rem);
    font-weight: 800;
    line-height: 1.08;
    letter-spacing: -.03em;
    margin-bottom: 1.5rem;
    animation: fadeSlideDown .9s ease .1s both;
    max-width: 900px;
}
.hero-title .line2 { display: block; margin-top: .1em; }
.hero-gradient {
    background: linear-gradient(135deg, #10b981 0%, #6366f1 50%, #10b981 100%);
    background-size: 200% auto;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    animation: gradientShift 4s linear infinite;
}
@keyframes gradientShift {
    0%   { background-position: 0% center; }
    100% { background-position: 200% center; }
}

.hero-sub {
    font-size: 1.15rem;
    color: var(--text-muted);
    max-width: 580px;
    line-height: 1.75;
    margin-bottom: 2.5rem;
    animation: fadeSlideDown 1s ease .2s both;
}

.hero-cta {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
    animation: fadeSlideDown 1s ease .3s both;
    margin-bottom: 4rem;
}

.btn-glow {
    background: linear-gradient(135deg, #10b981, #059669);
    color: #fff;
    padding: .85rem 2.2rem;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 700;
    border: none;
    cursor: pointer;
    position: relative;
    overflow: hidden;
    transition: all .3s ease;
    box-shadow: 0 0 0 rgba(16,185,129,0);
}
.btn-glow::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, transparent 0%, rgba(255,255,255,.15) 50%, transparent 100%);
    transform: translateX(-100%);
    transition: transform .5s ease;
}
.btn-glow:hover::before { transform: translateX(100%); }
.btn-glow:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 30px rgba(16,185,129,.45), 0 0 0 1px rgba(16,185,129,.3);
}

.btn-outline-glow {
    background: transparent;
    color: var(--text-main);
    padding: .85rem 2.2rem;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 700;
    border: 1px solid rgba(255,255,255,.12);
    cursor: pointer;
    transition: all .3s ease;
    backdrop-filter: blur(8px);
}
.btn-outline-glow:hover {
    background: rgba(255,255,255,.05);
    border-color: rgba(255,255,255,.25);
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,.3);
}

/* ---------- Floating Dashboard Preview ---------- */
.hero-preview {
    animation: fadeSlideDown 1.1s ease .4s both;
    position: relative;
    max-width: 860px;
    width: 100%;
    margin: 0 auto;
}
.preview-glow {
    position: absolute;
    inset: -2px;
    border-radius: 20px;
    background: linear-gradient(135deg, rgba(16,185,129,.4), rgba(99,102,241,.4));
    filter: blur(20px);
    opacity: .5;
    z-index: -1;
}
.preview-card {
    background: rgba(17,24,39,.85);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 18px;
    padding: 1.5rem;
    backdrop-filter: blur(20px);
    box-shadow: 0 30px 80px rgba(0,0,0,.6);
}
.preview-topbar {
    display: flex;
    align-items: center;
    gap: .75rem;
    margin-bottom: 1.25rem;
}
.win-dot {
    width: 12px; height: 12px; border-radius: 50%;
}
.wd-red   { background: #ff5f57; }
.wd-yellow{ background: #febc2e; }
.wd-green { background: #28c840; }
.preview-title-bar {
    flex: 1;
    background: rgba(255,255,255,.04);
    border: 1px solid rgba(255,255,255,.06);
    border-radius: 8px;
    padding: .4rem 1rem;
    font-size: .8rem;
    color: var(--text-muted);
    text-align: left;
}
.preview-stats-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 1.25rem;
}
.p-stat {
    background: rgba(255,255,255,.03);
    border: 1px solid rgba(255,255,255,.07);
    border-radius: 12px;
    padding: 1rem;
    text-align: left;
}
.p-stat-label { font-size: .72rem; color: var(--text-muted); margin-bottom: .3rem; text-transform: uppercase; letter-spacing: .06em; }
.p-stat-val   { font-size: 1.5rem; font-weight: 800; color: var(--text-main); }
.p-stat-val.green  { color: #10b981; }
.p-stat-val.purple { color: #6366f1; }
.p-stat-val.amber  { color: #f59e0b; }
.p-stat-delta { font-size: .72rem; margin-top: .2rem; }
.p-stat-delta.up   { color: #10b981; }
.p-stat-delta.down { color: #ef4444; }
.preview-chart-row {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 1rem;
}
.p-chart-box {
    background: rgba(255,255,255,.03);
    border: 1px solid rgba(255,255,255,.07);
    border-radius: 12px;
    padding: 1rem;
    height: 120px;
    overflow: hidden;
    position: relative;
}
.p-chart-label { font-size: .72rem; color: var(--text-muted); margin-bottom: .5rem; }
.mini-bars {
    display: flex;
    align-items: flex-end;
    gap: 5px;
    height: 75px;
}
.mini-bar {
    flex: 1;
    border-radius: 4px 4px 0 0;
    animation: barGrow .8s ease both;
}
@keyframes barGrow {
    from { height: 0 !important; }
}
.bar-g { background: linear-gradient(180deg,#10b981,rgba(16,185,129,.3)); }
.bar-p { background: linear-gradient(180deg,#6366f1,rgba(99,102,241,.3)); }
.p-pipe-box {
    background: rgba(255,255,255,.03);
    border: 1px solid rgba(255,255,255,.07);
    border-radius: 12px;
    padding: 1rem;
    height: 120px;
    display: flex;
    flex-direction: column;
    gap: .5rem;
    justify-content: center;
}
.p-pipe-item {
    display: flex;
    align-items: center;
    gap: .6rem;
    font-size: .78rem;
}
.p-pipe-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.p-pipe-bar { flex: 1; height: 4px; background: rgba(255,255,255,.06); border-radius: 999px; overflow: hidden; }
.p-pipe-fill { height: 100%; border-radius: 999px; animation: fillWidth 1.5s ease both; }
@keyframes fillWidth {
    from { width: 0 !important; }
}

@keyframes fadeSlideDown {
    from { opacity: 0; transform: translateY(-20px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ============================================================
   SCROLL INDICATOR
   ============================================================ */
.scroll-hint {
    position: absolute;
    bottom: 2rem;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .4rem;
    color: var(--text-muted);
    font-size: .78rem;
    letter-spacing: .08em;
    text-transform: uppercase;
    animation: scrollBounce 2s ease-in-out infinite;
}
.scroll-hint svg { opacity: .5; }
@keyframes scrollBounce {
    0%, 100% { transform: translateX(-50%) translateY(0); }
    50%       { transform: translateX(-50%) translateY(8px); }
}

/* ============================================================
   STATS TICKER
   ============================================================ */
.stats-ticker {
    background: rgba(17,24,39,.7);
    backdrop-filter: blur(16px);
    border-top: 1px solid rgba(255,255,255,.06);
    border-bottom: 1px solid rgba(255,255,255,.06);
    padding: 2.5rem 1.5rem;
}
.stats-ticker-inner {
    max-width: 1100px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: repeat(4,1fr);
    gap: 1rem;
    text-align: center;
}
.ticker-item { padding: 1.5rem 1rem; position: relative; }
.ticker-item:not(:last-child)::after {
    content: '';
    position: absolute;
    right: 0; top: 20%; height: 60%;
    width: 1px;
    background: rgba(255,255,255,.06);
}
.ticker-num {
    font-size: 2.8rem;
    font-weight: 800;
    background: linear-gradient(135deg,#10b981,#6366f1);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    line-height: 1;
    margin-bottom: .4rem;
}
.ticker-label { font-size: .85rem; color: var(--text-muted); font-weight: 500; }

/* ============================================================
   PIPELINE / HOW IT WORKS
   ============================================================ */
.pipeline-section {
    padding: 6rem 1.5rem;
    max-width: 1100px;
    margin: 0 auto;
}
.section-chip {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    background: rgba(99,102,241,.1);
    border: 1px solid rgba(99,102,241,.2);
    color: #818cf8;
    font-size: .78rem;
    font-weight: 700;
    letter-spacing: .1em;
    text-transform: uppercase;
    padding: .35rem .9rem;
    border-radius: 999px;
    margin-bottom: 1.25rem;
}
.section-heading {
    font-size: clamp(1.8rem, 3vw, 2.8rem);
    font-weight: 800;
    letter-spacing: -.025em;
    margin-bottom: .75rem;
}
.section-sub {
    color: var(--text-muted);
    font-size: 1rem;
    max-width: 540px;
    line-height: 1.7;
    margin-bottom: 3.5rem;
}
.pipeline-steps {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 0;
    position: relative;
}
.pipeline-steps::before {
    content: '';
    position: absolute;
    top: 36px;
    left: 10%;
    width: 80%;
    height: 2px;
    background: linear-gradient(90deg, rgba(16,185,129,.3), rgba(99,102,241,.3), rgba(16,185,129,.3));
    z-index: 0;
}
.pipe-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    position: relative;
    z-index: 1;
    padding: 0 .5rem;
}
.pipe-icon-wrap {
    width: 72px; height: 72px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    margin-bottom: 1rem;
    border: 2px solid transparent;
    background: linear-gradient(rgba(17,24,39,1), rgba(17,24,39,1)) padding-box,
                linear-gradient(135deg, rgba(16,185,129,.6), rgba(99,102,241,.6)) border-box;
    box-shadow: 0 0 20px rgba(16,185,129,.1);
    transition: all .4s ease;
}
.pipe-step:hover .pipe-icon-wrap {
    transform: translateY(-6px);
    box-shadow: 0 12px 30px rgba(16,185,129,.25);
}
.pipe-step h4 { font-size: .9rem; font-weight: 700; margin-bottom: .35rem; }
.pipe-step p  { font-size: .78rem; color: var(--text-muted); line-height: 1.5; }

/* arrow dots */
.pipe-arrow {
    position: absolute;
    top: 35px;
    right: -8px;
    width: 16px; height: 16px;
    background: rgba(17,24,39,1);
    border: 2px solid rgba(16,185,129,.4);
    border-radius: 50%;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: center;
}
.pipe-arrow::after {
    content: '';
    width: 6px; height: 6px;
    border-radius: 50%;
    background: #10b981;
    box-shadow: 0 0 8px #10b981;
    animation: pulse 1.8s ease-in-out infinite;
}

/* ============================================================
   FEATURES SECTION
   ============================================================ */
.features-section {
    padding: 6rem 1.5rem;
    background: rgba(17,24,39,.4);
    border-top: 1px solid rgba(255,255,255,.04);
    border-bottom: 1px solid rgba(255,255,255,.04);
}
.features-inner { max-width: 1100px; margin: 0 auto; }
.features-header { max-width: 560px; }
.features-grid-new {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
    margin-top: 3rem;
}
.feat-card {
    background: rgba(17,24,39,.6);
    border: 1px solid rgba(255,255,255,.06);
    border-radius: 16px;
    padding: 2rem;
    transition: all .35s ease;
    position: relative;
    overflow: hidden;
    backdrop-filter: blur(12px);
    cursor: default;
}
.feat-card::before {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 16px;
    background: linear-gradient(135deg, rgba(16,185,129,.06), rgba(99,102,241,.06));
    opacity: 0;
    transition: opacity .35s ease;
}
.feat-card:hover::before { opacity: 1; }
.feat-card:hover {
    transform: translateY(-6px);
    border-color: rgba(16,185,129,.25);
    box-shadow: 0 20px 50px rgba(0,0,0,.4), 0 0 0 1px rgba(16,185,129,.08);
}
.feat-card-glow {
    position: absolute;
    top: -30px; right: -30px;
    width: 100px; height: 100px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(16,185,129,.15), transparent 70%);
    transition: all .35s ease;
}
.feat-card:hover .feat-card-glow { transform: scale(1.5); opacity: .8; }

.feat-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 1.25rem;
    background: linear-gradient(135deg, rgba(16,185,129,.15), rgba(99,102,241,.15));
    border: 1px solid rgba(255,255,255,.06);
}
.feat-card h3 { font-size: 1rem; font-weight: 700; margin-bottom: .5rem; }
.feat-card p  { font-size: .88rem; color: var(--text-muted); line-height: 1.65; }
.feat-tag {
    display: inline-block;
    margin-top: 1rem;
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: #10b981;
    background: rgba(16,185,129,.08);
    border: 1px solid rgba(16,185,129,.15);
    padding: .2rem .7rem;
    border-radius: 999px;
}

/* ============================================================
   ROLES SECTION
   ============================================================ */
.roles-section {
    padding: 6rem 1.5rem;
    max-width: 1100px;
    margin: 0 auto;
}
.roles-grid-new {
    display: grid;
    grid-template-columns: repeat(4,1fr);
    gap: 1.25rem;
    margin-top: 3rem;
}
.role-card-new {
    background: rgba(17,24,39,.7);
    border: 1px solid rgba(255,255,255,.06);
    border-radius: 16px;
    padding: 2rem 1.5rem;
    text-align: center;
    transition: all .35s ease;
    position: relative;
    overflow: hidden;
}
.role-card-new::after {
    content: '';
    position: absolute;
    bottom: 0; left: 0;
    width: 100%; height: 3px;
    background: linear-gradient(90deg, #10b981, #6366f1);
    transform: scaleX(0);
    transform-origin: left;
    transition: transform .35s ease;
}
.role-card-new:hover::after { transform: scaleX(1); }
.role-card-new:hover {
    transform: translateY(-6px);
    border-color: rgba(99,102,241,.2);
    box-shadow: 0 20px 40px rgba(0,0,0,.4);
}
.role-emoji-new {
    width: 64px; height: 64px;
    border-radius: 50%;
    background: linear-gradient(135deg, rgba(16,185,129,.1), rgba(99,102,241,.1));
    border: 1px solid rgba(255,255,255,.07);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    margin: 0 auto 1.25rem;
}
.role-card-new h4 { font-size: 1.05rem; font-weight: 700; margin-bottom: .5rem; }
.role-card-new p  { font-size: .85rem; color: var(--text-muted); line-height: 1.6; }

/* ============================================================
   CTA SECTION
   ============================================================ */
.cta-section-new {
    padding: 6rem 1.5rem;
    position: relative;
    overflow: hidden;
}
.cta-inner {
    max-width: 700px;
    margin: 0 auto;
    text-align: center;
    position: relative;
    z-index: 1;
}
.cta-glow {
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%,-50%);
    width: 700px; height: 300px;
    border-radius: 50%;
    background: radial-gradient(ellipse, rgba(16,185,129,.08) 0%, rgba(99,102,241,.06) 50%, transparent 70%);
    filter: blur(30px);
}
.cta-section-new h2 {
    font-size: clamp(2rem, 4vw, 3.2rem);
    font-weight: 800;
    letter-spacing: -.03em;
    margin-bottom: 1rem;
    line-height: 1.15;
}
.cta-section-new p {
    color: var(--text-muted);
    font-size: 1.05rem;
    margin-bottom: 2.5rem;
    line-height: 1.75;
}
.cta-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

/* ============================================================
   FOOTER UPGRADE
   ============================================================ */
footer {
    background: rgba(8,12,20,.95) !important;
    border-top: 1px solid rgba(255,255,255,.05) !important;
    padding: 2.5rem 1.5rem !important;
}
.footer-inner {
    max-width: 1100px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}
.footer-brand { font-size: 1.15rem; font-weight: 700; }
.footer-brand span { color: #10b981; }
.footer-links { display: flex; gap: 1.5rem; }
.footer-links a { font-size: .85rem; color: var(--text-muted); transition: color .2s; }
.footer-links a:hover { color: var(--text-main); }
.footer-copy { font-size: .82rem; color: rgba(156,163,175,.5); }

/* ============================================================
   RESPONSIVE
   ============================================================ */
@media (max-width: 900px) {
    .features-grid-new { grid-template-columns: repeat(2,1fr); }
    .roles-grid-new    { grid-template-columns: repeat(2,1fr); }
    .pipeline-steps    { grid-template-columns: repeat(3,1fr); gap: 1.5rem; }
    .pipeline-steps::before { display: none; }
    .pipe-arrow        { display: none; }
    .preview-stats-row { grid-template-columns: repeat(2,1fr); }
    .preview-chart-row { grid-template-columns: 1fr; }
    .stats-ticker-inner{ grid-template-columns: repeat(2,1fr); }
}
@media (max-width: 600px) {
    .features-grid-new { grid-template-columns: 1fr; }
    .roles-grid-new    { grid-template-columns: 1fr; }
    .pipeline-steps    { grid-template-columns: 1fr; }
    .stats-ticker-inner{ grid-template-columns: 1fr; }
    .hero-title        { font-size: 2.6rem; }
    .footer-inner      { flex-direction: column; text-align: center; }
}
</style>

<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- Background Blobs -->
<div class="blob blob-1"></div>
<div class="blob blob-2"></div>
<div class="blob blob-3"></div>

<div class="landing-wrap">

    <!-- ===================== HERO ===================== -->
    <section class="hero-section">
        <div class="hero-badge">
            <span class="badge-dot"></span>
            Somalia's #1 Food Supply Chain Platform
        </div>

        <h1 class="hero-title">
            From <span class="hero-gradient">Farm to Table</span>
            <span class="line2">Tracked Every Step</span>
        </h1>

        <p class="hero-sub">
            SahanFresh connects local farmers, suppliers, and customers across Somalia with real-time inventory tracking, delivery management, and full supply chain transparency.
        </p>

        <div class="hero-cta">
            <a href="<?php echo BASE_URL; ?>customer/products.php" class="btn-glow">🛒 Shop Fresh Now</a>
            <a href="<?php echo BASE_URL; ?>auth/register.php" class="btn-outline-glow">Join the Network →</a>
        </div>

        <!-- Mini Dashboard Preview -->
        <div class="hero-preview">
            <div class="preview-glow"></div>
            <div class="preview-card">
                <div class="preview-topbar">
                    <span class="win-dot wd-red"></span>
                    <span class="win-dot wd-yellow"></span>
                    <span class="win-dot wd-green"></span>
                    <div class="preview-title-bar">sahanfresh.so/dashboard — SahanFresh Control Panel</div>
                </div>
                <div class="preview-stats-row">
                    <div class="p-stat">
                        <div class="p-stat-label">Active Orders</div>
                        <div class="p-stat-val green">1,284</div>
                        <div class="p-stat-delta up">↑ 12% this week</div>
                    </div>
                    <div class="p-stat">
                        <div class="p-stat-label">Inventory Batches</div>
                        <div class="p-stat-val purple">342</div>
                        <div class="p-stat-delta up">↑ 5 added today</div>
                    </div>
                    <div class="p-stat">
                        <div class="p-stat-label">Revenue</div>
                        <div class="p-stat-val amber">$48.2K</div>
                        <div class="p-stat-delta up">↑ 8.4% MTD</div>
                    </div>
                    <div class="p-stat">
                        <div class="p-stat-label">Expiry Alerts</div>
                        <div class="p-stat-val" style="color:#ef4444">7</div>
                        <div class="p-stat-delta down">↓ 3 resolved</div>
                    </div>
                </div>
                <div class="preview-chart-row">
                    <div class="p-chart-box">
                        <div class="p-chart-label">Weekly Sales Volume</div>
                        <div class="mini-bars">
                            <div class="mini-bar bar-g" style="height:55%"></div>
                            <div class="mini-bar bar-g" style="height:70%"></div>
                            <div class="mini-bar bar-p" style="height:45%"></div>
                            <div class="mini-bar bar-g" style="height:80%"></div>
                            <div class="mini-bar bar-p" style="height:65%"></div>
                            <div class="mini-bar bar-g" style="height:90%"></div>
                            <div class="mini-bar bar-p" style="height:72%"></div>
                            <div class="mini-bar bar-g" style="height:60%"></div>
                            <div class="mini-bar bar-g" style="height:85%"></div>
                            <div class="mini-bar bar-p" style="height:50%"></div>
                            <div class="mini-bar bar-g" style="height:95%"></div>
                            <div class="mini-bar bar-p" style="height:78%"></div>
                        </div>
                    </div>
                    <div class="p-pipe-box">
                        <div class="p-chart-label">Supply Chain Health</div>
                        <div class="p-pipe-item">
                            <div class="p-pipe-dot" style="background:#10b981;box-shadow:0 0 6px #10b981"></div>
                            <span style="font-size:.72rem;min-width:55px;color:var(--text-muted)">Inventory</span>
                            <div class="p-pipe-bar"><div class="p-pipe-fill" style="width:92%;background:#10b981"></div></div>
                            <span style="font-size:.7rem;color:var(--text-muted)">92%</span>
                        </div>
                        <div class="p-pipe-item">
                            <div class="p-pipe-dot" style="background:#6366f1;box-shadow:0 0 6px #6366f1"></div>
                            <span style="font-size:.72rem;min-width:55px;color:var(--text-muted)">Logistics</span>
                            <div class="p-pipe-bar"><div class="p-pipe-fill" style="width:78%;background:#6366f1"></div></div>
                            <span style="font-size:.7rem;color:var(--text-muted)">78%</span>
                        </div>
                        <div class="p-pipe-item">
                            <div class="p-pipe-dot" style="background:#f59e0b;box-shadow:0 0 6px #f59e0b"></div>
                            <span style="font-size:.72rem;min-width:55px;color:var(--text-muted)">Payments</span>
                            <div class="p-pipe-bar"><div class="p-pipe-fill" style="width:85%;background:#f59e0b"></div></div>
                            <span style="font-size:.7rem;color:var(--text-muted)">85%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Scroll Hint -->
        <div class="scroll-hint">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12l7 7 7-7"/></svg>
            Scroll to explore
        </div>
    </section>

    <!-- ===================== STATS TICKER ===================== -->
    <div class="stats-ticker">
        <div class="stats-ticker-inner">
            <div class="ticker-item">
                <div class="ticker-num" data-target="500">0</div>
                <div class="ticker-label">Tons Tracked Monthly</div>
            </div>
            <div class="ticker-item">
                <div class="ticker-num" data-target="1200">0</div>
                <div class="ticker-label">Orders Fulfilled</div>
            </div>
            <div class="ticker-item">
                <div class="ticker-num" data-target="100">0</div>
                <div class="ticker-label">% Real-Time Visibility</div>
            </div>
            <div class="ticker-item">
                <div class="ticker-num" data-target="7">0</div>
                <div class="ticker-label">Supported User Roles</div>
            </div>
        </div>
    </div>

    <!-- ===================== PIPELINE ===================== -->
    <section class="pipeline-section">
        <div class="section-chip">⚡ How It Works</div>
        <h2 class="section-heading">The SahanFresh Supply Chain</h2>
        <p class="section-sub">Every batch of food is traceable from harvest to your doorstep — no gaps, no guesswork.</p>

        <div class="pipeline-steps">
            <div class="pipe-step">
                <div class="pipe-icon-wrap">🌾</div>
                <h4>Harvest</h4>
                <p>Farmers log produce into the system with batch IDs and harvest dates.</p>
                <div class="pipe-arrow"></div>
            </div>
            <div class="pipe-step">
                <div class="pipe-icon-wrap">🏭</div>
                <h4>Processing</h4>
                <p>Suppliers receive, quality-check, and register batches into cold storage.</p>
                <div class="pipe-arrow"></div>
            </div>
            <div class="pipe-step">
                <div class="pipe-icon-wrap">📦</div>
                <h4>Warehousing</h4>
                <p>Inventory is tracked by expiry date with automated alerts for spoilage risk.</p>
                <div class="pipe-arrow"></div>
            </div>
            <div class="pipe-step">
                <div class="pipe-icon-wrap">🚚</div>
                <h4>Delivery</h4>
                <p>Drivers update statuses in real-time; customers track via a live map.</p>
                <div class="pipe-arrow"></div>
            </div>
            <div class="pipe-step">
                <div class="pipe-icon-wrap">🛒</div>
                <h4>Customer</h4>
                <p>Fresh produce delivered with full traceability receipt and payment record.</p>
            </div>
        </div>
    </section>

    <!-- ===================== FEATURES ===================== -->
    <section class="features-section">
        <div class="features-inner">
            <div class="features-header">
                <div class="section-chip">✦ Platform Features</div>
                <h2 class="section-heading">Built for the Modern Food Chain</h2>
                <p class="section-sub">Purpose-built tools that eliminate waste, accelerate delivery, and make every transaction transparent.</p>
            </div>
            <div class="features-grid-new">
                <div class="feat-card">
                    <div class="feat-card-glow"></div>
                    <div class="feat-icon">📦</div>
                    <h3>Batch Tracking</h3>
                    <p>Every food item tagged with batch number, expiry date, and storage status from warehouse to delivery point.</p>
                    <span class="feat-tag">FIFO Inventory</span>
                </div>
                <div class="feat-card">
                    <div class="feat-card-glow"></div>
                    <div class="feat-icon">🚚</div>
                    <h3>Live Delivery</h3>
                    <p>Customers see real-time order status as drivers move from pickup to doorstep — no more guessing.</p>
                    <span class="feat-tag">Real-Time</span>
                </div>
                <div class="feat-card">
                    <div class="feat-card-glow"></div>
                    <div class="feat-icon">⚠️</div>
                    <h3>Expiry Alerts</h3>
                    <p>Automated alerts flag batches nearing expiry, helping reduce waste and keep food safe for consumers.</p>
                    <span class="feat-tag">AI-Assisted</span>
                </div>
                <div class="feat-card">
                    <div class="feat-card-glow"></div>
                    <div class="feat-icon">📊</div>
                    <h3>Smart Reports</h3>
                    <p>Admin dashboards with sales analytics, supplier performance, and inventory health visualizations.</p>
                    <span class="feat-tag">Analytics</span>
                </div>
                <div class="feat-card">
                    <div class="feat-card-glow"></div>
                    <div class="feat-icon">🤝</div>
                    <h3>Supplier Portal</h3>
                    <p>Suppliers manage their own batches, purchase orders, and stock replenishment seamlessly.</p>
                    <span class="feat-tag">Self-Service</span>
                </div>
                <div class="feat-card">
                    <div class="feat-card-glow"></div>
                    <div class="feat-icon">💳</div>
                    <h3>Flexible Payments</h3>
                    <p>Supports Cash, EVC Plus, Sahal Mobile Money, and Card — covering every Somali payment method.</p>
                    <span class="feat-tag">Multi-Method</span>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================== ROLES ===================== -->
    <section class="roles-section">
        <div class="section-chip">👥 Who Uses SahanFresh</div>
        <h2 class="section-heading">A Platform for Everyone in the Chain</h2>
        <p class="section-sub">Four distinct role-based portals, each tailored to what that user actually needs.</p>

        <div class="roles-grid-new">
            <div class="role-card-new">
                <div class="role-emoji-new">👨‍💼</div>
                <h4>Admin</h4>
                <p>Full system control — manage users, products, suppliers, orders, and system-wide analytics.</p>
            </div>
            <div class="role-card-new">
                <div class="role-emoji-new">🌾</div>
                <h4>Supplier</h4>
                <p>Manage inventory batches, receive purchase orders, and track produce shipments to warehouse.</p>
            </div>
            <div class="role-card-new">
                <div class="role-emoji-new">🚗</div>
                <h4>Driver</h4>
                <p>View assigned deliveries, update statuses, and log delivery notes on the go from any device.</p>
            </div>
            <div class="role-card-new">
                <div class="role-emoji-new">🛒</div>
                <h4>Customer</h4>
                <p>Browse fresh food, place orders, and track delivery in real time with full traceability.</p>
            </div>
        </div>
    </section>

    <!-- ===================== CTA ===================== -->
    <section class="cta-section-new">
        <div class="cta-glow"></div>
        <div class="cta-inner">
            <h2>Ready to Transform Your<br><span class="hero-gradient">Food Supply Chain?</span></h2>
            <p>Join hundreds of farmers, suppliers, and customers already using SahanFresh to bring fresh, traceable food to every Somali table.</p>
            <div class="cta-buttons">
                <a href="<?php echo BASE_URL; ?>auth/register.php" class="btn-glow" style="font-size:1.05rem;">Create Free Account</a>
                <a href="<?php echo BASE_URL; ?>auth/login.php" class="btn-outline-glow" style="font-size:1.05rem;">Sign In →</a>
            </div>
        </div>
    </section>

</div><!-- /landing-wrap -->

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
/* ===== Animated Number Counter ===== */
function animateCounter(el) {
    const target = parseInt(el.dataset.target, 10);
    const suffix = target === 100 ? '%' : (target >= 1000 ? '+' : '+');
    const duration = 1800;
    const step = target / (duration / 16);
    let current = 0;
    const tick = () => {
        current = Math.min(current + step, target);
        el.textContent = Math.floor(current).toLocaleString() + suffix;
        if (current < target) requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
}

const counters = document.querySelectorAll('.ticker-num[data-target]');
const io = new IntersectionObserver((entries) => {
    entries.forEach(e => {
        if (e.isIntersecting) {
            animateCounter(e.target);
            io.unobserve(e.target);
        }
    });
}, { threshold: .5 });
counters.forEach(c => io.observe(c));

/* ===== Stagger scroll-in for cards ===== */
const cards = document.querySelectorAll('.feat-card, .role-card-new, .pipe-step');
const cardIO = new IntersectionObserver((entries) => {
    entries.forEach((e, i) => {
        if (e.isIntersecting) {
            e.target.style.animationDelay = (i * 0.08) + 's';
            e.target.classList.add('card-visible');
            cardIO.unobserve(e.target);
        }
    });
}, { threshold: .15 });
cards.forEach(c => {
    c.style.opacity = '0';
    c.style.transform = 'translateY(30px)';
    c.style.transition = 'opacity .6s ease, transform .6s ease';
    cardIO.observe(c);
});

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.feat-card, .role-card-new, .pipe-step').forEach(c => {
        c.style.opacity = '';
        c.style.transform = '';
    });
});

const styleSheet = document.createElement('style');
styleSheet.textContent = `.card-visible { opacity: 1 !important; transform: translateY(0) !important; }`;
document.head.appendChild(styleSheet);
</script>

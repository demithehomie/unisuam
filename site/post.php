<?php
/**
 * UNISUAM – post.php
 * Full blog post page with likes, shares, comments and related posts.
 */
require_once __DIR__ . '/config.php';

// ── Fetch post ────────────────────────────────────────────────────────────────
$slug = preg_replace('/[^a-z0-9\-]/', '', strtolower(trim($_GET['slug'] ?? '')));
if (!$slug) {
    http_response_code(302);
    header('Location: blog.html');
    exit;
}

$stmt = $db->prepare('SELECT * FROM posts WHERE slug = ?');
$stmt->execute([$slug]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
}

// ── View counter (throttle per session) ───────────────────────────────────────
if ($post) {
    session_start();
    $sess_key = 'viewed_' . $post['id'];
    if (empty($_SESSION[$sess_key])) {
        $db->prepare('UPDATE posts SET views = views + 1 WHERE id = ?')->execute([$post['id']]);
        $_SESSION[$sess_key] = true;
        $post['views'] += 1;
    }
}

// ── Related posts ─────────────────────────────────────────────────────────────
$related = [];
if ($post) {
    $rel = $db->prepare(
        'SELECT id, slug, title, excerpt, category, emoji, author, created_at
         FROM posts WHERE id != ? ORDER BY RANDOM() LIMIT 3'
    );
    $rel->execute([$post['id']]);
    $related = $rel->fetchAll();
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function estimateReadTime(string $content): int {
    $words = str_word_count(strip_tags($content));
    return max(1, (int) ceil($words / 200));
}

function formatDatePT(string $datetime): string {
    $months = ['jan','fev','mar','abr','mai','jun','jul','ago','set','out','nov','dez'];
    $ts = strtotime($datetime);
    return date('d', $ts) . ' de ' . $months[(int)date('n', $ts) - 1] . '. de ' . date('Y', $ts);
}

function categoryColor(string $cat): string {
    return match($cat) {
        'Inovação'       => '#2563EB',
        'Vestibular'     => '#FF4D00',
        'Vida Acadêmica' => '#059669',
        'Pesquisa'       => '#7C3AED',
        'Sustentabilidade' => '#0891B2',
        'Institucional'  => '#6B7280',
        default          => '#FF4D00',
    };
}

function categoryBg(string $cat): string {
    return match($cat) {
        'Inovação'       => 'linear-gradient(135deg,#1e3a8a 0%,#2563EB 100%)',
        'Vestibular'     => 'linear-gradient(135deg,#7c1d00 0%,#FF4D00 100%)',
        'Vida Acadêmica' => 'linear-gradient(135deg,#064e3b 0%,#059669 100%)',
        'Pesquisa'       => 'linear-gradient(135deg,#3b0764 0%,#7C3AED 100%)',
        'Sustentabilidade' => 'linear-gradient(135deg,#164e63 0%,#0891B2 100%)',
        'Institucional'  => 'linear-gradient(135deg,#1f2937 0%,#6B7280 100%)',
        default          => 'linear-gradient(135deg,#7c1d00 0%,#FF4D00 100%)',
    };
}

$read_time = $post ? estimateReadTime($post['content'] ?? '') : 1;
$cat_color = $post ? categoryColor($post['category']) : '#FF4D00';
$cat_bg    = $post ? categoryBg($post['category'])    : 'linear-gradient(135deg,#7c1d00,#FF4D00)';
?><!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $post ? htmlspecialchars($post['title']) . ' – UNISUAM Blog' : '404 – Post não encontrado | UNISUAM' ?></title>
<meta name="description" content="<?= $post ? htmlspecialchars($post['excerpt'] ?? '') : 'Página não encontrada' ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Familjen+Grotesk:ital,wght@0,400;0,600;0,700;1,400&family=Epilogue:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
<style>
/* ── Design Tokens ──────────────────────────────────────────────────────────── */
:root {
  --o: #FF4D00; --o2: #FF6B2B; --og: rgba(255,77,0,.12);
  --bg: #F8F6F2; --bg2: #EFEDE8; --card: #FFFFFF;
  --surf: #E8E6E1; --surf2: #DDD9D2;
  --t1: #0C0C0B; --t2: #5A564F; --t3: #9C9891;
  --brd: rgba(0,0,0,.07); --brd2: rgba(0,0,0,.13);
  --fd: 'Familjen Grotesk', sans-serif;
  --fb: 'Epilogue', sans-serif;
  --r: 14px; --rs: 8px; --rp: 999px;
  --ease: cubic-bezier(.4,0,.2,1);
}
[data-theme="dark"] {
  --bg: #0E0E0D; --bg2: #141412; --card: #1C1B19;
  --surf: #252420; --surf2: #2E2D29;
  --t1: #F2EFE9; --t2: #908C84; --t3: #5A564F;
  --brd: rgba(255,255,255,.06); --brd2: rgba(255,255,255,.11);
}

/* ── Reset & Base ───────────────────────────────────────────────────────────── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{font-size:16px;scroll-behavior:smooth;-webkit-text-size-adjust:100%}
body{font-family:var(--fd);background:var(--bg);color:var(--t1);line-height:1.6;transition:background .3s var(--ease),color .3s var(--ease)}
img{max-width:100%;display:block}
a{color:inherit;text-decoration:none}
button{font-family:inherit;cursor:pointer;border:none;background:none}

/* ── Nav ────────────────────────────────────────────────────────────────────── */
.nav{position:sticky;top:0;z-index:100;background:rgba(10,10,9,.92);border-bottom:1px solid rgba(255,255,255,.07);backdrop-filter:blur(22px);-webkit-backdrop-filter:blur(22px)}
.nav-inner{max-width:1200px;margin:0 auto;padding:0 24px;height:64px;display:flex;align-items:center;gap:32px}
.nav-logo{display:flex;align-items:center;flex-shrink:0}
.nav-logo img{height:30px;width:auto;display:block;object-fit:contain}
.nav-links{display:flex;gap:24px;margin-left:auto}
.nav-links a{font-size:.875rem;font-weight:600;color:rgba(255,255,255,.6);transition:color .2s}
.nav-links a:hover{color:#fff}
.nav-actions{display:flex;align-items:center;gap:12px;margin-left:24px}
.btn-theme{width:36px;height:36px;border-radius:var(--rp);background:var(--surf);display:flex;align-items:center;justify-content:center;font-size:1rem;transition:background .2s}
.btn-theme:hover{background:var(--surf2)}
.btn-cta{padding:8px 18px;border-radius:var(--rp);background:var(--o);color:#fff;font-size:.875rem;font-weight:700;transition:background .2s,transform .15s}
.btn-cta:hover{background:var(--o2);transform:translateY(-1px)}
.nav-hamburger{display:none;width:36px;height:36px;border-radius:var(--rs);background:var(--surf);align-items:center;justify-content:center;font-size:1.25rem}
.mobile-menu{display:none;flex-direction:column;background:var(--card);border-bottom:1px solid var(--brd);padding:16px 24px;gap:16px}
.mobile-menu a{font-size:1rem;font-weight:600;color:var(--t2);padding:8px 0;border-bottom:1px solid var(--brd)}
.mobile-menu.open{display:flex}

/* ── Hero ───────────────────────────────────────────────────────────────────── */
.post-hero{width:100%;padding:72px 24px 56px;display:flex;flex-direction:column;align-items:center;text-align:center;<?= $post ? 'background:' . $cat_bg . ';' : 'background:var(--surf);' ?>position:relative;overflow:hidden}
.post-hero::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse at center,rgba(255,255,255,.07) 0%,transparent 70%)}
.post-hero-emoji{font-size:5rem;line-height:1;margin-bottom:24px;filter:drop-shadow(0 8px 24px rgba(0,0,0,.3))}
.post-hero-category{display:inline-flex;align-items:center;padding:6px 16px;border-radius:var(--rp);background:rgba(255,255,255,.18);backdrop-filter:blur(8px);font-size:.8rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#fff;margin-bottom:20px}
.post-hero-title{font-size:clamp(1.75rem,4vw,3rem);font-weight:700;color:#fff;line-height:1.15;max-width:800px;letter-spacing:-.02em;text-shadow:0 2px 12px rgba(0,0,0,.25)}
.post-hero-meta{display:flex;flex-wrap:wrap;justify-content:center;gap:20px;margin-top:24px}
.post-hero-meta span{font-size:.875rem;color:rgba(255,255,255,.8);display:flex;align-items:center;gap:6px}

/* ── Content layout ─────────────────────────────────────────────────────────── */
.post-wrap{max-width:800px;margin:0 auto;padding:56px 24px}
.post-content{font-family:var(--fb);font-size:1.125rem;line-height:1.8;color:var(--t1)}
.post-content p{margin-bottom:1.5em}
.post-content strong{font-weight:600;color:var(--t1)}
.post-content em{font-style:italic}
.post-content h2{font-family:var(--fd);font-size:1.5rem;font-weight:700;color:var(--t1);margin:2em 0 .75em;letter-spacing:-.02em}
.post-content h3{font-family:var(--fd);font-size:1.25rem;font-weight:600;color:var(--t1);margin:1.75em 0 .5em}
.post-content ul,
.post-content ol{padding-left:1.5em;margin-bottom:1.5em}
.post-content li{margin-bottom:.5em}
.post-content blockquote{border-left:3px solid var(--o);padding:16px 20px;background:var(--og);border-radius:0 var(--rs) var(--rs) 0;margin:2em 0;font-style:italic;color:var(--t2)}

/* ── Divider ────────────────────────────────────────────────────────────────── */
.post-divider{height:1px;background:var(--brd2);margin:48px 0}

/* ── Like section ───────────────────────────────────────────────────────────── */
.like-section{display:flex;flex-direction:column;align-items:center;gap:12px;padding:32px 0}
.like-btn{display:flex;align-items:center;gap:10px;padding:14px 28px;border-radius:var(--rp);background:var(--surf);border:2px solid var(--brd2);font-size:1rem;font-weight:700;color:var(--t2);transition:all .25s var(--ease);font-family:var(--fd)}
.like-btn:hover{background:rgba(255,77,0,.08);border-color:var(--o);color:var(--o);transform:scale(1.04)}
.like-btn.liked{background:rgba(255,77,0,.12);border-color:var(--o);color:var(--o)}
.like-btn .heart{font-size:1.5rem;transition:transform .3s var(--ease)}
.like-btn.pop .heart{animation:heartpop .45s var(--ease)}
@keyframes heartpop{0%{transform:scale(1)}40%{transform:scale(1.5)}70%{transform:scale(.9)}100%{transform:scale(1)}}
.like-label{font-size:.85rem;color:var(--t3)}

/* ── Share section ──────────────────────────────────────────────────────────── */
.share-section{padding:24px 0}
.share-title{font-size:.8rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--t3);margin-bottom:16px}
.share-row{display:flex;flex-wrap:wrap;gap:10px}
.share-btn{display:flex;align-items:center;gap:8px;padding:10px 18px;border-radius:var(--rp);background:var(--surf);border:1px solid var(--brd2);font-size:.875rem;font-weight:600;color:var(--t2);transition:all .2s var(--ease);cursor:pointer;font-family:var(--fd)}
.share-btn:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,.1)}
.share-btn.whatsapp:hover{background:#25D366;border-color:#25D366;color:#fff}
.share-btn.linkedin:hover{background:#0A66C2;border-color:#0A66C2;color:#fff}
.share-btn.twitter:hover{background:#000;border-color:#000;color:#fff}
.share-btn.copy:hover{background:var(--o);border-color:var(--o);color:#fff}
.share-btn.copy.copied{background:var(--o);border-color:var(--o);color:#fff}

/* ── Comments ───────────────────────────────────────────────────────────────── */
.comments-section{padding:40px 0 0}
.section-title{font-size:1.5rem;font-weight:700;color:var(--t1);margin-bottom:24px;letter-spacing:-.02em}
.comment-list{display:flex;flex-direction:column;gap:16px;margin-bottom:40px}
.comment-card{display:flex;gap:14px;padding:20px;background:var(--card);border-radius:var(--r);border:1px solid var(--brd)}
.comment-avatar{width:42px;height:42px;border-radius:var(--rp);display:flex;align-items:center;justify-content:center;font-size:1.1rem;font-weight:700;color:#fff;flex-shrink:0;text-transform:uppercase}
.comment-body{flex:1;min-width:0}
.comment-header{display:flex;align-items:baseline;gap:10px;margin-bottom:6px;flex-wrap:wrap}
.comment-name{font-size:.95rem;font-weight:700;color:var(--t1)}
.comment-time{font-size:.8rem;color:var(--t3)}
.comment-text{font-size:.9rem;color:var(--t2);line-height:1.6;word-break:break-word}
.no-comments{text-align:center;padding:32px;color:var(--t3);font-size:.9rem}

/* Comment form */
.comment-form{background:var(--card);border:1px solid var(--brd);border-radius:var(--r);padding:28px}
.form-title{font-size:1.1rem;font-weight:700;color:var(--t1);margin-bottom:20px}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px}
.form-group{display:flex;flex-direction:column;gap:6px}
.form-group.full{grid-column:1/-1}
.form-label{font-size:.8rem;font-weight:600;color:var(--t2);letter-spacing:.04em}
.form-input,
.form-textarea{padding:10px 14px;border-radius:var(--rs);border:1.5px solid var(--brd2);background:var(--bg);color:var(--t1);font-size:.9rem;font-family:var(--fd);transition:border-color .2s;outline:none;resize:vertical}
.form-input:focus,
.form-textarea:focus{border-color:var(--o)}
.form-textarea{min-height:110px}
.hp-field{display:none!important}
.form-submit{width:100%;padding:13px;border-radius:var(--rp);background:var(--o);color:#fff;font-size:1rem;font-weight:700;font-family:var(--fd);cursor:pointer;border:none;transition:background .2s,transform .15s;margin-top:4px}
.form-submit:hover{background:var(--o2);transform:translateY(-1px)}
.form-submit:disabled{opacity:.6;cursor:not-allowed;transform:none}
.form-msg{margin-top:12px;font-size:.875rem;border-radius:var(--rs);padding:10px 14px;display:none}
.form-msg.success{display:block;background:rgba(5,150,105,.1);color:#059669;border:1px solid rgba(5,150,105,.2)}
.form-msg.error{display:block;background:rgba(220,38,38,.08);color:#DC2626;border:1px solid rgba(220,38,38,.15)}

/* ── Related posts ──────────────────────────────────────────────────────────── */
.related-section{background:var(--bg2);padding:64px 24px}
.related-inner{max-width:1200px;margin:0 auto}
.related-title{font-size:1.5rem;font-weight:700;color:var(--t1);margin-bottom:32px;letter-spacing:-.02em}
.related-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}
.post-card{background:var(--card);border-radius:var(--r);border:1px solid var(--brd);overflow:hidden;transition:transform .25s var(--ease),box-shadow .25s var(--ease);cursor:pointer;display:flex;flex-direction:column}
.post-card:hover{transform:translateY(-4px);box-shadow:0 12px 40px rgba(0,0,0,.1)}
.card-hero{height:100px;display:flex;align-items:center;justify-content:center;font-size:2.5rem;position:relative}
.card-body{padding:20px;flex:1;display:flex;flex-direction:column}
.card-cat{font-size:.75rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;margin-bottom:8px}
.card-title{font-size:1rem;font-weight:700;color:var(--t1);line-height:1.35;margin-bottom:8px;letter-spacing:-.01em}
.card-excerpt{font-size:.85rem;color:var(--t2);line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;flex:1}
.card-footer{display:flex;align-items:center;justify-content:space-between;margin-top:14px;padding-top:14px;border-top:1px solid var(--brd)}
.card-author{display:flex;align-items:center;gap:8px}
.card-avatar{width:26px;height:26px;border-radius:var(--rp);background:var(--o);color:#fff;font-size:.7rem;font-weight:700;display:flex;align-items:center;justify-content:center}
.card-date{font-size:.78rem;color:var(--t3)}
.card-arrow{font-size:1rem;color:var(--t3);transition:transform .2s,color .2s}
.post-card:hover .card-arrow{transform:translateX(4px);color:var(--o)}

/* ── Footer ─────────────────────────────────────────────────────────────────── */
.footer{background:var(--t1);color:rgba(255,255,255,.7);padding:56px 24px 32px}
[data-theme="dark"] .footer{background:#0a0a09}
.footer-inner{max-width:1200px;margin:0 auto}
.footer-top{display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:48px;margin-bottom:48px}
.footer-brand p{font-size:.875rem;line-height:1.7;margin-top:12px;max-width:280px}
.footer-col h4{font-size:.8rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:rgba(255,255,255,.4);margin-bottom:14px}
.footer-col a{display:block;font-size:.875rem;color:rgba(255,255,255,.6);padding:4px 0;transition:color .2s}
.footer-col a:hover{color:#fff}
.footer-logo{font-size:1.5rem;font-weight:700;color:var(--o)}
.footer-bottom{border-top:1px solid rgba(255,255,255,.08);padding-top:28px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px}
.footer-bottom p{font-size:.8rem;color:rgba(255,255,255,.35)}

/* ── Skeleton loader ────────────────────────────────────────────────────────── */
.skeleton{background:linear-gradient(90deg,var(--surf) 25%,var(--surf2) 50%,var(--surf) 75%);background-size:200% 100%;animation:shimmer 1.4s infinite;border-radius:var(--rs)}
@keyframes shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}

/* ── 404 ────────────────────────────────────────────────────────────────────── */
.not-found{min-height:60vh;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:80px 24px}
.not-found h1{font-size:clamp(2rem,6vw,4rem);font-weight:700;color:var(--t1);margin-bottom:12px}
.not-found p{font-size:1.1rem;color:var(--t2);margin-bottom:32px}

/* ── Responsive ─────────────────────────────────────────────────────────────── */
@media(max-width:900px){
  .related-grid{grid-template-columns:1fr 1fr}
  .footer-top{grid-template-columns:1fr 1fr;gap:32px}
}
@media(max-width:640px){
  .nav-links,.nav-actions .btn-cta{display:none}
  .nav-hamburger{display:flex}
  .post-hero{padding:48px 20px 40px}
  .post-hero-emoji{font-size:3.5rem}
  .post-wrap{padding:40px 20px}
  .related-grid{grid-template-columns:1fr}
  .form-grid{grid-template-columns:1fr}
  .footer-top{grid-template-columns:1fr;gap:28px}
  .footer-bottom{flex-direction:column;text-align:center}
  .share-row{gap:8px}
  .share-btn{padding:8px 14px;font-size:.8rem}
}
</style>
</head>
<body>

<!-- ── Nav ──────────────────────────────────────────────────────────────────── -->
<nav class="nav">
  <div class="nav-inner">
    <a href="index.html" class="nav-logo"><img src="unisuam-white-no-background.png" alt="UNISUAM"></a>
    <div class="nav-links">
      <a href="/">Início</a>
      <a href="/blog.html">Blog</a>
      <a href="/#cursos">Cursos</a>
      <a href="/#sobre">Sobre</a>
      <a href="/#contato">Contato</a>
    </div>
    <div class="nav-actions">
      <button class="btn-theme" id="themeBtn" aria-label="Alternar tema">🌙</button>
      <a href="/vestibular" class="btn-cta">Inscreva-se</a>
    </div>
    <button class="nav-hamburger" id="hamburgerBtn" aria-label="Menu">☰</button>
  </div>
  <div class="mobile-menu" id="mobileMenu">
    <a href="/">Início</a>
    <a href="/blog.html">Blog</a>
    <a href="/#cursos">Cursos</a>
    <a href="/#sobre">Sobre</a>
    <a href="/#contato">Contato</a>
    <a href="/vestibular" class="btn-cta" style="width:fit-content">Inscreva-se</a>
  </div>
</nav>

<?php if (!$post): ?>
<!-- ── 404 ─────────────────────────────────────────────────────────────────── -->
<div class="not-found">
  <div style="font-size:4rem;margin-bottom:16px">📭</div>
  <h1>Post não encontrado</h1>
  <p>O artigo que você procura não existe ou foi removido.</p>
  <a href="blog.html" class="btn-cta" style="display:inline-flex;text-decoration:none">← Voltar ao Blog</a>
</div>

<?php else: ?>
<!-- ── Hero ───────────────────────────────────────────────────────────────── -->
<section class="post-hero">
  <div class="post-hero-emoji"><?= htmlspecialchars($post['emoji']) ?></div>
  <span class="post-hero-category"><?= htmlspecialchars($post['category']) ?></span>
  <h1 class="post-hero-title"><?= htmlspecialchars($post['title']) ?></h1>
  <div class="post-hero-meta">
    <span>✍️ <?= htmlspecialchars($post['author']) ?></span>
    <span>📅 <?= formatDatePT($post['created_at']) ?></span>
    <span>⏱ <?= $read_time ?> min de leitura</span>
    <span>👁 <?= number_format($post['views']) ?> visualizações</span>
  </div>
</section>

<!-- ── Article body ───────────────────────────────────────────────────────── -->
<main class="post-wrap">
  <article class="post-content">
    <?= $post['content'] /* content already sanitised at seed time; user-submitted content is stripped in api */ ?>
  </article>

  <div class="post-divider"></div>

  <!-- Like section -->
  <div class="like-section">
    <button class="like-btn" id="likeBtn" aria-label="Curtir post">
      <span class="heart" id="heartIcon">🤍</span>
      <span id="likeCount">—</span>
      curtidas
    </button>
    <span class="like-label" id="likeLabel">Gostou? Deixe seu like!</span>
  </div>

  <div class="post-divider"></div>

  <!-- Share section -->
  <div class="share-section">
    <p class="share-title">Compartilhar</p>
    <div class="share-row">
      <button class="share-btn whatsapp" onclick="sharePost('whatsapp')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
        WhatsApp
      </button>
      <button class="share-btn linkedin" onclick="sharePost('linkedin')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
        LinkedIn
      </button>
      <button class="share-btn twitter" onclick="sharePost('twitter')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.748l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
        Twitter / X
      </button>
      <button class="share-btn copy" id="copyBtn" onclick="sharePost('copy')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
        <span id="copyLabel">Copiar link</span>
      </button>
    </div>
  </div>

  <div class="post-divider"></div>

  <!-- Comments -->
  <div class="comments-section">
    <h2 class="section-title">Comentários</h2>
    <div class="comment-list" id="commentList">
      <div class="no-comments" id="commentsLoading">Carregando comentários…</div>
    </div>

    <!-- Comment form -->
    <div class="comment-form">
      <p class="form-title">Deixe seu comentário</p>
      <form id="commentForm" novalidate>
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label" for="cName">Nome *</label>
            <input class="form-input" id="cName" name="name" type="text" placeholder="Seu nome" required maxlength="80">
          </div>
          <div class="form-group">
            <label class="form-label" for="cEmail">E-mail (opcional)</label>
            <input class="form-input" id="cEmail" name="email" type="email" placeholder="seu@email.com" maxlength="120">
          </div>
          <div class="form-group full">
            <label class="form-label" for="cContent">Comentário *</label>
            <textarea class="form-textarea" id="cContent" name="content" placeholder="Escreva seu comentário…" required maxlength="1000"></textarea>
          </div>
          <!-- Honeypot -->
          <div class="form-group hp-field" aria-hidden="true">
            <input class="form-input" id="hp" name="hp" type="text" tabindex="-1" autocomplete="off">
          </div>
        </div>
        <button type="submit" class="form-submit" id="submitBtn">Publicar comentário</button>
        <p class="form-msg" id="formMsg"></p>
      </form>
    </div>
  </div>
</main>

<!-- ── Related posts ──────────────────────────────────────────────────────── -->
<?php if ($related): ?>
<section class="related-section">
  <div class="related-inner">
    <h2 class="related-title">Leia também</h2>
    <div class="related-grid">
      <?php foreach ($related as $r): ?>
      <?php $rbg = categoryBg($r['category']); ?>
      <a href="post.php?slug=<?= urlencode($r['slug']) ?>" class="post-card">
        <div class="card-hero" style="background:<?= $rbg ?>">
          <span style="font-size:2.5rem"><?= htmlspecialchars($r['emoji']) ?></span>
        </div>
        <div class="card-body">
          <span class="card-cat" style="color:<?= categoryColor($r['category']) ?>"><?= htmlspecialchars($r['category']) ?></span>
          <h3 class="card-title"><?= htmlspecialchars($r['title']) ?></h3>
          <p class="card-excerpt"><?= htmlspecialchars($r['excerpt'] ?? '') ?></p>
          <div class="card-footer">
            <div class="card-author">
              <div class="card-avatar"><?= mb_strtoupper(mb_substr($r['author'], 0, 1)) ?></div>
              <span class="card-date"><?= formatDatePT($r['created_at']) ?></span>
            </div>
            <span class="card-arrow">→</span>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php endif; // end $post check ?>

<!-- ── Footer ─────────────────────────────────────────────────────────────── -->
<footer class="footer">
  <div class="footer-inner">
    <div class="footer-top">
      <div class="footer-brand">
        <div class="footer-logo">UNISUAM</div>
        <p>Transformando vidas e a Zona Oeste do Rio de Janeiro há mais de 50 anos com educação de qualidade e compromisso social.</p>
      </div>
      <div class="footer-col">
        <h4>Graduação</h4>
        <a href="#">Saúde</a>
        <a href="#">Tecnologia</a>
        <a href="#">Direito</a>
        <a href="#">Administração</a>
        <a href="#">Pedagogia</a>
      </div>
      <div class="footer-col">
        <h4>Institucional</h4>
        <a href="#">Sobre a UNISUAM</a>
        <a href="#">Pesquisa</a>
        <a href="#">Extensão</a>
        <a href="#">Sustentabilidade</a>
      </div>
      <div class="footer-col">
        <h4>Contato</h4>
        <a href="#">Fale Conosco</a>
        <a href="blog.html">Blog</a>
        <a href="#">Vestibular</a>
        <a href="#">Portal do Aluno</a>
      </div>
    </div>
    <div class="footer-bottom">
      <p>© <?= date('Y') ?> UNISUAM. Todos os direitos reservados.</p>
      <p>Av. Paris, 84 – Bonsucesso, Rio de Janeiro – RJ</p>
    </div>
  </div>
</footer>

<!-- ── Scripts ─────────────────────────────────────────────────────────────── -->
<script>
const POST_ID = <?= $post ? (int)$post['id'] : 0 ?>;
const PAGE_URL = encodeURIComponent(window.location.href);
const PAGE_TITLE = encodeURIComponent(document.title);

// ── Theme toggle ─────────────────────────────────────────────────────────────
(function(){
  const btn = document.getElementById('themeBtn');
  const html = document.documentElement;
  const saved = localStorage.getItem('unisuam-theme') || 'light';
  html.setAttribute('data-theme', saved);
  btn.textContent = saved === 'dark' ? '☀️' : '🌙';
  btn.addEventListener('click', () => {
    const next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', next);
    localStorage.setItem('unisuam-theme', next);
    btn.textContent = next === 'dark' ? '☀️' : '🌙';
  });
})();

// ── Mobile menu ──────────────────────────────────────────────────────────────
(function(){
  const btn  = document.getElementById('hamburgerBtn');
  const menu = document.getElementById('mobileMenu');
  if (!btn) return;
  btn.addEventListener('click', () => menu.classList.toggle('open'));
})();

if (POST_ID) {
  // ── Likes ────────────────────────────────────────────────────────────────
  const likeBtn   = document.getElementById('likeBtn');
  const heartIcon = document.getElementById('heartIcon');
  const likeCount = document.getElementById('likeCount');
  const likeLabel = document.getElementById('likeLabel');

  async function loadLikes() {
    try {
      const r = await fetch(`api/likes.php?post_id=${POST_ID}`);
      const d = await r.json();
      likeCount.textContent = d.count;
      likeBtn.classList.toggle('liked', d.liked);
      heartIcon.textContent = d.liked ? '❤️' : '🤍';
      likeLabel.textContent = d.liked ? 'Você curtiu este post!' : 'Gostou? Deixe seu like!';
    } catch(e) {}
  }

  likeBtn.addEventListener('click', async () => {
    likeBtn.disabled = true;
    try {
      const r = await fetch('api/likes.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `post_id=${POST_ID}`
      });
      const d = await r.json();
      likeCount.textContent = d.count;
      likeBtn.classList.toggle('liked', d.liked);
      heartIcon.textContent = d.liked ? '❤️' : '🤍';
      likeLabel.textContent = d.liked ? 'Você curtiu este post!' : 'Gostou? Deixe seu like!';
      likeBtn.classList.remove('pop');
      void likeBtn.offsetWidth; // reflow
      likeBtn.classList.add('pop');
    } catch(e) {}
    setTimeout(() => { likeBtn.disabled = false; }, 600);
  });

  loadLikes();

  // ── Shares ──────────────────────────────────────────────────────────────
  function trackShare(platform) {
    fetch('api/shares.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: `post_id=${POST_ID}&platform=${platform}`
    }).catch(() => {});
  }

  window.sharePost = function(platform) {
    const url = window.location.href;
    const title = document.title;
    let shareUrl = '';

    if (platform === 'whatsapp') {
      shareUrl = `https://wa.me/?text=${encodeURIComponent(title + ' ' + url)}`;
      window.open(shareUrl, '_blank', 'noopener');
    } else if (platform === 'linkedin') {
      shareUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(url)}`;
      window.open(shareUrl, '_blank', 'noopener');
    } else if (platform === 'twitter') {
      shareUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(title)}&url=${encodeURIComponent(url)}`;
      window.open(shareUrl, '_blank', 'noopener');
    } else if (platform === 'copy') {
      navigator.clipboard.writeText(url).then(() => {
        const btn = document.getElementById('copyBtn');
        const lbl = document.getElementById('copyLabel');
        btn.classList.add('copied');
        lbl.textContent = 'Link copiado!';
        setTimeout(() => { btn.classList.remove('copied'); lbl.textContent = 'Copiar link'; }, 2500);
      });
    }

    trackShare(platform);
  };

  // ── Comments ─────────────────────────────────────────────────────────────
  const commentList    = document.getElementById('commentList');
  const commentForm    = document.getElementById('commentForm');
  const submitBtn      = document.getElementById('submitBtn');
  const formMsg        = document.getElementById('formMsg');

  const AVATAR_COLORS = ['#FF4D00','#2563EB','#059669','#7C3AED','#0891B2','#D97706','#DB2777','#65A30D'];

  function avatarColor(name) {
    let h = 0;
    for (let i = 0; i < name.length; i++) h = (h * 31 + name.charCodeAt(i)) >>> 0;
    return AVATAR_COLORS[h % AVATAR_COLORS.length];
  }

  function relativeTimePT(dateStr) {
    const diff = (Date.now() - new Date(dateStr + 'Z').getTime()) / 1000;
    if (diff < 60)   return 'agora mesmo';
    if (diff < 3600) return `há ${Math.floor(diff/60)} min`;
    if (diff < 86400)return `há ${Math.floor(diff/3600)} h`;
    if (diff < 604800) return `há ${Math.floor(diff/86400)} dia${Math.floor(diff/86400)>1?'s':''}`;
    if (diff < 2592000) return `há ${Math.floor(diff/604800)} semana${Math.floor(diff/604800)>1?'s':''}`;
    return `há ${Math.floor(diff/2592000)} mês(es)`;
  }

  function renderComment(c) {
    const initial = c.name.trim().charAt(0).toUpperCase() || '?';
    const color   = avatarColor(c.name);
    const time    = relativeTimePT(c.created_at);
    const el = document.createElement('div');
    el.className = 'comment-card';
    el.innerHTML = `
      <div class="comment-avatar" style="background:${color}">${initial}</div>
      <div class="comment-body">
        <div class="comment-header">
          <span class="comment-name">${escHtml(c.name)}</span>
          <span class="comment-time">${time}</span>
        </div>
        <p class="comment-text">${escHtml(c.content)}</p>
      </div>`;
    return el;
  }

  function escHtml(s) {
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
            .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
  }

  async function loadComments() {
    try {
      const r = await fetch(`api/comments.php?post_id=${POST_ID}`);
      const d = await r.json();
      commentList.innerHTML = '';
      if (!d.comments || d.comments.length === 0) {
        commentList.innerHTML = '<div class="no-comments">Seja o primeiro a comentar!</div>';
      } else {
        d.comments.forEach(c => commentList.appendChild(renderComment(c)));
      }
    } catch(e) {
      commentList.innerHTML = '<div class="no-comments">Não foi possível carregar os comentários.</div>';
    }
  }

  commentForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    formMsg.className = 'form-msg';
    formMsg.textContent = '';

    const name    = commentForm.querySelector('#cName').value.trim();
    const email   = commentForm.querySelector('#cEmail').value.trim();
    const content = commentForm.querySelector('#cContent').value.trim();
    const hp      = commentForm.querySelector('#hp').value;

    if (!name || !content) {
      formMsg.className = 'form-msg error';
      formMsg.textContent = 'Por favor, preencha nome e comentário.';
      return;
    }

    submitBtn.disabled = true;
    submitBtn.textContent = 'Publicando…';

    const params = new URLSearchParams({ post_id: POST_ID, name, email, content, hp });

    try {
      const r = await fetch('api/comments.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: params.toString()
      });
      const d = await r.json();

      if (r.ok && d.comment) {
        // Remove "be first" placeholder if present
        const noComments = commentList.querySelector('.no-comments');
        if (noComments) noComments.remove();
        // Prepend new comment
        const el = renderComment(d.comment);
        commentList.insertBefore(el, commentList.firstChild);
        commentForm.reset();
        formMsg.className = 'form-msg success';
        formMsg.textContent = 'Comentário publicado com sucesso!';
        setTimeout(() => { formMsg.className = 'form-msg'; }, 4000);
      } else {
        throw new Error(d.error || 'Erro ao publicar');
      }
    } catch(err) {
      formMsg.className = 'form-msg error';
      formMsg.textContent = 'Erro ao publicar comentário. Tente novamente.';
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = 'Publicar comentário';
    }
  });

  loadComments();
}
</script>
</body>
</html>

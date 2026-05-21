<?php
/**
 * UNISUAM – api/likes.php
 * GET  ?post_id=X        → returns { count, liked }
 * POST post_id=X (body)  → toggles like, returns { count, liked }
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../config.php';

// ── Input ─────────────────────────────────────────────────────────────────────
$post_id = (int)($_GET['post_id'] ?? $_POST['post_id'] ?? 0);
if (!$post_id) {
    jsonResponse(['error' => 'post_id required'], 400);
}

// Verify post exists
$check = $db->prepare('SELECT id FROM posts WHERE id = ?');
$check->execute([$post_id]);
if (!$check->fetch()) {
    jsonResponse(['error' => 'Post not found'], 404);
}

// Pseudo-anonymous fingerprint: IP + UA + salt (never stored raw)
$ip  = $_SERVER['HTTP_CF_CONNECTING_IP']
    ?? $_SERVER['HTTP_X_FORWARDED_FOR']
    ?? $_SERVER['REMOTE_ADDR']
    ?? '0.0.0.0';
$ip  = trim(explode(',', $ip)[0]); // handle x-forwarded-for lists
$ip_hash = hash('sha256', $ip . ($_SERVER['HTTP_USER_AGENT'] ?? '') . 'unisuam_salt_2025');

// ── Logic ─────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $exists = $db->prepare('SELECT id FROM likes WHERE post_id = ? AND ip_hash = ?');
    $exists->execute([$post_id, $ip_hash]);

    if ($exists->fetch()) {
        $db->prepare('DELETE FROM likes WHERE post_id = ? AND ip_hash = ?')
           ->execute([$post_id, $ip_hash]);
        $liked = false;
    } else {
        $db->prepare('INSERT INTO likes (post_id, ip_hash) VALUES (?, ?)')
           ->execute([$post_id, $ip_hash]);
        $liked = true;
    }
} else {
    $exists = $db->prepare('SELECT id FROM likes WHERE post_id = ? AND ip_hash = ?');
    $exists->execute([$post_id, $ip_hash]);
    $liked = (bool) $exists->fetch();
}

$count = $db->prepare('SELECT COUNT(*) FROM likes WHERE post_id = ?');
$count->execute([$post_id]);

jsonResponse(['count' => (int) $count->fetchColumn(), 'liked' => $liked]);

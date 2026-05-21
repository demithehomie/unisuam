<?php
/**
 * UNISUAM – api/shares.php
 * POST {post_id, platform}  → record a share, return { ok, total }
 * GET  ?post_id=X           → return { total, by_platform: {...} }
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../config.php';

$allowed_platforms = ['whatsapp', 'linkedin', 'twitter', 'copy', 'facebook', 'telegram'];
$method = $_SERVER['REQUEST_METHOD'];

// ── GET ───────────────────────────────────────────────────────────────────────
if ($method === 'GET') {
    $post_id = (int) ($_GET['post_id'] ?? 0);
    if (!$post_id) { jsonResponse(['error' => 'post_id required'], 400); }

    $total_stmt = $db->prepare('SELECT COUNT(*) FROM shares WHERE post_id = ?');
    $total_stmt->execute([$post_id]);
    $total = (int) $total_stmt->fetchColumn();

    $bp_stmt = $db->prepare(
        'SELECT platform, COUNT(*) AS cnt FROM shares WHERE post_id = ? GROUP BY platform'
    );
    $bp_stmt->execute([$post_id]);
    $by_platform = [];
    foreach ($bp_stmt->fetchAll() as $row) {
        $by_platform[$row['platform']] = (int) $row['cnt'];
    }

    jsonResponse(['total' => $total, 'by_platform' => $by_platform]);
}

// ── POST ──────────────────────────────────────────────────────────────────────
if ($method === 'POST') {
    $body = [];
    $ct   = $_SERVER['CONTENT_TYPE'] ?? '';
    if (str_contains($ct, 'application/json')) {
        $raw  = file_get_contents('php://input');
        $body = json_decode($raw, true) ?? [];
    } else {
        $body = $_POST;
    }

    $post_id  = (int) ($body['post_id']  ?? 0);
    $platform = strtolower(trim($body['platform'] ?? 'unknown'));

    if (!$post_id) { jsonResponse(['error' => 'post_id required'], 400); }

    // Sanitise platform to known values
    if (!in_array($platform, $allowed_platforms, true)) {
        $platform = 'other';
    }

    // Verify post exists
    $chk = $db->prepare('SELECT id FROM posts WHERE id = ?');
    $chk->execute([$post_id]);
    if (!$chk->fetch()) { jsonResponse(['error' => 'Post not found'], 404); }

    $db->prepare('INSERT INTO shares (post_id, platform) VALUES (?, ?)')->execute([$post_id, $platform]);

    $total_stmt = $db->prepare('SELECT COUNT(*) FROM shares WHERE post_id = ?');
    $total_stmt->execute([$post_id]);

    jsonResponse(['ok' => true, 'total' => (int) $total_stmt->fetchColumn()]);
}

jsonResponse(['error' => 'Method not allowed'], 405);

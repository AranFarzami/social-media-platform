<?php

session_start();

require_once "../../config/database.php";

header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized"
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Method Not Allowed"
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

if (
    !isset($_POST['csrf_token']) ||
    !isset($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    http_response_code(403);
    echo json_encode([
        "success" => false,
        "message" => "Invalid request."
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$content = trim($_POST['content'] ?? '');

if ($content === '') {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Post cannot be empty."
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

if (mb_strlen($content) > 5000) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Post cannot be longer than 5000 characters."
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$stmt = $pdo->prepare("
    INSERT INTO posts (user_id, content)
    VALUES (?, ?)
");
$stmt->execute([$user_id, $content]);

$post_id = (int) $pdo->lastInsertId();

$stmt = $pdo->prepare("
    SELECT
        p.id,
        p.user_id,
        p.content,
        p.created_at,
        u.username,
        u.avatar
    FROM posts p
    INNER JOIN users u ON u.id = p.user_id
    WHERE p.id = ?
    LIMIT 1
");
$stmt->execute([$post_id]);

$post = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "message" => "Post created successfully.",
    "post" => $post
], JSON_UNESCAPED_UNICODE);

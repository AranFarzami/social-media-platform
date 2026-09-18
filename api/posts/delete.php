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
$post_id = (int) ($_POST['post_id'] ?? 0);

if ($post_id <= 0) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Invalid post."
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$stmt = $pdo->prepare("
    DELETE FROM posts
    WHERE id = ?
    AND user_id = ?
");
$stmt->execute([$post_id, $user_id]);

if ($stmt->rowCount() === 0) {
    http_response_code(404);
    echo json_encode([
        "success" => false,
        "message" => "Post not found or you are not allowed to delete it."
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

echo json_encode([
    "success" => true,
    "message" => "Post deleted successfully."
], JSON_UNESCAPED_UNICODE);

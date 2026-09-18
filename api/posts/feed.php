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

$current_user_id = (int) $_SESSION['user_id'];
$mine = isset($_GET['mine']) && $_GET['mine'] === '1';

$sql = "
    SELECT
        p.id,
        p.user_id,
        p.content,
        p.created_at,
        u.username,
        u.avatar
    FROM posts p
    INNER JOIN users u ON u.id = p.user_id
";

$params = [];

if ($mine) {
    $sql .= " WHERE p.user_id = ? ";
    $params[] = $current_user_id;
}

$sql .= " ORDER BY p.id DESC LIMIT 100 ";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "posts" => $posts
], JSON_UNESCAPED_UNICODE);

<?php

session_start();

require_once "../../config/database.php";

header("Content-Type: application/json; charset=UTF-8");




if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {

    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Unauthorized"
    ]);

    exit();
}




$current_user_id = (int) $_SESSION['user_id'];




$target_user_id = isset($_GET['user_id'])
    ? (int) $_GET['user_id']
    : 0;


if ($target_user_id <= 0 || $target_user_id === $current_user_id) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid user"
    ]);

    exit();
}




$stmt = $pdo->prepare("
    SELECT id
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$target_user_id]);

if (!$stmt->fetch()) {

    http_response_code(404);

    echo json_encode([
        "success" => false,
        "message" => "User not found"
    ]);

    exit();
}




$stmt = $pdo->prepare("
    SELECT
        id,
        sender_id,
        receiver_id,
        message,
        created_at
    FROM messages
    WHERE
        (
            sender_id = ?
            AND receiver_id = ?
        )
        OR
        (
            sender_id = ?
            AND receiver_id = ?
        )
    ORDER BY id ASC
");

$stmt->execute([
    $current_user_id,
    $target_user_id,
    $target_user_id,
    $current_user_id
]);

$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);



echo json_encode([
    "success" => true,
    "messages" => $messages
], JSON_UNESCAPED_UNICODE);
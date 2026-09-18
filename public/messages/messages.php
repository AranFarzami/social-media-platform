<?php

session_start();

require_once "../../config/database.php";



if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {

    header("Location: ../auth/login.php");
    exit();

}



$current_user_id = (int) $_SESSION['user_id'];




$search_chat_id = trim($_GET['chat_id'] ?? '');

$search_user = null;
$search_error = '';


if ($search_chat_id !== '') {

    $stmt = $pdo->prepare("
        SELECT
            id,
            username,
            avatar,
            chat_id
        FROM users
        WHERE chat_id = ?
        AND id != ?
        LIMIT 1
    ");

    $stmt->execute([
        $search_chat_id,
        $current_user_id
    ]);

    $search_user = $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$search_user) {

        $search_error = "No user found with this Chat ID.";

    }

}




$stmt = $pdo->prepare("
    SELECT
        u.id,
        u.username,
        u.avatar,
        u.chat_id,
        MAX(m.created_at) AS last_message_at
    FROM messages m

    INNER JOIN users u
        ON u.id =
        CASE
            WHEN m.sender_id = ? THEN m.receiver_id
            ELSE m.sender_id
        END

    WHERE
        m.sender_id = ?
        OR
        m.receiver_id = ?

    GROUP BY
        u.id,
        u.username,
        u.avatar,
        u.chat_id

    ORDER BY last_message_at DESC
");

$stmt->execute([
    $current_user_id,
    $current_user_id,
    $current_user_id
]);

$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);




foreach ($conversations as &$conversation) {

    $stmt = $pdo->prepare("
        SELECT
            message,
            sender_id,
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
        ORDER BY id DESC
        LIMIT 1
    ");

    $stmt->execute([
        $current_user_id,
        $conversation['id'],
        $conversation['id'],
        $current_user_id
    ]);

    $last_message = $stmt->fetch(PDO::FETCH_ASSOC);

    $conversation['last_message'] =
        $last_message['message'] ?? '';

    $conversation['last_sender_id'] =
        $last_message['sender_id'] ?? 0;

    $conversation['last_message_time'] =
        $last_message['created_at'] ?? '';

}

unset($conversation);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Messages</title>


    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        body {

            font-family: Arial, sans-serif;

            background: #f5f7fb;

            color: #222;

        }


        

        .navbar {

            height: 70px;

            background: #ffffff;

            border-bottom: 1px solid #e5e5e5;

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 0 40px;

        }


        .logo {

            font-size: 24px;

            font-weight: bold;

            color: #222;

            text-decoration: none;

        }


        .nav-links {

            display: flex;

            align-items: center;

            gap: 25px;

        }


        .nav-links > a {

            text-decoration: none;

            color: #555;

            font-size: 15px;

        }


        .nav-links > a:hover {

            color: #000;

        }


       

        .profile-menu {

            position: relative;

        }


        .profile-button {

            background: none;

            border: none;

            cursor: pointer;

            display: flex;

            align-items: center;

            gap: 8px;

            font-size: 15px;

        }


        .profile-button span {

            font-weight: 500;

        }


        .profile-dropdown {

            display: none;

            position: absolute;

            right: 0;

            top: 45px;

            width: 180px;

            background: white;

            border: 1px solid #ddd;

            border-radius: 10px;

            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);

            overflow: hidden;

            z-index: 100;

        }


        .profile-dropdown a {

            display: block;

            padding: 12px 15px;

            text-decoration: none;

            color: #333;

            font-size: 14px;

        }


        .profile-dropdown a:hover {

            background: #f5f5f5;

        }


        .profile-dropdown .logout {

            color: #e53935;

        }


        .profile-menu:hover .profile-dropdown {

            display: block;

        }


        

        .container {

            width: 100%;

            max-width: 800px;

            margin: 40px auto;

            padding: 0 20px;

        }


        .page-title {

            font-size: 28px;

            margin-bottom: 25px;

        }


       

        .search-box {

            background: #ffffff;

            border: 1px solid #e5e5e5;

            border-radius: 15px;

            padding: 20px;

            margin-bottom: 30px;

        }


        .search-title {

            font-size: 16px;

            font-weight: 600;

            margin-bottom: 12px;

        }


        .search-form {

            display: flex;

            gap: 10px;

        }


        .search-input {

            flex: 1;

            height: 45px;

            border: 1px solid #ddd;

            border-radius: 9px;

            padding: 0 14px;

            font-size: 14px;

            outline: none;

        }


        .search-input:focus {

            border-color: #999;

        }


        .search-button {

            height: 45px;

            padding: 0 20px;

            border: none;

            border-radius: 9px;

            background: #222;

            color: #fff;

            cursor: pointer;

        }


        .search-button:hover {

            background: #000;

        }


        

        .search-result {

            margin-top: 15px;

            border: 1px solid #eee;

            border-radius: 10px;

            overflow: hidden;

        }


        .search-user {

            display: flex;

            align-items: center;

            padding: 15px;

        }


        

        .avatar {

            width: 55px;

            height: 55px;

            border-radius: 50%;

            background: #222;

            color: #fff;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 20px;

            font-weight: bold;

            overflow: hidden;

            flex-shrink: 0;

        }


        .avatar img {

            width: 100%;

            height: 100%;

            object-fit: cover;

        }


        

        .user-info {

            margin-left: 15px;

            flex: 1;

            min-width: 0;

        }


        .username {

            font-size: 16px;

            font-weight: 600;

            margin-bottom: 5px;

        }


        .chat-id {

            font-size: 13px;

            color: #888;

        }


        .open-chat {

            text-decoration: none;

            background: #222;

            color: #fff;

            padding: 10px 15px;

            border-radius: 8px;

            font-size: 13px;

        }


        .open-chat:hover {

            background: #000;

        }


        .search-error {

            margin-top: 15px;

            color: #d32f2f;

            font-size: 14px;

        }


        

        .section-title {

            font-size: 19px;

            margin-bottom: 15px;

        }


        

        .users-box {

            background: #ffffff;

            border: 1px solid #e5e5e5;

            border-radius: 15px;

            overflow: hidden;

        }


        .user-item {

            display: flex;

            align-items: center;

            padding: 16px 20px;

            text-decoration: none;

            color: inherit;

            border-bottom: 1px solid #eeeeee;

            transition: background 0.2s;

        }


        .user-item:last-child {

            border-bottom: none;

        }


        .user-item:hover {

            background: #f7f8fa;

        }


        .conversation-info {

            margin-left: 15px;

            flex: 1;

            min-width: 0;

        }


        .conversation-username {

            font-size: 16px;

            font-weight: 600;

            margin-bottom: 5px;

        }


        .last-message {

            font-size: 13px;

            color: #888;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;

        }


        .conversation-time {

            font-size: 12px;

            color: #aaa;

            margin-left: 10px;

            white-space: nowrap;

        }


        .arrow {

            font-size: 22px;

            color: #aaa;

            margin-left: 15px;

        }


        

        .empty {

            padding: 50px 20px;

            text-align: center;

            color: #888;

            font-size: 15px;

            line-height: 1.6;

        }


       

        @media (max-width: 700px) {

            .navbar {

                padding: 0 20px;

            }


            .nav-links > a {

                display: none;

            }


            .container {

                margin-top: 25px;

            }


            .page-title {

                font-size: 24px;

            }


            .search-form {

                flex-direction: column;

            }


            .search-button {

                width: 100%;

            }


            .open-chat {

                padding: 9px 12px;

            }


            .conversation-time {

                display: none;

            }

        }

    </style>

</head>


<body>




<nav class="navbar">


    <a href="../index.php" class="logo">
        MiniSocial
    </a>


    <div class="nav-links">


        <a href="../index.php">
            Home
        </a>


        <a href="../index.php#composer">
            Create Post
        </a>


        <a href="../index.php?mine=1">
            My Posts
        </a>


        <a href="messages.php">
            Messages
        </a>


        <div class="profile-menu">


            <button class="profile-button">

                <span>

                    <?php

                    echo htmlspecialchars(
                        $_SESSION['username'],
                        ENT_QUOTES,
                        'UTF-8'
                    );

                    ?>

                </span>

                ▼

            </button>


            <div class="profile-dropdown">

                <a href="../profile/profile.php">
                    Profile
                </a>

                <a href="../profile/edit-profile.php">
                    Edit Profile
                </a>

                <a href="../auth/logout.php" class="logout">
                    Logout
                </a>

            </div>


        </div>


    </div>


</nav>





<main class="container">


    <h1 class="page-title">
        Messages
    </h1>





    <div class="search-box">


        <div class="search-title">
            Find someone by Chat ID
        </div>


        <form
            method="GET"
            action="messages.php"
            class="search-form"
        >


            <input
                type="text"
                name="chat_id"
                class="search-input"
                placeholder="Enter Chat ID..."
                value="<?php echo htmlspecialchars(
                    $search_chat_id,
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>"
                maxlength="30"
                required
            >


            <button
                type="submit"
                class="search-button"
            >
                Search
            </button>


        </form>



        <?php if ($search_chat_id !== ''): ?>


            <?php if ($search_user): ?>


                <div class="search-result">


                    <div class="search-user">


                        <div class="avatar">


                            <?php if (!empty($search_user['avatar'])): ?>


                                <img
                                    src="../../<?php echo htmlspecialchars(
                                        $search_user['avatar'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>"
                                    alt="Profile Picture"
                                >


                            <?php else: ?>


                                <?php

                                echo htmlspecialchars(

                                    strtoupper(

                                        substr(
                                            $search_user['username'],
                                            0,
                                            1
                                        )

                                    ),

                                    ENT_QUOTES,
                                    'UTF-8'

                                );

                                ?>

                            <?php endif; ?>


                        </div>



                        <div class="user-info">


                            <div class="username">

                                <?php

                                echo htmlspecialchars(
                                    $search_user['username'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                );

                                ?>

                            </div>


                            <div class="chat-id">

                                Chat ID:

                                <?php

                                echo htmlspecialchars(
                                    $search_user['chat_id'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                );

                                ?>

                            </div>


                        </div>



                        <a
                            href="chat.php?user_id=<?php echo (int)$search_user['id']; ?>"
                            class="open-chat"
                        >
                            Message
                        </a>


                    </div>


                </div>


            <?php else: ?>


                <div class="search-error">

                    <?php

                    echo htmlspecialchars(
                        $search_error,
                        ENT_QUOTES,
                        'UTF-8'
                    );

                    ?>

                </div>


            <?php endif; ?>


        <?php endif; ?>


    </div>



    

    <h2 class="section-title">
        Your Conversations
    </h2>


    <div class="users-box">


        <?php if (empty($conversations)): ?>


            <div class="empty">

                You don't have any conversations yet.

                <br>

                Search for someone using their Chat ID.

            </div>


        <?php else: ?>


            <?php foreach ($conversations as $conversation): ?>


                <a
                    href="chat.php?user_id=<?php echo (int)$conversation['id']; ?>"
                    class="user-item"
                >


                    <div class="avatar">


                        <?php if (!empty($conversation['avatar'])): ?>


                            <img
                                src="../../<?php echo htmlspecialchars(
                                    $conversation['avatar'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>"
                                alt="Profile Picture"
                            >


                        <?php else: ?>


                            <?php

                            echo htmlspecialchars(

                                strtoupper(

                                    substr(
                                        $conversation['username'],
                                        0,
                                        1
                                    )

                                ),

                                ENT_QUOTES,
                                'UTF-8'

                            );

                            ?>

                        <?php endif; ?>


                    </div>



                    <div class="conversation-info">


                        <div class="conversation-username">

                            <?php

                            echo htmlspecialchars(
                                $conversation['username'],
                                ENT_QUOTES,
                                'UTF-8'
                            );

                            ?>

                        </div>


                        <div class="last-message">

                            <?php

                            if (
                                (int)$conversation['last_sender_id']
                                ===
                                $current_user_id
                            ) {

                                echo "You: ";

                            }

                            echo htmlspecialchars(
                                $conversation['last_message'],
                                ENT_QUOTES,
                                'UTF-8'
                            );

                            ?>

                        </div>


                    </div>



                    <div class="conversation-time">

                        <?php

                        if (
                            !empty(
                                $conversation['last_message_time']
                            )
                        ) {

                            echo htmlspecialchars(

                                date(

                                    'H:i',

                                    strtotime(
                                        $conversation['last_message_time']
                                    )

                                ),

                                ENT_QUOTES,
                                'UTF-8'

                            );

                        }

                        ?>

                    </div>



                    <div class="arrow">
                        ›
                    </div>


                </a>


            <?php endforeach; ?>


        <?php endif; ?>


    </div>


</main>


</body>

</html>
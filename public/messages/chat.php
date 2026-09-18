<?php

session_start();

require_once "../../config/database.php";



if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {

    header("Location: ../auth/login.php");
    exit();

}



$current_user_id = (int) $_SESSION['user_id'];



$target_user_id = isset($_GET['user_id'])
    ? (int) $_GET['user_id']
    : 0;


if ($target_user_id <= 0 || $target_user_id === $current_user_id) {

    header("Location: messages.php");
    exit();

}




$stmt = $pdo->prepare("
    SELECT
        id,
        username,
        avatar,
        chat_id
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$target_user_id]);

$target_user = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$target_user) {

    header("Location: messages.php");
    exit();

}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Chat - <?php echo htmlspecialchars(
            $target_user['username'],
            ENT_QUOTES,
            'UTF-8'
        ); ?>
    </title>


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

            height: 100vh;

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


        

        .chat-container {

            width: 100%;

            max-width: 800px;

            height: calc(100vh - 100px);

            margin: 15px auto;

            padding: 0 15px;

            display: flex;

            flex-direction: column;

        }


        

        .chat-header {

            background: #ffffff;

            border: 1px solid #e5e5e5;

            border-radius: 15px 15px 0 0;

            padding: 14px 18px;

            display: flex;

            align-items: center;

        }


        .back-button {

            text-decoration: none;

            color: #555;

            font-size: 28px;

            margin-right: 15px;

        }


        .back-button:hover {

            color: #000;

        }


        

        .avatar {

            width: 45px;

            height: 45px;

            border-radius: 50%;

            background: #222;

            color: white;

            display: flex;

            align-items: center;

            justify-content: center;

            font-weight: bold;

            font-size: 18px;

            overflow: hidden;

            flex-shrink: 0;

        }


        .avatar img {

            width: 100%;

            height: 100%;

            object-fit: cover;

        }


        

        .header-info {

            margin-left: 12px;

        }


        .header-username {

            font-size: 16px;

            font-weight: 600;

        }


        .header-chat-id {

            margin-top: 4px;

            font-size: 12px;

            color: #888;

        }


   

        .connection-status {

            margin-left: auto;

            font-size: 12px;

            color: #999;

        }


        .connection-status.connected {

            color: #2e7d32;

        }


        .connection-status.disconnected {

            color: #d32f2f;

        }


        

        .messages-area {

            flex: 1;

            background: #ffffff;

            border-left: 1px solid #e5e5e5;

            border-right: 1px solid #e5e5e5;

            padding: 20px;

            overflow-y: auto;

            display: flex;

            flex-direction: column;

        }


        .empty-chat {

            margin: auto;

            text-align: center;

            color: #999;

            font-size: 14px;

        }


        .message-row {

            display: flex;

            margin-bottom: 12px;

        }


        .message-row.sent {

            justify-content: flex-end;

        }


        .message-row.received {

            justify-content: flex-start;

        }


        .message-bubble {

            max-width: 70%;

            padding: 9px 13px;

            border-radius: 15px;

            font-size: 14px;

            line-height: 1.5;

            word-break: break-word;

        }


        .sent .message-bubble {

            background: #222;

            color: #fff;

            border-bottom-right-radius: 4px;

        }


        .received .message-bubble {

            background: #f0f1f3;

            color: #222;

            border-bottom-left-radius: 4px;

        }


        .message-time {

            font-size: 10px;

            opacity: 0.55;

            margin-top: 4px;

            text-align: right;

        }


       

        .message-form {

            background: #ffffff;

            border: 1px solid #e5e5e5;

            border-top: none;

            border-radius: 0 0 15px 15px;

            padding: 12px;

            display: flex;

            gap: 10px;

        }


        .message-input {

            flex: 1;

            resize: none;

            height: 45px;

            border: 1px solid #ddd;

            border-radius: 10px;

            padding: 12px;

            outline: none;

            font-family: Arial, sans-serif;

            font-size: 14px;

        }


        .message-input:focus {

            border-color: #999;

        }


        .send-button {

            height: 45px;

            padding: 0 20px;

            border: none;

            border-radius: 10px;

            background: #222;

            color: white;

            cursor: pointer;

        }


        .send-button:hover {

            background: #000;

        }


        .send-button:disabled {

            background: #aaa;

            cursor: not-allowed;

        }


       

        @media (max-width: 700px) {

            .navbar {

                padding: 0 20px;

            }


            .nav-links > a {

                display: none;

            }


            .chat-container {

                height: calc(100vh - 85px);

                margin-top: 8px;

                padding: 0 8px;

            }


            .messages-area {

                padding: 15px 10px;

            }


            .message-bubble {

                max-width: 82%;

            }


            .connection-status {

                font-size: 10px;

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


        <a href="messages.php#my-posts">
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



<main class="chat-container">


  

    <div class="chat-header">


        <a
            href="messages.php"
            class="back-button"
        >
            ‹
        </a>


        <div class="avatar">


            <?php if (!empty($target_user['avatar'])): ?>


                <img
                    src="../../<?php echo htmlspecialchars(
                        $target_user['avatar'],
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
                            $target_user['username'],
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


        <div class="header-info">


            <div class="header-username">

                <?php

                echo htmlspecialchars(
                    $target_user['username'],
                    ENT_QUOTES,
                    'UTF-8'
                );

                ?>

            </div>


            <div class="header-chat-id">

                <?php

                echo htmlspecialchars(
                    $target_user['chat_id'],
                    ENT_QUOTES,
                    'UTF-8'
                );

                ?>

            </div>


        </div>


        <div
            id="connectionStatus"
            class="connection-status"
        >
            Connecting...
        </div>


    </div>



    

    <div
        id="messagesArea"
        class="messages-area"
    >

        <div
            id="emptyChat"
            class="empty-chat"
        >
            Loading messages...
        </div>

    </div>





    <form
        id="messageForm"
        class="message-form"
    >


        <textarea
            id="messageInput"
            class="message-input"
            placeholder="Write a message..."
            maxlength="5000"
        ></textarea>


        <button
            type="submit"
            id="sendButton"
            class="send-button"
            disabled
        >
            Send
        </button>


    </form>


</main>



<script>

const currentUserId = <?php echo $current_user_id; ?>;

const targetUserId = <?php echo $target_user_id; ?>;


const messagesArea =
    document.getElementById("messagesArea");

const emptyChat =
    document.getElementById("emptyChat");

const messageForm =
    document.getElementById("messageForm");

const messageInput =
    document.getElementById("messageInput");

const sendButton =
    document.getElementById("sendButton");

const connectionStatus =
    document.getElementById("connectionStatus");


let socket = null;

let reconnectTimer = null;

let loadedMessageIds = new Set();




function addMessage(message) {

    if (loadedMessageIds.has(Number(message.id))) {

        return;

    }

    loadedMessageIds.add(Number(message.id));


    if (emptyChat) {

        emptyChat.remove();

    }


    const row =
        document.createElement("div");


    const isSent =
        Number(message.sender_id)
        ===
        currentUserId;


    row.className =
        "message-row " +
        (isSent ? "sent" : "received");


    const bubble =
        document.createElement("div");


    bubble.className =
        "message-bubble";


    const text =
        document.createElement("div");


    text.textContent =
        message.message;


    const time =
        document.createElement("div");


    time.className =
        "message-time";


    const date =
        new Date(
            message.created_at.replace(" ", "T")
        );


    time.textContent =
        date.toLocaleTimeString(
            [],
            {
                hour: "2-digit",
                minute: "2-digit"
            }
        );


    bubble.appendChild(text);

    bubble.appendChild(time);

    row.appendChild(bubble);

    messagesArea.appendChild(row);


    scrollToBottom();

}



function scrollToBottom() {

    messagesArea.scrollTop =
        messagesArea.scrollHeight;

}




async function loadMessages() {

    try {

        const response =
            await fetch(
                "../../api/messages/get-chat.php?user_id="
                +
                targetUserId
            );


        const data =
            await response.json();


        if (!data.success) {

            return;

        }


        data.messages.forEach(
            addMessage
        );


        scrollToBottom();


    } catch (error) {

        console.error(
            "Message loading error:",
            error
        );

    }

}




function connectWebSocket() {

    if (
        socket
        &&
        (
            socket.readyState === WebSocket.OPEN
            ||
            socket.readyState === WebSocket.CONNECTING
        )
    ) {

        return;

    }


    connectionStatus.textContent =
        "Connecting...";

    connectionStatus.className =
        "connection-status";


    socket =
        new WebSocket(
            "ws://" +
            window.location.hostname +
            ":8080"
        );


   

    socket.onopen = function() {

        console.log(
            "WebSocket connected"
        );


        connectionStatus.textContent =
            "Online";

        connectionStatus.className =
            "connection-status connected";


        sendButton.disabled = false;


        socket.send(
            JSON.stringify({

                type: "join",

                user_id: currentUserId

            })
        );

    };


   

    socket.onmessage = function(event) {

        try {

            const data =
                JSON.parse(event.data);


            if (
                data.type === "message"
            ) {

                if (

                    (
                        Number(data.sender_id)
                        ===
                        targetUserId

                        &&
                        Number(data.receiver_id)
                        ===
                        currentUserId

                    )

                    ||

                    (
                        Number(data.sender_id)
                        ===
                        currentUserId

                        &&
                        Number(data.receiver_id)
                        ===
                        targetUserId
                    )

                ) {

                    addMessage(data);

                }

            }


        } catch (error) {

            console.error(
                "WebSocket message error:",
                error
            );

        }

    };


    

    socket.onclose = function() {

        console.log(
            "WebSocket disconnected"
        );


        connectionStatus.textContent =
            "Reconnecting...";

        connectionStatus.className =
            "connection-status disconnected";


        sendButton.disabled = true;


        clearTimeout(
            reconnectTimer
        );


        reconnectTimer =
            setTimeout(
                connectWebSocket,
                2000
            );

    };


    

    socket.onerror = function(error) {

        console.error(
            "WebSocket error:",
            error
        );

    };

}




messageForm.addEventListener(
    "submit",
    function(event) {

        event.preventDefault();


        const message =
            messageInput.value.trim();


        if (
            message === ""
        ) {

            return;

        }


        if (
            !socket
            ||
            socket.readyState !== WebSocket.OPEN
        ) {

            return;

        }


        socket.send(

            JSON.stringify({

                type: "message",

                receiver_id: targetUserId,

                message: message

            })

        );


        messageInput.value = "";

        messageInput.focus();

    }
);




messageInput.addEventListener(
    "keydown",
    function(event) {

        if (
            event.key === "Enter"
            &&
            !event.shiftKey
        ) {

            event.preventDefault();

            messageForm.requestSubmit();

        }

    }
);



loadMessages();

connectWebSocket();

</script>


</body>

</html>
<?php

session_start();
require_once "../../config/database.php";




if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../auth/login.php");
    exit();
}




$user_id = $_SESSION['user_id'];

$error = "";
$success = "";




function generateChatId()
{
    return "USR-" . strtoupper(
        substr(bin2hex(random_bytes(5)), 0, 8)
    );
}




if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_chat_id'])) {

    $chat_id = trim($_POST['chat_id'] ?? "");


   

    if ($chat_id === "") {

        $error = "Please enter a Chat ID.";

    } elseif (strlen($chat_id) < 5 || strlen($chat_id) > 30) {

        $error = "Chat ID must be between 5 and 30 characters.";

    } elseif (!preg_match('/^[A-Za-z0-9_]+$/', $chat_id)) {

        $error = "Chat ID can only contain letters, numbers and underscore.";

    } else {


        

        $check = $pdo->prepare("
            SELECT id
            FROM users
            WHERE chat_id = ?
            LIMIT 1
        ");

        $check->execute([$chat_id]);

        $exists = $check->fetch(PDO::FETCH_ASSOC);


        if ($exists) {

            $error = "This Chat ID is already taken.";

        } else {


            

            $update = $pdo->prepare("
                UPDATE users
                SET chat_id = ?
                WHERE id = ?
            ");

            $update->execute([
                $chat_id,
                $user_id
            ]);


            $success = "Your Chat ID has been created.";

        }
    }
}




$stmt = $pdo->prepare("
    SELECT id, username, email, bio, avatar, chat_id
    FROM users
    WHERE id = ?
");

$stmt->execute([$user_id]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$user) {
    die("User not found.");
}




$avatar = $user['avatar'] ?? "";

$username = $user['username'] ?? "";

$email = $user['email'] ?? "";

$bio = $user['bio'] ?? "";

$chat_id = $user['chat_id'] ?? "";




$first_char = strtoupper(
    substr($username, 0, 1)
);




$suggested_chat_id = generateChatId();

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Mini Twitter | Profile</title>


<style>



* {

    margin: 0;

    padding: 0;

    box-sizing: border-box;

    font-family: Arial, Helvetica, sans-serif;

}



body {

    background: #eef2f7;

}




nav {

    width: 100%;

    height: 70px;

    background: #fff;

    display: flex;

    justify-content: space-between;

    align-items: center;

    padding: 0 50px;

    box-shadow: 0 3px 10px rgba(0,0,0,.08);

}




.logo {

    color: #1DA1F2;

    font-size: 28px;

    font-weight: bold;

}



.nav-links {

    display: flex;

    gap: 30px;

}


.nav-links a {

    text-decoration: none;

    color: #444;

    font-weight: bold;

    transition: .3s;

}


.nav-links a:hover {

    color: #1DA1F2;

}




.profile {

    position: relative;

}


.profile-btn {

    background: #1DA1F2;

    color: white;

    padding: 10px 18px;

    border-radius: 25px;

    cursor: pointer;

    user-select: none;

    font-weight: bold;

}




.dropdown {

    position: absolute;

    top: 55px;

    right: 0;

    width: 220px;

    background: white;

    border-radius: 12px;

    box-shadow: 0 5px 20px rgba(0,0,0,.15);

    overflow: hidden;

    display: none;

    z-index: 100;

}


.dropdown.show {

    display: block;

}


.dropdown a {

    display: block;

    padding: 15px;

    color: #333;

    text-decoration: none;

    transition: .2s;

}


.dropdown a:hover {

    background: #f5f5f5;

}




.container {

    width: 900px;

    max-width: 95%;

    margin: 50px auto;

}



.card {

    background: white;

    border-radius: 15px;

    box-shadow: 0 5px 20px rgba(0,0,0,.08);

    padding: 40px;

}




.avatar {

    width: 120px;

    height: 120px;

    background: #1DA1F2;

    color: white;

    border-radius: 50%;

    display: flex;

    justify-content: center;

    align-items: center;

    font-size: 45px;

    margin: auto;

    overflow: hidden;

}




.avatar img {

    width: 100%;

    height: 100%;

    object-fit: cover;

    display: block;

}



.card h2 {

    text-align: center;

    margin-top: 20px;

    color: #222;

}




.bio {

    text-align: center;

    margin-top: 10px;

    color: #666;

    font-size: 15px;

}




.info {

    margin-top: 35px;

}


.info p {

    margin: 18px 0;

    font-size: 18px;

    color: #333;

}


.info b {

    color: #111;

}




.chat-id {

    color: #1DA1F2;

    font-weight: bold;

    letter-spacing: .5px;

}




.edit-button {

    display: block;

    width: 200px;

    margin: 30px auto 0;

    padding: 12px;

    text-align: center;

    background: #1DA1F2;

    color: white;

    text-decoration: none;

    border-radius: 8px;

    font-weight: bold;

    transition: .3s;

}


.edit-button:hover {

    background: #168bd0;

}




.modal {

    position: fixed;

    inset: 0;

    background: rgba(0, 0, 0, .55);

    display: flex;

    justify-content: center;

    align-items: center;

    z-index: 1000;

    padding: 20px;

}


.modal-box {

    width: 450px;

    max-width: 100%;

    background: white;

    border-radius: 18px;

    padding: 35px;

    box-shadow: 0 15px 50px rgba(0,0,0,.25);

    text-align: center;

}


.modal-icon {

    width: 65px;

    height: 65px;

    margin: 0 auto 20px;

    border-radius: 50%;

    background: #e8f5ff;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 30px;

}


.modal-box h2 {

    margin-bottom: 10px;

    color: #222;

}


.modal-box p {

    color: #777;

    line-height: 1.6;

    margin-bottom: 25px;

}


.chat-input {

    width: 100%;

    padding: 14px 15px;

    border: 1px solid #ddd;

    border-radius: 10px;

    font-size: 16px;

    outline: none;

    margin-bottom: 12px;

}


.chat-input:focus {

    border-color: #1DA1F2;

}


.suggestion {

    background: #f5f7fa;

    border-radius: 8px;

    padding: 10px;

    margin-bottom: 20px;

    color: #777;

    font-size: 13px;

}


.suggestion span {

    color: #1DA1F2;

    font-weight: bold;

}


.create-id-button {

    width: 100%;

    border: none;

    background: #1DA1F2;

    color: white;

    padding: 14px;

    border-radius: 10px;

    font-size: 16px;

    font-weight: bold;

    cursor: pointer;

    transition: .3s;

}


.create-id-button:hover {

    background: #168bd0;

}




.error {

    background: #ffecec;

    color: #d93025;

    border: 1px solid #ffcaca;

    padding: 12px;

    border-radius: 8px;

    margin-bottom: 15px;

    font-size: 14px;

}




.success {

    background: #eaf8ee;

    color: #188038;

    border: 1px solid #c8ebd2;

    padding: 12px;

    border-radius: 8px;

    margin-bottom: 20px;

    text-align: center;

}




@media(max-width:700px) {

    nav {

        padding: 0 15px;

    }


    .nav-links {

        display: none;

    }


    .container {

        width: 95%;

        margin-top: 30px;

    }


    .card {

        padding: 25px;

    }

}

</style>

</head>


<body>




<nav>


    <div class="logo">

        Mini Twitter

    </div>


    <div class="nav-links">

        <a href="../index.php">

            🏠 Home Feed

        </a>


        <a href="../index.php#composer">

            ➕ Create Post

        </a>


        <a href="../index.php?mine=1">

            📝 My Posts

        </a>


        <a href="../messages/messages.php">

            💬 Messages

        </a>

    </div>


    

    <div class="profile">


        <div
            class="profile-btn"
            id="profileBtn"
        >

            👤

            <?php echo htmlspecialchars($username); ?>

            ▼

        </div>


        <div
            class="dropdown"
            id="dropdown"
        >

            <a href="profile.php">

                👤 Profile

            </a>


            <a href="edit-profile.php">

                ✏️ Edit Profile

            </a>


            <a href="../index.php#composer">

                📝 Create Post

            </a>


            <a href="../index.php?mine=1">

                📝 My Posts

            </a>


            <a href="../messages/messages.php">

                💬 Messages

            </a>


            <a href="../index.php">

                🏠 Home Feed

            </a>


            <a href="../auth/logout.php">

                🚪 Logout

            </a>

        </div>

    </div>

</nav>





<div class="container">


    <div class="card">


        

        <div class="avatar">

            <?php if (!empty($avatar)): ?>

                <img
                    src="../../<?php echo htmlspecialchars($avatar); ?>"
                    alt="Profile Picture"
                >

            <?php else: ?>

                <?php echo htmlspecialchars($first_char); ?>

            <?php endif; ?>

        </div>



        

        <h2>

            Welcome,

            <?php echo htmlspecialchars($username); ?>

        </h2>



        

        <?php if (!empty($bio)): ?>

            <div class="bio">

                <?php
                echo nl2br(
                    htmlspecialchars($bio)
                );
                ?>

            </div>

        <?php endif; ?>



        

        <?php if ($success): ?>

            <div class="success">

                <?php echo htmlspecialchars($success); ?>

            </div>

        <?php endif; ?>



        

        <div class="info">


            <p>

                <b>Chat ID :</b>

                <span class="chat-id">

                    <?php if (!empty($chat_id)): ?>

                        <?php echo htmlspecialchars($chat_id); ?>

                    <?php else: ?>

                        Not created yet

                    <?php endif; ?>

                </span>

            </p>


            <p>

                <b>Username :</b>

                <?php echo htmlspecialchars($username); ?>

            </p>


            <p>

                <b>Email :</b>

                <?php echo htmlspecialchars($email); ?>

            </p>


        </div>



       

        <a
            href="edit-profile.php"
            class="edit-button"
        >

            ✏️ Edit Profile

        </a>


    </div>

</div>





<?php if (empty($chat_id)): ?>

<div class="modal">


    <div class="modal-box">


        <div class="modal-icon">

            💬

        </div>


        <h2>

            Create Your Chat ID

        </h2>


        <p>

            Create a unique Chat ID so other users
            can find you and send you messages.

        </p>


        <?php if ($error): ?>

            <div class="error">

                <?php echo htmlspecialchars($error); ?>

            </div>

        <?php endif; ?>


        <form method="POST">


            <input
                type="text"
                name="chat_id"
                class="chat-input"
                placeholder="Enter your Chat ID"
                value="<?php
                    echo htmlspecialchars(
                        $_POST['chat_id'] ?? $suggested_chat_id
                    );
                ?>"
                maxlength="30"
                autocomplete="off"
                required
            >


            <div class="suggestion">

                Suggested ID:

                <span>

                    <?php echo htmlspecialchars($suggested_chat_id); ?>

                </span>

            </div>


            <button
                type="submit"
                name="create_chat_id"
                class="create-id-button"
            >

                Create Chat ID

            </button>


        </form>


    </div>

</div>

<?php endif; ?>



<script>



const btn = document.getElementById("profileBtn");

const menu = document.getElementById("dropdown");


btn.addEventListener("click", function(event) {

    event.stopPropagation();

    menu.classList.toggle("show");

});


document.addEventListener("click", function() {

    menu.classList.remove("show");

});


menu.addEventListener("click", function(event) {

    event.stopPropagation();

});

</script>


</body>

</html>
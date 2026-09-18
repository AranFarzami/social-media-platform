<?php

session_start();

require_once "../../config/database.php";



if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: ../auth/login.php");
    exit();
}




$user_id = $_SESSION['user_id'];




$success_message = "";
$error_message = "";

$username = "";
$email = "";
$bio = "";
$avatar = "";



$stmt = $pdo->prepare("
    SELECT id, username, email, bio, avatar, password
    FROM users
    WHERE id = ?
");

$stmt->execute([$user_id]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$user) {

    session_destroy();

    header("Location: ../auth/login.php");
    exit();

}




$username = $user['username'];
$email = $user['email'];
$bio = $user['bio'] ?? "";
$avatar = $user['avatar'] ?? "";



if ($_SERVER["REQUEST_METHOD"] === "POST") {

   
    

    $new_username = trim($_POST['username'] ?? "");
    $new_email = trim($_POST['email'] ?? "");
    $new_bio = trim($_POST['bio'] ?? "");


   

    if ($new_username === "") {

        $error_message = "Username cannot be empty.";

    } elseif ($new_email === "") {

        $error_message = "Email cannot be empty.";

    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {

        $error_message = "Please enter a valid email address.";

    } elseif (strlen($new_username) > 50) {

        $error_message = "Username cannot be longer than 50 characters.";

    } elseif (strlen($new_bio) > 200) {

        $error_message = "Bio cannot be longer than 200 characters.";

    }


   

    if ($error_message === "") {

        $stmt = $pdo->prepare("
            SELECT id
            FROM users
            WHERE (username = ? OR email = ?)
            AND id != ?
        ");

        $stmt->execute([
            $new_username,
            $new_email,
            $user_id
        ]);

        $existing_user = $stmt->fetch(PDO::FETCH_ASSOC);


        if ($existing_user) {

            $error_message = "Username or email is already in use.";

        }

    }


   

    $new_avatar = $avatar;

    if (
        $error_message === "" &&
        isset($_FILES['avatar']) &&
        $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        $file = $_FILES['avatar'];


       

        if ($file['error'] !== UPLOAD_ERR_OK) {

            $error_message = "There was a problem uploading the image.";

        }


       

        $max_size = 2 * 1024 * 1024; // 2 MB

        if (
            $error_message === "" &&
            $file['size'] > $max_size
        ) {

            $error_message = "Image size must be less than 2 MB.";

        }


       

        if ($error_message === "") {

            $finfo = new finfo(FILEINFO_MIME_TYPE);

            $mime_type = $finfo->file($file['tmp_name']);


            $allowed_types = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp'
            ];


            if (!isset($allowed_types[$mime_type])) {

                $error_message = "Only JPG, PNG and WEBP images are allowed.";

            }

        }


       

        if ($error_message === "") {

            $upload_directory = "../../uploads/avatars/";


           

            if (!is_dir($upload_directory)) {

                mkdir($upload_directory, 0755, true);

            }


            

            $extension = $allowed_types[$mime_type];

            $file_name = bin2hex(random_bytes(16)) . "." . $extension;

            $file_path = $upload_directory . $file_name;


           

            if (move_uploaded_file($file['tmp_name'], $file_path)) {

                $new_avatar = "uploads/avatars/" . $file_name;


               

                if (!empty($avatar)) {

                    $old_avatar_path = "../../" . $avatar;

                    if (
                        is_file($old_avatar_path) &&
                        strpos(realpath($old_avatar_path), realpath($upload_directory)) === 0
                    ) {

                        unlink($old_avatar_path);

                    }

                }

            } else {

                $error_message = "Failed to save the uploaded image.";

            }

        }

    }


    

    if ($error_message === "") {

        try {

            $pdo->beginTransaction();


            

            $stmt = $pdo->prepare("
                UPDATE users
                SET username = ?,
                    email = ?,
                    bio = ?,
                    avatar = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $new_username,
                $new_email,
                $new_bio,
                $new_avatar,
                $user_id
            ]);


          

            $current_password = $_POST['current_password'] ?? "";
            $new_password = $_POST['new_password'] ?? "";
            $confirm_password = $_POST['confirm_password'] ?? "";


           

            if (
                $current_password !== "" ||
                $new_password !== "" ||
                $confirm_password !== ""
            ) {

                if ($current_password === "") {

                    throw new Exception("Please enter your current password.");

                }

                if (!password_verify($current_password, $user['password'])) {

                    throw new Exception("Current password is incorrect.");

                }

                if ($new_password === "") {

                    throw new Exception("Please enter a new password.");

                }

                if (strlen($new_password) < 8) {

                    throw new Exception("New password must be at least 8 characters.");

                }

                if ($new_password !== $confirm_password) {

                    throw new Exception("New passwords do not match.");

                }


                

                $hashed_password = password_hash(
                    $new_password,
                    PASSWORD_DEFAULT
                );


                

                $stmt = $pdo->prepare("
                    UPDATE users
                    SET password = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $hashed_password,
                    $user_id
                ]);

            }


           

            $pdo->commit();


            

            $_SESSION['username'] = $new_username;
            $_SESSION['email'] = $new_email;


           

            $username = $new_username;
            $email = $new_email;
            $bio = $new_bio;
            $avatar = $new_avatar;


            $success_message = "Profile updated successfully!";


        } catch (Exception $e) {

            if ($pdo->inTransaction()) {

                $pdo->rollBack();

            }

            $error_message = $e->getMessage();

        }

    }

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Mini Twitter | Edit Profile</title>


<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Arial, Helvetica, sans-serif;
}

body{

    background:#eef2f7;

}




nav{

    width:100%;
    height:70px;

    background:white;

    display:flex;

    justify-content:space-between;

    align-items:center;

    padding:0 50px;

    box-shadow:0 3px 10px rgba(0,0,0,.08);

}

.logo{

    color:#1DA1F2;

    font-size:28px;

    font-weight:bold;

}

.nav-links{

    display:flex;

    gap:30px;

}

.nav-links a{

    text-decoration:none;

    color:#444;

    font-weight:bold;

    transition:.3s;

}

.nav-links a:hover{

    color:#1DA1F2;

}




.profile{

    position:relative;

}

.profile-btn{

    background:#1DA1F2;

    color:white;

    padding:10px 18px;

    border-radius:25px;

    cursor:pointer;

    user-select:none;

    font-weight:bold;

}

.dropdown{

    position:absolute;

    top:55px;

    right:0;

    width:220px;

    background:white;

    border-radius:12px;

    box-shadow:0 5px 20px rgba(0,0,0,.15);

    overflow:hidden;

    display:none;

    z-index:100;

}

.dropdown.show{

    display:block;

}

.dropdown a{

    display:block;

    padding:15px;

    color:#333;

    text-decoration:none;

}

.dropdown a:hover{

    background:#f5f5f5;

}




.container{

    width:750px;

    max-width:95%;

    margin:40px auto;

}



.card{

    background:white;

    border-radius:15px;

    box-shadow:0 5px 20px rgba(0,0,0,.08);

    padding:35px;

    margin-bottom:25px;

}

.card h2{

    margin-bottom:30px;

    color:#222;

}




.success{

    background:#f0fff4;

    border-left:4px solid #48bb78;

    color:#276749;

    padding:14px;

    border-radius:8px;

    margin-bottom:20px;

}

.error{

    background:#fff5f5;

    border-left:4px solid #f56565;

    color:#c53030;

    padding:14px;

    border-radius:8px;

    margin-bottom:20px;

}




.avatar-container{

    text-align:center;

    margin-bottom:30px;

}

.avatar{

    width:120px;

    height:120px;

    border-radius:50%;

    margin:0 auto 15px;

    overflow:hidden;

    background:#1DA1F2;

    color:white;

    display:flex;

    justify-content:center;

    align-items:center;

    font-size:45px;

}

.avatar img{

    width:100%;

    height:100%;

    object-fit:cover;

}

.avatar-container label{

    display:inline-block;

    background:#1DA1F2;

    color:white;

    padding:10px 18px;

    border-radius:8px;

    cursor:pointer;

    font-weight:bold;

}

.avatar-container label:hover{

    background:#168bd0;

}

.avatar-container input{

    display:none;

}




.form-group{

    margin-bottom:22px;

}

.form-group label{

    display:block;

    margin-bottom:8px;

    font-weight:bold;

    color:#333;

}

.form-group input,
.form-group textarea{

    width:100%;

    padding:13px;

    border:2px solid #e2e8f0;

    border-radius:8px;

    outline:none;

    font-size:15px;

    transition:.3s;

}

.form-group input:focus,
.form-group textarea:focus{

    border-color:#1DA1F2;

    box-shadow:0 0 0 3px rgba(29,161,242,.1);

}

.form-group textarea{

    min-height:120px;

    resize:vertical;

}




.counter{

    text-align:right;

    font-size:12px;

    color:#777;

    margin-top:5px;

}




.save-btn{

    width:100%;

    border:none;

    padding:15px;

    background:#1DA1F2;

    color:white;

    font-size:16px;

    font-weight:bold;

    border-radius:8px;

    cursor:pointer;

    transition:.3s;

}

.save-btn:hover{

    background:#168bd0;

    transform:translateY(-1px);

}




.password-info{

    color:#777;

    font-size:14px;

    margin-bottom:20px;

}




@media(max-width:700px){

    nav{

        padding:0 15px;

    }

    .nav-links{

        display:none;

    }

    .container{

        width:95%;

    }

    .card{

        padding:25px;

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

<a href="../index.php">🏠 Home Feed</a>

<a href="../index.php#composer">➕ Create Post</a>

<a href="../index.php?mine=1">📝 My Posts</a>

<a href="../messages/messages.php">💬 Messages</a>

</div>


<div class="profile">

<div class="profile-btn" id="profileBtn">

👤 <?php echo htmlspecialchars($username); ?> ▼

</div>


<div class="dropdown" id="dropdown">

<a href="profile.php">👤 Profile</a>

<a href="edit-profile.php">✏️ Edit Profile</a>

<a href="../index.php#composer">📝 Create Post</a>

<a href="../index.php?mine=1">📝 My Posts</a>

<a href="../messages/messages.php">💬 Messages</a>

<a href="../index.php">🏠 Home Feed</a>

<a href="../auth/logout.php">🚪 Logout</a>

</div>

</div>

</nav>



<div class="container">




<div class="card">

<h2>✏️ Edit Profile</h2>


<?php if ($success_message !== ""): ?>

<div class="success">

✅ <?php echo htmlspecialchars($success_message); ?>

</div>

<?php endif; ?>


<?php if ($error_message !== ""): ?>

<div class="error">

❌ <?php echo htmlspecialchars($error_message); ?>

</div>

<?php endif; ?>


<form method="POST" enctype="multipart/form-data">




<div class="avatar-container">

<div class="avatar">

<?php if (!empty($avatar)): ?>

<img
    src="../../<?php echo htmlspecialchars($avatar); ?>"
    alt="Profile Picture"
>

<?php else: ?>

<?php
$first_char = strtoupper(
    substr($username, 0, 1)
);
?>

<?php echo htmlspecialchars($first_char); ?>

<?php endif; ?>

</div>


<label for="avatar">

📷 Change Profile Picture

</label>


<input
    type="file"
    name="avatar"
    id="avatar"
    accept="image/jpeg,image/png,image/webp"
>

</div>





<div class="form-group">

<label for="username">

Username

</label>

<input
    type="text"
    id="username"
    name="username"
    value="<?php echo htmlspecialchars($username); ?>"
    maxlength="50"
    required
>

</div>





<div class="form-group">

<label for="email">

Email

</label>

<input
    type="email"
    id="email"
    name="email"
    value="<?php echo htmlspecialchars($email); ?>"
    required
>

</div>





<div class="form-group">

<label for="bio">

Bio

</label>

<textarea
    id="bio"
    name="bio"
    maxlength="200"
><?php echo htmlspecialchars($bio); ?></textarea>

<div class="counter">

<span id="bioCounter">
<?php echo strlen($bio); ?>
</span>/200

</div>

</div>



<button
    type="submit"
    class="save-btn"
>

💾 Save Changes

</button>


</form>

</div>





<div class="card">

<h2>🔒 Change Password</h2>

<p class="password-info">

Leave these fields empty if you don't want to change your password.

</p>


<form method="POST" enctype="multipart/form-data">


<input
    type="hidden"
    name="username"
    value="<?php echo htmlspecialchars($username); ?>"
>

<input
    type="hidden"
    name="email"
    value="<?php echo htmlspecialchars($email); ?>"
>

<input
    type="hidden"
    name="bio"
    value="<?php echo htmlspecialchars($bio); ?>"
>


<div class="form-group">

<label for="current_password">

Current Password

</label>

<input
    type="password"
    id="current_password"
    name="current_password"
>

</div>


<div class="form-group">

<label for="new_password">

New Password

</label>

<input
    type="password"
    id="new_password"
    name="new_password"
    minlength="8"
>

</div>


<div class="form-group">

<label for="confirm_password">

Confirm New Password

</label>

<input
    type="password"
    id="confirm_password"
    name="confirm_password"
    minlength="8"
>

</div>


<button
    type="submit"
    class="save-btn"
>

🔐 Change Password

</button>

</form>

</div>


</div>



<script>



const profileBtn = document.getElementById("profileBtn");

const dropdown = document.getElementById("dropdown");


profileBtn.addEventListener("click", function(event){

    event.stopPropagation();

    dropdown.classList.toggle("show");

});


document.addEventListener("click", function(){

    dropdown.classList.remove("show");

});


dropdown.addEventListener("click", function(event){

    event.stopPropagation();

});




const bio = document.getElementById("bio");

const bioCounter = document.getElementById("bioCounter");


bio.addEventListener("input", function(){

    bioCounter.textContent = bio.value.length;

});




const avatarInput = document.getElementById("avatar");

const avatarContainer = document.querySelector(".avatar");


avatarInput.addEventListener("change", function(){

    const file = this.files[0];

    if (!file) {
        return;
    }


   

    const allowedTypes = [
        "image/jpeg",
        "image/png",
        "image/webp"
    ];


    if (!allowedTypes.includes(file.type)) {

        alert("Only JPG, PNG and WEBP images are allowed.");

        this.value = "";

        return;

    }


    

    if (file.size > 2 * 1024 * 1024) {

        alert("Image size must be less than 2 MB.");

        this.value = "";

        return;

    }


    

    const reader = new FileReader();


    reader.onload = function(event){

        avatarContainer.innerHTML = `
            <img
                src="${event.target.result}"
                alt="Avatar Preview"
            >
        `;

    };


    reader.readAsDataURL(file);

});

</script>


</body>

</html>
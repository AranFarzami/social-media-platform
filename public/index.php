<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: auth/login.php");
    exit();
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user_id = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT id, username, email, bio, avatar, chat_id
    FROM users
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$user_id]);

$current_user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$current_user) {
    session_destroy();
    header("Location: auth/login.php");
    exit();
}

$mine = isset($_GET['mine']) && $_GET['mine'] === '1';

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mini Twitter | <?php echo $mine ? 'My Posts' : 'Home Feed'; ?></title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, Helvetica, sans-serif;
    background: #eef2f7;
    color: #222;
}

nav {
    width: 100%;
    min-height: 70px;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 25px;
    padding: 0 50px;
    box-shadow: 0 3px 10px rgba(0,0,0,.08);
}

.logo {
    color: #1DA1F2;
    font-size: 26px;
    font-weight: bold;
    text-decoration: none;
}

.nav-links {
    display: flex;
    align-items: center;
    gap: 25px;
}

.nav-links a {
    color: #444;
    text-decoration: none;
    font-weight: bold;
}

.nav-links a:hover {
    color: #1DA1F2;
}

.profile-btn {
    background: #1DA1F2;
    color: #fff;
    text-decoration: none;
    padding: 10px 18px;
    border-radius: 25px;
    font-weight: bold;
}

.container {
    width: 650px;
    max-width: calc(100% - 30px);
    margin: 35px auto;
}

.card {
    background: #fff;
    border-radius: 15px;
    padding: 22px;
    margin-bottom: 20px;
    box-shadow: 0 5px 20px rgba(0,0,0,.07);
}

.composer textarea {
    width: 100%;
    min-height: 120px;
    resize: vertical;
    border: 1px solid #d9dee7;
    border-radius: 12px;
    padding: 15px;
    font-size: 15px;
    font-family: inherit;
    outline: none;
}

.composer textarea:focus {
    border-color: #1DA1F2;
    box-shadow: 0 0 0 3px rgba(29,161,242,.1);
}

.composer-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 12px;
}

.counter {
    color: #777;
    font-size: 13px;
}

.btn {
    border: none;
    border-radius: 9px;
    padding: 11px 20px;
    font-weight: bold;
    cursor: pointer;
}

.btn-primary {
    background: #1DA1F2;
    color: #fff;
}

.btn-danger {
    background: #fff;
    color: #e53935;
    border: 1px solid #e53935;
    padding: 7px 12px;
}

.btn:disabled {
    opacity: .6;
    cursor: not-allowed;
}

.feed-title {
    margin-bottom: 18px;
}

.post {
    background: #fff;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,.06);
}

.post-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.avatar {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    overflow: hidden;
    background: #1DA1F2;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 19px;
    flex-shrink: 0;
}

.avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.username {
    font-weight: bold;
}

.post-date {
    color: #888;
    font-size: 12px;
    margin-top: 3px;
}

.post-content {
    white-space: pre-wrap;
    overflow-wrap: anywhere;
    line-height: 1.6;
    margin: 18px 0 5px;
    font-size: 15px;
}

.empty {
    text-align: center;
    color: #777;
    padding: 35px 15px;
}

.status {
    display: none;
    padding: 12px 15px;
    border-radius: 10px;
    margin-bottom: 15px;
}

.status.error {
    display: block;
    background: #ffe9e9;
    color: #b42318;
}

.status.success {
    display: block;
    background: #e8f7ee;
    color: #137333;
}

@media (max-width: 900px) {
    nav {
        padding: 15px 20px;
        flex-wrap: wrap;
    }

    .nav-links {
        order: 3;
        width: 100%;
        justify-content: center;
        padding-bottom: 5px;
    }
}

@media (max-width: 600px) {
    .nav-links {
        gap: 12px;
        font-size: 13px;
    }
}
</style>
</head>

<body>

<nav>
    <a href="index.php" class="logo">Mini Twitter</a>

    <div class="nav-links">
        <a href="index.php">🏠 Home</a>
        <a href="index.php#composer">➕ Create Post</a>
        <a href="index.php?mine=1">📝 My Posts</a>
        <a href="messages/messages.php">💬 Messages</a>
    </div>

    <a href="profile/profile.php" class="profile-btn">
        👤 <?php echo htmlspecialchars($current_user['username'], ENT_QUOTES, 'UTF-8'); ?>
    </a>
</nav>

<div class="container">

    <div id="status" class="status"></div>

    <div class="card composer" id="composer">
        <h2 style="margin-bottom:15px;">
            <?php echo $mine ? 'My Posts' : 'Create a Post'; ?>
        </h2>

        <?php if (!$mine): ?>
        <form id="postForm">
            <textarea
                id="content"
                name="content"
                maxlength="5000"
                placeholder="What's happening?"
                required
            ></textarea>

            <div class="composer-footer">
                <span class="counter">
                    <span id="counter">0</span>/5000
                </span>

                <button type="submit" class="btn btn-primary" id="postButton">
                    Post
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>

    <h2 class="feed-title">
        <?php echo $mine ? 'My Posts' : 'Home Feed'; ?>
    </h2>

    <div id="feed">
        <div class="empty">Loading posts...</div>
    </div>

</div>

<script>
const currentUserId = <?php echo $user_id; ?>;
const csrfToken = <?php echo json_encode($_SESSION['csrf_token']); ?>;
const mine = <?php echo $mine ? 'true' : 'false'; ?>;

const feed = document.getElementById("feed");
const statusBox = document.getElementById("status");

function showStatus(message, type) {
    statusBox.textContent = message;
    statusBox.className = "status " + type;

    setTimeout(function() {
        statusBox.className = "status";
        statusBox.textContent = "";
    }, 3500);
}

function escapeAvatarPath(path) {
    return "../" + path.split("/").map(function(part) {
        return encodeURIComponent(part);
    }).join("/");
}

function createAvatar(username, avatar) {
    const wrapper = document.createElement("div");
    wrapper.className = "avatar";

    if (avatar) {
        const img = document.createElement("img");
        img.src = escapeAvatarPath(avatar);
        img.alt = "Profile Picture";
        wrapper.appendChild(img);
    } else {
        wrapper.textContent = username.charAt(0).toUpperCase();
    }

    return wrapper;
}

function createPostElement(post) {
    const article = document.createElement("article");
    article.className = "post";

    const header = document.createElement("div");
    header.className = "post-header";

    const userInfo = document.createElement("div");
    userInfo.className = "user-info";

    userInfo.appendChild(
        createAvatar(post.username, post.avatar)
    );

    const details = document.createElement("div");

    const username = document.createElement("div");
    username.className = "username";
    username.textContent = post.username;

    const date = document.createElement("div");
    date.className = "post-date";
    date.textContent = post.created_at;

    details.appendChild(username);
    details.appendChild(date);

    userInfo.appendChild(details);
    header.appendChild(userInfo);

    if (Number(post.user_id) === currentUserId) {
        const deleteButton = document.createElement("button");
        deleteButton.className = "btn btn-danger";
        deleteButton.textContent = "Delete";

        deleteButton.addEventListener("click", function() {
            deletePost(post.id);
        });

        header.appendChild(deleteButton);
    }

    const content = document.createElement("div");
    content.className = "post-content";
    content.textContent = post.content;

    article.appendChild(header);
    article.appendChild(content);

    return article;
}

async function loadFeed() {
    try {
        const url = mine
            ? "../api/posts/feed.php?mine=1"
            : "../api/posts/feed.php";

        const response = await fetch(url, {
            credentials: "same-origin"
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || "Could not load posts.");
        }

        feed.innerHTML = "";

        if (!data.posts || data.posts.length === 0) {
            const empty = document.createElement("div");
            empty.className = "card empty";
            empty.textContent = mine
                ? "You have not created any posts yet."
                : "No posts yet. Be the first to post!";
            feed.appendChild(empty);
            return;
        }

        data.posts.forEach(function(post) {
            feed.appendChild(createPostElement(post));
        });

    } catch (error) {
        feed.innerHTML = "";

        const empty = document.createElement("div");
        empty.className = "card empty";
        empty.textContent = error.message;
        feed.appendChild(empty);
    }
}

async function deletePost(postId) {
    if (!confirm("Delete this post?")) {
        return;
    }

    const formData = new FormData();
    formData.append("post_id", postId);
    formData.append("csrf_token", csrfToken);

    try {
        const response = await fetch("../api/posts/delete.php", {
            method: "POST",
            body: formData,
            credentials: "same-origin"
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || "Could not delete post.");
        }

        showStatus("Post deleted successfully.", "success");
        loadFeed();

    } catch (error) {
        showStatus(error.message, "error");
    }
}

const postForm = document.getElementById("postForm");
const content = document.getElementById("content");
const counter = document.getElementById("counter");

if (postForm) {
    content.addEventListener("input", function() {
        counter.textContent = content.value.length;
    });

    postForm.addEventListener("submit", async function(event) {
        event.preventDefault();

        const text = content.value.trim();

        if (text === "") {
            showStatus("Post cannot be empty.", "error");
            return;
        }

        const formData = new FormData();
        formData.append("content", text);
        formData.append("csrf_token", csrfToken);

        const button = document.getElementById("postButton");
        button.disabled = true;

        try {
            const response = await fetch("../api/posts/create.php", {
                method: "POST",
                body: formData,
                credentials: "same-origin"
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || "Could not create post.");
            }

            content.value = "";
            counter.textContent = "0";

            showStatus("Post created successfully.", "success");
            loadFeed();

        } catch (error) {
            showStatus(error.message, "error");
        } finally {
            button.disabled = false;
        }
    });
}

loadFeed();
</script>

</body>
</html>

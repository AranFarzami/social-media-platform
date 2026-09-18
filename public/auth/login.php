<?php

session_start();
require_once "../../config/database.php";

if (isset($_SESSION['login']) && $_SESSION['login'] === true) {
    header("Location: ../profile/profile.php");
    exit();
}

function user_login()
{
    global $pdo;

    if (
        isset($_POST['email']) &&
        isset($_POST['password'])
    ) {

        $email = $_POST['email'];
        $password = $_POST['password'];

        
        $stmt = $pdo->prepare("
            SELECT id, username, email, password
            FROM users
            WHERE email = ?
        ");

        $stmt->execute([$email]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        
        if (!$result) {
            echo "This user is not registered. Please create an account!";
            return;
        }
     

        
        if (password_verify($password, $result['password'])) {

            $_SESSION['login'] = true;
            $_SESSION['user_id'] = $result['id'];
            $_SESSION['username'] = $result['username'];
            $_SESSION['email'] = $result['email'];
            

            header("Location: ../profile/profile.php");
            exit();

        } else {

            echo "Incorrect password.";
            return;

        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    user_login();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Panel</title>
    
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        
        body::before {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            top: -100px;
            right: -100px;
            animation: float 6s ease-in-out infinite;
        }

        body::after {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            bottom: -50px;
            left: -50px;
            animation: float 8s ease-in-out infinite reverse;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(30px, -30px); }
        }

        .login {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 50px 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 450px;
            position: relative;
            z-index: 1;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #4facfe 0%, #00f2fe 100%);
            border-radius: 20px 20px 0 0;
        }

        .login-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .login-header .icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 35px;
            color: white;
            box-shadow: 0 10px 30px rgba(79, 172, 254, 0.3);
            transition: transform 0.3s;
        }

        .login-header .icon:hover {
            transform: scale(1.1) rotate(5deg);
        }

        .login-header h2 {
            color: #2d3748;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .login-header p {
            color: #718096;
            font-size: 14px;
            font-weight: 300;
        }

        .form-group {
            position: relative;
            margin-bottom: 25px;
        }

        .form-group .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
            font-size: 16px;
            transition: color 0.3s;
        }

        .form-group input {
            width: 100%;
            padding: 14px 45px 14px 45px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s;
            background: #f7fafc;
            color: #2d3748;
            outline: none;
        }

        .form-group input:focus {
            border-color: #4facfe;
            background: white;
            box-shadow: 0 0 0 4px rgba(79, 172, 254, 0.1);
        }

        .form-group input:focus + .input-icon {
            color: #4facfe;
        }

        .form-group input::placeholder {
            color: #a0aec0;
            font-weight: 300;
        }

        .form-group .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
            cursor: pointer;
            transition: color 0.3s;
        }

        .form-group .toggle-password:hover {
            color: #4facfe;
        }

        .submit-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 10px 30px rgba(79, 172, 254, 0.3);
            position: relative;
            overflow: hidden;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 40px rgba(79, 172, 254, 0.4);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .submit-btn::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transform: rotate(45deg);
            transition: all 0.5s;
        }

        .submit-btn:hover::after {
            left: 100%;
        }

        .register-link {
            text-align: center;
            margin-top: 20px;
            color: #718096;
            font-size: 14px;
        }

        .register-link a {
            color: #4facfe;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .register-link a:hover {
            color: #00f2fe;
            text-decoration: underline;
        }

        .error-message {
            background: #fff5f5;
            border-left: 4px solid #fc8181;
            padding: 12px 16px;
            border-radius: 8px;
            color: #c53030;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .error-message i {
            font-size: 18px;
        }

        .success-message {
            background: #f0fff4;
            border-left: 4px solid #68d391;
            padding: 12px 16px;
            border-radius: 8px;
            color: #276749;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .success-message i {
            font-size: 18px;
        }

        @media (max-width: 480px) {
            .login {
                padding: 30px 20px;
            }

            .login-header h2 {
                font-size: 24px;
            }

            .form-group input {
                padding: 12px 40px 12px 40px;
                font-size: 13px;
            }
        }

        
        .remember-me {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 25px;
            color: #4a5568;
            font-size: 14px;
            cursor: pointer;
        }

        .remember-me input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #4facfe;
            cursor: pointer;
        }
    </style>
</head>
<body>

<div class="login">
    <div class="login-header">
        <div class="icon">
            <i class="fas fa-sign-in-alt"></i>
        </div>
        <h2>Welcome Back</h2>
        <p>Sign in to continue your journey</p>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <form method="post" id="loginForm">
        <div class="form-group">
            <i class="fas fa-envelope input-icon"></i>
            <input type="email" name="email" placeholder="Email Address" required>
        </div>

        <div class="form-group">
            <i class="fas fa-lock input-icon"></i>
            <input type="password" name="password" id="password" placeholder="Password" required>
            <i class="fas fa-eye-slash toggle-password" id="togglePassword"></i>
        </div>

        <div class="remember-me">
            <input type="checkbox" id="remember">
            <label for="remember">Remember me</label>
        </div>

        <button type="submit" class="submit-btn">
            <i class="fas fa-arrow-right" style="margin-right: 10px;"></i>
            Sign In
        </button>
    </form>

    <div class="register-link">
        Don't have an account? <a href="register.php">Create Account</a>
    </div>
</div>

<script>

document.getElementById('togglePassword').addEventListener('click', function() {
    const password = document.getElementById('password');
    const icon = this;
    
    if (password.type === 'password') {
        password.type = 'text';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    } else {
        password.type = 'password';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    }
});


document.getElementById('loginForm').addEventListener('submit', function(e) {
    const btn = this.querySelector('.submit-btn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 10px;"></i> Signing in...';
    btn.style.opacity = '0.8';
    btn.style.cursor = 'not-allowed';
});


document.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        const form = document.getElementById('loginForm');
        if (form) {
            form.submit();
        }
    }
});
</script>

</body>
</html>
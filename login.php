<?php
require_once __DIR__ . '/admin_auth.php';
ensure_admin_schema($pdo);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['is_admin'] = ($user['role'] ?? 'user') === 'admin';
        header("Location: " . ($_SESSION['is_admin'] ? "admin.php" : "index.php"));
        exit();
    } else {
        $error = "Пошта немесе құпиясөз қате!";
    }
}
?>
<!DOCTYPE html>
<html lang="kk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Жүйеге кіру - Baspana Smart</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #F7F7F8;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 24px;
            color: #1A1A1A;
        }
        .auth-card {
            background: #fff;
            padding: 36px;
            border-radius: 14px;
            width: 100%;
            max-width: 420px;
            border: 1px solid #EBEBEB;
        }
        .logo-title { text-align: center; font-size: 1.65rem; font-weight: 800; color: #1A1A1A; margin-bottom: 8px; }
        .logo-title i { color: #E12A2A; }
        .subtitle { text-align: center; color: #757575; font-weight: 500; margin-bottom: 28px; }
        .input-group { position: relative; margin-bottom: 20px; }
        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #757575;
            font-size: 1.1rem;
        }
        input { 
            width: 100%; 
            padding: 16px 16px 16px 45px; 
            box-sizing: border-box; 
            border: 1px solid transparent; 
            border-radius: 12px; 
            font-size: 1rem;
            font-family: inherit;
            background: #F1F1F1;
            transition: all 0.3s;
        }
        input:focus {
            border-color: #E12A2A;
            background: #ffffff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(225, 42, 42, 0.1);
        }
        button { 
            width: 100%; 
            padding: 16px; 
            background: #E12A2A; 
            color: white; 
            border: none; 
            border-radius: 12px; 
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer; 
            transition: background 0.2s, transform 0.2s;
            margin-top: 10px;
        }
        button:hover { 
            transform: translateY(-1px);
            background: #C52222;
        }
        .error-msg { background: #fee2e2; color: #b91c1c; padding: 12px; border-radius: 8px; text-align: center; font-weight: 500; margin-bottom: 20px; border: 1px solid #fca5a5; }
        .success-msg { background: #d1fae5; color: #047857; padding: 12px; border-radius: 8px; text-align: center; font-weight: 500; margin-bottom: 20px; border: 1px solid #6ee7b7; }
        .bottom-links {
            text-align: center;
            margin-top: 25px;
            font-weight: 500;
            color: #757575;
        }
        .bottom-links a {
            color: #E12A2A;
            text-decoration: none;
            font-weight: 700;
            transition: color 0.3s;
        }
        .bottom-links a:hover { color: #C52222; text-decoration: underline; }
        .back-home {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #757575;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .back-home:hover { color: #E12A2A; }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="logo-title"><i class="fas fa-home"></i> Baspana Smart</div>
        <div class="subtitle">Жеке кабинетке кіру</div>
        
        <?php 
            if(isset($_SESSION['success'])) {
                echo "<div class='success-msg'><i class='fas fa-check-circle'></i> ".$_SESSION['success']."</div>";
                unset($_SESSION['success']);
            }
            if(isset($error)) echo "<div class='error-msg'><i class='fas fa-exclamation-circle'></i> $error</div>"; 
        ?>
        
        <form method="POST">
            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Электронды пошта" required>
            </div>
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Құпиясөз" required>
            </div>
            <button type="submit">Жүйеге кіру <i class="fas fa-sign-in-alt" style="margin-left: 8px;"></i></button>
        </form>
        
        <div class="bottom-links">
            Аккаунтыңыз жоқ па? <a href="register.php">Тіркелу</a>
        </div>
        <a href="index.php" class="back-home"><i class="fas fa-arrow-left"></i> Басты бетке қайту</a>
    </div>
</body>
</html>

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

const BASPANA_ADMIN_EMAIL = 'admin@baspana.kz';
const BASPANA_ADMIN_PASSWORD = 'admin12345';

function admin_clean($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function ensure_admin_schema(PDO $pdo) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            age INT NULL,
            city VARCHAR(80) NULL,
            social_status VARCHAR(80) NULL,
            savings BIGINT NULL,
            income BIGINT NULL,
            role VARCHAR(30) NOT NULL DEFAULT 'user',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $columns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    $missing = [
        'age' => "ALTER TABLE users ADD COLUMN age INT NULL",
        'city' => "ALTER TABLE users ADD COLUMN city VARCHAR(80) NULL",
        'social_status' => "ALTER TABLE users ADD COLUMN social_status VARCHAR(80) NULL",
        'savings' => "ALTER TABLE users ADD COLUMN savings BIGINT NULL",
        'income' => "ALTER TABLE users ADD COLUMN income BIGINT NULL",
        'role' => "ALTER TABLE users ADD COLUMN role VARCHAR(30) NOT NULL DEFAULT 'user'",
        'created_at' => "ALTER TABLE users ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
    ];

    foreach ($missing as $column => $sql) {
        if (!in_array($column, $columns, true)) {
            $pdo->exec($sql);
        }
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([BASPANA_ADMIN_EMAIL]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin) {
        $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE email = ?");
        $stmt->execute([BASPANA_ADMIN_EMAIL]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
        $stmt->execute(['Администратор', BASPANA_ADMIN_EMAIL, password_hash(BASPANA_ADMIN_PASSWORD, PASSWORD_DEFAULT)]);
    }
}

function current_user_is_admin(PDO $pdo) {
    if (empty($_SESSION['user_id'])) {
        return false;
    }

    if (!empty($_SESSION['is_admin'])) {
        return true;
    }

    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $role = $stmt->fetchColumn();
    $is_admin = $role === 'admin';
    $_SESSION['is_admin'] = $is_admin;
    return $is_admin;
}

function require_admin(PDO $pdo) {
    ensure_admin_schema($pdo);

    if (!current_user_is_admin($pdo)) {
        header("Location: login.php");
        exit;
    }
}
?>

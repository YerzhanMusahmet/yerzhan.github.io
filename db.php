<?php
$host = "sql113.infinityfree.com";
$dbname = "if0_42191133_yerzhan";
$username = "if0_42191133";
$password = "yerzhan07";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Дерекқорға қосылу мүмкін болмады: " . $e->getMessage());
}
?>
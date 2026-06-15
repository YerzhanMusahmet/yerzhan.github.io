<?php
$host = 'localhost';
$dbname = 'baspana_db';
$username = 'root'; // XAMPP-та әдепкі логин осылай
$password = '';     // XAMPP-та әдепкі құпиясөз бос болады

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Дерекқорға қосылу мүмкін болмады: " . $e->getMessage());
}
?>
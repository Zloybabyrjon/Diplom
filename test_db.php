<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=diplom;charset=utf8mb4', 'root', 'AXZVOPBA1023');
    echo "✅ Connected successfully\n";
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "\n";
}
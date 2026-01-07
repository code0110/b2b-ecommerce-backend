<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Dropping database b2b2...\n";
    $pdo->exec("DROP DATABASE IF EXISTS b2b2");
    
    echo "Creating database b2b2...\n";
    $pdo->exec("CREATE DATABASE b2b2");
    
    echo "Database b2b2 created successfully.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

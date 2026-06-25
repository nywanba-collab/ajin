<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;charset=utf8mb4', 'root', 'root123');
    echo "Connected OK\n";
    $stmt = $pdo->query("SHOW DATABASES LIKE 'agent_system'");
    if ($stmt->fetch()) {
        echo "Database 'agent_system' exists\n";
    } else {
        echo "Database 'agent_system' does NOT exist\n";
    }
} catch(Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
}

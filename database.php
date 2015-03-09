<?php
try {
    $db = new PDO("mysql:host=127.0.0.1;dbname=imasters;", "root", "123456");
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit;
}
?>
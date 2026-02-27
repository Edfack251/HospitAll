<?php
require_once 'app/config/database.php';
try {
    $stmt = $pdo->query("DESCRIBE pacientes");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
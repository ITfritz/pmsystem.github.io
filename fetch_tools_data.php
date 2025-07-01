<?php
require 'connect.php';

try {
    $stmt = $db->query("SELECT tool_name, usage_count FROM tools");
    $tools = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $labels = array_column($tools, 'tool_name');
    $values = array_column($tools, 'usage_count');

    echo json_encode([
        'success' => true,
        'labels' => $labels,
        'values' => $values
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
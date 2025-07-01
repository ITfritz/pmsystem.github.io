<?php
require_once 'connect.php';

$logisticsId = $_GET['logistics_id'] ?? null;

if ($logisticsId) {
    $stmt = $db->prepare("SELECT * FROM logistics WHERE logistics_id = ?");
    $stmt->execute([$logisticsId]);
    $logistics = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($logistics) {
        echo json_encode($logistics);
        exit();
    }
}

http_response_code(404);
echo json_encode(['error' => 'Logistics entry not found.']);
?>
<?php
session_start();
require_once 'connect.php';
require_once 'functions.php';

// Verify admin access
if (!isAdmin()) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Access denied.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientEmail = sanitizeInput($_POST['client_email']);
    $materials = sanitizeInput($_POST['materials']);
    $shipmentStatus = sanitizeInput($_POST['shipment_status']);
    $shipmentDate = sanitizeInput($_POST['shipment_date']);
    $deliveryDate = sanitizeInput($_POST['delivery_date']);
    $location = sanitizeInput($_POST['location']);

    try {
        $stmt = $db->prepare("
            INSERT INTO logistics (client_email, materials, shipment_status, shipment_date, delivery_date, location)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$clientEmail, $materials, $shipmentStatus, $shipmentDate, $deliveryDate, $location]);

        echo json_encode(['success' => 'Logistics entry added successfully.']);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode(['error' => 'An error occurred while adding the logistics entry.']);
    }
}
?>
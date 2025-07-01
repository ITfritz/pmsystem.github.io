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
    $logisticsId = sanitizeInput($_POST['logistics_id']);
    $clientEmail = sanitizeInput($_POST['client_email']);
    $materials = sanitizeInput($_POST['materials']);
    $shipmentStatus = sanitizeInput($_POST['shipment_status']);
    $shipmentDate = sanitizeInput($_POST['shipment_date']);
    $deliveryDate = sanitizeInput($_POST['delivery_date']);
    $location = sanitizeInput($_POST['location']);

    try {
        $stmt = $db->prepare("
            UPDATE logistics
            SET client_email = ?, materials = ?, shipment_status = ?, shipment_date = ?, delivery_date = ?, location = ?
            WHERE logistics_id = ?
        ");
        $stmt->execute([$clientEmail, $materials, $shipmentStatus, $shipmentDate, $deliveryDate, $location, $logisticsId]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => 'Logistics entry updated successfully.']);
        } else {
            http_response_code(404); // Not Found
            echo json_encode(['error' => 'Logistics entry not found or no changes made.']);
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        http_response_code(500); // Internal Server Error
        echo json_encode(['error' => 'An error occurred while updating the logistics entry.']);
    }
}
?>
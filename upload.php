<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['filled_form'])) {
    $file = $_FILES['filled_form'];
    $uploadDirectory = "uploads/";

    if (!is_dir($uploadDirectory)) {
        mkdir($uploadDirectory, 0755, true);
    }

    $uploadPath = $uploadDirectory . basename($file['name']);

    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        echo "File uploaded successfully!";
    } else {
        echo "Error uploading file.";
    }
}
?>
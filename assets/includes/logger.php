<?php
// assets/includes/logger.php

function logAction($conn, $user_id, $action_type, $details) {
    // Get the user's IP address
    $ip_address = $_SERVER['REMOTE_ADDR'];

    // Prepare the SQL statement
    $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action_type, details, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $user_id, $action_type, $details, $ip_address);
    
    // Execute the query
    $stmt->execute();
    $stmt->close();
}
?>
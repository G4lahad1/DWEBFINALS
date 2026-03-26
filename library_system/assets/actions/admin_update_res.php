<?php
session_start();

// 1. Admin-only
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../../index.php");
    exit;
}

require '../includes/db_connection.php';
require '../includes/logger.php';

$reservation_id = (int)($_GET['id']     ?? 0);
$action         = $_GET['action']        ?? '';
$admin_username = $_SESSION['username'] ?? 'Admin';

if (!$reservation_id || !$action) {
    die("Error: Missing reservation ID or action.");
}

// 2. Call sp_admin_update_reservation — validates action and fetches student_id
$stmt = $conn->prepare(
    "CALL sp_admin_update_reservation(?, ?, @p_student_id, @p_new_status, @p_result)"
);
$stmt->bind_param("is", $reservation_id, $action);
$stmt->execute();
$stmt->close();

$row        = $conn->query("SELECT @p_result AS result, @p_student_id AS sid, @p_new_status AS nstatus")->fetch_assoc();
$result     = $row['result']  ?? 'ERROR';
$student_id = $row['sid']     ?? 'Unknown';
$new_status = $row['nstatus'] ?? '';

switch ($result) {
    case 'SUCCESS':
        $log_type = ($action === 'approve') ? 'APPROVE_RESERVATION' : 'REJECT_RESERVATION';
        $details  = "Admin updated Reservation #$reservation_id for Student ID $student_id to: " . ucfirst($new_status);
        logAction($conn, $admin_username, $log_type, $details);
        header("Location: ../../admin/reservations.php?msg=$new_status");
        break;
    case 'INVALID_ACTION':
        die("Error: Invalid action. Only 'approve' or 'reject' are allowed.");
    default:
        header("Location: ../../admin/reservations.php?msg=error");
        break;
}
exit;
?>

<?php
session_start();
include '../includes/db_connection.php';
include '../includes/logger.php';

// 1. Check Login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../../index.php");
    exit;
}

// 2. Process the Form
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user_id    = $_SESSION['user_id'];
    $username   = $_SESSION['username'] ?? 'Student';
    $room_id    = $_POST['room_id']    ?? '';
    $date       = $_POST['date']       ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time   = $_POST['end_time']   ?? '';

    // 3. Basic server-side validation
    if (empty($room_id) || empty($date) || empty($start_time) || empty($end_time)) {
        header("Location: ../../reserve.php?msg=empty_fields");
        exit;
    }

    // 4. Call the stored procedure sp_process_reservation
    //    It handles: time validation, violation check, conflict check, and INSERT
    //    The OUT parameter @p_result receives the status string.
    $call_sql = "CALL sp_process_reservation(?, ?, ?, ?, ?, @p_result)";
    $stmt = $conn->prepare($call_sql);
    $stmt->bind_param("iisss", $user_id, $room_id, $date, $start_time, $end_time);
    $stmt->execute();
    $stmt->close();

    // 5. Retrieve the OUT parameter
    $result_row = $conn->query("SELECT @p_result AS result")->fetch_assoc();
    $result_code = $result_row['result'] ?? 'ERROR';

    // 6. Route based on what the procedure returned
    switch ($result_code) {
        case 'SUCCESS':
            $details = "Requested Room ID $room_id on $date ($start_time to $end_time).";
            logAction($conn, $username, 'REQUEST_RESERVATION', $details);
            header("Location: ../../reserve.php?msg=success");
            break;

        case 'CONFLICT':
            header("Location: ../../reserve.php?msg=collision");
            break;

        case 'INVALID_TIME':
            header("Location: ../../reserve.php?msg=invalid_time");
            break;

        case 'VIOLATION_BLOCK':
            // Student has an active suspension/penalty - block the reservation
            header("Location: ../../reserve.php?msg=violation_block");
            break;

        default:
            header("Location: ../../reserve.php?msg=error");
            break;
    }
    exit;

} else {
    header("Location: ../../dashboard.php");
    exit;
}
?>

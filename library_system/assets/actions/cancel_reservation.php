<?php
session_start();
require '../includes/db_connection.php';

// 1. Check Login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../../index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $reservation_id = (int)($_POST['reservation_id'] ?? 0);
    $user_id        = $_SESSION['user_id'];

    // 2. Call sp_cancel_reservation — ownership + status checks are in the procedure
    $stmt = $conn->prepare("CALL sp_cancel_reservation(?, ?, @p_result)");
    $stmt->bind_param("ii", $reservation_id, $user_id);
    $stmt->execute();
    $stmt->close();

    $row    = $conn->query("SELECT @p_result AS result")->fetch_assoc();
    $result = $row['result'] ?? 'ERROR';

    switch ($result) {
        case 'SUCCESS':
            header("Location: ../../history.php?msg=cancelled");
            break;
        case 'ALREADY_PROCESSED':
            header("Location: ../../history.php?msg=not_cancellable");
            break;
        default: // NOT_FOUND, ERROR
            header("Location: ../../history.php?msg=error");
            break;
    }
    exit;

} else {
    header("Location: ../../history.php");
    exit;
}
?>

<?php
session_start();

// 1. Admin-only
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../../index.php");
    exit;
}

require '../includes/db_connection.php';
require '../includes/logger.php';

$admin_id = $_SESSION['username'] ?? 'Admin';

// --- ACTION 1: ISSUE VIOLATION ---
if (isset($_POST['sanction_student'])) {
    $user_id = (int)$_POST['user_id'];
    $type    = $_POST['violation_type'];
    $desc    = $_POST['description']   ?? '';
    $penalty = $_POST['penalty'];

    // Call sp_issue_violation — validates inputs, sets violation_date to today
    $stmt = $conn->prepare("CALL sp_issue_violation(?, ?, ?, ?, @p_result)");
    $stmt->bind_param("isss", $user_id, $type, $desc, $penalty);
    $stmt->execute();
    $stmt->close();

    $row    = $conn->query("SELECT @p_result AS result")->fetch_assoc();
    $result = $row['result'] ?? 'ERROR';

    if ($result === 'SUCCESS') {
        $details = "Issued violation '$type' to Student ID $user_id. Penalty: $penalty.";
        logAction($conn, $admin_id, 'ISSUE_SANCTION', $details);
        header("Location: ../../admin/users.php?msg=sanctioned");
    } else {
        header("Location: ../../admin/users.php?error=failed");
    }
    exit;
}

// --- ACTION 2: RESOLVE (LIFT) ALL VIOLATIONS ---
if (isset($_GET['action']) && $_GET['action'] == 'resolve' && isset($_GET['user_id'])) {
    $user_id = (int)$_GET['user_id'];

    // Call sp_resolve_violations — internally calls fn_count_active_violations
    $stmt = $conn->prepare("CALL sp_resolve_violations(?, @p_result)");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    $row    = $conn->query("SELECT @p_result AS result")->fetch_assoc();
    $result = $row['result'] ?? 'ERROR';

    if (in_array($result, ['SUCCESS', 'NONE_FOUND'])) {
        $details = "Resolved/Lifted active sanctions for Student ID $user_id.";
        logAction($conn, $admin_id, 'RESOLVE_SANCTION', $details);
        header("Location: ../../admin/users.php?msg=resolved");
    } else {
        header("Location: ../../admin/users.php?error=failed");
    }
    exit;
}
?>

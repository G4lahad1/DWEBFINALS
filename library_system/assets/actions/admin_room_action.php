<?php
session_start();

// 1. Admin-only
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../../index.php");
    exit;
}

require '../includes/db_connection.php';

// --- ACTION: ADD ROOM ---
if (isset($_POST['add_room'])) {
    $name     = $_POST['room_name'];
    $type     = $_POST['room_type'];
    $capacity = (int)$_POST['capacity'];

    // sp_manage_room handles the INSERT
    $stmt = $conn->prepare("CALL sp_manage_room('add', 0, ?, ?, ?, @p_result)");
    $stmt->bind_param("ssi", $name, $type, $capacity);
    $stmt->execute();
    $stmt->close();

    $row    = $conn->query("SELECT @p_result AS result")->fetch_assoc();
    $result = $row['result'] ?? 'ERROR';

    header($result === 'ADDED'
        ? "Location: ../../admin/rooms.php?msg=added"
        : "Location: ../../admin/rooms.php?error=insert_failed");
    exit;
}

// --- ACTION: DELETE ROOM ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // sp_manage_room checks for active bookings before deleting
    $stmt = $conn->prepare("CALL sp_manage_room('delete', ?, '', '', 0, @p_result)");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    $row    = $conn->query("SELECT @p_result AS result")->fetch_assoc();
    $result = $row['result'] ?? 'ERROR';

    switch ($result) {
        case 'DELETED':
            header("Location: ../../admin/rooms.php?msg=deleted");
            break;
        case 'DELETE_BLOCKED':
            // Room has active reservations — warn the admin instead of silently failing
            header("Location: ../../admin/rooms.php?error=has_active_bookings");
            break;
        default:
            header("Location: ../../admin/rooms.php?error=delete_failed");
            break;
    }
    exit;
}
?>

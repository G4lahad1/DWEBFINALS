<?php
session_start();
require '../includes/db_connection.php';

// --- SECURITY: Admin-only ---
// Bug fix: was checking $_SESSION['loggedin'] (student key), admins use 'admin_logged_in'
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../../admin/profile.php");
    exit;
}


if (isset($_POST['action']) && $_POST['action'] === 'update_photo') {

    if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== 0) {
        header("Location: ../../admin/profile.php?msg=err_file");
        exit;
    }

    $tmp  = $_FILES['profile_image']['tmp_name'];
    $orig = $_FILES['profile_image']['name'];

    // Validate it's actually an image
    $check = getimagesize($tmp);
    if ($check === false) {
        header("Location: ../../admin/profile.php?msg=err_file");
        exit;
    }

    // Allowed MIME types
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($check['mime'], $allowed_types)) {
        header("Location: ../../admin/profile.php?msg=err_file");
        exit;
    }

    // Build a unique filename so uploads never collide or overwrite
    $ext      = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
    $filename = 'admin_' . $user_id . '_' . time() . '.' . $ext;

    // Path is relative to THIS file (assets/actions/) → go up two levels
    $upload_dir = __DIR__ . '/../../assets/uploads/profiles/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $dest = $upload_dir . $filename;

    if (!move_uploaded_file($tmp, $dest)) {
        header("Location: ../../admin/profile.php?msg=err_db");
        exit;
    }

    // Delete the old profile picture file to save disk space
    $old_stmt = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
    $old_stmt->bind_param("i", $user_id);
    $old_stmt->execute();
    $old_row = $old_stmt->get_result()->fetch_assoc();
    $old_stmt->close();

    if (!empty($old_row['profile_image'])) {
        $old_file = $upload_dir . $old_row['profile_image'];
        if (file_exists($old_file)) {
            unlink($old_file);
        }
    }

    // Store only the filename in the DB (not the full path, not the blob)
    $sql  = "UPDATE users SET profile_image = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $filename, $user_id);

    if ($stmt->execute()) {
        // Update session so the topbar reflects the new picture immediately
        $_SESSION['profile_image'] = $filename;
        header("Location: ../../admin/profile.php?msg=uploaded");
    } else {
        header("Location: ../../admin/profile.php?msg=err_db");
    }
    $stmt->close();
    exit;
}

// ======================================================================
// CASE 2: CHANGE PASSWORD
// Bug fix: was redirecting to ../../profile.php (student page).
// Now redirects to ../../admin/profile.php.
// ======================================================================
if (isset($_POST['action']) && $_POST['action'] === 'change_password') {

    $current_pass = $_POST['current_password'] ?? '';
    $new_pass     = $_POST['new_password']     ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    // Check new passwords match
    if ($new_pass !== $confirm_pass) {
        header("Location: ../../admin/profile.php?msg=err_mismatch");
        exit;
    }

    // Fetch the current hashed password from DB
    $sql  = "SELECT password FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        header("Location: ../../admin/profile.php?msg=err_pass");
        exit;
    }

    // Verify the entered current password against the stored hash
    if (!password_verify($current_pass, $row['password'])) {
        header("Location: ../../admin/profile.php?msg=err_pass");
        exit;
    }

    // Hash the new password and update
    $new_hash    = password_hash($new_pass, PASSWORD_DEFAULT);
    $update_sql  = "UPDATE users SET password = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("si", $new_hash, $user_id);

    if ($update_stmt->execute()) {
        // Bug fix: was redirecting to ../../profile.php (student page)
        header("Location: ../../admin/profile.php?msg=updated");
    } else {
        header("Location: ../../admin/profile.php?msg=error");
    }
    $update_stmt->close();
    exit;
}

// Fallback if no matching action
header("Location: ../../admin/profile.php");
exit;
?>

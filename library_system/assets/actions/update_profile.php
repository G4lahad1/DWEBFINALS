<?php
session_start();
require '../includes/db_connection.php';

// 1. Check Login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: Location: ../../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- CASE 1: UPDATE PHOTO ---
    if (isset($_POST['action']) && $_POST['action'] == 'update_photo') {
        
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            
            $fileContent = file_get_contents($_FILES['profile_image']['tmp_name']);
            
            // Validate it's an image
            $check = getimagesize($_FILES['profile_image']['tmp_name']);
            if($check !== false) {
                // Update Database
                $sql = "UPDATE users SET profile_image = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $fileContent, $user_id); // 's' because blob is treated as packet/string in some configs, or 'b'
                $stmt->send_long_data(0, $fileContent); // Ensures large blobs are sent correctly
                
                if($stmt->execute()) {
                    // Update Session Immediately
                    $_SESSION['profile_image'] = $fileContent;
                    header("Location: ../../profile.php?msg=uploaded");
                } else {
                    header("Location: ../../profile.php?msg=err_db");
                }
            } else {
                header("Location: ../../profile.php?msg=err_file");
            }
        }
    }

    // --- CASE 2: CHANGE PASSWORD ---

    elseif (isset($_POST['action']) && $_POST['action'] == 'change_password') {
        
        $current_pass = $_POST['current_password'];
        $new_pass = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];

        if ($new_pass !== $confirm_pass) {
            header("Location: ../../profile.php?msg=err_mismatch");
            exit;
        }


        $sql = "SELECT password FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();


        if (password_verify($current_pass, $row['password'])) {
            

            $new_pass_hash = password_hash($new_pass, PASSWORD_DEFAULT);


            $update_sql = "UPDATE users SET password = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            

            $update_stmt->bind_param("si", $new_pass_hash, $user_id); 
            
            if ($update_stmt->execute()) {
                header("Location: ../../profile.php?msg=updated");
            } else {
                header("Location: ../../profile.php?msg=error");
            }
        } else {

            header("Location: ../../profile.php?msg=err_pass");
        }
    }
}
?>

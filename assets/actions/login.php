<?php
session_start();

include '../includes/db_connection.php'; 
include '../includes/logger.php'; // Include the logger function

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $user = $_POST['username'];
    $pass = $_POST['password'];

    $sql = "SELECT id, username, password, full_name, profile_image, role FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $user); 
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        
        if (password_verify($pass, $row['password'])) {
            
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['name'] = $row['full_name'];
            $_SESSION['profile_image'] = $row['profile_image'];
            $_SESSION['role'] = $row['role']; 

            if ($row['role'] == 'admin') {
                $_SESSION['admin_logged_in'] = true;
                
                // --- LOG ACTION: Admin Success ---
                logAction($conn, $row['username'], 'ADMIN_LOGIN', 'Admin successfully logged in.');
                
                header("Location: ../../admin/dashboard.php");
                exit;

            } else {
                $_SESSION['loggedin'] = true;
                
                // --- LOG ACTION: Student Success ---
                logAction($conn, $row['username'], 'STUDENT_LOGIN', 'Student successfully logged in.');
                
                header("Location: ../../dashboard.php");
                exit;
            }

        } else {
            // --- LOG ACTION: Wrong Password ---
            logAction($conn, $user, 'FAILED_LOGIN', 'Invalid password attempt.');
            
            header("Location: ../../index.php?error=invalid_credentials");
            exit;
        }
    } else {
        // --- LOG ACTION: User Not Found ---
        logAction($conn, $user, 'FAILED_LOGIN', 'Login attempt with non-existent username.');
        
        header("Location: ../../index.php?error=invalid_credentials");
        exit;
    }

    $stmt->close();
    $conn->close();
}
?>
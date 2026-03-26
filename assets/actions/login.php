<?php
session_start();

include '../includes/db_connection.php'; 
include '../includes/logger.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $user = $_POST['username'];
    $pass = $_POST['password'];

    // 1. Prepare the statement using $pdo (not $conn)
    $sql = "SELECT id, username, password, full_name, profile_image, role FROM users WHERE username = ?";
    $stmt = $pdo->prepare($sql);
    
    // 2. Execute by passing the variables directly in an array
    $stmt->execute([$user]);

    // 3. Fetch the result directly (PDO handles this cleaner than MySQLi)
    $row = $stmt->fetch();

    // 4. Check if a row was actually returned
    if ($row) {
        
        if (password_verify($pass, $row['password'])) {
            
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['name'] = $row['full_name'];
            $_SESSION['profile_image'] = $row['profile_image'];
            $_SESSION['role'] = $row['role']; 

            if ($row['role'] == 'admin') {
                $_SESSION['admin_logged_in'] = true;
                
                // Passed $pdo instead of $conn
                logAction($pdo, $row['username'], 'ADMIN_LOGIN', 'Admin successfully logged in.');
                
                header("Location: ../../admin/dashboard.php");
                exit;

            } else {
                $_SESSION['loggedin'] = true;
                
                // Passed $pdo instead of $conn
                logAction($pdo, $row['username'], 'STUDENT_LOGIN', 'Student successfully logged in.');
                
                header("Location: ../../dashboard.php");
                exit;
            }

        } else {
            // Passed $pdo instead of $conn
            logAction($pdo, $user, 'FAILED_LOGIN', 'Invalid password attempt.');
            
            header("Location: ../../index.php?error=invalid_credentials");
            exit;
        }
    } else {
        // Passed $pdo instead of $conn
        logAction($pdo, $user, 'FAILED_LOGIN', 'Login attempt with non-existent username.');
        
        header("Location: ../../index.php?error=invalid_credentials");
        exit;
    }
}
?>

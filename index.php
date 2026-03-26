<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HAU Login Portal</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <img src="assets/css/photos/HAU Logo.png" alt="Holy Angel University Logo" class="logo">
            <h2>LIBSpaceHAU</h2>
            <h3>User Login</h3>
                <form action="assets/actions/login.php" method="POST">
    
    <?php
    if (isset($_GET['error'])) {
        $error_msg = "";
        
        if ($_GET['error'] == "invalid_credentials") {
            $error_msg = "Incorrect username/password. Please try again.";
        }
        
        // Only display the div if there is a message
        if ($error_msg) {
            echo '<div class="error-banner">' . $error_msg . '</div>';
        }
    }
    ?>
    <div class="input-group">
        <input type="text" name="username" placeholder="Username/ID Number" required>
    </div>
                
                <div class="input-group">
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit" class="login-btn">Login</button>
                <a href="#" class="forgot-link">Forgot Password?</a>
            </form>
        </div>
    </div>
</body>
</html>

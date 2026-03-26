<?php
// Fetch the environment variables provided by Railway
$host = getenv('MYSQLHOST') ?: '127.0.0.1'; 
$port = getenv('MYSQLPORT') ?: '3306';
$db   = getenv('MYSQLDATABASE') ?: 'my_local_db';
$user = getenv('MYSQLUSER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: '';

// Tell mysqli to throw strict PHP exceptions so we can catch errors easily
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Connect using mysqli instead of PDO
    $conn = new mysqli($host, $user, $pass, $db, $port);
    $conn->set_charset("utf8mb4");
} catch (\mysqli_sql_exception $e) {
    // If it fails, print the exact error for debugging
    die("Real PHP Error: " . $e->getMessage());
}

// Your library reservation auto-update logic goes here
$auto_update_sql = "
    UPDATE reservations 
    SET status = 'completed' 
    WHERE status = 'approved' 
    AND TIMESTAMP(reservation_date, end_time) < NOW()
";

// Using $conn just like the rest of your app!
$conn->query($auto_update_sql);
?>

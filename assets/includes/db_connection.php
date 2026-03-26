<?php

// Fetch the environment variables provided by Railway
$host = getenv('MYSQLHOST') ?: '127.0.0.1'; // Fallback to localhost for local dev
$port = getenv('MYSQLPORT') ?: '3306';
$db   = getenv('MYSQLDATABASE') ?: 'my_local_db';
$user = getenv('MYSQLUSER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: '';

// Set up the DSN (Data Source Name)
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Better security
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    // echo "Connected successfully!"; // Uncomment for testing
} catch (\PDOException $e) {
    // Log the error, don't show the raw message to users in production!
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}

$auto_update_sql = "
    UPDATE reservations 
    SET status = 'completed' 
    WHERE status = 'approved' 
    AND TIMESTAMP(reservation_date, end_time) < NOW()
";

// Execute the silent update
$conn->query($auto_update_sql);

?>

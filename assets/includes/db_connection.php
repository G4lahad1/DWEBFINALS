<?php

// Fetch the environment variables provided by Railway
$host = getenv('MYSQLHOST') ?: '127.0.0.1'; 
$port = getenv('MYSQLPORT') ?: '3306';
$db   = getenv('MYSQLDATABASE') ?: 'my_local_db';
$user = getenv('MYSQLUSER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: '';

// Set up the DSN (Data Source Name)
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, 
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       
    PDO::ATTR_EMULATE_PREPARES   => false,                  
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // TEMPORARY DEBUGGING: This will print the exact reason it's failing
    die("Real PHP Error: " . $e->getMessage() . " | Host tried: " . $host);
}

$auto_update_sql = "
    UPDATE reservations 
    SET status = 'completed' 
    WHERE status = 'approved' 
    AND TIMESTAMP(reservation_date, end_time) < NOW()
";

// FIXED: Using $pdo instead of $conn
$pdo->query($auto_update_sql);

?>

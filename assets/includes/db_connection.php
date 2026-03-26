<?php
$host = getenv('DB_HOST'); 
$user = getenv('DB_USER');
$pass = getenv('DB_PASSWORD');
$dbname = getenv('DB_NAME');
$port = getenv('DB_PORT'); // Crucial: Railway does not use the default 3306

// Using PDO
try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Connection successful! Ready to query your Rooms, Reservations, Users, and Audit Logs.
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
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

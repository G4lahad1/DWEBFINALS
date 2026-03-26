<?php
$host = 'db'; 
$user = 'root';
$pass = '';
$dbname = 'library_db';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }


// Auto-update logic
$auto_update_sql = "
    UPDATE reservations 
    SET status = 'completed' 
    WHERE status = 'approved' 
    AND TIMESTAMP(reservation_date, end_time) < NOW()
";

$conn->query($auto_update_sql);
?>
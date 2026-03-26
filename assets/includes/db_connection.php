<?php
$servername = "localhost";
$username = "root";       
$password = "";        
$dbname = "library_system"; 

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
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

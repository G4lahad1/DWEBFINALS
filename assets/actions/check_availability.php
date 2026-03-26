<?php
// assets/actions/check_availability.php
require '../includes/db_connection.php';

if (isset($_POST['date']) && isset($_POST['start']) && isset($_POST['end'])) {
    
    $date = $_POST['date'];
    $start = $_POST['start'];
    $end = $_POST['end'];

  
    
    $sql_busy = "SELECT room_id FROM reservations 
                 WHERE reservation_date = ? 
                 AND status IN ('approved', 'pending')
                 AND (start_time < ? AND end_time > ?)";
                 
    $stmt = $conn->prepare($sql_busy);
    $stmt->bind_param("sss", $date, $end, $start);
    $stmt->execute();
    $result_busy = $stmt->get_result();
    
    $busy_rooms = [];
    while($row = $result_busy->fetch_assoc()) {
        $busy_rooms[] = $row['room_id'];
    }

    // 2. Get ALL rooms, excluding the busy ones
    $sql_rooms = "SELECT * FROM rooms";
    $result_rooms = $conn->query($sql_rooms);
    
    $options = '<option value="">-- Select a Room --</option>';
    
    if ($result_rooms->num_rows > 0) {
        while($row = $result_rooms->fetch_assoc()) {
            // If this room ID is in our busy list, skip it!
            if (in_array($row['id'], $busy_rooms)) {
                continue; 
            }
            
            // Otherwise, add it to the dropdown options
            $options .= '<option value="'.$row['id'].'">'.$row['room_name'].' (Capacity: '.$row['capacity'].')</option>';
        }
    }
    
    // If no rooms are left after filtering
    if ($options == '<option value="">-- Select a Room --</option>') {
        echo '<option value="">No rooms available at this time</option>';
    } else {
        echo $options;
    }

}
?>

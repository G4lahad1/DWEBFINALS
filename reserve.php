<?php
session_start();
require 'assets/includes/db_connection.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit;
}
$current_page = 'reserve'; 
$page_title = 'Room Reservation';

// --- CONFIGURATION: SET LIMIT HERE ---
$DAILY_LIMIT = 1; 

// --- CHECK USER'S BOOKINGS FOR TODAY ---
$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// We count requests that are NOT cancelled or rejected
$sql_check = "SELECT COUNT(*) as count FROM reservations 
              WHERE user_id = ? 
              AND reservation_date = ? 
              AND status IN ('pending', 'approved')";

$stmt = $conn->prepare($sql_check);
$stmt->bind_param("is", $user_id, $today);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$current_bookings = $row['count'];
$limit_reached = ($current_bookings >= $DAILY_LIMIT);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HAU Library | Reserve Room</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <style>
        .limit-alert {
            background-color: #fdedec;
            color: #e74c3c;
            border: 1px solid #e74c3c;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin-top: 20px;
        }
        .limit-alert i {
            font-size: 40px;
            margin-bottom: 10px;
        }

        /* === TABLET === */
        @media (min-width: 769px) and (max-width: 1024px) {
            .time-inputs-row { flex-direction: row !important; gap: 16px !important; }
            .time-inputs-row > div { flex: 1; }
        }
        /* === MOBILE: stack time inputs === */
        @media (max-width: 768px) {
            .time-inputs-row { flex-direction: column !important; gap: 10px !important; }
        }
    </style>
</head>
<body>

   <?php include 'assets/includes/sidebar.php'; ?>
     <?php include 'assets/includes/topbar.php'; ?>

        <div class="home-content">
            <div class="sales-boxes">
                
                <div class="recent-sales box" style="width: 100%;">
                    <div class="title">New Reservation Details</div>

                    <?php if ($limit_reached): ?>
                        
                        <div class="limit-alert">
                            <i class='bx bx-block'></i>
                            <h3>Daily Limit Reached</h3>
                            <p>You have already made <strong><?php echo $current_bookings; ?></strong> reservation(s) for today.</p>
                            <p>To give everyone a fair chance, students are limited to <strong><?php echo $DAILY_LIMIT; ?></strong> booking per day.</p>
                            <br>
                            <a href="history.php" style="color: #c0392b; font-weight: bold; text-decoration: underline;">View My Bookings</a>
                        </div>

                    <?php else: ?>
                    
                        <div style="margin-top: 15px; margin-bottom: 15px;">
                            <?php
                            if (isset($_GET['msg'])) {
                                if ($_GET['msg'] == 'success') echo "<p style='color: green;'>Reservation submitted successfully! Status: Pending.</p>";
                                if ($_GET['msg'] == 'collision') echo "<p style='color: red;'>Error: This room is already booked for that time slot.</p>";
                                if ($_GET['msg'] == 'invalid_time') echo "<p style='color: red;'>Error: Invalid time range.</p>";
                                if ($_GET['msg'] == 'violation_block') echo "<p style='color: red;'>Error: You have an active penalty/suspension. Reservations are blocked until it is resolved.</p>";
                                if ($_GET['msg'] == 'error') echo "<p style='color: red;'>Database error. Please try again.</p>";
                            }
                            ?>
                            <p id="time_error" style="color: red; font-weight: 500; display: none;"></p>
                        </div>

                        <form action="assets/actions/process_reservation.php" method="POST" id="reserveForm" style="margin-top: 10px;">

                            <div style="margin-bottom: 15px;">
                                <label style="font-weight: 500; display: block; margin-bottom: 5px;">Date:</label>
                                <input type="date" name="date" id="date_input" required min="<?php echo date('Y-m-d'); ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                            </div>

                            <div class="time-inputs-row" style="margin-bottom: 15px; display: flex; gap: 20px;">
                                <div style="flex: 1;">
                                    <label style="font-weight: 500; display: block; margin-bottom: 5px;">Start Time (8am - 5pm):</label>
                                    <input type="time" name="start_time" id="start_input" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                                </div>
                                <div style="flex: 1;">
                                    <label style="font-weight: 500; display: block; margin-bottom: 5px;">End Time (Max 3 hrs):</label>
                                    <input type="time" name="end_time" id="end_input" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                                </div>
                            </div>

                            <div style="margin-bottom: 15px;">
                                <label style="font-weight: 500; display: block; margin-bottom: 5px;">Select Room:</label>
                                <select name="room_id" id="room_select" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                                    <option value="" disabled selected>-- Select Date & Time First --</option>
                                </select>
                            </div>

                            <div class="button" style="text-align: left;">
                                <button type="submit" id="submit_btn" disabled style="background: #ccc; color: #fff; padding: 10px 25px; border: none; border-radius: 4px; font-size: 15px; cursor: pointer;">
                                    Confirm Reservation
                                </button>
                            </div>

                        </form>

                    <?php endif; ?> </div>
            </div>
        </div>
    </section>

    <script src="assets/js/main.js"></script>
    <script>
    <?php if (!$limit_reached): ?> // Only run JS if form is visible
        const dateInput = document.getElementById('date_input');
        const startInput = document.getElementById('start_input');
        const endInput = document.getElementById('end_input');
        const roomSelect = document.getElementById('room_select');
        const errorMsg = document.getElementById('time_error');
        const submitBtn = document.getElementById('submit_btn');

        const LIBRARY_OPEN = "08:00";
        const LIBRARY_CLOSE = "17:00"; 
        const MAX_DURATION_HOURS = 3; 

        function validateAndTimeCheck() {
            const date = dateInput.value;
            const start = startInput.value;
            const end = endInput.value;
            
            errorMsg.style.display = 'none';
            errorMsg.innerText = "";
            submitBtn.disabled = true;
            submitBtn.style.background = "#ccc";
            roomSelect.innerHTML = '<option value="" disabled selected>-- Select Date & Time First --</option>';

            if(!date || !start || !end) return;

            if (start < LIBRARY_OPEN || end > LIBRARY_CLOSE) {
                showError("Library hours are only from 8:00 AM to 5:00 PM.");
                return;
            }

            if (start >= end) {
                showError("End time must be after start time.");
                return;
            }

            const startTimeDate = new Date(`1970-01-01T${start}:00`);
            const endTimeDate = new Date(`1970-01-01T${end}:00`);
            const diffMinutes = (endTimeDate - startTimeDate) / 1000 / 60; 

            if (diffMinutes > (MAX_DURATION_HOURS * 60)) {
                showError(`Maximum reservation time is ${MAX_DURATION_HOURS} hours.`);
                return;
            }

            checkAvailability(date, start, end);
        }

        function checkAvailability(date, start, end) {
            roomSelect.innerHTML = '<option>Checking availability...</option>';

            const formData = new FormData();
            formData.append('date', date);
            formData.append('start', start);
            formData.append('end', end);

            fetch('assets/actions/check_availability.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                roomSelect.innerHTML = data;
                submitBtn.disabled = false;
                submitBtn.style.background = "#119939";
            })
            .catch(error => console.error('Error:', error));
        }

        function showError(message) {
            errorMsg.innerText = message;
            errorMsg.style.display = 'block';
        }

        dateInput.addEventListener('change', validateAndTimeCheck);
        startInput.addEventListener('change', validateAndTimeCheck);
        endInput.addEventListener('change', validateAndTimeCheck);
    <?php endif; ?>
    </script>
</body>
</html>
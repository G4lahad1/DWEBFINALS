<?php
session_start();
require 'assets/includes/db_connection.php';

// 1. Check Login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit;
}

// --- CONFIG FOR SIDEBAR OUTPUT AND ACTIVE BAR---
$current_page = 'history'; 
$page_title = 'Reservation History';
// --------------------------

// 2. Fetch User's History
$user_id = $_SESSION['user_id'];

// We want ALL reservations, ordered by date (newest first)
$sql = "SELECT r.*, rm.room_name 
        FROM reservations r 
        JOIN rooms rm ON r.room_id = rm.id 
        WHERE r.user_id = ? 
        ORDER BY r.reservation_date DESC, r.start_time DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HAU Library | My History</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    
    <style>
                /* Modal Overlay (Background) */
        .modal-overlay {
            display: none; /* Hidden by default */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5); /* Semi-transparent black */
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        /* Modal Box (The actual window) */
        .modal-box {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            width: 400px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            animation: fadeIn 0.3s;
        }

        .modal-box h3 {
            margin-top: 0;
            color: #333;
            font-size: 20px;
        }

        .modal-box p {
            color: #666;
            margin: 15px 0 25px;
        }

        /* Modal Buttons */
        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .btn-confirm {
            background: #e74c3c;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-close {
            background: #ccc;
            color: #333;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .btn-cancel {
            background-color: #e74c3c; /* Nice Red */
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-family: 'Poppins', sans-serif;
            transition: background 0.3s ease;
        }

        .btn-cancel:hover {
            background-color: #c0392b; /* Darker Red on Hover */
        }

        /* Ensure the form doesn't mess up table alignment */
        .action-form {
            margin: 0;
            display: inline-block;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        th {
            font-weight: 500;
            color: #0A2558;
        }
        /* Status Badges */
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge.pending { background: #fff3cd; color: #856404; }
        .badge.approved { background: #d4edda; color: #155724; }
        .badge.completed { background: #cce5ff; color: #004085; }
        .badge.cancelled { background: #f8d7da; color: #721c24; }

        /* =============================================
           CARD LAYOUT — Reservation History on Mobile
           ============================================= */
        @media (min-width: 769px) and (max-width: 1024px) {
            table { min-width: 600px; }
            th, td { padding: 10px 12px; font-size: 13px; white-space: nowrap; }
            .table-wrapper { overflow-x: auto; }
        }

        @media (max-width: 768px) {

            /* Force ALL parent containers to stay within the viewport */
            .home-content,
            .sales-boxes,
            .recent-sales.box,
            .table-wrapper {
                width: 100% !important;
                max-width: 100% !important;
                overflow: hidden !important;
                box-sizing: border-box !important;
                min-width: 0 !important;
                padding-left: 14px !important;
                padding-right: 14px !important;
            }

            /* Break the table grid — everything becomes block */
            table {
                display: block;
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
            }

            thead, tbody, th, td, tr {
                display: block;
                width: 100%;
                max-width: 100%;
                box-sizing: border-box;
                min-width: 0;
            }

            /* Value text always wraps — no cutoff */
            tbody td > span,
            tbody td > a,
            tbody td > strong,
            tbody td > small,
            tbody td > button {
                max-width: 100%;
                word-break: break-word;
                overflow-wrap: break-word;
                white-space: normal;
                min-width: 0;
            }

            /* Hide original header row */
            thead tr {
                display: none;
            }

            /* Each <tr> = one card */
            tbody tr {
                width: 100%;
                background: #fff;
                border: 1px solid #e8e8e8;
                border-radius: 10px;
                margin-bottom: 12px;
                padding: 0;
                box-shadow: 0 2px 8px rgba(0,0,0,0.06);
                overflow: hidden;
            }

            tbody tr:last-child {
                margin-bottom: 0;
            }

            /* Date row — header strip at top of card */
            tbody td[data-label="Date"] {
                width: 100%;
                background: #781016;
                color: #fff;
                font-weight: 600;
                font-size: 14px;
                padding: 10px 14px;
                border: none;
                display: block;
                text-align: left;
            }

            tbody td[data-label="Date"]::before {
                display: none; /* date speaks for itself */
            }

            /* All other cells — label on left, value on right */
            tbody td {
                width: 100%;
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 9px 14px;
                border: none;
                border-top: 1px solid #f3f3f3;
                font-size: 13px;
                color: #333;
            }

            /* Label from data-label */
            tbody td::before {
                content: attr(data-label);
                font-weight: 600;
                font-size: 11px;
                color: #999;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                flex-shrink: 0;
                margin-right: 10px;
                min-width: 56px;
            }

            /* Action row — hide label, align button right */
            tbody td[data-label="Action"] {
                justify-content: flex-end;
                padding: 10px 14px;
                background: #fafafa;
            }

            tbody td[data-label="Action"]::before {
                display: none;
            }

            .btn-cancel {
                padding: 7px 16px;
                font-size: 13px;
                border-radius: 6px;
            }
        }


        @media (max-width: 480px) {
            tbody td { font-size: 13px; padding: 8px 12px; }
            tbody td[data-label="Date"] { font-size: 13px; padding: 9px 12px; }
        }

        /* === TABLET: keep normal table layout === */
    </style>
</head>
<body>
    <?php include 'assets/includes/sidebar.php'; ?>
    <?php include 'assets/includes/topbar.php'; ?>
        <div class="home-content">

            <?php if (isset($_GET['msg'])): ?>
                <?php if ($_GET['msg'] === 'cancelled'): ?>
                <div style="background:#d4edda;border-left:4px solid #28a745;padding:12px 18px;margin-bottom:16px;border-radius:4px;color:#155724;font-weight:500;">
                    <i class='bx bx-check-circle'></i> Reservation successfully cancelled.
                </div>
                <?php elseif ($_GET['msg'] === 'not_cancellable'): ?>
                <div style="background:#fdecea;border-left:4px solid #e74c3c;padding:12px 18px;margin-bottom:16px;border-radius:4px;color:#c0392b;font-weight:500;">
                    <i class='bx bx-error-circle'></i> This reservation can no longer be cancelled (it may already be approved or completed).
                </div>
                <?php elseif ($_GET['msg'] === 'error'): ?>
                <div style="background:#fdecea;border-left:4px solid #e74c3c;padding:12px 18px;margin-bottom:16px;border-radius:4px;color:#c0392b;font-weight:500;">
                    <i class='bx bx-error-circle'></i> An error occurred. Please try again.
                </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="sales-boxes">
                <div class="recent-sales box" style="width: 100%;">
                    <div class="title">All Reservations</div>

                    <?php if ($result->num_rows > 0): ?>
                    <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Room</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Action</th> </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td data-label="Date"><?php echo date("M d, Y", strtotime($row['reservation_date'])); ?></td>
                                    <td data-label="Room"><?php echo htmlspecialchars($row['room_name']); ?></td>
                                    <td data-label="Time">
                                        <?php 
                                            echo date("g:i A", strtotime($row['start_time'])) . " - " . date("g:i A", strtotime($row['end_time'])); 
                                        ?>
                                    </td>
                                    <td data-label="Status">
                                        <span class="badge <?php echo strtolower($row['status']); ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td data-label="Action">
                                        <?php
                                        // Use fn_is_reservation_cancellable — verifies ownership + pending status
                                        $can_stmt = $conn->prepare(
                                            "SELECT fn_is_reservation_cancellable(?, ?) AS can_cancel"
                                        );
                                        $can_stmt->bind_param("ii", $row['id'], $user_id);
                                        $can_stmt->execute();
                                        $can_cancel = (bool)$can_stmt->get_result()->fetch_assoc()['can_cancel'];
                                        $can_stmt->close();
                                        ?>
                                        <?php if($can_cancel): ?>
                                            <button type="button" class="btn-cancel" onclick="openModal(<?php echo $row['id']; ?>)">
                                                Cancel
                                            </button>
                                        <?php else: ?>
                                            <span style="color: #ccc; font-size: 13px;">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    </div><!-- /.table-wrapper -->

                    <?php else: ?>
                        <p style="padding: 20px; color: #666; text-align: center;">You haven't made any reservations yet.</p>
                        <div class="button" style="text-align: center;">
                            <a href="reserve.php">Make a Reservation</a>
                        </div>

                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <div id="cancelModal" class="modal-overlay">
        <div class="modal-box">
            <i class='bx bx-error-circle' style="font-size: 50px; color: #e74c3c;"></i>
                <h3>Cancel Reservation?</h3>
                <p>Are you sure you want to cancel this booking? This action cannot be undone.</p>
                    
                <form action="assets/actions/cancel_reservation.php" method="POST">
                    <input type="hidden" name="reservation_id" id="modal_reservation_id" value="">    
                    <div class="modal-buttons">
                        <button type="button" class="btn-close" onclick="closeModal()">No, Keep it</button>
                        <button type="submit" class="btn-confirm">Yes, Cancel it</button>
                    </div>
                </form>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    
    <script>
    // Open the modal and set the ID
    function openModal(id) {
        // Find the hidden input inside the modal and set its value to the reservation ID
        document.getElementById('modal_reservation_id').value = id;
        
        // Show the modal
        document.getElementById('cancelModal').style.display = 'flex';
    }

    // Close the modal
    function closeModal() {
        document.getElementById('cancelModal').style.display = 'none';
    }

    // Close modal if user clicks outside the box
    window.onclick = function(event) {
        var modal = document.getElementById('cancelModal');
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    </script>
</body>
</html>

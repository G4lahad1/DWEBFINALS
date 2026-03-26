<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../index.php");
    exit;
}

require '../assets/includes/db_connection.php';
$current_page = 'reservations';

// 1. FETCH PENDING REQUESTS
$sql_pending = "SELECT r.*, u.username, u.full_name, rm.room_name 
                FROM reservations r 
                JOIN users u ON r.user_id = u.id 
                JOIN rooms rm ON r.room_id = rm.id
                WHERE r.status = 'pending' 
                ORDER BY r.reservation_date ASC";
$res_pending = $conn->query($sql_pending);

// 2. FETCH HISTORY
$sql_all = "SELECT r.*, u.username, rm.room_name 
            FROM reservations r 
            JOIN users u ON r.user_id = u.id 
            JOIN rooms rm ON r.room_id = rm.id
            WHERE r.status != 'pending' 
            ORDER BY r.reservation_date DESC LIMIT 50";
$res_all = $conn->query($sql_all);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reservations | LibSpace Admin</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'> 
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .btn { padding: 5px 10px; border-radius: 4px; text-decoration: none; color: white; font-size: 12px; margin-right: 5px; cursor: pointer; border: none; }
        .btn-approve { background: #2ecc71; }
        .btn-reject { background: #e74c3c; }
        .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; }
        
        /* THE FIX: Added all missing status colors here */
        .status-approved { background: #d5f5e3; color: #2ecc71; }
        .status-rejected { background: #fadbd8; color: #e74c3c; }
        .status-cancelled { background: #f2f4f4; color: #7f8c8d; } /* Gray for cancelled */
        .status-completed { background: #ebf5fb; color: #2980b9; } /* Blue for completed */
        
        /* Modal Styles */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .modal-box { background: #fff; padding: 30px; border-radius: 12px; width: 400px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.3); animation: fadeIn 0.3s; }
        .modal-buttons { display: flex; gap: 10px; justify-content: center; margin-top: 20px; }
        .btn-close { background: #ccc; color: #333; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .btn-confirm { background: #e74c3c; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        @keyframes fadeIn { from {opacity: 0; transform: translateY(-20px);} to {opacity: 1; transform: translateY(0);} }


        /* === TABLET === */
        @media (min-width: 769px) and (max-width: 1024px) {
            .sales-boxes {
                flex-direction: row;
                flex-wrap: wrap;
                gap: 14px;
            }
            .sales-boxes .recent-sales.box,
            .sales-boxes .box[style*="width:100%"],
            .sales-boxes .box[style*="width: 100%"] {
                flex: 1 1 100%;
            }
            .overview-boxes .box {
                flex: 1 1 calc(33.333% - 10px);
                min-width: 0;
            }

            .table-wrapper { overflow-x: auto; -webkit-overflow-scrolling: touch; }
            table { min-width: 600px; }
            th, td { padding: 10px 12px; font-size: 13px; white-space: nowrap; }
            td .btn, td a.btn-approve, td .btn-approve, td .btn-reject,
            td .btn-cancel, td .btn-delete, td .btn-resolve, td .btn-sanction,
            td .badge, td .status-badge {
                white-space: nowrap !important;
                display: inline-flex;
                align-items: center;
            }
            .home-content { padding: 18px; }
        }
        /* === MOBILE === */
        

        /* Desktop: ensure tables always fill full width */
        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }
        .table-wrapper table {
            width: 100% !important;
            min-width: 0 !important;
        }
    
        @media (max-width: 768px) {

            /* Constrain all containers */
            .home-content,
            .sales-boxes,
            .recent-sales.box,
            .table-wrapper {
                width: 100% !important;
                max-width: 100% !important;
                overflow: hidden !important;
                box-sizing: border-box !important;
                min-width: 0 !important;
            }

            /* Break table into blocks */
            table { display: block; width: 100% !important; min-width: 0 !important; }
            thead, tbody, th, td, tr {
                display: block; width: 100%; max-width: 100%;
                box-sizing: border-box; min-width: 0;
            }
            thead tr { display: none; }

            /* Each row = card */
            tbody tr {
                background: #fff;
                border: 1px solid #e8e8e8;
                border-radius: 10px;
                margin-bottom: 12px;
                padding: 0;
                box-shadow: 0 2px 8px rgba(0,0,0,0.06);
                overflow: hidden;
            }
            tbody tr:last-child { margin-bottom: 0; }

            /* Date = left-border header (matches other admin cards) */
            tbody td[data-label="Date"] {
                background: #f9f1f1;
                border-left: 4px solid #781016;
                color: #781016;
                font-weight: 700;
                font-size: 14px;
                padding: 12px 14px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                border-top: none;
            }
            tbody td[data-label="Date"]::before { display: none; }

            /* All other cells */
            tbody td {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
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
                min-width: 62px;
                margin-right: 10px;
            }

            /* Student cell — stack name + id vertically */
            tbody td[data-label="Student"] {
                flex-direction: column;
                gap: 2px;
            }
            tbody td[data-label="Student"]::before {
                margin-bottom: 2px;
            }

            /* Actions cell — Approve/Reject buttons side by side */
            tbody td[data-label="Actions"] {
                background: #fafafa;
                justify-content: flex-end;
                gap: 8px;
                flex-wrap: wrap;
                padding: 10px 14px;
            }
            tbody td[data-label="Actions"]::before { display: none; }

            /* History log — status row footer */
            tbody td[data-label="Status"] {
                background: #fafafa;
                justify-content: space-between;
            }

            /* Approve / Reject button sizing on mobile */
            .btn-approve, .btn-reject {
                padding: 7px 14px !important;
                font-size: 12px !important;
                border-radius: 5px !important;
            }
        }

        @media (max-width: 480px) {
            tbody td { font-size: 12px; padding: 8px 12px; }
            tbody td[data-label="Date"] { font-size: 13px; padding: 9px 12px; }
            tbody td::before { min-width: 54px; font-size: 11px; }
        }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <section class="home-section">
        <?php include 'topbar.php'; ?>

        <div class="home-content">
            
            <div class="sales-boxes">
                <div class="recent-sales box" style="width:100%;">
                    <div class="title" style="color: #781016;">Needs Action (Pending)</div>
                    
                    <?php if ($res_pending->num_rows > 0): ?>
                    <div class="table-wrapper">
                        <table style="width:100%; border-collapse: collapse; margin-top:15px;">
                            <thead>
                                <tr style="text-align:left; border-bottom: 2px solid #eee;">
                                    <th style="padding:10px;">Date</th>
                                    <th>Student</th>
                                    <th>Room</th>
                                    <th>Time</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $res_pending->fetch_assoc()): ?>
                                <tr>
                                    <td data-label="Date"><?php echo date("M d, Y", strtotime($row['reservation_date'])); ?></td>
                                    <td data-label="Student">
                                        <strong><?php echo $row['full_name']; ?></strong><br>
                                        <small style="color:#888;"><?php echo $row['username']; ?></small>
                                    </td>
                                    <td data-label="Room" style="color:#0A2558; font-weight:500;"><?php echo $row['room_name']; ?></td>
                                    <td data-label="Time"><?php echo date("g:i A", strtotime($row['start_time'])) . ' - ' . date("g:i A", strtotime($row['end_time'])); ?></td>
                                    <td data-label="Actions">
                                        <a href="../assets/actions/admin_update_res.php?action=approve&id=<?php echo $row['id']; ?>" class="btn btn-approve">Approve</a>
                                        <button class="btn btn-reject" onclick="openRejectModal(<?php echo $row['id']; ?>)">Reject</button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div><!-- /.table-wrapper -->
                    <?php else: ?>
                        <p style="padding:20px; color:#666; font-style:italic;">No pending requests.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="sales-boxes" style="margin-top: 20px;">
                <div class="recent-sales box" style="width:100%;">
                    <div class="title">History Log</div>
                    <div class="table-wrapper">
                        <table style="width:100%; border-collapse: collapse; margin-top:15px;">
                            <tbody>
                                <?php while($row = $res_all->fetch_assoc()): ?>
                                <tr>
                                    <td data-label="Date"><?php echo date("M d, Y", strtotime($row['reservation_date'])); ?></td>
                                    <td data-label="Student"><?php echo $row['username']; ?></td>
                                    <td data-label="Room"><?php echo $row['room_name']; ?></td>
                                    <td data-label="Status"><span class="status-badge status-<?php echo strtolower($row['status']); ?>"><?php echo ucfirst($row['status']); ?></span></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div><!-- /.table-wrapper -->
                </div>
            </div>

        </div>
    </section>

    <div id="rejectModal" class="modal-overlay">
        <div class="modal-box">
            <i class='bx bx-error-circle' style="font-size: 50px; color: #e74c3c;"></i>
            <h3>Reject Reservation?</h3>
            <p>Are you sure you want to reject this student's request? This cannot be undone.</p>
            
            <form action="../assets/actions/admin_update_res.php" method="GET">
                <input type="hidden" name="id" id="modal_reject_id" value="">
                <input type="hidden" name="action" value="reject"> 
                
                <div class="modal-buttons">
                    <button type="button" class="btn-close" onclick="closeRejectModal()">Cancel</button>
                    <button type="submit" class="btn-confirm">Yes, Reject</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        // Open Modal
        function openRejectModal(id) {
            document.getElementById('modal_reject_id').value = id;
            document.getElementById('rejectModal').style.display = 'flex';
        }

        // Close Modal
        function closeRejectModal() {
            document.getElementById('rejectModal').style.display = 'none';
        }

        // Close if clicked outside
        window.onclick = function(event) {
            var modal = document.getElementById('rejectModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>
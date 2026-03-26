<?php
session_start();

// 1. SECURITY CHECK: Kick out anyone who isn't an admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../index.php"); // Go back to login
    exit;
}

// 2. CONNECT TO DATABASE (Go up one level to find the file)
require '../assets/includes/db_connection.php';

// --- CONFIG FOR SIDEBAR ---
$current_page = 'dashboard';
$page_title = 'Admin Dashboard';

// 3. FETCH ADMIN STATS
// Count Pending Requests (Needs Approval)
$pending_sql = "SELECT COUNT(*) as total FROM reservations WHERE status = 'pending'";
$pending_res = $conn->query($pending_sql);
$pending_count = $pending_res->fetch_assoc()['total'];

// Count Today's Bookings (Active Now)
$today_sql = "SELECT COUNT(*) as total FROM reservations WHERE reservation_date = CURDATE() AND status = 'approved'";
$today_res = $conn->query($today_sql);
$today_count = $today_res->fetch_assoc()['total'];

// Count Active Violations
$violation_sql = "SELECT COUNT(*) as total FROM violations WHERE status = 'Active'";
$violation_res = $conn->query($violation_sql);
$violation_count = $violation_res->fetch_assoc()['total'];

// 4. FETCH RECENT PENDING REQUESTS (For the table)
$recent_sql = "SELECT r.id, r.reservation_date, r.start_time, r.end_time, u.username, rm.room_name 
               FROM reservations r
               JOIN users u ON r.user_id = u.id
               JOIN rooms rm ON r.room_id = rm.id
               WHERE r.status = 'pending'
               ORDER BY r.reservation_date ASC
               LIMIT 5";
$recent_res = $conn->query($recent_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | LibSpace HAU</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    
    <style>
        /* Admin-specific tweaks */
        .box .number { font-size: 30px; font-weight: 600; }
        .box .indicator { display: flex; align-items: center; margin-top: 10px; }
        .box .indicator i { height: 20px; width: 20px; background: #e0f7fa; line-height: 20px; text-align: center; border-radius: 50%; color: #00838f; font-size: 14px; margin-right: 5px; }
        .box .indicator .text { font-size: 14px; color: #333; }
        /* Green Approve Button */
        .btn-approve { padding: 4px 8px; background: #2ecc71; color: white; border-radius: 4px; text-decoration: none; font-size: 12px; }
        .btn-approve:hover { background: #27ae60; }

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

        /* Desktop: table fills full width */
        .table-wrapper { width: 100%; overflow-x: auto; }
        .table-wrapper table { width: 100% !important; min-width: 0 !important; }

        /* =============================================
           CARD LAYOUT — Admin Dashboard Table on Mobile
           ============================================= */
        @media (max-width: 768px) {

            .home-content, .sales-boxes, .recent-sales.box, .table-wrapper {
                width: 100% !important; max-width: 100% !important;
                overflow: hidden !important; box-sizing: border-box !important; min-width: 0 !important;
            }

            table { display: block; width: 100% !important; min-width: 0 !important; }
            thead, tbody, th, td, tr {
                display: block; width: 100%; max-width: 100%;
                box-sizing: border-box; min-width: 0;
            }
            thead tr { display: none; }

            tbody tr {
                background: #fff;
                border: 1px solid #e8e8e8;
                border-radius: 12px;
                margin-bottom: 14px;
                padding: 0;
                box-shadow: 0 2px 10px rgba(0,0,0,0.07);
                overflow: hidden;
            }
            tbody tr:last-child { margin-bottom: 0; }

            /* Date = pink left-border header */
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
                padding: 10px 16px;
                border: none;
                border-top: 1px solid #f3f3f3;
                font-size: 13px;
                color: #444;
                word-break: break-word;
                overflow-wrap: break-word;
                white-space: normal;
            }

            /* Label from data-label */
            tbody td::before {
                content: attr(data-label);
                font-weight: 600;
                font-size: 11px;
                color: #bbb;
                text-transform: uppercase;
                letter-spacing: 0.6px;
                flex-shrink: 0;
                min-width: 62px;
                margin-right: 10px;
            }

            /* Value side wraps cleanly */
            tbody td > *:last-child {
                min-width: 0;
                max-width: calc(100% - 74px);
                word-break: break-word;
                overflow-wrap: break-word;
                white-space: normal;
                text-align: right;
            }

            /* Action row */
            tbody td[data-label="Action"] {
                background: #fafafa;
                justify-content: flex-end;
                padding: 10px 14px;
                border-top: 1px solid #efefef;
            }
            tbody td[data-label="Action"]::before { display: none; }

            .btn-approve {
                padding: 7px 14px !important;
                font-size: 12px !important;
                border-radius: 6px !important;
            }
        }

        @media (max-width: 480px) {
            tbody td { font-size: 12px; padding: 8px 12px; }
            tbody td[data-label="Date"] { font-size: 13px; padding: 10px 12px; }
            tbody td::before { min-width: 54px; }
        }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <section class="home-section">
        <?php include 'topbar.php'; ?>

        <div class="home-content">
            <div class="overview-boxes">
                <div class="box">
                    <div class="right-side">
                        <div class="box-topic">Pending Requests</div>
                        <div class="number"><?php echo $pending_count; ?></div>
                        <div class="indicator">
                            <i class='bx bx-time'></i>
                            <span class="text">Needs Approval</span>
                        </div>
                    </div>
                    <i class='bx bx-hourglass cart'></i> </div>
                
                <div class="box">
                    <div class="right-side">
                        <div class="box-topic">Bookings Today</div>
                        <div class="number"><?php echo $today_count; ?></div>
                        <div class="indicator">
                            <i class='bx bx-calendar-check'></i>
                            <span class="text">Scheduled Now</span>
                        </div>
                    </div>
                    <i class='bx bxs-calendar cart two'></i> </div>
                
                <div class="box">
                    <div class="right-side">
                        <div class="box-topic">Active Violations</div>
                        <div class="number"><?php echo $violation_count; ?></div>
                        <div class="indicator">
                            <i class='bx bx-error'></i>
                            <span class="text">Suspended Users</span>
                        </div>
                    </div>
                    <i class='bx bx-error-circle cart three'></i> </div>
            </div>

            <div class="sales-boxes">
                <div class="recent-sales box" style="width: 100%;">
                    <div class="title">Latest Pending Requests</div>
                    
                    <?php if ($recent_res->num_rows > 0): ?>
                    <div class="table-wrapper">
                        <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                            <thead>
                                <tr style="text-align: left; border-bottom: 2px solid #eee;">
                                    <th style="padding: 10px;">Date</th>
                                    <th style="padding: 10px;">Student</th>
                                    <th style="padding: 10px;">Room</th>
                                    <th style="padding: 10px;">Time</th>
                                    <th style="padding: 10px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $recent_res->fetch_assoc()): ?>
                                <tr>
                                    <td data-label="Date"><?php echo date("M d, Y", strtotime($row['reservation_date'])); ?></td>
                                    <td data-label="Student"><?php echo $row['username']; ?></td>
                                    <td data-label="Room"><?php echo $row['room_name']; ?></td>
                                    <td data-label="Time"><?php echo date("g:i A", strtotime($row['start_time'])) . ' - ' . date("g:i A", strtotime($row['end_time'])); ?></td>
                                    <td data-label="Action">
                                        <a href="reservations.php?action=approve&id=<?php echo $row['id']; ?>" class="btn-approve">Review</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div><!-- /.table-wrapper -->
                    <?php else: ?>
                        <p style="padding: 20px; color: #666;">No pending requests at the moment.</p>
                    <?php endif; ?>

                    <div class="button">
                        <a href="reservations.php">View All Reservations</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="../assets/js/main.js"></script>
</body>
</html>
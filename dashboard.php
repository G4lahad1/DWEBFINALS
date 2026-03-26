<?php
session_start();
require 'assets/includes/db_connection.php'; 

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit;
}

// --- CONFIG FOR SIDEBAR OUTPUT AND ACTIVE BAR---
$current_page = 'dashboard'; 
$page_title = 'Student Dashboard';
// --------------------------

// 1. GET DATA FROM DATABASE
$user_id = $_SESSION['user_id'];

// Get Reservations
$sql = "SELECT r.*, rm.room_name 
        FROM reservations r 
        JOIN rooms rm ON r.room_id = rm.id 
        WHERE r.user_id = ? 
        ORDER BY r.reservation_date DESC, r.start_time ASC 
        LIMIT 5";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$my_reservations = [];
while ($row = $result->fetch_assoc()) {
    $my_reservations[] = $row;
}

// 2. GET TOTAL BOOKINGS using stored function fn_get_total_reservations()
$count_stmt = $conn->prepare("SELECT fn_get_total_reservations(?) AS total");
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$total_bookings = $count_stmt->get_result()->fetch_assoc()['total'];
$count_stmt->close();

// 3. GET UPCOMING RESERVATION
$upcoming_sql = "SELECT r.*, rm.room_name 
                 FROM reservations r
                 JOIN rooms rm ON r.room_id = rm.id
                 WHERE r.user_id = ? 
                 AND r.status IN ('approved', 'pending')
                 AND (r.reservation_date > CURDATE() 
                      OR (r.reservation_date = CURDATE() AND r.start_time > CURTIME()))
                 ORDER BY r.reservation_date ASC, r.start_time ASC
                 LIMIT 1";

$up_stmt = $conn->prepare($upcoming_sql);
$up_stmt->bind_param("i", $user_id);
$up_stmt->execute();
$up_result = $up_stmt->get_result();
$upcoming = $up_result->fetch_assoc(); 

// 4. CHECK PENALTY STATUS
$penalty_sql = "SELECT COUNT(*) as count FROM violations WHERE user_id = ? AND status = 'Active'";
$penalty_stmt = $conn->prepare($penalty_sql);
$penalty_stmt->bind_param("i", $user_id);
$penalty_stmt->execute();
$penalty_res = $penalty_stmt->get_result();
$active_violations = $penalty_res->fetch_assoc()['count'];

if ($active_violations > 0) {
    $p_status = "Suspended";
    $p_text = "$active_violations Active Violation(s)";
    $p_icon = "bx-error-circle";
    $p_style = "color: #e74c3c;"; 
    $p_icon_bg = "background: #fdedec; color: #e74c3c;"; 
} 

else {
    $p_status = "Clear";
    $p_text = "Good Standing";
    $p_icon = "bx-check-shield";
    $p_style = ""; 
    $p_icon_bg = ""; 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HAU Library | Student Dashboard</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <style>
        /* === TABLET (769px–1024px) === */
        @media (min-width: 769px) and (max-width: 1024px) {
            /* Keep stat boxes 3-col but compact */
            .overview-boxes .box { padding: 12px 10px; }
            .right-side .number { font-size: 22px; }
            .right-side .box-topic { font-size: 13px; }

            /* Stack recent reservations + quick actions vertically
               so the table spreads full width */
            .sales-boxes {
                flex-direction: column !important;
                gap: 14px;
            }
            .sales-boxes .recent-sales,
            .sales-boxes .top-sales {
                flex: 1 1 100% !important;
                min-width: 0 !important;
                width: 100% !important;
            }

            /* Reservation detail columns — horizontal scroll if needed */
            .sales-details { overflow-x: auto; flex-wrap: nowrap; }
            ul.details li, ul.details li a { font-size: 13px; white-space: nowrap; }

            /* Quick actions */
            .top-sales li .product { font-size: 13px; }
            .top-sales li { padding: 10px; }
        }

        /* ── Reservation table (replaces old ul columns) ── */
        .res-table-wrapper { overflow-x: auto; margin-top: 10px; }
        .res-table {
            width: 100%;
            border-collapse: collapse;
        }
        .res-table th {
            font-size: 13px;
            font-weight: 500;
            color: #999;
            padding: 8px 10px 10px;
            border-bottom: 1px solid #eee;
            text-align: left;
            white-space: nowrap;
        }
        .res-table td {
            font-size: 13px;
            color: #333;
            padding: 11px 10px;
            border-bottom: 1px solid #f3f3f3;
            white-space: nowrap;
        }
        .res-table tr:last-child td { border-bottom: none; }
        .res-table td a { text-decoration: none; color: #333; }

        /* ── MOBILE card layout for the reservation table ── */
        @media (max-width: 768px) {

            /* Overview stat boxes: 2 columns */
            .overview-boxes .box { flex: 1 1 calc(50% - 6px); }
            /* overflow-x on body (set in dashboard.css) handles containment */

            /* Constrain parents — no overflow:hidden so sticky topbar works */
            .home-content,
            .sales-boxes,
            .recent-sales.box,
            .top-sales.box,
            .res-table-wrapper {
                width: 100% !important;
                max-width: 100% !important;
                box-sizing: border-box !important;
                min-width: 0 !important;
            }

            .res-table, .res-table thead, .res-table tbody,
            .res-table th, .res-table td, .res-table tr {
                display: block;
                width: 100%;
                max-width: 100%;
                box-sizing: border-box;
                min-width: 0;
            }

            .res-table thead tr { display: none; }

            .res-table tbody tr {
                background: #fff;
                border: 1px solid #e8e8e8;
                border-radius: 10px;
                margin-bottom: 12px;
                padding: 0;
                box-shadow: 0 2px 8px rgba(0,0,0,0.06);
                overflow: hidden;   /* needed for border-radius to clip corners */
            }
            .res-table tbody tr:last-child { margin-bottom: 0; }

            /* Date = full maroon header (matches history.php style) */
            .res-table td[data-label="Date"] {
                background: #781016;
                color: #fff;
                font-weight: 700;
                font-size: 14px;
                padding: 11px 14px;
                display: block;
                white-space: normal;
                border: none;
            }
            .res-table td[data-label="Date"]::before { display: none; }

            .res-table td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 9px 14px;
                border: none;
                border-top: 1px solid #f3f3f3;
                font-size: 13px;
                color: #444;
                white-space: normal;
                word-break: break-word;
                overflow-wrap: break-word;
                box-sizing: border-box;
                width: 100%;
                max-width: 100%;
                min-width: 0;
            }
            /* Value text — right aligned, natural size */
            .res-table td > span,
            .res-table td > a {
                text-align: right;
                min-width: 0;
                word-break: break-word;
                overflow-wrap: break-word;
            }

            .res-table td::before {
                content: attr(data-label);
                font-weight: 600;
                font-size: 11px;
                color: #bbb;
                text-transform: uppercase;
                letter-spacing: 0.6px;
                flex-shrink: 0;
                min-width: 54px;
                margin-right: 10px;
            }

            /* Value side — never overflows */
            .res-table td > * {
                min-width: 0;
                word-break: break-word;
                overflow-wrap: break-word;
            }

            .res-table td[data-label="Status"] {
                background: #fafafa;
                border-top: 1px solid #efefef;
            }
        }
    </style>
</head>
<body>

    <?php include 'assets/includes/sidebar.php'; ?>
    <?php include 'assets/includes/topbar.php'; ?>

        <div class="home-content">
            <div class="overview-boxes">
                <div class="box">
                    <div class="right-side">
                        <div class="box-topic">Total Bookings</div>
                        <div class="number"><?php echo $total_bookings; ?></div>
                        <div class="indicator">
                            <i class='bx bx-up-arrow-alt'></i>
                            <span class="text">All Time</span>
                        </div>
                    </div>
                </div>
                
                <div class="box">
                    <div class="right-side">
                        <div class="box-topic">Upcoming</div>
                        <?php if ($upcoming): ?>
                            <div class="number">
                                <?php echo ($upcoming['reservation_date'] == date('Y-m-d')) ? "Today" : date("M d", strtotime($upcoming['reservation_date'])); ?>
                            </div>
                            <div class="indicator">
                                <i class='bx bx-time-five'></i>
                                <span class="text">
                                    <?php echo date("g:i A", strtotime($upcoming['start_time'])) . " - " . $upcoming['room_name']; ?>
                                </span>
                            </div>
                        <?php else: ?>
                            <div class="number">--</div>
                            <div class="indicator">
                                <span class="text">No upcoming bookings</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <i class='bx bxs-calendar-check cart two'></i>
                </div>
                
                <div class="box">
                    <div class="right-side">
                        <div class="box-topic">Penalty Status</div>
                        <div class="number" style="<?php echo $p_style; ?>"><?php echo $p_status; ?></div>
                        <div class="indicator">
                            <span class="text" style="<?php echo $p_style; ?>"><?php echo $p_text; ?></span>
                        </div>
                    </div>
                    <i class='bx <?php echo $p_icon; ?> cart three' style="<?php echo $p_icon_bg; ?>"></i>
                </div>
            </div>

            <div class="sales-boxes">
                <div class="recent-sales box">
                    <div class="title">Current & Recent Reservations</div>
                    
                    <?php if (empty($my_reservations)): ?>
                        <div style="padding: 20px; color: #666;">No reservations found. <a href="reserve.php" style="color:#c22727; font-weight:bold;">Book a room now.</a></div>
                    
                    <?php else: ?>
                    <div class="table-wrapper res-table-wrapper">
                        <table class="res-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Room</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($my_reservations as $res):
                                    $status_class = ($res['status'] == 'approved' || $res['status'] == 'completed') ? 'return' : 'pending';
                                    if($res['status'] == 'cancelled') $status_class = 'return';
                                ?>
                                <tr>
                                    <td data-label="Date"><?php echo date("d M Y", strtotime($res['reservation_date'])); ?></td>
                                    <td data-label="Room"><?php echo htmlspecialchars($res['room_name']); ?></td>
                                    <td data-label="Time"><?php echo date("g:i A", strtotime($res['start_time'])) . " - " . date("g:i A", strtotime($res['end_time'])); ?></td>
                                    <td data-label="Status"><span class="status <?php echo $status_class; ?>"><?php echo ucfirst($res['status']); ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php endif; ?>

                    <div class="button">
                        <a href="history.php">See All</a>
                    </div>
                </div>
                
                <div class="top-sales box">
                    <div class="title">Quick Actions</div>
                    <ul class="top-sales-details">
                        <li><a href="reserve.php"><span class="product">Reserve Discussion Room</span></a><span class="price"><i class='bx bx-chevron-right'></i></span></li>
                        <li><a href="reserve.php"><span class="product">Reserve Multimedia Room</span></a><span class="price"><i class='bx bx-chevron-right'></i></span></li>
                        <li><a href="#"><span class="product">Report an Issue</span></a><span class="price"><i class='bx bx-chevron-right'></i></span></li>
                        <li><a href="#"><span class="product">View Library Rules</span></a><span class="price"><i class='bx bx-chevron-right'></i></span></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <script src="assets/js/main.js"></script>
</body>
</html>


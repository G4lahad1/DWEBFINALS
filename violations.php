<?php
session_start();
require 'assets/includes/db_connection.php';

// 1. Check Login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit;
}

// --- CONFIG FOR SIDEBAR OUTPUT AND ACTIVE BAR---
$current_page = 'violations'; 
$page_title = 'Student Violations';


$check_sql = "SELECT id, violation_date, penalty FROM violations WHERE status = 'Active'";
$check_result = $conn->query($check_sql);

if ($check_result->num_rows > 0) {
    while ($row = $check_result->fetch_assoc()) {
        $duration = 0;
        $multiplier = "days"; 
        if (preg_match('/(\d+)\s*(Day|Week|Month)/i', $row['penalty'], $matches)) {
            $count = intval($matches[1]);
            $unit = strtolower($matches[2]);

            // Convert everything to a date string format
            if (strpos($unit, 'week') !== false) {
                $multiplier = "weeks";
            } elseif (strpos($unit, 'month') !== false) {
                $multiplier = "months";
            }

            $violation_start = strtotime($row['violation_date']);
            $expiration_date = strtotime("+$count $multiplier", $violation_start);

            if (time() > $expiration_date) {
                $update_id = $row['id'];
                $conn->query("UPDATE violations SET status = 'Resolved' WHERE id = $update_id");
            }
        }
    }
}

$user_id = $_SESSION['user_id'];

// 2. Fetch Violations
$sql = "SELECT * FROM violations WHERE user_id = ? ORDER BY violation_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// 3. Check Account Standing using the stored function fn_count_active_violations()
//    This keeps the business logic in the database layer and reusable.
$fn_stmt = $conn->prepare("SELECT fn_count_active_violations(?) AS active_count");
$fn_stmt->bind_param("i", $user_id);
$fn_stmt->execute();
$active_violations = $fn_stmt->get_result()->fetch_assoc()['active_count'];
$fn_stmt->close();

// Determine Status Appearance
$status_color = ($active_violations > 0) ? '#e74c3c' : '#2ecc71'; // Red if bad, Green if good
$status_text = ($active_violations > 0) ? 'Suspended / Penalty Active' : 'Good Standing';
$status_icon = ($active_violations > 0) ? 'bx-error' : 'bx-check-shield';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HAU Library | Violations</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    
    <style>
        /* Status Banner at the top */
        .status-banner {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 5px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .status-icon-box {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        .status-info h4 {
            margin: 0;
            font-size: 18px;
            color: #333;
        }
        .status-info p {
            margin: 5px 0 0;
            color: #666;
            font-size: 14px;
        }

        /* Table Styles (Matching your History Page) */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #eee; }
        th { font-weight: 500; color: #0A2558; }
        
        /* Badges */
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; }
        .badge.active { background: #f8d7da; color: #721c24; } /* Red */
        .badge.resolved { background: #d4edda; color: #155724; } /* Green */

        /* =============================================
           CARD LAYOUT — Violations on Mobile
           ============================================= */
        @media (min-width: 769px) and (max-width: 1024px) {
            table { min-width: 600px; }
            th, td { padding: 10px 12px; font-size: 13px; white-space: nowrap; }
            .table-wrapper { overflow-x: auto; }
            .status-banner { flex-direction: row; align-items: center; }
        }

        @media (max-width: 768px) {

            /* Constrain all parent containers */
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

            /* Status banner stacks on mobile */
            .status-banner {
                flex-direction: column;
                align-items: flex-start;
            }

            /* Break table into cards */
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

            thead tr { display: none; }

            /* Each <tr> = one card */
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

            /* Date = maroon header strip at top of card */
            tbody td[data-label="Date"] {
                background: #781016;
                color: #fff;
                font-weight: 600;
                font-size: 14px;
                padding: 10px 14px;
                border: none;
                display: block;
            }
            tbody td[data-label="Date"]::before { display: none; }

            /* All other rows: label left, value right */
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

            /* Description can be long — let it wrap */
            tbody td[data-label="Description"] {
                flex-direction: column;
                gap: 4px;
            }
            tbody td[data-label="Description"]::before {
                margin-right: 0;
                margin-bottom: 2px;
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

            /* Status row — badge aligned right */
            tbody td[data-label="Status"] {
                background: #fafafa;
                justify-content: space-between;
                align-items: center;
            }
        }


        @media (max-width: 480px) {
            tbody td { font-size: 12px; padding: 8px 12px; }
            tbody td[data-label="Date"] { font-size: 13px; padding: 9px 12px; }
            tbody td::before { min-width: 54px; }
        }

        /* === TABLET: keep normal table layout === */
    </style>
</head>
<body>

  <?php include 'assets/includes/sidebar.php'; ?>
  <?php include 'assets/includes/topbar.php'; ?>


<div class="home-content">
    <div class="sales-boxes">
        <div style="width: 100%;">
            
            <div class="status-banner">
                <div class="status-icon-box" style="background-color: <?php echo $status_color; ?>;">
                    <i class='bx <?php echo $status_icon; ?>'></i>
                </div>
                <div class="status-info">
                    <h4>Current Status: <span style="color: <?php echo $status_color; ?>;"><?php echo $status_text; ?></span></h4>
                    <p>
                        <?php if($active_violations > 0): ?>
                            You have <?php echo $active_violations; ?> active violation(s). Please contact the librarian to resolve this.
                        <?php else: ?>
                            You have no active violations. You are eligible to reserve rooms.
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <div class="recent-sales box" style="width: 100%;">
                <div class="title">Violation Record</div>
                
                <?php if ($result->num_rows > 0): ?>
                <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Violation Type</th>
                            <th>Description</th>
                            <th>Penalty</th>
                            <th style="text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td data-label="Date"><?php echo date("M d, Y", strtotime($row['violation_date'])); ?></td>
                                <td data-label="Type">
                                    <span style="font-weight: 500; color: #333;">
                                        <?php echo htmlspecialchars($row['violation_type']); ?>
                                    </span>
                                </td>
                                <td data-label="Description" style="color: #666;"><?php echo htmlspecialchars($row['description']); ?></td>
                                <td data-label="Penalty" style="color: #e74c3c; font-weight: 500;"><?php echo htmlspecialchars($row['penalty']); ?></td>
                                <td data-label="Status">
                                    <span class="badge <?php echo strtolower($row['status']); ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                </div><!-- /.table-wrapper -->
                <?php else: ?>
                    <div style="padding: 40px; text-align: center; color: #666;">
                        <i class='bx bx-check-circle' style="font-size: 40px; color: #2ecc71; margin-bottom: 10px;"></i>
                        <p>Great job! You have zero violations on your record.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>
    </section>

    <script src="assets/js/main.js"></script>
</body>
</html>

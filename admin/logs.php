<?php
// admin/logs.php
session_start();

// SECURITY CHECK: Ensure user is logged in as Admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../index.php");
    exit;
}

include '../assets/includes/db_connection.php';

// Fetch the 50 most recent logs
$sql = "SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 50";
$result = $conn->query($sql);
$current_page = 'logs'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Audit Logs | Admin</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    
    <style>
        .logs-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 14px;
        }
        .logs-table th {
            text-align: left;
            padding: 12px;
            background-color: #f8fafc;
            color: #333;
            font-weight: 600;
            border-bottom: 2px solid #e2e8f0;
        }
        .logs-table td {
            padding: 12px;
            border-bottom: 1px solid #f1f5f9;
            color: #475569;
        }
        .logs-table tr:hover {
            background-color: #f8fafc;
        }
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            background-color: #e0e7ff;
            color: #3730a3;
        }
        .badge-danger { background-color: #fee2e2; color: #991b1b; }
        .badge-success { background-color: #d1fae5; color: #065f46; }


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

            .table-wrapper { overflow-x: visible; }
            /* Let table fill full width — no horizontal scroll needed */
            table { width: 100%; min-width: 0; table-layout: fixed; }
            th, td { padding: 10px 10px; font-size: 12px; white-space: nowrap; vertical-align: top; }
            /* Details column: take remaining space and wrap text */
            th:last-child, td:last-child {
                white-space: normal !important;
                word-break: break-word;
                overflow-wrap: break-word;
                width: 35%;
            }
            /* Date col */
            th:first-child, td:first-child { width: 18%; }
            /* Username col */
            th:nth-child(2), td:nth-child(2) { width: 12%; }
            /* Action badge col */
            th:nth-child(3), td:nth-child(3) { width: 22%; }
            td .badge, td .status-badge {
                white-space: nowrap !important;
                display: inline-flex;
                align-items: center;
            }
            .home-content { padding: 16px; }
        }
        /* === MOBILE === */
        
        /* Desktop: table fills full width */
        .table-wrapper { width: 100%; overflow-x: auto; }
        .table-wrapper table { width: 100% !important; min-width: 0 !important; }

        /* =============================================
           CARD LAYOUT — Audit Logs Table on Mobile
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

            /* Date = maroon left-border header */
            tbody td[data-label="Date"] {
                background: #f9f1f1;
                border-left: 4px solid #781016;
                color: #781016;
                font-weight: 700;
                font-size: 14px;
                padding: 12px 14px;
                display: block;
                border-top: none;
            }
            tbody td[data-label="Date"]::before { display: none; }

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

            /* Details cell — long text wraps */
            tbody td[data-label="Details"] {
                flex-direction: column;
                gap: 4px;
                color: #666;
                font-size: 12px;
            }
            tbody td[data-label="Details"]::before {
                margin-bottom: 2px;
            }

            /* Action type badge row */
            tbody td[data-label="Action"] {
                background: #fafafa;
                border-top: 1px solid #efefef;
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
            <div class="sales-boxes">
                <div class="recent-sales box" style="width: 100%;">
                    <div class="title">Recent Activity</div>
                    
                    <div class="table-wrapper">
                        <table class="logs-table">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Username</th>
                                    <th>Action</th>
                                    <th>Details</th>
                
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <?php 
                                            // PHP Logic to dynamically assign badge colors
                                            $badgeClass = 'badge';
                                            $action = strtoupper($row['action_type']);
                                            
                                            if (strpos($action, 'FAILED') !== false || strpos($action, 'REJECT') !== false || strpos($action, 'SANCTION') !== false) {
                                                $badgeClass .= ' badge-danger';
                                            } elseif (strpos($action, 'SUCCESS') !== false || strpos($action, 'APPROVE') !== false) {
                                                $badgeClass .= ' badge-success';
                                            }
                                        ?>
                                        <tr>
                                            <td data-label="Date"><?php echo date('M d, Y h:i A', strtotime($row['created_at'])); ?></td>
                                            <td data-label="User"><?php echo htmlspecialchars($row['user_id']); ?></td>
                                            <td data-label="Action"><span class="<?php echo $badgeClass; ?>"><?php echo htmlspecialchars($row['action_type']); ?></span></td>
                                            <td data-label="Details"><?php echo htmlspecialchars($row['details']); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center; padding: 20px;">No system logs found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="../assets/js/main.js"></script>
</body>
</html>
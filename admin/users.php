<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../index.php");
    exit;
}

require '../assets/includes/db_connection.php';
$current_page = 'users';

// Fetch students and count active violations
$sql = "SELECT u.id, u.username, u.full_name, 
        (SELECT COUNT(*) FROM violations v WHERE v.user_id = u.id AND v.status = 'Active') as active_violations
        FROM users u 
        WHERE u.role = 'student' 
        ORDER BY u.full_name ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students | LibSpace Admin</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    
    <style>
        /* Status Badges */
        .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; }
        .status-clear { background: #d5f5e3; color: #2ecc71; }
        .status-suspended { background: #fadbd8; color: #e74c3c; }

        /* Action Buttons */
        .btn { padding: 5px 10px; border-radius: 4px; text-decoration: none; color: white; font-size: 12px; border: none; cursor: pointer; }
        .btn-sanction { background: #e74c3c; } 
        .btn-resolve { background: #2ecc71; } 

        /* --- YOUR CUSTOM MODAL STYLES --- */
        .modal-overlay { 
            display: none; 
            position: fixed; top: 0; left: 0; 
            width: 100%; height: 100%; 
            background: rgba(0,0,0,0.5); 
            z-index: 1000; 
            justify-content: center; align-items: center; 
        }
        .modal-box { 
            background: #fff; padding: 30px; border-radius: 12px; 
            width: 400px; text-align: center; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.3); 
            animation: fadeIn 0.3s; 
        }
        .modal-buttons { display: flex; gap: 10px; justify-content: center; margin-top: 20px; }
        .btn-close { background: #ccc; color: #333; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        
        /* Green Confirm Button for Resolving */
        .btn-confirm-green { background: #2ecc71; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .btn-confirm-red { background: #e74c3c; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        
        /* Form inputs for the Sanction Modal */
        .form-group { margin-bottom: 15px; text-align: left; }
        .form-group label { display: block; margin-bottom: 5px; font-size: 14px; color: #555; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px; }

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
        
        /* Desktop: table always fills full width */
        .table-wrapper { width: 100%; overflow-x: auto; }
        .table-wrapper table { width: 100% !important; min-width: 0 !important; }

        /* =============================================
           CARD LAYOUT — Students/Users Table on Mobile
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

            /* Full name = left-bordered header */
            tbody td[data-label="Name"] {
                background: #f9f1f1;
                border-left: 4px solid #781016;
                color: #781016;
                font-weight: 700;
                font-size: 15px;
                padding: 12px 14px;
                display: block;
                border-top: none;
            }
            tbody td[data-label="Name"]::before { display: none; }

            tbody td[data-label="Student ID"] {
                color: #888;
                font-size: 12px;
                background: #fafafa;
                border-top: 1px solid #f0e0e0;
            }

            tbody td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 10px 16px;
                border: none;
                border-top: 1px solid #f3f3f3;
                font-size: 13px;
                color: #444;
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

            tbody td[data-label="Action"] {
                background: #fafafa;
                justify-content: flex-end;
                gap: 8px;
                padding: 10px 14px;
                border-top: 1px solid #efefef;
            }
            tbody td[data-label="Action"]::before { display: none; }

            .btn { padding: 7px 14px !important; font-size: 12px !important; border-radius: 6px !important; }
        }

        @media (max-width: 480px) {
            tbody td { font-size: 12px; padding: 8px 12px; }
            tbody td[data-label="Name"] { font-size: 13px; padding: 10px 12px; }
            tbody td[data-label="Student ID"] { font-size: 12px; padding: 8px 12px; color: #888; }
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
                <div class="recent-sales box" style="width:100%;">
                    <div class="title">Student Directory</div>
                    <div class="table-wrapper">
                        <table style="width:100%; border-collapse: collapse; margin-top:15px;">
                            <thead>
                                <tr style="text-align:left; border-bottom: 2px solid #eee;">
                                    <th style="padding:10px;">Full Name</th>
                                    <th>Student ID</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $result->fetch_assoc()): 
                                    $is_suspended = $row['active_violations'] > 0;
                                ?>
                                <tr>
                                    <td data-label="Name" style="font-weight:500;"><?php echo $row['full_name']; ?></td>
                                    <td data-label="Student ID" style="color:#888;"><?php echo $row['username']; ?></td>
                                    <td data-label="Status">
                                        <?php if($is_suspended): ?>
                                            <span class="status-badge status-suspended">Suspended</span>
                                        <?php else: ?>
                                            <span class="status-badge status-clear">Good Standing</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Action">
                                        <?php if($is_suspended): ?>
                                            <button class="btn btn-resolve" onclick="openResolveModal(<?php echo $row['id']; ?>)">
                                               Resolve
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sanction" onclick="openSanctionModal(<?php echo $row['id']; ?>, '<?php echo $row['full_name']; ?>')">
                                                Sanction
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div><!-- /.table-wrapper -->
                </div>
            </div>
        </div>
    </section>

    <div id="resolveModal" class="modal-overlay">
        <div class="modal-box">
            <i class='bx bx-check-circle' style="font-size: 50px; color: #2ecc71;"></i>
            <h3>Lift Suspension?</h3>
            <p>Are you sure you want to resolve all violations for this student? They will be allowed to book rooms again.</p>
                
            <form action="../assets/actions/admin_violation_action.php" method="GET">
                <input type="hidden" name="action" value="resolve">
                <input type="hidden" name="user_id" id="modal_resolve_id" value=""> 
                
                <div class="modal-buttons">
                    <button type="button" class="btn-close" onclick="closeResolveModal()">Cancel</button>
                    <button type="submit" class="btn-confirm-green">Yes, Lift Ban</button>
                </div>
            </form>
        </div>
    </div>

    <div id="sanctionModal" class="modal-overlay">
        <div class="modal-box" style="text-align: left;">
            <h3 style="margin-bottom: 15px; text-align: center;">Sanction Student</h3>
            <p id="modalStudentName" style="margin-bottom: 15px; color: #666; text-align: center;"></p>
            
            <form action="../assets/actions/admin_violation_action.php" method="POST">
                <input type="hidden" name="user_id" id="modalSanctionId">
                <input type="hidden" name="sanction_student" value="true">

                <div class="form-group">
                    <label>Violation Type</label>
                    <select name="violation_type" required>
                        <option value="Late Return">Late Return</option>
                        <option value="Noise/Disruption">Noise/Disruption</option>
                        <option value="Lost Item">Lost Item</option>
                        <option value="Damaged Equipment">Damaged Equipment</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Penalty Duration</label>
                    <select name="penalty" required>
                        <option value="1 Day">1 Day Suspension</option>
                        <option value="3 Days">3 Days Suspension</option>
                        <option value="1 Week">1 Week Suspension</option>
                        <option value="Indefinite">Indefinite (Requires Admin Removal)</option>
                    </select>
                </div>

                <div class="modal-buttons">
                    <button type="button" class="btn-close" onclick="closeSanctionModal()">Cancel</button>
                    <button type="submit" class="btn-confirm-red">Apply Sanction</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        // --- LOGIC FOR RESOLVE MODAL ---
        function openResolveModal(id) {
            document.getElementById('modal_resolve_id').value = id;
            document.getElementById('resolveModal').style.display = 'flex';
        }
        function closeResolveModal() {
            document.getElementById('resolveModal').style.display = 'none';
        }

   
        function openSanctionModal(id, name) {
            document.getElementById('modalSanctionId').value = id;
            document.getElementById('modalStudentName').innerText = "Student: " + name;
            document.getElementById('sanctionModal').style.display = 'flex';
        }
        function closeSanctionModal() {
            document.getElementById('sanctionModal').style.display = 'none';
        }

      
        window.onclick = function(event) {
            var m1 = document.getElementById('resolveModal');
            var m2 = document.getElementById('sanctionModal');
            if (event.target == m1) m1.style.display = "none";
            if (event.target == m2) m2.style.display = "none";
        }
    </script>
</body>
</html>
<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../index.php");
    exit;
}

require '../assets/includes/db_connection.php';
$current_page = 'rooms';

// Show alert if room deletion was blocked due to active bookings
$delete_blocked = isset($_GET['error']) && $_GET['error'] === 'has_active_bookings';

// Fetch all rooms
$sql = "SELECT * FROM rooms ORDER BY room_name ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Rooms | LibSpace Admin</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    
    <style>
        /* Form Styles */
        .form-container { background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 5px 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: 500; margin-bottom: 5px; color: #333; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
        
        /* Submit Button */
        .btn-submit { background: #0A2558; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; width: 100%; font-size: 15px; }
        .btn-submit:hover { background: #081D45; }

        /* Delete Button */
        .btn-delete { background: #fadbd8; color: #e74c3c; padding: 5px 10px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: 500; border: none; cursor: pointer; }
        .btn-delete:hover { background: #e74c3c; color: white; }

        /* Modal CSS */
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
        .btn-confirm { background: #e74c3c; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        @keyframes fadeIn { from {opacity: 0; transform: translateY(-20px);} to {opacity: 1; transform: translateY(0);} }

        /* Mobile Responsive adjustments */
        @media (max-width: 768px) {
            .sales-boxes { flex-direction: column !important; }
            .sales-boxes .box { width: 100% !important; min-width: 0 !important; flex: 1 1 100% !important; }
            
            thead { display: none; }
            tbody tr { display: block; margin-bottom: 15px; border: 1px solid #eee; border-radius: 8px; }
            tbody td { display: flex; justify-content: space-between; padding: 10px; border: none; border-bottom: 1px solid #f5f5f5; }
            tbody td::before { content: attr(data-label); font-weight: 600; color: #666; }
        }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <section class="home-section">
        <?php include 'topbar.php'; ?><div class="home-content">

            <?php if ($delete_blocked): ?>
            <div style="background:#fdecea;border-left:4px solid #e74c3c;padding:12px 18px;margin-bottom:16px;border-radius:4px;color:#c0392b;font-weight:500;">
                <i class='bx bx-error-circle'></i>
                Cannot delete room — it still has pending or approved reservations.
            </div>
            <?php endif; ?>

            <div class="sales-boxes" style="display: flex; gap: 20px; flex-wrap: wrap;">
                
                <div class="recent-sales box" style="flex: 1; min-width: 300px;">
                    <div class="title">Add New Room</div>
                    <form action="../assets/actions/admin_room_action.php" method="POST" style="margin-top: 20px;">
                        <div class="form-group">
                            <label>Room Name</label>
                            <input type="text" name="room_name" placeholder="e.g. Discussion Room A" required>
                        </div>
                        <div class="form-group">
                            <label>Room Type</label>
                            <select name="room_type">
                                <option value="Discussion">Discussion Room</option>
                                <option value="Multimedia">Multimedia Room</option>
                                <option value="Quiet Area">Quiet Area</option>
                                <option value="Lab">Computer Lab</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Capacity (People)</label>
                            <input type="number" name="capacity" min="1" value="6" required>
                        </div>
                        <button type="submit" name="add_room" class="btn-submit">
                            <i class='bx bx-plus-circle'></i> Add Room
                        </button>
                    </form>
                </div>

                <div class="recent-sales box" style="flex: 2; min-width: 400px;">
                    <div class="title">Existing Rooms</div>
                    <div class="table-wrapper">
                        <table style="width:100%; border-collapse: collapse; margin-top:15px;">
                            <thead>
                                <tr style="text-align:left; border-bottom: 2px solid #eee;">
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Capacity</th>
                                    <th style="text-align: right; padding-right: 20px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td data-label="Name" style="font-weight: 500;">
                                            <?php echo htmlspecialchars($row['room_name']); ?>
                                        </td>
                                        <td data-label="Type"><?php echo htmlspecialchars($row['type']); ?></td>
                                        <td data-label="Capacity"><?php echo htmlspecialchars($row['capacity']); ?> pax</td>
                                        <td data-label="Action" style="text-align: right;">
                                            <button class="btn-delete" onclick="openDeleteModal(<?php echo $row['id']; ?>)">
                                               <i class='bx bx-trash'></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" style="padding:20px; text-align: center;">No rooms found. Add one!</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <div id="deleteModal" class="modal-overlay">
        <div class="modal-box">
            <i class='bx bx-error-circle' style="font-size: 50px; color: #e74c3c;"></i>
            <h3>Delete Room?</h3>
            <p>Are you sure you want to delete this room? This action cannot be undone.</p>
                
            <form action="../assets/actions/admin_room_action.php" method="GET">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="modal_room_id" value=""> 
                
                <div class="modal-buttons">
                    <button type="button" class="btn-close" onclick="closeDeleteModal()">No, Keep it</button>
                    <button type="submit" class="btn-confirm">Yes, Delete it</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        function toggleMenu() {
            document.getElementById("subMenu").classList.toggle("open-menu");
        }
        
        function openDeleteModal(id) {
            document.getElementById('modal_room_id').value = id;
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        window.onclick = function(event) {
            var modal = document.getElementById('deleteModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>
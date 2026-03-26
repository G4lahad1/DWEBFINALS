<?php
session_start();
require '../assets/includes/db_connection.php';

// 1. Check Login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../index.php");
    exit;
}

// --- CONFIG FOR SIDEBAR OUTPUT ---
$current_page = 'profile'; 
$page_title = 'Admin Profile';

$user_id = $_SESSION['user_id'];

// 2. Fetch User Details
$sql = "SELECT username, full_name, profile_image FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Load profile picture from file-based storage (assets/uploads/profiles/)
$_pic_file = $user['profile_image'] ?? '';
$_pic_path = dirname(__DIR__) . '/assets/uploads/profiles/' . $_pic_file;
if (!empty($_pic_file) && file_exists($_pic_path)) {
    $profilePic = '../assets/uploads/profiles/' . htmlspecialchars($_pic_file);
} else {
    $profilePic = '../assets/css/photos/profile.png';
}

// 3. Handle Messages
$msg = "";
$msg_type = "";
if (isset($_GET['msg'])) {
    $msg_map = [
        'uploaded'     => ['Profile picture updated!', 'success'],
        'updated'      => ['Password changed successfully!', 'success'],
        'err_file'     => ['Error uploading file. Make sure it is an image.', 'error'],
        'err_pass'     => ['Incorrect current password.', 'error'],
        'err_mismatch' => ['New passwords do not match.', 'error']
    ];
    if (isset($msg_map[$_GET['msg']])) {
        $msg = $msg_map[$_GET['msg']][0];
        $msg_type = $msg_map[$_GET['msg']][1];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HAU Library | Profile</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .profile-card {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 10px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 600px;
            margin: 40px auto; /* Added margin for better spacing */
        }

        .profile-img-container {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto 20px;
        }

        .profile-img-lg {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #580a0a;
        }

        .upload-icon {
            position: absolute;
            bottom: 0;
            right: 0;
            background: #580a0a;
            color: #fff;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 2px solid #fff;
        }

        .form-group { text-align: left; margin-bottom: 15px; }
        .form-group label { font-weight: 500; color: #333; display: block; margin-bottom: 5px; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; }
        
        .section-title {
            text-align: left;
            margin: 30px 0 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            color: #851016;
            font-size: 18px;
            font-weight: 600;
        }

        .btn-save {
            background: #580a0a;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
            font-size: 15px;
            transition: 0.3s;
        }
        .btn-save:hover { opacity: 0.9; }

        .alert { padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 14px; }
        .alert.success { background: #d4edda; color: #155724; }
        .alert.error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <section class="home-section">
        <?php include 'topbar.php'; ?>

        <div class="home-content"> <div class="profile-card">
                
                <?php if($msg): ?>
                    <div class="alert <?php echo $msg_type; ?>"><?php echo $msg; ?></div>
                <?php endif; ?>

                <form action="../assets/actions/admin_update_profile.php" method="POST" enctype="multipart/form-data">
                    <div class="profile-img-container">
                        <img src="<?php echo $profilePic; ?>" class="profile-img-lg" id="previewImg">
                        <input type="file" name="profile_image" id="fileInput" style="display: none;" accept="image/*" onchange="this.form.submit()">
                        <label for="fileInput" class="upload-icon">
                            <i class='bx bx-camera'></i>
                        </label>
                    </div>
                    <input type="hidden" name="action" value="update_photo">
                </form>

                <h2><?php echo htmlspecialchars($user['full_name']); ?></h2>
                <p style="color: #666; font-size: 14px;">Admin ID: <?php echo htmlspecialchars($user['username']); ?></p>

                <div class="section-title">Change Password</div>
                
                <form action="../assets/actions/admin_update_profile.php" method="POST">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" required>
                    </div>

                    <button type="submit" class="btn-save">Update Password</button>
                </form>
            </div>
        </div>
    </section>

    <script src="../assets/js/main.js"></script>
</body>
</html>
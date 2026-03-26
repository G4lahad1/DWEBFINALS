<?php
/**
 * Admin Topbar — shared include for all admin pages.
 *
 * Requires before including:
 *   $page_title  (string) — text shown in the top-left breadcrumb label
 *
 * Session variables used:
 *   $_SESSION['name']          — admin display name
 *   $_SESSION['profile_image'] — profile pic filename (stored in assets/uploads/profiles/)
 */

// Determine profile picture path
$_tb_pic_file = $_SESSION['profile_image'] ?? '';
$_tb_base     = dirname(__DIR__) . '/assets/uploads/profiles/';
$_tb_webpath  = '../assets/uploads/profiles/';

if (!empty($_tb_pic_file) && file_exists($_tb_base . $_tb_pic_file)) {
    $topbarProfilePic = $_tb_webpath . htmlspecialchars($_tb_pic_file);
} else {
    $topbarProfilePic = '../assets/css/photos/profile.png';
}

$topbarName = htmlspecialchars($_SESSION['name'] ?? $_SESSION['username'] ?? 'Admin');
$topbarTitle = htmlspecialchars($page_title ?? 'Admin Panel');
?>

<nav class="top-navbar">
    <div class="sidebar-button">
        <i class='bx bx-menu sidebarBtn'></i>
        <span class="dashboard"><?php echo $topbarTitle; ?></span>
    </div>

    <div class="profile-details" onclick="toggleMenu()">
        <img src="<?php echo $topbarProfilePic; ?>" alt="profile">
        <span class="admin_name"><?php echo $topbarName; ?></span>
        <i class='bx bx-chevron-down'></i>

        <div class="sub-menu-wrap" id="subMenu">
            <div class="sub-menu">
                <div class="user-info">
                    <h3><?php echo $topbarName; ?></h3>
                </div>
                <hr>
                <a href="profile.php" class="sub-menu-link">
                    <i class='bx bx-user'></i>
                    <p>Profile</p>
                    <span>&#8250;</span>
                </a>
                <a href="../assets/actions/logout.php" class="sub-menu-link">
                    <i class='bx bx-log-out'></i>
                    <p>Logout</p>
                    <span>&#8250;</span>
                </a>
            </div>
        </div>
    </div>
</nav>

<script>
    function toggleMenu() {
        document.getElementById("subMenu").classList.toggle("open-menu");
    }
    window.addEventListener("click", function(e) {
        if (!e.target.closest('.profile-details')) {
            var m = document.getElementById("subMenu");
            if (m) m.classList.remove("open-menu");
        }
    });
</script>

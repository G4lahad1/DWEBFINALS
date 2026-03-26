<section class="home-section">
    <nav class="top-navbar">
        <div class="sidebar-button">
            <i class='bx bx-menu sidebarBtn'></i>
            <span class="dashboard"><?= $page_title ?></span>
        </div>

        <div class="profile-details" onclick="toggleMenu()">
            <?php
                $profilePic = "assets/css/photos/profile.png";
                if (isset($_SESSION['profile_image']) && !empty($_SESSION['profile_image'])) {
                    $profilePic = 'data:image/jpeg;base64,' . base64_encode($_SESSION['profile_image']);
                }
                $displayName = isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Student';
            ?>
            <img src="<?php echo $profilePic; ?>" alt="profile">
            <span class="admin_name"><?php echo $displayName; ?></span>
            <i class='bx bx-chevron-down'></i>

            <div class="sub-menu-wrap" id="subMenu">
                <div class="sub-menu">
                    <div class="user-info">
                        <h3><?php echo $displayName; ?></h3>
                    </div>
                    <hr>
                    <a href="profile.php" class="sub-menu-link">
                        <i class='bx bx-user'></i>
                        <p>Profile</p>
                        <span>&#8250;</span>
                    </a>
                    <a href="assets/actions/logout.php" class="sub-menu-link">
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
    window.addEventListener("click", function(event) {
        if (!event.target.closest('.profile-details')) {
            var subMenu = document.getElementById("subMenu");
            if (subMenu && subMenu.classList.contains('open-menu')) {
                subMenu.classList.remove('open-menu');
            }
        }
    });
</script>

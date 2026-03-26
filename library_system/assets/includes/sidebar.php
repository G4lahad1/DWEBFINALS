<!-- Dark overlay — shown behind sidebar on mobile when open -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<nav class="sidebar" id="sidebar">
    <div class="logo-details">
        <img src="assets/css/photos/HAU Logo.png" alt="HAU Logo">
        <span class="logo_name">HAU Library</span>
    </div>
    
    <ul class="nav-links">
        <li>
            <a href="dashboard.php" class="<?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>">
                <i class='bx bx-grid-alt'></i>
                <span class="link_name">Dashboard</span>
            </a>
        </li>
        <li>
            <a href="reserve.php" class="<?php echo ($current_page == 'reserve') ? 'active' : ''; ?>">
                <i class='bx bx-calendar-plus'></i>
                <span class="link_name">Reserve Room</span>
            </a>
        </li>
        <li>
            <a href="history.php" class="<?php echo ($current_page == 'history') ? 'active' : ''; ?>">
                <i class='bx bx-history'></i>
                <span class="link_name">My History</span>
            </a>
        </li>
        <li>
            <a href="violations.php" class="<?php echo ($current_page == 'violations') ? 'active' : ''; ?>">
                <i class='bx bx-error-circle'></i>
                <span class="link_name">Violations</span>
            </a>
        </li>
        <li>
            <a href="profile.php" class="<?php echo ($current_page == 'profile') ? 'active' : ''; ?>">
                <i class='bx bx-user'></i>
                <span class="link_name">Profile</span>
            </a>
        </li>
        <li class="log_out">
            <a href="assets/actions/logout.php">
                <i class='bx bx-log-out'></i>
                <span class="link_name">Log Out</span>
            </a>
        </li>
    </ul>
</nav>

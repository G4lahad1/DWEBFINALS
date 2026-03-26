<!-- Dark overlay — shown behind sidebar on mobile when open -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<nav class="sidebar" id="sidebar">
    <div class="logo-details">
        <img src="../assets/css/photos/HAU Logo.png" alt="HAU Logo">
        <span class="logo_name">Admin Portal</span>
    </div>
    
    <ul class="nav-links">
        <li>
            <a href="dashboard.php" class="<?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>">
                <i class='bx bx-grid-alt'></i>
                <span class="link_name">Dashboard</span>
            </a>
        </li>
        <li>
            <a href="reservations.php" class="<?php echo ($current_page == 'reservations') ? 'active' : ''; ?>">
                <i class='bx bx-list-ul'></i>
                <span class="link_name">Reservations</span>
            </a>
        </li>
        <li>
            <a href="rooms.php" class="<?php echo ($current_page == 'rooms') ? 'active' : ''; ?>">
                <i class='bx bx-door-open'></i>
                <span class="link_name">Manage Rooms</span>
            </a>
        </li>
        <li>
            <a href="users.php" class="<?php echo ($current_page == 'users') ? 'active' : ''; ?>">
                <i class='bx bx-user-pin'></i>
                <span class="link_name">Students</span>
            </a>
        </li>
        <li>
            <a href="logs.php" class="<?php echo ($current_page == 'logs') ? 'active' : ''; ?>">
                <i class='bx bx-shield-quarter'></i>
                <span class="link_name">Audit Logs</span>
            </a>
        </li>
         <li>
            <a href="profile.php" class="<?php echo ($current_page == 'profile') ? 'active' : ''; ?>">
                <i class='bx bx-user'></i>
                <span class="link_name">Profile</span>
            </a>
        </li>
        <li class="log_out">
            <a href="../assets/actions/logout.php">
                <i class='bx bx-log-out'></i>
                <span class="link_name">Log Out</span>
            </a>
        </li>
    </ul>
</nav>

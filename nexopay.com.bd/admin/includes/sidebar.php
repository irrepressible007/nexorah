<nav class="admin-sidebar">
    <ul class="sidebar-menu">
        <li><a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a></li>
        <li><a href="events.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'events.php' ? 'active' : ''; ?>">
            <i class="fas fa-calendar"></i> Events
        </a></li>
        <li><a href="registrations.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'registrations.php' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i> Registrations
        </a></li>
        <li><a href="highlights.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'highlights.php' ? 'active' : ''; ?>">
            <i class="fas fa-star"></i> Highlights
        </a></li>
        <li><a href="partners.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'partners.php' ? 'active' : ''; ?>">
            <i class="fas fa-handshake"></i> Partners
        </a></li>
        <li><a href="messages.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'active' : ''; ?>">
            <i class="fas fa-envelope"></i> Messages
        </a></li>
        <li><a href="settings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i> Settings
        </a></li>
    </ul>
</nav>
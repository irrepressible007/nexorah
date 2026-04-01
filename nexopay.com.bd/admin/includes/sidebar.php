<nav class="admin-sidebar" style="overflow-y:auto; overflow-x:hidden;">
    <ul class="sidebar-menu">
        <li><a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a></li>
        <li><a href="events.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'events.php' ? 'active' : ''; ?>">
            <i class="fas fa-calendar"></i> Events
        </a></li>
        <li><a href="view_events.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'view_events.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-pie"></i> View Events
        </a></li>
        
        <?php
        // Ensure Database connection exists
        if (!isset($db)) {
            require_once dirname(__DIR__, 2) . '/config/database.php';
            $db = (new Database())->getConnection();
        }
        $ongoing_events = $db->query("SELECT id, title FROM events WHERE status = 'ongoing' ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
        ?>
        
        <li class="nav-item">
            <a href="#" onclick="toggleSubMenu('ongoingEventsMenu'); return false;" style="display: flex; justify-content: space-between; align-items: center;">
                <span><i class="fas fa-calendar-check"></i> View Ongoing Events</span>
                <i class="fas fa-chevron-down" style="font-size: 0.8em;"></i>
            </a>
            <ul id="ongoingEventsMenu" class="sidebar-submenu" style="display: <?php echo isset($_GET['event_id']) ? 'block' : 'none'; ?>; list-style: none; padding-left: 15px; margin-top: 5px; background: rgba(0,0,0,0.05); border-radius: 5px;">
                <?php if (empty($ongoing_events)): ?>
                    <li><a href="#" style="font-size: 0.9em; padding: 8px 15px; color: #888;">No ongoing events</a></li>
                <?php else: ?>
                    <?php foreach ($ongoing_events as $ev): ?>
                        <li style="margin-bottom: 5px;">
                            <a href="#" onclick="toggleSubMenu('eventMenu<?php echo $ev['id']; ?>'); return false;" style="font-size: 0.95em; border-left: 2px solid #ddd; display: flex; justify-content: space-between;">
                                <span style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:85%;">
                                    <i class="fas fa-cube"></i> <?php echo htmlspecialchars($ev['title']); ?>
                                </span>
                                <i class="fas fa-angle-down" style="font-size: 0.7em;"></i>
                            </a>
                            <ul id="eventMenu<?php echo $ev['id']; ?>"" style="display: <?php echo (isset($_GET['event_id']) && $_GET['event_id'] == $ev['id']) ? 'block' : 'none'; ?>; list-style: none; padding-left: 10px; margin-top: 5px;">
                                <li>
                                    <a href="registrations.php?event_id=<?php echo $ev['id']; ?>" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'registrations.php' && isset($_GET['event_id']) && $_GET['event_id'] == $ev['id']) ? 'active' : ''; ?>" style="font-size: 0.85em; padding: 6px 15px;">
                                        <i class="fas fa-users" style="font-size: 0.9em;"></i> General Registrations
                                    </a>
                                </li>
                                <li>
                                    <a href="volunteer_applications.php?event_id=<?php echo $ev['id']; ?>" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'volunteer_applications.php' && isset($_GET['event_id']) && $_GET['event_id'] == $ev['id']) ? 'active' : ''; ?>" style="font-size: 0.85em; padding: 6px 15px;">
                                        <i class="fas fa-hands-helping" style="font-size: 0.9em;"></i> Volunteers
                                    </a>
                                </li>
                                <li>
                                    <a href="ca_applications.php?event_id=<?php echo $ev['id']; ?>" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'ca_applications.php' && isset($_GET['event_id']) && $_GET['event_id'] == $ev['id']) ? 'active' : ''; ?>" style="font-size: 0.85em; padding: 6px 15px;">
                                        <i class="fas fa-graduation-cap" style="font-size: 0.9em;"></i> CA Applications
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </li>

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

<script>
function toggleSubMenu(menuId) {
    var menu = document.getElementById(menuId);
    if (menu.style.display === "none" || menu.style.display === "") {
        menu.style.display = "block";
    } else {
        menu.style.display = "none";
    }
}
</script>

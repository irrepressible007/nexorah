<header class="admin-header">
    <div class="header-content">
        <div class="header-left">
            <button id="sidebar-toggle" class="sidebar-toggle" aria-label="Toggle sidebar">
                <i class="fas fa-bars"></i>
            </button>
            <h2>Nexopay Admin</h2>
        </div>
        <div class="header-right">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </div>
</header>
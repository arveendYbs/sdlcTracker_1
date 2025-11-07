<!-- Sidebar -->
<div class="sidebar">
    <link rel="stylesheet" href="css/styles.css">
    <div class="sidebar-brand">
        <div class="brand-icon">📊</div>
        <div class="brand-text">SDLC Tracker</div>
    </div>
    <nav class="sidebar-menu">
        <div class="menu-header">MAIN</div>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">
            <span class="icon">📊</span>
            <span>Dashboard</span>
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'projects.php' ? 'active' : ''; ?>" href="projects.php">
            <span class="icon">📁</span>
            <span>Projects</span>
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'features.php' ? 'active' : ''; ?>" href="features.php">
            <span class="icon">✨</span>
            <span>Features</span>
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'bugs.php' ? 'active' : ''; ?>" href="bugs.php">
            <span class="icon">🐛</span>
            <span>Bugs</span>
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'releases.php' ? 'active' : ''; ?>" href="releases.php">
            <span class="icon">🚀</span>
            <span>Releases</span>
        </a>
    </nav>
</div>
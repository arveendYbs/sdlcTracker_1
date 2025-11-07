<?php
/**
 * Dashboard - Main Entry Point
 * Modern Sneat-inspired design
 */

require_once 'db.php';

// Fetch dashboard statistics
$stats = [];

// Total projects
$result = $conn->query("SELECT COUNT(*) as count FROM projects");
$stats['total_projects'] = $result->fetch_assoc()['count'];

// Total features
$result = $conn->query("SELECT COUNT(*) as count FROM features");
$stats['total_features'] = $result->fetch_assoc()['count'];

// Total bugs
$result = $conn->query("SELECT COUNT(*) as count FROM bugs");
$stats['total_bugs'] = $result->fetch_assoc()['count'];

// Total releases
$result = $conn->query("SELECT COUNT(*) as count FROM releases");
$stats['total_releases'] = $result->fetch_assoc()['count'];

// Active projects
$result = $conn->query("SELECT COUNT(*) as count FROM projects WHERE status = 'Active'");
$stats['active_projects'] = $result->fetch_assoc()['count'];

// Open bugs
$result = $conn->query("SELECT COUNT(*) as count FROM bugs WHERE status = 'Open'");
$stats['open_bugs'] = $result->fetch_assoc()['count'];

// Features in progress
$result = $conn->query("SELECT COUNT(*) as count FROM features WHERE status = 'In Progress'");
$stats['features_in_progress'] = $result->fetch_assoc()['count'];

// SDLC Phase distribution for chart
$phase_query = "SELECT phase, COUNT(*) as count FROM projects GROUP BY phase ORDER BY FIELD(phase, 'Requirements', 'Design', 'Development', 'Testing', 'Deployment')";
$phase_result = $conn->query($phase_query);
$phase_data = [];
while ($row = $phase_result->fetch_assoc()) {
    $phase_data[] = $row;
}

// Recent projects
$recent_projects_query = "SELECT * FROM projects ORDER BY created_at DESC LIMIT 5";
$recent_projects = $conn->query($recent_projects_query);

// Project status distribution
$status_query = "SELECT status, COUNT(*) as count FROM projects GROUP BY status";
$status_result = $conn->query($status_query);
$status_data = [];
while ($row = $status_result->fetch_assoc()) {
    $status_data[] = $row;
}

// Bug severity distribution
$severity_query = "SELECT severity, COUNT(*) as count FROM bugs GROUP BY severity ORDER BY FIELD(severity, 'Low', 'Medium', 'High', 'Critical')";
$severity_result = $conn->query($severity_query);
$severity_data = [];
while ($row = $severity_result->fetch_assoc()) {
    $severity_data[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SDLC Tracker</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #696cff;
            --success: #71dd37;
            --danger: #ff3e1d;
            --warning: #ffab00;
            --info: #03c3ec;
            --dark: #233446;
            --secondary: #8592a3;
            --light-bg: #f5f5f9;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--light-bg);
            font-size: 15px;
            color: #697a8d;
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 260px;
            height: 100vh;
            background: #fff;
            box-shadow: 0 0.125rem 0.5rem rgba(0,0,0,0.04);
            z-index: 1000;
            overflow-y: auto;
        }
        
        .sidebar-brand {
            padding: 1.5rem 1.625rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-bottom: 1px solid #f0f2f5;
        }
        
        .brand-icon {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, var(--primary), #9055fd);
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.25rem;
        }
        
        .brand-text {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .sidebar-menu { padding: 1.25rem 0; }
        
        .menu-header {
            padding: 0.75rem 1.625rem 0.375rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: #a1acb8;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        
        .sidebar .nav-link {
            color: #697a8d;
            padding: 0.5625rem 1.625rem;
            margin: 0.125rem 1rem;
            border-radius: 0.375rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
            transition: all 0.2s;
            text-decoration: none;
        }
        
        .sidebar .nav-link .icon {
            width: 20px;
            text-align: center;
        }
        
        .sidebar .nav-link:hover {
            background: rgba(105, 108, 255, 0.08);
            color: var(--primary);
        }
        
        .sidebar .nav-link.active {
            background: linear-gradient(72deg, var(--primary) 22%, rgba(105, 108, 255, 0.7) 76%);
            color: #fff !important;
            box-shadow: 0 0.125rem 0.375rem rgba(105, 108, 255, 0.4);
        }
        
        /* Main Content */
        .main-content {
            margin-left: 260px;
            padding: 1.625rem 1.875rem;
            min-height: 100vh;
        }
        
        .page-header {
            margin-bottom: 1.5rem;
        }
        
        .page-header h4 {
            font-size: 1.625rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.125rem;
        }
        
        .page-header .subtitle {
            color: #a1acb8;
            font-size: 0.9375rem;
        }
        
        /* Stat Cards */
        .stat-card {
            background: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 0.125rem 0.375rem rgba(67, 89, 113, 0.12);
            transition: all 0.2s;
            border: none;
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 0.25rem 0.75rem rgba(67, 89, 113, 0.16);
        }
        
        .stat-card .card-body {
            padding: 1.5rem;
        }
        
        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .stat-card.projects .stat-icon {
            background: rgba(105, 108, 255, 0.12);
            color: var(--primary);
        }
        
        .stat-card.features .stat-icon {
            background: rgba(113, 221, 55, 0.12);
            color: var(--success);
        }
        
        .stat-card.bugs .stat-icon {
            background: rgba(255, 62, 29, 0.12);
            color: var(--danger);
        }
        
        .stat-card.releases .stat-icon {
            background: rgba(3, 195, 236, 0.12);
            color: var(--info);
        }
        
        .stat-value {
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        .stat-label {
            font-size: 0.9375rem;
            color: #a1acb8;
            margin-top: 0.25rem;
        }
        
        .stat-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.8125rem;
            font-weight: 600;
            margin-top: 0.75rem;
        }
        
        .stat-badge.success { color: var(--success); }
        .stat-badge.danger { color: var(--danger); }
        
        /* Chart Cards */
        .chart-card {
            background: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 0.125rem 0.375rem rgba(67, 89, 113, 0.12);
            border: none;
        }
        
        .chart-card .card-header {
            background: transparent;
            border: none;
            padding: 1.5rem 1.5rem 0.75rem;
        }
        
        .chart-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
        }
        
        .chart-card .card-body {
            padding: 0 1.5rem 1.5rem;
        }
        
        .chart-container {
            position: relative;
            height: 320px;
        }
        
        /* Recent Projects */
        .list-group-item {
            border: none;
            padding: 1rem;
            margin-bottom: 0.5rem;
            background: #fafbfc;
            border-radius: 0.375rem;
            transition: all 0.2s;
        }
        
        .list-group-item:hover {
            background: #f0f2f5;
            transform: translateX(4px);
        }
        
        .project-title {
            font-weight: 600;
            color: var(--dark);
            font-size: 0.9375rem;
            margin-bottom: 0.25rem;
        }
        
        .project-desc {
            font-size: 0.8125rem;
            color: #a1acb8;
            margin-bottom: 0.5rem;
        }
        
        /* Badges */
        .badge {
            padding: 0.375rem 0.75rem;
            font-weight: 500;
            font-size: 0.75rem;
            border-radius: 0.25rem;
        }
        
        .bg-label-primary {
            background: rgba(105, 108, 255, 0.12) !important;
            color: var(--primary) !important;
        }
        
        .bg-label-success {
            background: rgba(113, 221, 55, 0.12) !important;
            color: var(--success) !important;
        }
        
        .bg-label-danger {
            background: rgba(255, 62, 29, 0.12) !important;
            color: var(--danger) !important;
        }
        
        .bg-label-warning {
            background: rgba(255, 171, 0, 0.12) !important;
            color: var(--warning) !important;
        }
        
        .bg-label-info {
            background: rgba(3, 195, 236, 0.12) !important;
            color: var(--info) !important;
        }
        
        .bg-label-secondary {
            background: rgba(133, 146, 163, 0.12) !important;
            color: var(--secondary) !important;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon">📊</div>
            <div class="brand-text">SDLC Tracker</div>
        </div>
        <nav class="sidebar-menu">
            <div class="menu-header">MAIN</div>
            <a class="nav-link active" href="index.php">
                <span class="icon">📊</span>
                <span>Dashboard</span>
            </a>
            <a class="nav-link" href="projects.php">
                <span class="icon">📁</span>
                <span>Projects</span>
            </a>
            <a class="nav-link" href="features.php">
                <span class="icon">✨</span>
                <span>Features</span>
            </a>
            <a class="nav-link" href="bugs.php">
                <span class="icon">🐛</span>
                <span>Bugs</span>
            </a>
            <a class="nav-link" href="releases.php">
                <span class="icon">🚀</span>
                <span>Releases</span>
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h4>Dashboard 👋</h4>
            <p class="subtitle">Welcome to your SDLC Project Tracker</p>
        </div>

        <!-- Stats Cards -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card stat-card projects">
                    <div class="card-body">
                        <div class="stat-icon">📁</div>
                        <div class="stat-value"><?php echo $stats['total_projects']; ?></div>
                        <div class="stat-label">Total Projects</div>
                        <div class="stat-badge success">
                            <span>↑</span> <?php echo $stats['active_projects']; ?> Active
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card stat-card features">
                    <div class="card-body">
                        <div class="stat-icon">✨</div>
                        <div class="stat-value"><?php echo $stats['total_features']; ?></div>
                        <div class="stat-label">Total Features</div>
                        <div class="stat-badge success">
                            <span>⚡</span> <?php echo $stats['features_in_progress']; ?> In Progress
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card stat-card bugs">
                    <div class="card-body">
                        <div class="stat-icon">🐛</div>
                        <div class="stat-value"><?php echo $stats['total_bugs']; ?></div>
                        <div class="stat-label">Total Bugs</div>
                        <div class="stat-badge danger">
                            <span>⚠️</span> <?php echo $stats['open_bugs']; ?> Open
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card stat-card releases">
                    <div class="card-body">
                        <div class="stat-icon">🚀</div>
                        <div class="stat-value"><?php echo $stats['total_releases']; ?></div>
                        <div class="stat-label">Total Releases</div>
                        <div class="stat-badge success">
                            <span>✓</span> Planned & Completed
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-lg-6">
                <div class="card chart-card">
                    <div class="card-header">
                        <h5 class="chart-title">SDLC Phase Distribution</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="phaseChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-12 col-lg-6">
                <div class="card chart-card">
                    <div class="card-header">
                        <h5 class="chart-title">Project Status Overview</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Charts & Recent Projects -->
        <div class="row g-3">
            <div class="col-12 col-lg-6">
                <div class="card chart-card">
                    <div class="card-header">
                        <h5 class="chart-title">Bug Severity Analysis</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="severityChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-12 col-lg-6">
                <div class="card chart-card">
                    <div class="card-header">
                        <h5 class="chart-title">Recent Projects</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <?php while ($project = $recent_projects->fetch_assoc()): ?>
                            <a href="project_detail.php?id=<?php echo $project['id']; ?>" 
                               class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="project-title"><?php echo html_escape($project['name']); ?></div>
                                        <div class="project-desc"><?php echo substr(html_escape($project['description']), 0, 60) . '...'; ?></div>
                                        <div>
                                            <span class="badge bg-label-<?php echo get_badge_class($project['status'], 'project'); ?>">
                                                <?php echo $project['status']; ?>
                                            </span>
                                            <span class="badge bg-label-<?php echo get_badge_class($project['phase'], 'phase'); ?> ms-1">
                                                <?php echo $project['phase']; ?>
                                            </span>
                                        </div>
                                    </div>
                                    <small class="text-muted"><?php echo $project['progress']; ?>%</small>
                                </div>
                            </a>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="js/chart.umd.js"></script>
    <script>
        // SDLC Phase Chart
        const phaseCtx = document.getElementById('phaseChart').getContext('2d');
        new Chart(phaseCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($phase_data, 'phase')); ?>,
                datasets: [{
                    label: 'Projects',
                    data: <?php echo json_encode(array_column($phase_data, 'count')); ?>,
                    backgroundColor: [
                        'rgba(133, 146, 163, 0.85)',
                        'rgba(3, 195, 236, 0.85)',
                        'rgba(105, 108, 255, 0.85)',
                        'rgba(255, 171, 0, 0.85)',
                        'rgba(113, 221, 55, 0.85)'
                    ],
                    borderRadius: 8,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { 
                        beginAtZero: true,
                        ticks: { stepSize: 1 },
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    x: { grid: { display: false } }
                }
            }
        });

        // Status Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($status_data, 'status')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($status_data, 'count')); ?>,
                    backgroundColor: [
                        'rgba(113, 221, 55, 0.85)',
                        'rgba(255, 171, 0, 0.85)',
                        'rgba(3, 195, 236, 0.85)'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 15, usePointStyle: true }
                    }
                }
            }
        });

        // Bug Severity Chart
        const severityCtx = document.getElementById('severityChart').getContext('2d');
        new Chart(severityCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_column($severity_data, 'severity')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($severity_data, 'count')); ?>,
                    backgroundColor: [
                        'rgba(3, 195, 236, 0.85)',
                        'rgba(255, 171, 0, 0.85)',
                        'rgba(255, 62, 29, 0.85)',
                        'rgba(35, 52, 70, 0.85)'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 15, usePointStyle: true }
                    }
                }
            }
        });
    </script>
</body>
</html>
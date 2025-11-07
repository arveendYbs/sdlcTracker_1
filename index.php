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
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'sidebar.php'; ?>

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
<?php
/**
 * Dashboard - Main Entry Point
 * 
 * Displays overview statistics and charts for the SDLC Project Tracker
 * including total counts and SDLC phase distribution.
 */

require_once __DIR__ . '/db.php';

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
    <title>Dashboard - SDLC Project Tracker</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
            padding: 0;
        }
        .sidebar .nav-link {
            color: #ecf0f1;
            padding: 12px 20px;
            border-left: 3px solid transparent;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-left-color: #3498db;
        }
        .sidebar .nav-link.active {
            background-color: rgba(52, 152, 219, 0.2);
            border-left-color: #3498db;
            font-weight: 600;
        }
        .sidebar-brand {
            padding: 20px;
            color: #fff;
            font-size: 1.5rem;
            font-weight: bold;
            background-color: rgba(0, 0, 0, 0.2);
        }
        .main-content {
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .stat-card {
            border-left: 4px solid;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .stat-card.projects { border-left-color: #3498db; }
        .stat-card.features { border-left-color: #2ecc71; }
        .stat-card.bugs { border-left-color: #e74c3c; }
        .stat-card.releases { border-left-color: #9b59b6; }
        .chart-container {
            position: relative;
            height: 300px;
        }
        .page-header {
            background: white;
            padding: 20px;
            margin-bottom: 30px;
            border-bottom: 3px solid #3498db;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar">
                <div class="sidebar-brand">
                    <i class="bi bi-kanban"></i> SDLC Tracker
                </div>
                <nav class="nav flex-column mt-3">
                    <a class="nav-link active" href="index.php">
                        <span>📊</span> Dashboard
                    </a>
                    <a class="nav-link" href="projects.php">
                        <span>📁</span> Projects
                    </a>
                    <a class="nav-link" href="features.php">
                        <span>✨</span> Features
                    </a>
                    <a class="nav-link" href="bugs.php">
                        <span>🐛</span> Bugs
                    </a>
                    <a class="nav-link" href="releases.php">
                        <span>🚀</span> Releases
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 main-content p-4">
                <!-- Page Header -->
                <div class="page-header">
                    <h2 class="mb-0">📊 Dashboard Overview</h2>
                    <p class="text-muted mb-0">Real-time statistics and insights</p>
                </div>

                <!-- Statistics Cards -->
                <div class="row g-4 mb-4">
                    <!-- Total Projects -->
                    <div class="col-md-3">
                        <div class="card stat-card projects">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Total Projects</h6>
                                        <h2 class="mb-0"><?php echo $stats['total_projects']; ?></h2>
                                        <small class="text-success">
                                            <?php echo $stats['active_projects']; ?> Active
                                        </small>
                                    </div>
                                    <div class="text-primary" style="font-size: 3rem; opacity: 0.3;">📁</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Features -->
                    <div class="col-md-3">
                        <div class="card stat-card features">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Total Features</h6>
                                        <h2 class="mb-0"><?php echo $stats['total_features']; ?></h2>
                                        <small class="text-warning">
                                            <?php echo $stats['features_in_progress']; ?> In Progress
                                        </small>
                                    </div>
                                    <div class="text-success" style="font-size: 3rem; opacity: 0.3;">✨</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Bugs -->
                    <div class="col-md-3">
                        <div class="card stat-card bugs">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Total Bugs</h6>
                                        <h2 class="mb-0"><?php echo $stats['total_bugs']; ?></h2>
                                        <small class="text-danger">
                                            <?php echo $stats['open_bugs']; ?> Open
                                        </small>
                                    </div>
                                    <div class="text-danger" style="font-size: 3rem; opacity: 0.3;">🐛</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Releases -->
                    <div class="col-md-3">
                        <div class="card stat-card releases">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Total Releases</h6>
                                        <h2 class="mb-0"><?php echo $stats['total_releases']; ?></h2>
                                        <small class="text-info">Planned & Completed</small>
                                    </div>
                                    <div class="text-info" style="font-size: 3rem; opacity: 0.3;">🚀</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row g-4 mb-4">
                    <!-- SDLC Phase Distribution -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">SDLC Phase Distribution</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="phaseChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Project Status Distribution -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">Project Status Overview</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="statusChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Charts Row -->
                <div class="row g-4 mb-4">
                    <!-- Bug Severity Distribution -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-danger text-white">
                                <h5 class="mb-0">Bug Severity Analysis</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="severityChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Projects -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0">Recent Projects</h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    <?php while ($project = $recent_projects->fetch_assoc()): ?>
                                    <a href="project_detail.php?id=<?php echo $project['id']; ?>" 
                                       class="list-group-item list-group-item-action">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo html_escape($project['name']); ?></h6>
                                            <small>
                                                <span class="badge bg-<?php echo get_badge_class($project['phase'], 'phase'); ?>">
                                                    <?php echo $project['phase']; ?>
                                                </span>
                                            </small>
                                        </div>
                                        <p class="mb-1 small text-muted">
                                            <?php echo substr(html_escape($project['description']), 0, 80) . '...'; ?>
                                        </p>
                                        <small>
                                            <span class="badge bg-<?php echo get_badge_class($project['status'], 'project'); ?>">
                                                <?php echo $project['status']; ?>
                                            </span>
                                            <span class="text-muted ms-2">Progress: <?php echo $project['progress']; ?>%</span>
                                        </small>
                                    </a>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="js/chart.umd.js"></script>
    <script>
        // SDLC Phase Distribution Chart
        const phaseCtx = document.getElementById('phaseChart').getContext('2d');
        new Chart(phaseCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($phase_data, 'phase')); ?>,
                datasets: [{
                    label: 'Number of Projects',
                    data: <?php echo json_encode(array_column($phase_data, 'count')); ?>,
                    backgroundColor: [
                        'rgba(108, 117, 125, 0.8)',
                        'rgba(23, 162, 184, 0.8)',
                        'rgba(0, 123, 255, 0.8)',
                        'rgba(255, 193, 7, 0.8)',
                        'rgba(40, 167, 69, 0.8)'
                    ],
                    borderColor: [
                        'rgba(108, 117, 125, 1)',
                        'rgba(23, 162, 184, 1)',
                        'rgba(0, 123, 255, 1)',
                        'rgba(255, 193, 7, 1)',
                        'rgba(40, 167, 69, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Project Status Distribution Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($status_data, 'status')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($status_data, 'count')); ?>,
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.8)',
                        'rgba(255, 193, 7, 0.8)',
                        'rgba(23, 162, 184, 0.8)'
                    ],
                    borderColor: [
                        'rgba(40, 167, 69, 1)',
                        'rgba(255, 193, 7, 1)',
                        'rgba(23, 162, 184, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Bug Severity Distribution Chart
        const severityCtx = document.getElementById('severityChart').getContext('2d');
        new Chart(severityCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_column($severity_data, 'severity')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($severity_data, 'count')); ?>,
                    backgroundColor: [
                        'rgba(23, 162, 184, 0.8)',
                        'rgba(255, 193, 7, 0.8)',
                        'rgba(220, 53, 69, 0.8)',
                        'rgba(52, 58, 64, 0.8)'
                    ],
                    borderColor: [
                        'rgba(23, 162, 184, 1)',
                        'rgba(255, 193, 7, 1)',
                        'rgba(220, 53, 69, 1)',
                        'rgba(52, 58, 64, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>
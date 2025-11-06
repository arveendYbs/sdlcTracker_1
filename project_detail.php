<?php
/**
 * Project Detail Page
 * 
 * Displays detailed information about a specific project
 * including associated features, bugs, and releases with visualizations
 */

require_once __DIR__ . '/db.php';

// Get project ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: projects.php');
    exit;
}

$project_id = (int)$_GET['id'];

// Fetch project details
$stmt = $conn->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();
$project = $result->fetch_assoc();
$stmt->close();

if (!$project) {
    header('Location: projects.php');
    exit;
}

// Fetch project statistics
$stats = [];

// Feature stats
$result = $conn->query("SELECT status, COUNT(*) as count FROM features WHERE project_id = $project_id GROUP BY status");
$stats['features'] = [];
while ($row = $result->fetch_assoc()) {
    $stats['features'][$row['status']] = $row['count'];
}

// Bug stats
$result = $conn->query("SELECT status, COUNT(*) as count FROM bugs WHERE project_id = $project_id GROUP BY status");
$stats['bugs'] = [];
while ($row = $result->fetch_assoc()) {
    $stats['bugs'][$row['status']] = $row['count'];
}

// Release stats
$result = $conn->query("SELECT COUNT(*) as count FROM releases WHERE project_id = $project_id");
$stats['releases_count'] = $result->fetch_assoc()['count'];

// Fetch features
$features_result = $conn->query("SELECT * FROM features WHERE project_id = $project_id ORDER BY created_at DESC");

// Fetch bugs
$bugs_result = $conn->query("SELECT * FROM bugs WHERE project_id = $project_id ORDER BY created_at DESC");

// Fetch releases
$releases_result = $conn->query("SELECT * FROM releases WHERE project_id = $project_id ORDER BY release_date DESC");

// Calculate feature completion percentage
$total_features = array_sum($stats['features']);
$done_features = $stats['features']['Done'] ?? 0;
$feature_completion = $total_features > 0 ? round(($done_features / $total_features) * 100) : 0;

// Bug severity distribution
$severity_result = $conn->query("SELECT severity, COUNT(*) as count FROM bugs WHERE project_id = $project_id GROUP BY severity ORDER BY FIELD(severity, 'Low', 'Medium', 'High', 'Critical')");
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
    <title><?php echo html_escape($project['name']); ?> - SDLC Project Tracker</title>
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
        .page-header {
            background: white;
            padding: 20px;
            margin-bottom: 30px;
            border-bottom: 3px solid #3498db;
        }
        .stat-box {
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid;
            margin-bottom: 15px;
        }
        .chart-container {
            position: relative;
            height: 250px;
        }
        .progress {
            height: 25px;
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
                    <a class="nav-link" href="index.php">
                        <span>📊</span> Dashboard
                    </a>
                    <a class="nav-link active" href="projects.php">
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
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-0">📁 <?php echo html_escape($project['name']); ?></h2>
                            <p class="text-muted mb-0"><?php echo html_escape($project['description']); ?></p>
                        </div>
                        <a href="projects.php" class="btn btn-secondary">← Back to Projects</a>
                    </div>
                </div>

                <!-- Project Overview -->
                <div class="row g-4 mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Project Details</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <strong>Status:</strong>
                                        <span class="badge bg-<?php echo get_badge_class($project['status'], 'project'); ?> ms-2">
                                            <?php echo $project['status']; ?>
                                        </span>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <strong>SDLC Phase:</strong>
                                        <span class="badge bg-<?php echo get_badge_class($project['phase'], 'phase'); ?> ms-2">
                                            <?php echo $project['phase']; ?>
                                        </span>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <strong>Start Date:</strong> <?php echo format_date($project['start_date']); ?>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <strong>End Date:</strong> <?php echo format_date($project['end_date']); ?>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <strong>Overall Progress:</strong>
                                        <div class="progress mt-2">
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                 style="width: <?php echo $project['progress']; ?>%"
                                                 aria-valuenow="<?php echo $project['progress']; ?>" 
                                                 aria-valuemin="0" aria-valuemax="100">
                                                <?php echo $project['progress']; ?>%
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0">Quick Stats</h5>
                            </div>
                            <div class="card-body">
                                <div class="stat-box bg-light" style="border-left-color: #2ecc71;">
                                    <h4 class="mb-1"><?php echo $total_features; ?></h4>
                                    <small class="text-muted">Total Features</small>
                                </div>
                                <div class="stat-box bg-light" style="border-left-color: #e74c3c;">
                                    <h4 class="mb-1"><?php echo array_sum($stats['bugs']); ?></h4>
                                    <small class="text-muted">Total Bugs</small>
                                </div>
                                <div class="stat-box bg-light" style="border-left-color: #9b59b6;">
                                    <h4 class="mb-1"><?php echo $stats['releases_count']; ?></h4>
                                    <small class="text-muted">Total Releases</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">Feature Status Distribution</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="featureChart"></canvas>
                                </div>
                                <div class="mt-3 text-center">
                                    <h5>Feature Completion: <?php echo $feature_completion; ?>%</h5>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-danger text-white">
                                <h5 class="mb-0">Bug Severity Distribution</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="bugChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Features Table -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">✨ Features (<?php echo $total_features; ?>)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Feature Title</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($features_result->num_rows > 0): ?>
                                        <?php while ($feature = $features_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $feature['id']; ?></td>
                                            <td><?php echo html_escape($feature['title']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo get_badge_class($feature['status'], 'feature'); ?>">
                                                    <?php echo $feature['status']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo format_date($feature['created_at'], 'M d, Y g:i A'); ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">No features found for this project</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Bugs Table -->
                <div class="card mb-4">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0">🐛 Bugs (<?php echo array_sum($stats['bugs']); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Bug Title</th>
                                        <th>Severity</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($bugs_result->num_rows > 0): ?>
                                        <?php while ($bug = $bugs_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $bug['id']; ?></td>
                                            <td><?php echo html_escape($bug['title']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo get_badge_class($bug['severity'], 'severity'); ?>">
                                                    <?php echo $bug['severity']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo get_badge_class($bug['status'], 'bug'); ?>">
                                                    <?php echo $bug['status']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo format_date($bug['created_at'], 'M d, Y g:i A'); ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">No bugs found for this project</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Releases Table -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">🚀 Releases (<?php echo $stats['releases_count']; ?>)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Version</th>
                                        <th>Release Date</th>
                                        <th>Progress</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($releases_result->num_rows > 0): ?>
                                        <?php while ($release = $releases_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $release['id']; ?></td>
                                            <td><strong><?php echo html_escape($release['version']); ?></strong></td>
                                            <td><?php echo format_date($release['release_date']); ?></td>
                                            <td>
                                                <div class="progress" style="width: 100px;">
                                                    <div class="progress-bar bg-info" role="progressbar" 
                                                         style="width: <?php echo $release['progress']; ?>%">
                                                        <?php echo $release['progress']; ?>%
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo substr(html_escape($release['notes']), 0, 50) . '...'; ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">No releases found for this project</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="js/chart.umd.js"></script>
    <script>
        // Feature Status Chart
        const featureCtx = document.getElementById('featureChart').getContext('2d');
        new Chart(featureCtx, {
            type: 'doughnut',
            data: {
                labels: ['To Do', 'In Progress', 'Done'],
                datasets: [{
                    data: [
                        <?php echo $stats['features']['To Do'] ?? 0; ?>,
                        <?php echo $stats['features']['In Progress'] ?? 0; ?>,
                        <?php echo $stats['features']['Done'] ?? 0; ?>
                    ],
                    backgroundColor: [
                        'rgba(108, 117, 125, 0.8)',
                        'rgba(255, 193, 7, 0.8)',
                        'rgba(40, 167, 69, 0.8)'
                    ],
                    borderColor: [
                        'rgba(108, 117, 125, 1)',
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
                        position: 'bottom'
                    }
                }
            }
        });

        // Bug Severity Chart
        const bugCtx = document.getElementById('bugChart').getContext('2d');
        new Chart(bugCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($severity_data, 'severity')); ?>,
                datasets: [{
                    label: 'Number of Bugs',
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
    </script>
</body>
</html>
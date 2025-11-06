<?php
/**
 * Releases Management Page
 * 
 * Full CRUD operations for managing releases and versions
 * Includes: List view, Add, Edit, and Delete functionality
 */

require_once __DIR__ . '/db.php';

// Handle form submissions
$message = '';
$message_type = '';

// DELETE operation
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM releases WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $message = "Release deleted successfully!";
        $message_type = "success";
    } else {
        $message = "Error deleting release: " . $conn->error;
        $message_type = "danger";
    }
    $stmt->close();
}

// CREATE operation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'create') {
    $project_id = (int)$_POST['project_id'];
    $version = trim($_POST['version']);
    $release_date = $_POST['release_date'];
    $notes = trim($_POST['notes']);
    $progress = (int)$_POST['progress'];
    
    $stmt = $conn->prepare("INSERT INTO releases (project_id, version, release_date, notes, progress) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isssi", $project_id, $version, $release_date, $notes, $progress);
    
    if ($stmt->execute()) {
        $message = "Release created successfully!";
        $message_type = "success";
    } else {
        $message = "Error creating release: " . $conn->error;
        $message_type = "danger";
    }
    $stmt->close();
}

// UPDATE operation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update') {
    $id = (int)$_POST['id'];
    $project_id = (int)$_POST['project_id'];
    $version = trim($_POST['version']);
    $release_date = $_POST['release_date'];
    $notes = trim($_POST['notes']);
    $progress = (int)$_POST['progress'];
    
    $stmt = $conn->prepare("UPDATE releases SET project_id=?, version=?, release_date=?, notes=?, progress=? WHERE id=?");
    $stmt->bind_param("isssii", $project_id, $version, $release_date, $notes, $progress, $id);
    
    if ($stmt->execute()) {
        $message = "Release updated successfully!";
        $message_type = "success";
    } else {
        $message = "Error updating release: " . $conn->error;
        $message_type = "danger";
    }
    $stmt->close();
}

// Fetch all releases with project names
$releases_query = "SELECT r.*, p.name as project_name FROM releases r 
                   LEFT JOIN projects p ON r.project_id = p.id 
                   ORDER BY r.release_date DESC, r.created_at DESC";
$releases_result = $conn->query($releases_query);

// Fetch all projects for dropdown
$projects_result = $conn->query("SELECT id, name FROM projects ORDER BY name");

// Fetch release for editing if edit parameter is set
$edit_release = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM releases WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_release = $result->fetch_assoc();
    $stmt->close();
}

// Get release statistics
$total_releases = $conn->query("SELECT COUNT(*) as count FROM releases")->fetch_assoc()['count'];
$upcoming_releases = $conn->query("SELECT COUNT(*) as count FROM releases WHERE release_date > CURDATE()")->fetch_assoc()['count'];
$past_releases = $conn->query("SELECT COUNT(*) as count FROM releases WHERE release_date <= CURDATE()")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Releases - SDLC Project Tracker</title>
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
            border-bottom: 3px solid #9b59b6;
        }
        .stat-mini {
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 10px;
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
                    <a class="nav-link" href="projects.php">
                        <span>📁</span> Projects
                    </a>
                    <a class="nav-link" href="features.php">
                        <span>✨</span> Features
                    </a>
                    <a class="nav-link" href="bugs.php">
                        <span>🐛</span> Bugs
                    </a>
                    <a class="nav-link active" href="releases.php">
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
                            <h2 class="mb-0">🚀 Release Planning</h2>
                            <p class="text-muted mb-0">Manage version releases and deployment schedules</p>
                        </div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#releaseModal" onclick="resetForm()">
                            ➕ Plan New Release
                        </button>
                    </div>
                </div>

                <!-- Alert Messages -->
                <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo html_escape($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="stat-mini bg-primary text-white">
                            <strong>Total Releases:</strong> <?php echo $total_releases; ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-mini bg-warning text-dark">
                            <strong>Upcoming:</strong> <?php echo $upcoming_releases; ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-mini bg-success text-white">
                            <strong>Past Releases:</strong> <?php echo $past_releases; ?>
                        </div>
                    </div>
                </div>

                <!-- Releases Table -->
                <div class="card">
                    <div class="card-header" style="background-color: #9b59b6; color: white;">
                        <h5 class="mb-0">All Releases</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Version</th>
                                        <th>Project</th>
                                        <th>Release Date</th>
                                        <th>Progress</th>
                                        <th>Notes</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($releases_result->num_rows > 0): ?>
                                        <?php while ($release = $releases_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $release['id']; ?></td>
                                            <td><strong><?php echo html_escape($release['version']); ?></strong></td>
                                            <td>
                                                <a href="project_detail.php?id=<?php echo $release['project_id']; ?>" class="text-decoration-none">
                                                    <?php echo html_escape($release['project_name']); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <?php 
                                                $date = format_date($release['release_date']);
                                                $is_upcoming = strtotime($release['release_date']) > time();
                                                $badge_class = $is_upcoming ? 'warning' : 'success';
                                                ?>
                                                <span class="badge bg-<?php echo $badge_class; ?>">
                                                    <?php echo $date; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="progress">
                                                    <div class="progress-bar bg-info" role="progressbar" 
                                                         style="width: <?php echo $release['progress']; ?>%"
                                                         aria-valuenow="<?php echo $release['progress']; ?>" 
                                                         aria-valuemin="0" aria-valuemax="100">
                                                        <?php echo $release['progress']; ?>%
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php 
                                                $notes = html_escape($release['notes']);
                                                echo strlen($notes) > 50 ? substr($notes, 0, 50) . '...' : $notes; 
                                                ?>
                                            </td>
                                            <td>
                                                <a href="?edit=<?php echo $release['id']; ?>" 
                                                   class="btn btn-sm btn-warning" 
                                                   data-bs-toggle="modal" 
                                                   data-bs-target="#releaseModal"
                                                   onclick="editRelease(<?php echo htmlspecialchars(json_encode($release)); ?>)"
                                                   title="Edit">✏️</a>
                                                <a href="?delete=<?php echo $release['id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this release?')"
                                                   title="Delete">🗑️</a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4">
                                                <p class="text-muted mb-0">No releases found. Click "Plan New Release" to get started!</p>
                                            </td>
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

    <!-- Release Modal (Create/Edit) -->
    <div class="modal fade" id="releaseModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header text-white" style="background-color: #9b59b6;">
                    <h5 class="modal-title" id="modalTitle">Plan New Release</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="formAction" value="create">
                        <input type="hidden" name="id" id="releaseId">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="project_id" class="form-label">Project *</label>
                                <select class="form-select" id="project_id" name="project_id" required>
                                    <option value="">Select a project</option>
                                    <?php
                                    $projects_result->data_seek(0); // Reset pointer
                                    while ($project = $projects_result->fetch_assoc()): 
                                    ?>
                                    <option value="<?php echo $project['id']; ?>">
                                        <?php echo html_escape($project['name']); ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="version" class="form-label">Version *</label>
                                <input type="text" class="form-control" id="version" name="version" 
                                       placeholder="e.g., v1.0.0, 2.5.1" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="release_date" class="form-label">Release Date</label>
                                <input type="date" class="form-control" id="release_date" name="release_date">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="progress" class="form-label">Progress (%)</label>
                                <input type="number" class="form-control" id="progress" name="progress" 
                                       min="0" max="100" value="0" required>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label for="notes" class="form-label">Release Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="4" 
                                          placeholder="What's new in this release?"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn text-white" style="background-color: #9b59b6;">Save Release</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        // Reset form to create mode
        function resetForm() {
            document.getElementById('modalTitle').textContent = 'Plan New Release';
            document.getElementById('formAction').value = 'create';
            document.getElementById('releaseId').value = '';
            document.querySelector('#releaseModal form').reset();
        }

        // Populate form for editing
        function editRelease(release) {
            document.getElementById('modalTitle').textContent = 'Edit Release';
            document.getElementById('formAction').value = 'update';
            document.getElementById('releaseId').value = release.id;
            document.getElementById('project_id').value = release.project_id;
            document.getElementById('version').value = release.version;
            document.getElementById('release_date').value = release.release_date;
            document.getElementById('notes').value = release.notes;
            document.getElementById('progress').value = release.progress;
        }

        // Auto-open modal if edit parameter is present
        <?php if ($edit_release): ?>
        window.addEventListener('DOMContentLoaded', function() {
            editRelease(<?php echo json_encode($edit_release); ?>);
            new bootstrap.Modal(document.getElementById('releaseModal')).show();
        });
        <?php endif; ?>
    </script>
</body>
</html>
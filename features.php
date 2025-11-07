<?php
/**
 * Features Management Page
 * 
 * Full CRUD operations for managing features/user stories
 * Includes: List view, Add, Edit, and Delete functionality
 */

require_once __DIR__ . '/db.php';

// Handle form submissions
$message = '';
$message_type = '';

// DELETE operation
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM features WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $message = "Feature deleted successfully!";
        $message_type = "success";
    } else {
        $message = "Error deleting feature: " . $conn->error;
        $message_type = "danger";
    }
    $stmt->close();
}

// CREATE operation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'create') {
    $project_id = (int)$_POST['project_id'];
    $title = trim($_POST['title']);
    $status = $_POST['status'];
    
    $stmt = $conn->prepare("INSERT INTO features (project_id, title, status) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $project_id, $title, $status);
    
    if ($stmt->execute()) {
        $message = "Feature created successfully!";
        $message_type = "success";
    } else {
        $message = "Error creating feature: " . $conn->error;
        $message_type = "danger";
    }
    $stmt->close();
}

// UPDATE operation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update') {
    $id = (int)$_POST['id'];
    $project_id = (int)$_POST['project_id'];
    $title = trim($_POST['title']);
    $status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE features SET project_id=?, title=?, status=? WHERE id=?");
    $stmt->bind_param("issi", $project_id, $title, $status, $id);
    
    if ($stmt->execute()) {
        $message = "Feature updated successfully!";
        $message_type = "success";
    } else {
        $message = "Error updating feature: " . $conn->error;
        $message_type = "danger";
    }
    $stmt->close();
}

// Fetch all features with project names
$features_query = "SELECT f.*, p.name as project_name FROM features f 
                   LEFT JOIN projects p ON f.project_id = p.id 
                   ORDER BY f.created_at DESC";
$features_result = $conn->query($features_query);

// Fetch all projects for dropdown
$projects_result = $conn->query("SELECT id, name FROM projects ORDER BY name");

// Fetch feature for editing if edit parameter is set
$edit_feature = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM features WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_feature = $result->fetch_assoc();
    $stmt->close();
}

// Get feature statistics
$status_stats = [];
$stats_result = $conn->query("SELECT status, COUNT(*) as count FROM features GROUP BY status");
while ($row = $stats_result->fetch_assoc()) {
    $status_stats[$row['status']] = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Features - SDLC Project Tracker</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
    <!-- <style>
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
            border-bottom: 3px solid #2ecc71;
        }
        .stat-mini {
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 10px;
        }
    </style> -->
</head>
<body>
    <div class="container-fluid">
        <?php include 'sidebar.php'; ?>

            <!-- Main Content -->
            <div class="main-content">
                <!-- Page Header -->
                <div class="page-header">
                        <div>
                            <h2 class="mb-0">✨ Features Backlog</h2>
                            <p class="text-muted mb-0">Manage all feature requests and user stories</p>
                        </div>
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#featureModal" onclick="resetForm()">
                            ➕ Add New Feature
                        </button>
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
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card stat-card projects">
                            <div class="card-body">
                            <strong>To Do:</strong> <?php echo $status_stats['To Do'] ?? 0; ?> features
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card stat-card projects">
                            <div class="card-body bg-warning text-dark">
                            <strong>In Progress:</strong> <?php echo $status_stats['In Progress'] ?? 0; ?> features
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card stat-card projects">
                            <div class="card-body bg-success text-white">
                            <strong>Done:</strong> <?php echo $status_stats['Done'] ?? 0; ?> features
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Features Table -->
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">All Features</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Feature Title</th>
                                        <th>Project</th>
                                        <th>Status</th>
                                        <th>Created Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($features_result->num_rows > 0): ?>
                                        <?php while ($feature = $features_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $feature['id']; ?></td>
                                            <td><strong><?php echo html_escape($feature['title']); ?></strong></td>
                                            <td>
                                                <a href="project_detail.php?id=<?php echo $feature['project_id']; ?>" class="text-decoration-none">
                                                    <?php echo html_escape($feature['project_name']); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo get_badge_class($feature['status'], 'feature'); ?>">
                                                    <?php echo $feature['status']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo format_date($feature['created_at'], 'M d, Y g:i A'); ?></td>
                                            <td>
                                                <a href="?edit=<?php echo $feature['id']; ?>" 
                                                   class="btn btn-sm btn-warning" 
                                                   data-bs-toggle="modal" 
                                                   data-bs-target="#featureModal"
                                                   onclick="editFeature(<?php echo htmlspecialchars(json_encode($feature)); ?>)"
                                                   title="Edit">✏️</a>
                                                <a href="?delete=<?php echo $feature['id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this feature?')"
                                                   title="Delete">🗑️</a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4">
                                                <p class="text-muted mb-0">No features found. Click "Add New Feature" to get started!</p>
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

    <!-- Feature Modal (Create/Edit) -->
    <div class="modal fade" id="featureModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="modalTitle">Add New Feature</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="formAction" value="create">
                        <input type="hidden" name="id" id="featureId">
                        
                        <div class="mb-3">
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
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">Feature Title *</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Status *</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="To Do">To Do</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Done">Done</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Save Feature</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        // Reset form to create mode
        function resetForm() {
            document.getElementById('modalTitle').textContent = 'Add New Feature';
            document.getElementById('formAction').value = 'create';
            document.getElementById('featureId').value = '';
            document.querySelector('#featureModal form').reset();
        }

        // Populate form for editing
        function editFeature(feature) {
            document.getElementById('modalTitle').textContent = 'Edit Feature';
            document.getElementById('formAction').value = 'update';
            document.getElementById('featureId').value = feature.id;
            document.getElementById('project_id').value = feature.project_id;
            document.getElementById('title').value = feature.title;
            document.getElementById('status').value = feature.status;
        }

        // Auto-open modal if edit parameter is present
        <?php if ($edit_feature): ?>
        window.addEventListener('DOMContentLoaded', function() {
            editFeature(<?php echo json_encode($edit_feature); ?>);
            new bootstrap.Modal(document.getElementById('featureModal')).show();
        });
        <?php endif; ?>
    </script>
</body>
</html>
<?php
/**
 * Projects Management Page
 * 
 * Full CRUD operations for managing projects
 * Includes: List view, Add, Edit, and Delete functionality
 */

require_once __DIR__ . '/db.php';

// Handle form submissions
$message = '';
$message_type = '';

// DELETE operation
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM projects WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $message = "Project deleted successfully!";
        $message_type = "success";
    } else {
        $message = "Error deleting project: " . $conn->error;
        $message_type = "danger";
    }
    $stmt->close();
}

// CREATE operation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'create') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $phase = $_POST['phase'];
    $progress = (int)$_POST['progress'];
    $status = $_POST['status'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    $stmt = $conn->prepare("INSERT INTO projects (name, description, phase, progress, status, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssisss", $name, $description, $phase, $progress, $status, $start_date, $end_date);
    
    if ($stmt->execute()) {
        $message = "Project created successfully!";
        $message_type = "success";
    } else {
        $message = "Error creating project: " . $conn->error;
        $message_type = "danger";
    }
    $stmt->close();
}

// UPDATE operation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update') {
    $id = (int)$_POST['id'];
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $phase = $_POST['phase'];
    $progress = (int)$_POST['progress'];
    $status = $_POST['status'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    $stmt = $conn->prepare("UPDATE projects SET name=?, description=?, phase=?, progress=?, status=?, start_date=?, end_date=? WHERE id=?");
    $stmt->bind_param("sssisssi", $name, $description, $phase, $progress, $status, $start_date, $end_date, $id);
    
    if ($stmt->execute()) {
        $message = "Project updated successfully!";
        $message_type = "success";
    } else {
        $message = "Error updating project: " . $conn->error;
        $message_type = "danger";
    }
    $stmt->close();
}

// Fetch all projects
$projects_query = "SELECT * FROM projects ORDER BY created_at DESC";
$projects_result = $conn->query($projects_query);

// Fetch project for editing if edit parameter is set
$edit_project = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_project = $result->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projects - SDLC Project Tracker</title>
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
            border-bottom: 3px solid #3498db;
        }
        .progress {
            height: 25px;
        }
        .table-actions a {
            margin-right: 5px;
        }
    </style> -->
</head>
<body>
    <div class="container-fluid">
        
            <?php include 'sidebar.php'; ?>

            <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header ">
            <div>
                <h4>Projects Management 📁</h4>
                <p class="subtitle">Manage all your software development projects</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#projectModal" onclick="resetForm()">
                ➕ Add New Project
            </button>
        </div>

                <!-- Alert Messages -->
                <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo html_escape($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Projects Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">All Projects</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Project Name</th>
                                        <th>Phase</th>
                                        <th>Status</th>
                                        <th>Progress</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($projects_result->num_rows > 0): ?>
                                        <?php while ($project = $projects_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $project['id']; ?></td>
                                            <td>
                                                <strong><?php echo html_escape($project['name']); ?></strong><br>
                                                <small class="text-muted">
                                                    <?php echo substr(html_escape($project['description']), 0, 50) . '...'; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo get_badge_class($project['phase'], 'phase'); ?>">
                                                    <?php echo $project['phase']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo get_badge_class($project['status'], 'project'); ?>">
                                                    <?php echo $project['status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="progress">
                                                    <div class="progress-bar bg-success" role="progressbar" 
                                                         style="width: <?php echo $project['progress']; ?>%"
                                                         aria-valuenow="<?php echo $project['progress']; ?>" 
                                                         aria-valuemin="0" aria-valuemax="100">
                                                        <?php echo $project['progress']; ?>%
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo format_date($project['start_date']); ?></td>
                                            <td><?php echo format_date($project['end_date']); ?></td>
                                            <td class="table-actions">
                                                <a href="project_detail.php?id=<?php echo $project['id']; ?>" 
                                                   class="btn btn-sm btn-info" title="View Details">👁️</a>
                                                <a href="?edit=<?php echo $project['id']; ?>" 
                                                   class="btn btn-sm btn-warning" 
                                                   data-bs-toggle="modal" 
                                                   data-bs-target="#projectModal"
                                                   onclick="editProject(<?php echo htmlspecialchars(json_encode($project)); ?>)"
                                                   title="Edit">✏️</a>
                                                <a href="?delete=<?php echo $project['id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this project? This will also delete all associated features, bugs, and releases.')"
                                                   title="Delete">🗑️</a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4">
                                                <p class="text-muted mb-0">No projects found. Click "Add New Project" to get started!</p>
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

    <!-- Project Modal (Create/Edit) -->
    <div class="modal fade" id="projectModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">Add New Project</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="formAction" value="create">
                        <input type="hidden" name="id" id="projectId">
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="name" class="form-label">Project Name *</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="phase" class="form-label">SDLC Phase *</label>
                                <select class="form-select" id="phase" name="phase" required>
                                    <option value="Requirements">Requirements</option>
                                    <option value="Design">Design</option>
                                    <option value="Development">Development</option>
                                    <option value="Testing">Testing</option>
                                    <option value="Deployment">Deployment</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status *</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="Active">Active</option>
                                    <option value="On Hold">On Hold</option>
                                    <option value="Completed">Completed</option>
                                </select>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="progress" class="form-label">Progress (%)</label>
                                <input type="number" class="form-control" id="progress" name="progress" 
                                       min="0" max="100" value="0" required>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Project</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        // Reset form to create mode
        function resetForm() {
            document.getElementById('modalTitle').textContent = 'Add New Project';
            document.getElementById('formAction').value = 'create';
            document.getElementById('projectId').value = '';
            document.querySelector('#projectModal form').reset();
        }

        // Populate form for editing
        function editProject(project) {
            document.getElementById('modalTitle').textContent = 'Edit Project';
            document.getElementById('formAction').value = 'update';
            document.getElementById('projectId').value = project.id;
            document.getElementById('name').value = project.name;
            document.getElementById('description').value = project.description;
            document.getElementById('phase').value = project.phase;
            document.getElementById('status').value = project.status;
            document.getElementById('progress').value = project.progress;
            document.getElementById('start_date').value = project.start_date;
            document.getElementById('end_date').value = project.end_date;
        }

        // Auto-open modal if edit parameter is present
        <?php if ($edit_project): ?>
        window.addEventListener('DOMContentLoaded', function() {
            editProject(<?php echo json_encode($edit_project); ?>);
            new bootstrap.Modal(document.getElementById('projectModal')).show();
        });
        <?php endif; ?>
    </script>
</body>
</html>
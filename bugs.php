<?php
/**
 * Bugs Management Page
 * 
 * Full CRUD operations for managing bugs and issues
 * Includes: List view, Add, Edit, and Delete functionality
 */

require_once __DIR__ . '/db.php';

// Handle form submissions
$message = '';
$message_type = '';

// DELETE operation
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM bugs WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $message = "Bug deleted successfully!";
        $message_type = "success";
    } else {
        $message = "Error deleting bug: " . $conn->error;
        $message_type = "danger";
    }
    $stmt->close();
}

// CREATE operation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'create') {
    $project_id = (int)$_POST['project_id'];
    $title = trim($_POST['title']);
    $severity = $_POST['severity'];
    $status = $_POST['status'];
    
    $stmt = $conn->prepare("INSERT INTO bugs (project_id, title, severity, status) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $project_id, $title, $severity, $status);
    
    if ($stmt->execute()) {
        $message = "Bug created successfully!";
        $message_type = "success";
    } else {
        $message = "Error creating bug: " . $conn->error;
        $message_type = "danger";
    }
    $stmt->close();
}

// UPDATE operation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update') {
    $id = (int)$_POST['id'];
    $project_id = (int)$_POST['project_id'];
    $title = trim($_POST['title']);
    $severity = $_POST['severity'];
    $status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE bugs SET project_id=?, title=?, severity=?, status=? WHERE id=?");
    $stmt->bind_param("isssi", $project_id, $title, $severity, $status, $id);
    
    if ($stmt->execute()) {
        $message = "Bug updated successfully!";
        $message_type = "success";
    } else {
        $message = "Error updating bug: " . $conn->error;
        $message_type = "danger";
    }
    $stmt->close();
}

// Fetch all bugs with project names
$bugs_query = "SELECT b.*, p.name as project_name FROM bugs b 
               LEFT JOIN projects p ON b.project_id = p.id 
               ORDER BY 
                   FIELD(b.severity, 'Critical', 'High', 'Medium', 'Low'),
                   FIELD(b.status, 'Open', 'In Review', 'Closed'),
                   b.created_at DESC";
$bugs_result = $conn->query($bugs_query);

// Fetch all projects for dropdown
$projects_result = $conn->query("SELECT id, name FROM projects ORDER BY name");

// Fetch bug for editing if edit parameter is set
$edit_bug = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM bugs WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_bug = $result->fetch_assoc();
    $stmt->close();
}

// Get bug statistics
$severity_stats = [];
$severity_result = $conn->query("SELECT severity, COUNT(*) as count FROM bugs GROUP BY severity");
while ($row = $severity_result->fetch_assoc()) {
    $severity_stats[$row['severity']] = $row['count'];
}

$status_stats = [];
$status_result = $conn->query("SELECT status, COUNT(*) as count FROM bugs GROUP BY status");
while ($row = $status_result->fetch_assoc()) {
    $status_stats[$row['status']] = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bugs - SDLC Project Tracker</title>
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
            border-bottom: 3px solid #e74c3c;
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
                            <h2 class="mb-0">🐛 Bug Tracker</h2>
                            <p class="text-muted mb-0">Track and manage all reported bugs and issues</p>
                        </div>
                        <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#bugModal" onclick="resetForm()">
                            ➕ Report New Bug
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
                    <div class="col-10 col-sm-6 col-xl-3">
                        <div class="card stat-card projects">
                            <div class="card-body">
                            <strong>Low:</strong> <?php echo $severity_stats['Low'] ?? 0; ?> bugs
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card stat-card projects">
                            <div class="card-body bg-warning text-dark rounded">
                            <strong>Medium:</strong> <?php echo $severity_stats['Medium'] ?? 0; ?> bugs
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card stat-card projects">
                            <div class="card-body bg-danger text-white rounded">
                            <strong>High:</strong> <?php echo $severity_stats['High'] ?? 0; ?> bugs
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card stat-card projects">
                            <div class="card-body bg-dark text-white rounded">
                            <strong>Critical:</strong> <?php echo $severity_stats['Critical'] ?? 0; ?> bugs
                            </div>
                        </div>
                    </div>
                

                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card stat-card projects">
                            <div class="card-body bg-danger text-white rounded">
                                <strong>Open:</strong> <?php echo $status_stats['Open'] ?? 0; ?> bugs
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card stat-card projects">
                            <div class="card-body bg-warning text-dark rounded">
                            <strong>In Review:</strong> <?php echo $status_stats['In Review'] ?? 0; ?> bugs
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card stat-card projects">
                            <div class="card-body bg-success text-white rounded">
                            <strong>Closed:</strong> <?php echo $status_stats['Closed'] ?? 0; ?> bugs
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bugs Table -->
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0">All Bugs</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Bug Title</th>
                                        <th>Project</th>
                                        <th>Severity</th>
                                        <th>Status</th>
                                        <th>Created Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($bugs_result->num_rows > 0): ?>
                                        <?php while ($bug = $bugs_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $bug['id']; ?></td>
                                            <td><strong><?php echo html_escape($bug['title']); ?></strong></td>
                                            <td>
                                                <a href="project_detail.php?id=<?php echo $bug['project_id']; ?>" class="text-decoration-none">
                                                    <?php echo html_escape($bug['project_name']); ?>
                                                </a>
                                            </td>
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
                                            <td>
                                                <a href="?edit=<?php echo $bug['id']; ?>" 
                                                   class="btn btn-sm btn-warning" 
                                                   data-bs-toggle="modal" 
                                                   data-bs-target="#bugModal"
                                                   onclick="editBug(<?php echo htmlspecialchars(json_encode($bug)); ?>)"
                                                   title="Edit">✏️</a>
                                                <a href="?delete=<?php echo $bug['id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this bug?')"
                                                   title="Delete">🗑️</a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4">
                                                <p class="text-muted mb-0">No bugs found. Click "Report New Bug" to get started!</p>
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

    <!-- Bug Modal (Create/Edit) -->
    <div class="modal fade" id="bugModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="modalTitle">Report New Bug</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="formAction" value="create">
                        <input type="hidden" name="id" id="bugId">
                        
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
                            <label for="title" class="form-label">Bug Title *</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="severity" class="form-label">Severity *</label>
                            <select class="form-select" id="severity" name="severity" required>
                                <option value="Low">Low</option>
                                <option value="Medium" selected>Medium</option>
                                <option value="High">High</option>
                                <option value="Critical">Critical</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Status *</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="Open">Open</option>
                                <option value="In Review">In Review</option>
                                <option value="Closed">Closed</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Save Bug</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        // Reset form to create mode
        function resetForm() {
            document.getElementById('modalTitle').textContent = 'Report New Bug';
            document.getElementById('formAction').value = 'create';
            document.getElementById('bugId').value = '';
            document.querySelector('#bugModal form').reset();
        }

        // Populate form for editing
        function editBug(bug) {
            document.getElementById('modalTitle').textContent = 'Edit Bug';
            document.getElementById('formAction').value = 'update';
            document.getElementById('bugId').value = bug.id;
            document.getElementById('project_id').value = bug.project_id;
            document.getElementById('title').value = bug.title;
            document.getElementById('severity').value = bug.severity;
            document.getElementById('status').value = bug.status;
        }

        // Auto-open modal if edit parameter is present
        <?php if ($edit_bug): ?>
        window.addEventListener('DOMContentLoaded', function() {
            editBug(<?php echo json_encode($edit_bug); ?>);
            new bootstrap.Modal(document.getElementById('bugModal')).show();
        });
        <?php endif; ?>
    </script>
</body>
</html>
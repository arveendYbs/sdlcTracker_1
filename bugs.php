<?php
/**
 * Bugs Management Page
 * 
 * Full CRUD operations for managing bugs and issues
 * Includes: List view, Add, Edit, and Delete functionality
 */

require_once 'db.php';

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
    $resolution_remarks = isset($_POST['resolution_remarks']) ? trim($_POST['resolution_remarks']) : null;
    $resolution_code = isset($_POST['resolution_code']) ? trim($_POST['resolution_code']) : null;
    
    // If status is being changed to Closed, set resolved_at timestamp
    $resolved_at = null;
    if ($status == 'Closed') {
        $resolved_at = date('Y-m-d H:i:s');
    }
    
    if ($resolved_at) {
        $stmt = $conn->prepare("UPDATE bugs SET project_id=?, title=?, severity=?, status=?, resolution_remarks=?, resolution_code=?, resolved_at=? WHERE id=?");
        $stmt->bind_param("issssssi", $project_id, $title, $severity, $status, $resolution_remarks, $resolution_code, $resolved_at, $id);
    } else {
        $stmt = $conn->prepare("UPDATE bugs SET project_id=?, title=?, severity=?, status=?, resolution_remarks=?, resolution_code=? WHERE id=?");
        $stmt->bind_param("isssssi", $project_id, $title, $severity, $status, $resolution_remarks, $resolution_code, $id);
    }
    
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
    <link href="css/style.css" rel="stylesheet">
    <!-- <style>
        /* Resolution Feature Styles */
        .has-resolution {
            background: linear-gradient(90deg, rgba(3, 195, 236, 0.05) 0%, transparent 10%);
        }
        
        .has-resolution:hover {
            background: linear-gradient(90deg, rgba(3, 195, 236, 0.12) 0%, rgba(3, 195, 236, 0.05) 100%) !important;
            transition: all 0.2s;
        }
        
        tr.has-resolution td:first-child::before {
            content: "💡";
            position: absolute;
            left: 5px;
            font-size: 0.8rem;
        }
        
        /* Code display styling */
        #resolutionCodeContent {
            font-family: 'Courier New', Courier, monospace;
        }
        
        /* Resolution modal styling */
        #resolutionModal .card {
            border: 1px solid #dee2e6;
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
        }
        
        #resolutionModal .card-header {
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            font-weight: 600;
        }
        
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
            <!-- Sidebar -->
            <?php include 'sidebar.php'; ?>

            <!-- Main Content -->
            <div class="main-content">
                <!-- Page Header -->
                <div class="page-header">
                        <div>
                            <h4 class="mb-0">🐛 Bug Tracker</h4>
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
                    <div class="col-12 col-sm-6 col-xl-3">
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
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card stat-card projects">
                            <div class="card-body">
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
                                        <tr class="<?php echo ($bug['resolution_remarks'] || $bug['resolution_code']) ? 'has-resolution' : ''; ?>" 
                                            style="<?php echo ($bug['resolution_remarks'] || $bug['resolution_code']) ? 'cursor: pointer;' : ''; ?>"
                                            <?php if ($bug['resolution_remarks'] || $bug['resolution_code']): ?>
                                            onclick="viewBugResolution(<?php echo htmlspecialchars(json_encode($bug)); ?>)"
                                            title="Click to view resolution details"
                                            <?php endif; ?>>
                                            <td><?php echo $bug['id']; ?></td>
                                            <td>
                                                <strong><?php echo html_escape($bug['title']); ?></strong>
                                                <?php if ($bug['resolution_remarks'] || $bug['resolution_code']): ?>
                                                <span class="badge bg-label-info ms-2" style="font-size: 0.7rem;">📄 Has Resolution</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="project_detail.php?id=<?php echo $bug['project_id']; ?>" 
                                                   class="text-decoration-none"
                                                   onclick="event.stopPropagation();">
                                                    <?php echo html_escape($bug['project_name']); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <span class="badge bg-label-<?php echo get_badge_class($bug['severity'], 'severity'); ?>">
                                                    <?php echo $bug['severity']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-label-<?php echo get_badge_class($bug['status'], 'bug'); ?>">
                                                    <?php echo $bug['status']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo format_date($bug['created_at'], 'M d, Y g:i A'); ?></td>
                                            <td onclick="event.stopPropagation();">
                                                <?php if ($bug['resolution_remarks'] || $bug['resolution_code']): ?>
                                                <button class="btn btn-sm btn-info" 
                                                        onclick="viewBugResolution(<?php echo htmlspecialchars(json_encode($bug)); ?>)"
                                                        title="View Resolution">👁️</button>
                                                <?php endif; ?>
                                                <a href="?edit=<?php echo $bug['id']; ?>" 
                                                   class="btn btn-sm btn-warning" 
                                                   data-bs-toggle="modal" 
                                                   data-bs-target="#bugModal"
                                                   onclick="editBug(<?php echo htmlspecialchars(json_encode($bug)); ?>); event.stopPropagation();"
                                                   title="Edit">✏️</a>
                                                <a href="?delete=<?php echo $bug['id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="if(confirm('Are you sure you want to delete this bug?')) return true; else { event.stopPropagation(); return false; }"
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
                            <select class="form-select" id="status" name="status" required onchange="toggleResolutionFields()">
                                <option value="Open">Open</option>
                                <option value="In Review">In Review</option>
                                <option value="Closed">Closed</option>
                            </select>
                        </div>
                        
                        <!-- Resolution Fields (shown when status is Closed) -->
                        <div id="resolutionFields" style="display: none;">
                            <div class="alert alert-info">
                                <strong>📝 Bug Resolution</strong><br>
                                Please provide resolution details for this closed bug.
                            </div>
                            
                            <div class="mb-3">
                                <label for="resolution_remarks" class="form-label">Resolution Remarks</label>
                                <textarea class="form-control" id="resolution_remarks" name="resolution_remarks" rows="3" 
                                          placeholder="Describe how the bug was resolved..."></textarea>
                                <small class="text-muted">Brief description of the fix or resolution</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="resolution_code" class="form-label">Resolution Code/Script (Optional)</label>
                                <textarea class="form-control" id="resolution_code" name="resolution_code" rows="5" 
                                          style="font-family: monospace; font-size: 0.875rem;"
                                          placeholder="Paste the code fix here...&#10;function fixBug() {&#10;    // Your code here&#10;}"></textarea>
                                <small class="text-muted">Paste the code snippet or SQL script that fixed the bug</small>
                            </div>
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

    <!-- Resolution View Modal -->
    <div class="modal fade" id="resolutionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">🐛 Bug Resolution Details</h5>
                        <p class="mb-0">
                            <span id="resolutionBugId" class="text-muted"></span> - 
                            <span id="resolutionBugTitle" class="fw-semibold"></span>
                        </p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Bug Info -->
                    <div class="d-flex gap-3 mb-4">
                        <div>
                            <strong>Status:</strong> 
                            <span id="resolutionStatus" class="badge"></span>
                        </div>
                        <div>
                            <strong>Severity:</strong> 
                            <span id="resolutionSeverity" class="badge"></span>
                        </div>
                    </div>
                    
                    <!-- Resolved Date -->
                    <div id="resolvedAtSection" class="mb-4">
                        <div class="alert alert-success">
                            <strong>✓ Resolved:</strong> <span id="resolvedAtContent"></span>
                        </div>
                    </div>
                    
                    <!-- Resolution Remarks -->
                    <div id="resolutionRemarksSection" class="mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">📝 Resolution Remarks</h6>
                            </div>
                            <div class="card-body">
                                <p id="resolutionRemarksContent" class="mb-0" style="white-space: pre-wrap;"></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Resolution Code -->
                    <div id="resolutionCodeSection">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">💻 Resolution Code</h6>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="copyCode()">
                                    📋 Copy Code
                                </button>
                            </div>
                            <div class="card-body">
                                <pre id="resolutionCodeContent" style="background: #f5f5f5; padding: 1rem; border-radius: 0.375rem; max-height: 400px; overflow-y: auto; margin: 0; font-size: 0.875rem;"><code></code></pre>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle resolution fields based on status
        function toggleResolutionFields() {
            const status = document.getElementById('status').value;
            const resolutionFields = document.getElementById('resolutionFields');
            const remarksField = document.getElementById('resolution_remarks');
            const codeField = document.getElementById('resolution_code');
            
            if (status === 'Closed') {
                resolutionFields.style.display = 'block';
                remarksField.required = false; // Optional but encouraged
            } else {
                resolutionFields.style.display = 'none';
                remarksField.required = false;
                codeField.required = false;
            }
        }
        
        // Reset form to create mode
        function resetForm() {
            document.getElementById('modalTitle').textContent = 'Report New Bug';
            document.getElementById('formAction').value = 'create';
            document.getElementById('bugId').value = '';
            document.querySelector('#bugModal form').reset();
            document.getElementById('resolutionFields').style.display = 'none';
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
            document.getElementById('resolution_remarks').value = bug.resolution_remarks || '';
            document.getElementById('resolution_code').value = bug.resolution_code || '';
            
            // Show/hide resolution fields based on status
            toggleResolutionFields();
        }
        
        // View bug resolution details
        function viewBugResolution(bug) {
            const modal = new bootstrap.Modal(document.getElementById('resolutionModal'));
            document.getElementById('resolutionBugTitle').textContent = bug.title;
            document.getElementById('resolutionBugId').textContent = '#' + bug.id;
            document.getElementById('resolutionStatus').textContent = bug.status;
            document.getElementById('resolutionStatus').className = 'badge bg-label-' + getBugStatusBadge(bug.status);
            document.getElementById('resolutionSeverity').textContent = bug.severity;
            document.getElementById('resolutionSeverity').className = 'badge bg-label-' + getSeverityBadge(bug.severity);
            
            if (bug.resolution_remarks) {
                document.getElementById('resolutionRemarksContent').textContent = bug.resolution_remarks;
                document.getElementById('resolutionRemarksSection').style.display = 'block';
            } else {
                document.getElementById('resolutionRemarksSection').style.display = 'none';
            }
            
            if (bug.resolution_code) {
                document.getElementById('resolutionCodeContent').textContent = bug.resolution_code;
                document.getElementById('resolutionCodeSection').style.display = 'block';
            } else {
                document.getElementById('resolutionCodeSection').style.display = 'none';
            }
            
            if (bug.resolved_at) {
                document.getElementById('resolvedAtContent').textContent = formatDate(bug.resolved_at);
                document.getElementById('resolvedAtSection').style.display = 'block';
            } else {
                document.getElementById('resolvedAtSection').style.display = 'none';
            }
            
            modal.show();
        }
        
        // Helper function to get badge class for bug status
        function getBugStatusBadge(status) {
            const badges = {
                'Open': 'danger',
                'In Review': 'warning',
                'Closed': 'success'
            };
            return badges[status] || 'secondary';
        }
        
        // Helper function to get badge class for severity
        function getSeverityBadge(severity) {
            const badges = {
                'Low': 'info',
                'Medium': 'warning',
                'High': 'danger',
                'Critical': 'dark'
            };
            return badges[severity] || 'secondary';
        }
        
        // Format date for display
        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        // Copy code to clipboard
        function copyCode() {
            const code = document.getElementById('resolutionCodeContent').textContent;
            navigator.clipboard.writeText(code).then(function() {
                const btn = document.querySelector('[onclick="copyCode()"]');
                const originalText = btn.innerHTML;
                btn.innerHTML = '✓ Copied!';
                btn.classList.add('btn-success');
                btn.classList.remove('btn-secondary');
                setTimeout(function() {
                    btn.innerHTML = originalText;
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-secondary');
                }, 2000);
            });
        }

        <?php if ($edit_bug): ?>
        window.addEventListener('DOMContentLoaded', function() {
            editBug(<?php echo json_encode($edit_bug); ?>);
            new bootstrap.Modal(document.getElementById('bugModal')).show();
        });
        <?php endif; ?>
    </script>
</body>
</html>
<?php
/**
 * Database Connection File
 * 
 * This file handles the MySQL database connection for the SDLC Project Tracker.
 * It uses mysqli with proper error handling and UTF-8 character encoding.
 * 
 * @author Full-Stack Developer
 * @version 1.0
 */

// Database configuration constants
define('DB_HOST', 'localhost');      // Database host
define('DB_USER', 'root');           // Database username (adjust as needed)
define('DB_PASS', '');               // Database password (adjust as needed)
define('DB_NAME', 'sdlc_tracker');        // Database name
define('DB_CHARSET', 'utf8mb4');     // Character encoding
/** @var mysqli $conn */

// Initialize connection variable
$conn = null;

/**
 * Establish database connection
 */
try {
    // Create new mysqli connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check for connection errors
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set character encoding to UTF-8
    if (!$conn->set_charset(DB_CHARSET)) {
        throw new Exception("Error setting charset: " . $conn->error);
    }
    
    // Connection successful - uncomment below for debugging
    // echo "Database connected successfully";
    
} catch (Exception $e) {
    // Log error and display user-friendly message
    error_log("Database Connection Error: " . $e->getMessage());
    
    // Display error page
    /* die("
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Database Connection Error</title>
        <link href='css/bootstrap.min.css' rel='stylesheet'>
    </head>
    <body class='bg-light'>
        <div class='container mt-5'>
            <div class='row justify-content-center'>
                <div class='col-md-6'>
                    <div class='card border-danger'>
                        <div class='card-header bg-danger text-white'>
                            <h5 class='mb-0'><i class='bi bi-exclamation-triangle'></i> Database Connection Error</h5>
                        </div>
                        <div class='card-body'>
                            <p class='mb-2'><strong>Unable to connect to the database.</strong></p>
                            <p class='mb-3'>Please check the following:</p>
                            <ul>
                                <li>MySQL server is running</li>
                                <li>Database 'sdlc_db' exists (run database.sql)</li>
                                <li>Database credentials in db.php are correct</li>
                                <li>Database user has proper permissions</li>
                            </ul>
                            <hr>
                            <p class='text-muted small mb-0'><strong>Technical Details:</strong><br>" . htmlspecialchars($e->getMessage()) . "</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    "); */
}

/**
 * Helper function to execute prepared statements safely
 * 
 * @param mysqli $conn Database connection
 * @param string $sql SQL query with placeholders
 * @param string $types Types of parameters (s=string, i=integer, d=double, b=blob)
 * @param array $params Array of parameters
 * @return mysqli_result|bool Result set or boolean
 */
function execute_query($conn, $sql, $types = "", $params = []) {
    try {
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        // Bind parameters if provided
        if (!empty($types) && !empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        // Execute statement
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        // Get result for SELECT queries
        $result = $stmt->get_result();
        
        $stmt->close();
        
        return $result !== false ? $result : true;
        
    } catch (Exception $e) {
        error_log("Query Execution Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Helper function to sanitize output for HTML display
 * 
 * @param string $string The string to sanitize
 * @return string Sanitized string
 */
function html_escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Helper function to format dates
 * 
 * @param string $date Date string
 * @param string $format Desired format (default: 'M d, Y')
 * @return string Formatted date or empty string
 */
function format_date($date, $format = 'M d, Y') {
    if (empty($date) || $date == '0000-00-00') {
        return '';
    }
    $timestamp = strtotime($date);
    return $timestamp ? date($format, $timestamp) : '';
}

/**
 * Helper function to get badge color based on status
 * 
 * @param string $status Status value
 * @param string $type Type of status (project, feature, bug, severity)
 * @return string Bootstrap badge class
 */
function get_badge_class($status, $type = 'project') {
    $badges = [
        'project' => [
            'Active' => 'success',
            'On Hold' => 'warning',
            'Completed' => 'info'
        ],
        'phase' => [
            'Requirements' => 'secondary',
            'Design' => 'info',
            'Development' => 'primary',
            'Testing' => 'warning',
            'Deployment' => 'success'
        ],
        'feature' => [
            'To Do' => 'secondary',
            'In Progress' => 'warning',
            'Done' => 'success'
        ],
        'bug' => [
            'Open' => 'danger',
            'In Review' => 'warning',
            'Closed' => 'success'
        ],
        'severity' => [
            'Low' => 'info',
            'Medium' => 'warning',
            'High' => 'danger',
            'Critical' => 'dark'
        ]
    ];
    
    return $badges[$type][$status] ?? 'secondary';
}
?>
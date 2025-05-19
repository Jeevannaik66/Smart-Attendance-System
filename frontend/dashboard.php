<?php
session_start();

// Enhanced security checks
require('database/db_config.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("Location: admin_login.php");
    exit;
}

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

// Verify session consistency
if ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR'] || 
    $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
    session_destroy();
    header("Location: admin_login.php");
    exit;
}

// Session timeout (30 minutes)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header("Location: admin_login.php?timeout=1");
    exit;
}
$_SESSION['last_activity'] = time();

// Fetch admin details
$admin_id = $_SESSION['admin_id'];
$admin_username = $_SESSION['admin_username'];

// Fetch dashboard statistics
$stats = [
    'total_users' => 0,
    'total_attendance' => 0,
    'today_attendance' => 0,
    'active_users' => 0
];

// Prepared statements for security
$queries = [
    "SELECT COUNT(*) FROM users",
    "SELECT COUNT(*) FROM attendance",
    "SELECT COUNT(*) FROM attendance WHERE date = CURDATE()",
    "SELECT COUNT(DISTINCT user_id) FROM attendance WHERE date = CURDATE()"
];

foreach ($queries as $index => $query) {
    $result = $conn->query($query);
    if ($result) {
        $stats[array_keys($stats)[$index]] = $result->fetch_row()[0];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Smart Attendance System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary: #4e73df;
            --primary-dark: #2e59d9;
            --secondary: #1cc88a;
            --warning: #f6c23e;
            --danger: #e74a3b;
            --dark: #5a5c69;
            --light: #f8f9fc;
            --sidebar-width: 250px;
            --topbar-height: 4.375rem;
            --card-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        }
        
        body {
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 
                        'Helvetica Neue', Arial, sans-serif;
            background-color: var(--light);
            color: var(--dark);
            overflow-x: hidden;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background: linear-gradient(180deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding-top: var(--topbar-height);
            box-shadow: var(--card-shadow);
            z-index: 1000;
            transition: all 0.3s;
        }
        
        .sidebar-brand {
            height: var(--topbar-height);
            text-decoration: none;
            font-size: 1.2rem;
            font-weight: 800;
            padding: 1rem;
            text-align: center;
            letter-spacing: 0.05rem;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            background: rgba(0,0,0,0.1);
        }
        
        .sidebar-divider {
            border-top: 1px solid rgba(255, 255, 255, 0.15);
            margin: 1rem 0;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-left: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
            border-left: 3px solid white;
        }
        
        .sidebar .nav-link i {
            width: 20px;
            text-align: center;
            margin-right: 0.5rem;
        }
        
        /* Main Content Area */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            min-height: calc(100vh - var(--topbar-height));
            padding-top: calc(var(--topbar-height) + 20px);
        }
        
        /* Topbar Styles */
        .topbar {
            height: var(--topbar-height);
            box-shadow: var(--card-shadow);
            background-color: white;
            position: fixed;
            top: 0;
            right: 0;
            left: var(--sidebar-width);
            z-index: 999;
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        /* Card Styles */
        .card {
            border: none;
            border-radius: 0.5rem;
            box-shadow: var(--card-shadow);
            margin-bottom: 1.5rem;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1.5rem rgba(58, 59, 69, 0.2);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 1rem 1.5rem;
            font-weight: 700;
            border-radius: 0.5rem 0.5rem 0 0 !important;
        }
        
        /* Stat Cards */
        .stat-card {
            border-left: 0.25rem solid;
            transition: all 0.3s;
        }
        
        .stat-card.primary {
            border-left-color: var(--primary);
        }
        
        .stat-card.success {
            border-left-color: var(--secondary);
        }
        
        .stat-card.warning {
            border-left-color: var(--warning);
        }
        
        .stat-card.dark {
            border-left-color: var(--dark);
        }
        
        .stat-card .stat-icon {
            font-size: 2rem;
            opacity: 0.7;
        }
        
        .stat-card .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
        }
        
        .stat-card .stat-label {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
            color: var(--dark);
            opacity: 0.8;
        }
        
        /* Quick Actions */
        .quick-link {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            border-radius: 0.5rem;
            transition: all 0.3s;
        }
        
        .quick-link:hover {
            transform: scale(1.05);
        }
        
        .quick-link i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        /* Recent Activity */
        .activity-item {
            border-left: 3px solid transparent;
            transition: all 0.3s;
            padding: 0.75rem 1.25rem;
        }
        
        .activity-item:hover {
            background-color: rgba(0,0,0,0.03);
        }
        
        .activity-item.primary {
            border-left-color: var(--primary);
        }
        
        .activity-item.success {
            border-left-color: var(--secondary);
        }
        
        .activity-item.danger {
            border-left-color: var(--danger);
        }
        
        .activity-item i {
            font-size: 1.25rem;
            width: 30px;
        }
        
        /* Admin Profile */
        .admin-profile {
            display: flex;
            align-items: center;
        }
        
        .admin-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-left: 1rem;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content, .topbar {
                margin-left: 0;
                left: 0;
            }
            
            .sidebar-brand {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <a class="sidebar-brand" href="dashboard.php">
            <i class="bi bi-people-fill me-2"></i>
            <span>Smart Attendance</span>
        </a>
        
        <div class="sidebar-divider"></div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="dashboard.php">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="manage_users.php">
                    <i class="bi bi-people"></i>
                    <span>Manage Users</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="attendance.php">
                    <i class="bi bi-clipboard-data"></i>
                    <span>Attendance</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="live_attendance.php">
                    <i class="bi bi-camera-video"></i>
                    <span>Live Attendance</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="reports.php">
                    <i class="bi bi-graph-up"></i>
                    <span>Reports</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="settings.php">
                    <i class="bi bi-gear"></i>
                    <span>Settings</span>
                </a>
            </li>
            <li class="nav-item mt-3">
                <a class="nav-link text-danger" href="logout.php">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
        <nav class="topbar shadow">
            <h5 class="mb-0 fw-bold"><i class="bi bi-speedometer2 me-2"></i>Dashboard Overview</h5>
            <div class="admin-profile">
                <span class="text-muted">Welcome, <strong><?php echo htmlspecialchars($admin_username); ?></strong></span>
                <div class="admin-avatar">
                    <?php echo strtoupper(substr($admin_username, 0, 1)); ?>
                </div>
            </div>
        </nav>
        
        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
            <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                <i class="bi bi-download me-1"></i> Generate Report
            </a>
        </div>
        
        <!-- Stats Cards -->
        <div class="row">
            <!-- Total Users Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card primary h-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="stat-label">Total Users</div>
                                <div class="stat-value"><?php echo $stats['total_users']; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-people stat-icon text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Total Attendance Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card success h-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="stat-label">Total Attendance</div>
                                <div class="stat-value"><?php echo $stats['total_attendance']; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-clipboard-check stat-icon text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Today's Attendance Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card warning h-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="stat-label">Today's Attendance</div>
                                <div class="stat-value"><?php echo $stats['today_attendance']; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-calendar-day stat-icon text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Active Users Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card dark h-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="stat-label">Active Today</div>
                                <div class="stat-value"><?php echo $stats['active_users']; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-person-check stat-icon text-dark"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Content Row -->
        <div class="row">
            <!-- Quick Actions -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="bi bi-lightning me-2"></i>Quick Actions
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <a href="manage_users.php" class="btn btn-primary w-100 py-4 quick-link">
                                    <i class="bi bi-person-plus"></i>
                                    <div class="mt-2">Add User</div>
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="mark_attendance.php" class="btn btn-success w-100 py-4 quick-link">
                                    <i class="bi bi-camera"></i>
                                    <div class="mt-2">Mark Attendance</div>
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="live_attendance.php" class="btn btn-warning w-100 py-4 quick-link">
                                    <i class="bi bi-camera-video"></i>
                                    <div class="mt-2">Live Attendance</div>
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="reports.php" class="btn btn-info w-100 py-4 quick-link">
                                    <i class="bi bi-file-earmark-bar-graph"></i>
                                    <div class="mt-2">Generate Report</div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="bi bi-clock-history me-2"></i>Recent Activity
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <div class="list-group-item activity-item success">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-person-plus text-success me-3"></i>
                                    <div>
                                        <div class="fw-bold">New user registered</div>
                                        <small class="text-muted">5 minutes ago</small>
                                    </div>
                                </div>
                            </div>
                            <div class="list-group-item activity-item primary">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-camera-video text-primary me-3"></i>
                                    <div>
                                        <div class="fw-bold">Attendance marked via camera</div>
                                        <small class="text-muted">1 hour ago</small>
                                    </div>
                                </div>
                            </div>
                            <div class="list-group-item activity-item danger">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-file-earmark-pdf text-danger me-3"></i>
                                    <div>
                                        <div class="fw-bold">Monthly report generated</div>
                                        <small class="text-muted">3 hours ago</small>
                                    </div>
                                </div>
                            </div>
                            <div class="list-group-item activity-item primary">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-database text-primary me-3"></i>
                                    <div>
                                        <div class="fw-bold">Database backup completed</div>
                                        <small class="text-muted">Yesterday</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Additional Content Row -->
        <div class="row">
            <!-- System Status -->
            <div class="col-lg-12 mb-4">
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="bi bi-heart-pulse me-2"></i>System Status
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3 mb-3">
                                <div class="p-3 border rounded bg-light">
                                    <i class="bi bi-server fs-1 text-primary mb-2"></i>
                                    <h5 class="fw-bold">Database</h5>
                                    <span class="badge bg-success">Online</span>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="p-3 border rounded bg-light">
                                    <i class="bi bi-camera-video fs-1 text-primary mb-2"></i>
                                    <h5 class="fw-bold">Camera</h5>
                                    <span class="badge bg-success">Connected</span>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="p-3 border rounded bg-light">
                                    <i class="bi bi-shield-check fs-1 text-primary mb-2"></i>
                                    <h5 class="fw-bold">Security</h5>
                                    <span class="badge bg-success">Active</span>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="p-3 border rounded bg-light">
                                    <i class="bi bi-cloud fs-1 text-primary mb-2"></i>
                                    <h5 class="fw-bold">Backup</h5>
                                    <span class="badge bg-warning text-dark">Pending</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JavaScript -->
    <script>
        // Activate tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Toggle sidebar on mobile
            document.getElementById('sidebarToggle').addEventListener('click', function() {
                document.querySelector('.sidebar').classList.toggle('active');
            });
        });
    </script>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
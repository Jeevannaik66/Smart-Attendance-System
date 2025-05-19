<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("location: admin_login.php");
    exit;
}

// Include database configuration
require_once "database/db_config.php";

// Get admin username safely
$admin_username = $_SESSION['admin_username'] ?? 'Admin'; // Fallback to 'Admin' if not set

// Set default date filter (Today)
$date_filter = date('Y-m-d');

// Check if a specific date is provided via GET
if (isset($_GET['date']) && !empty($_GET['date'])) {
    // Validate the date format
    if (DateTime::createFromFormat('Y-m-d', $_GET['date']) !== false) {
        $date_filter = $_GET['date'];
    }
}

// Prepare and execute the query with parameterized statement
$sql = "SELECT attendance.id, users.name, attendance.date, attendance.time, attendance.status
        FROM attendance
        INNER JOIN users ON attendance.user_id = users.id
        WHERE attendance.date = ?
        ORDER BY attendance.time DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $date_filter);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Records - Smart Attendance System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom Styles -->
    <style>
        :root {
            --primary: #4e73df;
            --primary-dark: #2e59d9;
            --secondary: #1cc88a;
            --danger: #e74a3b;
            --warning: #f6c23e;
            --dark: #5a5c69;
            --light: #f8f9fc;
            --sidebar-width: 250px;
            --topbar-height: 4.375rem;
        }
        
        body {
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 
                        'Helvetica Neue', Arial, sans-serif;
            background-color: var(--light);
            color: var(--dark);
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
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            z-index: 1000;
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
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
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
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
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
        
        /* User Profile */
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
            background-color: var(--primary);
        }
        
        /* Table Styles */
        .table th {
            font-weight: 600;
            color: var(--dark);
            border-top: none;
            background-color: var(--light);
        }
        
        .table tr {
            transition: background-color 0.2s;
        }
        
        .table tr:hover {
            background-color: rgba(78, 115, 223, 0.05);
        }
        
        /* Status Badges */
        .status-badge {
            padding: 0.35em 0.65em;
            border-radius: 0.25rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
        }
        
        .status-present {
            background-color: rgba(28, 200, 138, 0.1);
            color: var(--secondary);
        }
        
        .status-absent {
            background-color: rgba(231, 74, 59, 0.1);
            color: var(--danger);
        }
        
        .status-late {
            background-color: rgba(246, 194, 62, 0.1);
            color: var(--warning);
        }
        
        /* Date Filter Form */
        .date-filter-card {
            border-left: 0.25rem solid var(--primary);
        }
        
        /* Empty State */
        .empty-state {
            padding: 3rem 0;
            text-align: center;
            color: var(--dark);
        }
        
        .empty-state i {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 1rem;
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
            <i class="bi bi-calendar-check me-2"></i>
            <span>Smart Attendance</span>
        </a>
        
        <div class="sidebar-divider"></div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="dashboard.php">
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
                <a class="nav-link active" href="attendance.php">
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
            <div class="d-flex align-items-center">
                <button class="btn btn-link d-md-none me-3" type="button" data-bs-toggle="collapse" data-bs-target=".sidebar">
                    <i class="bi bi-list" style="font-size: 1.5rem;"></i>
                </button>
                <h5 class="mb-0 fw-bold"><i class="bi bi-clipboard-data me-2"></i>Attendance Records</h5>
            </div>
            <div class="d-flex align-items-center">
                <span class="me-3 d-none d-sm-inline">Welcome, <strong><?php echo htmlspecialchars($admin_username); ?></strong></span>
                <div class="dropdown">
                    <a href="#" class="d-block link-dark text-decoration-none dropdown-toggle" id="dropdownUser" data-bs-toggle="dropdown">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($admin_username, 0, 1)); ?>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear me-2"></i>Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Content -->
        <div class="content-wrapper">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800 fw-bold"><i class="bi bi-clipboard-data me-2"></i>Attendance Records</h1>
                <div>
                    <span class="badge bg-primary">
                        <i class="bi bi-calendar-date me-1"></i>
                        <?php echo date('F j, Y', strtotime($date_filter)); ?>
                    </span>
                </div>
            </div>

            <!-- Date Filter -->
            <div class="card date-filter-card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-funnel me-2"></i>Filter Records</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="attendance.php" class="row g-3">
                        <div class="col-md-6">
                            <label for="date" class="form-label">Select Date</label>
                            <input type="date" class="form-control" id="date" name="date" 
                                   value="<?php echo htmlspecialchars($date_filter); ?>" 
                                   max="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-6 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-funnel me-2"></i>Apply Filter
                            </button>
                            <a href="attendance.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-clockwise me-2"></i>Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Attendance Records -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-list-check me-2"></i>
                        Attendance Records
                    </h5>
                    <span class="badge bg-primary">
                        <?php echo $result->num_rows; ?> records
                    </span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <?php
                                        $status_class = '';
                                        $status_icon = '';
                                        if ($row['status'] == 'Present') {
                                            $status_class = 'status-present';
                                            $status_icon = 'bi-check-circle';
                                        } elseif ($row['status'] == 'Absent') {
                                            $status_class = 'status-absent';
                                            $status_icon = 'bi-x-circle';
                                        } elseif ($row['status'] == 'Late') {
                                            $status_class = 'status-late';
                                            $status_icon = 'bi-clock-history';
                                        }
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($row['date'])); ?></td>
                                            <td><?php echo date('h:i A', strtotime($row['time'])); ?></td>
                                            <td>
                                                <span class="status-badge <?php echo $status_class; ?>">
                                                    <i class="bi <?php echo $status_icon; ?> me-1"></i>
                                                    <?php echo htmlspecialchars($row['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center empty-state">
                                            <i class="bi bi-clipboard-x"></i>
                                            <h5 class="mt-2">No attendance records found</h5>
                                            <p class="text-muted">No records available for the selected date</p>
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

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle sidebar on mobile
            document.getElementById('sidebarToggle').addEventListener('click', function() {
                document.querySelector('.sidebar').classList.toggle('active');
            });
            
            // Set today's date as default if no date is selected
            const dateInput = document.getElementById('date');
            if (dateInput && !dateInput.value) {
                dateInput.valueAsDate = new Date();
            }
        });
    </script>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
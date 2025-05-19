<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("location: admin_login.php");
    exit;
}

// Get admin username safely
$admin_username = $_SESSION['admin_username'] ?? 'Admin'; // Fallback to 'Admin' if not set

// Include database configuration
require_once "database/db_config.php";

// Initialize filter variables
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$name_filter = isset($_GET['name']) ? $_GET['name'] : '';

// Handle status update via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['attendance_id']) && isset($_POST['status'])) {
    $attendance_id = $_POST['attendance_id'];
    $status = $_POST['status'];
    
    $update_sql = "UPDATE attendance SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param('si', $status, $attendance_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'status' => $status]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    exit;
}

// Handle report generation
if (isset($_GET['generate_report'])) {
    // Get all student attendance data
    $report_sql = "SELECT 
                    users.id as user_id,
                    users.name, 
                    users.roll_no,
                    COUNT(CASE WHEN attendance.status = 'Present' THEN 1 END) as present_count,
                    COUNT(CASE WHEN attendance.status = 'Absent' THEN 1 END) as absent_count,
                    COUNT(attendance.id) as total_days
                  FROM users
                  LEFT JOIN attendance ON users.id = attendance.user_id
                  GROUP BY users.id";
    
    $report_result = $conn->query($report_sql);
    
    // Set headers for download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="attendance_report_' . date('Y-m-d') . '.csv"');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Write CSV headers
    fputcsv($output, ['ID', 'Name', 'Roll No', 'Present', 'Absent', 'Total Days']);
    
    // Write data
    while ($row = $report_result->fetch_assoc()) {
        fputcsv($output, [
            $row['user_id'],
            $row['name'],
            $row['roll_no'],
            $row['present_count'],
            $row['absent_count'],
            $row['total_days']
        ]);
    }
    
    fclose($output);
    exit;
}

// Build base query for attendance records
$sql = "SELECT attendance.id as attendance_id, users.id as user_id, users.name, users.roll_no, attendance.date, attendance.time, attendance.status 
        FROM attendance
        JOIN users ON attendance.user_id = users.id";

// Add filters to query
$where = [];
$params = [];
$types = '';

if (!empty($date_filter)) {
    $where[] = "attendance.date = ?";
    $params[] = $date_filter;
    $types .= 's';
}

if (!empty($status_filter)) {
    $where[] = "attendance.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if (!empty($name_filter)) {
    $where[] = "users.name LIKE ?";
    $params[] = "%$name_filter%";
    $types .= 's';
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY attendance.date DESC, attendance.time DESC";

// Prepare and execute query
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Function to get attendance count
function getAttendanceCount($status = null) {
    global $conn, $date_filter;
    
    $sql = "SELECT COUNT(*) AS count FROM attendance";
    $where = [];
    $params = [];
    $types = '';
    
    if (!empty($status)) {
        $where[] = "status = ?";
        $params[] = $status;
        $types .= 's';
    }
    
    if (!empty($date_filter)) {
        $where[] = "date = ?";
        $params[] = $date_filter;
        $types .= 's';
    }
    
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'];
}

// Get counts for chart
$present_count = getAttendanceCount('Present');
$absent_count = getAttendanceCount('Absent');
$total_count = getAttendanceCount();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Reports - Smart Attendance System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #1cc88a;
            --danger-color: #e74a3b;
            --dark-color: #5a5c69;
            --light-color: #f8f9fc;
        }
        
        body {
            background-color: var(--light-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            background: linear-gradient(180deg, var(--primary-color) 0%, #224abe 100%);
            min-height: 100vh;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .sidebar-brand {
            height: 4.375rem;
            text-decoration: none;
            font-size: 1.2rem;
            font-weight: 800;
            padding: 1.5rem 1rem;
            text-align: center;
            letter-spacing: 0.05rem;
            z-index: 1;
            color: white;
        }
        
        .sidebar-divider {
            border-top: 1px solid rgba(255, 255, 255, 0.15);
            margin: 0 1rem 1rem;
        }
        
        .nav-item .nav-link {
            position: relative;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 600;
            padding: 0.75rem 1rem;
            margin-bottom: 0.2rem;
        }
        
        .nav-item .nav-link:hover,
        .nav-item .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .nav-item .nav-link i {
            font-size: 0.85rem;
            margin-right: 0.25rem;
        }
        
        .topbar {
            height: 4.375rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            background-color: white;
        }
        
        .user-profile-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .content-wrapper {
            padding: 1.5rem;
        }
        
        .card {
            border: none;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            padding: 1rem 1.35rem;
            font-weight: 700;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 1rem;
        }
        
        .stat-card {
            border-left: 0.25rem solid;
            border-radius: 0.35rem;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .card-primary {
            border-left-color: var(--primary-color);
        }
        
        .card-success {
            border-left-color: var(--secondary-color);
        }
        
        .card-danger {
            border-left-color: var(--danger-color);
        }
        
        .card-dark {
            border-left-color: var(--dark-color);
        }
        
        .stat-icon {
            font-size: 2rem;
            opacity: 0.3;
            position: absolute;
            right: 1rem;
            top: 1rem;
        }
        
        .status-present {
            color: var(--secondary-color);
            font-weight: 600;
        }
        
        .status-absent {
            color: var(--danger-color);
            font-weight: 600;
        }
        
        .table th {
            background-color: #f8f9fc;
            font-weight: 700;
        }
        
        .filter-form {
            background-color: white;
            border-radius: 0.35rem;
            padding: 1.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        }
        
        .badge-present {
            background-color: var(--secondary-color);
        }
        
        .badge-absent {
            background-color: var(--danger-color);
        }
        
        .status-select {
            min-width: 120px;
            cursor: pointer;
        }
        
        .status-select:focus {
            box-shadow: none;
        }
        
        .status-select.present {
            border-color: var(--secondary-color);
            background-color: rgba(28, 200, 138, 0.1);
        }
        
        .status-select.absent {
            border-color: var(--danger-color);
            background-color: rgba(231, 74, 59, 0.1);
        }
        
        .save-status {
            display: none;
            margin-left: 5px;
        }
        
        .toast {
            z-index: 9999;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar collapse bg-primary">
                <div class="position-sticky pt-3">
                    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="dashboard.php">
                        <i class="bi bi-calendar-check me-2"></i>
                        <span>Smart Attendance</span>
                    </a>
                    <div class="sidebar-divider"></div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="bi bi-speedometer2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="attendance.php">
                                <i class="bi bi-clipboard-data"></i>
                                Attendance
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="live_attendance.php">
                                <i class="bi bi-camera-video"></i>
                                Live Attendance
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_users.php">
                                <i class="bi bi-people-fill"></i>
                                Manage Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="reports.php">
                                <i class="bi bi-graph-up"></i>
                                Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="bi bi-box-arrow-right"></i>
                                Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <!-- Topbar -->
                <nav class="navbar topbar mb-4 static-top shadow">
                    <div class="container-fluid">
                        <button class="btn btn-link d-md-none rounded-circle me-3" type="button" data-bs-toggle="collapse" data-bs-target=".sidebar">
                            <i class="bi bi-list"></i>
                        </button>
                        
                        <div class="d-flex align-items-center ms-auto">
                        <span class="me-3">Welcome, <?php echo htmlspecialchars($admin_username); ?></span>
                        <div class="dropdown">
                                <a class="dropdown-toggle d-flex align-items-center" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-expanded="false">
                                    <?php if (!empty($_SESSION['photo'])): ?>
                                        <img src="<?php echo htmlspecialchars($_SESSION['photo']); ?>" class="user-profile-img">
                                    <?php else: ?>
                                        <div class="user-profile-img bg-primary text-white d-flex align-items-center justify-content-center">
                                            <i class="bi bi-person"></i>
                                        </div>
                                    <?php endif; ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuLink">
                                    <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                                    <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear me-2"></i>Settings</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </nav>

                <!-- Content -->
                <div class="content-wrapper">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-graph-up me-2"></i>Attendance Reports</h1>
                        <a href="reports.php?generate_report=true" class="btn btn-success">
                            <i class="bi bi-file-earmark-excel me-2"></i>Generate Report
                        </a>
                    </div>

                    <!-- Stats Cards -->
                    <div class="row mb-4">
                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card stat-card card-success h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Present</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $present_count; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="bi bi-check-circle stat-icon text-success"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card stat-card card-danger h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                                Absent</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $absent_count; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="bi bi-x-circle stat-icon text-danger"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card stat-card card-primary h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Total Records</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_count; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="bi bi-list-check stat-icon text-primary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Form -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-funnel me-2"></i>Filter Records</h5>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="reports.php" class="row g-3">
                                <div class="col-md-4">
                                    <label for="date" class="form-label">Date</label>
                                    <input type="date" class="form-control" id="date" name="date" 
                                           value="<?php echo htmlspecialchars($date_filter); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="">All Statuses</option>
                                        <option value="Present" <?php echo $status_filter == 'Present' ? 'selected' : ''; ?>>Present</option>
                                        <option value="Absent" <?php echo $status_filter == 'Absent' ? 'selected' : ''; ?>>Absent</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="name" class="form-label">Name</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo htmlspecialchars($name_filter); ?>" 
                                           placeholder="Search by name">
                                </div>
                                <div class="col-12 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="bi bi-funnel me-2"></i>Apply
                                    </button>
                                    <a href="reports.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-clockwise me-2"></i>Reset
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Charts -->
                    <div class="row mb-4">
                        <div class="col-lg-12">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="bi bi-pie-chart me-2"></i>Attendance Distribution</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="attendanceChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Attendance Records -->
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Attendance Records</h5>
                                <div>
                                    <span class="badge bg-primary">
                                        Showing <?php echo $result->num_rows; ?> records
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Roll No</th>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result->num_rows > 0): ?>
                                            <?php while ($row = $result->fetch_assoc()): ?>
                                                <?php
                                                $status_class = strtolower($row['status']);
                                                ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['roll_no']); ?></td>
                                                    <td><?php echo date('M j, Y', strtotime($row['date'])); ?></td>
                                                    <td><?php echo date('h:i A', strtotime($row['time'])); ?></td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <select class="form-select status-select <?php echo $status_class; ?>" 
                                                                    data-attendance-id="<?php echo $row['attendance_id']; ?>">
                                                                <option value="Present" <?php echo $row['status'] == 'Present' ? 'selected' : ''; ?>>Present</option>
                                                                <option value="Absent" <?php echo $row['status'] == 'Absent' ? 'selected' : ''; ?>>Absent</option>
                                                            </select>
                                                            <button class="btn btn-sm btn-success save-status" 
                                                                    data-attendance-id="<?php echo $row['attendance_id']; ?>">
                                                                <i class="bi bi-check"></i>
                                                            </button>
                                                            <span class="badge rounded-pill ms-2 d-none badge-<?php echo $status_class; ?>">
                                                                <?php echo htmlspecialchars($row['status']); ?>
                                                            </span>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-4">
                                                    <i class="bi bi-exclamation-circle me-2"></i>
                                                    No attendance records found matching your criteria
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        // Attendance Distribution Chart
        const ctx = document.getElementById('attendanceChart').getContext('2d');
        const attendanceChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Present', 'Absent'],
                datasets: [{
                    data: [
                        <?php echo $present_count; ?>,
                        <?php echo $absent_count; ?>
                    ],
                    backgroundColor: [
                        '#1cc88a',
                        '#e74a3b'
                    ],
                    borderColor: '#fff',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        // Handle status dropdown changes
        $(document).ready(function() {
            $('.status-select').change(function() {
                const $row = $(this).closest('td');
                const newStatus = $(this).val();
                
                // Update select appearance
                $(this).removeClass('present absent')
                       .addClass(newStatus.toLowerCase());
                
                // Show save button for this row only
                $row.find('.save-status').show();
            });
            
            // Save status changes
            $(document).on('click', '.save-status', function() {
                const $button = $(this);
                const attendanceId = $button.data('attendance-id');
                const $select = $button.siblings('.status-select');
                const newStatus = $select.val();
                const $badge = $button.siblings('.badge');
                const $row = $button.closest('tr');
                
                // Show loading state
                $button.html('<i class="bi bi-arrow-repeat"></i>');
                $button.prop('disabled', true);
                
                // Send AJAX request
                $.ajax({
                    url: 'reports.php',
                    method: 'POST',
                    data: {
                        attendance_id: attendanceId,
                        status: newStatus
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Update badge for this row only
                            $badge.removeClass('badge-present badge-absent d-none')
                                  .addClass('badge-' + newStatus.toLowerCase())
                                  .text(newStatus)
                                  .removeClass('d-none');
                            
                            // Hide save button for this row
                            $button.hide();
                            
                            // Show success message
                            const $toast = $(`
                                <div class="toast align-items-center text-white bg-success border-0 position-fixed bottom-0 end-0 m-3" role="alert" aria-live="assertive" aria-atomic="true">
                                    <div class="d-flex">
                                        <div class="toast-body">
                                            <i class="bi bi-check-circle me-2"></i> Status updated successfully!
                                        </div>
                                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                                    </div>
                                </div>
                            `);
                            $('body').append($toast);
                            const toast = new bootstrap.Toast($toast[0]);
                            toast.show();
                            
                            // Remove toast after hiding
                            $toast.on('hidden.bs.toast', function() {
                                $(this).remove();
                            });
                            
                            // Reload the page after 1 second to update counts
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        } else {
                            alert('Error updating status: ' + (response.error || 'Unknown error'));
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Error updating status: ' + error);
                    },
                    complete: function() {
                        // Reset button state
                        $button.html('<i class="bi bi-check"></i>');
                        $button.prop('disabled', false);
                    }
                });
            });
            
            // Hide save buttons initially
            $('.save-status').hide();
        });
    </script>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
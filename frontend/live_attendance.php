<?php
session_start();
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("location: admin_login.php");
    exit;
}

// Get admin username safely
$admin_username = $_SESSION['admin_username'] ?? 'Admin'; // Fallback to 'Admin' if not set

// Include database configuration
require_once "database/db_config.php";

// Handle marking attendance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_attendance'])) {
    $user_id = intval($_POST['user_id']);
    
    // Mark attendance in database
    $stmt = $conn->prepare("INSERT INTO attendance (user_id, date, time) VALUES (?, CURDATE(), CURTIME())");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Attendance marked successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error marking attendance']);
    }
    exit;
}

// Fetch recent attendance logs
$logs = [];
$stmt = $conn->prepare("SELECT a.*, u.name, u.roll_no FROM attendance a JOIN users u ON a.user_id = u.id ORDER BY a.date DESC, a.time DESC LIMIT 50");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $logs[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Attendance - Smart Attendance System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom Styles -->
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #1cc88a;
            --danger-color: #e74a3b;
            --warning-color: #f6c23e;
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
            width: 250px;
            position: fixed;
            z-index: 1000;
        }
        
        .sidebar-brand {
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 1.2rem;
            font-weight: 700;
            color: white;
            padding: 0 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-nav {
            padding: 1rem 0;
        }
        
        .nav-item {
            margin: 0.25rem 0;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .nav-link:hover {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
            border-left: 3px solid white;
        }
        
        .nav-link i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
            width: 24px;
            text-align: center;
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
        
        .video-container {
            position: relative;
            width: 100%;
            max-width: 640px;
            margin: 0 auto;
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.1);
            border: 3px solid var(--primary-color);
        }
        
        .video-container video, 
        .video-container canvas {
            width: 100%;
            height: auto;
            display: block;
        }
        
        .attendance-table {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        }
        
        .table th {
            background-color: #f8f9fc;
            border-top: none;
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
        }
        
        .btn-recognition {
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            margin: 0 0.5rem;
        }
        
        .recognition-status {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            padding: 0.75rem;
            border-radius: 0.5rem;
            background-color: white;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        }
        
        .log-entry {
            transition: all 0.3s ease;
        }
        
        .log-entry.new {
            background-color: rgba(28, 200, 138, 0.1);
        }
        
        .mark-btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar collapse bg-primary">
                <div class="position-sticky">
                    <a class="sidebar-brand" href="dashboard.php">
                        <i class="bi bi-calendar-check me-2"></i>
                        <span>Smart Attendance</span>
                    </a>
                    
                    <div class="sidebar-nav">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link" href="dashboard.php">
                                    <i class="bi bi-speedometer2"></i>
                                    <span>Dashboard</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="manage_users.php">
                                    <i class="bi bi-people-fill"></i>
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
                                <a class="nav-link active" href="live_attendance.php">
                                    <i class="bi bi-camera-video-fill"></i>
                                    <span>Live Attendance</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="reports.php">
                                    <i class="bi bi-graph-up"></i>
                                    <span>Reports</span>
                                </a>
                            </li>
                            <li class="nav-item mt-3">
                                <a class="nav-link" href="logout.php">
                                    <i class="bi bi-box-arrow-right"></i>
                                    <span>Logout</span>
                                </a>
                            </li>
                        </ul>
                    </div>
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
                        <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-camera-video-fill me-2"></i>Live Attendance</h1>
                    </div>

                    <div class="recognition-status text-center mb-4">
                        <i class="bi bi-camera-video-off me-2"></i>
                        <span id="statusText">Recognition not started</span>
                    </div>

                    <div class="text-center mb-4">
                        <div class="video-container mb-3">
                            <video id="video" width="640" height="480" autoplay muted style="display: none;"></video>
                            <canvas id="canvas" width="640" height="480" style="display: none;"></canvas>
                        </div>
                        
                        <div class="d-flex justify-content-center">
                            <button id="startBtn" class="btn btn-primary btn-recognition">
                                <i class="bi bi-play-circle me-2"></i>Start Recognition
                            </button>
                            <button id="stopBtn" class="btn btn-danger btn-recognition" disabled>
                                <i class="bi bi-stop-circle me-2"></i>Stop Recognition
                            </button>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Attendance Log</h5>
                            <div class="input-group" style="width: 300px;">
                                <input type="text" class="form-control" placeholder="Search logs..." id="searchInput">
                                <button class="btn btn-outline-secondary" type="button">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle attendance-table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Roll No</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="logTableBody">
                                        <?php foreach ($logs as $log): ?>
                                            <tr class="log-entry">
                                                <td><?php echo htmlspecialchars($log['name']); ?></td>
                                                <td><?php echo htmlspecialchars($log['roll_no']); ?></td>
                                                <td>
                                                    <span class="badge bg-success rounded-pill">
                                                        <i class="bi bi-check-circle me-1"></i>Present
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($log['date']); ?></td>
                                                <td><?php echo htmlspecialchars($log['time']); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary mark-btn" disabled>
                                                        <i class="bi bi-check-lg"></i> Marked
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
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

    <script>
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const logTableBody = document.getElementById('logTableBody');
        const ctx = canvas.getContext('2d');
        const startBtn = document.getElementById('startBtn');
        const stopBtn = document.getElementById('stopBtn');
        const statusText = document.getElementById('statusText');
        const statusBox = document.querySelector('.recognition-status');
        const searchInput = document.getElementById('searchInput');

        let lastUserID = null;
        let intervalId = null;
        let stream = null;
        let recognitionActive = false;

        function addLogEntry(user, isNew = false) {
            const now = new Date();
            const dateString = now.toLocaleDateString();
            const timeString = now.toLocaleTimeString();
            
            const row = document.createElement('tr');
            row.className = isNew ? 'log-entry new' : 'log-entry';
            
            row.innerHTML = `
                <td>${user.name}</td>
                <td>${user.roll_no}</td>
                <td>
                    <span class="badge bg-success rounded-pill">
                        <i class="bi bi-check-circle me-1"></i>Present
                    </span>
                </td>
                <td>${dateString}</td>
                <td>${timeString}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary mark-btn" disabled>
                        <i class="bi bi-check-lg"></i> Marked
                    </button>
                </td>
            `;
            
            logTableBody.prepend(row);
            
            // Remove 'new' class after animation
            if (isNew) {
                setTimeout(() => {
                    row.classList.remove('new');
                }, 3000);
            }
            
            // Update status box
            statusBox.innerHTML = `<i class="bi bi-check-circle-fill me-2"></i><span>Recognition active - ${user.name} recognized</span>`;
            statusBox.style.color = 'var(--secondary-color)';
        }

        function addUnknownEntry() {
            const now = new Date();
            const dateString = now.toLocaleDateString();
            const timeString = now.toLocaleTimeString();
            
            const row = document.createElement('tr');
            row.className = 'log-entry new';
            
            row.innerHTML = `
                <td colspan="2">Unknown User</td>
                <td>
                    <span class="badge bg-warning rounded-pill">
                        <i class="bi bi-exclamation-triangle me-1"></i>Unknown
                    </span>
                </td>
                <td>${dateString}</td>
                <td>${timeString}</td>
                <td>
                    <button class="btn btn-sm btn-outline-secondary mark-btn" disabled>
                        <i class="bi bi-x-lg"></i> Not Marked
                    </button>
                </td>
            `;
            
            logTableBody.prepend(row);
            
            // Remove 'new' class after animation
            setTimeout(() => {
                row.classList.remove('new');
            }, 3000);
            
            // Update status box
            statusBox.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-2"></i><span>Recognition active - Unknown user</span>`;
            statusBox.style.color = 'var(--danger-color)';
        }

        async function sendFrame() {
            if (!stream) return;

            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.8));

            const formData = new FormData();
            formData.append('image', blob, 'frame.jpg');

            try {
                const response = await fetch('http://127.0.0.1:5000/recognize', { 
                    method: 'POST', 
                    body: formData 
                });
                const result = await response.json();
                
                if (result.success) {
                    if (result.recognized) {
                        if (result.user.id !== lastUserID) {
                            lastUserID = result.user.id;
                            
                            // Add to log table
                            addLogEntry(result.user, true);
                            
                            // Mark attendance
                            try {
                                const markResponse = await fetch('live_attendance.php', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                    body: `mark_attendance=true&user_id=${encodeURIComponent(result.user.id)}`
                                });
                                const markResult = await markResponse.json();
                                
                                if (!markResult.success) {
                                    console.error('Error marking attendance:', markResult.message);
                                }
                            } catch (e) {
                                console.error('Error marking attendance:', e);
                            }
                        }
                    } else {
                        lastUserID = null;
                        addUnknownEntry();
                    }
                } else {
                    console.error("Recognition error:", result.error || 'Unknown error');
                }
            } catch (e) {
                console.error("Failed to contact recognition API:", e);
            }
        }

        // Search functionality
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = logTableBody.getElementsByTagName('tr');
            
            for (let row of rows) {
                const cells = row.getElementsByTagName('td');
                let found = false;
                
                for (let i = 0; i < cells.length - 1; i++) { // Skip action column
                    if (cells[i].textContent.toLowerCase().includes(searchTerm)) {
                        found = true;
                        break;
                    }
                }
                
                row.style.display = found ? '' : 'none';
            }
        });

        // Start Button
        startBtn.addEventListener('click', async () => {
            try {
                stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { 
                        width: { ideal: 640 },
                        height: { ideal: 480 },
                        facingMode: 'user' 
                    } 
                });
                
                video.srcObject = stream;
                video.style.display = 'block';
                startBtn.disabled = true;
                stopBtn.disabled = false;
                intervalId = setInterval(sendFrame, 2000);
                recognitionActive = true;
                
                statusText.textContent = "Recognition active";
                statusBox.innerHTML = `<i class="bi bi-camera-video me-2"></i><span>Recognition active - Scanning...</span>`;
                statusBox.style.color = 'var(--primary-color)';
                
            } catch (err) {
                alert('Error accessing webcam: ' + err);
            }
        });

        // Stop Button
        stopBtn.addEventListener('click', () => {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
                video.style.display = 'none';
                clearInterval(intervalId);
                intervalId = null;
                startBtn.disabled = false;
                stopBtn.disabled = true;
                recognitionActive = false;
                lastUserID = null;
                
                statusText.textContent = "Recognition stopped";
                statusBox.innerHTML = `<i class="bi bi-camera-video-off me-2"></i><span>Recognition not started</span>`;
                statusBox.style.color = 'var(--dark-color)';
            }
        });

        // Clean up on page unload
        window.addEventListener('beforeunload', () => {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
        });
    </script>
</body>
</html>
<?php
session_start();

// Enhanced admin authentication check
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("Location: admin_login.php");
    exit;
}

require_once "database/db_config.php";
$admin_username = $_SESSION['admin_username'] ?? 'Admin';

// Handle Add User
if (isset($_POST['add_user'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $roll_no = trim($_POST['roll_no']);
    $photo = $_FILES['photo']['name'];
    $face_image_data = $_POST['face_image_data'] ?? null;

    // Validate inputs
    if (empty($name) || empty($email) || empty($roll_no)) {
        echo "<script>alert('Name, email and roll number are required!');</script>";
    } else {
        // Validate email and roll number uniqueness
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR roll_no = ?");
        $stmt->bind_param("ss", $email, $roll_no);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $existing = $result->fetch_assoc();
            if ($existing['email'] === $email) {
                echo "<script>alert('Email already exists!');</script>";
            } else {
                echo "<script>alert('Roll number already exists!');</script>";
            }
        } else {
            // Insert user with NULL images first to get ID
            $stmt = $conn->prepare("INSERT INTO users (name, email, roll_no, photo, face_image) VALUES (?, ?, ?, NULL, NULL)");
            $stmt->bind_param("sss", $name, $email, $roll_no);
            
            if ($stmt->execute()) {
                $user_id = $conn->insert_id;
                $photo_path = null;
                $face_image_path = null;
                $success = true;
                
                // Create directories if they don't exist
                if (!is_dir('uploads')) {
                    mkdir('uploads', 0755, true);
                }
                if (!is_dir('dataset')) {
                    mkdir('dataset', 0755, true);
                }
                
                // Process profile photo if uploaded
                if (!empty($_FILES['photo']['tmp_name'])) {
                    $photo_ext = pathinfo($photo, PATHINFO_EXTENSION);
                    $photo_filename = str_replace(' ', '_', $name)."_".$roll_no."_".$user_id."_photo.".$photo_ext;
                    $photo_path = "uploads/".$photo_filename;
                    
                    if (!move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path)) {
                        $success = false;
                        echo "<script>alert('Error uploading profile photo');</script>";
                    }
                }
                
                // Process face image if captured (without GD library)
                if ($face_image_data && $success) {
                    $face_image = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $face_image_data));
                    if ($face_image !== false) {
                        $face_filename = str_replace(' ', '_', $name)."_".$roll_no."_".$user_id.".jpg";
                        $face_image_path = "dataset/".$face_filename;
                        
                        // Save the image directly
                        if (!file_put_contents($face_image_path, $face_image)) {
                            $success = false;
                            echo "<script>alert('Error saving face image');</script>";
                        }
                    } else {
                        $success = false;
                        echo "<script>alert('Invalid face image format');</script>";
                    }
                }
                
                // Update user with image paths if both succeeded
                if ($success) {
                    $stmt = $conn->prepare("UPDATE users SET photo = ?, face_image = ? WHERE id = ?");
                    $stmt->bind_param("ssi", $photo_path, $face_image_path, $user_id);
                    if ($stmt->execute()) {
                        echo "<script>
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: 'User added successfully!',
                                showConfirmButton: false,
                                timer: 1500
                            }).then(() => { window.location='manage_users.php'; });
                        </script>";
                    } else {
                        echo "<script>alert('Database update error');</script>";
                    }
                }
            } else {
                echo "<script>alert('Error creating user: ".$stmt->error."');</script>";
            }
        }
    }
}

// Handle Delete User
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    
    // Get user data first
    $stmt = $conn->prepare("SELECT photo, face_image FROM users WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    // Delete user from database
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        // Delete associated files
        if (!empty($user['photo']) && file_exists($user['photo'])) {
            unlink($user['photo']);
        }
        if (!empty($user['face_image']) && file_exists($user['face_image'])) {
            unlink($user['face_image']);
        }
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Deleted!',
                text: 'User deleted successfully!',
                showConfirmButton: false,
                timer: 1500
            }).then(() => { window.location='manage_users.php'; });
        </script>";
    } else {
        echo "<script>alert('Error: ".$stmt->error."');</script>";
    }
}

// Fetch all users
$sql = "SELECT * FROM users ORDER BY name ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Smart Attendance System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
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
        }
        
        body {
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 
                        'Helvetica Neue', Arial, sans-serif;
            background-color: #f5f7fb;
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
        
        /* User Avatar */
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
        
        /* Webcam Container */
        .webcam-container {
            position: relative;
            width: 100%;
            max-width: 320px;
            border-radius: 0.5rem;
            overflow: hidden;
            border: 2px solid var(--primary);
            margin-bottom: 1rem;
        }
        
        /* Buttons */
        .btn-capture {
            background-color: var(--primary);
            color: white;
            transition: all 0.3s;
        }
        
        .btn-capture:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        /* Table Styles */
        .table th {
            font-weight: 600;
            color: var(--dark);
            border-top: none;
        }
        
        .table tr {
            transition: background-color 0.2s;
        }
        
        .table tr:hover {
            background-color: rgba(78, 115, 223, 0.05);
        }
        
        /* Badges */
        .badge {
            font-weight: 500;
            padding: 0.35em 0.65em;
        }
        
        /* Action Buttons */
        .action-buttons .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        
        .action-buttons .btn:hover {
            transform: translateY(-2px);
        }
        
        /* Form Controls */
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
        }
        
        /* Modal Header */
        .modal-header {
            background-color: var(--primary);
            color: white;
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
            <i class="bi bi-people-fill me-2"></i>
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
                <a class="nav-link active" href="manage_users.php">
                    <i class="bi bi-people"></i>
                    <span>Manage Users</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="attendance.php">
                    <i class="bi bi-calendar-check"></i>
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
                <h5 class="mb-0 fw-bold"><i class="bi bi-people me-2"></i>User Management</h5>
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
                <h1 class="h3 mb-0 text-gray-800 fw-bold"><i class="bi bi-people me-2"></i>Manage Users</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="bi bi-plus-lg me-2"></i>Add User
                </button>
            </div>

            <!-- User List -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-table me-2"></i>User List</h5>
                    <div class="input-group" style="width: 300px;">
                        <input type="text" class="form-control" placeholder="Search users..." id="searchInput">
                        <button class="btn btn-outline-secondary" type="button">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="userTable">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Photo</th>
                                    <th>Name</th>
                                    <th>Roll No</th>
                                    <th>Email</th>
                                    <th>Face Data</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                                            <td>
                                                <div class="user-avatar">
                                                    <i class="bi bi-person"></i>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['roll_no']); ?></td>
                                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                                            <td>
                                                <?php if (!empty($row['face_image'])): ?>
                                                    <span class="badge bg-success rounded-pill">
                                                        <i class="bi bi-check-circle me-1"></i>Captured
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning rounded-pill">
                                                        <i class="bi bi-exclamation-triangle me-1"></i>Missing
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2 action-buttons">
                                                    <a href="edit_user.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-outline-danger delete-btn" data-id="<?php echo $row['id']; ?>">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center empty-state">
                                            <i class="bi bi-people"></i>
                                            <h5 class="mt-2">No users found</h5>
                                            <p class="text-muted">Add your first user to get started</p>
                                            <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                                <i class="bi bi-plus-lg me-2"></i>Add User
                                            </button>
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

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel"><i class="bi bi-person-plus me-2"></i>Add New User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" enctype="multipart/form-data" id="userForm">
                    <input type="hidden" name="face_image_data" id="face_image_data">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="mb-3">
                                    <label for="roll_no" class="form-label">Roll Number</label>
                                    <input type="text" class="form-control" id="roll_no" name="roll_no" required>
                                </div>
                                <div class="mb-3">
                                    <label for="photo" class="form-label">Profile Photo</label>
                                    <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
                                    <div class="form-text">JPG, JPEG, or PNG (Max 5MB)</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex flex-column align-items-center">
                                    <div class="webcam-container">
                                        <video id="webcam" width="100%" height="240" autoplay></video>
                                        <canvas id="canvas" width="320" height="240" style="display:none;"></canvas>
                                    </div>
                                    <button type="button" class="btn btn-primary btn-capture w-100 mb-3" id="captureButton">
                                        <i class="bi bi-camera me-2"></i>Capture Face
                                    </button>
                                    <div id="captureStatus" class="text-center text-muted small">
                                        <i class="bi bi-info-circle me-1"></i>Please capture a clear frontal face image for recognition
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_user" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i>Save User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Custom JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const webcam = document.getElementById("webcam");
            const canvas = document.getElementById("canvas");
            const context = canvas.getContext("2d");
            const captureButton = document.getElementById("captureButton");
            const faceInput = document.getElementById("face_image_data");
            const captureStatus = document.getElementById("captureStatus");
            const deleteButtons = document.querySelectorAll(".delete-btn");
            const searchInput = document.getElementById("searchInput");
            const userTable = document.getElementById("userTable");
            let stream = null;

            // Start webcam
            async function startWebcam() {
                try {
                    stream = await navigator.mediaDevices.getUserMedia({ 
                        video: { 
                            width: 320, 
                            height: 240,
                            facingMode: "user"
                        } 
                    });
                    webcam.srcObject = stream;
                    captureButton.disabled = false;
                } catch (err) {
                    console.error("Error accessing webcam: ", err);
                    captureStatus.innerHTML = '<span class="text-danger"><i class="bi bi-camera-video-off me-1"></i>Could not access webcam. Please enable camera permissions.</span>';
                    captureButton.disabled = true;
                }
            }

            // Capture image
            captureButton.addEventListener("click", function() {
                context.drawImage(webcam, 0, 0, canvas.width, canvas.height);
                faceInput.value = canvas.toDataURL("image/jpeg", 0.8);
                captureStatus.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i>Face captured successfully!</span>';
                
                // Add a small animation to the button
                captureButton.innerHTML = '<i class="bi bi-check-circle me-2"></i>Captured!';
                captureButton.classList.remove('btn-primary');
                captureButton.classList.add('btn-success');
                
                setTimeout(() => {
                    captureButton.innerHTML = '<i class="bi bi-camera me-2"></i>Recapture';
                    captureButton.classList.remove('btn-success');
                    captureButton.classList.add('btn-primary');
                }, 2000);
            });

            // Delete user confirmation
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.getAttribute('data-id');
                    
                    Swal.fire({
                        title: 'Are you sure?',
                        text: "You won't be able to revert this!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, delete it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = `manage_users.php?delete_id=${userId}`;
                        }
                    });
                });
            });

            // Search functionality
            searchInput.addEventListener('keyup', function() {
                const searchTerm = this.value.toLowerCase();
                const rows = userTable.getElementsByTagName('tr');
                
                for (let i = 1; i < rows.length; i++) {
                    const cells = rows[i].getElementsByTagName('td');
                    let found = false;
                    
                    for (let j = 0; j < cells.length - 1; j++) { // Skip actions column
                        if (cells[j].textContent.toLowerCase().includes(searchTerm)) {
                            found = true;
                            break;
                        }
                    }
                    
                    rows[i].style.display = found ? '' : 'none';
                }
            });

            // Initialize webcam when modal is shown
            const addUserModal = document.getElementById('addUserModal');
            addUserModal.addEventListener('shown.bs.modal', startWebcam);
            
            // Clean up webcam when modal is hidden
            addUserModal.addEventListener('hidden.bs.modal', () => {
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                }
                // Reset form and status
                document.getElementById('userForm').reset();
                captureStatus.innerHTML = '<i class="bi bi-info-circle me-1"></i>Please capture a clear frontal face image for recognition';
                if (captureButton.classList.contains('btn-success')) {
                    captureButton.classList.remove('btn-success');
                    captureButton.classList.add('btn-primary');
                    captureButton.innerHTML = '<i class="bi bi-camera me-2"></i>Capture Face';
                }
            });
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>
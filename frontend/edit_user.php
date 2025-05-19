<?php
session_start();

// Admin authentication check
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("Location: admin_login.php");
    exit;
}

require_once "database/db_config.php";

// Get user ID from URL
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("User not found");
}

// Handle form submission
if (isset($_POST['update_user'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $roll_no = trim($_POST['roll_no']);
    $photo = $_FILES['photo']['name'];
    $face_image_data = $_POST['face_image_data'] ?? null;
    $remove_face_data = isset($_POST['remove_face_data']) ? true : false;

    // Validate inputs
    if (empty($name) || empty($email) || empty($roll_no)) {
        echo "<script>alert('Name, email and roll number are required!');</script>";
    } else {
        // Check if email or roll number is being changed to one that already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE (email = ? OR roll_no = ?) AND id != ?");
        $stmt->bind_param("ssi", $email, $roll_no, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $existing = $result->fetch_assoc();
            if ($existing['email'] === $email) {
                echo "<script>alert('Email already exists!');</script>";
            } else {
                echo "<script>alert('Roll number already exists!');</script>";
            }
            $email = $user['email']; // Revert to original email
            $roll_no = $user['roll_no']; // Revert to original roll number
        }

        // Initialize variables
        $photo_path = $user['photo'];
        $face_image_path = $user['face_image'];
        $success = true;

        // Process profile photo if uploaded
        if (!empty($_FILES['photo']['tmp_name'])) {
            // Delete old photo if exists
            if (!empty($photo_path) && file_exists($photo_path)) {
                unlink($photo_path);
            }

            $photo_ext = pathinfo($photo, PATHINFO_EXTENSION);
            $photo_filename = "user_".str_replace(' ', '_', $name)."_".$user_id."_photo.".$photo_ext;
            $photo_path = "uploads/".$photo_filename;
            
            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path)) {
                $success = false;
                echo "<script>alert('Error uploading profile photo');</script>";
                $photo_path = $user['photo']; // Revert to original photo
            }
        }

        // Process face image if captured or removed
        if ($remove_face_data) {
            // Delete old face image if exists
            if (!empty($face_image_path) && file_exists($face_image_path)) {
                unlink($face_image_path);
            }
            $face_image_path = null;
        } elseif ($face_image_data) {
            // Delete old face image if exists
            if (!empty($face_image_path) && file_exists($face_image_path)) {
                unlink($face_image_path);
            }

            $face_image = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $face_image_data));
            $face_filename = "user_".str_replace(' ', '_', $name)."_".$user_id.".jpg";
            $face_image_path = "dataset/".$face_filename;
            
            if (!file_put_contents($face_image_path, $face_image)) {
                $success = false;
                echo "<script>alert('Error saving face image');</script>";
                $face_image_path = $user['face_image']; // Revert to original face image
            }
        }

        // Update user in database
        if ($success) {
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, roll_no = ?, photo = ?, face_image = ? WHERE id = ?");
            $stmt->bind_param("sssssi", $name, $email, $roll_no, $photo_path, $face_image_path, $user_id);
            
            if ($stmt->execute()) {
                echo "<script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Updated!',
                        text: 'User updated successfully!',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => { window.location='manage_users.php'; });
                </script>";
            } else {
                echo "<script>alert('Error updating user: ".$stmt->error."');</script>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Smart Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        /* Consistent styling with manage_users.php */
        :root {
            --primary-color: #4e73df;
            --secondary-color: #1cc88a;
            --accent-color: #f6c23e;
            --danger-color: #e74a3b;
            --dark-color: #5a5c69;
            --light-color: #f8f9fc;
        }
        
        body {
            background-color: #f5f7fb;
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        
        .card {
            border: none;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        }
        
        .user-photo-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary-color);
            margin: 0 auto;
            display: block;
        }
        
        .webcam-container {
            position: relative;
            width: 100%;
            max-width: 320px;
            border-radius: 0.35rem;
            overflow: hidden;
            border: 2px solid var(--primary-color);
            margin: 0 auto;
        }
        
        .btn-capture {
            background-color: var(--primary-color);
            color: white;
            transition: all 0.3s;
        }
        
        .btn-capture:hover {
            background-color: #3a5bc7;
            transform: translateY(-2px);
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Edit User</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" id="editUserForm">
                            <input type="hidden" name="face_image_data" id="face_image_data">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="text-center mb-4">
                                        <?php if (!empty($user['photo'])): ?>
                                            <img src="<?php echo htmlspecialchars($user['photo']); ?>" class="user-photo-preview mb-2" id="photoPreview">
                                        <?php else: ?>
                                            <div class="user-photo-preview mb-2 d-flex align-items-center justify-content-center bg-secondary text-white" id="photoPreview">
                                                <i class="bi bi-person" style="font-size: 3rem;"></i>
                                            </div>
                                        <?php endif; ?>
                                        <input type="file" class="form-control" id="photo" name="photo" accept="image/*" onchange="previewPhoto(this)">
                                        <div class="form-text">Upload new profile photo (optional)</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="roll_no" class="form-label">Roll Number</label>
                                        <input type="text" class="form-control" id="roll_no" name="roll_no" value="<?php echo htmlspecialchars($user['roll_no']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label class="form-label">Face Recognition Data</label>
                                        <?php if (!empty($user['face_image'])): ?>
                                            <div class="alert alert-success">
                                                <i class="bi bi-check-circle-fill me-2"></i>Face data is registered
                                            </div>
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="checkbox" id="removeFaceData" name="remove_face_data">
                                                <label class="form-check-label" for="removeFaceData">
                                                    Remove face recognition data
                                                </label>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-warning">
                                                <i class="bi bi-exclamation-triangle-fill me-2"></i>No face data registered
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="webcam-container mb-3">
                                            <video id="webcam" width="100%" height="240" autoplay></video>
                                            <canvas id="canvas" width="320" height="240" style="display:none;"></canvas>
                                        </div>
                                        
                                        <button type="button" class="btn btn-primary w-100 mb-2" id="captureButton">
                                            <i class="bi bi-camera me-2"></i>Capture New Face Data
                                        </button>
                                        
                                        <div id="captureStatus" class="text-center text-muted small">
                                            Capture a clear frontal face image for recognition
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-4">
                                <a href="manage_users.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left me-2"></i>Back to List
                                </a>
                                <button type="submit" name="update_user" class="btn btn-primary">
                                    <i class="bi bi-save me-2"></i>Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const webcam = document.getElementById("webcam");
            const canvas = document.getElementById("canvas");
            const context = canvas.getContext("2d");
            const captureButton = document.getElementById("captureButton");
            const faceInput = document.getElementById("face_image_data");
            const captureStatus = document.getElementById("captureStatus");
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
                
                // Add visual feedback
                captureButton.innerHTML = '<i class="bi bi-check-circle me-2"></i>Captured!';
                captureButton.classList.remove('btn-primary');
                captureButton.classList.add('btn-success');
                
                setTimeout(() => {
                    captureButton.innerHTML = '<i class="bi bi-camera me-2"></i>Recapture';
                    captureButton.classList.remove('btn-success');
                    captureButton.classList.add('btn-primary');
                }, 2000);
            });

            // Clean up webcam when leaving page
            window.addEventListener('beforeunload', () => {
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                }
            });

            // Initialize webcam
            startWebcam();
        });

        // Preview uploaded photo
        function previewPhoto(input) {
            const preview = document.getElementById('photoPreview');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    if (preview.tagName === 'IMG') {
                        preview.src = e.target.result;
                    } else {
                        // Replace the div with an img element
                        const newPreview = document.createElement('img');
                        newPreview.id = 'photoPreview';
                        newPreview.className = 'user-photo-preview mb-2';
                        newPreview.src = e.target.result;
                        preview.parentNode.replaceChild(newPreview, preview);
                    }
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>

<?php
$conn->close();
?>
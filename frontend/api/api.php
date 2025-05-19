<?php
header('Content-Type: application/json');
include('database/db_config.php');

// Security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['status' => 'error', 'message' => 'Only POST requests allowed']));
}

// Get the action
$action = $_POST['action'] ?? '';
$response = [];

try {
    switch ($action) {
        case 'recognize':
            // Handle face recognition
            if (empty($_FILES['face_image'])) {
                throw new Exception("No image uploaded", 400);
            }

            $image = $_FILES['face_image'];
            if ($image['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Image upload error", 400);
            }

            // Save the image temporarily
            $tempPath = 'temp/' . uniqid() . '.jpg';
            if (!move_uploaded_file($image['tmp_name'], $tempPath)) {
                throw new Exception("Failed to process image", 500);
            }

            // Call Python script
            $command = escapeshellcmd("python face_recognition.py " . escapeshellarg($tempPath));
            $output = shell_exec($command);
            $result = json_decode($output, true);

            if (json_last_error() !== JSON_ERROR_NONE || !isset($result['status'])) {
                throw new Exception("Recognition failed", 500);
            }

            // Handle recognition result
            if ($result['status'] === 'success') {
                $user_id = $result['user_id'];
                
                // Get user details
                $stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();

                if ($user) {
                    // Mark attendance
                    $stmt = $conn->prepare(
                        "INSERT INTO attendance (user_id, name, status, date) 
                         VALUES (?, ?, 'Present', NOW())"
                    );
                    $stmt->bind_param("is", $user_id, $user['name']);
                    $stmt->execute();

                    $response = [
                        'status' => 'success',
                        'user_id' => $user_id,
                        'name' => $user['name']
                    ];
                } else {
                    throw new Exception("User not found", 404);
                }
            } else {
                $response = [
                    'status' => 'not_found',
                    'message' => 'No matching face found'
                ];
            }
            
            // Clean up temp file
            unlink($tempPath);
            break;

        case 'refresh':
            // Refresh face dataset
            $output = shell_exec("python face_recognition.py --refresh");
            $response = [
                'status' => 'success',
                'message' => 'Face dataset refreshed'
            ];
            break;

        default:
            throw new Exception("Invalid action", 400);
    }
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    $response = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
}

echo json_encode($response);
?>
<?php
session_start();
include 'connect.php';
include 'auth_check.php';
requireLogin();

// Determine if this is JSON or form data
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$isJson = strpos($contentType, 'application/json') !== false;
$data = [];

if ($isJson) {
    // Handle JSON input (API calls)
    header('Content-Type: application/json');
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid input data']);
        exit();
    }
} else {
    // Handle form data (POST from profile.php)
    $data = [
        'id' => $_SESSION['user_id'],
        'fname' => $_POST['first_name'] ?? '',
        'mname' => $_POST['middle_name'] ?? '',
        'lname' => $_POST['last_name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'position' => $_SESSION['user_type'] === 'teacher' ? 'Teacher' : 'Admin',
        'password' => $_POST['new_password'] ?? '',
        'imageData' => $_FILES['profile_image'] ?? null
    ];
}

// Validate required fields
$teacherId = isset($data['id']) ? (int)$data['id'] : 0;
$fname = isset($data['fname']) ? trim($data['fname']) : '';
$mname = isset($data['mname']) ? trim($data['mname']) : '';
$lname = isset($data['lname']) ? trim($data['lname']) : '';
$email = isset($data['email']) ? trim($data['email']) : '';
$position = isset($data['position']) ? trim($data['position']) : 'Teacher';
$password = isset($data['password']) ? trim($data['password']) : '';

if ($teacherId <= 0) {
    if ($isJson) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    } else {
        header('Location: profile.php?error=invalid_id');
    }
    exit();
}

if (empty($fname) || empty($lname) || empty($email)) {
    if ($isJson) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
    } else {
        header('Location: profile.php?error=missing_fields');
    }
    exit();
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    if ($isJson) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    } else {
        header('Location: profile.php?error=invalid_email');
    }
    exit();
}

// Check if email already exists for other users
$checkEmail = $conn->prepare("SELECT id FROM teacher WHERE email = ? AND id != ?");
if (!$checkEmail) {
    if ($isJson) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    } else {
        header('Location: profile.php?error=db_error');
    }
    exit();
}
$checkEmail->bind_param("si", $email, $teacherId);
if (!$checkEmail->execute()) {
    if ($isJson) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Execute error: ' . $checkEmail->error]);
    } else {
        header('Location: profile.php?error=db_error');
    }
    exit();
}
$result = $checkEmail->get_result();
if ($result->num_rows > 0) {
    if ($isJson) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
    } else {
        header('Location: profile.php?error=email_exists');
    }
    $checkEmail->close();
    exit();
}
$checkEmail->close();

// Get current user data
$getCurrentData = $conn->prepare("SELECT image, password FROM teacher WHERE id = ?");
if (!$getCurrentData) {
    if ($isJson) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    } else {
        header('Location: profile.php?error=db_error');
    }
    exit();
}
$getCurrentData->bind_param("i", $teacherId);
if (!$getCurrentData->execute()) {
    if ($isJson) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Execute error: ' . $getCurrentData->error]);
    } else {
        header('Location: profile.php?error=db_error');
    }
    exit();
}
$currentData = $getCurrentData->get_result()->fetch_assoc();
$getCurrentData->close();
if (!$currentData) {
    if ($isJson) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
    } else {
        header('Location: profile.php?error=not_found');
    }
    exit();
}

// Handle image upload
$imagePath = $currentData['image'];
$imageChanged = false;

// Handle JSON base64 image data
if ($isJson && isset($data['imageData']) && !empty($data['imageData'])) {
    $imageData = $data['imageData'];
    
    if (strpos($imageData, 'data:image') === 0) {
        // Decode base64 image
        if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $matches)) {
            $ext = $matches[1];
            if (!in_array($ext, ['jpeg', 'png', 'gif', 'jpg'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid image type']);
                exit();
            }
            
            // Convert jpeg to jpg if needed
            if ($ext === 'jpeg') $ext = 'jpg';
            
            $imageDataBase64 = substr($imageData, strpos($imageData, ',') + 1);
            $decodedImage = base64_decode($imageDataBase64);
            
            if ($decodedImage === false) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Failed to decode image']);
                exit();
            }
            
            // Delete old image if not default
            if ($currentData['image'] && $currentData['image'] !== 'image/default.jpg' && file_exists($currentData['image'])) {
                @unlink($currentData['image']);
            }
            
            // Create image directory if not exists
            if (!is_dir('image')) {
                mkdir('image', 0755, true);
            }
            
            // Generate unique filename
            $filename = date('dmYHis') . '_' . uniqid() . '.' . $ext;
            $imagePath = 'image/' . $filename;
            
            // Save image
            if (file_put_contents($imagePath, $decodedImage) === false) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to save image']);
                exit();
            }
            
            $imageChanged = true;
        }
    }
} else if (!$isJson && !empty($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
    // Handle form file upload
    $file = $_FILES['profile_image'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
        if ($isJson) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid image type']);
        } else {
            header('Location: profile.php?error=invalid_image');
        }
        exit();
    }
    $decodedImage = file_get_contents($file['tmp_name']);
    if ($decodedImage === false) {
        if ($isJson) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Failed to read image']);
        } else {
            header('Location: profile.php?error=image_read');
        }
        exit();
    }
    // Delete old image if not default
    if ($currentData['image'] !== 'image/default.jpg' && file_exists($currentData['image'])) {
        @unlink($currentData['image']);
    }
    // Generate unique filename
    $filename = date('dmYHis') . '_' . uniqid() . '.' . $ext;
    $imagePath = 'image/' . $filename;
    // Save image
    if (!move_uploaded_file($file['tmp_name'], $imagePath)) {
        if ($isJson) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to save image']);
        } else {
            header('Location: profile.php?error=image_save');
        }
        exit();
    }
    $imageChanged = true;
}

// Determine password to use
if (!empty($password)) {
    $currentPassword = $currentData['password'];
    if (substr($currentPassword, 0, 4) === '$2y$' || substr($currentPassword, 0, 4) === '$2a$') {
        $finalPassword = password_hash($password, PASSWORD_DEFAULT);
    } else {
        $finalPassword = $password;
    }
} else {
    $finalPassword = $currentData['password'];
}

// Update user in database
$stmt = $conn->prepare("UPDATE teacher SET fname = ?, mname = ?, lname = ?, email = ?, password = ?, image = ? WHERE id = ?");
if (!$stmt) {
    if ($isJson) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Prepare error: ' . $conn->error]);
    } else {
        header('Location: profile.php?error=db_prepare');
    }
    exit();
}
$stmt->bind_param("ssssssi", $fname, $mname, $lname, $email, $finalPassword, $imagePath, $teacherId);
if (!$stmt->execute()) {
    if ($isJson) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Execute error: ' . $stmt->error]);
    } else {
        header('Location: profile.php?error=execute_failed');
    }
    $stmt->close();
    exit();
}

// Check if any rows were affected
$affectedRows = $stmt->affected_rows;
$stmt->close();
if ($affectedRows > 0) {
    // Log activity
    $adminId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;
    $description = "Edit User (ID: $teacherId, Name: $fname $lname)";
    $activityStmt = $conn->prepare("INSERT INTO activity (aid, description, date) VALUES (?, ?, NOW())");
    if ($activityStmt) {
        $activityStmt->bind_param("is", $adminId, $description);
        $activityStmt->execute();
        $activityStmt->close();
    }
    if ($isJson) {
        echo json_encode([
            'success' => true,
            'message' => 'User updated successfully',
            'imageChanged' => $imageChanged
        ]);
    } else {
        header('Location: profile.php?success=1');
    }
} else {
    if ($isJson) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'No rows were updated'
        ]);
    } else {
        header('Location: profile.php?error=no_update');
    }
}

// Close database connection
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>

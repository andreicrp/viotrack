<?php
/**
 * get-student.php - Fetch a single student's complete data
 */

session_start();

// Require login
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

header('Content-Type: application/json');

try {
    require_once '../connect.php';

    // Get student ID from query parameter
    $student_id = isset($_GET['id']) ? intval($_GET['id']) : null;

    if (!$student_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Student ID is required'
        ]);
        exit;
    }

    // Fetch complete student data
    $query = "SELECT 
        id,
        fname,
        mname,
        lname,
        lrn,
        email,
        gender,
        grade,
        section,
        academicyear,
        guardian,
        guardiancontact,
        image
    FROM student 
    WHERE id = ?";

    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $student_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Determine avatar path
        $avatar = 'image/default.png';
        
        if (!empty($row['image']) && $row['image'] != 'image/default.png' && $row['image'] != 'image/default.jpg') {
            if (strpos($row['image'], 'http') === 0 || file_exists('../' . $row['image'])) {
                $avatar = $row['image'];
            } else {
                if (file_exists('../image/default.png')) {
                    $avatar = 'image/default.png';
                } else {
                    $nameParts = explode(' ', trim($row['fname'] . ' ' . $row['lname']));
                    $initials = '';
                    if (count($nameParts) >= 2) {
                        $initials = substr($nameParts[0], 0, 1) . substr($nameParts[count($nameParts)-1], 0, 1);
                    } else {
                        $initials = substr($nameParts[0], 0, 2);
                    }
                    $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($initials) . '&background=7c3aed&color=fff';
                }
            }
        } else if (file_exists('../image/default.png')) {
            $avatar = 'image/default.png';
        } else {
            $nameParts = explode(' ', trim($row['fname'] . ' ' . $row['lname']));
            $initials = '';
            if (count($nameParts) >= 2) {
                $initials = substr($nameParts[0], 0, 1) . substr($nameParts[count($nameParts)-1], 0, 1);
            } else {
                $initials = substr($nameParts[0], 0, 2);
            }
            $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($initials) . '&background=7c3aed&color=fff';
        }

        echo json_encode([
            'success' => true,
            'student' => [
                'id' => $row['id'],
                'fname' => $row['fname'],
                'mname' => $row['mname'] || '',
                'lname' => $row['lname'],
                'lrn' => $row['lrn'],
                'email' => $row['email'],
                'gender' => $row['gender'],
                'grade' => $row['grade'],
                'section' => $row['section'],
                'academicyear' => $row['academicyear'],
                'guardian' => $row['guardian'],
                'guardiancontact' => $row['guardiancontact'],
                'avatar' => $avatar
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Student not found'
        ]);
    }

    $stmt->close();

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

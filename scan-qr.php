<?php
// Set timezone first
date_default_timezone_set('Asia/Manila');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once 'connect.php';

// Get the student ID and token from URL parameters
$studentId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

// If no student ID or token provided, redirect to home
if ($studentId === 0 || empty($token)) {
    header('Location: dashboard.php');
    exit();
}

// Check if user is logged in FIRST
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    // Not logged in - redirect to login page with return URL and preserve token
    // Store QR info for after login
    $_SESSION['qr_student_id'] = $studentId;
    $_SESSION['qr_token'] = $token;
    
    // Redirect to login with proper return URL
    $returnUrl = urlencode('scan-qr.php?id=' . $studentId . '&token=' . urlencode($token));
    header('Location: login.php?return=' . $returnUrl . '&error=not_logged_in');
    exit();
}

// User is logged in - now validate the token
$stmt = $conn->prepare("SELECT id, student_id FROM qr_tokens WHERE student_id = ? AND token = ? AND created_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE) LIMIT 1");

if (!$stmt) {
    // Table might not exist - proceed anyway (will handle in scan page)
    error_log("QR Token validation error: " . $conn->error);
} else {
    $stmt->bind_param("is", $studentId, $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // Invalid token or expired
        $stmt->close();
        $conn->close();
        header('Location: adminstudentviolation.php?error=invalid_or_expired_qr');
        exit();
    }

    $stmt->close();
}

// Verify the student exists
$studentStmt = $conn->prepare("SELECT id FROM student WHERE id = ? LIMIT 1");
$studentStmt->bind_param("i", $studentId);
$studentStmt->execute();
$studentResult = $studentStmt->get_result();

if ($studentResult->num_rows === 0) {
    $studentStmt->close();
    $conn->close();
    header('Location: students.php?error=student_not_found');
    exit();
}

$studentStmt->close();
$conn->close();

// Show location capture page
?>
<!DOCTYPE html>
<html>
<head>
    <title>Capturing Location...</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #27367f 0%, #1a2557 50%, #0f1d3d 100%);
            overflow: hidden;
        }

        .capture-container {
            background: white;
            padding: 60px 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(39, 54, 127, 0.3);
            text-align: center;
            max-width: 450px;
            animation: slideUp 0.6s ease-out;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .icon-wrapper {
            width: 80px;
            height: 80px;
            margin: 0 auto 30px;
            background: linear-gradient(135deg, #27367f 0%, #1a2557 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(39, 54, 127, 0.2);
        }

        .spinner {
            border: 4px solid rgba(255, 255, 255, 0.15);
            border-top: 4px solid #fff;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            animation: spin 1.2s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }

        h2 {
            color: #27367f;
            margin: 0 0 12px 0;
            font-size: 26px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        p {
            color: #6b7280;
            margin: 0 0 24px 0;
            font-size: 15px;
            line-height: 1.6;
            font-weight: 500;
        }

        .status {
            margin-top: 24px;
            padding: 14px 16px;
            border-radius: 12px;
            background: #f3f4f6;
            color: #6b7280;
            font-size: 13px;
            font-weight: 500;
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
            min-height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .error {
            color: #b91c1c;
            background: #fef2f2;
            border: 1px solid #fecaca;
        }

        .success {
            color: #15803d;
            background: #f0fdf4;
            border: 1px solid #86efac;
        }

        /* Mobile responsiveness */
        @media (max-width: 480px) {
            .capture-container {
                padding: 40px 24px;
                max-width: 90%;
                border-radius: 16px;
            }

            h2 {
                font-size: 22px;
            }

            p {
                font-size: 14px;
            }

            .icon-wrapper {
                width: 70px;
                height: 70px;
                margin: 0 auto 24px;
            }

            .spinner {
                width: 50px;
                height: 50px;
            }
        }
    </style>
</head>
<body>
    <div class="capture-container">
        <div class="icon-wrapper">
            <div class="spinner"></div>
        </div>
        <h2>Capturing Location</h2>
        <p>Please allow access to your device location</p>
        <div class="status" id="status">Requesting location permission...</div>
    </div>

    <script>
        // Student ID from PHP
        var studentId = <?php echo $studentId; ?>;
        
        // Function to set cookie
        function setCookie(name, value, minutesToExpire = 30) {
            const expiryTime = new Date();
            expiryTime.setTime(expiryTime.getTime() + (minutesToExpire * 60 * 1000));
            document.cookie = name + "=" + encodeURIComponent(value) + "; expires=" + expiryTime.toUTCString() + "; path=/";
            console.log('Cookie set:', name, '=', value);
        }
        
        // Function to show alert for location permission
        function showLocationAlert() {
            return confirm('📍 Location Permission Required\n\nThis system needs your device location to track where violations were recorded.\n\nPlease click OK to allow access to your location.');
        }
        
        // Attempt to capture location
        function captureLocationFromQR() {
            const statusDiv = document.getElementById('status');
            
            if ("geolocation" in navigator) {
                // Show permission alert to user
                if (!showLocationAlert()) {
                    console.warn('User denied location permission');
                    
                    // Use default location (Manila)
                    setCookie('user_lat', '14.6124466');
                    setCookie('user_lng', '120.9879835');
                    setCookie('user_accuracy', '0');
                    
                    // Update status
                    statusDiv.textContent = '⚠️ Location permission denied. Using default location (Manila)...';
                    statusDiv.className = 'status error';
                    
                    // Redirect after 2 seconds
                    setTimeout(function() {
                        window.location.href = 'adminstudentviolation.php?id=' + studentId;
                    }, 2000);
                    return;
                }
                
                statusDiv.textContent = '⏳ Requesting your location...';
                
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        const accuracy = Math.round(position.coords.accuracy);
                        
                        console.log('Location captured:', lat, lng, 'Accuracy:', accuracy + 'm');
                        
                        // Store in cookies
                        setCookie('user_lat', lat.toString());
                        setCookie('user_lng', lng.toString());
                        setCookie('user_accuracy', accuracy.toString());
                        
                        // Update status
                        statusDiv.textContent = '✅ Location captured successfully! Accuracy: ' + accuracy + 'm. Redirecting...';
                        statusDiv.className = 'status success';
                        
                        // Redirect to student violation page after 1 second
                        setTimeout(function() {
                            window.location.href = 'adminstudentviolation.php?id=' + studentId;
                        }, 1000);
                    },
                    function(error) {
                        console.warn('Geolocation error:', error.message, 'Code:', error.code);
                        
                        // Use default location (Manila)
                        setCookie('user_lat', '14.6124466');
                        setCookie('user_lng', '120.9879835');
                        setCookie('user_accuracy', '0');
                        
                        // Update status based on error type
                        let errorMsg = 'Location error. ';
                        if (error.code === error.PERMISSION_DENIED) {
                            errorMsg += 'Permission denied. Using default location...';
                        } else if (error.code === error.POSITION_UNAVAILABLE) {
                            errorMsg += 'Location information unavailable. Using default location...';
                        } else if (error.code === error.TIMEOUT) {
                            errorMsg += 'Location request timed out. Using default location...';
                        } else {
                            errorMsg += 'Using default location...';
                        }
                        
                        statusDiv.textContent = '⚠️ ' + errorMsg;
                        statusDiv.className = 'status error';
                        
                        console.log('Using default location (Manila)');
                        
                        // Redirect after 2 seconds
                        setTimeout(function() {
                            window.location.href = 'adminstudentviolation.php?id=' + studentId;
                        }, 2000);
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 15000,  // 15 second timeout
                        maximumAge: 0
                    }
                );
            } else {
                console.log('Geolocation not supported by browser');
                
                // Use default location
                setCookie('user_lat', '14.6124466');
                setCookie('user_lng', '120.9879835');
                setCookie('user_accuracy', '0');
                
                // Update status
                statusDiv.textContent = '❌ Geolocation not supported by your browser. Using default location (Manila)...';
                statusDiv.className = 'status error';
                
                // Redirect after 2 seconds
                setTimeout(function() {
                    window.location.href = 'adminstudentviolation.php?id=' + studentId;
                }, 2000);
            }
        }
        
        // Start capturing location when page loads
        window.addEventListener('load', function() {
            console.log('Page loaded, starting location capture...');
            console.log('Online status:', navigator.onLine ? '🟢 ONLINE' : '🔴 OFFLINE');
            captureLocationFromQR();
        });
        
        // Listen for offline event
        window.addEventListener('offline', function() {
            console.log('📴 Connection lost - User is now offline');
            const statusDiv = document.getElementById('locationStatus');
            if (statusDiv) {
                statusDiv.textContent = '📴 OFFLINE MODE: Location will be recorded when you add the violation';
                statusDiv.className = 'status warning';
            }
        });
        
        // Listen for online event
        window.addEventListener('online', function() {
            console.log('📡 Connection restored');
            const statusDiv = document.getElementById('locationStatus');
            if (statusDiv) {
                statusDiv.textContent = '📡 Connection restored. Continue normally...';
                statusDiv.className = 'status success';
            }
        });
    </script>
</body>
</html>
<?php
?>

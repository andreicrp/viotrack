<?php
session_start();
include 'connect.php';

// Set security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    // Check if there's a return URL from QR code scan
    if (isset($_GET['return'])) {
        $redirectTo = urldecode($_GET['return']);
    } else {
        $redirectTo = 'dashboard.php';
    }
    header('Location: ' . $redirectTo);
    exit();
}

$error = '';
$success = '';
$return = isset($_GET['return']) ? htmlspecialchars($_GET['return'], ENT_QUOTES, 'UTF-8') : '';
$qrToken = isset($_GET['token']) ? htmlspecialchars($_GET['token'], ENT_QUOTES, 'UTF-8') : '';
$qrId = isset($_GET['qr_id']) ? intval($_GET['qr_id']) : 0;

// Check for saved email and password from cookies
$savedEmail = isset($_COOKIE['viotrack_email']) ? htmlspecialchars($_COOKIE['viotrack_email'], ENT_QUOTES, 'UTF-8') : '';
$savedPassword = isset($_COOKIE['viotrack_password']) ? htmlspecialchars($_COOKIE['viotrack_password'], ENT_QUOTES, 'UTF-8') : '';
$isRemembered = !empty($savedEmail) && !empty($savedPassword);

// Check if session expired
if (isset($_GET['session_expired']) && $_GET['session_expired'] == 1) {
    $error = 'Your session has expired. Please log in again.';
}

// Check if QR code is invalid
if (isset($_GET['error']) && $_GET['error'] === 'invalid_qr') {
    $error = 'Invalid QR code. Please try again.';
}

// Check if user logged out
if (isset($_GET['logged_out']) && $_GET['logged_out'] == 1) {
    $success = 'You have been logged out successfully.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid request. Please try again.';
    } else {
        // Sanitize and validate inputs
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $user_type = $_POST['user_type'] ?? '';
        $postQrToken = $_POST['qr_token'] ?? '';
        $postQrId = intval($_POST['qr_id'] ?? 0);
        $rememberMe = isset($_POST['remember']) ? true : false;
        
        // Validate email format
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address';
        } elseif (empty($password) || strlen($password) < 1) {
            $error = 'Please enter your password';
        } elseif (empty($user_type) || !in_array($user_type, ['admin', 'teacher'])) {
            $error = 'Please select a valid user type';
        } else {
            // Query both admin and teacher tables
            $user = null;
            $userTable = null;
            
            // First try admin table
            if ($user_type === 'admin') {
                $stmt = $conn->prepare("SELECT id, fname, lname, email, password, role FROM admin WHERE email = ? LIMIT 1");
                if ($stmt) {
                    $stmt->bind_param("s", $email);
                    if ($stmt->execute()) {
                        $result = $stmt->get_result();
                        if ($result->num_rows > 0) {
                            $user = $result->fetch_assoc();
                            $userTable = 'admin';
                        }
                    }
                    $stmt->close();
                }
            }
            
            // If not found in admin, try teacher table
            if (!$user) {
                $stmt = $conn->prepare("SELECT id, fname, lname, email, password, position FROM teacher WHERE email = ? LIMIT 1");
                if ($stmt) {
                    $stmt->bind_param("s", $email);
                    if ($stmt->execute()) {
                        $result = $stmt->get_result();
                        if ($result->num_rows > 0) {
                            $user = $result->fetch_assoc();
                            $userTable = 'teacher';
                        }
                    }
                    $stmt->close();
                }
            }
            
            if (!$user) {
                $error = 'Invalid email or password';
            } else {
                // Verify password - detect hashed vs plain text
                $passwordMatch = false;
                $storedPassword = $user['password'];
                
                // Check if password is hashed (bcrypt format: starts with $2y$ or $2a$)
                if (substr($storedPassword, 0, 4) === '$2y$' || substr($storedPassword, 0, 4) === '$2a$') {
                    // Password is hashed, use password_verify
                    if (password_verify($password, $storedPassword)) {
                        $passwordMatch = true;
                    }
                } else {
                    // Password is plain text, use direct comparison
                    if ($password === $storedPassword) {
                        $passwordMatch = true;
                    }
                }
                
                if ($passwordMatch) {
                    // Get user role/position
                    $userRole = strtolower($userTable === 'admin' ? ($user['role'] ?? 'admin') : $user['position']);
                    $requestedType = strtolower($user_type);
                    
                    // Validate role matches requested type
                    // For admins: accept 'admin', 'super admin', 'system admin', or empty (default to admin)
                    // For teachers: accept 'teacher', 'head teacher', 'department head'
                    $isValidAdmin = ($requestedType === 'admin' && (empty($userRole) || $userRole === 'admin' || $userRole === 'super admin' || $userRole === 'system admin'));
                    $isValidTeacher = ($requestedType === 'teacher' && in_array($userRole, ['teacher', 'head teacher', 'department head']));
                    
                    if ($isValidAdmin || $isValidTeacher) {
                        // Set session variables
                        $_SESSION['user_id'] = (int)$user['id'];
                        $_SESSION['user_name'] = htmlspecialchars(trim($user['fname'] . ' ' . $user['lname']), ENT_QUOTES, 'UTF-8');
                        $_SESSION['user_type'] = $requestedType;
                        $_SESSION['user_email'] = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
                        $_SESSION['session_start_time'] = time(); // Set session start time for timeout
                        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR']; // Store IP for additional validation
                        
                        // Regenerate session ID for security
                        session_regenerate_id(true);
                        
                        // Handle "Remember Me" - save email and password to cookies
                        if ($rememberMe) {
                            setcookie('viotrack_email', $email, time() + (30 * 24 * 60 * 60), '/');
                            setcookie('viotrack_password', $password, time() + (30 * 24 * 60 * 60), '/');
                        } else {
                            // Clear cookies if remember me is not checked
                            setcookie('viotrack_email', '', time() - 3600, '/');
                            setcookie('viotrack_password', '', time() - 3600, '/');
                        }
                        
                        // Check if QR token was provided - validate and use it
                        if (!empty($postQrToken) && $postQrId > 0) {
                            header('Location: scan-qr.php?id=' . $postQrId . '&token=' . urlencode($postQrToken));
                            exit();
                        }
                        
                        // Check if there's a return URL (from QR code scan)
                        if (isset($_POST['return']) && !empty($_POST['return'])) {
                            $redirectTo = urldecode($_POST['return']);
                        } else {
                            $redirectTo = 'dashboard.php';
                        }
                        header('Location: ' . $redirectTo);
                        exit();
                    } else {
                        $error = 'Your account type does not match the selected role';
                    }
                } else {
                    $error = 'Invalid email or password';
                }
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
    <title>Viotrack Admin - Login</title>
    <link rel="icon" type="image/x-icon" href="images/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-image: url('images/loginbg.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            display: flex;
            flex-direction: column;
            width: 100%;
            max-width: 480px;
            border-radius: 8px;
            overflow: visible;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            background: white;
            position: relative;
        }

        /* Logo at the top right inside the box */
        .login-image-side {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 80px;
            height: 80px;
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }

        .image-wrapper {
            position: relative;
            z-index: 1;
            text-align: center;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .school-logo {
            width: 85%;
            height: 85%;
            object-fit: contain;
        }

        /* Form Side */
        .login-form-side {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 40px 40px 40px;
            background: white;
            min-height: auto;
            width: 100%;
        }

        .login-box {
            width: 100%;
            max-width: 380px;
        }

        .login-box h1 {
            font-size: 32px;
            font-weight: 700;
            color: #27367f;
            text-align: left;
            margin-bottom: 5px;
            margin-top: 0;
        }

        .subtitle {
            font-size: 14px;
            color: #999;
            text-align: left;
            margin-bottom: 24px;
        }

        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 12px 14px;
            border-radius: 6px;
            margin-bottom: 22px;
            border-left: 4px solid #c62828;
            font-size: 13px;
        }

        .success-message {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 12px 14px;
            border-radius: 6px;
            margin-bottom: 22px;
            border-left: 4px solid #2e7d32;
            font-size: 13px;
        }

        .form-group {
            margin-bottom: 22px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
        }

        .user-type-selector {
            display: flex;
            border: 1.5px solid #e0e0e0;
            border-radius: 6px;
            overflow: hidden;
            background: #f9f9f9;
        }

        .user-type-option {
            flex: 1;
            display: flex;
            align-items: center;
        }

        .user-type-option input[type="radio"] {
            display: none;
        }

        .user-type-label {
            flex: 1;
            padding: 11px 12px;
            text-align: center;
            background: #f9f9f9;
            border-right: 1.5px solid #e0e0e0;
            cursor: pointer;
            font-weight: 500;
            color: #666;
            margin: 0;
            transition: all 0.2s;
        }

        .user-type-option:last-child .user-type-label {
            border-right: none;
        }

        .user-type-option input[type="radio"]:checked + .user-type-label {
            background: #27367f;
            color: white;
        }

        .form-input {
            width: 100%;
            padding: 11px 13px;
            border: 1px solid #999;
            border-radius: 4px;
            font-size: 14px;
            background: white;
            color: #333;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-input:focus {
            outline: none;
            border-color: #27367f;
            background: white;
            box-shadow: 0 0 0 3px rgba(39, 54, 127, 0.1);
        }

        .form-input::placeholder {
            color: #999;
            font-weight: 400;
        }

        .password-input-wrapper {
            position: relative;
        }

        .password-input-wrapper .form-input {
            padding-right: 38px;
        }

        .toggle-password {
            position: absolute;
            right: 13px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #999;
            cursor: pointer;
            font-size: 16px;
            padding: 5px;
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .checkbox-container {
            display: flex;
            align-items: center;
            cursor: pointer;
            color: #666;
            gap: 8px;
        }

        .checkbox-container input[type="checkbox"] {
            cursor: pointer;
            width: 16px;
            height: 16px;
            accent-color: #27367f;
        }

        .forgot-link {
            color: #27367f;
            text-decoration: none;
            font-weight: 500;
        }

        .btn-signin {
            width: 100%;
            padding: 13px 16px;
            background: #27367f;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: background 0.2s;
        }

        .btn-signin:hover {
            background: #1a2557;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .login-container {
                max-width: 100%;
            }
            
            .login-form-side {
                padding: 40px 30px;
                min-height: auto;
            }
            
            .login-box {
                max-width: 100%;
            }
            
            .login-box h1 {
                font-size: 26px;
            }
            
            .form-options {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            
            .login-image-side {
                width: 70px;
                height: 70px;
                top: 15px;
                right: 15px;
            }
        }

        @media (max-width: 480px) {
            .login-form-side {
                padding: 30px 20px;
            }
            
            .login-box h1 {
                font-size: 24px;
            }
            
            .subtitle {
                font-size: 13px;
            }
            
            .form-group {
                margin-bottom: 15px;
            }
            
            .btn-signin {
                padding: 10px;
                font-size: 13px;
            }
            
            .login-image-side {
                width: 60px;
                height: 60px;
                top: 12px;
                right: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- School Logo (Top Right) -->
        <div class="login-image-side">
            <div class="image-wrapper">
                <img src="images/phcm-logo.png" alt="School Logo" class="school-logo">
    <div class="login-brand-panel">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
        <div class="brand-content">
            <div class="brand-logo">
                <img src="images/phcm-logo.png" alt="PHCM Logo">
                <div class="brand-logo-text">VIOTRACK<span>Perpetual Help College</span></div>
            </div>
            <h1 class="brand-heading">Student<br><span class="grad">Violation</span><br>Tracker</h1>
            <p class="brand-sub">A smart, offline-capable system for tracking, managing, and resolving student violations with ease.</p>
            <div class="brand-features">
                <div class="brand-feature"><div class="brand-feature-icon"><i class="fas fa-wifi-slash"></i></div>Offline-first — works without internet</div>
                <div class="brand-feature"><div class="brand-feature-icon"><i class="fas fa-qrcode"></i></div>QR Code student identification</div>
                <div class="brand-feature"><div class="brand-feature-icon"><i class="fas fa-chart-bar"></i></div>Real-time analytics dashboard</div>
                <div class="brand-feature"><div class="brand-feature-icon"><i class="fas fa-shield-halved"></i></div>Role-based access control</div>
            </div>
        </div>
    </div>

    <div class="login-form-panel">
        <div class="login-box">
            <h1 class="login-title">Welcome back</h1>
            <p class="login-subtitle">Sign in to your VioTrack account to continue.</p>

            <?php if ($error): ?>
                <div class="alert alert-error"><i class="fas fa-circle-exclamation"></i><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><i class="fas fa-circle-check"></i><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php if ($return): ?><input type="hidden" name="return" value="<?php echo $return; ?>"><?php endif; ?>
                <?php if ($qrToken): ?>
                    <input type="hidden" name="qr_token" value="<?php echo $qrToken; ?>">
                    <input type="hidden" name="qr_id" value="<?php echo $qrId; ?>">
                <?php endif; ?>

                <div class="role-tabs">
                    <input type="radio" id="admin" name="user_type" value="admin" class="role-tab" checked>
                    <label for="admin" class="role-tab-label"><i class="fas fa-crown"></i> Admin</label>
                    <input type="radio" id="teacher" name="user_type" value="teacher" class="role-tab">
                    <label for="teacher" class="role-tab-label"><i class="fas fa-chalkboard-teacher"></i> Teacher</label>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <div class="input-wrapper">
                        <input type="email" id="email" name="email" class="form-input" placeholder="you@school.edu.ph" value="<?php echo $savedEmail; ?>" required>
                        <i class="fas fa-envelope input-icon"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" class="form-input" placeholder="Enter your password" value="<?php echo $savedPassword; ?>" required>
                        <i class="fas fa-lock input-icon"></i>
                        <button type="button" class="toggle-password" id="togglePassword"><i class="fas fa-eye"></i></button>
                    </div>
                </div>

                <div class="form-options">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember" <?php echo $isRemembered ? 'checked' : ''; ?>>
                        <span>Keep me signed in</span>
                    </label>
                    <a href="https://docs.google.com/forms/d/e/1FAIpQLSd7SN8jra5WfROhysYtjd80zMUSwSnxpcQ-a3d1bu8CiogDng/viewform" class="forgot-link" target="_blank" rel="noopener">Forgot password?</a>
                </div>

                <button type="submit" class="btn-signin" id="signInBtn">
                    <span class="btn-text"><i class="fas fa-arrow-right-to-bracket" style="margin-right:8px;"></i>Sign In</span>
                    <span class="spinner"></span>
                </button>
            </form>

            <p class="login-footer-note">
                Having trouble? Contact your <strong>system administrator</strong>.<br>
                &copy; <?php echo date('Y'); ?> Perpetual Help College of Manila
            </p>
        </div>
    </div>

    <script>
                    this.innerHTML = '<i class="fas fa-eye"></i>';
                }
            });
        }
    </script>
    
    <script></script>
</body>
</html>
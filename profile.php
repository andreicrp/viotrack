<?php
require_once 'auth_check.php';
requireLogin();
include 'header.php';
include 'sidebar.php';
// Database connection
require_once('connect.php');
if (!$conn) {
    die('Database connection failed');
}
// Fetch user profile from database based on logged-in user
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$userType = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'admin';
if (!$userId) {
    die("User not logged in.");
}

// Check both admin and teacher tables
$sql = null;
$tableSource = null;

// First try admin table
if ($userType === 'admin') {
    $sql = "SELECT id, fname, mname, lname, email, password, role as position, image
            FROM admin
            WHERE id = ?";
    $tableSource = 'admin';
}

// If no table selected yet, try teacher table
if (!$sql) {
    $sql = "SELECT id, fname, mname, lname, email, password, position, image
            FROM teacher
            WHERE id = ?";
    $tableSource = 'teacher';
}

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $profile = [
        'first_name'   => $row['fname'],
        'middle_name'  => $row['mname'] ?? '',
        'last_name'    => $row['lname'],
        'email'        => $row['email'],
        'role'         => $row['position'],
        'password'     => $row['password'],
        'avatar'       => !empty($row['image']) ? $row['image'] : 'image/default.jpg',
        'user_type'    => $userType,
        'table_source' => $tableSource
    ];
} else {
    die("User profile not found in database.");
}
?>
<main class="main-content">
    <div class="profile-wrapper">
        <!-- Profile Header Card -->
        <div class="profile-header-card">
            <div class="profile-banner"></div>
            <div class="profile-header-info">
                <div class="profile-avatar-section">
                    <img src="<?php echo htmlspecialchars($profile['avatar']); ?>"
                         alt="Profile Avatar"
                         class="profile-avatar"
                         id="profileImagePreview"
                         onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode(substr($profile['first_name'], 0, 1) . substr($profile['last_name'], 0, 1)); ?>&background=667eea&color=fff&size=200'">
                </div>
                <div class="profile-user-info">
                    <h1 class="profile-username"><?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?></h1>
                    <p class="profile-position"><?php echo htmlspecialchars($profile['role']); ?></p>
                    <p class="profile-user-email">
                        <i class="fas fa-envelope"></i>
                        <?php echo htmlspecialchars($profile['email']); ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Profile Form -->
        <form class="profile-form" id="profileForm" method="POST" enctype="multipart/form-data" action="php/update_teacher.php">
            
            <div class="form-grid">
                <!-- Left Column -->
                <div class="form-column">
                    <!-- Profile Picture Upload -->
                    <div class="form-section">
                        <div class="section-header">
                            <div class="section-icon">
                                <i class="fas fa-camera"></i>
                            </div>
                            <h3 class="section-title">Profile Picture</h3>
                        </div>
                        <div class="section-content">
                            <div class="upload-area">
                                <input type="file" id="profileImage" name="profile_image" accept="image/*" class="file-input-hidden">
                                <label for="profileImage" class="upload-label">
                                    <div class="upload-icon">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                    </div>
                                    <p class="upload-text">Click to upload</p>
                                    <p class="upload-hint">PNG, JPG, GIF up to 5MB</p>
                                    <span class="selected-file" id="fileName">No file chosen</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Basic Information -->
                    <div class="form-section">
                        <div class="section-header">
                            <div class="section-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <h3 class="section-title">Basic Information</h3>
                        </div>
                        <div class="section-content">
                            <div class="input-group">
                                <label class="input-label">First Name <span class="required-mark">*</span></label>
                                <input type="text" name="first_name" class="input-field" value="<?php echo htmlspecialchars($profile['first_name']); ?>" required>
                            </div>
                            <div class="input-group">
                                <label class="input-label">Middle Name</label>
                                <input type="text" name="middle_name" class="input-field" value="<?php echo htmlspecialchars($profile['middle_name']); ?>">
                            </div>
                            <div class="input-group">
                                <label class="input-label">Last Name <span class="required-mark">*</span></label>
                                <input type="text" name="last_name" class="input-field" value="<?php echo htmlspecialchars($profile['last_name']); ?>" required>
                            </div>
                            <div class="input-group">
                                <label class="input-label">Email Address <span class="required-mark">*</span></label>
                                <input type="email" name="email" class="input-field" value="<?php echo htmlspecialchars($profile['email']); ?>" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="form-column">
                    <!-- Security Settings -->
                    <div class="form-section">
                        <div class="section-header">
                            <div class="section-icon">
                                <i class="fas fa-lock"></i>
                            </div>
                            <h3 class="section-title">Security Settings</h3>
                        </div>
                        <div class="section-content">
                            <div class="info-message">
                                <i class="fas fa-info-circle"></i>
                                <span>Leave password fields blank if you don't want to change it.</span>
                            </div>
                            <div class="input-group">
                                <label class="input-label">New Password</label>
                                <div class="password-wrapper">
                                    <input type="password" name="new_password" class="input-field" id="newPassword" placeholder="Enter new password">
                                    <button type="button" class="password-toggle" onclick="togglePasswordVisibility('newPassword')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="input-group">
                                <label class="input-label">Confirm Password</label>
                                <div class="password-wrapper">
                                    <input type="password" name="confirm_password" class="input-field" id="confirmPassword" placeholder="Confirm new password">
                                    <button type="button" class="password-toggle" onclick="togglePasswordVisibility('confirmPassword')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="resetForm()">
                    <i class="fas fa-times"></i>
                    Cancel
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('profileImage');
    const fileName = document.getElementById('fileName');
    const profileImagePreview = document.getElementById('profileImagePreview');
    const profileForm = document.getElementById('profileForm');
    
    fileInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            fileName.textContent = this.files[0].name;
            const reader = new FileReader();
            reader.onload = function(e) {
                profileImagePreview.src = e.target.result;
            };
            reader.readAsDataURL(this.files[0]);
        } else {
            fileName.textContent = 'No file chosen';
        }
    });
    
    // Form submission validation
    profileForm.addEventListener('submit', function(e) {
        const newPassword = document.getElementById('newPassword').value.trim();
        const confirmPassword = document.getElementById('confirmPassword').value.trim();
        
        // If new password is provided, validate it
        if (newPassword) {
            // Check minimum length
            if (newPassword.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long');
                return;
            }
            
            // Check if passwords match
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('New password and confirm password do not match');
                return;
            }
        }
    });
});

function togglePasswordVisibility(fieldId) {
    const field = document.getElementById(fieldId);
    const btn = event.target.closest('.password-toggle');
    const icon = btn.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

function resetForm() {
    if (confirm('Are you sure you want to discard all changes?')) {
        location.reload();
    }
}
</script>

<?php include 'footer.php'; ?>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

.main-content {
    background: #f5f7fa;
    min-height: 100vh;
    padding: 30px 20px;
}

.profile-wrapper {
    max-width: 1200px;
    margin: 0 auto;
}

/* Profile Header Card */
.profile-header-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    margin-bottom: 30px;
}

.profile-banner {
    height: 190px;
    background-image: linear-gradient(135deg, rgba(39, 54, 127, 0.25) 0%, rgba(26, 37, 87, 0.25) 100%), 
                      url('images/profilelong.png');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    background-attachment: scroll;
    position: relative;
}

.profile-header-info {
    padding: 0 40px 30px;
    display: flex;
    align-items: flex-end;
    gap: 25px;
    margin-top: -30px;
    position: relative;
}

.profile-avatar-section {
    flex-shrink: 0;
}

.profile-avatar {
    width: 140px;
    height: 140px;
    border-radius: 50%;
    border: none;
    object-fit: cover;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    background: white;
}

.profile-user-info {
    padding-bottom: 10px;
}

.profile-username {
    font-size: 28px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 4px;
}

.profile-position {
    font-size: 16px;
    color: #27367f;
    font-weight: 600;
    margin-bottom: 8px;
    text-transform: capitalize;
}

.profile-user-email {
    font-size: 14px;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 8px;
}

.profile-user-email i {
    color: #94a3b8;
}

/* Form Styles */
.profile-form {
    background: white;
    border-radius: 16px;
    padding: 40px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 30px;
    margin-bottom: 30px;
}

.form-column {
    display: flex;
    flex-direction: column;
    gap: 30px;
}

.form-section {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
}

.section-header {
    background: #f8fafc;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    border-bottom: 1px solid #e2e8f0;
}

.section-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: linear-gradient(135deg, #27367f 0%, #1a2557 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 16px;
}

.section-title {
    font-size: 16px;
    font-weight: 700;
    color: #1e293b;
}

.section-content {
    padding: 24px 20px;
}

/* Upload Area */
.upload-area {
    width: 100%;
}

.file-input-hidden {
    display: none;
}

.upload-label {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    border: 2px dashed #cbd5e1;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    background: #f8fafc;
}

.upload-label:hover {
    border-color: #27367f;
    background: #f0f4ff;
}

.upload-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: #e8ebf7;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 12px;
}

.upload-icon i {
    font-size: 24px;
    color: #27367f;
}

.upload-text {
    font-size: 15px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 4px;
}

.upload-hint {
    font-size: 13px;
    color: #94a3b8;
    margin-bottom: 12px;
}

.selected-file {
    font-size: 12px;
    color: #64748b;
    padding: 6px 12px;
    background: white;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
}

/* Info Message */
.info-message {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    background: #eff6ff;
    border-left: 3px solid #3b82f6;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 13px;
    color: #1e40af;
}

.info-message i {
    color: #3b82f6;
}

/* Input Groups */
.input-group {
    margin-bottom: 20px;
}

.input-group:last-child {
    margin-bottom: 0;
}

.input-label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 8px;
}

.required-mark {
    color: #ef4444;
}

.input-field {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    transition: all 0.2s ease;
    background: white;
}

.input-field:focus {
    outline: none;
    border-color: #27367f;
    box-shadow: 0 0 0 3px rgba(39, 54, 127, 0.1);
}

.password-wrapper {
    position: relative;
}

.password-wrapper .input-field {
    padding-right: 45px;
}

.password-toggle {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    padding: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    transition: color 0.2s ease;
}

.password-toggle:hover {
    color: #27367f;
}

/* Form Actions */
.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
}

.btn {
    padding: 12px 28px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.btn-primary {
    background: linear-gradient(135deg, #27367f 0%, #1a2557 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(39, 54, 127, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(39, 54, 127, 0.4);
}

.btn-secondary {
    background: #f1f5f9;
    color: #64748b;
}

.btn-secondary:hover {
    background: #e2e8f0;
    color: #475569;
}

/* Responsive Design */
@media (max-width: 992px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .profile-header-info {
        flex-direction: column;
        align-items: center;
        text-align: center;
        padding: 0 20px 30px;
    }

    .profile-user-email {
        justify-content: center;
    }

    .profile-form {
        padding: 30px 20px;
    }

    .form-actions {
        flex-direction: column-reverse;
    }

    .btn {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .main-content {
        padding: 20px 10px;
    }

    .profile-banner {
        height: 120px;
    }

    .profile-avatar {
        width: 100px;
        height: 100px;
    }

    .profile-username {
        font-size: 22px;
    }

    .section-content {
        padding: 20px 16px;
    }
}
</style>
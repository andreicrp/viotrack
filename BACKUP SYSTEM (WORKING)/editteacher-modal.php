<!-- editteacher-modal.php -->
<!-- Edit Teacher Modal -->
<div class="edit-teacher-modal-overlay" id="editTeacherModal">
    <div class="edit-teacher-modal">
        <div class="edit-teacher-modal-header">
            <h2>Edit Teacher</h2>
            <button class="edit-teacher-modal-close" onclick="closeEditTeacherModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="edit-teacher-modal-body">
            <!-- Profile Image Section -->
            <div class="edit-teacher-profile-image-section">
                <div class="edit-teacher-profile-image-wrapper">
                    <img src="" alt="Teacher Profile" class="edit-teacher-profile-image" id="editTeacherProfileImage">
                </div>
                <div class="edit-teacher-file-input-wrapper">
                    <label for="editTeacherFileInput" class="edit-teacher-file-input-label">
                        <input type="file" id="editTeacherFileInput" class="edit-teacher-file-input" accept="image/*">
                        <span class="edit-teacher-file-btn">Choose File</span>
                        <span class="edit-teacher-file-name" id="editTeacherFileName">No file chosen</span>
                    </label>
                </div>
            </div>
            
            <!-- Edit Teacher Form -->
            <form class="edit-teacher-form" id="editTeacherForm">
                <input type="hidden" id="editTeacherId" name="teacher_id">
                
                <!-- First Name -->
                <div class="edit-teacher-form-group">
                    <label class="edit-teacher-form-label">
                        First Name <span class="edit-teacher-required">*</span>
                    </label>
                    <input type="text" class="edit-teacher-form-input" id="editTeacherFirstName" name="first_name" required>
                </div>
                
                <!-- Middle Name -->
                <div class="edit-teacher-form-group">
                    <label class="edit-teacher-form-label">Middle Name</label>
                    <input type="text" class="edit-teacher-form-input" id="editTeacherMiddleName" name="middle_name">
                </div>
                
                <!-- Last Name -->
                <div class="edit-teacher-form-group">
                    <label class="edit-teacher-form-label">
                        Last Name <span class="edit-teacher-required">*</span>
                    </label>
                    <input type="text" class="edit-teacher-form-input" id="editTeacherLastName" name="last_name" required>
                </div>
                
                <!-- Email Address -->
                <div class="edit-teacher-form-group full-width">
                    <label class="edit-teacher-form-label">
                        Email Address <span class="edit-teacher-required">*</span>
                    </label>
                    <input type="email" class="edit-teacher-form-input" id="editTeacherEmail" name="email" required>
                </div>
                
                <!-- Role -->
                <div class="edit-teacher-form-group">
                    <label class="edit-teacher-form-label">
                        Role <span class="edit-teacher-required">*</span>
                    </label>
                    <select class="edit-teacher-form-select" id="editTeacherRole" name="role" required>
                        <option value="">Select Role</option>
                        <option value="Teacher">Teacher</option>
                        <option value="Head Teacher">Head Teacher</option>
                        <option value="Department Head">Department Head</option>
                    </select>
                </div>
                
                <!-- Password (Editable) -->
                <div class="edit-teacher-form-group full-width">
                    <label class="edit-teacher-form-label">Password</label>
                    <div class="edit-teacher-password-wrapper">
                        <input type="password" class="edit-teacher-form-input" id="editTeacherPassword" name="password" placeholder="Leave blank to keep current password">
                        <button type="button" class="edit-teacher-show-password-btn" onclick="toggleEditTeacherPasswordVisibility(event)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="edit-teacher-modal-footer">
            <button class="edit-teacher-modal-btn btn-save-edit-teacher" onclick="saveEditTeacher()">
                Save
            </button>
            <button class="edit-teacher-modal-btn btn-cancel-edit-teacher" onclick="closeEditTeacherModal()">
                Close
            </button>
        </div>
    </div>
</div>

<script>
// Store current editing teacher data
let currentEditTeacherData = null;

// Open Edit Teacher Modal
function openEditTeacherModal(teacherData) {
    currentEditTeacherData = teacherData;
    
    // Set teacher ID
    document.getElementById('editTeacherId').value = teacherData.id;
    
    // Set profile image
    const defaultImage = 'https://ui-avatars.com/api/?name=' + encodeURIComponent((teacherData.fname + ' ' + teacherData.lname).trim()) + '&background=6366f1&color=fff&size=140';
    document.getElementById('editTeacherProfileImage').src = teacherData.image || defaultImage;
    
    // Populate form fields with correct field names
    document.getElementById('editTeacherFirstName').value = teacherData.fname || '';
    document.getElementById('editTeacherMiddleName').value = teacherData.mname || '';
    document.getElementById('editTeacherLastName').value = teacherData.lname || '';
    document.getElementById('editTeacherEmail').value = teacherData.email || '';
    document.getElementById('editTeacherRole').value = teacherData.position || '';
    document.getElementById('editTeacherPassword').value = '';
    document.getElementById('editTeacherPassword').placeholder = 'Leave blank to keep current password';
    
    // Reset file input
    document.getElementById('editTeacherFileInput').value = '';
    document.getElementById('editTeacherFileName').textContent = 'No file chosen';
    
    // Show modal
    document.getElementById('editTeacherModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

// Close Edit Teacher Modal
function closeEditTeacherModal() {
    document.getElementById('editTeacherModal').classList.remove('show');
    document.body.style.overflow = '';
    currentEditTeacherData = null;
    
    // Reset form
    document.getElementById('editTeacherForm').reset();
    document.getElementById('editTeacherFileName').textContent = 'No file chosen';
}

// Close modal when clicking overlay
document.addEventListener('DOMContentLoaded', function() {
    const editTeacherModal = document.getElementById('editTeacherModal');
    if (editTeacherModal) {
        editTeacherModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditTeacherModal();
            }
        });
    }
    
    // File input change handler
    const editTeacherFileInput = document.getElementById('editTeacherFileInput');
    if (editTeacherFileInput) {
        editTeacherFileInput.addEventListener('change', function() {
            const fileName = this.files[0]?.name || 'No file chosen';
            document.getElementById('editTeacherFileName').textContent = fileName;
            
            // Preview image
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('editTeacherProfileImage').src = e.target.result;
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('editTeacherModal');
        if (modal && modal.classList.contains('show')) {
            closeEditTeacherModal();
        }
    }
});

// Toggle password visibility
function toggleEditTeacherPasswordVisibility(event) {
    event.preventDefault();
    const passwordField = document.getElementById('editTeacherPassword');
    const btn = event.target.closest('.edit-teacher-show-password-btn');
    const icon = btn.querySelector('i');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordField.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Save Edit Teacher
function saveEditTeacher() {
    const form = document.getElementById('editTeacherForm');
    
    // Validate form
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Get form data
    const formData = new FormData(form);
    const profileImgSrc = document.getElementById('editTeacherProfileImage').src;
    
    // Validate password strength if provided
    const password = formData.get('password').trim();
    if (password.length > 0 && password.length < 6) {
        alert('Password must be at least 6 characters long');
        return;
    }
    
    // Only send image data if it's a new base64 image
    let imageData = null;
    if (profileImgSrc && profileImgSrc.startsWith('data:image')) {
        imageData = profileImgSrc;
    }
    
    const updatedData = {
        id: parseInt(document.getElementById('editTeacherId').value),
        fname: formData.get('first_name').trim(),
        mname: formData.get('middle_name').trim(),
        lname: formData.get('last_name').trim(),
        email: formData.get('email').trim(),
        position: formData.get('role'),
        password: password,
        imageData: imageData
    };
    
    // Disable submit button to prevent duplicate submissions
    const submitBtn = document.querySelector('.btn-save-edit-teacher');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Saving...';
    
    // Send data to server
    fetch('update_teacher.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(updatedData)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert('Teacher information updated successfully!');
            closeEditTeacherModal();
            location.reload();
        } else {
            alert('Error updating teacher: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the teacher. Please try again.');
    })
    .finally(() => {
        // Re-enable submit button
        submitBtn.disabled = false;
        submitBtn.textContent = 'Save';
    });
}
</script>

<style>
.edit-teacher-password-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.edit-teacher-password-wrapper .edit-teacher-form-input {
    flex: 1;
    padding-right: 40px;
}

.edit-teacher-show-password-btn {
    position: absolute;
    right: 12px;
    background: none;
    border: none;
    cursor: pointer;
    color: #64748b;
    font-size: 16px;
    padding: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color 0.2s ease;
}

.edit-teacher-show-password-btn:hover {
    color: #1e293b;
}

.edit-teacher-show-password-btn:focus {
    outline: none;
    color: #3b82f6;
}
</style>
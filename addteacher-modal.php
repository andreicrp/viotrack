<!-- addteacher-modal.php -->
<!-- Add Teacher Modal -->
<div class="add-teacher-modal-overlay" id="addTeacherModal">
    <div class="add-teacher-modal">
        <div class="add-teacher-modal-header">
            <h2>Add Teacher</h2>
            <button class="add-teacher-modal-close" onclick="closeAddTeacherModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="add-teacher-modal-body">
            <!-- Profile Image Section -->
            <div class="add-teacher-profile-image-section">
                <div class="add-teacher-profile-image-wrapper">
                    <img src="" alt="Teacher Profile" class="add-teacher-profile-image" id="addTeacherProfileImage" style="display: none;">
                    <i class="fas fa-user add-teacher-profile-placeholder" id="addTeacherProfilePlaceholder"></i>
                </div>
                <div class="add-teacher-file-input-wrapper">
                    <label for="addTeacherFileInput" class="add-teacher-file-input-label">
                        <input type="file" id="addTeacherFileInput" class="add-teacher-file-input" accept="image/*">
                        <span class="add-teacher-file-btn">Choose File</span>
                        <span class="add-teacher-file-name" id="addTeacherFileName">No file chosen</span>
                    </label>
                </div>
                <span style="font-size: 12px; color: #6b7280; margin-top: 8px;">Profile Image</span>
            </div>
            
            <!-- Add Teacher Form -->
            <form class="add-teacher-form" id="addTeacherForm">
                <!-- First Name -->
                <div class="add-teacher-form-group">
                    <label class="add-teacher-form-label">
                        First Name <span class="add-teacher-required">*</span>
                    </label>
                    <input type="text" class="add-teacher-form-input" id="addTeacherFirstName" name="first_name" placeholder="" required>
                </div>
                
                <!-- Middle Name -->
                <div class="add-teacher-form-group">
                    <label class="add-teacher-form-label">Middle Name</label>
                    <input type="text" class="add-teacher-form-input" id="addTeacherMiddleName" name="middle_name" placeholder="">
                </div>
                
                <!-- Last Name -->
                <div class="add-teacher-form-group">
                    <label class="add-teacher-form-label">
                        Last Name <span class="add-teacher-required">*</span>
                    </label>
                    <input type="text" class="add-teacher-form-input" id="addTeacherLastName" name="last_name" placeholder="" required>
                </div>
                
                <!-- Email Address -->
                <div class="add-teacher-form-group full-width">
                    <label class="add-teacher-form-label">
                        Email Address <span class="add-teacher-required">*</span>
                    </label>
                    <input type="email" class="add-teacher-form-input" id="addTeacherEmail" name="email" placeholder="" required>
                </div>
                
                <!-- Role -->
                <div class="add-teacher-form-group">
                    <label class="add-teacher-form-label">
                        Role <span class="add-teacher-required">*</span>
                    </label>
                    <select class="add-teacher-form-select" id="addTeacherRole" name="role" required>
                        <option value="Teacher">Teacher</option>
                        <option value="Head Teacher">Head Teacher</option>
                        <option value="Department Head">Department Head</option>
                    </select>
                </div>
                
                <!-- Password -->
                <div class="add-teacher-form-group">
                    <label class="add-teacher-form-label">
                        Password <span class="add-teacher-required">*</span>
                    </label>
                    <input type="text" class="add-teacher-form-input" id="addTeacherPassword" name="password" placeholder="" required>
                </div>
            </form>
        </div>
        
        <div class="add-teacher-modal-footer">
            <button class="add-teacher-modal-btn btn-add-teacher" onclick="saveAddTeacher()">
                Add
            </button>
            <button class="add-teacher-modal-btn btn-cancel-add-teacher" onclick="closeAddTeacherModal()">
                Close
            </button>
        </div>
    </div>
</div>

<script>
// Open Add Teacher Modal
function openAddTeacherModal() {
    const modal = document.getElementById('addTeacherModal');
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

// Close Add Teacher Modal
function closeAddTeacherModal() {
    const modal = document.getElementById('addTeacherModal');
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }
    resetAddTeacherForm();
}

// Reset Add Teacher Form
function resetAddTeacherForm() {
    document.getElementById('addTeacherForm').reset();
    document.getElementById('addTeacherFileInput').value = '';
    document.getElementById('addTeacherFileName').textContent = 'No file chosen';
    document.getElementById('addTeacherProfileImage').style.display = 'none';
    document.getElementById('addTeacherProfilePlaceholder').style.display = 'block';
}

// Handle File Selection for Add Teacher Modal
document.addEventListener('DOMContentLoaded', function() {
    const addTeacherFileInput = document.getElementById('addTeacherFileInput');
    if (addTeacherFileInput) {
        addTeacherFileInput.addEventListener('change', function() {
            const fileName = this.files[0]?.name || 'No file chosen';
            document.getElementById('addTeacherFileName').textContent = fileName;
            
            // Preview image
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.getElementById('addTeacherProfileImage');
                    const placeholder = document.getElementById('addTeacherProfilePlaceholder');
                    img.src = e.target.result;
                    img.style.display = 'block';
                    placeholder.style.display = 'none';
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
});

// Close modal when clicking overlay
document.addEventListener('DOMContentLoaded', function() {
    const addTeacherModal = document.getElementById('addTeacherModal');
    if (addTeacherModal) {
        addTeacherModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeAddTeacherModal();
            }
        });
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('addTeacherModal');
        if (modal && modal.classList.contains('show')) {
            closeAddTeacherModal();
        }
    }
});

// Save Add Teacher
function saveAddTeacher() {
    const form = document.getElementById('addTeacherForm');
    
    // Validate form
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Get form data
    const formData = new FormData(form);
    const profileImgSrc = document.getElementById('addTeacherProfileImage').src;
    
    // Validate password strength
    const password = formData.get('password').trim();
    if (password.length < 6) {
        alert('Password must be at least 6 characters long');
        return;
    }
    
    // Only send image data if it exists and is base64
    let imageData = null;
    if (profileImgSrc && profileImgSrc.startsWith('data:image')) {
        imageData = profileImgSrc;
    }
    
    const teacherData = {
        fname: formData.get('first_name').trim(),
        mname: formData.get('middle_name').trim(),
        lname: formData.get('last_name').trim(),
        email: formData.get('email').trim(),
        position: formData.get('role'),
        password: password,
        imageData: imageData
    };
    
    // Disable submit button to prevent duplicate submissions
    const submitBtn = document.querySelector('.btn-add-teacher');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Adding...';
    
    // Send data to server
    fetch('add_teacher.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(teacherData)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert('Teacher added successfully!');
            closeAddTeacherModal();
            location.reload();
        } else {
            alert('Error adding teacher: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while adding the teacher. Please try again.');
    })
    .finally(() => {
        // Re-enable submit button
        submitBtn.disabled = false;
        submitBtn.textContent = 'Add';
    });
}
</script>
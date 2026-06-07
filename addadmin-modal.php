<!-- addadmin-modal.php -->
<!-- Add Admin User Modal -->
<div class="add-admin-modal-overlay" id="addAdminModal">
    <div class="add-admin-modal">
        <div class="add-admin-modal-header">
            <h2>Add Admin User</h2>
            <button class="add-admin-modal-close" onclick="closeAddAdminModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="add-admin-modal-body">
            <!-- Profile Image Section -->
            <div class="add-admin-profile-image-section">
                <div class="add-admin-profile-image-wrapper">
                    <img src="" alt="Admin Profile" class="add-admin-profile-image" id="addAdminProfileImage" style="display: none;">
                    <i class="fas fa-user add-admin-profile-placeholder" id="addAdminProfilePlaceholder"></i>
                </div>
                <div class="add-admin-file-input-wrapper">
                    <label for="addAdminFileInput" class="add-admin-file-input-label">
                        <input type="file" id="addAdminFileInput" class="add-admin-file-input" accept="image/*">
                        <span class="add-admin-file-btn">Choose File</span>
                        <span class="add-admin-file-name" id="addAdminFileName">No file chosen</span>
                    </label>
                </div>
                <span style="font-size: 12px; color: #6b7280; margin-top: 8px;">Profile Image</span>
            </div>
            
            <!-- Add Admin Form -->
            <form class="add-admin-form" id="addAdminForm">
                <!-- First Name -->
                <div class="add-admin-form-group">
                    <label class="add-admin-form-label">
                        First Name <span class="add-admin-required">*</span>
                    </label>
                    <input type="text" class="add-admin-form-input" id="addAdminFirstName" name="first_name" placeholder="" required>
                </div>
                
                <!-- Middle Name -->
                <div class="add-admin-form-group">
                    <label class="add-admin-form-label">Middle Name</label>
                    <input type="text" class="add-admin-form-input" id="addAdminMiddleName" name="middle_name" placeholder="">
                </div>
                
                <!-- Last Name -->
                <div class="add-admin-form-group">
                    <label class="add-admin-form-label">
                        Last Name <span class="add-admin-required">*</span>
                    </label>
                    <input type="text" class="add-admin-form-input" id="addAdminLastName" name="last_name" placeholder="" required>
                </div>
                
                <!-- Email Address -->
                <div class="add-admin-form-group full-width">
                    <label class="add-admin-form-label">
                        Email Address <span class="add-admin-required">*</span>
                    </label>
                    <input type="email" class="add-admin-form-input" id="addAdminEmail" name="email" placeholder="" required>
                </div>
                
                <!-- Role -->
                <div class="add-admin-form-group">
                    <label class="add-admin-form-label">
                        Role <span class="add-admin-required">*</span>
                    </label>
                    <select class="add-admin-form-select" id="addAdminRole" name="role" required>
                        <option value="Admin">Admin</option>
                        <option value="Super Admin">Super Admin</option>
                        <option value="System Admin">System Admin</option>
                    </select>
                </div>
                
                <!-- Password -->
                <div class="add-admin-form-group full-width">
                    <label class="add-admin-form-label">
                        Password <span class="add-admin-required">*</span>
                    </label>
                    <div style="position: relative;">
                        <input type="password" class="add-admin-form-input" id="addAdminPassword" name="password" placeholder="" required style="padding-right: 44px;">
                        <button type="button" class="toggle-password-btn" onclick="toggleAddAdminPasswordVisibility()" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #6b7280; cursor: pointer; font-size: 16px; padding: 0; transition: color 0.2s; line-height: 1;" onmouseover="this.style.color='#374151'" onmouseout="this.style.color='#6b7280'">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="add-admin-modal-footer">
            <button class="add-admin-modal-btn btn-add-admin" onclick="saveAddAdmin()">
                Add
            </button>
            <button class="add-admin-modal-btn btn-cancel-add-admin" onclick="closeAddAdminModal()">
                Close
            </button>
        </div>
    </div>
</div>

<script>
// Open Add Admin Modal
function openAddAdminModal() {
    const modal = document.getElementById('addAdminModal');
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

// Toggle Password Visibility for Add Admin
function toggleAddAdminPasswordVisibility() {
    const input = document.getElementById('addAdminPassword');
    const btn = event.target.closest('button');
    const icon = btn.querySelector('i');

    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Close Add Admin Modal
function closeAddAdminModal() {
    const modal = document.getElementById('addAdminModal');
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }
    resetAddAdminForm();
}

// Reset Add Admin Form
function resetAddAdminForm() {
    document.getElementById('addAdminForm').reset();
    document.getElementById('addAdminFileInput').value = '';
    document.getElementById('addAdminFileName').textContent = 'No file chosen';
    document.getElementById('addAdminProfileImage').style.display = 'none';
    document.getElementById('addAdminProfilePlaceholder').style.display = 'block';
}

// Handle File Selection for Add Admin Modal
document.addEventListener('DOMContentLoaded', function() {
    const addAdminFileInput = document.getElementById('addAdminFileInput');
    if (addAdminFileInput) {
        addAdminFileInput.addEventListener('change', function() {
            const fileName = this.files[0]?.name || 'No file chosen';
            document.getElementById('addAdminFileName').textContent = fileName;
            
            // Preview image
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.getElementById('addAdminProfileImage');
                    const placeholder = document.getElementById('addAdminProfilePlaceholder');
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
    const addAdminModal = document.getElementById('addAdminModal');
    if (addAdminModal) {
        addAdminModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeAddAdminModal();
            }
        });
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('addAdminModal');
        if (modal && modal.classList.contains('show')) {
            closeAddAdminModal();
        }
    }
});

// Save Add Admin
function saveAddAdmin() {
    const form = document.getElementById('addAdminForm');
    
    // Validate form
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Get form data
    const formData = new FormData(form);
    const profileImgSrc = document.getElementById('addAdminProfileImage').src;
    
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
    
    const adminData = {
        fname: formData.get('first_name').trim(),
        mname: formData.get('middle_name').trim(),
        lname: formData.get('last_name').trim(),
        email: formData.get('email').trim(),
        role: formData.get('role'),
        password: password,
        imageData: imageData
    };
    
    // Disable submit button to prevent duplicate submissions
    const submitBtn = document.querySelector('.btn-add-admin');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Adding...';
    
    // Send data to server
    fetch('add_admin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(adminData)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert('Admin user added successfully!');
            closeAddAdminModal();
            location.reload();
        } else {
            alert('Error adding admin user: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while adding the admin user. Please try again.');
    })
    .finally(() => {
        // Re-enable submit button
        submitBtn.disabled = false;
        submitBtn.textContent = 'Add';
    });
}
</script>

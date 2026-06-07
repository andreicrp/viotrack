<!-- Edit Admin User Modal -->
<div id="editAdminModal" class="edit-admin-modal-overlay">
    <div class="edit-admin-modal">
        <div class="edit-admin-modal-header">
            <h2>
                <i class="fas fa-user-edit"></i> Edit Admin User
            </h2>
            <button type="button" class="edit-admin-modal-close" onclick="closeEditAdminModal()">×</button>
        </div>

        <form id="editAdminForm" class="edit-admin-form" onsubmit="submitEditAdminForm(event)">
            <div class="edit-admin-modal-body">
                <!-- Hidden Admin ID -->
                <input type="hidden" id="editAdminId" name="id">

                <!-- Profile Section -->
                <div class="edit-admin-profile-image-section">
                    <div class="edit-admin-profile-image-wrapper">
                        <img id="editAdminProfileImage" src="https://ui-avatars.com/api/?name=Admin&background=6366f1&color=fff&size=140" alt="Admin Profile" class="edit-admin-profile-image">
                    </div>
                    <label class="edit-admin-file-input-label" for="editAdminFileInput">
                        <span class="edit-admin-file-btn">
                            <i class="fas fa-cloud-upload-alt"></i> Upload Photo
                        </span>
                        <span id="editAdminFileName" class="edit-admin-file-name">No file chosen</span>
                    </label>
                    <input type="file" id="editAdminFileInput" class="edit-admin-file-input" accept="image/*" onchange="handleEditAdminImageUpload(event)">
                </div>

                <!-- Form Fields -->
                <div class="edit-admin-form-row">
                    <!-- First Name -->
                    <div class="edit-admin-form-group">
                        <label for="editAdminFirstName" class="edit-admin-form-label">
                            First Name
                            <span class="edit-admin-required">*</span>
                        </label>
                        <input type="text" id="editAdminFirstName" name="fname" required placeholder="Enter first name" class="edit-admin-form-input">
                    </div>

                    <!-- Middle Name -->
                    <div class="edit-admin-form-group">
                        <label for="editAdminMiddleName" class="edit-admin-form-label">Middle Name</label>
                        <input type="text" id="editAdminMiddleName" name="mname" placeholder="Enter middle name" class="edit-admin-form-input">
                    </div>
                </div>

                <div class="edit-admin-form-row">
                    <!-- Last Name -->
                    <div class="edit-admin-form-group">
                        <label for="editAdminLastName" class="edit-admin-form-label">
                            Last Name
                            <span class="edit-admin-required">*</span>
                        </label>
                        <input type="text" id="editAdminLastName" name="lname" required placeholder="Enter last name" class="edit-admin-form-input">
                    </div>

                    <!-- Email -->
                    <div class="edit-admin-form-group">
                        <label for="editAdminEmail" class="edit-admin-form-label">
                            Email Address
                            <span class="edit-admin-required">*</span>
                        </label>
                        <input type="email" id="editAdminEmail" name="email" required placeholder="Enter email address" class="edit-admin-form-input">
                    </div>
                </div>

                <div class="edit-admin-form-row">
                    <!-- Role -->
                    <div class="edit-admin-form-group">
                        <label for="editAdminRole" class="edit-admin-form-label">
                            Role
                            <span class="edit-admin-required">*</span>
                        </label>
                        <select id="editAdminRole" name="role" required class="edit-admin-form-select">
                            <option value="">Select a role</option>
                            <option value="Admin">Admin</option>
                            <option value="Super Admin">Super Admin</option>
                            <option value="System Admin">System Admin</option>
                        </select>
                    </div>

                    <!-- Password -->
                    <div class="edit-admin-form-group">
                        <label for="editAdminPassword" class="edit-admin-form-label">Password</label>
                        <div style="position: relative;">
                            <input type="password" id="editAdminPassword" name="password" placeholder="Leave blank to keep current password" class="edit-admin-form-input" style="padding-right: 44px;">
                            <button type="button" class="toggle-password-btn" onclick="toggleEditAdminPasswordVisibility()" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #64748b; cursor: pointer; font-size: 16px; padding: 0; transition: color 0.2s; line-height: 1;" onmouseover="this.style.color='#27367f'" onmouseout="this.style.color='#64748b'">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small style="font-size: 12px; color: #6b7280; margin-top: 6px; display: block;">Leave blank to keep the current password</small>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="edit-admin-modal-footer">
                <button type="button" class="edit-admin-modal-btn btn-cancel-edit-admin" onclick="closeEditAdminModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" class="edit-admin-modal-btn btn-save-edit-admin">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>


<script>
function handleEditAdminImageUpload(event) {
    const file = event.target.files[0];
    if (!file) return;

    const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
    if (!validTypes.includes(file.type)) {
        alert('Please select a valid image file (JPEG, PNG, GIF)');
        return;
    }

    if (file.size > 5 * 1024 * 1024) {
        alert('File size must not exceed 5MB');
        return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('editAdminProfileImage').src = e.target.result;
        document.getElementById('editAdminFileName').textContent = file.name;
        document.getElementById('editAdminFileName').style.color = '#10b981';
    };
    reader.readAsDataURL(file);
}

function toggleEditAdminPasswordVisibility() {
    const input = document.getElementById('editAdminPassword');
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

function submitEditAdminForm(event) {
    event.preventDefault();

    const adminId = document.getElementById('editAdminId').value;
    const fname = document.getElementById('editAdminFirstName').value.trim();
    const mname = document.getElementById('editAdminMiddleName').value.trim();
    const lname = document.getElementById('editAdminLastName').value.trim();
    const email = document.getElementById('editAdminEmail').value.trim();
    const role = document.getElementById('editAdminRole').value.trim();
    const password = document.getElementById('editAdminPassword').value.trim();

    // Validation
    if (!fname || !lname || !email || !role) {
        alert('Please fill in all required fields');
        return;
    }

    if (!email.includes('@')) {
        alert('Please enter a valid email address');
        return;
    }

    // Get image data if changed
    const fileInput = document.getElementById('editAdminFileInput');
    let imageData = null;

    if (fileInput.files && fileInput.files.length > 0) {
        const reader = new FileReader();
        reader.onload = function(e) {
            imageData = e.target.result;
            submitEditAdminData(adminId, fname, mname, lname, email, role, password, imageData);
        };
        reader.readAsDataURL(fileInput.files[0]);
    } else {
        submitEditAdminData(adminId, fname, mname, lname, email, role, password, null);
    }
}

function submitEditAdminData(adminId, fname, mname, lname, email, role, password, imageData) {
    const payload = {
        id: parseInt(adminId),
        fname: fname,
        mname: mname,
        lname: lname,
        email: email,
        role: role,
        password: password
    };

    // Only add imageData if it exists and is not null
    if (imageData && imageData !== null && imageData !== '') {
        payload.imageData = imageData;
    }

    console.log('Admin ID:', adminId);
    console.log('Name:', fname, mname, lname);
    console.log('Email:', email);
    console.log('Role:', role);
    console.log('Has image data:', !!payload.imageData);
    console.log('Payload keys:', Object.keys(payload));

    fetch('update_admin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload)
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.text().then(text => {
            console.log('Response text:', text.substring(0, 500));
            try {
                const data = JSON.parse(text);
                return { ok: response.ok, status: response.status, data: data };
            } catch (e) {
                console.error('Failed to parse JSON:', text);
                return { ok: response.ok, status: response.status, data: null, rawText: text };
            }
        });
    })
    .then(result => {
        console.log('Result:', result);
        
        if (!result.ok) {
            throw new Error(`HTTP error! status: ${result.status}`);
        }
        
        if (!result.data) {
            console.error('No JSON data received:', result.rawText);
            throw new Error('Invalid response from server');
        }
        
        if (result.data.success) {
            alert('Admin user updated successfully!');
            closeEditAdminModal();
            location.reload();
        } else {
            alert('Error: ' + (result.data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        console.error('Error stack:', error.stack);
        alert('An error occurred while updating the admin user. Please try again.');
    });
}

function closeEditAdminModal() {
    const modal = document.getElementById('editAdminModal');
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
        // Reset form
        document.getElementById('editAdminForm').reset();
        document.getElementById('editAdminFileName').textContent = 'No file chosen';
    }
}

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    const modal = document.getElementById('editAdminModal');
    if (event.target === modal) {
        closeEditAdminModal();
    }
});
</script>

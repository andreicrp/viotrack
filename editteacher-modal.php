<!-- Edit Teacher Modal -->
<div id="editTeacherModal" class="edit-teacher-modal-overlay">
    <div class="edit-teacher-modal">
        <div class="edit-teacher-modal-header">
            <h2>
                <i class="fas fa-user-edit"></i> Edit Teacher
            </h2>
            <button type="button" class="edit-teacher-modal-close" onclick="closeEditTeacherModal()">×</button>
        </div>

        <form id="editTeacherForm" class="edit-teacher-form" onsubmit="submitEditTeacherForm(event)">
            <div class="edit-teacher-modal-body">
                <!-- Hidden Teacher ID -->
                <input type="hidden" id="editTeacherId" name="id">

                <!-- Profile Section -->
                <div class="edit-teacher-profile-image-section">
                    <div class="edit-teacher-profile-image-wrapper">
                        <img id="editTeacherProfileImage" src="https://ui-avatars.com/api/?name=Teacher&background=6366f1&color=fff&size=140" alt="Teacher Profile" class="edit-teacher-profile-image">
                    </div>
                    <label class="edit-teacher-file-input-label" for="editTeacherFileInput">
                        <span class="edit-teacher-file-btn">
                            <i class="fas fa-cloud-upload-alt"></i> Upload Photo
                        </span>
                        <span id="editTeacherFileName" class="edit-teacher-file-name">No file chosen</span>
                    </label>
                    <input type="file" id="editTeacherFileInput" class="edit-teacher-file-input" accept="image/*" onchange="handleEditTeacherImageUpload(event)">
                </div>

                <!-- Form Fields -->
                <div class="edit-teacher-form-row">
                    <!-- First Name -->
                    <div class="edit-teacher-form-group">
                        <label for="editTeacherFirstName" class="edit-teacher-form-label">
                            First Name
                            <span class="edit-teacher-required">*</span>
                        </label>
                        <input type="text" id="editTeacherFirstName" name="fname" required placeholder="Enter first name" class="edit-teacher-form-input">
                    </div>

                    <!-- Middle Name -->
                    <div class="edit-teacher-form-group">
                        <label for="editTeacherMiddleName" class="edit-teacher-form-label">Middle Name</label>
                        <input type="text" id="editTeacherMiddleName" name="mname" placeholder="Enter middle name" class="edit-teacher-form-input">
                    </div>
                </div>

                <div class="edit-teacher-form-row">
                    <!-- Last Name -->
                    <div class="edit-teacher-form-group">
                        <label for="editTeacherLastName" class="edit-teacher-form-label">
                            Last Name
                            <span class="edit-teacher-required">*</span>
                        </label>
                        <input type="text" id="editTeacherLastName" name="lname" required placeholder="Enter last name" class="edit-teacher-form-input">
                    </div>

                    <!-- Email -->
                    <div class="edit-teacher-form-group">
                        <label for="editTeacherEmail" class="edit-teacher-form-label">
                            Email Address
                            <span class="edit-teacher-required">*</span>
                        </label>
                        <input type="email" id="editTeacherEmail" name="email" required placeholder="Enter email address" class="edit-teacher-form-input">
                    </div>
                </div>

                <div class="edit-teacher-form-row">
                    <!-- Position -->
                    <div class="edit-teacher-form-group">
                        <label for="editTeacherPosition" class="edit-teacher-form-label">
                            Position
                            <span class="edit-teacher-required">*</span>
                        </label>
                        <select id="editTeacherPosition" name="position" required class="edit-teacher-form-select">
                            <option value="">Select a position</option>
                            <option value="Teacher">Teacher</option>
                            <option value="Head Teacher">Head Teacher</option>
                            <option value="Department Head">Department Head</option>
                        </select>
                    </div>

                    <!-- Password -->
                    <div class="edit-teacher-form-group">
                        <label for="editTeacherPassword" class="edit-teacher-form-label">Password</label>
                        <div style="position: relative;">
                            <input type="password" id="editTeacherPassword" name="password" placeholder="Leave blank to keep current password" class="edit-teacher-form-input" style="padding-right: 44px;">
                            <button type="button" class="toggle-password-btn" onclick="toggleEditTeacherPasswordVisibility()" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #64748b; cursor: pointer; font-size: 16px; padding: 0; transition: color 0.2s; line-height: 1;" onmouseover="this.style.color='#27367f'" onmouseout="this.style.color='#64748b'">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small style="font-size: 12px; color: #6b7280; margin-top: 6px; display: block;">Leave blank to keep the current password</small>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="edit-teacher-modal-footer">
                <button type="button" class="edit-teacher-modal-btn btn-cancel-edit-teacher" onclick="closeEditTeacherModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" class="edit-teacher-modal-btn btn-save-edit-teacher">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function handleEditTeacherImageUpload(event) {
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
        document.getElementById('editTeacherProfileImage').src = e.target.result;
        document.getElementById('editTeacherFileName').textContent = file.name;
        document.getElementById('editTeacherFileName').style.color = '#10b981';
    };
    reader.readAsDataURL(file);
}

function toggleEditTeacherPasswordVisibility() {
    const input = document.getElementById('editTeacherPassword');
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

function submitEditTeacherForm(event) {
    event.preventDefault();

    const teacherId = document.getElementById('editTeacherId').value;
    const fname = document.getElementById('editTeacherFirstName').value.trim();
    const mname = document.getElementById('editTeacherMiddleName').value.trim();
    const lname = document.getElementById('editTeacherLastName').value.trim();
    const email = document.getElementById('editTeacherEmail').value.trim();
    const position = document.getElementById('editTeacherPosition').value.trim();
    const password = document.getElementById('editTeacherPassword').value.trim();

    // Validation
    if (!fname || !lname || !email || !position) {
        alert('Please fill in all required fields');
        return;
    }

    if (!email.includes('@')) {
        alert('Please enter a valid email address');
        return;
    }

    // Get image data if changed
    const fileInput = document.getElementById('editTeacherFileInput');
    let imageData = null;

    if (fileInput.files && fileInput.files.length > 0) {
        const reader = new FileReader();
        reader.onload = function(e) {
            imageData = e.target.result;
            submitEditTeacherData(teacherId, fname, mname, lname, email, position, password, imageData);
        };
        reader.readAsDataURL(fileInput.files[0]);
    } else {
        submitEditTeacherData(teacherId, fname, mname, lname, email, position, password, null);
    }
}

function submitEditTeacherData(teacherId, fname, mname, lname, email, position, password, imageData) {
    const payload = {
        id: parseInt(teacherId),
        fname: fname,
        mname: mname,
        lname: lname,
        email: email,
        position: position,
        password: password
    };

    if (imageData) {
        payload.imageData = imageData;
    }

    fetch('update_teacher.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert('Teacher updated successfully!');
            closeEditTeacherModal();
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the teacher. Please try again.');
    });
}

function openEditTeacherModal(teacherData) {
    const modal = document.getElementById('editTeacherModal');
    if (!modal) {
        console.error('Modal element not found!');
        return;
    }

    // Set teacher ID
    document.getElementById('editTeacherId').value = teacherData.id;
    
    // Set profile image
    const defaultImage = 'https://ui-avatars.com/api/?name=' + encodeURIComponent((teacherData.fname + ' ' + teacherData.lname).trim()) + '&background=6366f1&color=fff&size=140';
    document.getElementById('editTeacherProfileImage').src = teacherData.image || defaultImage;
    
    // Populate form fields
    document.getElementById('editTeacherFirstName').value = teacherData.fname || '';
    document.getElementById('editTeacherMiddleName').value = teacherData.mname || '';
    document.getElementById('editTeacherLastName').value = teacherData.lname || '';
    document.getElementById('editTeacherEmail').value = teacherData.email || '';
    const positionValue = teacherData.position || 'Teacher';
    document.getElementById('editTeacherPosition').value = positionValue;
    document.getElementById('editTeacherPassword').value = '';
    
    // Reset file input
    document.getElementById('editTeacherFileInput').value = '';
    document.getElementById('editTeacherFileName').textContent = 'No file chosen';
    
    // Show modal
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeEditTeacherModal() {
    const modal = document.getElementById('editTeacherModal');
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
        // Reset form
        document.getElementById('editTeacherForm').reset();
        document.getElementById('editTeacherFileName').textContent = 'No file chosen';
    }
}

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    const modal = document.getElementById('editTeacherModal');
    if (event.target === modal) {
        closeEditTeacherModal();
    }
});
</script>
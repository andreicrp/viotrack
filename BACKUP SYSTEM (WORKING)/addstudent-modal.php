<!-- addstudent-modal.php -->
<!-- Add Student Modal -->
<div class="add-modal-overlay" id="addStudentModal">
    <div class="add-student-modal">
        <div class="add-modal-header">
            <h2>Add Student</h2>
            <button class="add-modal-close" onclick="closeAddModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="add-modal-body">
            <!-- Profile Image Section -->
            <div class="add-profile-image-section">
                <div class="add-profile-image-wrapper">
                    <img src="image/default.png" alt="Student Profile" class="add-profile-image" id="addProfileImage" onerror="this.style.display='none'; document.getElementById('addProfilePlaceholder').style.display='flex';">
                    <i class="fas fa-user add-profile-placeholder" id="addProfilePlaceholder" style="display: none;"></i>
                </div>
                <div class="add-file-input-wrapper">
                    <label for="addFileInput" class="add-file-input-label">
                        <input type="file" id="addFileInput" name="image" class="add-file-input" accept="image/*">
                        <span class="add-file-btn">Choose File</span>
                        <span class="add-file-name" id="addFileName">No file chosen</span>
                    </label>
                </div>
                <span style="font-size: 12px; color: #6b7280; margin-top: 8px;">Profile Image (Optional - Default will be used if not provided)</span>
            </div>
            
            <!-- Add Student Form -->
            <form class="add-form" id="addStudentForm" enctype="multipart/form-data">
                <!-- First Name -->
                <div class="add-form-group">
                    <label class="add-form-label">
                        First Name <span class="add-required">*</span>
                    </label>
                    <input type="text" class="add-form-input" id="addFirstName" name="fname" required>
                </div>
                
                <!-- Middle Name -->
                <div class="add-form-group">
                    <label class="add-form-label">Middle Name</label>
                    <input type="text" class="add-form-input" id="addMiddleName" name="mname">
                </div>
                
                <!-- Last Name -->
                <div class="add-form-group">
                    <label class="add-form-label">
                        Last Name <span class="add-required">*</span>
                    </label>
                    <input type="text" class="add-form-input" id="addLastName" name="lname" required>
                </div>
                
                <!-- Student ID (LRN) -->
                <div class="add-form-group">
                    <label class="add-form-label">
                        Student ID (LRN) <span class="add-required">*</span>
                    </label>
                    <input type="text" class="add-form-input" id="addStudentId" name="lrn" placeholder="e.g., 22-0962-208" required>
                </div>
                
                <!-- Gender -->
                <div class="add-form-group">
                    <label class="add-form-label">
                        Gender <span class="add-required">*</span>
                    </label>
                    <select class="add-form-select" id="addGender" name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                
                <!-- Academic Year -->
                <div class="add-form-group">
                    <label class="add-form-label">
                        Academic Year <span class="add-required">*</span>
                    </label>
                    <input type="text" class="add-form-input" id="addAcademicYear" name="academicyear" placeholder="e.g., 2025-2026" value="2025-2026" required>
                </div>
                
                <!-- Grade Level -->
                <div class="add-form-group">
                    <label class="add-form-label">
                        Grade Level <span class="add-required">*</span>
                    </label>
                    <select class="add-form-select" id="addGradeLevel" name="grade" required>
                        <option value="">Select Grade</option>
                        <option value="Grade 7">Grade 7</option>
                        <option value="Grade 8">Grade 8</option>
                        <option value="Grade 9">Grade 9</option>
                        <option value="Grade 10">Grade 10</option>
                        <option value="Grade 11">Grade 11</option>
                        <option value="Grade 12">Grade 12</option>
                    </select>
                </div>
                
                <!-- Section -->
                <div class="add-form-group">
                    <label class="add-form-label">
                        Section <span class="add-required">*</span>
                    </label>
                    <input type="text" class="add-form-input" id="addSection" name="section" placeholder="e.g., Hope" required>
                </div>
                
                <!-- Guardian -->
                <div class="add-form-group">
                    <label class="add-form-label">
                        Guardian <span class="add-required">*</span>
                    </label>
                    <input type="text" class="add-form-input" id="addGuardian" name="guardian" required>
                </div>
                
                <!-- Guardian Contact Number -->
                <div class="add-form-group">
                    <label class="add-form-label">
                        Guardian Contact Number <span class="add-required">*</span>
                    </label>
                    <input type="text" class="add-form-input" id="addGuardianContact" name="guardiancontact" placeholder="e.g., 639123456789" required>
                </div>
                
                <!-- Email Address -->
                <div class="add-form-group full-width">
                    <label class="add-form-label">
                        Email Address <span class="add-required">*</span>
                    </label>
                    <input type="email" class="add-form-input" id="addEmail" name="email" required>
                </div>
            </form>
        </div>
        
        <div class="add-modal-footer">
            <button class="add-modal-btn btn-add-student" onclick="saveAddStudent()">
                Add
            </button>
            <button class="add-modal-btn btn-cancel-add" onclick="closeAddModal()">
                Close
            </button>
        </div>
    </div>
</div>

<script>
// Open Add Student Modal
function openAddModal() {
    document.getElementById('addStudentModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

// Close Add Student Modal
function closeAddModal() {
    document.getElementById('addStudentModal').classList.remove('show');
    document.body.style.overflow = '';
    resetAddForm();
}

// Reset Add Student Form
function resetAddForm() {
    document.getElementById('addStudentForm').reset();
    document.getElementById('addFileInput').value = '';
    document.getElementById('addFileName').textContent = 'No file chosen';
    
    // Reset to default image
    const img = document.getElementById('addProfileImage');
    const placeholder = document.getElementById('addProfilePlaceholder');
    img.src = 'image/default.png';
    img.style.display = 'block';
    placeholder.style.display = 'none';
    
    // If default image fails to load, show placeholder icon
    img.onerror = function() {
        img.style.display = 'none';
        placeholder.style.display = 'flex';
    };
}

// Handle File Selection for Add Modal
document.addEventListener('DOMContentLoaded', function() {
    const addFileInput = document.getElementById('addFileInput');
    if (addFileInput) {
        addFileInput.addEventListener('change', function() {
            const fileName = this.files[0]?.name || 'No file chosen';
            document.getElementById('addFileName').textContent = fileName;
            
            // Preview image
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.getElementById('addProfileImage');
                    const placeholder = document.getElementById('addProfilePlaceholder');
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
    const addModal = document.getElementById('addStudentModal');
    if (addModal) {
        addModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeAddModal();
            }
        });
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('addStudentModal')?.classList.contains('show')) {
        closeAddModal();
    }
});

// Save Add Student
function saveAddStudent() {
    const form = document.getElementById('addStudentForm');
    
    // Validate form
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Create FormData object
    const formData = new FormData(form);
    
    // Add file if selected
    const fileInput = document.getElementById('addFileInput');
    if (fileInput.files.length > 0) {
        formData.append('image', fileInput.files[0]);
    }
    
    // Show loading state
    const addBtn = document.querySelector('.btn-add-student');
    const originalText = addBtn.textContent;
    addBtn.disabled = true;
    addBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
    
    // Send to server
    fetch('add-student.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        addBtn.disabled = false;
        addBtn.textContent = originalText;
        
        if (data.success) {
            alert('Student added successfully!');
            closeAddModal();
            location.reload(); // Refresh to show new student
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        addBtn.disabled = false;
        addBtn.textContent = originalText;
        console.error('Error:', error);
        alert('Error adding student. Please try again.');
    });
}
</script>
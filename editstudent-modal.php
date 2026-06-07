    <!-- editstudent-modal.php -->
    <!-- Edit Student Modal -->
    <div class="edit-modal-overlay" id="editStudentModal">
        <div class="edit-student-modal">
            <div class="edit-modal-header">
                <h2>Edit Student</h2>
                <button class="edit-modal-close" onclick="closeEditModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="edit-modal-body">
                <!-- Profile Image Section -->
                <div class="edit-profile-image-section">
                    <div class="edit-profile-image-wrapper">
                        <img src="" alt="Student Profile" class="edit-profile-image" id="editProfileImage">
                    </div>
                    <div class="edit-file-input-wrapper">
                        <label for="editFileInput" class="edit-file-input-label">
                            <input type="file" id="editFileInput" name="image" class="edit-file-input" accept="image/*">
                            <span class="edit-file-btn">Choose File</span>
                            <span class="edit-file-name" id="editFileName">No file chosen</span>
                        </label>
                    </div>
                </div>
                
                <!-- Edit Form -->
                <form class="edit-form" id="editStudentForm" enctype="multipart/form-data">
                    <input type="hidden" id="editStudentDbId" name="student_id">
                    
                    <!-- First Name -->
                    <div class="edit-form-group">
                        <label class="edit-form-label">
                            First Name <span class="edit-required">*</span>
                        </label>
                        <input type="text" class="edit-form-input" id="editFirstName" name="fname" required>
                    </div>
                    
                    <!-- Middle Name -->
                    <div class="edit-form-group">
                        <label class="edit-form-label">Middle Name</label>
                        <input type="text" class="edit-form-input" id="editMiddleName" name="mname">
                    </div>
                    
                    <!-- Last Name -->
                    <div class="edit-form-group">
                        <label class="edit-form-label">
                            Last Name <span class="edit-required">*</span>
                        </label>
                        <input type="text" class="edit-form-input" id="editLastName" name="lname" required>
                    </div>
                    
                    <!-- Student ID -->
                    <div class="edit-form-group">
                        <label class="edit-form-label">
                            Student ID (LRN) <span class="edit-required">*</span>
                        </label>
                        <input type="text" class="edit-form-input" id="editStudentId" name="lrn" required>
                    </div>
                    
                    <!-- Gender -->
                    <div class="edit-form-group">
                        <label class="edit-form-label">
                            Gender <span class="edit-required">*</span>
                        </label>
                        <select class="edit-form-select" id="editGender" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    
                    <!-- Academic Year -->
                    <div class="edit-form-group">
                        <label class="edit-form-label">
                            Academic Year <span class="edit-required">*</span>
                        </label>
                        <input type="text" class="edit-form-input" id="editAcademicYear" name="academicyear" placeholder="e.g., 2025-2026" required>
                    </div>
                    
                    <!-- Grade Level -->
                    <div class="edit-form-group">
                        <label class="edit-form-label">
                            Grade Level <span class="edit-required">*</span>
                        </label>
                        <select class="edit-form-select" id="editGradeLevel" name="grade" required>
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
                    <div class="edit-form-group">
                        <label class="edit-form-label">
                            Section <span class="edit-required">*</span>
                        </label>
                        <input type="text" class="edit-form-input" id="editSection" name="section" required>
                    </div>
                    
                    <!-- Guardian -->
                    <div class="edit-form-group">
                        <label class="edit-form-label">
                            Guardian <span class="edit-required">*</span>
                        </label>
                        <input type="text" class="edit-form-input" id="editGuardian" name="guardian" required>
                    </div>
                    
                    <!-- Guardian Contact Number -->
                    <div class="edit-form-group">
                        <label class="edit-form-label">
                            Guardian Contact Number <span class="edit-required">*</span>
                        </label>
                        <input type="text" class="edit-form-input" id="editGuardianContact" name="guardiancontact" required>
                    </div>
                    
                    <!-- Email Address -->
                    <div class="edit-form-group full-width">
                        <label class="edit-form-label">
                            Email Address <span class="edit-required">*</span>
                        </label>
                        <input type="email" class="edit-form-input" id="editEmail" name="email" required>
                    </div>
                </form>
            </div>
            
            <div class="edit-modal-footer">
                <button class="edit-modal-btn btn-save-edit" onclick="saveEditStudent()">
                    Save
                </button>
                <button class="edit-modal-btn btn-cancel-edit" onclick="closeEditModal()">
                    Close
                </button>
            </div>
        </div>
    </div>

    <script>
    // Store current editing student data
    let currentEditStudentData = null;

    // Open Edit Student Modal
    function openEditModal(studentData) {
        console.log('openEditModal called with studentData:', studentData);
        
        currentEditStudentData = studentData;
        
        // Validate that we have the required data
        if (!studentData || !studentData.db_id) {
            console.error('ERROR: Student data or db_id is missing!', studentData);
            alert('Error: Cannot open edit modal. Student data is incomplete.');
            return;
        }
        
        // Get the latest data from the table row (which is fresh after reload)
        const row = document.querySelector(`tr[data-db-id="${studentData.db_id}"]`);
        let dataToUse = studentData;
        
        if (row && row.dataset.student) {
            try {
                const freshData = JSON.parse(row.dataset.student);
                console.log('Using fresh data from table row:', freshData);
                dataToUse = freshData;
                currentEditStudentData = freshData;
            } catch (e) {
                console.error('Failed to parse fresh data, using cached:', e);
            }
        }
        
        // Parse name - now we have direct access to fname, mname, lname
        let firstName = dataToUse.fname || '';
        let middleName = dataToUse.mname || '';
        let lastName = dataToUse.lname || '';
        
        // Debug: log what we're getting
        console.log('dataToUse object:', JSON.stringify(dataToUse, null, 2));
        console.log('Using direct name fields:', { firstName, middleName, lastName });
        console.log('Check if fname exists:', 'fname' in dataToUse, dataToUse.fname);
        
        // If fname/mname/lname are undefined, try to extract from the 'name' field as fallback
        if (!firstName && !lastName && dataToUse.name) {
            const nameParts = (dataToUse.name || '').trim().split(' ').filter(p => p);
            if (nameParts.length >= 2) {
                firstName = nameParts[0];
                lastName = nameParts[nameParts.length - 1];
                middleName = nameParts.length > 2 ? nameParts.slice(1, -1).join(' ') : '';
                console.log('Extracted from name field:', { firstName, middleName, lastName });
            }
        }
        
        // Set profile image
        const avatarUrl = dataToUse.avatar || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(dataToUse.name) + '&background=7c3aed&color=fff&size=140';
        document.getElementById('editProfileImage').src = avatarUrl;
        
        // Populate form fields with explicit error checking
        const fields = [
            { id: 'editStudentDbId', value: dataToUse.db_id },
            { id: 'editFirstName', value: firstName },
            { id: 'editMiddleName', value: middleName },
            { id: 'editLastName', value: lastName },
            { id: 'editStudentId', value: dataToUse.id },
            { id: 'editGender', value: dataToUse.gender },
            { id: 'editAcademicYear', value: dataToUse.school_year },
            { id: 'editGradeLevel', value: dataToUse.grade },
            { id: 'editSection', value: dataToUse.section },
            { id: 'editGuardian', value: dataToUse.guardian_name },
            { id: 'editGuardianContact', value: dataToUse.guardian_contact },
            { id: 'editEmail', value: dataToUse.email }
        ];
        
        fields.forEach(field => {
            const element = document.getElementById(field.id);
            if (element) {
                element.value = field.value || '';
                console.log('Set ' + field.id + ' to:', field.value);
            } else {
                console.error('Element not found:', field.id);
            }
        });
        
        // Reset file input
        const fileInput = document.getElementById('editFileInput');
        if (fileInput) {
            fileInput.value = '';
        }
        document.getElementById('editFileName').textContent = 'No file chosen';
        
        // Show modal
        const modal = document.getElementById('editStudentModal');
        if (modal) {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
            console.log('Modal opened');
        } else {
            console.error('Modal element not found!');
        }
    }

    // Close Edit Student Modal
    function closeEditModal() {
        console.log('closeEditModal called');
        const modal = document.getElementById('editStudentModal');
        if (modal) {
            modal.classList.remove('show');
            console.log('Modal show class removed');
        }
        document.body.style.overflow = '';
        currentEditStudentData = null;
        
        // Do NOT reset the form here - let openEditModal populate it with fresh data
        // This ensures the form always shows the latest data
    }

    // Close modal when clicking overlay
    document.addEventListener('DOMContentLoaded', function() {
        const editModal = document.getElementById('editStudentModal');
        if (editModal) {
            editModal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeEditModal();
                }
            });
        }
        
        // File input change handler
        const editFileInput = document.getElementById('editFileInput');
        if (editFileInput) {
            editFileInput.addEventListener('change', function() {
                const fileName = this.files[0]?.name || 'No file chosen';
                document.getElementById('editFileName').textContent = fileName;
                
                // Preview image
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        document.getElementById('editProfileImage').src = e.target.result;
                    };
                    reader.readAsDataURL(this.files[0]);
                }
            });
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && document.getElementById('editStudentModal')?.classList.contains('show')) {
            closeEditModal();
        }
    });

    // Save Edit Student
    function saveEditStudent() {
        console.log('=== SAVE EDIT STUDENT CALLED ===');
        
        // Get all form values directly from DOM elements
        const studentId = document.getElementById('editStudentDbId').value;
        const fname = document.getElementById('editFirstName').value.trim();
        const mname = document.getElementById('editMiddleName').value.trim();
        const lname = document.getElementById('editLastName').value.trim();
        const email = document.getElementById('editEmail').value.trim();
        const gender = document.getElementById('editGender').value;
        const lrn = document.getElementById('editStudentId').value.trim();
        const grade = document.getElementById('editGradeLevel').value;
        const section = document.getElementById('editSection').value.trim();
        const academicyear = document.getElementById('editAcademicYear').value.trim();
        const guardian = document.getElementById('editGuardian').value.trim();
        const guardiancontact = document.getElementById('editGuardianContact').value.trim();
        
        console.log('Form values extracted:');
        console.log({studentId, fname, mname, lname, email, gender, lrn, grade, section, academicyear, guardian, guardiancontact});
        
        // Validation
        if (!studentId) {
            alert('ERROR: Student ID not found. Please refresh and try again.');
            console.error('Student ID is empty!');
            return;
        }
        
        if (!fname || !lname || !email || !lrn) {
            alert('Please fill all required fields');
            console.error('Missing required fields!');
            return;
        }
        
        // Show loading
        const btn = document.querySelector('.btn-save-edit');
        const btnText = btn.textContent;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        
        // Create form data
        const formData = new FormData();
        formData.append('student_id', studentId);
        formData.append('fname', fname);
        formData.append('mname', mname);
        formData.append('lname', lname);
        formData.append('email', email);
        formData.append('gender', gender);
        formData.append('lrn', lrn);
        formData.append('grade', grade);
        formData.append('section', section);
        formData.append('academicyear', academicyear);
        formData.append('guardian', guardian);
        formData.append('guardiancontact', guardiancontact);
        
        // Add image file if selected
        const fileInput = document.getElementById('editFileInput');
        if (fileInput.files.length > 0) {
            formData.append('image', fileInput.files[0]);
            console.log('Image file:', fileInput.files[0].name);
        }
        
        console.log('Sending update request to update-student.php');
        
        // Send request
        fetch('update-student.php', {
            method: 'POST',
            body: formData
        })
        .then(resp => {
            console.log('Response received, status:', resp.status);
            return resp.text();
        })
        .then(text => {
            console.log('Response text:', text);
            btn.disabled = false;
            btn.textContent = btnText;
            
            let data = {};
            try {
                data = JSON.parse(text);
            } catch(e) {
                console.error('Failed to parse JSON:', e);
                alert('Server error: Invalid response');
                return;
            }
            
            if (data.success) {
                alert('Student updated successfully!');
                // Simply reload the page - this is the most reliable way to get fresh data
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(err => {
            console.error('Fetch failed:', err);
            btn.disabled = false;
            btn.textContent = btnText;
            alert('Failed to update: ' + err.message);
        });
    }
    </script>
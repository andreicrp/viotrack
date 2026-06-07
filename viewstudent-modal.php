<!-- viewstudent-modal.php -->
<!-- Student Profile Modal -->
<div class="modal-overlay" id="studentModal">
    <div class="student-modal">
        <div class="modal-header">
            <h2>Student Profile</h2>
            <button class="modal-close" onclick="closeStudentModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="modal-body">
            <div class="profile-section">
                <!-- Profile Image -->
                <div class="profile-image-container">
                    <img src="" alt="Student Profile" class="profile-image" id="modalStudentImage">
                    <span class="profile-image-label">Student Profile Image</span>
                </div>
                
                <!-- Profile Details -->
                <div class="profile-details">
                    <div class="detail-row">
                        <i class="fas fa-id-card detail-icon"></i>
                        <div class="detail-content">
                            <div class="detail-label">Student ID</div>
                            <div class="detail-value" id="modalStudentId">-</div>
                        </div>
                    </div>
                    
                    <div class="detail-row">
                        <i class="fas fa-user detail-icon"></i>
                        <div class="detail-content">
                            <div class="detail-label">Full Name</div>
                            <div class="detail-value" id="modalStudentName">-</div>
                        </div>
                    </div>
                    
                    <div class="detail-row">
                        <i class="fas fa-graduation-cap detail-icon"></i>
                        <div class="detail-content">
                            <div class="detail-label">Grade Level</div>
                            <div class="detail-value" id="modalGradeLevel">-</div>
                        </div>
                    </div>
                    
                    <div class="detail-row">
                        <i class="fas fa-envelope detail-icon"></i>
                        <div class="detail-content">
                            <div class="detail-label">Email</div>
                            <div class="detail-value email" id="modalStudentEmail">-</div>
                        </div>
                    </div>
                    
                    <div class="detail-row">
                        <i class="fas fa-user-friends detail-icon"></i>
                        <div class="detail-content">
                            <div class="detail-label">Guardian</div>
                            <div class="detail-value" id="modalGuardianName">-</div>
                        </div>
                    </div>
                    
                    <div class="detail-row">
                        <i class="fas fa-phone detail-icon"></i>
                        <div class="detail-content">
                            <div class="detail-label">Guardian Contact</div>
                            <div class="detail-value" id="modalGuardianContact">-</div>
                        </div>
                    </div>
                    
                    <div class="detail-row">
                        <i class="fas fa-venus-mars detail-icon"></i>
                        <div class="detail-content">
                            <div class="detail-label">Gender</div>
                            <div class="detail-value" id="modalStudentGender">-</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="modal-footer">
            <?php 
            // Check if user is admin or teacher
            $isAdmin = (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin');
            $isTeacher = (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'teacher');
            if ($isAdmin):
            ?>
            <button type="button" class="modal-btn btn-track-location" onclick="trackLocation(); return false;">
                <i class="fas fa-map-marker-alt"></i> Track Location
            </button>
            <?php endif; ?>
            <button type="button" class="modal-btn btn-profile-record" id="profileRecordBtn">
                <i class="fas fa-file-alt"></i> Profile & Record
            </button>
            <button type="button" class="modal-btn btn-close-modal" onclick="closeStudentModal(); return false;">
                Close
            </button>
        </div>
    </div>
</div>

<script>
// Store current student data
let currentStudentData = null;

// Open Student Modal (called from students.php)
function openStudentModal(studentData) {
    console.log('=== openStudentModal called ===');
    console.log('Received studentData:', studentData);
    console.log('db_id in received data:', studentData ? studentData.db_id : 'studentData is null');
    
    currentStudentData = studentData;
    
    if (!studentData) {
        console.error('CRITICAL: studentData is null/undefined in openStudentModal');
        alert('Error: No student data provided to modal');
        return;
    }
    
    // Populate modal with student data
    document.getElementById('modalStudentImage').src = studentData.avatar || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(studentData.name) + '&background=7c3aed&color=fff&size=180';
    document.getElementById('modalStudentId').textContent = studentData.id || '-';
    document.getElementById('modalStudentName').textContent = studentData.name || '-';
    document.getElementById('modalGradeLevel').textContent = (studentData.grade || '-') + ' - ' + (studentData.section || '') + ' (' + (studentData.school_year || '') + ')';
    document.getElementById('modalStudentEmail').textContent = studentData.email || '-';
    document.getElementById('modalGuardianName').textContent = studentData.guardian_name || '-';
    document.getElementById('modalGuardianContact').textContent = studentData.guardian_contact || '-';
    document.getElementById('modalStudentGender').textContent = studentData.gender || '-';
    
    console.log('Modal fields populated');
    console.log('currentStudentData set to:', currentStudentData);
    
    // Show modal
    document.getElementById('studentModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

// Close Student Modal
function closeStudentModal() {
    document.getElementById('studentModal').classList.remove('show');
    document.body.style.overflow = '';
    currentStudentData = null;
}

// Close modal when clicking overlay
document.addEventListener('DOMContentLoaded', function() {
    const studentModal = document.getElementById('studentModal');
    if (studentModal) {
        studentModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeStudentModal();
            }
        });
    }
    
    // Add event listener for Profile & Record button
    const profileRecordBtn = document.getElementById('profileRecordBtn');
    if (profileRecordBtn) {
        profileRecordBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            viewProfileRecord();
        });
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('studentModal')?.classList.contains('show')) {
        closeStudentModal();
    }
});

// Action button functions
function trackLocation() {
    console.log('=== trackLocation called ===');
    console.log('currentStudentData:', currentStudentData);
    
    if (!currentStudentData) {
        console.error('CRITICAL: currentStudentData is null or undefined');
        alert('Error: Student data not loaded. Please close this modal and try again.');
        return false;
    }
    
    // Use db_id (internal database ID) not LRN
    const dbId = parseInt(currentStudentData.db_id);
    console.log('Parsed DB ID:', dbId);
    console.log('Student Name:', currentStudentData.name);
    
    if (!dbId || dbId <= 0 || isNaN(dbId)) {
        console.error('CRITICAL: db_id is invalid:', dbId);
        console.error('Full student data:', currentStudentData);
        alert('Error: Cannot retrieve student location data. Student ID is invalid.');
        return false;
    }
    
    const url = 'track-student.php?id=' + dbId;
    console.log('=== Navigating to track-student page:', url, '===');
    window.location.href = url;
    return false;
}

function viewProfileRecord() {
    console.log('=== viewProfileRecord called ===');
    console.log('currentStudentData object:', currentStudentData);
    
    if (!currentStudentData) {
        console.error('CRITICAL: currentStudentData is null or undefined');
        alert('Error: Student data not loaded. Please close this modal and try again.');
        return false;
    }
    
    console.log('Student ID (LRN):', currentStudentData.id);
    console.log('Database ID:', currentStudentData.db_id);
    console.log('Student Name:', currentStudentData.name);
    
    const dbId = parseInt(currentStudentData.db_id);
    console.log('Parsed DB ID:', dbId);
    
    if (!dbId || dbId <= 0) {
        console.error('CRITICAL: db_id is invalid:', dbId);
        alert('Error: Invalid database ID (' + dbId + '). Student data: ' + JSON.stringify(currentStudentData));
        return false;
    }
    
    const url = 'adminstudentviolation.php?id=' + dbId;
    console.log('=== Navigating to:', url, '===');
    window.location.href = url;
    return false;
}
</script>
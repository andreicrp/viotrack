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
            // Check if user is admin
            $isAdmin = (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin');
            if ($isAdmin):
            ?>
            <button type="button" class="modal-btn btn-track-location" onclick="trackLocation(); return false;">
                <i class="fas fa-map-marker-alt"></i> Track Location
            </button>
            <?php endif; ?>
            <button type="button" class="modal-btn btn-profile-record" id="profileRecordBtn">
                <i class="fas fa-file-alt"></i> Profile & Record
            </button>
            <?php if ($isAdmin): ?>
            <button type="button" class="modal-btn btn-print-id" onclick="printIdCard(); return false;">
                <i class="fas fa-print"></i> Print ID Card
            </button>
            <?php endif; ?>
            <button type="button" class="modal-btn btn-close-modal" onclick="closeStudentModal(); return false;">
                Close
            </button>
        </div>
    </div>
</div>

<!-- Modal functions now defined in students.php main script for global scope -->
<!-- Resolution Letter Modal -->
<div class="resolution-modal-overlay" id="resolutionModal">
    <div class="resolution-modal">
        <div class="resolution-modal-header">
            <h2>Violation Resolution Letter</h2>
            <div class="resolution-header-actions">
                <?php 
                // Check if user is admin
                $isAdmin = (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin');
                if ($isAdmin):
                ?>
                <button class="btn-print" onclick="printLetter()">
                    <i class="fas fa-print"></i> Print
                </button>
                <?php endif; ?>
                <button class="resolution-modal-close" onclick="closeResolutionModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <div class="resolution-modal-body">
            <div class="letter-content" id="letterContent">
                <!-- School Header -->
                <div class="school-header">
                    <div class="school-header-content">
                        <img src="images/phcm-logo.png" alt="School Logo" class="school-logo">
                        <div class="school-header-text">
                            <p class="school-country">Republic of the Philippines</p>
                            <h2 class="school-name">PERPETUAL HELP COLLEGE OF MANILA</h2>
                            <p class="school-motto">CHARACTER BUILDING IS NATION BUILDING</p>
                        </div>
                    </div>
                    <div class="school-header-line"></div>
                </div>
                
                <h1 class="letter-title">VIOLATION RESOLUTION LETTER</h1>
                
                <div class="letter-info">
                    <p><strong>Date:</strong> <span id="resolutionDate">November 21, 2025</span></p>
                    <p><strong>Student ID:</strong> <span id="resolutionStudentId">00-0000-000</span></p>
                    <p><strong>Student Name:</strong> <span id="resolutionStudent">Student Name</span></p>
                    <p><strong>Grade/Section:</strong> <span id="resolutionGradeSection">Grade 7</span></p>
                </div>
                
                <div class="letter-body">
                    <p>To whom it may concern,</p>
                    
                    <p>This letter serves to inform you that the following violation(s) committed by <strong id="letterStudentNameBody">Student Name</strong> have been officially resolved:</p>
                    
                    <table class="letter-table">
                        <thead>
                            <tr>
                                <th>Violation</th>
                                <th>Type</th>
                                <th>Date Committed</th>
                            </tr>
                        </thead>
                        <tbody id="letterViolationsTable">
                            <tr>
                                <td id="tablViolation">-</td>
                                <td id="tablType">-</td>
                                <td id="tablDate">-</td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <p>The student has complied with the required corrective actions, and the matter(s) have now been closed in accordance with the school's disciplinary procedures.</p>
                    
                    <p>We encourage the student to continue demonstrating positive behavior and adhering to all school rules moving forward.</p>
                    
                    <p>If further clarification is needed, please contact the school administration.</p>
                    
                    <p>Respectfully,</p>
                </div>
                
                <div class="letter-signature">
                    <div class="signature-line"></div>
                    <p>Sheryl B. Gamboa</p>
                    <p>Prefect of Discipline</p>
                    <p>Perpetual Help College of Manila</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Violation type colors */
.letter-table td:nth-child(2)::before {
    content: '';
}

.type-minor {
    color: #10b981;
    font-weight: 600;
}

.type-serious {
    color: #f59e0b;
    font-weight: 600;
}

.type-major {
    color: #ef4444;
    font-weight: 600;
}
</style>

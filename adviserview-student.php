<?php
// adviserview-student.php
include 'auth_check.php';
include 'connect.php';

// Require login - admin or teacher/adviser can access
requireAdminOrTeacher();

include 'header.php';
include 'sidebar.php';

// Get adviser ID from query parameter
$adviserId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Security check: if user is a teacher, they can only view their own students
$currentUserId = getCurrentUserId();
$userType = getCurrentUserType();

if ($userType === 'teacher') {
    // If no adviser ID provided, try to get the teacher's own adviser profile
    if ($adviserId <= 0) {
        $teacherAdviserSQL = "SELECT id FROM adviser WHERE tid = ?";
        $teacherStmt = $conn->prepare($teacherAdviserSQL);
        if ($teacherStmt) {
            $teacherStmt->bind_param('i', $currentUserId);
            $teacherStmt->execute();
            $teacherResult = $teacherStmt->get_result();
            if ($teacherResult->num_rows > 0) {
                $teacherAdviser = $teacherResult->fetch_assoc();
                $adviserId = intval($teacherAdviser['id']);
            }
            $teacherStmt->close();
        }
    }
    
    // If still no adviser ID, teacher is not assigned as an adviser
   
    // Verify the adviser ID matches the teacher's ID
    $verifyAdviserSQL = "SELECT id, tid FROM adviser WHERE id = ? AND tid = ?";
    $verifyStmt = $conn->prepare($verifyAdviserSQL);
    if ($verifyStmt) {
        $verifyStmt->bind_param('ii', $adviserId, $currentUserId);
        $verifyStmt->execute();
        $verifyResult = $verifyStmt->get_result();
        if ($verifyResult->num_rows === 0) {
            die("You do not have permission to view this adviser's students");
        }
        $verifyStmt->close();
    }
}

if ($adviserId <= 0) {
    die("Invalid adviser ID");
}

// Fetch adviser details
$adviserSQL = "SELECT 
                    a.id,
                    a.tid,
                    a.fname,
                    a.mname,
                    a.lname,
                    a.email,
                    a.grade_level,
                    a.class_section,
                    a.image,
                    (SELECT COUNT(*) FROM student s WHERE (s.grade = a.grade_level OR s.grade = CONCAT('Grade ', TRIM(REPLACE(a.grade_level, 'Grade ', ''))) OR s.grade = TRIM(REPLACE(a.grade_level, 'Grade ', '')))) as student_count
                FROM adviser a
                WHERE a.id = ?";

$adviserStmt = $conn->prepare($adviserSQL);
if (!$adviserStmt) {
    die("Error: " . $conn->error);
}

$adviserStmt->bind_param('i', $adviserId);
$adviserStmt->execute();
$adviserResult = $adviserStmt->get_result();

if ($adviserResult->num_rows === 0) {
    die("Adviser not found");
}

$adviser = $adviserResult->fetch_assoc();
$adviserStmt->close();

// Fetch students for this adviser
$gradeLevel = $adviser['grade_level'];
$classSection = $adviser['class_section'];

// Handle both "Grade X" and "X" formats for grade matching
$studentsSQL = "SELECT 
                    id,
                    lrn,
                    fname,
                    mname,
                    lname,
                    email,
                    gender,
                    image,
                    grade,
                    section
                FROM student 
                WHERE (grade = ? OR grade = CONCAT('Grade ', ?) OR grade = TRIM(REPLACE(?, 'Grade ', '')))
                AND section = ?
                ORDER BY lname ASC, fname ASC";

$studentsStmt = $conn->prepare($studentsSQL);
if (!$studentsStmt) {
    die("Error: " . $conn->error);
}

// Extract grade number for matching
$gradeNum = trim(str_replace('Grade ', '', $gradeLevel));
$studentsStmt->bind_param('ssss', $gradeLevel, $gradeNum, $gradeLevel, $classSection);
$studentsStmt->execute();
$studentsResult = $studentsStmt->get_result();
$students = [];
if ($studentsResult->num_rows > 0) {
    while($row = $studentsResult->fetch_assoc()) {
        $students[] = $row;
    }
}
$studentsStmt->close();

$avatar = !empty($adviser['image']) ? htmlspecialchars($adviser['image'], ENT_QUOTES, 'UTF-8') : 'image/default.jpg';
$initials = substr($adviser['fname'], 0, 1) . substr($adviser['lname'], 0, 1);
$fullName = htmlspecialchars(trim($adviser['fname'] . ' ' . (!empty($adviser['mname']) ? $adviser['mname'] . ' ' : '') . $adviser['lname']), ENT_QUOTES, 'UTF-8');
$email = htmlspecialchars($adviser['email'], ENT_QUOTES, 'UTF-8');
$gradeDisplay = htmlspecialchars($adviser['grade_level'], ENT_QUOTES, 'UTF-8');
$sectionDisplay = htmlspecialchars($adviser['class_section'], ENT_QUOTES, 'UTF-8');

// Generate a consistent color palette based on adviser ID for consistent theming
$colors = [
    ['bg' => '#fef3c7', 'text' => '#92400e', 'border' => '#f59e0b', 'shadow' => 'rgba(245, 158, 11, 0.2)'],   // Amber
    ['bg' => '#fee2e2', 'text' => '#991b1b', 'border' => '#ef4444', 'shadow' => 'rgba(239, 68, 68, 0.2)'],       // Red
    ['bg' => '#dbeafe', 'text' => '#0c2340', 'border' => '#3b82f6', 'shadow' => 'rgba(59, 130, 246, 0.2)'],     // Blue
    ['bg' => '#dcfce7', 'text' => '#15803d', 'border' => '#22c55e', 'shadow' => 'rgba(34, 197, 94, 0.2)'],       // Green
    ['bg' => '#f3e8ff', 'text' => '#581c87', 'border' => '#a855f7', 'shadow' => 'rgba(168, 85, 247, 0.2)'],     // Purple
    ['bg' => '#fce7f3', 'text' => '#831843', 'border' => '#ec4899', 'shadow' => 'rgba(236, 72, 153, 0.2)'],     // Pink
    ['bg' => '#e0e7ff', 'text' => '#312e81', 'border' => '#6366f1', 'shadow' => 'rgba(99, 102, 241, 0.2)'],     // Indigo
    ['bg' => '#e5e7eb', 'text' => '#1f2937', 'border' => '#9ca3af', 'shadow' => 'rgba(156, 163, 175, 0.2)'],    // Gray
];

// Use adviser ID to select consistent colors
$colorIndex = $adviserId % count($colors);
$selectedColor = $colors[$colorIndex];
?>

<link rel="stylesheet" href="css/addrecord-modal.css">
<style>
    @media (max-width: 768px) {
        .card-header {
            flex-direction: column !important;
            gap: 12px !important;
            padding: 12px !important;
        }
        
        .card-title-section h3 {
            font-size: 14px !important;
        }
        
        .card-title-section p {
            font-size: 11px !important;
        }
        
        .header-actions {
            width: 100% !important;
            gap: 8px !important;
        }
        
        .header-actions button {
            flex: 1;
            padding: 8px 12px !important;
            font-size: 12px !important;
        }
        
        [data-grade] {
            padding: 16px !important;
        }
        
        [data-grade] > div {
            flex-direction: column !important;
            gap: 16px !important;
            text-align: center !important;
            align-items: center !important;
        }
        
        [data-grade] img {
            width: 80px !important;
            height: 80px !important;
        }
        
        [data-grade] h2 {
            font-size: 18px !important;
        }
        
        [data-grade] > div > div:last-child {
            width: 100% !important;
        }
        
        [data-grade] p {
            font-size: 12px !important;
        }
        
        [data-grade] [style*="grid"] {
            grid-template-columns: repeat(3, 1fr) !important;
            gap: 10px !important;
        }
        
        [data-grade] [style*="background"] {
            padding: 12px 8px !important;
        }
        
        [data-grade] [style*="background"] p:first-child {
            font-size: 9px !important;
        }
        
        [data-grade] [style*="background"] p:last-child {
            font-size: 18px !important;
        }
        
        .student-card {
            flex-direction: column !important;
            gap: 12px !important;
            padding: 12px !important;
            text-align: center !important;
        }
        
        .student-card img {
            width: 56px !important;
            height: 56px !important;
            margin: 0 auto !important;
        }
        
        .student-card h4 {
            font-size: 14px !important;
        }
        
        .student-card p {
            font-size: 11px !important;
        }
        
        .student-card button {
            width: 100% !important;
            padding: 10px 12px !important;
            font-size: 12px !important;
        }
        
        #studentSearch {
            width: 100% !important;
            font-size: 12px !important;
            padding: 6px 10px !important;
        }
    }
    
    @media (max-width: 480px) {
        main.main-content {
            padding: 8px !important;
        }
        
        .card {
            border-radius: 8px !important;
        }
        
        .card-header {
            padding: 8px !important;
            gap: 8px !important;
        }
        
        .card-title-section h3 {
            font-size: 13px !important;
            margin: 0 !important;
        }
        
        .card-title-section p {
            font-size: 10px !important;
            margin: 2px 0 0 0 !important;
        }
        
        .header-actions button {
            padding: 6px 10px !important;
            font-size: 11px !important;
        }
        
        .header-actions button i {
            font-size: 12px !important;
        }
        
        [data-grade] {
            padding: 12px !important;
            border-bottom: 1px solid #e5e7eb !important;
        }
        
        [data-grade] > div {
            gap: 12px !important;
        }
        
        [data-grade] img {
            width: 70px !important;
            height: 70px !important;
        }
        
        [data-grade] h2 {
            font-size: 16px !important;
            margin: 0 0 2px 0 !important;
        }
        
        [data-grade] > div > div:last-child > p {
            font-size: 11px !important;
            margin: 0 0 12px 0 !important;
        }
        
        [data-grade] [style*="grid"] {
            grid-template-columns: repeat(3, 1fr) !important;
            gap: 8px !important;
        }
        
        [data-grade] [style*="background"] {
            padding: 10px 6px !important;
        }
        
        [data-grade] [style*="background"] p:first-child {
            font-size: 8px !important;
        }
        
        [data-grade] [style*="background"] p:last-child {
            font-size: 16px !important;
            margin: 4px 0 0 0 !important;
        }
        
        .student-card {
            gap: 10px !important;
            padding: 10px !important;
        }
        
        .student-card img {
            width: 48px !important;
            height: 48px !important;
        }
        
        .student-card h4 {
            font-size: 13px !important;
        }
        
        .student-card p {
            font-size: 10px !important;
            margin: 2px 0 !important;
        }
        
        .student-card button {
            width: 100% !important;
            padding: 8px 10px !important;
            font-size: 11px !important;
            margin: 4px 0 0 0 !important;
        }
        
        .student-card button i {
            font-size: 11px !important;
        }
        
        .students-section-header {
            flex-direction: column !important;
            gap: 10px !important;
            margin-bottom: 12px !important;
        }
        
        .students-section-header h3 {
            margin: 0 !important;
            font-size: 14px !important;
        }
        
        #studentSearch {
            padding: 6px 8px !important;
            font-size: 11px !important;
            margin: 0 !important;
        }
    }
</style>

<main class="main-content">
    <div class="card">
        <div class="card-header" style="display: flex; flex-direction: row; gap: 12px; padding: 16px; justify-content: space-between; align-items: center;">
            <div class="card-title-section" style="flex: 1;">
                <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #1f2937;">Adviser Details</h3>
                <p class="subtitle" style="margin: 4px 0 0 0; font-size: 12px; color: #6b7280;">View and manage students</p>
            </div>
            <div class="header-actions" style="display: flex; gap: 8px; flex-shrink: 0;">
                <button class="btn btn-secondary" style="background: #10b981; color: #fff; border: none; font-weight: 600; padding: 8px 12px; border-radius: 6px; cursor: pointer; transition: all 0.2s; box-shadow: 0 2px 8px rgba(16, 185, 129, 0.2); font-size: 12px;" onclick="openAddBulkViolationModal()" onmouseover="this.style.background='#059669'" onmouseout="this.style.background='#10b981'" title="Add Bulk Violations">
                    <i class="fas fa-plus"></i> <span style="display: none;">Bulk</span>
                </button>
                <button class="btn btn-secondary" style="background: #6b7280; color: #fff; border: none; font-weight: 600; padding: 8px 12px; border-radius: 6px; cursor: pointer; transition: all 0.2s; box-shadow: 0 2px 8px rgba(107, 114, 128, 0.2); font-size: 12px;" onclick="goBackToAdvisers()" title="Back to Advisers">
                    <i class="fas fa-arrow-left"></i> <span style="display: none;">Back</span>
                </button>
            </div>
        </div>

        <!-- Adviser Info Section -->
        <div style="padding: 24px; border-bottom: 1px solid #e5e7eb;" data-grade="<?php echo $gradeDisplay; ?>" data-section="<?php echo $sectionDisplay; ?>">
            <div style="display: flex; flex-direction: column; gap: 20px; align-items: center; text-align: center;">
                <div style="flex-shrink: 0;">
                    <img src="<?php echo $avatar; ?>" 
                         alt="<?php echo $fullName; ?>" 
                         style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 4px solid #667eea; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);"
                         onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($initials); ?>&background=667eea&color=fff&size=100'">
                </div>
                <div style="flex: 1; min-width: 0; width: 100%;">
                    <h2 style="margin: 0 0 4px 0; font-size: 22px; font-weight: 700; color: #1f2937; word-wrap: break-word;"><?php echo $fullName; ?></h2>
                    <p style="margin: 0 0 20px 0; font-size: 13px; color: #6b7280; word-break: break-word; overflow-wrap: break-word;"><?php echo $email; ?></p>
                    
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                        <div style="background: <?php echo $selectedColor['bg']; ?>; padding: 14px 10px; border-radius: 8px; border: 2px solid <?php echo $selectedColor['border']; ?>; box-shadow: 0 2px 8px <?php echo $selectedColor['shadow']; ?>; transition: all 0.3s ease;">
                            <p style="margin: 0; font-size: 10px; color: <?php echo $selectedColor['text']; ?>; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Grade Level</p>
                            <p style="margin: 6px 0 0 0; font-size: 20px; font-weight: 700; color: <?php echo $selectedColor['text']; ?>;"><?php echo $gradeDisplay; ?></p>
                        </div>
                        <div style="background: <?php echo $selectedColor['bg']; ?>; padding: 14px 10px; border-radius: 8px; border: 2px solid <?php echo $selectedColor['border']; ?>; box-shadow: 0 2px 8px <?php echo $selectedColor['shadow']; ?>; transition: all 0.3s ease;">
                            <p style="margin: 0; font-size: 10px; color: <?php echo $selectedColor['text']; ?>; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Section</p>
                            <p style="margin: 6px 0 0 0; font-size: 20px; font-weight: 700; color: <?php echo $selectedColor['text']; ?>; word-wrap: break-word;"><?php echo $sectionDisplay; ?></p>
                        </div>
                        <div style="background: <?php echo $selectedColor['bg']; ?>; padding: 14px 10px; border-radius: 8px; border: 2px solid <?php echo $selectedColor['border']; ?>; box-shadow: 0 2px 8px <?php echo $selectedColor['shadow']; ?>; transition: all 0.3s ease;">
                            <p style="margin: 0; font-size: 10px; color: <?php echo $selectedColor['text']; ?>; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Total Students</p>
                            <p style="margin: 6px 0 0 0; font-size: 20px; font-weight: 700; color: <?php echo $selectedColor['text']; ?>;"><?php echo count($students); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Students Section -->
        <div style="padding: 24px;">
            <div class="students-section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 12px;">
                <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #1f2937;">
                    <i class="fas fa-book" style="margin-right: 8px; color: #667eea;"></i>Enrolled Students
                </h3>
                <input type="text" id="studentSearch" placeholder="Search by name, Student ID, or email..." style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; width: 250px; flex: 1;" onkeyup="filterStudents()">
            </div>

            <?php if (count($students) > 0): ?>
                <div style="display: grid; gap: 16px;" id="studentsContainer">
                    <?php foreach ($students as $student): ?>
                        <?php
                        $studentName = htmlspecialchars(trim($student['fname'] . ' ' . (!empty($student['mname']) ? $student['mname'] . ' ' : '') . $student['lname']), ENT_QUOTES, 'UTF-8');
                        $studentInitials = substr($student['fname'], 0, 1) . substr($student['lname'], 0, 1);
                        $studentLRN = htmlspecialchars($student['lrn'], ENT_QUOTES, 'UTF-8');
                        $studentEmail = htmlspecialchars($student['email'], ENT_QUOTES, 'UTF-8');
                        $studentGender = htmlspecialchars($student['gender'], ENT_QUOTES, 'UTF-8');
                        
                        // Determine avatar - Check if custom image exists, otherwise use generated avatar
                        $studentAvatar = 'image/default.jpg';
                        if (!empty($student['image']) && $student['image'] != 'image/default.jpg' && $student['image'] != 'image/default.png') {
                            // If it's a URL or file exists, use it
                            if (strpos($student['image'], 'http') === 0 || file_exists($student['image'])) {
                                $studentAvatar = htmlspecialchars($student['image'], ENT_QUOTES, 'UTF-8');
                            } else {
                                // File doesn't exist, generate avatar with initials
                                $studentAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($studentInitials) . '&background=10b981&color=fff&size=64';
                            }
                        } else {
                            // No custom image, generate avatar with initials
                            $studentAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($studentInitials) . '&background=10b981&color=fff&size=64';
                        }
                        ?>
                        <div class="student-card" style="display: flex; gap: 16px; padding: 16px; background: #f9fafb; border-radius: 12px; border: 1px solid #e5e7eb; transition: all 0.2s; align-items: flex-start;" onmouseover="this.style.background='#f3f4f6'; this.style.borderColor='#d1d5db'; this.style.transform='translateX(4px)'" onmouseout="this.style.background='#f9fafb'; this.style.borderColor='#e5e7eb'; this.style.transform='translateX(0)'" data-name="<?php echo strtolower($studentName); ?>" data-id="<?php echo (int)$student['id']; ?>" data-email="<?php echo strtolower($studentEmail); ?>">
                            <div style="flex-shrink: 0;">
                                <img src="<?php echo $studentAvatar; ?>" 
                                     alt="<?php echo $studentName; ?>" 
                                     style="width: 64px; height: 64px; border-radius: 50%; object-fit: cover; border: 2px solid #667eea;">
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <h4 style="margin: 0 0 4px 0; font-size: 16px; font-weight: 700; color: #1f2937;"><?php echo $studentName; ?></h4>
                                <p style="margin: 0 0 4px 0; font-size: 13px; color: #6b7280;">Student ID: <strong><?php echo $studentLRN; ?></strong></p>
                                <p style="margin: 0 0 4px 0; font-size: 13px; color: #6b7280;">Email: <?php echo $studentEmail; ?></p>
                                <p style="margin: 0; font-size: 12px; color: #6b7280;">Gender: <strong><?php echo ucfirst($studentGender); ?></strong> • Grade: <strong><?php echo htmlspecialchars($student['grade'], ENT_QUOTES, 'UTF-8'); ?></strong> • Section: <strong><?php echo htmlspecialchars($student['section'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
                            </div>
                            <div style="flex-shrink: 0; display: flex; gap: 8px;">
                                <button onclick="addViolation(<?php echo (int)$student['id']; ?>, '<?php echo htmlspecialchars($studentName, ENT_QUOTES); ?>')" style="padding: 8px 16px; background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px; transition: all 0.2s; white-space: nowrap;" onmouseover="this.style.background='#dc2626'; this.style.boxShadow='0 2px 8px rgba(220, 38, 38, 0.3)'" onmouseout="this.style.background='#ef4444'; this.style.boxShadow='none'">
                                    <i class="fas fa-exclamation-circle" style="margin-right: 4px;"></i>Add Violation
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 40px 20px;">
                    <i class="fas fa-inbox" style="font-size: 48px; color: #cbd5e1; display: block; margin-bottom: 16px;"></i>
                    <p style="margin: 0; color: #9ca3af; font-size: 16px;">No students assigned to this section</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
function goBackToAdvisers() {
    window.location.href = 'advisers.php';
}

function addViolation(studentId, studentName) {
    window.location.href = 'adminstudentviolation.php?id=' + studentId;
}

function openAddBulkViolationModal() {
    // Get all visible students from the filtered list
    const visibleStudents = [];
    document.querySelectorAll('.student-card').forEach(card => {
        if (card.style.display !== 'none') {
            const studentId = parseInt(card.getAttribute('data-id'));
            const name = card.querySelector('h4').textContent;
            const lrn = card.querySelector('strong').textContent;
            
            visibleStudents.push({
                id: studentId,
                name: name,
                lrn: lrn
            });
        }
    });
    
    // Open the modal
    const modal = document.getElementById('addBulkViolationsModal');
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
        
        // Populate grade and section
        const infoSection = document.querySelector('[data-grade]');
        if (infoSection) {
            const grade = infoSection.getAttribute('data-grade');
            const section = infoSection.getAttribute('data-section');
            document.getElementById('bulkGradeDisplay').textContent = grade;
            document.getElementById('bulkSectionDisplay').textContent = section;
        }
        
        // Populate students list
        updateBulkStudentsList(visibleStudents);
        
        // Initialize form state
        document.querySelectorAll('.bulk-student-checkbox').forEach(cb => cb.checked = false);
        document.querySelectorAll('.bulk-violation-checkbox').forEach(cb => cb.checked = false);
        checkBulkFormValidity();
        updateBulkSummary();
    }
}

function filterStudents() {
    const searchInput = document.getElementById('studentSearch').value.toLowerCase();
    const cards = document.querySelectorAll('.student-card');
    let visibleCount = 0;
    
    cards.forEach(card => {
        const name = card.getAttribute('data-name');
        const id = card.getAttribute('data-id');
        const email = card.getAttribute('data-email');
        
        if (name.includes(searchInput) || id.includes(searchInput) || email.includes(searchInput)) {
            card.style.display = 'flex';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    // Show "no results" message if no students match
    const container = document.getElementById('studentsContainer');
    let noResults = container.querySelector('.no-results');
    
    if (visibleCount === 0 && searchInput.length > 0) {
        if (!noResults) {
            noResults = document.createElement('div');
            noResults.className = 'no-results';
            noResults.style.cssText = 'text-align: center; padding: 40px 20px; color: #9ca3af;';
            noResults.innerHTML = '<i class="fas fa-search" style="font-size: 48px; color: #cbd5e1; display: block; margin-bottom: 16px;"></i><p style="margin: 0; font-size: 16px;">No students found matching your search</p>';
            container.appendChild(noResults);
        }
    } else if (noResults) {
        noResults.remove();
    }
}</script>

<?php
// Include the bulk violations modal
if (file_exists('addbulkviolations-modal.php')) {
    include 'addbulkviolations-modal.php';
}

$conn->close();
include 'footer.php';
?>

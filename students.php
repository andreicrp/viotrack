<?php
// students.php
include 'auth_check.php';
include 'connect.php';
include 'header.php';
include 'sidebar.php';

// Require login - only admin and teacher can access
requireAdminOrTeacher();

// Check user type (Admin or Teacher)
$isAdmin = (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin');
$isTeacher = (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'teacher');

// Fetch statistics
$total_students_query = "SELECT COUNT(*) as total FROM student";
$total_result = executeQuery($total_students_query);
$total_students = $total_result->fetch_assoc()['total'];

// For this example, assuming all students are active
$active_students = $total_students;

// Count pending issues (students with pending records)
$pending_query = "SELECT COUNT(DISTINCT sid) as pending FROM record WHERE status = 'Pending'";
$pending_result = executeQuery($pending_query);
$pending_issues = $pending_result->fetch_assoc()['pending'];

$stats = [
    'total_students' => $total_students,
    'active_students' => $active_students,
    'pending_issues' => $pending_issues
];

// Fetch all students with their information
$students_query = "SELECT 
    s.id,
    s.lrn,
    s.fname,
    s.mname,
    s.lname,
    CONCAT(s.fname, ' ', COALESCE(s.mname, ''), ' ', s.lname) as name,
    s.email,
    s.gender,
    s.grade,
    s.section,
    s.academicyear as school_year,
    s.guardian as guardian_name,
    s.guardiancontact as guardian_contact,
    s.image
FROM student s
ORDER BY s.lname, s.fname";

$students_result = executeQuery($students_query);
$students = [];

if ($students_result->num_rows > 0) {
    while($row = $students_result->fetch_assoc()) {
        // Determine avatar path
        $avatar = 'image/default.png';
        
        // Check if student has a custom image uploaded
        if (!empty($row['image']) && $row['image'] != 'image/default.png' && $row['image'] != 'image/default.jpg') {
            // If it's a local file path, check if it exists
            if (strpos($row['image'], 'http') === 0 || file_exists($row['image'])) {
                $avatar = $row['image'];
            } else {
                // File doesn't exist, use default image or generate avatar
                if (file_exists('image/default.png')) {
                    $avatar = 'image/default.png';
                } else {
                    // Generate initials avatar as fallback
                    $nameParts = explode(' ', $row['name']);
                    $initials = '';
                    if (count($nameParts) >= 2) {
                        $initials = substr($nameParts[0], 0, 1) . substr($nameParts[count($nameParts)-1], 0, 1);
                    } else {
                        $initials = substr($nameParts[0], 0, 2);
                    }
                    $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($initials) . '&background=7c3aed&color=fff';
                }
            }
        } else if (file_exists('image/default.png')) {
            // Use the default image if it exists
            $avatar = 'image/default.png';
        } else {
            // Generate initials avatar as fallback
            $nameParts = explode(' ', $row['name']);
            $initials = '';
            if (count($nameParts) >= 2) {
                $initials = substr($nameParts[0], 0, 1) . substr($nameParts[count($nameParts)-1], 0, 1);
            } else {
                $initials = substr($nameParts[0], 0, 2);
            }
            $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($initials) . '&background=7c3aed&color=fff';
        }
        
        $students[] = [
            'id' => $row['lrn'],
            'db_id' => $row['id'],
            'fname' => $row['fname'],
            'mname' => $row['mname'],
            'lname' => $row['lname'],
            'name' => $row['name'],
            'email' => $row['email'],
            'gender' => $row['gender'],
            'grade' => $row['grade'],
            'section' => $row['section'],
            'school_year' => $row['school_year'],
            'guardian_name' => $row['guardian_name'],
            'guardian_contact' => $row['guardian_contact'],
            'avatar' => $avatar
        ];
    }
}
?>

<!-- Include CSS Files -->
<link rel="stylesheet" href="css/student-modal.css">
<link rel="stylesheet" href="css/students.css">
<link rel="stylesheet" href="css/filter-modal.css">
<link rel="stylesheet" href="css/editstudent-modal.css">
<link rel="stylesheet" href="css/studentimport-modal.css">
<link rel="stylesheet" href="css/addstudent-modal.css">

<main class="main-content">
    <!-- Student List Card -->
    <div class="card student-card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
            <div class="card-title-section">
                <h3>Student List</h3>
                <p class="subtitle" style="font-size: 14px; color: #6b7280; margin: 4px 0 0 0;">You have <?php echo $stats['total_students']; ?> student(s)</p>
            </div>
            <!-- Header Actions Row - Aligned Right -->
            <div class="header-actions" style="display: flex; gap: 12px; flex-wrap: wrap; margin-left: auto;">
                <?php if ($isAdmin): ?>
                <button class="btn btn-danger btn-delete-selected" id="deleteSelectedBtn" style="display: none;">
                    <i class="fas fa-trash"></i> Delete Selected
                </button>
                <?php endif; ?>
                <button class="filter-btn-main" id="openFilterBtn" onclick="openFilterModal()">
                    <i class="fas fa-filter"></i> Filter
                </button>
                <?php if ($isAdmin): ?>
                <button class="btn btn-export" onclick="exportStudents()">
                    <i class="fas fa-download"></i> Export
                </button>
                <button class="btn btn-success" style="background: #f59e0b; color: #fff;" onclick="openImportModal()">
                    <i class="fas fa-file-import"></i> Import
                </button>
                <button class="btn btn-primary" style="background: #3b82f6;" id="addStudentBtn">
                    <i class="fas fa-user-plus"></i> Add Student
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Table Controls -->
        <div class="table-controls" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 20px;">
            <div class="entries-control" style="display: flex; align-items: center; gap: 8px; font-size: 14px; color: #374151;">
                <select id="entriesPerPage" style="padding: 8px 12px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 14px;">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span>entries per page</span>
            </div>
            <div class="search-control" style="display: flex; align-items: center; gap: 8px;">
                <label style="font-size: 14px; color: #374151;">Search:</label>
                <input type="text" id="searchInput" style="padding: 8px 12px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 14px; width: 200px;">
            </div>
        </div>

        <!-- Student Table -->
        <div class="table-container">
            <table class="student-table">
                <thead>
                    <tr>
                        <th class="checkbox-col">
                            <input type="checkbox" id="selectAll">
                        </th>
                        <th>STUDENT</th>
                        <th>EMAIL</th>
                        <th>GRADE & SECTION</th>
                        <th>GUARDIAN</th>
                        <th>ACTION</th>
                    </tr>
                </thead>
                <tbody id="studentTableBody">
                    <?php foreach ($students as $student): ?>
                    <tr data-student-id="<?php echo $student['id']; ?>"
                        data-db-id="<?php echo $student['db_id']; ?>"
                        data-grade="<?php echo $student['grade']; ?>"
                        data-section="<?php echo $student['section']; ?>"
                        data-school-year="<?php echo $student['school_year']; ?>"
                        data-student='<?php echo htmlspecialchars(json_encode($student), ENT_QUOTES, 'UTF-8'); ?>'>
                        <td class="checkbox-col">
                            <input type="checkbox" class="student-checkbox" value="<?php echo $student['db_id']; ?>">
                        </td>
                        <td>
                            <div class="student-info">
                                <img src="<?php echo $student['avatar']; ?>" alt="<?php echo $student['name']; ?>" class="student-avatar" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($student['name']); ?>&background=7c3aed&color=fff'">
                                <div class="student-details">
                                    <span class="student-name"><?php echo $student['name']; ?></span>
                                    <span class="student-id">Student ID: <?php echo $student['id']; ?></span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span style="font-size: 14px; color: #374151;" class="student-email"><?php echo $student['email']; ?></span>
                        </td>
                        <td>
                            <div class="grade-info">
                                <span class="grade-level"><?php echo $student['grade']; ?> - <?php echo $student['section']; ?></span>
                                <span class="school-year"><?php echo $student['school_year']; ?></span>
                            </div>
                        </td>
                        <td>
                            <div class="guardian-info">
                                <span class="guardian-name"><?php echo $student['guardian_name']; ?></span>
                                <span class="guardian-contact"><?php echo $student['guardian_contact']; ?></span>
                            </div>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-view" onclick="viewStudent(this)">
                                    <i class="fas fa-eye"></i> View Student
                                </button>
                                <?php if ($isAdmin): ?>
                                <button class="btn btn-id-card" onclick="printIdCard(this)">
                                    <i class="fas fa-id-card"></i> ID Card
                                </button>
                                <button class="btn btn-edit" onclick="editStudent(this)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn btn-remove" onclick="removeStudent(this)">
                                    <i class="fas fa-trash"></i> Remove
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="table-footer">
            <span class="showing-text">Showing <strong>1</strong> to <strong><?php echo min(count($students), 10); ?></strong> of <strong><?php echo count($students); ?></strong> entries</span>
            <div class="pagination">
                <button class="page-btn" disabled>«</button>
                <button class="page-btn" disabled>‹</button>
                <button class="page-btn active">1</button>
                <button class="page-btn" disabled>›</button>
                <button class="page-btn" disabled>»</button>
            </div>
        </div>
    </div>
</main>

<!-- Include Filter Modal -->
<?php include 'filter-modal.php'; ?>

<!-- Include Student View Modal -->
<?php include 'viewstudent-modal.php'; ?>

<!-- Include Edit Student Modal -->
<?php include 'editstudent-modal.php'; ?>

<!-- Include Import Student Modal -->
<?php include 'studentimport-modal.php'; ?>

<!-- Include Add Student Modal -->
<?php include 'addstudent-modal.php'; ?>

<script>
// Export Students Function - Exports ALL students
function exportStudents() {
    // Prepare CSV header
    let csvContent = "Student ID,First Name,Middle Name,Last Name,Email,Gender,Academic Year,Grade Level,Section,Guardian Name,Guardian Contact\n";
    
    // Get ALL rows (including hidden/filtered ones)
    const rows = document.querySelectorAll("#studentTableBody tr");
    let exportCount = 0;
    
    rows.forEach(row => {
        // Export ALL rows, don't skip hidden ones
        if (!row.dataset.student) return;
        
        try {
            const student = JSON.parse(row.dataset.student);
            
            // Split name into parts
            const nameParts = (student.name || '').trim().split(' ');
            let fname = '', mname = '', lname = '';
            
            if (nameParts.length >= 3) {
                fname = nameParts[0];
                mname = nameParts.slice(1, -1).join(' ');
                lname = nameParts[nameParts.length - 1];
            } else if (nameParts.length === 2) {
                fname = nameParts[0];
                lname = nameParts[1];
            } else if (nameParts.length === 1) {
                fname = nameParts[0];
            }
            
            // Create CSV row with proper escaping
            const csvRow = [
                student.id || '',
                fname,
                mname,
                lname,
                student.email || '',
                student.gender || '',
                student.school_year || '',
                student.grade || '',
                student.section || '',
                student.guardian_name || '',
                student.guardian_contact || ''
            ].map(field => `"${String(field).replace(/"/g, '""')}"`).join(',');
            
            csvContent += csvRow + "\n";
            exportCount++;
        } catch (e) {
            console.error('Error processing row:', e);
        }
    });
    
    if (exportCount === 0) {
        alert('No students to export.');
        return;
    }
    
    // Create and download file
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement("a");
    const url = URL.createObjectURL(blob);
    
    link.setAttribute("href", url);
    link.setAttribute("download", `students_export_${new Date().getTime()}.csv`);
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    alert(`Successfully exported ${exportCount} student(s)!`);
}

// View Student - Opens the view modal
function viewStudent(button) {
    try {
        const row = button.closest('tr');
        
        // Debug: Check raw data attribute
        const rawData = row.dataset.student;
        console.log('Raw data-student attribute:', rawData);
        
        // Parse JSON
        const studentData = JSON.parse(rawData);
        console.log('Parsed student data:', studentData);
        console.log('DB ID from parsed data:', studentData.db_id);
        
        // Verify db_id exists
        if (!studentData.db_id) {
            console.error('WARNING: db_id is missing from student data!');
            alert('Error: Student ID not properly loaded. Please refresh the page.');
            return;
        }
        
        openStudentModal(studentData);
    } catch (error) {
        console.error('Error in viewStudent:', error);
        alert('Error loading student data: ' + error.message);
    }
}

// Print Student ID Card (Admin only)
function printIdCard(button) {
    try {
        const row = button.closest('tr');
        const studentData = JSON.parse(row.dataset.student);
        
        if (!studentData.db_id) {
            alert('Error: Student ID not found');
            return;
        }
        
        // Open print-student-id.php with the student ID
        window.open(`print-student-id.php?id=${studentData.db_id}`, '_blank', 'width=800,height=900');
    } catch (error) {
        console.error('Error in printIdCard:', error);
        alert('Error printing ID card: ' + error.message);
    }
}

// Edit Student - Opens the edit modal
function editStudent(button) {
    const row = button.closest('tr');
    const studentData = JSON.parse(row.dataset.student);
    openEditModal(studentData);
}

// Remove Student
function removeStudent(button) {
    if (confirm('Are you sure you want to remove this student?')) {
        const row = button.closest('tr');
        const dbId = row.dataset.dbId;
        
        // Send AJAX request to delete student
        fetch('delete-student.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: dbId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                row.remove();
                updateShowingText();
                alert('Student removed successfully.');
                location.reload(); // Refresh to update counts
            } else {
                alert('Error removing student: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error removing student.');
        });
    }
}

// Update showing text after operations
function updateShowingText() {
    const totalRows = document.querySelectorAll('#studentTableBody tr:not([style*="display: none"])').length;
    const showingText = document.querySelector('.showing-text');
    if (showingText) {
        showingText.innerHTML = `Showing <strong>1</strong> to <strong>${totalRows}</strong> of <strong>${totalRows}</strong> entries`;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
    
    // Add Student Button Event Listener
    const addStudentBtn = document.getElementById('addStudentBtn');
    if (addStudentBtn) {
        addStudentBtn.addEventListener('click', function() {
            console.log('Add Student button clicked');
            openAddModal();
        });
    }

    function updateDeleteButton() {
        const checkedBoxes = document.querySelectorAll('.student-checkbox:checked');
        const count = checkedBoxes.length;
        deleteSelectedBtn.style.display = count > 0 ? 'flex' : 'none';
    }

    // Select All functionality
    selectAllCheckbox.addEventListener('change', function() {
        document.querySelectorAll('.student-checkbox').forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateDeleteButton();
    });

    // Individual checkbox change
    document.querySelectorAll('.student-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const allBoxes = document.querySelectorAll('.student-checkbox');
            const checkedBoxes = document.querySelectorAll('.student-checkbox:checked');
            selectAllCheckbox.checked = checkedBoxes.length === allBoxes.length;
            selectAllCheckbox.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < allBoxes.length;
            updateDeleteButton();
        });
    });

    // Delete Selected - Uses single delete-student.php
    deleteSelectedBtn.addEventListener('click', function() {
        const checkedBoxes = document.querySelectorAll('.student-checkbox:checked');
        const count = checkedBoxes.length;
        const ids = Array.from(checkedBoxes).map(cb => cb.value);
        
        if (confirm(`Are you sure you want to delete ${count} selected student(s)?`)) {
            // Send AJAX request to delete multiple students
            fetch('delete-student.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ ids: ids })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    checkedBoxes.forEach(checkbox => {
                        checkbox.closest('tr').remove();
                    });
                    selectAllCheckbox.checked = false;
                    selectAllCheckbox.indeterminate = false;
                    updateDeleteButton();
                    updateShowingText();
                    alert(`Successfully deleted ${count} student(s).`);
                    location.reload(); // Refresh to update counts
                } else {
                    alert('Error deleting students: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting students.');
            });
        }
    });

    // Search functionality
    document.getElementById('searchInput').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        document.querySelectorAll('#studentTableBody tr').forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
        updateShowingText();
    });
});
</script>

<?php 
include 'footer.php'; 
?>
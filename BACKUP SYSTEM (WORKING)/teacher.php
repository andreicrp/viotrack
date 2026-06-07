<?php
// teacher.php
include 'auth_check.php';

// Require login - only admin can access teacher management
requireAdmin();

include 'header.php';
include 'sidebar.php';

// Fetch teachers from database using prepared statement
$sql = "SELECT id, fname, mname, lname, email, password, position, image 
        FROM teacher 
        ORDER BY id DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}

$result = $stmt->get_result();
if (!$result) {
    die("Get result failed: " . $stmt->error);
}

$teachers = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $teachers[] = $row;
    }
}

$stmt->close();
$totalTeachers = count($teachers);
?>

<!-- Include CSS Files -->
<link rel="stylesheet" href="css/editteacher-modal.css">
<link rel="stylesheet" href="css/addteacher-modal.css">

<main class="main-content">
    <!-- Teacher List Card -->
    <div class="card teacher-card">
        <div class="card-header">
            <div class="card-title-section">
                <h3>Teacher List</h3>
                <p class="subtitle">You have <?php echo $totalTeachers; ?> teacher(s)</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-export" onclick="exportTeachers()">
                    <i class="fas fa-download"></i> Export
                </button>
                <button class="btn btn-success" style="background: #10b981; color: #fff; border: none; font-weight: 600; padding: 10px 18px; border-radius: 6px; cursor: pointer; transition: all 0.2s;" onclick="openImportModal()">
                    <i class="fas fa-file-import"></i> Import
                </button>
                <button class="btn btn-primary" style="background: #27367f; color: #fff; border: none; font-weight: 600; padding: 10px 18px; border-radius: 6px; cursor: pointer; transition: all 0.2s; box-shadow: 0 2px 8px rgba(39, 54, 127, 0.2);" id="addTeacherBtn" onclick="openAddTeacherModal()">
                    <i class="fas fa-user-plus"></i> Add new teacher
                </button>
                <button class="btn btn-danger btn-delete-selected" id="deleteSelectedBtn" style="display: none;">
                    Delete Selected
                </button>
            </div>
        </div>

        <!-- Table Controls -->
        <div class="table-controls">
            <div class="entries-control">
                <select id="entriesPerPage" class="entries-select">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span>entries per page</span>
            </div>
            <div class="search-control">
                <label>Search:</label>
                <input type="text" id="searchInput" class="search-input" placeholder="">
            </div>
        </div>

        <!-- Teacher Table -->
        <div class="table-container">
            <table class="teacher-table" id="teacherTable">
                <thead>
                    <tr>
                        <th class="checkbox-col">
                            <input type="checkbox" id="selectAll">
                        </th>
                        <th class="sortable" data-sort="name">
                            TEACHER <i class="fas fa-sort"></i>
                        </th>
                        <th class="sortable" data-sort="account">
                            ACCOUNT <i class="fas fa-sort"></i>
                        </th>
                        <th class="sortable" data-sort="action">
                            ACTION <i class="fas fa-sort"></i>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($teachers) > 0): ?>
                        <?php foreach ($teachers as $teacher): ?>
                        <tr data-teacher-id="<?php echo (int)$teacher['id']; ?>">
                            <td class="checkbox-col">
                                <input type="checkbox" class="teacher-checkbox" value="<?php echo (int)$teacher['id']; ?>">
                            </td>
                            <td>
                                <div class="teacher-info">
                                    <?php 
                                    $avatar = !empty($teacher['image']) ? htmlspecialchars($teacher['image'], ENT_QUOTES, 'UTF-8') : 'image/default.jpg';
                                    $initials = substr($teacher['fname'], 0, 1) . substr($teacher['lname'], 0, 1);
                                    $fullName = htmlspecialchars(trim($teacher['fname'] . ' ' . (!empty($teacher['mname']) ? $teacher['mname'] . ' ' : '') . $teacher['lname']), ENT_QUOTES, 'UTF-8');
                                    ?>
                                    <img src="<?php echo $avatar; ?>" 
                                         alt="<?php echo $fullName; ?>" 
                                         class="teacher-avatar" 
                                         onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($initials); ?>&background=6366f1&color=fff'">
                                    <div class="teacher-details">
                                        <span class="teacher-name"><?php echo $fullName; ?></span>
                                        <span class="teacher-role"><?php echo htmlspecialchars($teacher['position'] ?? 'Teacher', ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="account-info">
                                    <span class="account-email"><?php echo htmlspecialchars($teacher['email'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="account-password"><?php echo str_repeat('*', strlen($teacher['password'] ?? '')); ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-edit" onclick="editTeacher(<?php echo (int)$teacher['id']; ?>)">
                                        <i class="far fa-file-alt"></i> Edit
                                    </button>
                                    <button class="btn btn-remove" onclick="removeTeacher(<?php echo (int)$teacher['id']; ?>)">
                                        <i class="far fa-trash-alt"></i> Remove
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 20px;">No teachers found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Table Footer -->
        <div class="table-footer">
            <span class="showing-text">Showing <strong>1</strong> to <strong><?php echo $totalTeachers; ?></strong> of <strong><?php echo $totalTeachers; ?></strong> entry</span>
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

<!-- Include Edit Teacher Modal -->
<?php include 'editteacher-modal.php'; ?>

<!-- Include Add Teacher Modal -->
<?php include 'addteacher-modal.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const teacherCheckboxes = document.querySelectorAll('.teacher-checkbox');
    const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');

    function updateDeleteButton() {
        const checkedBoxes = document.querySelectorAll('.teacher-checkbox:checked');
        deleteSelectedBtn.style.display = checkedBoxes.length > 0 ? 'inline-flex' : 'none';
    }

    // Select All functionality
    selectAllCheckbox.addEventListener('change', function() {
        teacherCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateDeleteButton();
    });

    // Individual checkbox change
    teacherCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const allChecked = document.querySelectorAll('.teacher-checkbox:checked').length === teacherCheckboxes.length;
            const someChecked = document.querySelectorAll('.teacher-checkbox:checked').length > 0;
            selectAllCheckbox.checked = allChecked;
            selectAllCheckbox.indeterminate = someChecked && !allChecked;
            updateDeleteButton();
        });
    });

    // Delete Selected
    deleteSelectedBtn.addEventListener('click', function() {
        const checkedBoxes = document.querySelectorAll('.teacher-checkbox:checked');
        const ids = Array.from(checkedBoxes).map(cb => parseInt(cb.value)).filter(id => id > 0);
        const count = ids.length;
        
        if (count === 0) {
            alert('Please select at least one teacher to delete');
            return;
        }
        
        if (confirm(`Are you sure you want to delete ${count} selected teacher(s)? This action cannot be undone.`)) {
            // Disable button during deletion
            deleteSelectedBtn.disabled = true;
            const originalText = deleteSelectedBtn.textContent;
            deleteSelectedBtn.textContent = 'Deleting...';
            
            // Send AJAX request to delete teachers
            fetch('delete_teachers.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ ids: ids })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if(data.success) {
                    alert(`Successfully deleted ${data.deletedCount} teacher(s).${data.skippedCount > 0 ? ' (' + data.skippedCount + ' admin account(s) skipped)' : ''}`);
                    location.reload();
                } else {
                    alert('Error deleting teachers: ' + (data.message || 'Unknown error'));
                    deleteSelectedBtn.disabled = false;
                    deleteSelectedBtn.textContent = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting teachers. Please try again.');
                deleteSelectedBtn.disabled = false;
                deleteSelectedBtn.textContent = originalText;
            });
        }
    });

    // Search functionality
    document.getElementById('searchInput').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll('#teacherTable tbody tr');
        let visibleCount = 0;
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const isMatch = text.includes(searchTerm);
            row.style.display = isMatch ? '' : 'none';
            if (isMatch) visibleCount++;
        });
        
        // Show no results message if needed
        if (visibleCount === 0 && searchTerm.length > 0) {
            console.log('No teachers match your search');
        }
    });

    // Entries per page functionality
    document.getElementById('entriesPerPage').addEventListener('change', function() {
        const entriesPerPage = parseInt(this.value);
        // Implement pagination if needed
        console.log('Entries per page changed to:', entriesPerPage);
    });
});

function editTeacher(id) {
    if (!id || id <= 0) {
        alert('Invalid teacher ID');
        return;
    }

    // Fetch teacher data via AJAX
    fetch(`get_teacher.php?id=${encodeURIComponent(id)}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if(data.success && data.teacher) {
                openEditTeacherModal(data.teacher);
            } else {
                alert('Error fetching teacher data: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while fetching teacher data. Please try again.');
        });
}

function removeTeacher(id) {
    if (!id || id <= 0) {
        alert('Invalid teacher ID');
        return;
    }

    if (confirm('Are you sure you want to remove this teacher? This action cannot be undone.')) {
        // Send AJAX request to delete teacher
        fetch('delete_teacher.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: parseInt(id) })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if(data.success) {
                alert('Teacher removed successfully.');
                location.reload();
            } else {
                alert('Error removing teacher: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while removing the teacher. Please try again.');
        });
    }
}

function openAddTeacherModal() {
    const modal = document.getElementById('addTeacherModal');
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    } else {
        console.error('Add teacher modal not found');
    }
}

// Export Teachers - Exports ALL teachers
function exportTeachers() {
    // Prepare CSV header
    let csvContent = "First Name,Middle Name,Last Name,Email,Position,Password\n";
    
    // Get ALL rows from the table
    const rows = document.querySelectorAll("#teacherTable tbody tr");
    let exportCount = 0;
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length < 4) return;
        
        try {
            // Extract teacher info from the row
            const teacherName = cells[1].querySelector('.teacher-name')?.textContent || '';
            const teacherRole = cells[1].querySelector('.teacher-role')?.textContent || '';
            const email = cells[2].querySelector('.account-email')?.textContent || '';
            const passwordMask = cells[2].querySelector('.account-password')?.textContent || '';
            
            // Parse name - assuming format "FirstName MiddleName LastName"
            const nameParts = teacherName.trim().split(' ');
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
                fname,
                mname,
                lname,
                email,
                teacherRole,
                '' // Password will be empty - must be set during import
            ].map(field => `"${String(field).replace(/"/g, '""')}"`).join(',');
            
            csvContent += csvRow + "\n";
            exportCount++;
        } catch (e) {
            console.error('Error processing row:', e);
        }
    });
    
    if (exportCount === 0) {
        alert('No teachers to export.');
        return;
    }
    
    // Create and download file
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement("a");
    const url = URL.createObjectURL(blob);
    
    link.setAttribute("href", url);
    link.setAttribute("download", `teachers_export_${new Date().getTime()}.csv`);
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    alert(`Successfully exported ${exportCount} teacher(s)!`);
}

// Open Import Modal
function openImportModal() {
    const modal = document.createElement('div');
    modal.id = 'importTeacherModal';
    modal.innerHTML = `
        <div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 20px;">
            <div style="background: white; border-radius: 12px; padding: 30px; max-width: 600px; width: 100%; box-shadow: 0 4px 20px rgba(0,0,0,0.15); max-height: 90vh; overflow-y: auto;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                    <h2 style="margin: 0; color: #1e293b; font-size: 20px; font-weight: 700;">
                        <i class="fas fa-file-import" style="color: #f59e0b; margin-right: 8px;"></i>Import Teachers
                    </h2>
                    <button onclick="closeImportModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #94a3b8; padding: 0;">×</button>
                </div>
                
                <!-- How to Import Section -->
                <div style="background: #eff6ff; border-left: 4px solid #3b82f6; padding: 16px; border-radius: 6px; margin-bottom: 24px;">
                    <h3 style="margin: 0 0 12px 0; color: #1e40af; font-size: 14px; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-info-circle"></i> How to Import Teachers
                    </h3>
                    <ol style="margin: 0; padding-left: 20px; color: #1e40af; font-size: 13px; line-height: 1.6;">
                        <li>Download the CSV template below</li>
                        <li>Fill in the teacher information in the template</li>
                        <li>Save the file as CSV format</li>
                        <li>Upload the completed CSV file below</li>
                        <li>Click "Import Teachers" to complete the process</li>
                    </ol>
                </div>
                
                <!-- Download Template Section -->
                <div style="background: #f5f7fa; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 24px; text-align: center; margin-bottom: 24px;">
                    <p style="margin: 0 0 16px 0; color: #64748b; font-size: 14px;">Need a template? Download our sample CSV file to get started.</p>
                    <button onclick="downloadTeacherTemplate()" style="background: #10b981; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; box-shadow: 0 2px 8px rgba(16, 185, 129, 0.2);" onmouseover="this.style.boxShadow='0 4px 12px rgba(16, 185, 129, 0.3)'; this.style.transform='translateY(-2px)'" onmouseout="this.style.boxShadow='0 2px 8px rgba(16, 185, 129, 0.2)'; this.style.transform='translateY(0)'">
                        <i class="fas fa-download"></i> Download CSV Template
                    </button>
                </div>
                
                <!-- Upload Section -->
                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #334155; margin-bottom: 12px;">Upload CSV File <span style="color: #ef4444;">*</span></label>
                    <div style="border: 2px dashed #cbd5e1; border-radius: 8px; padding: 32px 20px; text-align: center; background: #f9fafb; cursor: pointer; transition: all 0.2s;" id="uploadArea">
                        <div style="margin-bottom: 12px;">
                            <i class="fas fa-cloud-upload-alt" style="font-size: 32px; color: #cbd5e1; display: block; margin-bottom: 12px;"></i>
                            <p style="margin: 0 0 4px 0; color: #f59e0b; font-weight: 600; cursor: pointer;">Click to upload</p>
                            <p style="margin: 0; color: #94a3b8; font-size: 13px;">or drag and drop</p>
                        </div>
                        <p style="margin: 12px 0 0 0; color: #94a3b8; font-size: 12px;">CSV files only (Max 5MB)</p>
                    </div>
                    <input type="file" id="importFile" accept=".csv" style="display: none;">
                    <p id="fileName" style="margin: 12px 0 0 0; font-size: 13px; color: #64748b;">No file chosen</p>
                </div>
                
                <!-- CSV Format Info -->
                <div style="background: #f0f9ff; border-left: 3px solid #06b6d4; padding: 12px; border-radius: 6px; margin-bottom: 24px; font-size: 13px; color: #0c4a6e;">
                    <strong>CSV Format:</strong><br>
                    First Name, Middle Name, Last Name, Email, Position, Password
                </div>
                
                <!-- Buttons -->
                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button onclick="closeImportModal()" style="padding: 12px 24px; border: 1px solid #e2e8f0; background: white; border-radius: 6px; cursor: pointer; font-weight: 600; color: #334155; font-size: 14px; transition: all 0.2s;" onmouseover="this.style.background='#f1f5f9'; this.style.borderColor='#cbd5e1'" onmouseout="this.style.background='white'; this.style.borderColor='#e2e8f0'">Cancel</button>
                    <button id="importBtn" onclick="processImport()" style="padding: 12px 24px; background: #27367f; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; box-shadow: 0 2px 8px rgba(39, 54, 127, 0.2);" onmouseover="this.style.background='#1a2557'; this.style.boxShadow='0 4px 12px rgba(39, 54, 127, 0.3)'; this.style.transform='translateY(-2px)'" onmouseout="this.style.background='#27367f'; this.style.boxShadow='0 2px 8px rgba(39, 54, 127, 0.2)'; this.style.transform='translateY(0)'">
                        <i class="fas fa-upload"></i> Import Teachers
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';
    
    // Setup file input and drag-drop
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('importFile');
    const fileName = document.getElementById('fileName');
    
    uploadArea.addEventListener('click', () => fileInput.click());
    
    fileInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            fileName.textContent = this.files[0].name;
            fileName.style.color = '#10b981';
        }
    });
    
    // Drag and drop
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.style.borderColor = '#3b82f6';
        uploadArea.style.background = '#eff6ff';
    });
    
    uploadArea.addEventListener('dragleave', () => {
        uploadArea.style.borderColor = '#cbd5e1';
        uploadArea.style.background = '#f9fafb';
    });
    
    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.style.borderColor = '#cbd5e1';
        uploadArea.style.background = '#f9fafb';
        
        if (e.dataTransfer.files && e.dataTransfer.files[0]) {
            fileInput.files = e.dataTransfer.files;
            fileName.textContent = e.dataTransfer.files[0].name;
            fileName.style.color = '#10b981';
        }
    });
}

// Download Teacher CSV Template
function downloadTeacherTemplate() {
    const csvContent = "First Name,Middle Name,Last Name,Email,Position,Password\n" +
        "John,M.,Doe,john.doe@example.com,Teacher,password123\n" +
        "Jane,,Smith,jane.smith@example.com,Head Teacher,password456\n" +
        "Robert,Lee,Johnson,robert.johnson@example.com,Department Head,password789";
    
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement("a");
    const url = URL.createObjectURL(blob);
    
    link.setAttribute("href", url);
    link.setAttribute("download", "teachers_template.csv");
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function closeImportModal() {
    const modal = document.getElementById('importTeacherModal');
    if (modal) {
        modal.remove();
        document.body.style.overflow = '';
    }
}

function processImport() {
    const fileInput = document.getElementById('importFile');
    if (!fileInput.files[0]) {
        alert('Please select a CSV file');
        return;
    }
    
    const file = fileInput.files[0];
    const reader = new FileReader();
    
    reader.onload = function(e) {
        try {
            const csv = e.target.result;
            const lines = csv.split('\n');
            
            if (lines.length < 2) {
                alert('CSV file is empty');
                return;
            }
            
            const teachers = [];
            
            // Skip header row (line 0)
            for (let i = 1; i < lines.length; i++) {
                const line = lines[i].trim();
                if (!line) continue;
                
                // Parse CSV line (handles quoted fields)
                const fields = [];
                let currentField = '';
                let inQuotes = false;
                
                for (let j = 0; j < line.length; j++) {
                    const char = line[j];
                    if (char === '"') {
                        inQuotes = !inQuotes;
                    } else if (char === ',' && !inQuotes) {
                        fields.push(currentField.trim().replace(/^"|"$/g, ''));
                        currentField = '';
                    } else {
                        currentField += char;
                    }
                }
                fields.push(currentField.trim().replace(/^"|"$/g, ''));
                
                if (fields.length >= 6) {
                    teachers.push({
                        fname: fields[0].trim(),
                        mname: fields[1].trim(),
                        lname: fields[2].trim(),
                        email: fields[3].trim(),
                        position: fields[4].trim(),
                        password: fields[5].trim()
                    });
                }
            }
            
            if (teachers.length === 0) {
                alert('No valid teacher records found in CSV');
                return;
            }
            
            // Send to server for import
            fetch('import-teachers.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ teachers: teachers })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Successfully imported ${data.imported} teacher(s)!`);
                    closeImportModal();
                    location.reload();
                } else {
                    alert('Error importing teachers: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error importing teachers');
            });
        } catch (error) {
            console.error('Error parsing CSV:', error);
            alert('Error parsing CSV file: ' + error.message);
        }
    };
    
    reader.readAsText(file);
}

</script>

<?php 
$stmt->close();
$conn->close();
include 'footer.php'; 
?>
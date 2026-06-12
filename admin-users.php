<?php
// admin-users.php
include 'auth_check.php';

// Require login - only admin can access admin user management
requireAdmin();

include 'header.php';
include 'sidebar.php';

// Fetch admin users from database using prepared statement
$sql = "SELECT id, fname, mname, lname, email, password, role, image 
        FROM admin 
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
    die("Get result failed: " . $conn->error);
}

$adminUsers = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $adminUsers[] = $row;
    }
}

$stmt->close();
$totalAdminUsers = count($adminUsers);
?>

<!-- Include CSS Files -->
<link rel="stylesheet" href="css/editadmin-modal.css">
<link rel="stylesheet" href="css/addadmin-modal.css">

<main class="main-content">
    <!-- Admin Users List Card -->
    <div class="card teacher-card">
        <div class="card-header">
            <div class="card-title-section">
                <h3>Admin Users List</h3>
                <p class="subtitle">You have <?php echo $totalAdminUsers; ?> admin user(s)</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-export" onclick="exportAdminUsers()">
                    <i class="fas fa-download"></i> Export
                </button>
                <button class="btn btn-success" style="background: #10b981; color: #fff; border: none; font-weight: 600; padding: 10px 18px; border-radius: 6px; cursor: pointer; transition: all 0.2s;" onclick="openImportModal()">
                    <i class="fas fa-file-import"></i> Import
                </button>
                <button class="btn btn-primary" style="background: #27367f; color: #fff; border: none; font-weight: 600; padding: 10px 18px; border-radius: 6px; cursor: pointer; transition: all 0.2s; box-shadow: 0 2px 8px rgba(39, 54, 127, 0.2);" id="addAdminUserBtn" onclick="openAddAdminUserModal()">
                    <i class="fas fa-user-plus"></i> Add new admin user
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

        <!-- Admin Users Table -->
        <div class="table-container">
            <table class="teacher-table" id="adminUserTable">
                <thead>
                    <tr>
                        <th class="checkbox-col">
                            <input type="checkbox" id="selectAll">
                        </th>
                        <th class="sortable" data-sort="name">
                            ADMIN USER <i class="fas fa-sort"></i>
                        </th>
                        <th class="sortable" data-sort="role">
                            ROLE <i class="fas fa-sort"></i>
                        </th>
                        <th class="sortable" data-sort="account">
                            EMAIL <i class="fas fa-sort"></i>
                        </th>
                        <th class="sortable" data-sort="action">
                            ACTION <i class="fas fa-sort"></i>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($adminUsers) > 0): ?>
                        <?php foreach ($adminUsers as $adminUser): ?>
                        <tr data-admin-user-id="<?php echo (int)$adminUser['id']; ?>">
                            <td class="checkbox-col">
                                <input type="checkbox" class="admin-user-checkbox" value="<?php echo (int)$adminUser['id']; ?>">
                            </td>
                            <td>
                                <div class="teacher-info">
                                    <?php 
                                    $avatar = !empty($adminUser['image']) ? htmlspecialchars($adminUser['image'], ENT_QUOTES, 'UTF-8') : 'image/default.jpg';
                                    $initials = substr($adminUser['fname'], 0, 1) . substr($adminUser['lname'], 0, 1);
                                    $fullName = htmlspecialchars(trim($adminUser['fname'] . ' ' . (!empty($adminUser['mname']) ? $adminUser['mname'] . ' ' : '') . $adminUser['lname']), ENT_QUOTES, 'UTF-8');
                                    ?>
                                    <img src="<?php echo $avatar; ?>" 
                                         alt="<?php echo $fullName; ?>" 
                                         class="teacher-avatar" 
                                         onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($initials); ?>&background=6366f1&color=fff'">
                                    <div class="teacher-details">
                                        <span class="teacher-name"><?php echo $fullName; ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="role-badge" style="background: #dbeafe; color: #1e40af; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600;">
                                    <?php echo htmlspecialchars($adminUser['role'] ?? 'Admin', ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td>
                                <span class="account-email"><?php echo htmlspecialchars($adminUser['email'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-edit" onclick="editAdminUser(<?php echo (int)$adminUser['id']; ?>)">
                                        <i class="far fa-file-alt"></i> Edit
                                    </button>
                                    <button class="btn btn-remove" onclick="removeAdminUser(<?php echo (int)$adminUser['id']; ?>)">
                                        <i class="far fa-trash-alt"></i> Remove
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 20px;">No admin users found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Table Footer -->
        <div class="table-footer">
            <span class="showing-text">Showing <strong>1</strong> to <strong><?php echo $totalAdminUsers; ?></strong> of <strong><?php echo $totalAdminUsers; ?></strong> entry</span>
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

<!-- Include Edit Admin Modal -->
<?php include 'php/modals/editadmin-modal.php'; ?>

<!-- Include Add Admin Modal -->
<?php include 'php/modals/addadmin-modal.php'; ?>

<script>
// Define edit and remove functions BEFORE DOMContentLoaded
function editAdminUser(id) {
    if (!id || id <= 0) {
        alert('Invalid admin user ID');
        return;
    }

    // Fetch admin user data via AJAX
    fetch(`get_admin.php?id=${encodeURIComponent(id)}`)
        .then(response => {
            return response.json();
        })
        .then(data => {
            if(data.success && data.admin) {
                // Open the modal and populate with data
                const adminData = data.admin;
                
                // Set admin ID
                document.getElementById('editAdminId').value = adminData.id;
                
                // Set profile image
                const defaultImage = 'https://ui-avatars.com/api/?name=' + encodeURIComponent((adminData.fname + ' ' + adminData.lname).trim()) + '&background=6366f1&color=fff&size=140';
                document.getElementById('editAdminProfileImage').src = adminData.image || defaultImage;
                
                // Populate form fields
                document.getElementById('editAdminFirstName').value = adminData.fname || '';
                document.getElementById('editAdminMiddleName').value = adminData.mname || '';
                document.getElementById('editAdminLastName').value = adminData.lname || '';
                document.getElementById('editAdminEmail').value = adminData.email || '';
                const roleValue = adminData.role || adminData.position || '';
                document.getElementById('editAdminRole').value = roleValue;
                document.getElementById('editAdminPassword').value = '';
                
                // Reset file input
                document.getElementById('editAdminFileInput').value = '';
                document.getElementById('editAdminFileName').textContent = 'No file chosen';
                
                // Show modal
                const modal = document.getElementById('editAdminModal');
                if (modal) {
                    modal.classList.add('show');
                    document.body.style.overflow = 'hidden';
                } else {
                    console.error('Edit admin modal not found');
                }
            } else {
                alert('Error fetching admin user data: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while fetching admin user data. Please try again.');
        });
}

function removeAdminUser(id) {
    if (!id || id <= 0) {
        alert('Invalid admin user ID');
        return;
    }

    if (confirm('Are you sure you want to remove this admin user? This action cannot be undone.')) {
        // Send AJAX request to delete admin user
        fetch('php/delete_admin.php', {
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
                alert('Admin user removed successfully.');
                location.reload();
            } else {
                alert('Error removing admin user: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while removing the admin user. Please try again.');
        });
    }
}

function openAddAdminUserModal() {
    const modal = document.getElementById('addAdminModal');
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    } else {
        console.error('Add admin user modal not found');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const adminUserCheckboxes = document.querySelectorAll('.admin-user-checkbox');
    const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');

    function updateDeleteButton() {
        const checkedBoxes = document.querySelectorAll('.admin-user-checkbox:checked');
        deleteSelectedBtn.style.display = checkedBoxes.length > 0 ? 'inline-flex' : 'none';
    }

    // Select All functionality
    selectAllCheckbox.addEventListener('change', function() {
        adminUserCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateDeleteButton();
    });

    // Individual checkbox change
    adminUserCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const allChecked = document.querySelectorAll('.admin-user-checkbox:checked').length === adminUserCheckboxes.length;
            const someChecked = document.querySelectorAll('.admin-user-checkbox:checked').length > 0;
            selectAllCheckbox.checked = allChecked;
            selectAllCheckbox.indeterminate = someChecked && !allChecked;
            updateDeleteButton();
        });
    });

    // Delete Selected
    deleteSelectedBtn.addEventListener('click', function() {
        const checkedBoxes = document.querySelectorAll('.admin-user-checkbox:checked');
        const ids = Array.from(checkedBoxes).map(cb => parseInt(cb.value)).filter(id => id > 0);
        const count = ids.length;
        
        if (count === 0) {
            alert('Please select at least one admin user to delete');
            return;
        }
        
        if (confirm(`Are you sure you want to delete ${count} selected admin user(s)? This action cannot be undone.`)) {
            // Disable button during deletion
            deleteSelectedBtn.disabled = true;
            const originalText = deleteSelectedBtn.textContent;
            deleteSelectedBtn.textContent = 'Deleting...';
            
            // Send AJAX request to delete admin users
            fetch('php/delete-admin-users.php', {
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
                    alert(`Successfully deleted ${data.deletedCount} admin user(s).`);
                    location.reload();
                } else {
                    alert('Error deleting admin users: ' + (data.message || 'Unknown error'));
                    deleteSelectedBtn.disabled = false;
                    deleteSelectedBtn.textContent = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting admin users. Please try again.');
                deleteSelectedBtn.disabled = false;
                deleteSelectedBtn.textContent = originalText;
            });
        }
    });

    // Search functionality
    document.getElementById('searchInput').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll('#adminUserTable tbody tr');
        let visibleCount = 0;
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const isMatch = text.includes(searchTerm);
            row.style.display = isMatch ? '' : 'none';
            if (isMatch) visibleCount++;
        });
        
        // Show no results message if needed
        if (visibleCount === 0 && searchTerm.length > 0) {
            console.log('No admin users match your search');
        }
    });

    // Entries per page functionality
    document.getElementById('entriesPerPage').addEventListener('change', function() {
        const entriesPerPage = parseInt(this.value);
        // Implement pagination if needed
        console.log('Entries per page changed to:', entriesPerPage);
    });
});

// Export Admin Users - Exports ALL admin users
function exportAdminUsers() {
    // Prepare CSV header
    let csvContent = "First Name,Middle Name,Last Name,Email,Position,Password\n";
    
    // Get ALL rows from the table
    const rows = document.querySelectorAll("#adminUserTable tbody tr");
    let exportCount = 0;
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length < 4) return;
        
        try {
            // Extract admin user info from the row
            const adminUserName = cells[1].querySelector('.teacher-name')?.textContent || '';
            const adminUserRole = cells[1].querySelector('.teacher-role')?.textContent || '';
            const email = cells[2].querySelector('.account-email')?.textContent || '';
            const passwordMask = cells[2].querySelector('.account-password')?.textContent || '';
            
            // Parse name - assuming format "FirstName MiddleName LastName"
            const nameParts = adminUserName.trim().split(' ');
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
                adminUserRole,
                '' // Password will be empty - must be set during import
            ].map(field => `"${String(field).replace(/"/g, '""')}"`).join(',');
            
            csvContent += csvRow + "\n";
            exportCount++;
        } catch (e) {
            console.error('Error processing row:', e);
        }
    });
    
    if (exportCount === 0) {
        alert('No admin users to export.');
        return;
    }
    
    // Create and download file
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement("a");
    const url = URL.createObjectURL(blob);
    
    link.setAttribute("href", url);
    link.setAttribute("download", `admin_users_export_${new Date().getTime()}.csv`);
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    alert(`Successfully exported ${exportCount} admin user(s)!`);
}

// Open Import Modal
function openImportModal() {
    const modal = document.createElement('div');
    modal.id = 'importAdminUserModal';
    modal.innerHTML = `
        <div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 20px;">
            <div style="background: white; border-radius: 12px; padding: 30px; max-width: 600px; width: 100%; box-shadow: 0 4px 20px rgba(0,0,0,0.15); max-height: 90vh; overflow-y: auto;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                    <h2 style="margin: 0; color: #1e293b; font-size: 20px; font-weight: 700;">
                        <i class="fas fa-file-import" style="color: #f59e0b; margin-right: 8px;"></i>Import Admin Users
                    </h2>
                    <button onclick="closeImportModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #94a3b8; padding: 0;">×</button>
                </div>
                
                <!-- How to Import Section -->
                <div style="background: #eff6ff; border-left: 4px solid #3b82f6; padding: 16px; border-radius: 6px; margin-bottom: 24px;">
                    <h3 style="margin: 0 0 12px 0; color: #1e40af; font-size: 14px; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-info-circle"></i> How to Import Admin Users
                    </h3>
                    <ol style="margin: 0; padding-left: 20px; color: #1e40af; font-size: 13px; line-height: 1.6;">
                        <li>Download the CSV template below</li>
                        <li>Fill in the admin user information in the template</li>
                        <li>Save the file as CSV format</li>
                        <li>Upload the completed CSV file below</li>
                        <li>Click "Import Admin Users" to complete the process</li>
                    </ol>
                </div>
                
                <!-- Download Template Section -->
                <div style="background: #f5f7fa; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 24px; text-align: center; margin-bottom: 24px;">
                    <p style="margin: 0 0 16px 0; color: #64748b; font-size: 14px;">Need a template? Download our sample CSV file to get started.</p>
                    <button onclick="downloadAdminUserTemplate()" style="background: #10b981; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; box-shadow: 0 2px 8px rgba(16, 185, 129, 0.2);" onmouseover="this.style.boxShadow='0 4px 12px rgba(16, 185, 129, 0.3)'; this.style.transform='translateY(-2px)'" onmouseout="this.style.boxShadow='0 2px 8px rgba(16, 185, 129, 0.2)'; this.style.transform='translateY(0)'">
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
                    First Name, Middle Name, Last Name, Email, Role, Password<br>
                    <strong>Valid Roles:</strong> Admin, Super Admin, System Admin
                </div>
                
                <!-- Buttons -->
                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button onclick="closeImportModal()" style="padding: 12px 24px; border: 1px solid #e2e8f0; background: white; border-radius: 6px; cursor: pointer; font-weight: 600; color: #334155; font-size: 14px; transition: all 0.2s;" onmouseover="this.style.background='#f1f5f9'; this.style.borderColor='#cbd5e1'" onmouseout="this.style.background='white'; this.style.borderColor='#e2e8f0'">Cancel</button>
                    <button id="importBtn" onclick="processImport()" style="padding: 12px 24px; background: #27367f; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; box-shadow: 0 2px 8px rgba(39, 54, 127, 0.2);" onmouseover="this.style.background='#1a2557'; this.style.boxShadow='0 4px 12px rgba(39, 54, 127, 0.3)'; this.style.transform='translateY(-2px)'" onmouseout="this.style.background='#27367f'; this.style.boxShadow='0 2px 8px rgba(39, 54, 127, 0.2)'; this.style.transform='translateY(0)'">
                        <i class="fas fa-upload"></i> Import Admin Users
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

// Download Admin User CSV Template
function downloadAdminUserTemplate() {
    const csvContent = "First Name,Middle Name,Last Name,Email,Role,Password\n" +
        "John,M.,Admin,john.admin@example.com,Admin,password123\n" +
        "Jane,,Smith,jane.admin@example.com,Super Admin,password456\n" +
        "Robert,Lee,Wilson,robert.admin@example.com,System Admin,password789";
    
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement("a");
    const url = URL.createObjectURL(blob);
    
    link.setAttribute("href", url);
    link.setAttribute("download", "admin_users_template.csv");
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function closeImportModal() {
    const modal = document.getElementById('importAdminUserModal');
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
            
            const adminUsers = [];
            
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
                    adminUsers.push({
                        fname: fields[0].trim(),
                        mname: fields[1].trim(),
                        lname: fields[2].trim(),
                        email: fields[3].trim(),
                        role: fields[4].trim(),
                        password: fields[5].trim()
                    });
                }
            }
            
            if (adminUsers.length === 0) {
                alert('No valid admin user records found in CSV');
                return;
            }
            
            // Send to server for import
            fetch('php/import-admin-users.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ adminUsers: adminUsers })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Successfully imported ${data.imported} admin user(s)!`);
                    closeImportModal();
                    location.reload();
                } else {
                    alert('Error importing admin users: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error importing admin users');
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

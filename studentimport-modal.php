<!-- studentimport-modal.php -->
<!-- Import Student Modal -->
<div class="import-modal-overlay" id="importStudentModal">
    <div class="import-student-modal">
        <div class="import-modal-header">
            <h2>
                <i class="fas fa-file-import"></i>
                Import Students
            </h2>
            <button class="import-modal-close" onclick="closeImportModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="import-modal-body">
            <!-- Instructions -->
            <div class="import-instructions">
                <h4>
                    <i class="fas fa-info-circle"></i>
                    How to Import Students
                </h4>
                <ol>
                    <li>Download the CSV template below</li>
                    <li>Fill in the student information in the template</li>
                    <li>Save the file as CSV format</li>
                    <li>Upload the completed CSV file below</li>
                    <li>Click "Import Students" to complete the process</li>
                </ol>
            </div>
            
            <!-- Download Template -->
            <div class="download-template-section">
                <p>Need a template? Download our sample CSV file to get started.</p>
                <button class="btn-download-template" onclick="downloadTemplate()">
                    <i class="fas fa-download"></i>
                    Download CSV Template
                </button>
            </div>
            
            <!-- File Upload -->
            <div class="file-upload-section">
                <label class="file-upload-label">
                    Upload CSV File <span class="required">*</span>
                </label>
                <div class="file-upload-area" id="fileUploadArea" onclick="document.getElementById('importFileInput').click()">
                    <input 
                        type="file" 
                        id="importFileInput" 
                        class="file-input-hidden" 
                        accept=".csv"
                        onchange="handleFileSelect(event)"
                    >
                    <div class="file-upload-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <div class="file-upload-text">
                        <strong>Click to upload</strong> or drag and drop
                    </div>
                    <div class="file-upload-hint">
                        CSV files only (Max 5MB)
                    </div>
                </div>
                
                <!-- Selected File Display -->
                <div class="selected-file" id="selectedFileDisplay">
                    <div class="file-icon">
                        <i class="fas fa-file-csv"></i>
                    </div>
                    <div class="file-info">
                        <div class="file-name" id="selectedFileName">filename.csv</div>
                        <div class="file-size" id="selectedFileSize">0 KB</div>
                    </div>
                    <button class="remove-file-btn" onclick="removeSelectedFile()" type="button">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <!-- Import Progress -->
            <div class="import-progress" id="importProgress">
                <div class="progress-header">
                    <span class="progress-label">Importing students...</span>
                    <span class="progress-percentage" id="progressPercentage">0%</span>
                </div>
                <div class="progress-bar-container">
                    <div class="progress-bar" id="progressBar"></div>
                </div>
                <div id="importResultMessage" style="margin-top: 10px; font-size: 14px;"></div>
            </div>
        </div>
        
        <div class="import-modal-footer">
            <button class="import-modal-btn btn-cancel-import" onclick="closeImportModal()">
                Cancel
            </button>
            <button class="import-modal-btn btn-import-students" id="importBtn" onclick="importStudents()" disabled>
                <i class="fas fa-file-import"></i>
                Import Students
            </button>
        </div>
    </div>
</div>

<script>
let selectedFile = null;

// Open Import Modal
function openImportModal() {
    document.getElementById('importStudentModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

// Close Import Modal
function closeImportModal() {
    document.getElementById('importStudentModal').classList.remove('show');
    document.body.style.overflow = '';
    resetImportForm();
}

// Reset Import Form
function resetImportForm() {
    selectedFile = null;
    document.getElementById('importFileInput').value = '';
    document.getElementById('selectedFileDisplay').classList.remove('show');
    document.getElementById('importProgress').classList.remove('show');
    document.getElementById('importBtn').disabled = true;
    document.getElementById('progressBar').style.width = '0%';
    document.getElementById('progressPercentage').textContent = '0%';
    document.getElementById('importResultMessage').innerHTML = '';
}

// Handle File Selection
function handleFileSelect(event) {
    const file = event.target.files[0];
    
    if (!file) return;
    
    // Validate file type
    if (!file.name.endsWith('.csv')) {
        alert('Please select a CSV file.');
        return;
    }
    
    // Validate file size (5MB max)
    const maxSize = 5 * 1024 * 1024; // 5MB in bytes
    if (file.size > maxSize) {
        alert('File size must be less than 5MB.');
        return;
    }
    
    selectedFile = file;
    
    // Display selected file
    document.getElementById('selectedFileName').textContent = file.name;
    document.getElementById('selectedFileSize').textContent = formatFileSize(file.size);
    document.getElementById('selectedFileDisplay').classList.add('show');
    document.getElementById('importBtn').disabled = false;
}

// Remove Selected File
function removeSelectedFile() {
    event.stopPropagation();
    selectedFile = null;
    document.getElementById('importFileInput').value = '';
    document.getElementById('selectedFileDisplay').classList.remove('show');
    document.getElementById('importBtn').disabled = true;
}

// Format File Size
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

// Download Template
function downloadTemplate() {
    const csvContent = 'Student ID,First Name,Middle Name,Last Name,Email,Gender,Academic Year,Grade Level,Section,Guardian Name,Guardian Contact\n' +
        '22-1000-100,John,M,Doe,john.doe@example.com,Male,2025-2026,Grade 7,Hope,Jane Doe,639123456789\n' +
        '22-1000-101,Jane,S,Smith,jane.smith@example.com,Female,2025-2026,Grade 8,Love,John Smith,639987654321\n';
    
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    link.setAttribute('href', url);
    link.setAttribute('download', 'student_import_template.csv');
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    URL.revokeObjectURL(url);
}

// Import Students - FIXED
function importStudents() {
    if (!selectedFile) {
        alert('Please select a file to import.');
        return;
    }
    
    // Show progress
    document.getElementById('importProgress').classList.add('show');
    document.getElementById('importBtn').disabled = true;
    document.getElementById('progressBar').style.width = '0%';
    document.getElementById('progressPercentage').textContent = '0%';
    document.getElementById('importResultMessage').innerHTML = '';
    
    // Create FormData
    const formData = new FormData();
    formData.append('csv_file', selectedFile);
    
    // Simulate progress animation
    let progress = 0;
    const progressInterval = setInterval(() => {
        if (progress < 90) {
            progress += 10;
            document.getElementById('progressBar').style.width = progress + '%';
            document.getElementById('progressPercentage').textContent = progress + '%';
        }
    }, 200);
    
    // Send to server
    fetch('import-students.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        clearInterval(progressInterval);
        
        // Complete progress
        document.getElementById('progressBar').style.width = '100%';
        document.getElementById('progressPercentage').textContent = '100%';
        
        if (data.success) {
            let resultMessage = `<p style="color: #059669; font-weight: 500;">
                ✓ ${data.message}
            </p>`;
            
            if (data.errors && data.errors.length > 0) {
                resultMessage += '<p style="color: #dc2626; margin-top: 10px;"><strong>Errors:</strong></p><ul style="margin: 5px 0; padding-left: 20px; color: #dc2626;">';
                data.errors.forEach(error => {
                    resultMessage += `<li>${error}</li>`;
                });
                resultMessage += '</ul>';
            }
            
            document.getElementById('importResultMessage').innerHTML = resultMessage;
            
            setTimeout(() => {
                alert(`Import completed!\n\nSuccess: ${data.success_count} student(s)\nErrors: ${data.error_count}`);
                closeImportModal();
                location.reload(); // Refresh to show new students
            }, 2000);
        } else {
            document.getElementById('importResultMessage').innerHTML = 
                `<p style="color: #dc2626;">✗ Error: ${data.message}</p>`;
            document.getElementById('importBtn').disabled = false;
        }
    })
    .catch(error => {
        clearInterval(progressInterval);
        console.error('Error:', error);
        document.getElementById('importResultMessage').innerHTML = 
            '<p style="color: #dc2626;">✗ Error importing students. Please try again.</p>';
        document.getElementById('importBtn').disabled = false;
    });
}

// Close modal when clicking overlay
document.addEventListener('DOMContentLoaded', function() {
    const importModal = document.getElementById('importStudentModal');
    if (importModal) {
        importModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeImportModal();
            }
        });
    }
    
    // Drag and drop functionality
    const uploadArea = document.getElementById('fileUploadArea');
    if (uploadArea) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            uploadArea.addEventListener(eventName, () => {
                uploadArea.classList.add('dragover');
            }, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, () => {
                uploadArea.classList.remove('dragover');
            }, false);
        });
        
        uploadArea.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length > 0) {
                const fileInput = document.getElementById('importFileInput');
                fileInput.files = files;
                handleFileSelect({ target: fileInput });
            }
        }
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('importStudentModal')?.classList.contains('show')) {
        closeImportModal();
    }
});
</script>
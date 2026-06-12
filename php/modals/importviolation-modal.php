<!-- importviolation-modal.php -->
<!-- Import Violation Modal -->
<div class="import-violation-modal-overlay" id="importViolationModal">
    <div class="import-violation-modal">
        <div class="import-violation-modal-header">
            <h2>
                <i class="fas fa-file-import"></i>
                Import Violations
            </h2>
            <button class="import-violation-modal-close" onclick="closeImportViolationModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="import-violation-modal-body">
            <!-- Instructions -->
            <div class="import-violation-instructions">
                <h4>
                    <i class="fas fa-info-circle"></i>
                    How to Import Violations
                </h4>
                <ol>
                    <li>Download the CSV template below</li>
                    <li>Fill in the violation information in the template</li>
                    <li>Save the file as CSV format</li>
                    <li>Upload the completed CSV file below</li>
                    <li>Click "Import Violations" to complete the process</li>
                </ol>
            </div>
            
            <!-- Download Template -->
            <div class="download-violation-template-section">
                <p>Need a template? Download our sample CSV file to get started.</p>
                <button class="btn-download-violation-template" onclick="downloadViolationTemplate()">
                    <i class="fas fa-download"></i>
                    Download CSV Template
                </button>
            </div>
            
            <!-- File Upload -->
            <div class="violation-file-upload-section">
                <label class="violation-file-upload-label">
                    Upload CSV File <span class="required">*</span>
                </label>
                <div class="violation-file-upload-area" id="violationFileUploadArea" onclick="document.getElementById('importViolationFileInput').click()">
                    <input 
                        type="file" 
                        id="importViolationFileInput" 
                        class="violation-file-input-hidden" 
                        accept=".csv"
                        onchange="handleViolationFileSelect(event)"
                    >
                    <div class="violation-file-upload-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <div class="violation-file-upload-text">
                        <strong>Click to upload</strong> or drag and drop
                    </div>
                    <div class="violation-file-upload-hint">
                        CSV files only (Max 5MB)
                    </div>
                </div>
                
                <!-- Selected File Display -->
                <div class="violation-selected-file" id="violationSelectedFileDisplay">
                    <div class="violation-file-icon">
                        <i class="fas fa-file-csv"></i>
                    </div>
                    <div class="violation-file-info">
                        <div class="violation-file-name" id="violationSelectedFileName">filename.csv</div>
                        <div class="violation-file-size" id="violationSelectedFileSize">0 KB</div>
                    </div>
                    <button class="remove-violation-file-btn" onclick="removeSelectedViolationFile()" type="button">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <!-- Import Progress -->
            <div class="import-violation-progress" id="importViolationProgress">
                <div class="violation-progress-header">
                    <span class="violation-progress-label">Importing violations...</span>
                    <span class="violation-progress-percentage" id="violationProgressPercentage">0%</span>
                </div>
                <div class="violation-progress-bar-container">
                    <div class="violation-progress-bar" id="violationProgressBar"></div>
                </div>
            </div>
        </div>
        
        <div class="import-violation-modal-footer">
            <button class="import-violation-modal-btn btn-cancel-import-violation" onclick="closeImportViolationModal()">
                Cancel
            </button>
            <button class="import-violation-modal-btn btn-import-violations" id="importViolationBtn" onclick="importViolations()" disabled>
                <i class="fas fa-file-import"></i>
                Import Violations
            </button>
        </div>
    </div>
</div>

<script>
let selectedViolationFile = null;

// Open Import Violation Modal
function openImportViolationModal() {
    document.getElementById('importViolationModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

// Close Import Violation Modal
function closeImportViolationModal() {
    document.getElementById('importViolationModal').classList.remove('show');
    document.body.style.overflow = '';
    resetImportViolationForm();
}

// Reset Import Violation Form
function resetImportViolationForm() {
    selectedViolationFile = null;
    document.getElementById('importViolationFileInput').value = '';
    document.getElementById('violationSelectedFileDisplay').classList.remove('show');
    document.getElementById('importViolationProgress').classList.remove('show');
    document.getElementById('importViolationBtn').disabled = true;
    document.getElementById('violationProgressBar').style.width = '0%';
    document.getElementById('violationProgressPercentage').textContent = '0%';
}

// Handle Violation File Selection
function handleViolationFileSelect(event) {
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
    
    selectedViolationFile = file;
    
    // Display selected file
    document.getElementById('violationSelectedFileName').textContent = file.name;
    document.getElementById('violationSelectedFileSize').textContent = formatViolationFileSize(file.size);
    document.getElementById('violationSelectedFileDisplay').classList.add('show');
    document.getElementById('importViolationBtn').disabled = false;
}

// Remove Selected Violation File
function removeSelectedViolationFile() {
    event.stopPropagation();
    selectedViolationFile = null;
    document.getElementById('importViolationFileInput').value = '';
    document.getElementById('violationSelectedFileDisplay').classList.remove('show');
    document.getElementById('importViolationBtn').disabled = true;
}

// Format Violation File Size
function formatViolationFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

// Download Violation Template
function downloadViolationTemplate() {
    const csvContent = 'data:text/csv;charset=utf-8,' + 
        'Violation Title,Description,Type\n' +
        'Late Arrival,Student arrived late to class,Minor\n' +
        'Use of Gadget,Student used mobile phone during class,Serious\n' +
        'Physical Assault,Student engaged in physical altercation,Major\n';
    
    const link = document.createElement('a');
    link.setAttribute('href', encodeURI(csvContent));
    link.setAttribute('download', 'violation_import_template.csv');
    document.body.appendChild(link);
    link.click();
    link.remove();
}

// Import Violations
function importViolations() {
    if (!selectedViolationFile) {
        alert('Please select a file to import.');
        return;
    }
    
    // Show progress
    document.getElementById('importViolationProgress').classList.add('show');
    document.getElementById('importViolationBtn').disabled = true;
    
    // Read file
    const reader = new FileReader();
    
    reader.onload = function(e) {
        const csvData = e.target.result;
        processViolationCSV(csvData);
    };
    
    reader.onerror = function() {
        alert('Error reading file. Please try again.');
        resetViolationProgress();
    };
    
    reader.readAsText(selectedViolationFile);
}

// Process Violation CSV Data
function processViolationCSV(csvData) {
    try {
        // Split by lines
        const lines = csvData.split('\n').filter(line => line.trim());
        
        if (lines.length < 2) {
            alert('CSV file is empty or invalid.');
            resetViolationProgress();
            return;
        }
        
        // Skip header row
        const dataRows = lines.slice(1);
        const totalRows = dataRows.length;
        let processedRows = 0;
        
        // Parse CSV data into violations
        const violations = [];
        const importedTitles = new Set();
        
        dataRows.forEach(line => {
            const parts = line.split(',').map(part => part.trim().replace(/^"|"$/g, ''));
            if (parts.length >= 3) {
                const title = parts[0];
                const lowerTitle = title.toLowerCase();
                
                // Check for duplicates within import file itself
                if (importedTitles.has(lowerTitle)) {
                    alert(`⚠️ Duplicate violation found in import file: "${title}". Duplicates will be skipped.`);
                    return; // Skip this violation
                }
                
                importedTitles.add(lowerTitle);
                violations.push({
                    title: title,
                    description: parts[1],
                    type: parts[2]
                });
            }
        });
        
        if (violations.length === 0) {
            alert('No valid violation data found in CSV file.');
            resetViolationProgress();
            return;
        }
        
        // Send to server for database insertion
        fetch('php/import-violations-handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ violations: violations })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Simulate progress completion
                let currentProgress = 30;
                const progressInterval = setInterval(() => {
                    currentProgress += 10;
                    if (currentProgress > 100) currentProgress = 100;
                    
                    document.getElementById('violationProgressBar').style.width = currentProgress + '%';
                    document.getElementById('violationProgressPercentage').textContent = currentProgress + '%';
                    
                    if (currentProgress >= 100) {
                        clearInterval(progressInterval);
                        
                        setTimeout(() => {
                            alert(`Successfully imported ${data.imported} violation(s)!`);
                            closeImportViolationModal();
                            location.reload();
                        }, 500);
                    }
                }, 50);
            } else {
                alert('Error importing violations: ' + (data.message || 'Unknown error'));
                resetViolationProgress();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error importing violations: ' + error.message);
            resetViolationProgress();
        });
        
    } catch (error) {
        alert('Error processing CSV file. Please check the file format.');
        console.error('CSV Processing Error:', error);
        resetViolationProgress();
    }
}

// Reset Violation Progress
function resetViolationProgress() {
    document.getElementById('importViolationProgress').classList.remove('show');
    document.getElementById('importViolationBtn').disabled = false;
    document.getElementById('violationProgressBar').style.width = '0%';
    document.getElementById('violationProgressPercentage').textContent = '0%';
}

// Close modal when clicking overlay
document.addEventListener('DOMContentLoaded', function() {
    const importViolationModal = document.getElementById('importViolationModal');
    if (importViolationModal) {
        importViolationModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeImportViolationModal();
            }
        });
    }
    
    // Drag and drop functionality
    const violationUploadArea = document.getElementById('violationFileUploadArea');
    if (violationUploadArea) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            violationUploadArea.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            violationUploadArea.addEventListener(eventName, () => {
                violationUploadArea.classList.add('dragover');
            }, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            violationUploadArea.addEventListener(eventName, () => {
                violationUploadArea.classList.remove('dragover');
            }, false);
        });
        
        violationUploadArea.addEventListener('drop', handleViolationDrop, false);
        
        function handleViolationDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length > 0) {
                const fileInput = document.getElementById('importViolationFileInput');
                fileInput.files = files;
                handleViolationFileSelect({ target: fileInput });
            }
        }
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('importViolationModal')?.classList.contains('show')) {
        closeImportViolationModal();
    }
});
</script>

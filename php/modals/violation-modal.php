<!-- violation-modal.php -->
<!-- Include CSS if not already loaded -->
<link rel="stylesheet" href="css/violation-modal.css">

<!-- Add/Edit Violation Modal -->
<div class="violation-modal-overlay" id="violationModal">
    <div class="violation-modal">
        <div class="violation-modal-header">
            <h2 id="violationModalTitle">Add Violation</h2>
            <button class="violation-modal-close" onclick="closeViolationModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="violation-modal-body">
            <!-- Violation Form -->
            <form class="violation-form" id="violationForm">
                <!-- Hidden field for violation ID (for editing) -->
                <input type="hidden" id="violationId" name="violation_id" value="">
                
                <!-- Violation Title -->
                <div class="violation-form-group">
                    <label class="violation-form-label">
                        Violation Title <span class="violation-required">*</span>
                    </label>
                    <input 
                        type="text" 
                        class="violation-form-input" 
                        id="violationTitle" 
                        name="violation_title" 
                        placeholder="Enter violation title"
                        required
                    >
                </div>
                
                <!-- Description -->
                <div class="violation-form-group">
                    <label class="violation-form-label">
                        Description <span class="violation-required">*</span>
                    </label>
                    <textarea 
                        class="violation-form-textarea" 
                        id="violationDescription" 
                        name="violation_description" 
                        placeholder="Enter violation description"
                        required
                    ></textarea>
                </div>
                
                <!-- Violation Type -->
                <div class="violation-form-group">
                    <label class="violation-form-label">
                        Violation Type <span class="violation-required">*</span>
                    </label>
                    <select 
                        class="violation-form-select" 
                        id="violationType" 
                        name="violation_type" 
                        required
                    >
                        <option value="">Select violation type</option>
                        <option value="Minor" class="type-option-minor">Minor</option>
                        <option value="Serious" class="type-option-serious">Serious</option>
                        <option value="Major" class="type-option-major">Major</option>
                    </select>
                </div>
            </form>
        </div>
        
        <div class="violation-modal-footer">
            <button class="violation-modal-btn btn-save-violation" onclick="saveViolation()">
                <i class="fas fa-save"></i>
                <span id="violationSaveBtnText">Save</span>
            </button>
            <button class="violation-modal-btn btn-cancel-violation" onclick="closeViolationModal()">
                Close
            </button>
        </div>
    </div>
</div>

<script>
// Store current violation data when editing
let currentEditingViolationId = null;

// Open Add Violation Modal
function openAddViolationModal() {
    currentEditingViolationId = null;
    document.getElementById('violationModalTitle').textContent = 'Add Violation';
    document.getElementById('violationSaveBtnText').textContent = 'Save';
    resetViolationForm();
    document.getElementById('violationModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

// Open Edit Violation Modal
function openEditViolationModal(violationId) {
    currentEditingViolationId = violationId;
    document.getElementById('violationModalTitle').textContent = 'Edit Violation';
    document.getElementById('violationSaveBtnText').textContent = 'Update';
    
    // Get violation data from table row
    const row = document.querySelector(`tr[data-violation-id="${violationId}"]`);
    if (row) {
        const title = row.querySelector('.violation-title').textContent;
        const description = row.querySelector('.violation-desc').textContent;
        const type = row.getAttribute('data-type');
        
        document.getElementById('violationId').value = violationId;
        document.getElementById('violationTitle').value = title;
        document.getElementById('violationDescription').value = description;
        document.getElementById('violationType').value = type;
    }
    
    document.getElementById('violationModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

// Close Violation Modal
function closeViolationModal() {
    document.getElementById('violationModal').classList.remove('show');
    document.body.style.overflow = '';
    resetViolationForm();
}

// Reset Violation Form
function resetViolationForm() {
    document.getElementById('violationForm').reset();
    document.getElementById('violationId').value = '';
    currentEditingViolationId = null;
}

// Save Violation (Add or Update)
function saveViolation() {
    const form = document.getElementById('violationForm');
    
    // Validate form
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Get form values
    const titleInput = document.getElementById('violationTitle');
    const title = titleInput.value.trim();
    
    // Check for duplicate violation names (only for new violations)
    if (!currentEditingViolationId) {
        const rows = document.querySelectorAll('#violationTable tbody tr');
        const exists = Array.from(rows).some(row => {
            const rowTitle = row.querySelector('.violation-title')?.textContent.trim();
            return rowTitle && rowTitle.toLowerCase() === title.toLowerCase();
        });
        
        if (exists) {
            alert('⚠️ A violation with this name already exists!');
            titleInput.focus();
            titleInput.classList.add('error');
            return;
        }
    }
    
    // Remove error class if it was there
    titleInput.classList.remove('error');
    
    // Disable button to prevent double submission
    const saveBtn = document.querySelector('.btn-save-violation');
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    
    // Get form data
    const formData = new FormData(form);
    
    // Debug: Log form data
    console.log('Form Data:');
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
    }
    
    // Send AJAX request
    fetch('php/save_violation.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response ok:', response.ok);
        
        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                console.error('Response is not JSON:', text);
                throw new Error('Server returned non-JSON response: ' + text.substring(0, 200));
            });
        }
        
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        
        if (data.success) {
            alert(data.message);
            
            if (currentEditingViolationId) {
                // Update existing row
                updateViolationInTable(data.data);
            } else {
                // Add new row or reload page
                if (typeof addViolationToTable === 'function') {
                    addViolationToTable(data.data);
                } else {
                    // Reload page if addViolationToTable function doesn't exist
                    window.location.reload();
                }
            }
            
            closeViolationModal();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Detailed Error:', error);
        console.error('Error stack:', error.stack);
        alert('An error occurred while saving the violation: ' + error.message);
    })
    .finally(() => {
        // Re-enable button
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="fas fa-save"></i> <span id="violationSaveBtnText">' + 
                           (currentEditingViolationId ? 'Update' : 'Save') + '</span>';
    });
}

// Update Violation in Table
function updateViolationInTable(violationData) {
    const row = document.querySelector(`tr[data-violation-id="${violationData.id}"]`);
    if (!row) return;
    
    // Update row attributes
    row.setAttribute('data-type', violationData.type);
    
    // Update title
    const titleEl = row.querySelector('.violation-title');
    if (titleEl) titleEl.textContent = violationData.title;
    
    // Update description
    const descEl = row.querySelector('.violation-desc');
    if (descEl) descEl.textContent = violationData.description;
    
    // Update type badge
    const typeEl = row.querySelector('.violation-type');
    if (typeEl) {
        typeEl.textContent = violationData.type;
        typeEl.className = 'violation-type ' + violationData.type.toLowerCase();
    }
}

// Add Violation to Table (optional - can also just reload page)
function addViolationToTable(violationData) {
    const tbody = document.querySelector('#violationTable tbody');
    if (!tbody) {
        // If table doesn't exist, reload page
        window.location.reload();
        return;
    }
    
    // Determine type class
    const typeClass = violationData.type.toLowerCase();
    
    // Create new row
    const newRow = document.createElement('tr');
    newRow.setAttribute('data-violation-id', violationData.id);
    newRow.setAttribute('data-type', violationData.type);
    
    newRow.innerHTML = `
        <td class="checkbox-col">
            <input type="checkbox" class="violation-checkbox" value="${violationData.id}">
        </td>
        <td>
            <div class="violation-content">
                <span class="violation-title">${violationData.title}</span>
                <span class="violation-desc">${violationData.description}</span>
            </div>
        </td>
        <td>
            <span class="violation-type ${typeClass}">${violationData.type}</span>
        </td>
        <td>
            <div class="action-buttons">
                <button class="btn-action" onclick="openEditViolationModal(${violationData.id})">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <button class="btn-action btn-delete" onclick="deleteViolation(${violationData.id})">
                    <i class="fas fa-trash"></i> Remove
                </button>
            </div>
        </td>
    `;
    
    // Add to table (at the top)
    tbody.insertBefore(newRow, tbody.firstChild);
    
    // Update showing text if function exists
    if (typeof updateShowingText === 'function') {
        updateShowingText();
    }
    
    // Add checkbox listener if function exists
    const checkbox = newRow.querySelector('.violation-checkbox');
    if (checkbox && typeof updateSelectAll === 'function') {
        checkbox.addEventListener('change', updateSelectAll);
    }
}

// Delete Violation
function deleteViolation(violationId) {
    if (!confirm('Are you sure you want to delete this violation?')) {
        return;
    }
    
    // Send AJAX request
    const formData = new FormData();
    formData.append('violation_id', violationId);
    
    fetch('php/delete_violation.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            
            // Reload page to refresh the list and stats
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting the violation. Please try again.');
    });
}

// Close modal when clicking overlay
document.addEventListener('DOMContentLoaded', function() {
    const violationModal = document.getElementById('violationModal');
    if (violationModal) {
        violationModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeViolationModal();
            }
        });
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('violationModal')?.classList.contains('show')) {
        closeViolationModal();
    }
});
</script>
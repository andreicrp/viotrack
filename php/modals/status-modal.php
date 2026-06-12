<!-- status-modal.php - CORRECTED -->
<style>
/* Complete inline styles for status modal */
.status-modal-overlay {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    background: rgba(0, 0, 0, 0.5) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    z-index: 9999 !important;
    opacity: 0 !important;
    visibility: hidden !important;
    transition: all 0.3s ease !important;
    padding: 20px !important;
}

.status-modal-overlay.show {
    opacity: 1 !important;
    visibility: visible !important;
}

.status-modal {
    background: #fff !important;
    border-radius: 12px !important;
    width: 100% !important;
    max-width: 500px !important;
    max-height: 90vh !important;
    overflow: hidden !important;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2) !important;
    transform: translateY(-20px) scale(0.95) !important;
    transition: all 0.3s ease !important;
    display: flex !important;
    flex-direction: column !important;
}

.status-modal-overlay.show .status-modal {
    transform: translateY(0) scale(1) !important;
}

.status-modal-header {
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    padding: 20px 24px !important;
    border-bottom: 1px solid #e5e7eb !important;
    background: #fff !important;
    flex-shrink: 0 !important;
}

.status-modal-header h2 {
    font-size: 18px !important;
    font-weight: 600 !important;
    color: #1f2937 !important;
    margin: 0 !important;
    display: flex !important;
    align-items: center !important;
    gap: 10px !important;
}

.status-modal-header h2 i {
    color: #6366f1 !important;
}

.status-modal-close {
    width: 32px !important;
    height: 32px !important;
    border: none !important;
    background: transparent !important;
    color: #9ca3af !important;
    font-size: 20px !important;
    cursor: pointer !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    transition: all 0.2s !important;
    border-radius: 6px !important;
}

.status-modal-close:hover {
    background: #f3f4f6 !important;
    color: #1f2937 !important;
}

.status-modal-body {
    padding: 24px !important;
    overflow-y: auto !important;
    flex: 1 !important;
}

.current-status-display {
    background: #f9fafb !important;
    border: 1px solid #e5e7eb !important;
    border-radius: 8px !important;
    padding: 16px !important;
    margin-bottom: 24px !important;
}

.current-status-label {
    font-size: 13px !important;
    font-weight: 500 !important;
    color: #6b7280 !important;
    margin-bottom: 8px !important;
}

.current-status-value {
    display: inline-block !important;
    padding: 6px 14px !important;
    border-radius: 20px !important;
    font-size: 13px !important;
    font-weight: 600 !important;
}

.current-status-value.pending {
    background: #fef3c7 !important;
    color: #b45309 !important;
}

.current-status-value.resolved {
    background: #d1fae5 !important;
    color: #047857 !important;
}

.current-status-value.escalated {
    background: #fee2e2 !important;
    color: #dc2626 !important;
}

.status-selection-section {
    margin-bottom: 24px !important;
}

.status-selection-label {
    font-size: 14px !important;
    font-weight: 600 !important;
    color: #374151 !important;
    margin-bottom: 12px !important;
    display: flex !important;
    align-items: center !important;
    gap: 6px !important;
}

.status-required {
    color: #ef4444 !important;
}

.status-options {
    display: flex !important;
    flex-direction: column !important;
    gap: 12px !important;
}

.status-option {
    position: relative !important;
    cursor: pointer !important;
}

.status-option-input {
    position: absolute !important;
    opacity: 0 !important;
    cursor: pointer !important;
}

.status-option-label {
    display: flex !important;
    align-items: center !important;
    gap: 12px !important;
    padding: 16px !important;
    border: 2px solid #e5e7eb !important;
    border-radius: 10px !important;
    cursor: pointer !important;
    transition: all 0.2s !important;
    background: #fff !important;
}

.status-option-input:checked + .status-option-label {
    border-color: #6366f1 !important;
    background: #eef2ff !important;
}

.status-option-label:hover {
    border-color: #d1d5db !important;
    background: #f9fafb !important;
}

.status-option-input:checked + .status-option-label:hover {
    border-color: #6366f1 !important;
    background: #eef2ff !important;
}

.status-option-icon {
    width: 44px !important;
    height: 44px !important;
    border-radius: 10px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 20px !important;
    flex-shrink: 0 !important;
    transition: all 0.2s !important;
}

.status-option-icon.pending {
    background: #fef3c7 !important;
    color: #f59e0b !important;
}

.status-option-icon.resolved {
    background: #d1fae5 !important;
    color: #10b981 !important;
}

.status-option-icon.escalated {
    background: #fee2e2 !important;
    color: #ef4444 !important;
}

.status-option-input:checked + .status-option-label .status-option-icon.pending {
    background: #f59e0b !important;
    color: #fff !important;
}

.status-option-input:checked + .status-option-label .status-option-icon.resolved {
    background: #10b981 !important;
    color: #fff !important;
}

.status-option-input:checked + .status-option-label .status-option-icon.escalated {
    background: #ef4444 !important;
    color: #fff !important;
}

.status-option-info {
    flex: 1 !important;
}

.status-option-name {
    font-size: 15px !important;
    font-weight: 600 !important;
    color: #1f2937 !important;
    margin-bottom: 4px !important;
}

.status-option-desc {
    font-size: 13px !important;
    color: #6b7280 !important;
    line-height: 1.4 !important;
}

.status-option-radio {
    width: 20px !important;
    height: 20px !important;
    border: 2px solid #d1d5db !important;
    border-radius: 50% !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    flex-shrink: 0 !important;
    transition: all 0.2s !important;
}

.status-option-input:checked + .status-option-label .status-option-radio {
    border-color: #6366f1 !important;
    background: #6366f1 !important;
}

.status-option-radio::after {
    content: '' !important;
    width: 8px !important;
    height: 8px !important;
    border-radius: 50% !important;
    background: #fff !important;
    opacity: 0 !important;
    transition: opacity 0.2s !important;
}

.status-option-input:checked + .status-option-label .status-option-radio::after {
    opacity: 1 !important;
}

.status-modal-footer {
    display: flex !important;
    gap: 12px !important;
    padding: 20px 24px !important;
    border-top: 1px solid #e5e7eb !important;
    background: #f9fafb !important;
    flex-shrink: 0 !important;
}

.status-modal-btn {
    flex: 1 !important;
    padding: 12px 20px !important;
    border-radius: 8px !important;
    font-size: 14px !important;
    font-weight: 500 !important;
    cursor: pointer !important;
    transition: all 0.2s !important;
    border: none !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 8px !important;
}

.btn-update-status {
    background: #6366f1 !important;
    color: #fff !important;
}

.btn-update-status:hover {
    background: #4f46e5 !important;
}

.btn-update-status:disabled {
    background: #d1d5db !important;
    cursor: not-allowed !important;
}

.btn-cancel-status {
    background: #fff !important;
    color: #374151 !important;
    border: 1px solid #e5e7eb !important;
}

.btn-cancel-status:hover {
    background: #f9fafb !important;
    border-color: #d1d5db !important;
}
</style>

<!-- Status Change Modal -->
<div class="status-modal-overlay" id="statusModal">
    <div class="status-modal">
        <div class="status-modal-header">
            <h2>
                <i class="fas fa-flag"></i>
                Change Violation Status
            </h2>
            <button class="status-modal-close" onclick="closeStatusModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="status-modal-body">
            <!-- Current Status Display -->
            <div class="current-status-display">
                <div class="current-status-label">Current Status:</div>
                <span class="current-status-value" id="currentStatusBadge">Pending</span>
            </div>
            
            <!-- Status Selection -->
            <div class="status-selection-section">
                <label class="status-selection-label">
                    Select New Status <span class="status-required">*</span>
                </label>
                
                <div class="status-options">
                    <!-- Pending Option -->
                    <div class="status-option">
                        <input 
                            type="radio" 
                            id="statusPending" 
                            name="violationStatus" 
                            value="Pending" 
                            class="status-option-input"
                        >
                        <label for="statusPending" class="status-option-label">
                            <div class="status-option-icon pending">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="status-option-info">
                                <div class="status-option-name">Pending</div>
                                <div class="status-option-desc">Violation is awaiting review or action</div>
                            </div>
                            <div class="status-option-radio"></div>
                        </label>
                    </div>
                    
                    <!-- Resolved Option -->
                    <div class="status-option">
                        <input 
                            type="radio" 
                            id="statusResolved" 
                            name="violationStatus" 
                            value="Resolved" 
                            class="status-option-input"
                        >
                        <label for="statusResolved" class="status-option-label">
                            <div class="status-option-icon resolved">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="status-option-info">
                                <div class="status-option-name">Resolved</div>
                                <div class="status-option-desc">Violation has been addressed and closed</div>
                            </div>
                            <div class="status-option-radio"></div>
                        </label>
                    </div>
                    
                    <!-- Escalated Option -->
                    <div class="status-option">
                        <input 
                            type="radio" 
                            id="statusEscalated" 
                            name="violationStatus" 
                            value="Escalated" 
                            class="status-option-input"
                        >
                        <label for="statusEscalated" class="status-option-label">
                            <div class="status-option-icon escalated">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="status-option-info">
                                <div class="status-option-name">Escalated</div>
                                <div class="status-option-desc">Violation requires higher-level intervention</div>
                            </div>
                            <div class="status-option-radio"></div>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="status-modal-footer">
            <button class="status-modal-btn btn-cancel-status" onclick="closeStatusModal()">
                Cancel
            </button>
            <button class="status-modal-btn btn-update-status" id="updateStatusBtn" onclick="updateViolationStatus()" disabled>
                <i class="fas fa-check"></i>
                Update Status
            </button>
        </div>
    </div>
</div>

<script>
console.log('=== STATUS MODAL SCRIPT LOADING ===');

// Store current violation ID being edited
var currentStatusViolationId = null;

// Open Status Modal - CORRECTED WITH PROPER ATTRIBUTE ACCESS
window.openStatusModal = function(violationId) {
    console.log('✓ openStatusModal called with ID:', violationId);
    console.log('ID type:', typeof violationId);
    currentStatusViolationId = violationId;
    
    // Convert to string for comparison
    const searchId = String(violationId);
    
    // Get current status from table row
    const allRows = document.querySelectorAll('#violationTableBody tr[data-violation-id]');
    let row = null;
    
    for (let r of allRows) {
        const rowId = r.getAttribute('data-violation-id');
        if (String(rowId) === searchId) {
            row = r;
            break;
        }
    }
    
    console.log('Row found:', row);
    
    if (row) {
        const currentStatus = row.getAttribute('data-status');
        console.log('Current status:', currentStatus);
        const badge = document.getElementById('currentStatusBadge');
        if (badge) {
            badge.textContent = currentStatus;
            badge.className = 'current-status-value ' + currentStatus.toLowerCase();
        }
    }
    
    // Reset radio buttons
    document.querySelectorAll('input[name="violationStatus"]').forEach(radio => {
        radio.checked = false;
    });
    
    // Disable update button initially
    const updateBtn = document.getElementById('updateStatusBtn');
    if (updateBtn) {
        updateBtn.disabled = true;
    }
    
    // Show modal
    const modal = document.getElementById('statusModal');
    console.log('Modal element:', modal);
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
        console.log('✓ Modal should be visible now, classes:', modal.className);
    } else {
        console.error('✗ Modal element not found!');
    }
};

// Close Status Modal
window.closeStatusModal = function() {
    console.log('Closing modal');
    const modal = document.getElementById('statusModal');
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }
    currentStatusViolationId = null;
};

// Update Violation Status - CORRECTED WITH DATABASE UPDATE
window.updateViolationStatus = function() {
    console.log('Updating status for violation ID:', currentStatusViolationId);
    
    // Get selected status
    const selectedRadio = document.querySelector('input[name="violationStatus"]:checked');
    if (!selectedRadio) {
        alert('Please select a status');
        return;
    }
    
    const newStatus = selectedRadio.value;
    console.log('New status:', newStatus);
    
    // Disable button to prevent double-click
    const updateBtn = document.getElementById('updateStatusBtn');
    updateBtn.disabled = true;
    updateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
    
    // Send update to server
    fetch('php/update-status-handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update_status&record_id=${currentStatusViolationId}&new_status=${newStatus}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Convert to string for comparison
            const searchId = String(currentStatusViolationId);
            
            // Find the row - search ALL rows including hidden ones
            let row = null;
            const allRows = document.querySelectorAll('#violationTableBody tr[data-violation-id]');
            
            for (let r of allRows) {
                const rowId = r.getAttribute('data-violation-id');
                if (String(rowId) === searchId) {
                    row = r;
                    break;
                }
            }
            
            if (row) {
                const statusBadge = row.querySelector('.status-badge');
                statusBadge.textContent = newStatus;
                statusBadge.className = 'status-badge ' + newStatus.toLowerCase();
                row.setAttribute('data-status', newStatus);
                
                // Add or remove Doc Proof button based on status
                const actionButtons = row.querySelector('.action-buttons');
                const existingDocBtn = actionButtons.querySelector('.btn-doc-proof');
                
                if (newStatus === 'Resolved' && !existingDocBtn) {
                    const docBtn = document.createElement('button');
                    docBtn.className = 'btn-doc-proof';
                    docBtn.innerHTML = '<i class="fas fa-file-alt"></i> Doc Proof';
                    
                    // CRITICAL FIX: Store the violation ID in a closure properly
                    const violationId = currentStatusViolationId;
                    docBtn.onclick = function() {
                        console.log('Doc Proof clicked for violation ID:', violationId);
                        viewResolutionLetter(violationId);
                    };
                    
                    const deleteBtn = actionButtons.querySelector('.btn-delete');
                    if (deleteBtn) {
                        actionButtons.insertBefore(docBtn, deleteBtn);
                    } else {
                        actionButtons.appendChild(docBtn);
                    }
                    
                    console.log('Doc Proof button added for ID:', violationId);
                } else if (newStatus !== 'Resolved' && existingDocBtn) {
                    existingDocBtn.remove();
                    console.log('Doc Proof button removed');
                }
            }
            
            // Show success message
            alert(`Status updated successfully to "${newStatus}"!`);
            
            // Close modal
            window.closeStatusModal();
        } else {
            alert('Error: ' + (data.message || 'Failed to update status'));
            updateBtn.disabled = false;
            updateBtn.innerHTML = '<i class="fas fa-check"></i> Update Status';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating status: ' + error.message);
        updateBtn.disabled = false;
        updateBtn.innerHTML = '<i class="fas fa-check"></i> Update Status';
    });
};

console.log('✓ Functions declared:', typeof window.openStatusModal, typeof window.closeStatusModal, typeof window.updateViolationStatus);

// Initialize status modal event listeners when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('✓ Status modal DOMContentLoaded fired');
    
    // Listen for radio button changes
    const statusRadios = document.querySelectorAll('input[name="violationStatus"]');
    const updateBtn = document.getElementById('updateStatusBtn');
    
    console.log('Radio buttons found:', statusRadios.length);
    console.log('Update button found:', updateBtn);
    
    if (statusRadios && updateBtn) {
        statusRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                console.log('Radio changed to:', this.value);
                updateBtn.disabled = false;
            });
        });
    }
    
    // Close modal when clicking overlay
    const statusModal = document.getElementById('statusModal');
    if (statusModal) {
        console.log('✓ Modal element found, adding event listeners');
        statusModal.addEventListener('click', function(e) {
            if (e.target === this) {
                window.closeStatusModal();
            }
        });
    }
    
    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && statusModal?.classList.contains('show')) {
            window.closeStatusModal();
        }
    });
});

console.log('=== STATUS MODAL SCRIPT LOADED ===');
</script><?php
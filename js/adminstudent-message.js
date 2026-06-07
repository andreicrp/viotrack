// adminstudent-message.js

// Function to be called from adminstudentviolation.php
function sendMessage() {
    if (typeof studentId !== 'undefined') {
        window.location.href = `adminstudent-message.php?student_id=${studentId}`;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const reportForm = document.getElementById('reportForm');
    const reportDateInput = document.getElementById('reportdate');
    
    // Set minimum date to today
    if (reportDateInput) {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        
        const minDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
        reportDateInput.min = minDateTime;
    }
    
    // Form submission handler
    if (reportForm) {
        reportForm.addEventListener('submit', function(e) {
            // Validate form before submission
            const reportType = document.getElementById('reporttype').value;
            const reportDate = document.getElementById('reportdate').value;
            
            if (!reportType) {
                e.preventDefault();
                alert('Please select a report type');
                document.getElementById('reporttype').focus();
                return false;
            }
            
            if (!reportDate) {
                e.preventDefault();
                alert('Please select a date and time');
                document.getElementById('reportdate').focus();
                return false;
            }
            
            // Confirm submission
            const selectedDate = new Date(reportDate);
            const formattedDate = selectedDate.toLocaleString('en-US', {
                month: 'long',
                day: 'numeric',
                year: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
            
            const confirmMessage = `Are you sure you want to send this report?\n\nType: ${reportType}\nDate: ${formattedDate}`;
            
            if (!confirm(confirmMessage)) {
                e.preventDefault();
                return false;
            }
            
            // Show loading state
            const submitBtn = reportForm.querySelector('.btn-primary');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            }
        });
    }
    
    // Select element styling on change
    const selectElements = document.querySelectorAll('.form-select');
    selectElements.forEach(select => {
        select.addEventListener('change', function() {
            if (this.value) {
                this.style.color = '#1f2937';
            } else {
                this.style.color = '#6b7280';
            }
        });
    });
    
    // Auto-resize textarea
    const textarea = document.getElementById('reportcomment');
    if (textarea) {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    }
});

// Format date display
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
    });
}

// Check for success message in URL
window.addEventListener('load', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('success') === 'report_sent') {
        showSuccessMessage('Report sent successfully!');
        // Remove success parameter from URL
        const newUrl = window.location.pathname + '?' + 
                      Array.from(urlParams.entries())
                           .filter(([key]) => key !== 'success')
                           .map(([key, value]) => `${key}=${value}`)
                           .join('&');
        window.history.replaceState({}, '', newUrl);
    }
});

// Show success message
function showSuccessMessage(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-success';
    alertDiv.innerHTML = `
        <i class="fas fa-check-circle"></i>
        ${message}
    `;
    
    const mainContent = document.querySelector('.main-content');
    const firstChild = mainContent.firstElementChild;
    mainContent.insertBefore(alertDiv, firstChild);
    
    // Add success alert styles if not already in CSS
    if (!document.querySelector('style[data-alert-success]')) {
        const style = document.createElement('style');
        style.setAttribute('data-alert-success', '');
        style.textContent = `
            .alert-success {
                background: #f0fdf4;
                color: #166534;
                border: 1px solid #bbf7d0;
            }
            .alert-success i {
                color: #22c55e;
            }
        `;
        document.head.appendChild(style);
    }
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        alertDiv.style.transition = 'opacity 0.3s ease';
        alertDiv.style.opacity = '0';
        setTimeout(() => alertDiv.remove(), 300);
    }, 5000);
}
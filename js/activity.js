// activity.js - Activity Log Functions
let currentActivityId = null;

document.addEventListener('DOMContentLoaded', function() {
    initializeActivityLog();
});

function initializeActivityLog() {
    // Close menu when clicking outside
    document.addEventListener('click', function(e) {
        const menu = document.getElementById('activityMenu');
        if (!e.target.closest('.activity-action-btn') && !e.target.closest('.activity-menu')) {
            menu.style.display = 'none';
        }
    });

    // Checkbox functionality
    initializeCheckboxes();
    
    // Filter tabs functionality
    initializeFilterTabs();
    
    // Search functionality with debounce
    initializeSearch();
    
    // Date range picker
    initializeDateRangePicker();
}

function initializeCheckboxes() {
    const activityCheckboxes = document.querySelectorAll('.activity-checkbox');
    const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
    const selectedCountSpan = document.getElementById('selectedCount');

    function updateDeleteButton() {
        const checkedBoxes = document.querySelectorAll('.activity-checkbox:checked');
        const count = checkedBoxes.length;
        selectedCountSpan.textContent = count;
        deleteSelectedBtn.style.display = count > 0 ? 'flex' : 'none';
    }

    // Individual checkbox change
    activityCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateDeleteButton();
        });
    });

    // Delete Selected button click
    deleteSelectedBtn.addEventListener('click', function() {
        const checkedBoxes = document.querySelectorAll('.activity-checkbox:checked');
        const ids = Array.from(checkedBoxes).map(cb => parseInt(cb.value)).filter(id => id > 0);
        const count = ids.length;
        
        if (count === 0) {
            showNotification('Please select at least one activity to delete', 'warning');
            return;
        }
        
        if (confirm(`Are you sure you want to delete ${count} selected activity log(s)?`)) {
            deleteMultipleActivities(ids, checkedBoxes, updateDeleteButton);
        }
    });
}

function deleteMultipleActivities(ids, checkboxes, callback) {
    const formData = new FormData();
    formData.append('activity_ids', JSON.stringify(ids));
    
    fetch('php/delete_activity.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Remove items from DOM with animation
            checkboxes.forEach(checkbox => {
                const item = checkbox.closest('.activity-item');
                if (item) {
                    item.style.opacity = '0';
                    item.style.transform = 'translateX(-20px)';
                    setTimeout(() => item.remove(), 300);
                }
            });
            
            callback();
            showNotification(data.message || 'Activities deleted successfully', 'success');
            
            // Reload if no activities left
            setTimeout(() => {
                const remainingActivities = document.querySelectorAll('.activity-item');
                if (remainingActivities.length === 0) {
                    location.reload();
                }
            }, 500);
        } else {
            showNotification('Error: ' + (data.message || 'Unknown error occurred'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred while deleting activities.', 'error');
    });
}

function initializeFilterTabs() {
    const filterTabs = document.querySelectorAll('.filter-tab');
    
    filterTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const filter = this.getAttribute('data-filter');
            const currentParams = new URLSearchParams(window.location.search);
            currentParams.set('filter', filter);
            currentParams.set('page', '1');
            window.location.href = `activity.php?${currentParams.toString()}`;
        });
    });
}

function initializeSearch() {
    const searchInput = document.getElementById('activitySearch');
    let searchTimeout;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const searchTerm = this.value;
            const currentParams = new URLSearchParams(window.location.search);
            
            if (searchTerm) {
                currentParams.set('search', searchTerm);
            } else {
                currentParams.delete('search');
            }
            currentParams.set('page', '1');
            
            window.location.href = `activity.php?${currentParams.toString()}`;
        }, 500);
    });
}

function initializeDateRangePicker() {
    const dateRangeBtn = document.getElementById('dateRangeBtn');
    const dateRangeDropdown = document.getElementById('dateRangeDropdown');
    
    dateRangeBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        dateRangeDropdown.classList.toggle('show');
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!dateRangeDropdown.contains(e.target) && e.target !== dateRangeBtn) {
            dateRangeDropdown.classList.remove('show');
        }
    });
    
    // Preset buttons
    const presetBtns = document.querySelectorAll('.preset-btn');
    const dateRangeText = document.getElementById('dateRangeText');
    
    presetBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            presetBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const preset = this.getAttribute('data-preset');
            const today = new Date();
            let startDate, endDate;
            
            switch(preset) {
                case 'today':
                    startDate = formatDate(today);
                    endDate = formatDate(today);
                    dateRangeText.textContent = 'Today';
                    break;
                case '7days':
                    const weekAgo = new Date(today);
                    weekAgo.setDate(today.getDate() - 6);
                    startDate = formatDate(weekAgo);
                    endDate = formatDate(today);
                    dateRangeText.textContent = 'Last 7 days';
                    break;
                case '30days':
                    const monthAgo = new Date(today);
                    monthAgo.setDate(today.getDate() - 29);
                    startDate = formatDate(monthAgo);
                    endDate = formatDate(today);
                    dateRangeText.textContent = 'Last 30 days';
                    break;
            }
            
            dateRangeDropdown.classList.remove('show');
            
            const currentParams = new URLSearchParams(window.location.search);
            currentParams.set('start', startDate);
            currentParams.set('end', endDate);
            currentParams.set('page', '1');
            window.location.href = `activity.php?${currentParams.toString()}`;
        });
    });
}

function showActivityMenu(event, activityId) {
    event.stopPropagation();
    currentActivityId = activityId;
    
    const menu = document.getElementById('activityMenu');
    const button = event.currentTarget;
    const rect = button.getBoundingClientRect();
    
    // Position menu near the button
    menu.style.display = 'block';
    menu.style.top = (rect.bottom + 5) + 'px';
    menu.style.left = (rect.left - 150) + 'px';
}

function viewActivity() {
    const activityItem = document.querySelector(`[data-activity-id="${currentActivityId}"]`);
    if (activityItem) {
        const description = activityItem.querySelector('.activity-description').textContent;
        const user = activityItem.querySelector('.activity-name').textContent;
        const time = activityItem.querySelector('.activity-time').textContent;
        const type = activityItem.querySelector('.activity-type').textContent;
        
        const detailsHtml = `
            <div style="padding: 20px;">
                <h3 style="margin-top: 0;">Activity Details</h3>
                <p><strong>ID:</strong> ${currentActivityId}</p>
                <p><strong>User:</strong> ${user}</p>
                <p><strong>Type:</strong> ${type}</p>
                <p><strong>Time:</strong> ${time}</p>
                <p><strong>Description:</strong> ${description}</p>
            </div>
        `;
        
        showModal('Activity Details', detailsHtml);
    }
    document.getElementById('activityMenu').style.display = 'none';
}

function exportActivity() {
    window.location.href = `export_activity.php?id=${currentActivityId}`;
    document.getElementById('activityMenu').style.display = 'none';
}

function deleteActivity() {
    if (!currentActivityId) {
        showNotification('Error: Activity ID not found', 'error');
        return;
    }
    
    if (confirm(`Are you sure you want to delete this activity log?`)) {
        const formData = new FormData();
        formData.append('activity_id', parseInt(currentActivityId));
        
        fetch('php/delete_activity.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const activityItem = document.querySelector(`[data-activity-id="${currentActivityId}"]`);
                if (activityItem) {
                    activityItem.style.opacity = '0';
                    activityItem.style.transform = 'translateX(-20px)';
                    setTimeout(() => activityItem.remove(), 300);
                }
                showNotification(data.message || 'Activity deleted successfully', 'success');
                
                // Reload if no activities left
                setTimeout(() => {
                    const remainingActivities = document.querySelectorAll('.activity-item');
                    if (remainingActivities.length === 0) {
                        location.reload();
                    }
                }, 500);
            } else {
                showNotification('Error: ' + (data.message || 'Unknown error occurred'), 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred while deleting the activity.', 'error');
        });
    }
    document.getElementById('activityMenu').style.display = 'none';
}

function closeDateRangePicker() {
    document.getElementById('dateRangeDropdown').classList.remove('show');
}

function applyDateRange() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    
    if (!startDate || !endDate) {
        showNotification('Please select both start and end dates', 'warning');
        return;
    }
    
    if (new Date(startDate) > new Date(endDate)) {
        showNotification('Start date must be before end date', 'error');
        return;
    }
    
    const start = new Date(startDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    const end = new Date(endDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    document.getElementById('dateRangeText').textContent = `${start} - ${end}`;
    closeDateRangePicker();
    
    const currentParams = new URLSearchParams(window.location.search);
    currentParams.set('start', startDate);
    currentParams.set('end', endDate);
    currentParams.set('page', '1');
    window.location.href = `activity.php?${currentParams.toString()}`;
}

function exportActivities() {
    const currentParams = new URLSearchParams(window.location.search);
    window.location.href = `export_activities.php?${currentParams.toString()}`;
}

function goToPage(page) {
    const currentParams = new URLSearchParams(window.location.search);
    currentParams.set('page', page);
    window.location.href = `activity.php?${currentParams.toString()}`;
}

// Helper Functions
function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : type === 'warning' ? '#f59e0b' : '#6366f1'};
        color: white;
        padding: 16px 24px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        display: flex;
        align-items: center;
        gap: 12px;
        animation: slideIn 0.3s ease;
    `;
    
    const icon = type === 'success' ? '✓' : type === 'error' ? '✕' : type === 'warning' ? '⚠' : 'ℹ';
    notification.innerHTML = `<span style="font-size: 18px;">${icon}</span><span>${message}</span>`;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

function showModal(title, content) {
    // Create modal overlay
    const overlay = document.createElement('div');
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
    `;
    
    // Create modal
    const modal = document.createElement('div');
    modal.style.cssText = `
        background: white;
        border-radius: 12px;
        max-width: 600px;
        width: 90%;
        max-height: 80vh;
        overflow-y: auto;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    `;
    
    modal.innerHTML = `
        <div style="padding: 24px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0;">${title}</h3>
            <button onclick="this.closest('.modal-overlay').remove()" style="border: none; background: none; font-size: 24px; cursor: pointer; color: #9ca3af;">×</button>
        </div>
        ${content}
    `;
    
    overlay.className = 'modal-overlay';
    overlay.appendChild(modal);
    document.body.appendChild(overlay);
    
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
            overlay.remove();
        }
    });
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

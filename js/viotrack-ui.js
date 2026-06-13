/**
 * VioTrack UI Helpers
 * Provides toast notifications and confirm dialogs using SweetAlert2
 */

// ── Toast notification system ──
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3500,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer);
        toast.addEventListener('mouseleave', Swal.resumeTimer);
    }
});

/**
 * Show a success toast
 * @param {string} message
 */
function toastSuccess(message) {
    Toast.fire({ icon: 'success', title: message });
}

/**
 * Show an error toast
 * @param {string} message
 */
function toastError(message) {
    Toast.fire({ icon: 'error', title: message, timer: 5000 });
}

/**
 * Show an info toast
 * @param {string} message
 */
function toastInfo(message) {
    Toast.fire({ icon: 'info', title: message });
}

/**
 * Show a warning toast
 * @param {string} message
 */
function toastWarning(message) {
    Toast.fire({ icon: 'warning', title: message });
}

/**
 * Premium confirm dialog (replaces window.confirm)
 * @param {string} title
 * @param {string} text
 * @param {string} confirmText
 * @param {string} type - 'danger', 'warning', 'info'
 * @returns {Promise<boolean>}
 */
async function confirmAction(title, text, confirmText = 'Confirm', type = 'warning') {
    const colorMap = {
        danger:  { confirm: '#f43f5e', icon: 'warning' },
        warning: { confirm: '#f59e0b', icon: 'warning' },
        info:    { confirm: '#6366f1', icon: 'question' }
    };
    const cfg = colorMap[type] || colorMap.warning;
    
    const result = await Swal.fire({
        title,
        text,
        icon: cfg.icon,
        showCancelButton: true,
        confirmButtonColor: cfg.confirm,
        cancelButtonColor: '#64748b',
        confirmButtonText: confirmText,
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        backdrop: 'rgba(0,0,0,0.4)',
        customClass: {
            popup: 'viotrack-swal-popup',
            title: 'viotrack-swal-title'
        }
    });
    return result.isConfirmed;
}

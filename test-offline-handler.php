<?php
// Minimal test - just the offline handler
?>
<script>
// Inject offline handler into addRecordForm DIRECTLY
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('addRecordForm');
    if (form) {
        // Remove all existing listeners
        const newForm = form.cloneNode(true);
        form.parentNode.replaceChild(newForm, form);
        
        const updatedForm = document.getElementById('addRecordForm');
        updatedForm.addEventListener('submit', function(e) {
            console.log('Form submitted, navigator.onLine =', navigator.onLine);
            
            if (!navigator.onLine) {
                console.log('OFFLINE MODE - intercepting');
                e.preventDefault();
                
                // Just show success for now
                alert('✓ Offline mode working! Data would be saved.');
                return false;
            }
            
            console.log('Online mode - allowing normal submission');
            // Let it continue normally
        });
    }
});
</script>

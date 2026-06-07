/**
 * Offline Violation Manager
 * Handles saving violations when offline and syncing when online
 */

class OfflineViolationManager {
    constructor() {
        this.storageKey = 'pendingViolationRecords';
        this.isSyncing = false;
        this.syncInterval = 5000; // 5 seconds
        this.init();
    }
    
    /**
     * Initialize offline manager
     */
    init() {
        // Listen for online/offline events
        window.addEventListener('online', () => this.onOnline());
        window.addEventListener('offline', () => this.onOffline());
        
        // Auto-sync every 5 seconds if online
        setInterval(() => {
            if (navigator.onLine && !this.isSyncing) {
                this.checkAndSync();
            }
        }, this.syncInterval);
        
        // Check status on page load
        if (!navigator.onLine) {
            this.showOfflineBadge();
        }
        
        console.log('✓ Offline Manager initialized');
    }
    
    /**
     * Save violation offline (in browser's localStorage)
     * @param {Object} recordData - Violation data to save
     * @returns {Object} Saved record with unique ID
     */
    saveOffline(recordData) {
        const pending = this.getPending();
        
        const record = {
            id: Date.now(), // Unique ID using timestamp
            ...recordData,
            status: recordData.status || 'Pending',
            offline_recorded_at: new Date().toISOString(),
            is_synced: false,
            accuracy: recordData.accuracy || 0
        };
        
        pending.push(record);
        localStorage.setItem(this.storageKey, JSON.stringify(pending));
        
        console.log('✓ Saved offline:', record);
        return record;
    }
    
    /**
     * Get all pending violations from localStorage
     * @returns {Array} Array of pending violations
     */
    getPending() {
        return JSON.parse(localStorage.getItem(this.storageKey) || '[]');
    }
    
    /**
     * Auto-sync when online
     * Sends all pending violations to server
     */
    async checkAndSync() {
        const pending = this.getPending();
        
        if (pending.length === 0) return;
        
        this.isSyncing = true;
        this.showSyncingBadge(pending.length);
        
        try {
            const response = await fetch('sync-violations.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ records: pending })
            });
            
            const result = await response.json();
            
            console.log('Sync response:', result);
            
            if (result.success) {
                console.log('Synced record IDs:', result.synced);
                console.log('Pending before clear:', pending);
                
                // IMPORTANT: Clear ALL pending records after successful sync
                // Don't rely on filtering - just clear everything
                localStorage.removeItem(this.storageKey);
                
                // Show success notification
                this.showNotification(
                    `✓ Synced ${result.synced.length} violation(s) successfully!`,
                    'success'
                );
                
                console.log(`✓ Successfully synced and cleared localStorage. Total synced: ${result.synced.length}`);
                
                // Reload page to refresh the list
                this.removeBadge();
                setTimeout(() => location.reload(), 1500);
            } else {
                console.error('Sync failed - server returned success=false:', result);
                this.showNotification('❌ Sync failed. Will retry...', 'error');
            }
        } catch (error) {
            console.error('Sync fetch error:', error);
            console.error('Error type:', error.name);
            console.error('Error message:', error.message);
            this.showNotification('❌ Sync failed. Will retry...', 'error');
        } finally {
            this.isSyncing = false;
        }
    }
    
    /**
     * Called when internet is restored
     */
    onOnline() {
        console.log('📡 Internet restored');
        this.showNotification('📡 Internet restored - Syncing now...', 'success');
        setTimeout(() => this.checkAndSync(), 500);
    }
    
    /**
     * Called when internet is lost
     */
    onOffline() {
        console.log('📴 Internet lost');
        this.showOfflineBadge();
        this.showNotification('📴 Internet lost - Working in offline mode', 'warning');
    }
    
    /**
     * Check sync status from server
     */
    updateStatus() {
        fetch('check-sync-status.php')
            .then(r => r.json())
            .then(data => {
                if (data.pending_count > 0) {
                    this.showPendingBadge(data.pending_count);
                }
            })
            .catch(e => console.log('Status check failed:', e));
    }
    
    /**
     * Get alert stack count for positioning
     */
    getAlertStackOffset() {
        const badges = document.querySelectorAll('[data-alert-type]');
        return badges.length * 90; // 90px per alert + margin
    }
    
    /**
     * Show offline badge UI
     */
    showOfflineBadge() {
        this.removeBadge();
        
        const badge = document.createElement('div');
        badge.id = 'offlineBadge';
        badge.className = 'offline-alert-badge';
        badge.setAttribute('data-alert-type', 'offline');
        badge.innerHTML = `
            <div class="offline-badge-content">
                <span class="offline-icon">📡</span>
                <div class="offline-badge-text">
                    <div class="offline-badge-title">Offline Mode</div>
                    <div class="offline-badge-subtitle">Changes will sync when online</div>
                </div>
                <button class="offline-badge-close" onclick="this.closest('[data-alert-type]').remove()" aria-label="Close">×</button>
            </div>
        `;
        
        // Inject CSS if not already present
        if (!document.getElementById('offline-alert-styles')) {
            const style = document.createElement('style');
            style.id = 'offline-alert-styles';
            style.innerHTML = `
                .alert-container {
                    position: fixed;
                    top: 75px;
                    right: 20px;
                    z-index: 9999;
                    display: flex;
                    flex-direction: column;
                    gap: 12px;
                    pointer-events: none;
                }
                
                .alert-container > * {
                    pointer-events: auto;
                }
                
                .offline-alert-badge {
                    animation: slideInOffline 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
                }
                
                .offline-badge-content {
                    display: flex;
                    align-items: center;
                    gap: 14px;
                    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
                    color: white;
                    padding: 16px 20px;
                    border-radius: 12px;
                    box-shadow: 0 8px 28px rgba(239, 68, 68, 0.35);
                    border: 1px solid rgba(255, 255, 255, 0.15);
                    font-weight: 500;
                    backdrop-filter: blur(8px);
                    max-width: 320px;
                    min-width: 280px;
                }
                
                .offline-icon {
                    font-size: 24px;
                    flex-shrink: 0;
                    animation: pulse 2s ease-in-out infinite;
                }
                
                @keyframes pulse {
                    0%, 100% { opacity: 1; transform: scale(1); }
                    50% { opacity: 0.7; transform: scale(1.1); }
                }
                
                .offline-badge-text {
                    flex: 1;
                }
                
                .offline-badge-title {
                    font-size: 14px;
                    font-weight: 700;
                    margin-bottom: 2px;
                    letter-spacing: -0.3px;
                }
                
                .offline-badge-subtitle {
                    font-size: 12px;
                    opacity: 0.95;
                    font-weight: 500;
                }
                
                .offline-badge-close {
                    background: rgba(255, 255, 255, 0.2);
                    border: none;
                    color: white;
                    width: 28px;
                    height: 28px;
                    border-radius: 8px;
                    cursor: pointer;
                    font-size: 18px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    transition: all 0.2s ease;
                    flex-shrink: 0;
                    padding: 0;
                }
                
                .offline-badge-close:hover {
                    background: rgba(255, 255, 255, 0.35);
                    transform: scale(1.1);
                }
                
                .offline-badge-close:active {
                    transform: scale(0.95);
                }
                
                @keyframes slideInOffline {
                    from {
                        opacity: 0;
                        transform: translateX(360px) translateY(-20px);
                    }
                    to {
                        opacity: 1;
                        transform: translateX(0) translateY(0);
                    }
                }
                
                /* Mobile Styles */
                @media (max-width: 768px) {
                    .alert-container {
                        top: auto;
                        bottom: 20px;
                        right: 16px;
                        left: 16px;
                    }
                    
                    .offline-alert-badge {
                        width: 100%;
                    }
                    
                    .offline-badge-content {
                        min-width: auto;
                        max-width: none;
                        gap: 12px;
                        padding: 14px 16px;
                    }
                    
                    .offline-badge-title {
                        font-size: 13px;
                    }
                    
                    .offline-badge-subtitle {
                        font-size: 11px;
                    }
                }
            `;
            document.head.appendChild(style);
        }
        
        this.appendToAlertContainer(badge);
    }
    
    /**
     * Show syncing badge UI
     */
    showSyncingBadge(count) {
        this.removeBadge();
        
        const badge = document.createElement('div');
        badge.id = 'offlineBadge';
        badge.className = 'syncing-alert-badge';
        badge.setAttribute('data-alert-type', 'syncing');
        badge.innerHTML = `
            <div class="syncing-badge-content">
                <span class="syncing-spinner"></span>
                <div class="syncing-badge-text">
                    <div class="syncing-badge-title">Syncing Data</div>
                    <div class="syncing-badge-subtitle">${count} pending violation${count !== 1 ? 's' : ''}</div>
                </div>
            </div>
        `;
        
        // Inject CSS if not already present
        if (!document.getElementById('syncing-alert-styles')) {
            const style = document.createElement('style');
            style.id = 'syncing-alert-styles';
            style.innerHTML = `
                .syncing-alert-badge {
                    animation: slideInSync 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
                }
                
                .syncing-badge-content {
                    display: flex;
                    align-items: center;
                    gap: 14px;
                    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
                    color: white;
                    padding: 16px 20px;
                    border-radius: 12px;
                    box-shadow: 0 8px 28px rgba(245, 158, 11, 0.35);
                    border: 1px solid rgba(255, 255, 255, 0.15);
                    font-weight: 500;
                    backdrop-filter: blur(8px);
                    max-width: 320px;
                    min-width: 280px;
                }
                
                .syncing-spinner {
                    width: 18px;
                    height: 18px;
                    border: 2px solid rgba(255, 255, 255, 0.3);
                    border-top: 2px solid white;
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                    flex-shrink: 0;
                }
                
                @keyframes spin {
                    to { transform: rotate(360deg); }
                }
                
                .syncing-badge-text {
                    flex: 1;
                }
                
                .syncing-badge-title {
                    font-size: 14px;
                    font-weight: 700;
                    margin-bottom: 2px;
                    letter-spacing: -0.3px;
                }
                
                .syncing-badge-subtitle {
                    font-size: 12px;
                    opacity: 0.95;
                    font-weight: 500;
                }
                
                @keyframes slideInSync {
                    from {
                        opacity: 0;
                        transform: translateX(360px) translateY(-20px);
                    }
                    to {
                        opacity: 1;
                        transform: translateX(0) translateY(0);
                    }
                }
                
                /* Mobile Styles */
                @media (max-width: 768px) {
                    .syncing-alert-badge {
                        width: 100%;
                    }
                    
                    .syncing-badge-content {
                        min-width: auto;
                        max-width: none;
                        gap: 12px;
                        padding: 14px 16px;
                    }
                    
                    .syncing-badge-title {
                        font-size: 13px;
                    }
                    
                    .syncing-badge-subtitle {
                        font-size: 11px;
                    }
                }
            `;
            document.head.appendChild(style);
        }
        
        this.appendToAlertContainer(badge);
    }
    
    /**
     * Show notification toast
     */
    showNotification(message, type = 'info') {
        const notif = document.createElement('div');
        notif.className = `offline-notification offline-notification-${type}`;
        notif.setAttribute('data-alert-type', 'notification');
        
        const icons = {
            success: '✓',
            error: '✕',
            warning: '⚠',
            info: 'ℹ'
        };
        
        notif.innerHTML = `
            <span class="notification-icon">${icons[type]}</span>
            <span class="notification-message">${message}</span>
        `;
        
        // Inject CSS if not already present
        if (!document.getElementById('notification-styles')) {
            const style = document.createElement('style');
            style.id = 'notification-styles';
            style.innerHTML = `
                .offline-notification {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    padding: 14px 18px;
                    border-radius: 10px;
                    font-size: 13px;
                    font-weight: 600;
                    color: white;
                    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
                    border: 1px solid rgba(255, 255, 255, 0.2);
                    animation: slideInNotif 0.4s cubic-bezier(0.34, 1.56, 0.64, 1), slideOutNotif 0.4s ease-in 2.6s forwards;
                    max-width: 320px;
                    min-width: 280px;
                    backdrop-filter: blur(8px);
                }
                
                .offline-notification-success {
                    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
                    box-shadow: 0 8px 24px rgba(34, 197, 94, 0.25);
                }
                
                .offline-notification-error {
                    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
                    box-shadow: 0 8px 24px rgba(239, 68, 68, 0.25);
                }
                
                .offline-notification-warning {
                    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
                    box-shadow: 0 8px 24px rgba(245, 158, 11, 0.25);
                }
                
                .offline-notification-info {
                    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
                    box-shadow: 0 8px 24px rgba(59, 130, 246, 0.25);
                }
                
                .notification-icon {
                    font-size: 16px;
                    font-weight: 800;
                    flex-shrink: 0;
                    width: 24px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                
                .notification-message {
                    flex: 1;
                }
                
                @keyframes slideInNotif {
                    from {
                        opacity: 0;
                        transform: translateX(360px) translateY(-20px);
                    }
                    to {
                        opacity: 1;
                        transform: translateX(0) translateY(0);
                    }
                }
                
                @keyframes slideOutNotif {
                    from {
                        opacity: 1;
                        transform: translateX(0) translateY(0);
                    }
                    to {
                        opacity: 0;
                        transform: translateX(360px) translateY(-20px);
                    }
                }
                
                /* Mobile Styles */
                @media (max-width: 768px) {
                    .offline-notification {
                        min-width: auto;
                        max-width: none;
                    }
                }
            `;
            document.head.appendChild(style);
        }
        
        this.appendToAlertContainer(notif);
        
        setTimeout(() => notif.remove(), 3000);
    }
    
    /**
     * Append alert to container with stacking support
     */
    appendToAlertContainer(element) {
        let container = document.getElementById('alert-container');
        
        if (!container) {
            container = document.createElement('div');
            container.id = 'alert-container';
            container.className = 'alert-container';
            document.body.appendChild(container);
        }
        
        container.appendChild(element);
    }
    
    /**
     * Remove badge
     */
    removeBadge() {
        const badge = document.getElementById('offlineBadge');
        if (badge) badge.remove();
    }
    
    /**
     * Clear all pending violations (admin function)
     */
    clearPending() {
        localStorage.removeItem(this.storageKey);
        this.removeBadge();
        console.log('✓ Cleared all pending violations');
    }
    
    /**
     * Get count of pending violations
     */
    getPendingCount() {
        return this.getPending().length;
    }
}

// Add CSS animations
(function() {
    const style = document.createElement('style');
    style.textContent = `
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        @keyframes slideInRight {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    `;
    document.head.appendChild(style);
})();

// Initialize on page load
const offlineManager = new OfflineViolationManager();

/**
 * Geolocation Handler
 * Gets user's current GPS coordinates for violation tracking
 */

class GeolocationHandler {
    constructor() {
        this.timeout = 5000; // 5 seconds timeout
        this.maxAttempts = 3;
        this.attempt = 0;
    }
    
    /**
     * Get current user location
     * @returns {Promise<Object>} Location object with latitude, longitude, accuracy
     */
    getLocation() {
        return new Promise((resolve) => {
            // Check if geolocation is supported
            if (!navigator.geolocation) {
                console.warn('⚠️ Geolocation not supported on this device');
                resolve(this.getDefaultLocation());
                return;
            }
            
            // Request current position
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    // Success callback
                    const location = {
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy,
                        timestamp: position.timestamp
                    };
                    
                    console.log('✓ Location obtained:', location);
                    resolve(location);
                },
                (error) => {
                    // Error callback
                    console.warn('⚠️ Geolocation error:', error.message);
                    
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            console.warn('User denied geolocation permission');
                            break;
                        case error.POSITION_UNAVAILABLE:
                            console.warn('Location information is unavailable');
                            break;
                        case error.TIMEOUT:
                            console.warn('Location request timed out');
                            break;
                    }
                    
                    // Return default if permission denied or error
                    resolve(this.getDefaultLocation());
                },
                {
                    enableHighAccuracy: false, // Set to true for more accurate (slower)
                    timeout: this.timeout,
                    maximumAge: 30000 // Use cached location if available (30 seconds)
                }
            );
        });
    }
    
    /**
     * Get default location (when permission denied or unavailable)
     * @returns {Object} Default location object
     */
    getDefaultLocation() {
        return {
            latitude: 0,
            longitude: 0,
            accuracy: 0,
            timestamp: Date.now()
        };
    }
    
    /**
     * Check if geolocation is available
     * @returns {Boolean}
     */
    isAvailable() {
        return !!navigator.geolocation;
    }
    
    /**
     * Watch user location (continuous tracking)
     * @param {Function} callback - Called with location data
     * @returns {Number} Watch ID (use to clear watch)
     */
    watchLocation(callback) {
        if (!navigator.geolocation) {
            console.warn('Geolocation not supported');
            return null;
        }
        
        return navigator.geolocation.watchPosition(
            (position) => {
                const location = {
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy: position.coords.accuracy,
                    timestamp: position.timestamp
                };
                callback(location);
            },
            (error) => {
                console.warn('Watch location error:', error.message);
            },
            {
                enableHighAccuracy: false,
                timeout: this.timeout,
                maximumAge: 30000
            }
        );
    }
    
    /**
     * Clear location watch
     * @param {Number} watchId - ID returned from watchLocation()
     */
    clearWatch(watchId) {
        if (watchId && navigator.geolocation) {
            navigator.geolocation.clearWatch(watchId);
            console.log('✓ Watch cleared');
        }
    }
    
    /**
     * Calculate distance between two coordinates (in meters)
     * Uses Haversine formula
     * @param {Number} lat1 
     * @param {Number} lon1 
     * @param {Number} lat2 
     * @param {Number} lon2 
     * @returns {Number} Distance in meters
     */
    calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371000; // Earth's radius in meters
        const dLat = this.toRad(lat2 - lat1);
        const dLon = this.toRad(lon2 - lon1);
        const a = 
            Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(this.toRad(lat1)) * Math.cos(this.toRad(lat2)) *
            Math.sin(dLon / 2) * Math.sin(dLon / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c; // Distance in meters
    }
    
    /**
     * Convert degrees to radians
     * @param {Number} deg 
     * @returns {Number}
     */
    toRad(deg) {
        return deg * (Math.PI / 180);
    }
}

// Initialize on page load
const geoHandler = new GeolocationHandler();

console.log('✓ Geolocation Handler initialized');

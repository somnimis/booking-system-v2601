// Session Cache Manager for Admin Panel
class AdminSessionCache {
    constructor() {
        this.cachePrefix = 'admin_cache_';
        this.cacheDuration = 30 * 60 * 1000; // 30 minutes cache
    }

    set(key, data) {
        const cacheData = {
            data: data,
            timestamp: Date.now()
        };
        sessionStorage.setItem(this.cachePrefix + key, JSON.stringify(cacheData));
    }

    get(key) {
        const cached = sessionStorage.getItem(this.cachePrefix + key);
        if (!cached) return null;
        
        const cacheData = JSON.parse(cached);
        if (Date.now() - cacheData.timestamp > this.cacheDuration) {
            sessionStorage.removeItem(this.cachePrefix + key);
            return null;
        }
        
        return cacheData.data;
    }

    clear(key) {
        if (key) {
            sessionStorage.removeItem(this.cachePrefix + key);
        } else {
            // Clear all admin cache
            Object.keys(sessionStorage).forEach(key => {
                if (key.startsWith(this.cachePrefix)) {
                    sessionStorage.removeItem(key);
                }
            });
        }
    }

    has(key) {
        return this.get(key) !== null;
    }
}

const adminCache = new AdminSessionCache();

// Function to preload and cache admin data
async function preloadAdminData(token) {
    if (!token) return null;

    // Check if we have cached data
    const cachedProfile = adminCache.get('profile');
    const cachedRoles = adminCache.get('roles');
    const cachedNotifications = adminCache.get('notifications');

    // Return cached data if available and not expired
    if (cachedProfile && cachedRoles) {
        return {
            profile: cachedProfile,
            roles: cachedRoles,
            notifications: cachedNotifications
        };
    }

    // Fetch fresh data
    try {
        const [profileRes, rolesRes, notifRes] = await Promise.all([
            fetch("/api/admin/profile", {
                headers: { Authorization: `Bearer ${token}`, Accept: "application/json" },
                credentials: "include"
            }),
            fetch("/api/admin-role", {
                headers: { Authorization: `Bearer ${token}`, Accept: "application/json" },
                credentials: "include"
            }),
            fetch('/api/admin/notifications', {
                headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' }
            })
        ]);

        if (!profileRes.ok) throw new Error('Profile fetch failed');

        const profileData = await profileRes.json();
        const rolesData = rolesRes.ok ? await rolesRes.json() : [];
        const notificationsData = notifRes.ok ? await notifRes.json() : { notifications: [], unread_count: 0 };

        const roles = rolesData.data || rolesData;
        
        // Cache the data
        adminCache.set('profile', profileData);
        adminCache.set('roles', roles);
        adminCache.set('notifications', notificationsData);
        
        return {
            profile: profileData,
            roles: roles,
            notifications: notificationsData
        };
    } catch (error) {
        console.error('Error preloading admin data:', error);
        return null;
    }
}
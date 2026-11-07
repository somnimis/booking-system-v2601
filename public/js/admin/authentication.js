// authentication.js - Updated version

// Cache for head admin status
let isHeadAdminCache = null;

// Function to ensure token is in localStorage when loading admin pages
function ensureAdminToken() {
    // Check if we're on an admin page
    if (window.location.pathname.startsWith('/admin/')) {
        // Try to get token from URL query parameter (after login redirect)
        const urlParams = new URLSearchParams(window.location.search);
        const urlToken = urlParams.get('token');
        
        if (urlToken) {
            // Save token to localStorage
            localStorage.setItem('adminToken', urlToken);
            // Remove token from URL to clean it up
            const newUrl = window.location.pathname + window.location.hash;
            window.history.replaceState({}, document.title, newUrl);
        }
        
        // Also check cookie for token if needed
        const token = localStorage.getItem('adminToken');
        if (!token) {
            // No token found, redirect to login
            window.location.href = '/admin/login';
            return false;
        }
        
        // For AJAX requests, set the Authorization header globally
        if (token) {
            // Set up axios or fetch interceptor
            if (window.axios) {
                window.axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
            }
        }
        
        return !!token;
    }
    return true;
}

// Call this immediately when the script loads
ensureAdminToken();

// Check if current admin is Head Admin (uses injected data)
function checkIfHeadAdmin() {
    if (isHeadAdminCache !== null) {
        return isHeadAdminCache;
    }
    
    // Use the injected window.Admin data
    if (window.Admin) {
        isHeadAdminCache = window.Admin.role_title === 'Head Admin';
    } else {
        // Fallback to localStorage if window.Admin not available
        const roleTitle = localStorage.getItem('adminRoleTitle');
        isHeadAdminCache = roleTitle === 'Head Admin';
    }
    
    localStorage.setItem('adminRole', isHeadAdminCache ? 'Head Admin' : 'Other');
    return isHeadAdminCache;
}

// Get admin profile from injected data
function getAdminProfile() {
    return window.Admin || null;
}

// Get admin ID
function getAdminId() {
    return window.Admin?.admin_id || localStorage.getItem('adminId');
}

// Get admin role ID
function getAdminRoleId() {
    return window.Admin?.role_id || localStorage.getItem('adminRoleId');
}

// Clear cache (for logout)
function clearAdminRoleCache() {
    isHeadAdminCache = null;
    localStorage.removeItem('adminRole');
    localStorage.removeItem('adminId');
    localStorage.removeItem('adminRoleTitle');
    localStorage.removeItem('adminRoleId');
    localStorage.removeItem('isApprovalRole');
    localStorage.removeItem('adminToken');
}

// Logout functionality
document.getElementById("logoutLink")?.addEventListener("click", async (e) => {
    e.preventDefault();
    
    const token = localStorage.getItem("adminToken");
    
    // Clear all caches
    clearAdminRoleCache();
    sessionStorage.clear();
    
    if (token) {
        try {
            await fetch("/api/admin/logout", {
                method: "POST",
                headers: {
                    Authorization: `Bearer ${token}`,
                    Accept: "application/json",
                },
                credentials: "include",
            });
        } catch (error) {
            console.error("Logout error:", error);
        }
    }
    
    window.location.href = "/admin/login";
});

// Export for use in other scripts
window.checkIfHeadAdmin = checkIfHeadAdmin;
window.getAdminProfile = getAdminProfile;
window.getAdminId = getAdminId;
window.getAdminRoleId = getAdminRoleId;
window.clearAdminRoleCache = clearAdminRoleCache;
window.ensureAdminToken = ensureAdminToken;
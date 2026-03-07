// Declare the cache variable at the top level
let isHeadAdminCache = null;

// Optimized version with caching
async function checkIfHeadAdmin() {
    // Return cached result if available
    if (isHeadAdminCache !== null) {
        return isHeadAdminCache;
    }

    try {
        const adminToken = localStorage.getItem('adminToken');
        if (!adminToken) {
            console.log('No admin token found');
            isHeadAdminCache = false;
            return false;
        }

        const response = await fetch('/api/admin/profile', {
            headers: {
                'Authorization': `Bearer ${adminToken}`,
                'Accept': 'application/json'
            },
            credentials: 'include'
        });

        if (!response.ok) {
            console.error('Failed to fetch admin profile for role check');
            isHeadAdminCache = false;
            return false;
        }

        const adminData = await response.json();
        
        // Check if user is Head Admin based on role title
        const isHeadAdmin = adminData.role?.role_title === 'Head Admin';
        
        // Cache the result
        isHeadAdminCache = isHeadAdmin;
        
        // Store in localStorage for persistence across page refreshes
        localStorage.setItem('adminRole', isHeadAdmin ? 'Head Admin' : 'Other');
        
        console.log('Head Admin status:', isHeadAdmin);
        return isHeadAdmin;

    } catch (error) {
        console.error('Error checking admin role:', error);
        isHeadAdminCache = false;
        return false;
    }
}

// Function to clear cache (useful when logging out or when role changes)
function clearAdminRoleCache() {
    isHeadAdminCache = null;
    localStorage.removeItem('adminRole');
}

// Single DOMContentLoaded event listener
document.addEventListener("DOMContentLoaded", async () => {
    console.log("Checking token...");
    const token = localStorage.getItem("adminToken");
    console.log("Token exists:", !!token);

    if (!token) {
        window.location.href = "/admin/login";
        return;
    }

    try {
        // Check authentication and get profile
        const [authResponse, rolesResponse] = await Promise.all([
            fetch("/api/admin/profile", {
                headers: {
                    Authorization: `Bearer ${token}`,
                    Accept: "application/json",
                },
                credentials: "include",
            }),
            fetch("/api/admin-role", {
                headers: {
                    Authorization: `Bearer ${token}`,
                    Accept: "application/json",
                },
                credentials: "include",
            })
        ]);

        if (!authResponse.ok) {
            const text = await authResponse.text();
            console.log("HTTP Status:", authResponse.status);
            console.log("Response:", text);
            throw new Error("Session expired or invalid");
        }

        const data = await authResponse.json();
        
        // Handle the new response structure from your updated adminRoles method
        let roles = [];
        if (rolesResponse.ok) {
            const rolesData = await rolesResponse.json();
            // Check if the response has the new structure with success/data wrapper
            roles = rolesData.data || rolesData;
        }
        
        console.log("Authenticated as", data.email);
        console.log("Available roles:", roles);
        
        // Store admin ID for client-side checks
        if (data.admin_id) {
            localStorage.setItem("adminId", data.admin_id);
        }

        // Get current user's role
        const userRoleId = data.role?.role_id;
        
        // Find the role title from the roles list
        const userRole = roles.find(r => r.role_id === userRoleId);
        const roleTitle = userRole ? userRole.role_title : data.role?.role_title || '';
        
        console.log("User role title:", roleTitle);

        // Store role info for other uses
        localStorage.setItem('adminRoleTitle', roleTitle);
        localStorage.setItem('adminRoleId', userRoleId);
        
        // Check if approval role for UI purposes
        const isApprovalRole = roleTitle === 'Vice President of Administration' || 
                               roleTitle === 'Approving Officer';
        localStorage.setItem('isApprovalRole', isApprovalRole);

        console.log("Head Admin role check completed:", await checkIfHeadAdmin());

    } catch (error) {
        console.error("Authentication error:", error);
        localStorage.removeItem("adminToken");
        clearAdminRoleCache();
        window.location.href = "/admin/login";
    }
});

// Logout functionality
document.getElementById("logoutLink")?.addEventListener("click", async (e) => {
    e.preventDefault();
    clearAdminRoleCache();

    const token = localStorage.getItem("adminToken");
    if (!token) {
        window.location.href = "/admin/login";
        return;
    }

    try {
        await fetch("/api/admin/logout", {
            method: "POST",
            headers: {
                Authorization: `Bearer ${token}`,
                Accept: "application/json",
            },
            credentials: "include", // Critical for Sanctum
        });
    } catch (error) {
        console.error("Logout error:", error);
    } finally {
        localStorage.removeItem("adminToken");
        window.location.href = "/admin/login";
    }
});
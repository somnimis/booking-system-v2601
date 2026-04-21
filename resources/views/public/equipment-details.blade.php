{{-- resources/views/public/equipment-details.blade.php --}}
@extends('layouts.app')

@section('title', 'Equipment Details - CPU Booking')

@section('content')
<style>
.equipment-header {
    background: linear-gradient(135deg, #003366 0%, #002244 100%);
    border-radius: 0;
    padding: 5rem 2rem 2rem 2rem;
    margin-bottom: 2rem;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    position: relative;
    width: 100vw;
    margin-left: calc(-50vw + 50%);
    min-height: 350px;
}
    /* Breadcrumb Styles */
    .hero-breadcrumb {
        position: absolute;
        top: 1.5rem;
        left: 2rem;
        z-index: 10;
    }

    .hero-breadcrumb .breadcrumb-item + .breadcrumb-item::before {
        color: rgba(255, 255, 255, 0.5);
        content: "/";
    }

    .hero-breadcrumb a {
        color: rgba(255, 255, 255, 0.7);
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 500;
        transition: color 0.2s;
    }

    .hero-breadcrumb a:hover {
        color: var(--cpu-secondary);
    }

    .hero-breadcrumb .breadcrumb-item.active {
        color: #fff;
        font-weight: 600;
    }

    .header-content { flex: 1; }
    
    .equipment-image-container {
        position: relative;
        margin-bottom: 1rem;
    }

    .equipment-image {
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        background: #f8f9fa;
    }
    
    .equipment-image img {
        width: 100%;
        height: 450px;
        object-fit: contain;
        background: #f8f9fa;
    }

    /* Shopee-style Thumbnail Gallery */
    .thumbnail-container {
        display: flex;
        gap: 10px;
        margin-top: 15px;
        overflow-x: auto;
        padding-bottom: 5px;
    }

    .thumbnail-item {
        width: 80px;
        height: 80px;
        border-radius: 8px;
        cursor: pointer;
        border: 2px solid transparent;
        overflow: hidden;
        transition: all 0.2s ease;
        flex-shrink: 0;
    }

    .thumbnail-item:hover { border-color: var(--cpu-secondary); }
    .thumbnail-item.active { border-color: var(--cpu-secondary); box-shadow: 0 0 8px rgba(255, 193, 7, 0.5); }

    .thumbnail-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .info-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .info-card h4 {
        color: var(--cpu-primary);
        margin-bottom: 1rem;
        font-weight: 600;
        border-left: 4px solid var(--cpu-secondary);
        padding-left: 1rem;
    }
    
    .info-row {
        display: flex;
        padding: 0.75rem 0;
        border-bottom: 1px solid #eee;
    }
    
    .info-label { width: 140px; font-weight: 600; color: #555; }
    .info-value { flex: 1; color: #333; }
    
    /* Fixed centering for badges */
    .status-badge, .category-badge-custom {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.4rem 1rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        line-height: 1;
    }

    .category-badge-custom {
        background-color: rgba(255,255,255,0.2);
        color: white;
        border: 1px solid rgba(255,255,255,0.3);
    }
    
    .btn-book {
        background-color: var(--cpu-secondary);
        color: #000 !important;
        border: none;
        padding: 1rem 2.5rem;
        font-weight: 700;
        border-radius: 50px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
    
    .btn-book:hover:not(:disabled) {
        background-color: var(--cpu-secondary-hover);
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.3);
    }

    /* Remove button styles matching facility-details */
    .btn-remove {
        background-color: #dc3545;
        color: white;
        border: none;
        padding: 1rem 2.5rem;
        font-weight: 700;
        border-radius: 50px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }

    .btn-remove:hover:not(:disabled) {
        background-color: #bb2d3b;
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.3);
    }

    .btn-book:disabled, .btn-remove:disabled {
        opacity: 0.7 !important;
        cursor: not-allowed !important;
        transform: none !important;
    }

    /* Keep button background color when disabled/loading */
    .btn-book:disabled {
        background-color: var(--cpu-secondary) !important;
        color: #000 !important;
    }

    .btn-remove:disabled {
        background-color: #dc3545 !important;
        color: white !important;
    }

    @keyframes shimmer {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
    /* Mobile Responsive Styles */
@media (max-width: 768px) {
    .equipment-header {
        padding: 3rem 1rem 1.5rem 1rem;
        min-height: 280px;
        flex-direction: column;
        align-items: flex-start;
        gap: 1.5rem;
    }

    .hero-breadcrumb {
        top: 1rem;
        left: 1rem;
    }

    .hero-breadcrumb a,
    .hero-breadcrumb .breadcrumb-item.active {
        font-size: 0.75rem;
    }

    .header-content {
        width: 100%;
    }

    .header-content h1 {
        font-size: 1.75rem;
        margin-bottom: 0.75rem !important;
    }

    .header-action {
        margin-left: 0 !important;
        width: 100%;
    }

    .header-action .btn-book,
    .header-action .btn-remove {
        width: 100%;
        padding: 0.75rem 1rem;
        font-size: 1rem;
    }

    .equipment-image img {
        height: 250px;
    }

    .thumbnail-item {
        width: 60px;
        height: 60px;
    }

    .info-card {
        padding: 1rem;
    }

    .info-card h4 {
        font-size: 1.25rem;
    }

    .info-row {
        flex-direction: column;
        padding: 0.5rem 0;
    }

    .info-label {
        width: 100%;
        margin-bottom: 0.25rem;
    }

    .info-value {
        width: 100%;
    }

    .status-badge,
    .category-badge-custom {
        font-size: 0.75rem;
        padding: 0.3rem 0.75rem;
    }
}

@media (max-width: 480px) {
    .equipment-header {
        padding: 2.5rem 0.75rem 1rem 0.75rem;
        min-height: 240px;
    }

    .hero-breadcrumb {
        top: 0.75rem;
        left: 0.75rem;
    }

    .header-content h1 {
        font-size: 1.5rem;
    }

    .equipment-image img {
        height: 200px;
    }

    .thumbnail-item {
        width: 50px;
        height: 50px;
    }

    .info-card {
        padding: 0.75rem;
    }

    .btn-book,
    .btn-remove {
        padding: 0.6rem 1rem;
        font-size: 0.9rem;
    }
}
</style>

<div class="container">
    <div id="loadingSkeleton" style="display: block;">
        <div class="skeleton-detail">
            <div class="skeleton-line-title"></div>
            <div class="row">
                <div class="col-md-6"><div class="skeleton-image"></div></div>
                <div class="col-md-6"><div class="skeleton-line"></div><div class="skeleton-line"></div></div>
            </div>
        </div>
    </div>

    <div id="equipmentContent" style="display: none;">
        <div class="equipment-header">
            <!-- Breadcrumb Navigation -->
            <div class="hero-breadcrumb">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="/home">Home</a></li>
                        <li class="breadcrumb-item"><a href="/booking-catalog">Booking Catalog</a></li>
                        <li class="breadcrumb-item active" aria-current="page" id="breadcrumbCurrent">
                            Equipment Details
                        </li>
                    </ol>
                </nav>
            </div>
            
            <div class="header-content">
                <h1 class="display-5 fw-bold mb-3" id="equipmentName"></h1>
                <div class="d-flex flex-wrap gap-2">
                    <span class="status-badge" id="statusBadge"></span>
                    <span class="category-badge-custom" id="categoryBadge"></span>
                </div>
            </div>
            <div class="header-action ms-4">
                <button class="btn btn-book btn-lg" id="bookNowBtn">
                    <i class="bi bi-calendar-check me-2"></i>Add Equipment
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="equipment-image-container">
                    <div class="equipment-image">
                        <img id="mainImage" src="" alt="Main Image">
                    </div>
                    <div class="thumbnail-container" id="imageGallery">
                        </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="info-card">
                    <h4>Equipment Information</h4>
                    <div class="info-row">
                        <div class="info-label">Brand:</div>
                        <div class="info-value" id="brand"></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Location:</div>
                        <div class="info-value" id="storageLocation"></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Rate:</div>
                        <div class="info-value"><span id="baseFee"></span> / <span id="rateType"></span></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Department:</div>
                        <div class="info-value" id="department"></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Availability:</div>
                        <div class="info-value" id="availability"></div>
                    </div>
                </div>
                
                <div class="info-card">
                    <h4>Description</h4>
                    <p id="description" class="mb-0 text-muted" style="white-space: pre-line;"></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const equipmentId = window.location.pathname.split('/').pop();
    const defaultImg = 'https://res.cloudinary.com/dn98ntlkd/image/upload/v1759850278/t4fyv56wog6pglhwvwtn.png';
    let isInForm = false;

    document.addEventListener('DOMContentLoaded', () => {
        loadEquipmentDetails();
        checkSessionStatus();
    });

    function loadEquipmentDetails() {
        fetch(`/api/equipment-details/${equipmentId}`, { headers: { 'Accept': 'application/json' } })
        .then(res => res.json())
        .then(res => {
            if (res.success && res.data) {
                displayEquipmentDetails(res.data);
                document.getElementById('loadingSkeleton').style.display = 'none';
                document.getElementById('equipmentContent').style.display = 'block';
            } else {
                showError('Equipment not found');
            }
        }).catch(() => showError('Failed to load equipment details'));
    }

    function checkSessionStatus() {
        fetch('/requisition/get-items')
            .then(res => res.json())
            .then(res => {
                if (res.success && res.data.selected_items) {
                    isInForm = res.data.selected_items.some(item => item.type === 'equipment' && item.equipment_id == equipmentId);
                    updateButtonUI();
                }
            })
            .catch(err => console.error('Session check failed:', err));
    }

    function displayEquipmentDetails(equipment) {
        // Update breadcrumb with equipment name
        const breadcrumbCurrent = document.getElementById('breadcrumbCurrent');
        if (breadcrumbCurrent) {
            breadcrumbCurrent.textContent = equipment.equipment_name || 'Equipment Details';
        }
        
        document.getElementById('equipmentName').textContent = equipment.equipment_name;
        
        const sBadge = document.getElementById('statusBadge');
        sBadge.textContent = equipment.status?.status_name || 'Unknown';
        sBadge.style.backgroundColor = equipment.status?.color_code || '#6c757d';
        sBadge.style.color = '#fff';
        
        document.getElementById('categoryBadge').textContent = equipment.category?.category_name || 'Uncategorized';
        
        const mainImage = document.getElementById('mainImage');
        const gallery = document.getElementById('imageGallery');
        const images = equipment.images && equipment.images.length > 0 ? equipment.images : [{image_url: defaultImg}];

        const primary = images.find(img => img.image_type === 'Primary') || images[0];
        mainImage.src = primary.image_url;

        gallery.innerHTML = images.map((img, idx) => `
            <div class="thumbnail-item ${img.image_url === primary.image_url ? 'active' : ''}" onclick="updateMainImage(this, '${img.image_url}')">
                <img src="${img.image_url}" alt="Thumbnail ${idx + 1}" onerror="this.src='${defaultImg}'">
            </div>
        `).join('');

        document.getElementById('brand').textContent = equipment.brand || 'Generic';
        document.getElementById('storageLocation').textContent = equipment.storage_location || 'Main Office';
        document.getElementById('baseFee').textContent = equipment.base_fee == 0 ? 'Free' : `₱${parseFloat(equipment.base_fee).toLocaleString()}`;
        document.getElementById('rateType').textContent = equipment.rate_type || 'Use';
        document.getElementById('department').textContent = equipment.department?.department_name || 'N/A';
        document.getElementById('description').textContent = equipment.description || 'No description provided.';
        
        const availableQty = equipment.available_quantity || 0;
        const availEl = document.getElementById('availability');
        
        if (availableQty > 0) {
            availEl.innerHTML = `<span class="availability-available"><i class="bi bi-check-circle-fill me-1"></i>${availableQty} units available</span>`;
            const btn = document.getElementById('bookNowBtn');
            btn.disabled = false;
            btn.addEventListener('click', handleButtonClick);
        } else {
            availEl.innerHTML = `<span class="availability-unavailable"><i class="bi bi-x-circle-fill me-1"></i>Out of Stock</span>`;
            const btn = document.getElementById('bookNowBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="bi bi-dash-circle me-2"></i>UNAVAILABLE';
        }
    }

    function handleButtonClick() {
        const btn = document.getElementById('bookNowBtn');
        
        if (isInForm) {
            removeFromRequisition(equipmentId, btn);
        } else {
            addToRequisition(equipmentId, btn);
        }
    }

    function addToRequisition(id, btn) {
        // Store original button content and disable
        btn.disabled = true;
        const originalContent = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Adding equipment...';
        
        // Ensure button keeps its background color
        if (btn.classList.contains('btn-book')) {
            btn.style.backgroundColor = 'var(--cpu-secondary)';
        }

        fetch('/requisition/add-item', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ 
                equipment_id: id, 
                type: 'equipment',
                quantity: 1 
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                isInForm = true;
                updateButtonUI();
                window.dispatchEvent(new CustomEvent('cart-updated', { detail: data.data }));
            } else {
                alert(data.message || 'Failed to add equipment.');
                btn.disabled = false;
                btn.innerHTML = originalContent;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to add equipment. Please try again.');
            btn.disabled = false;
            btn.innerHTML = originalContent;
        });
    }

    function removeFromRequisition(id, btn) {
        // Store original button content and disable
        btn.disabled = true;
        const originalContent = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Removing equipment...';
        
        // Ensure button keeps its background color
        if (btn.classList.contains('btn-remove')) {
            btn.style.backgroundColor = '#dc3545';
        }

        fetch('/requisition/remove-item', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ 
                equipment_id: id, 
                type: 'equipment' 
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                isInForm = false;
                updateButtonUI();
                window.dispatchEvent(new CustomEvent('cart-updated', { detail: data.data }));
            } else {
                alert(data.message || 'Failed to remove equipment.');
                btn.disabled = false;
                btn.innerHTML = originalContent;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to remove equipment. Please try again.');
            btn.disabled = false;
            btn.innerHTML = originalContent;
        });
    }

    function updateButtonUI() {
        const btn = document.getElementById('bookNowBtn');
        if (isInForm) {
            btn.classList.remove('btn-book');
            btn.classList.add('btn-remove');
            btn.style.backgroundColor = '';
            btn.innerHTML = '<i class="bi bi-x-circle me-2"></i>Remove Equipment';
        } else {
            btn.classList.remove('btn-remove');
            btn.classList.add('btn-book');
            btn.style.backgroundColor = '';
            btn.innerHTML = '<i class="bi bi-calendar-check me-2"></i>Add Equipment';
        }
        btn.disabled = false;
    }

    function updateMainImage(el, url) {
        document.getElementById('mainImage').src = url;
        document.querySelectorAll('.thumbnail-item').forEach(item => item.classList.remove('active'));
        el.classList.add('active');
    }
    
    function showError(message) {
        document.getElementById('loadingSkeleton').innerHTML = `<div class="alert alert-danger m-5 text-center"><h3>${message}</h3></div>`;
    }
</script>
@endsection
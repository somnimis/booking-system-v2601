{{-- resources/views/public/facility-details.blade.php --}}
@extends('layouts.app')

@section('title', 'Facility Details - CPU Booking')

@section('content')

    <style>
        /* Breadcrumb Styles */
.hero-breadcrumb {
    position: absolute;
    top: 2rem;
    left: 0;
    width: 100%;
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
    color: var(--cpu-accent);
}

.hero-breadcrumb .breadcrumb-item.active {
    color: #fff;
    font-weight: 600;
}
        /* Force button colors when disabled */
        .btn-reserve:disabled,
        .btn-remove:disabled {
            opacity: 0.7 !important;
            cursor: not-allowed !important;
        }

        .btn-reserve:disabled {
            background-color: var(--cpu-accent) !important;
            color: white !important;
        }

        .btn-remove:disabled {
            background-color: #dc3545 !important;
            color: white !important;
        }

        /* Ensure buttons keep their background even when disabled/loading */
        .btn-reserve:disabled,
        .btn-remove:disabled {
            opacity: 0.7;
            color: white !important;
            cursor: not-allowed;
        }

        :root {
            --cpu-primary: #003366;
            --tile-bg: #f8f9fa;
            --skeleton-base: #e9ecef;
            --skeleton-shimmer: #f8f9fa;
            --cpu-accent: #e8b342;
            /* Custom Button Color */
        }

        /* SKELETON ANIMATION */
        @keyframes shimmer {
            0% {
                background-position: -468px 0;
            }

            100% {
                background-position: 468px 0;
            }
        }

        .hero-banner,
        .skeleton-hero {
            height: 40vh;
            min-height: 300px;
        }

        /* Custom Button Style */
        .btn-reserve {
            background-color: var(--cpu-accent);
            color: white;
            border: none;
            transition: transform 0.2s, background-color 0.2s;
        }

        .btn-reserve:hover {
            background-color: #d1a13b;
            color: white;
            transform: translateY(-2px);
        }

        .skeleton-wrapper [class^="skeleton-"] {
            background: linear-gradient(to right, var(--skeleton-base) 8%, var(--skeleton-shimmer) 18%, var(--skeleton-base) 33%);
            background-size: 800px 104px;
            animation: shimmer 1.5s linear infinite forwards;
        }

        .skeleton-hero {
            height: 60vh;
            min-height: 450px;
            width: 100%;
        }

        .skeleton-tile {
            height: 120px;
            width: 100%;
        }

        .skeleton-block {
            width: 100%;
        }

        /* EXISTING STYLES */
        .hero-banner {
            height: 60vh;
            min-height: 450px;
            background-color: #1a1a1a;
        }

        .hero-image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            filter: brightness(0.6);
        }

        .info-tile {
            background: var(--tile-bg);
            border: 1px solid #eee;
        }

        .tile-label {
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 1px;
            color: #999;
        }

        .tile-value {
            font-size: 2.5rem;
            font-weight: 900;
            line-height: 1;
            color: #222;
        }

        .tile-value-sm {
            font-size: 1.5rem;
            font-weight: 800;
        }

        .overview-card {
            background: #f1f3f5;
        }

        .overview-text {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #444;
        }

        .identity-card {
            border: 1px solid #eee;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }

        .ls-widest {
            letter-spacing: 0.15em;
        }

        .btn-remove {
            background-color: #dc3545;
            /* Bootstrap danger red */
            color: white;
            border: none;
            transition: all 0.2s;
        }

        .btn-remove:hover {
            background-color: #bb2d3b;
            color: white;
        }
        /* Mobile Responsive Styles */
@media (max-width: 768px) {
    .hero-banner {
        height: 50vh;
        min-height: 350px;
    }

    .hero-breadcrumb {
        top: 1rem;
        left: 0;
        padding: 0 1rem;
    }

    .hero-breadcrumb a,
    .hero-breadcrumb .breadcrumb-item.active {
        font-size: 0.75rem;
    }

    .hero-banner .container {
        padding-left: 1rem;
        padding-right: 1rem;
    }

    .hero-banner h1 {
        font-size: 1.75rem !important;
    }

    .badge.fw-bold.px-3.py-2 {
        font-size: 0.7rem;
        padding: 0.25rem 0.75rem !important;
    }

    #facilityCodeLabel {
        font-size: 0.7rem;
    }

    .btn-reserve,
    .btn-remove {
        width: 100%;
        padding: 0.75rem 1rem !important;
        font-size: 1rem;
    }

    .info-tile {
        padding: 1rem !important;
    }

    .tile-value {
        font-size: 1.5rem;
    }

    .tile-value-sm {
        font-size: 1.1rem;
    }

    .tile-label {
        font-size: 0.6rem;
    }

    .overview-card {
        padding: 1.5rem !important;
    }

    .overview-text {
        font-size: 0.95rem;
        line-height: 1.6;
    }

    .identity-card {
        padding: 1.5rem !important;
    }

    .identity-card h6 {
        font-size: 0.8rem;
        margin-bottom: 1rem !important;
    }

    .location-box {
        padding: 1rem !important;
    }

    .container.pb-5 {
        padding-bottom: 2rem !important;
    }

    .row.g-3.mb-5 {
        margin-bottom: 1.5rem !important;
    }
}

@media (max-width: 480px) {
    .hero-banner {
        height: 45vh;
        min-height: 300px;
    }

    .hero-banner h1 {
        font-size: 1.5rem !important;
    }

    .hero-banner .d-flex.align-items-center.gap-2.mb-3 {
        flex-wrap: wrap;
    }

    .tile-value {
        font-size: 1.25rem;
    }

    .tile-value-sm {
        font-size: 1rem;
    }

    .overview-card h3 {
        font-size: 1.25rem;
        margin-bottom: 1rem !important;
    }

    .overview-text {
        font-size: 0.85rem;
    }

    .identity-card .mb-4 {
        margin-bottom: 1rem !important;
    }

    .identity-card label {
        font-size: 0.7rem;
    }

    .identity-card .fw-bold {
        font-size: 0.9rem;
    }
}
    </style>

    <!-- Loading State - Outside main container -->
    <div id="loadingState" class="skeleton-wrapper">
        <div class="skeleton-hero mb-5"></div>
        <div class="container pb-5">
            <div class="row g-3 mb-5">
                @for($i = 0; $i < 4; $i++)
                    <div class="col-6 col-md-3">
                        <div class="skeleton-tile rounded-4"></div>
                    </div>
                @endfor
            </div>
            <div class="row g-5">
                <div class="col-lg-7">
                    <div class="skeleton-block rounded-4" style="height: 400px;"></div>
                </div>
                <div class="col-lg-5">
                    <div class="skeleton-block rounded-4 mb-4" style="height: 300px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content - Hidden initially -->
<div id="facilityContent" style="display: none;">
    <div class="hero-banner position-relative overflow-hidden mb-5">
        <div id="heroImageContainer" class="hero-image-overlay"></div>
        
<div class="hero-breadcrumb">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="/home">Home</a></li>
                <li class="breadcrumb-item"><a href="/booking-catalog">Booking Catalog</a></li>
                <li class="breadcrumb-item active text-white" aria-current="page" id="breadcrumbCurrent">
                    Facility Details
                </li>
            </ol>
        </nav>
    </div>
</div>

        <div class="container position-relative h-100 d-flex align-items-end pb-4">
                <div class="row w-100 align-items-end">
                    <div class="col-md-8 text-white mb-4 mb-md-0">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="badge fw-bold px-3 py-2" id="statusBadge"></span>
                            <span class="text-white-50 small fw-bold" id="facilityCodeLabel"></span>
                        </div>
                        <h1 class="display-3 fw-bold mb-0" id="facilityName"></h1>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <button type="button" id="btnReserve"
                            class="btn btn-reserve btn-lg px-5 py-3 fw-bold shadow-sm rounded-3">
                            <i class="bi bi-calendar-check me-2"></i>Reserve Facility
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="container pb-5">
            <div class="row g-3 mb-5">
                <div class="col-6 col-md-3">
                    <div class="info-tile p-4 h-100 rounded-4">
                        <span class="tile-label">CAPACITY</span>
                        <div class="d-flex align-items-baseline gap-2 mt-2">
                            <span class="tile-value" id="capacityVal">-</span>
                            <small class="text-muted fw-bold">Unit</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="info-tile p-4 h-100 rounded-4">
                        <span class="tile-label">ENVIRONMENT</span>
                        <div class="d-flex align-items-center gap-2 mt-2">
                            <i class="bi bi-house-door-fill text-primary fs-4"></i>
                            <span class="tile-value-sm" id="envVal">-</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="info-tile p-4 h-100 rounded-4">
                        <span class="tile-label">FLOOR LEVELS</span>
                        <div class="d-flex align-items-baseline gap-2 mt-2">
                            <span class="tile-value" id="floorVal">-</span>
                            <small class="text-muted fw-bold">Stories</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="info-tile p-4 h-100 rounded-4">
                        <span class="tile-label">RATE TYPE</span>
                        <div class="d-flex align-items-center gap-2 mt-2">
                            <i class="bi bi-clock-fill text-primary fs-4"></i>
                            <span class="tile-value-sm" id="rateVal">-</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-5">
                <div class="col-lg-7">
                    <div class="overview-card p-5 rounded-4 h-100">
                        <h3 class="fw-bold mb-4 d-flex align-items-center gap-3">
                            <i class="bi bi-file-earmark-text-fill"></i>
                            Architectural Overview
                        </h3>
                        <div id="fullDescription" class="overview-text"></div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="identity-card p-5 rounded-4 mb-4">
                        <h6 class="text-uppercase fw-bold ls-widest text-muted mb-4">Facility Identity</h6>

                        <div class="mb-4">
                            <label class="small fw-bold text-uppercase opacity-50 d-block">Category</label>
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-grid-fill text-primary"></i>
                                <span class="fw-bold" id="catName">-</span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="small fw-bold text-uppercase opacity-50 d-block">Sub-Category</label>
                            <span class="fw-bold" id="subCatName">-</span>
                        </div>

                        <div class="location-box p-4 rounded-3 bg-light">
                            <label class="small fw-bold text-uppercase opacity-50 d-block mb-2">Location Notes</label>
                            <p class="fst-italic mb-0" id="locNote"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


@endsection
@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const facilityId = window.location.pathname.split('/').pop();
            const btnReserve = document.getElementById('btnReserve');

            // 1. Fetch Facility Details and Session State
            Promise.all([
                fetch(`/api/facility-details/${facilityId}`).then(res => res.json()),
                fetch('/requisition/get-items').then(res => res.json())
            ])
.then(([facilityResponse, sessionResponse]) => {
if (facilityResponse.success && facilityResponse.data) {
    const data = facilityResponse.data;

    // --- UPDATED BREADCRUMB POPULATION ---
    const breadcrumbCurrent = document.getElementById('breadcrumbCurrent');
    if (breadcrumbCurrent) {
        breadcrumbCurrent.textContent = data.facility_name || 'Facility Details';
    }

    // Hero Content Population
    const statusBadge = document.getElementById('statusBadge');
    const status = data.status_name || 'Unknown';
                        statusBadge.textContent = status.toUpperCase();

                        if (status === 'Available') {
                            statusBadge.classList.add('bg-success', 'text-white');
                        } else if (status === 'Unavailable') {
                            statusBadge.classList.add('bg-danger', 'text-white');
                            btnReserve.disabled = true;
                        } else {
                            statusBadge.classList.add('bg-light', 'text-primary');
                        }

                        document.getElementById('facilityName').textContent = data.facility_name || 'N/A';
                        document.getElementById('facilityCodeLabel').textContent = data.facility_code ? `ID: ${data.facility_code}` : '';

                        const bgImg = data.images && data.images.length > 0 ? data.images[0].image_url : '/images/placeholder-facility.png';
                        document.getElementById('heroImageContainer').style.backgroundImage = `url('${bgImg}')`;

                        document.getElementById('capacityVal').textContent = data.capacity || 'N/A';
                        document.getElementById('envVal').textContent = data.location_type || 'N/A';
                        document.getElementById('floorVal').textContent = data.total_levels || '1';
                        document.getElementById('rateVal').textContent = data.rate_type || 'N/A';

                        document.getElementById('fullDescription').innerHTML = data.description || 'No description available.';
                        document.getElementById('catName').textContent = data.category_name || 'Uncategorized';
                        document.getElementById('subCatName').textContent = data.subcategory_name || 'N/A';
                        document.getElementById('locNote').textContent = data.location_note ? `"${data.location_note}"` : 'No location notes provided.';

                        // --- CHECK SESSION STATE ---
                        if (sessionResponse.success && sessionResponse.data.selected_items) {
                            const isInSession = sessionResponse.data.selected_items.some(item =>
                                item.type === 'facility' && item.facility_id == facilityId
                            );
                            if (isInSession) {
                                setRemoveState(btnReserve);
                            } else {
                                setReserveState(btnReserve);
                            }
                        }

                        btnReserve.addEventListener('click', function () {
                            if (this.classList.contains('btn-remove')) {
                                removeFromRequisition(facilityId, btnReserve);
                            } else {
                                addToRequisition(facilityId, btnReserve);
                            }
                        });

                        document.getElementById('loadingState').style.display = 'none';
                        document.getElementById('facilityContent').style.display = 'block';
                    } else {
                        showError('Facility not found');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('Failed to load facility details');
                });
        });

        function addToRequisition(id, btn) {
            btn.disabled = true;
            // Keep the current class, don't remove it
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Adding facility...';

            fetch('/requisition/add-item', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ facility_id: id, type: 'facility' })
            })
                .then(res => res.json())
                .then(data => {
                    btn.disabled = false;
                    if (data.success) {
                        setRemoveState(btn);
                        document.dispatchEvent(new CustomEvent('cart-updated', { detail: data.data }));
                    } else {
                        alert(data.message || 'Failed to add facility.');
                        setReserveState(btn);
                    }
                })
                .catch(error => {
                    btn.disabled = false;
                    setReserveState(btn);
                });
        }

        function removeFromRequisition(id, btn) {
            btn.disabled = true;
            // Keep the current class, don't remove it
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Removing facility...';

            fetch('/requisition/remove-item', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ facility_id: id, type: 'facility' })
            })
                .then(res => res.json())
                .then(data => {
                    btn.disabled = false;
                    if (data.success) {
                        setReserveState(btn);
                        document.dispatchEvent(new CustomEvent('cart-updated', { detail: data.data }));
                    } else {
                        alert(data.message || 'Failed to remove facility.');
                        setRemoveState(btn);
                    }
                })
                .catch(error => {
                    btn.disabled = false;
                    setRemoveState(btn);
                });
        }
        function setRemoveState(btn) {
            btn.classList.remove('btn-reserve');
            btn.classList.add('btn-remove');
            btn.style.opacity = '1';
            btn.innerHTML = '<i class="bi bi-x-circle me-2"></i>Remove Facility';
        }

        function setReserveState(btn) {
            btn.classList.remove('btn-remove');
            btn.classList.add('btn-reserve');
            btn.style.opacity = '1';
            btn.innerHTML = '<i class="bi bi-calendar-check me-2"></i>Reserve Facility';
        }

        function showError(message) {
            const wrapper = document.getElementById('loadingState');
            wrapper.innerHTML = `<div class="alert alert-danger">${message}</div>`;
        }
    </script>
@endsection
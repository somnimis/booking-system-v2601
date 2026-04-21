{{-- resources/views/public/pick-a-venue.blade.php --}}
@extends('layouts.app')

@section('title', 'Pick a Venue - CPU Booking')

@section('content')
    <div class="container py-5">
        <!-- Header Section -->
        <div class="text-center mb-5">
            <h1 class="display-5 fw-bold" style="color: var(--cpu-primary);">Pick a Venue</h1>
            <p class="lead text-muted">Choose from our wide selection of campus facilities for your event or activity</p>
            <div class="d-flex justify-content-center mt-3">
                <div class="border-bottom" style="width: 80px; border-color: var(--cpu-secondary) !important;"></div>
            </div>
        </div>

        <!-- Search and Filter Section -->
        <div class="row mb-5">
            <div class="col-md-6 mx-auto">
                <div class="input-group">
                    <span class="input-group-text bg-white">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" id="searchInput" class="form-control form-control-lg"
                        placeholder="Search for a venue by name or location...">
                    <button id="searchButton" class="btn btn-primary" type="button">
                        Search
                    </button>
                    <button id="clearSearch" class="btn btn-outline-secondary" type="button" style="display: none;">
                        <i class="bi bi-x-lg"></i> Clear
                    </button>
                </div>
            </div>
        </div>
        <!-- Grid Wrapper -->
        <div class="position-relative">

            <!-- Top Bar with Results Info, Pagination, and Controls -->
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <!-- Results Info - Left side -->
                <div id="resultsInfo" class="text-muted" style="display: none;">
                    Showing <span id="showingStart">0</span> to <span id="showingEnd">0</span> of <span
                        id="totalResults">0</span> venues
                </div>

                <!-- Top Pagination - Center -->
                <div id="topPaginationContainer" style="display: none;">
                    <nav aria-label="Venue pagination top">
                        <ul class="pagination pagination-sm mb-0" id="topPagination">
                            <!-- Pagination will be dynamically loaded here -->
                        </ul>
                    </nav>
                </div>

                <!-- Right side controls -->
                <div class="d-flex align-items-center gap-2">
                    <label class="text-muted small mb-0">Show:</label>
                    <select id="perPageSelect" class="form-select form-select-sm w-auto">
                        <option value="12">12 per page</option>
                        <option value="24">24 per page</option>
                        <option value="36">36 per page</option>
                        <option value="48">48 per page</option>
                    </select>
                    <button id="refreshCacheBtn" class="btn btn-sm btn-outline-secondary" title="Refresh data">
                        <i class="bi bi-arrow-repeat"></i>
                    </button>
                </div>
            </div>

            <!-- Loading Spinner -->
            <div id="loadingSpinner" class="text-center py-5">
                <div class="spinner-border" style="color: var(--cpu-primary);" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3 text-muted">Loading facilities...</p>
            </div>

            <!-- Venue Grid Wrapper -->
            <div id="venueGridWrapper">
                <!-- Venue Grid -->
                <div id="venueGrid" class="row g-4">
                    <!-- Venues will be dynamically loaded here -->
                </div>

                <!-- Bottom Pagination Section -->
                <div id="paginationContainer" class="d-flex justify-content-center mt-5" style="display: none;">
                    <nav aria-label="Venue pagination">
                        <ul class="pagination" id="pagination">
                            <!-- Pagination will be dynamically loaded here -->
                        </ul>
                    </nav>
                </div>
            </div>

            <!-- No Results Message -->
            <div id="noResults" class="text-center py-5" style="display: none;">
                <i class="bi bi-building fs-1 text-muted"></i>
                <h3 class="mt-3 text-muted">No venues found</h3>
                <p class="text-muted">Try adjusting your search terms</p>
            </div>
        </div>

        <style>
            .venue-card {
                transition: transform 0.2s ease, box-shadow 0.2s ease;
                cursor: pointer;
                border: none;
                border-radius: 15px;
                overflow: hidden;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            }

            .venue-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15);
            }

            .venue-card .card-img-top {
                height: 200px;
                object-fit: cover;
                background-color: var(--cpu-primary-light);
            }

            .venue-card .card-body {
                padding: 1.5rem;
            }

            .venue-card .card-title {
                color: var(--cpu-primary);
                font-weight: 600;
                margin-bottom: 0.75rem;
            }

            .location-badge {
                background-color: var(--cpu-primary-light);
                color: var(--cpu-primary);
                padding: 0.25rem 0.75rem;
                border-radius: 20px;
                font-size: 0.8rem;
                display: inline-flex;
                align-items: center;
                gap: 0.25rem;
                max-width: 100%;
                cursor: help;
            }

            .location-badge span {
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                max-width: 150px;
            }

            @media (max-width: 768px) {
                .location-badge span {
                    max-width: 100px;
                }
            }

            .capacity-badge {
                background-color: #f8f9fa;
                color: #6c757d;
                padding: 0.25rem 0.75rem;
                border-radius: 20px;
                font-size: 0.8rem;
                display: inline-flex;
                align-items: center;
                gap: 0.25rem;
            }

            .venue-card .fee-text {
                font-size: 1.1rem;
                font-weight: 600;
                color: var(--cpu-secondary-hover);
            }

            .venue-card .btn-view {
                background-color: var(--cpu-primary);
                color: white;
                border-radius: 25px;
                padding: 0.5rem 1.5rem;
                transition: all 0.2s ease;
            }

            .venue-card .btn-view:hover {
                background-color: var(--cpu-primary-hover);
                transform: scale(1.05);
            }

            .category-badge {
                position: absolute;
                top: 1rem;
                right: 1rem;
                background-color: rgba(0, 51, 102, 0.9);
                color: white;
                padding: 0.35rem 0.85rem;
                border-radius: 20px;
                font-size: 0.75rem;
                font-weight: 500;
                z-index: 1;
            }

            .pagination .page-link {
                color: var(--cpu-primary);
            }

            .pagination .page-item.active .page-link {
                background-color: var(--cpu-primary);
                border-color: var(--cpu-primary);
                color: white;
            }

            .pagination .page-link:hover {
                color: var(--cpu-primary-hover);
            }

            /* Add to the existing style section */
            #loadingSpinner {
                transition: padding 0.3s ease;
                min-height: 200px;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
            }

            #resultsInfo,
            #topPaginationContainer {
                transition: opacity 0.2s ease;
            }

            /* Ensure the venue grid has a minimum height to prevent layout shift */
            #venueGrid {
                min-height: 400px;
            }

            /* Skeleton loading effect for venue cards while loading */
            .venue-grid-loading .col-md-6 {
                opacity: 0.5;
                pointer-events: none;
            }

            html {
                scroll-behavior: smooth;
            }
        </style>
@endsection

    @section('scripts')
        <script>
            // Cache management
            let currentPage = 1;
            let currentSearch = '';
            let totalPages = 1;
            let isLoading = false;

            // Load parent facilities on page load
            document.addEventListener('DOMContentLoaded', function () {
                // Get URL parameters
                const urlParams = new URLSearchParams(window.location.search);
                const parentFacilityId = urlParams.get('parent_facility_id');
                const equipmentId = urlParams.get('equipment_id');

                // Determine what we're viewing
                let viewType = 'facilities'; // 'facilities' or 'equipment'
                let parentId = null;

                if (equipmentId) {
                    viewType = 'equipment';
                    console.log('Viewing equipment details for ID:', equipmentId);
                } else if (parentFacilityId) {
                    parentId = parentFacilityId;
                    console.log('Viewing child facilities for parent ID:', parentId);
                }

                // === UPDATE HEADER BASED ON VIEW TYPE === //
                const headerTitle = document.querySelector('.display-5');
                const headerDescription = document.querySelector('.lead');

                if (viewType === 'equipment') {
                    headerTitle.textContent = 'Browse Equipment';
                    headerDescription.textContent = 'Select equipment for your next event or activity';
                    document.querySelector('#searchInput').placeholder = 'Search for equipment by name...';
                } else if (parentId) {
                    headerTitle.textContent = 'Select a Space';
                    headerDescription.textContent = 'Choose from available spaces in this venue';
                    document.querySelector('#searchInput').placeholder = 'Search for spaces...';
                } else {
                    headerTitle.textContent = 'Pick a Venue';
                    headerDescription.textContent = 'Choose from our wide selection of campus facilities for your event or activity';
                    document.querySelector('#searchInput').placeholder = 'Search for a venue by name or location...';
                }

                loadParentFacilities();

                // Search button click
                const searchButton = document.getElementById('searchButton');
                searchButton.addEventListener('click', function () {
                    const searchInput = document.getElementById('searchInput');
                    currentSearch = searchInput.value;
                    currentPage = 1;
                    loadParentFacilities(true);
                });

                // Enter key in search input
                const searchInput = document.getElementById('searchInput');
                searchInput.addEventListener('keypress', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        currentSearch = this.value;
                        currentPage = 1;
                        loadParentFacilities(true);
                    }
                });

                // Clear search button
                const clearSearch = document.getElementById('clearSearch');
                clearSearch.addEventListener('click', function () {
                    searchInput.value = '';
                    currentSearch = '';
                    currentPage = 1;
                    loadParentFacilities(true);
                });

                // Per page selector
                const perPageSelect = document.getElementById('perPageSelect');
                perPageSelect.addEventListener('change', function () {
                    currentPage = 1;
                    loadParentFacilities(true);
                });

                // Refresh button
                const refreshBtn = document.getElementById('refreshCacheBtn');
                refreshBtn.addEventListener('click', function () {
                    loadParentFacilities(true, true);
                });
            });

            function loadParentFacilities(forceRefresh = false, bypassAllCache = false) {
                if (isLoading) return;
                isLoading = true;

                const loadingSpinner = document.getElementById('loadingSpinner');
                const venueGrid = document.getElementById('venueGrid');
                const paginationContainer = document.getElementById('paginationContainer');
                const noResults = document.getElementById('noResults');
                const resultsInfo = document.getElementById('resultsInfo');
                const clearSearchBtn = document.getElementById('clearSearch');
                const topPaginationContainer = document.getElementById('topPaginationContainer');

                // Show loading spinner with extra padding
                loadingSpinner.style.display = 'block';
                loadingSpinner.style.padding = '100px 0';

                // Hide venue grid
                venueGrid.style.display = 'none';
                noResults.style.display = 'none';

                // Apply loading state to pagination containers
                if (paginationContainer) {
                    paginationContainer.style.opacity = '0.5';
                    paginationContainer.style.pointerEvents = 'none';
                }
                if (topPaginationContainer) {
                    topPaginationContainer.style.opacity = '0.5';
                    topPaginationContainer.style.pointerEvents = 'none';
                }

                if (resultsInfo.style.display === 'block') {
                    resultsInfo.style.opacity = '0.5';
                }

                clearSearchBtn.style.display = currentSearch ? 'block' : 'none';

                const perPage = document.getElementById('perPageSelect').value;
                let url = '';

                // Build URL based on view type
                if (viewType === 'equipment') {
                    url = `/api/equipment?page=${currentPage}&per_page=${perPage}`;
                    if (currentSearch) {
                        url += `&search=${encodeURIComponent(currentSearch)}`;
                    }
                } else if (parentId) {
                    // Show child facilities of a parent
                    url = `/api/parent-facilities/children?parent_id=${parentId}&page=${currentPage}&per_page=${perPage}`;
                    if (currentSearch) {
                        url += `&search=${encodeURIComponent(currentSearch)}`;
                    }
                } else {
                    // Show all parent facilities
                    url = `/api/parent-facilities?page=${currentPage}&per_page=${perPage}`;
                    if (currentSearch) {
                        url += `&search=${encodeURIComponent(currentSearch)}`;
                    }
                }

                if (bypassAllCache) {
                    url += `&_=${Date.now()}`;
                }

                console.log('Loading page:', currentPage, 'URL:', url);

                fetch(url, {
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                })
                    .then(response => response.json())
                    .then(response => {
                        if (viewType === 'equipment') {
                            // Handle equipment response
                            if (response.data && response.data.length > 0) {
                                renderEquipment(response.data);
                                renderEquipmentPagination(response);
                                updateEquipmentResultsInfo(response);
                                venueGrid.style.display = 'flex';
                                resultsInfo.style.display = 'block';
                                if (paginationContainer) {
                                    paginationContainer.style.display = 'flex';
                                    paginationContainer.style.opacity = '1';
                                    paginationContainer.style.pointerEvents = 'auto';
                                }
                                if (topPaginationContainer) {
                                    topPaginationContainer.style.display = 'flex';
                                    topPaginationContainer.style.opacity = '1';
                                    topPaginationContainer.style.pointerEvents = 'auto';
                                }
                                resultsInfo.style.opacity = '1';
                            } else {
                                noResults.style.display = 'block';
                                resultsInfo.style.display = 'none';
                                if (paginationContainer) paginationContainer.style.display = 'none';
                                if (topPaginationContainer) topPaginationContainer.style.display = 'none';
                            }
                        } else if (response.success) {
                            // Handle facilities response
                            const venues = response.data;
                            const pagination = response.pagination;
                            totalPages = pagination.last_page;

                            if (venues.length > 0) {
                                renderVenues(venues);
                                renderPagination(pagination);
                                updateResultsInfo(pagination);
                                venueGrid.style.display = 'flex';
                                resultsInfo.style.display = 'block';
                                if (paginationContainer) {
                                    paginationContainer.style.display = 'flex';
                                    paginationContainer.style.opacity = '1';
                                    paginationContainer.style.pointerEvents = 'auto';
                                }
                                if (topPaginationContainer) {
                                    topPaginationContainer.style.display = 'flex';
                                    topPaginationContainer.style.opacity = '1';
                                    topPaginationContainer.style.pointerEvents = 'auto';
                                }
                                resultsInfo.style.opacity = '1';
                            } else {
                                noResults.style.display = 'block';
                                resultsInfo.style.display = 'none';
                                if (paginationContainer) paginationContainer.style.display = 'none';
                                if (topPaginationContainer) topPaginationContainer.style.display = 'none';
                            }
                        } else {
                            noResults.style.display = 'block';
                            resultsInfo.style.display = 'none';
                            if (paginationContainer) paginationContainer.style.display = 'none';
                            if (topPaginationContainer) topPaginationContainer.style.display = 'none';
                        }
                    })
                    .catch(error => {
                        console.error('Error loading items:', error);
                        noResults.style.display = 'block';
                        resultsInfo.style.display = 'none';
                        if (paginationContainer) paginationContainer.style.display = 'none';
                        if (topPaginationContainer) topPaginationContainer.style.display = 'none';
                        document.querySelector('#noResults p').textContent = 'Failed to load items. Please try again.';
                    })
                    .finally(() => {
                        loadingSpinner.style.display = 'none';
                        loadingSpinner.style.padding = '50px 0';
                        isLoading = false;
                    });
            }

            function truncateText(text, maxLength = 30) {
                if (!text) return 'Location TBA';
                if (text.length <= maxLength) return text;
                return text.substring(0, maxLength) + '...';
            }

            function renderVenues(venues) {
                const venueGrid = document.getElementById('venueGrid');

                let html = '';
                venues.forEach(venue => {
                    const imageUrl = venue.images && venue.images.length > 0
                        ? venue.images[0].image_url
                        : 'https://res.cloudinary.com/dn98ntlkd/image/upload/v1759850278/t4fyv56wog6pglhwvwtn.png';

                    const hasChildren = venue.has_children || false;
                    const locationTypeIcon = venue.location_type === 'Indoors' ? 'bi-building' : 'bi-tree';
                    const capacityText = venue.capacity === 1 ? 'Flexible Capacity' : `Up to ${venue.capacity} people`;
                    const feeText = venue.base_fee == 0 ? 'Contact for pricing' : `₱${parseFloat(venue.base_fee).toLocaleString()}`;
                    const description = venue.description ? venue.description.substring(0, 100) : 'No description available';
                    const locationNote = venue.location_note || 'Location TBA';
                    const truncatedLocation = truncateText(locationNote, 25);

                    html += `
                                                        <div class="col-md-6 col-lg-4">
                                                            <div class="card venue-card h-100" data-facility-id="${venue.facility_id}" data-has-children="${hasChildren}">
                                                                <div class="position-relative">
                                                                    <img src="${imageUrl}" class="card-img-top" alt="${escapeHtml(venue.facility_name)}" 
                                                                         loading="lazy"
                                                                         onerror="this.onerror=null; this.src='/images/placeholder-facility.png'">
                                                                    <span class="category-badge">
                                                                        <i class="bi ${locationTypeIcon} me-1"></i>
                                                                        ${venue.location_type}
                                                                    </span>
                                                                </div>
                                                                <div class="card-body d-flex flex-column">
                                                                    <h5 class="card-title">${escapeHtml(venue.facility_name)}</h5>
                                                                    <p class="card-text text-muted small mb-3">${escapeHtml(description)}${venue.description && venue.description.length > 100 ? '...' : ''}</p>

                                                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                                                        <span class="location-badge" title="${escapeHtml(locationNote)}">
                                                                            <i class="bi bi-geo-alt"></i>
                                                                            <span>${escapeHtml(truncatedLocation)}</span>
                                                                        </span>
                                                                        <span class="capacity-badge">
                                                                            <i class="bi bi-people"></i>
                                                                            ${capacityText}
                                                                        </span>
                                                                    </div>

                                                                    <div class="mt-auto">
                                                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                                                            <span class="fee-text">${feeText}</span>
                                                                            <small class="text-muted">${venue.rate_type}</small>
                                                                        </div>
                                                                        <button class="btn btn-view w-100 view-venue-btn">
                                                                            ${hasChildren ? 'View Available Spaces <i class="bi bi-arrow-right ms-2"></i>' : 'View Details <i class="bi bi-arrow-right ms-2"></i>'}
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    `;
                });

                venueGrid.innerHTML = html;

                // Attach click handlers to cards
                document.querySelectorAll('.venue-card').forEach(card => {
                    card.addEventListener('click', function (e) {
                        if (e.target.closest('.view-venue-btn')) return;

                        const facilityId = this.dataset.facilityId;
                        const hasChildren = this.dataset.hasChildren === 'true';

                        if (hasChildren) {
                            window.location.href = `/booking-catalog?parent_facility_id=${facilityId}`;
                        } else {
                            window.location.href = `/facility/${facilityId}`;
                        }
                    });
                });

                // Button click handlers
                document.querySelectorAll('.view-venue-btn').forEach(btn => {
                    btn.addEventListener('click', function (e) {
                        e.stopPropagation();
                        const card = this.closest('.venue-card');
                        const facilityId = card.dataset.facilityId;
                        const hasChildren = card.dataset.hasChildren === 'true';

                        if (hasChildren) {
                            window.location.href = `/booking-catalog?parent_facility_id=${facilityId}`;
                        } else {
                            window.location.href = `/facility/${facilityId}`;
                        }
                    });
                });
            }

            function renderEquipment(items) {
                const venueGrid = document.getElementById('venueGrid');
                const html = items.map(item => {
                    const imageUrl = item.images?.[0]?.image_url || 'https://res.cloudinary.com/dn98ntlkd/image/upload/v1759850278/t4fyv56wog6pglhwvwtn.png';
                    const availableQty = item.available_quantity || 0;
                    const statusText = availableQty > 0 ? 'Available' : 'Out of Stock';
                    const statusColor = availableQty > 0 ? 'var(--cpu-secondary)' : '#dc3545';

                    return `
                <div class="col-md-6 col-lg-4">
                    <div class="card venue-card h-100" data-equipment-id="${item.equipment_id}">
                        <div class="position-relative">
                            <img src="${imageUrl}" class="card-img-top" alt="${escapeHtml(item.equipment_name)}" loading="lazy"
                                onerror="this.onerror=null; this.src='/images/placeholder-equipment.png'">
                            <span class="category-badge" style="background-color: ${statusColor}">
                                <i class="bi ${availableQty > 0 ? 'bi-check-circle' : 'bi-x-circle'} me-1"></i>${statusText}
                            </span>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">${escapeHtml(item.equipment_name)}</h5>
                            <p class="card-text text-muted small mb-3">${escapeHtml(item.description?.substring(0, 100) || 'No description available')}</p>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="fee-text">${availableQty} available</span>
                                <small class="text-muted">${item.category?.category_name || 'Uncategorized'}</small>
                            </div>
                            <button class="btn btn-view w-100 view-equipment-btn">View Details <i class="bi bi-arrow-right ms-2"></i></button>
                        </div>
                    </div>
                </div>
            `;
                }).join('');

                venueGrid.innerHTML = html;

// Update the equipment card click handler
venueGrid.querySelectorAll('.venue-card').forEach(card => {
    card.addEventListener('click', (e) => {
        if (e.target.closest('.view-equipment-btn')) return;
        const equipmentId = card.dataset.equipmentId;
        window.location.href = `/equipment-details/${equipmentId}`;
    });
});

// Update the equipment button click handler
venueGrid.querySelectorAll('.view-equipment-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const equipmentId = btn.closest('.venue-card').dataset.equipmentId;
        window.location.href = `/equipment-details/${equipmentId}`;
    });
});
            }

            function renderEquipmentPagination(paginationData) {
                const pagination = {
                    current_page: paginationData.current_page,
                    last_page: paginationData.last_page,
                    per_page: paginationData.per_page,
                    total: paginationData.total
                };

                const paginationHtml = generatePaginationHtml(pagination);

                const bottomPaginationEl = document.getElementById('pagination');
                const topPaginationEl = document.getElementById('topPagination');

                if (bottomPaginationEl) bottomPaginationEl.innerHTML = paginationHtml;
                if (topPaginationEl) topPaginationEl.innerHTML = paginationHtml;

                const topContainer = document.getElementById('topPaginationContainer');
                const bottomContainer = document.getElementById('paginationContainer');

                if (topContainer) topContainer.style.display = 'flex';
                if (bottomContainer) bottomContainer.style.display = 'flex';

                const attachHandlers = (containerId) => {
                    const container = document.getElementById(containerId);
                    if (!container) return;

                    container.querySelectorAll('.page-link[data-page]').forEach(button => {
                        button.removeEventListener('click', handleEquipmentPaginationClick);
                        button.addEventListener('click', handleEquipmentPaginationClick);
                    });
                };

                attachHandlers('pagination');
                attachHandlers('topPagination');
            }

            function handleEquipmentPaginationClick(e) {
                e.preventDefault();
                e.stopPropagation();

                const page = parseInt(this.dataset.page);
                if (!isNaN(page) && page !== currentPage) {
                    currentPage = page;
                    loadParentFacilities(false);
                    smoothScrollToTop();
                }
            }

            function updateEquipmentResultsInfo(paginationData) {
                const showingStart = (paginationData.current_page - 1) * paginationData.per_page + 1;
                const showingEnd = Math.min(paginationData.current_page * paginationData.per_page, paginationData.total);

                document.getElementById('showingStart').textContent = showingStart;
                document.getElementById('showingEnd').textContent = showingEnd;
                document.getElementById('totalResults').textContent = paginationData.total;

                const resultsInfo = document.getElementById('resultsInfo');
                const existingSearchSpan = document.getElementById('searchTerm');
                if (existingSearchSpan) existingSearchSpan.remove();

                if (currentSearch && currentSearch.trim() !== '') {
                    const searchSpan = document.createElement('span');
                    searchSpan.id = 'searchTerm';
                    searchSpan.innerHTML = ` for "<strong>${escapeHtml(currentSearch)}</strong>"`;
                    resultsInfo.appendChild(searchSpan);
                }
            }

            function renderPagination(pagination) {
                // Use the pagination data directly from the server
                const currentPageFromServer = pagination.current_page;

                // Update our currentPage to match the server
                if (currentPageFromServer !== currentPage) {
                    currentPage = currentPageFromServer;
                }

                const paginationHtml = generatePaginationHtml(pagination);

                // Render both top and bottom pagination - only update content, not visibility
                const bottomPaginationEl = document.getElementById('pagination');
                const topPaginationEl = document.getElementById('topPagination');

                if (bottomPaginationEl) bottomPaginationEl.innerHTML = paginationHtml;
                if (topPaginationEl) topPaginationEl.innerHTML = paginationHtml;

                // Show both pagination containers (they should already be visible from load)
                const topContainer = document.getElementById('topPaginationContainer');
                const bottomContainer = document.getElementById('paginationContainer');

                if (topContainer) topContainer.style.display = 'flex';
                if (bottomContainer) bottomContainer.style.display = 'flex';

                // Remove existing event listeners and add new ones
                const attachHandlers = (containerId) => {
                    const container = document.getElementById(containerId);
                    if (!container) return;

                    container.querySelectorAll('.page-link[data-page]').forEach(button => {
                        button.removeEventListener('click', handlePaginationClick);
                        button.addEventListener('click', handlePaginationClick);
                    });
                };

                attachHandlers('pagination');
                attachHandlers('topPagination');
            }

            function handlePaginationClick(e) {
                e.preventDefault();
                e.stopPropagation();

                const page = parseInt(this.dataset.page);
                if (!isNaN(page) && page !== currentPage) {
                    console.log('Navigating to page:', page);
                    currentPage = page;
                    loadParentFacilities(false);
                    // Smooth scroll to top with multiple fallbacks
                    smoothScrollToTop();
                }
            }

            function smoothScrollToTop() {
                // Method 1: Modern smooth scroll behavior
                try {
                    window.scrollTo({
                        top: 0,
                        left: 0,
                        behavior: 'smooth'
                    });
                } catch (e) {
                    // Method 2: Fallback for older browsers
                    try {
                        const scrollStep = -window.scrollY / (500 / 15);
                        const scrollInterval = setInterval(function () {
                            if (window.scrollY !== 0) {
                                window.scrollBy(0, scrollStep);
                            } else {
                                clearInterval(scrollInterval);
                            }
                        }, 15);
                    } catch (e2) {
                        // Method 3: Last resort - instant scroll
                        window.scrollTo(0, 0);
                    }
                }
            }
            function generatePaginationHtml(pagination) {
                let html = '';

                // Previous button - use the actual previous page from server
                const prevPage = pagination.current_page - 1;
                if (prevPage >= 1) {
                    html += `
                                    <li class="page-item">
                                        <button class="page-link" data-page="${prevPage}" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </button>
                                    </li>
                                `;
                } else {
                    html += `
                                    <li class="page-item disabled">
                                        <span class="page-link">&laquo;</span>
                                    </li>
                                `;
                }

                // Page numbers - use the actual pagination data
                const startPage = Math.max(1, pagination.current_page - 2);
                const endPage = Math.min(pagination.last_page, pagination.current_page + 2);

                // First page
                if (startPage > 1) {
                    html += `
                                    <li class="page-item">
                                        <button class="page-link" data-page="1">1</button>
                                    </li>
                                `;
                    if (startPage > 2) {
                        html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                    }
                }

                // Page numbers
                for (let i = startPage; i <= endPage; i++) {
                    if (i === pagination.current_page) {
                        html += `
                                        <li class="page-item active">
                                            <span class="page-link">${i}</span>
                                        </li>
                                    `;
                    } else {
                        html += `
                                        <li class="page-item">
                                            <button class="page-link" data-page="${i}">${i}</button>
                                        </li>
                                    `;
                    }
                }

                // Last page
                if (endPage < pagination.last_page) {
                    if (endPage < pagination.last_page - 1) {
                        html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                    }
                    html += `
                                    <li class="page-item">
                                        <button class="page-link" data-page="${pagination.last_page}">${pagination.last_page}</button>
                                    </li>
                                `;
                }

                // Next button - use the actual next page from server
                const nextPage = pagination.current_page + 1;
                if (nextPage <= pagination.last_page) {
                    html += `
                                    <li class="page-item">
                                        <button class="page-link" data-page="${nextPage}" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </button>
                                    </li>
                                `;
                } else {
                    html += `
                                    <li class="page-item disabled">
                                        <span class="page-link">&raquo;</span>
                                    </li>
                                `;
                }

                return html;
            }

            function updateResultsInfo(pagination) {
                const showingStart = (pagination.current_page - 1) * pagination.per_page + 1;
                const showingEnd = Math.min(pagination.current_page * pagination.per_page, pagination.total);

                document.getElementById('showingStart').textContent = showingStart;
                document.getElementById('showingEnd').textContent = showingEnd;
                document.getElementById('totalResults').textContent = pagination.total;

                // Add search term to results info if searching
                const resultsInfo = document.getElementById('resultsInfo');

                // Remove existing search term span if it exists
                const existingSearchSpan = document.getElementById('searchTerm');
                if (existingSearchSpan) {
                    existingSearchSpan.remove();
                }

                // Add new search term span if searching
                if (currentSearch && currentSearch.trim() !== '') {
                    const searchSpan = document.createElement('span');
                    searchSpan.id = 'searchTerm';
                    searchSpan.innerHTML = ` for "<strong>${escapeHtml(currentSearch)}</strong>"`;
                    resultsInfo.appendChild(searchSpan);
                }
            }

            function escapeHtml(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        </script>
    @endsection
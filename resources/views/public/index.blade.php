@extends('layouts.app')

@section('title', 'CPU Facility & Equipment Booking Services')

@section('content')
  <link rel="stylesheet" href="{{ asset('css/public/global-styles.css') }}" />
  <style>
    :root {
      --glass-bg: rgba(255, 255, 255, 0.05);
      --glass-border: rgba(255, 255, 255, 0.15);
      --cpu-gold: #e8b342;
      --cpu-gold-hover: #f7c55f;
      --cpu-blue: #003366;
      --cpu-blue-hover: #004a94;
    }

    body {
      background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), 
                  url("{{ asset('assets/homepage.jpg') }}");
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      background-attachment: fixed;
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      color: #fff;
    }
    

    .section-header-flex {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}

.section-title-area {
  margin-bottom: 0;
}

    /* Modern Hero Section */
    .hero-section {
      min-height: 70vh;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      opacity: 0;
      transition: all 1s cubic-bezier(0.2, 0.8, 0.2, 1);
    }

    .hero-section.visible {
      opacity: 1;
    }

    .hero-section h1 {
      font-size: clamp(2.5rem, 5vw, 4rem);
      font-weight: 800;
      letter-spacing: -0.02em;
      line-height: 1.1;
      margin-bottom: 1.5rem;
    }

    /* Glass Panels for Sections */
    .catalog-section-container {
      background: var(--glass-bg);
      backdrop-filter: blur(10px);
      border: 1px solid var(--glass-border);
      border-radius: 30px;
      padding: 3rem;
      margin-bottom: 4rem;
      box-shadow: 0 15px 35px rgba(0,0,0,0.2);
    }

    .section-title-area h2 {
      font-weight: 700;
      letter-spacing: -0.5px;
      position: relative;
      display: inline-block;
      color: white;
    }

    .section-title-area h2::after {
      content: '';
      position: absolute;
      bottom: -10px;
      left: 0;
      width: 50px;
      height: 4px;
      background: var(--cpu-gold);
      border-radius: 2px;
    }

    /* Modern Search Inputs */
    .search-wrapper .input-group {
      background: rgba(255, 255, 255, 0.9);
      border-radius: 12px;
      overflow: hidden;
      transition: all 0.3s ease;
      border: 2px solid transparent;
    }

    .search-wrapper .input-group:focus-within {
      border-color: var(--cpu-gold);
      box-shadow: 0 5px 15px rgba(232, 179, 66, 0.3);
    }

    .search-wrapper input {
      border: none !important;
      padding: 12px 15px;
      background: transparent;
    }

    .search-wrapper .input-group-text {
      background: transparent;
      border: none;
      color: #6c757d;
    }

    /* Modernized Venue/Equipment Cards */
    .venue-card {
      background: #fff;
      border: none;
      border-radius: 20px;
      transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      color: #333;
      cursor: pointer;
      overflow: hidden;
    }

    .venue-card:hover {
      box-shadow: 0 20px 40px rgba(0,0,0,0.4);
    }

    .venue-card .card-img-top {
      height: 220px;
      object-fit: cover;
      width: 100%;
    }

    .btn-view {
      background: var(--cpu-blue);
      color: white;
      border-radius: 12px;
      font-weight: 600;
      padding: 0.8rem;
      border: none;
      transition: all 0.3s ease;
    }

    .btn-view:hover {
      background: var(--cpu-blue-hover);
      box-shadow: 0 8px 20px rgba(0, 51, 102, 0.3);
      color: white;
    }

.btn-gold {
  background: linear-gradient(135deg, var(--cpu-gold), #f3c969);
  color: #111;
  border-radius: 14px;
  border: 1px solid rgba(0, 0, 0, 0.08);
  font-weight: 600;
  letter-spacing: 0.3px;
  padding: 0.6rem 1.4rem;
  box-shadow: 0 6px 16px rgba(232, 179, 66, 0.25);
  transition: all 0.25s ease;
  position: relative;
  overflow: hidden;
}

.btn-gold:hover {
  background: linear-gradient(135deg, var(--cpu-gold-hover), #f7d47a);
  box-shadow: 0 12px 28px rgba(232, 179, 66, 0.35);
  color: #111;
}

.btn-gold:active {
  box-shadow: 0 6px 14px rgba(232, 179, 66, 0.2);
}

.btn-gold:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(232, 179, 66, 0.25);
}
    /* Skeleton Loading Update */
    .skeleton-card {
      border-radius: 20px;
      background: rgba(255, 255, 255, 0.1);
      overflow: hidden;
    }

    .skeleton-image {
      height: 220px;
      background: linear-gradient(90deg, rgba(255,255,255,0.1) 25%, rgba(255,255,255,0.2) 50%, rgba(255,255,255,0.1) 75%);
      background-size: 200% 100%;
      animation: shimmer 1.5s infinite;
    }

    .skeleton-content {
      padding: 1.5rem;
    }

    .skeleton-title {
      height: 24px;
      width: 80%;
      background: linear-gradient(90deg, rgba(255,255,255,0.1) 25%, rgba(255,255,255,0.2) 50%, rgba(255,255,255,0.1) 75%);
      background-size: 200% 100%;
      animation: shimmer 1.5s infinite;
      border-radius: 4px;
      margin-bottom: 12px;
    }

    .skeleton-text {
      height: 60px;
      background: linear-gradient(90deg, rgba(255,255,255,0.1) 25%, rgba(255,255,255,0.2) 50%, rgba(255,255,255,0.1) 75%);
      background-size: 200% 100%;
      animation: shimmer 1.5s infinite;
      border-radius: 4px;
      margin-bottom: 12px;
    }

    .skeleton-badge {
      height: 28px;
      width: 100px;
      background: linear-gradient(90deg, rgba(255,255,255,0.1) 25%, rgba(255,255,255,0.2) 50%, rgba(255,255,255,0.1) 75%);
      background-size: 200% 100%;
      animation: shimmer 1.5s infinite;
      border-radius: 20px;
      display: inline-block;
    }

    .skeleton-button {
      height: 48px;
      background: linear-gradient(90deg, rgba(255,255,255,0.1) 25%, rgba(255,255,255,0.2) 50%, rgba(255,255,255,0.1) 75%);
      background-size: 200% 100%;
      animation: shimmer 1.5s infinite;
      border-radius: 12px;
      margin-top: 16px;
    }

    @keyframes shimmer {
      0% {
        background-position: 200% 0;
      }
      100% {
        background-position: -200% 0;
      }
    }

    /* Custom Toast */
    .toast {
      backdrop-filter: blur(15px);
      background: rgba(255, 255, 255, 0.95) !important;
      border-radius: 15px !important;
    }

    .toast-header {
      border-radius: 15px 15px 0 0 !important;
    }

    .location-badge {
      background-color: #e9ecef;
      color: var(--cpu-blue);
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

    .fee-text {
      font-size: 1.1rem;
      font-weight: 600;
      color: var(--cpu-gold);
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

    .results-info {
      color: rgba(255,255,255,0.8);
      font-size: 0.9rem;
    }

    .no-results {
      text-align: center;
      padding: 50px 0;
    }

    .no-results i {
      font-size: 3rem;
      color: rgba(255, 255, 255, 0.5);
    }

    .no-results h3 {
      color: white;
      margin-top: 1rem;
    }

    .no-results p {
      color: rgba(255, 255, 255, 0.5);
    }

    .text-white-50 {
      color: rgba(255, 255, 255, 0.7) !important;
    }
  </style>

<section class="hero-section">
  <div class="container">
    <div class="hero-glass-card col-lg-8 mx-auto">
      <h2 class="fw-bold">
        Simplify the way you book campus facilities, equipment, and services — all in one platform, anytime, anywhere.
      </h2>

      <br>

      <div class="d-flex justify-content-center gap-3 flex-wrap">
        <a href="/booking-catalog" class="btn btn-gold btn-lg px-5 py-3 fw-bold">
          Start Booking
        </a>
      </div>
    </div>
  </div>
</section>

  <div class="container pb-5">
    <!-- Facilities Section -->
    <div id="facilitiesSection" class="catalog-section-container">
<div class="section-header-flex">
  <div class="section-title-area">
    <h2>Available Venues</h2>
    <p class="text-white-50 mt-2">Discover campus spaces for your events and meetings.</p>
  </div>
  <a href="/booking-catalog" class="btn btn-gold px-4 py-2">View All Venues →</a>
</div>

      <div id="facilitiesSkeleton" class="row g-4" style="display: none;">
        <div class="col-md-6 col-lg-4">
          <div class="skeleton-card">
            <div class="skeleton-image"></div>
            <div class="skeleton-content">
              <div class="skeleton-title"></div>
              <div class="skeleton-text"></div>
              <div class="d-flex gap-2 mb-3">
                <div class="skeleton-badge"></div>
                <div class="skeleton-badge"></div>
              </div>
              <div class="skeleton-button"></div>
            </div>
          </div>
        </div>
        <div class="col-md-6 col-lg-4">
          <div class="skeleton-card">
            <div class="skeleton-image"></div>
            <div class="skeleton-content">
              <div class="skeleton-title"></div>
              <div class="skeleton-text"></div>
              <div class="d-flex gap-2 mb-3">
                <div class="skeleton-badge"></div>
                <div class="skeleton-badge"></div>
              </div>
              <div class="skeleton-button"></div>
            </div>
          </div>
        </div>
        <div class="col-md-6 col-lg-4">
          <div class="skeleton-card">
            <div class="skeleton-image"></div>
            <div class="skeleton-content">
              <div class="skeleton-title"></div>
              <div class="skeleton-text"></div>
              <div class="d-flex gap-2 mb-3">
                <div class="skeleton-badge"></div>
                <div class="skeleton-badge"></div>
              </div>
              <div class="skeleton-button"></div>
            </div>
          </div>
        </div>
      </div>

      <div id="facilitiesGrid" class="row g-4"></div>

      <div id="facilitiesNoResults" class="no-results" style="display: none;">
        <i class="bi bi-building"></i>
        <h3>No venues found</h3>
        <p>Try adjusting your search terms</p>
      </div>
    </div>

    <!-- Equipment Section -->
<div id="equipmentSection" class="catalog-section-container">
  <div class="section-header-flex">
<div class="section-title-area">
  <h2>Available Equipment</h2>
  <p class="text-white-50 mt-2">Resources ready for academic activities, events, and campus use.</p>
</div>
<a href="/booking-catalog" class="btn btn-gold px-4 py-2 glass-btn">
  View All Equipment →
</a>
  </div>
      <div id="equipmentSkeleton" class="row g-4" style="display: none;">
        <div class="col-md-6 col-lg-4">
          <div class="skeleton-card">
            <div class="skeleton-image"></div>
            <div class="skeleton-content">
              <div class="skeleton-title"></div>
              <div class="skeleton-text"></div>
              <div class="d-flex justify-content-between mb-3">
                <div class="skeleton-badge" style="width: 80px;"></div>
                <div class="skeleton-badge" style="width: 100px;"></div>
              </div>
              <div class="skeleton-button"></div>
            </div>
          </div>
        </div>
        <div class="col-md-6 col-lg-4">
          <div class="skeleton-card">
            <div class="skeleton-image"></div>
            <div class="skeleton-content">
              <div class="skeleton-title"></div>
              <div class="skeleton-text"></div>
              <div class="d-flex justify-content-between mb-3">
                <div class="skeleton-badge" style="width: 80px;"></div>
                <div class="skeleton-badge" style="width: 100px;"></div>
              </div>
              <div class="skeleton-button"></div>
            </div>
          </div>
        </div>
        <div class="col-md-6 col-lg-4">
          <div class="skeleton-card">
            <div class="skeleton-image"></div>
            <div class="skeleton-content">
              <div class="skeleton-title"></div>
              <div class="skeleton-text"></div>
              <div class="d-flex justify-content-between mb-3">
                <div class="skeleton-badge" style="width: 80px;"></div>
                <div class="skeleton-badge" style="width: 100px;"></div>
              </div>
              <div class="skeleton-button"></div>
            </div>
          </div>
        </div>
      </div>

      <div id="equipmentGrid" class="row g-4"></div>

      <div id="equipmentNoResults" class="no-results" style="display: none;">
        <i class="bi bi-tools"></i>
        <h3>No equipment found</h3>
        <p>Try adjusting your search terms</p>
      </div>
    </div>
  </div>

  <!-- Storage Consent Toast -->
  <div class="toast-container position-fixed bottom-0 start-0 p-3" style="z-index: 3000;">
    <div id="storageConsentToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true"
      data-bs-autohide="false" style="min-width: 350px;">
      <div class="toast-header" style="background-color: var(--cpu-blue); color: white;">
        <i class="bi bi-cookie me-2"></i>
        <strong class="me-auto">We Value Your Privacy</strong>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"
          id="closeToastBtn"></button>
      </div>
      <div class="toast-body p-3" style="background-color: white;">
        <div class="text-center mb-2">
          <i class="bi bi-shield-check" style="font-size: 2rem; color: var(--cpu-blue);"></i>
        </div>
        <p class="mb-2 small">
          We use cookies and your browser's local storage to remember your activity, such as items in your cart or form
          progress. This helps you continue where you left off without losing your selections when you refresh or leave
          the page.
        </p>
        <div class="d-grid mt-2">
          <button type="button" class="btn btn-sm fw-bold" id="acceptStorageBtn"
            style="background-color: var(--cpu-blue); color: white;">
            <i class="bi bi-check-lg me-1"></i>I Understand
          </button>
        </div>
      </div>
    </div>
  </div>
  

  <script>
    // Facilities State
    let facilitiesPage = 1;
    let facilitiesLoading = false;
    let facilitiesTotalPages = 1;

    // Equipment State
    let equipmentPage = 1;
    let equipmentLoading = false;
    let equipmentTotalPages = 1;

    // Animation observer
    const heroSection = document.querySelector(".hero-section");
    if (heroSection) {
      new IntersectionObserver((entries, obs) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add("visible");
            obs.unobserve(entry.target);
          }
        });
      }, { threshold: 0.2 }).observe(heroSection);
    }

    // Load both sections on page load
    document.addEventListener("DOMContentLoaded", () => {
      loadFacilities();
      loadEquipment();
      setupToastConsent();
    });

    function setupToastConsent() {
      const hasSeenStorageToast = localStorage.getItem('storage_consent_seen');
      const toastElement = document.getElementById('storageConsentToast');

      if (!hasSeenStorageToast && toastElement) {
        setTimeout(() => new bootstrap.Toast(toastElement, { autohide: false }).show(), 1500);
      }

      document.getElementById('acceptStorageBtn')?.addEventListener('click', () => {
        localStorage.setItem('storage_consent_seen', 'true');
        bootstrap.Toast.getInstance(toastElement)?.hide();
      });

      document.getElementById('closeToastBtn')?.addEventListener('click', () => {
        localStorage.setItem('storage_consent_seen', 'true');
      });
    }

    // === FACILITIES FUNCTIONS ===
    function loadFacilities() {
      if (facilitiesLoading) return;
      facilitiesLoading = true;

      const skeletonEl = document.getElementById('facilitiesSkeleton');
      const gridEl = document.getElementById('facilitiesGrid');
      const noResultsEl = document.getElementById('facilitiesNoResults');

      skeletonEl.style.display = 'flex';
      gridEl.style.display = 'none';
      noResultsEl.style.display = 'none';

      let url = `/api/parent-facilities?page=${facilitiesPage}&per_page=6`;

      fetch(url, { headers: { 'Accept': 'application/json' } })
        .then(res => res.json())
        .then(res => {
          skeletonEl.style.display = 'none';
          if (res.success && res.data?.length > 0) {
            renderFacilities(res.data);
            gridEl.style.display = 'flex';
          } else {
            noResultsEl.style.display = 'block';
          }
        })
        .catch(() => {
          skeletonEl.style.display = 'none';
          noResultsEl.style.display = 'block';
          document.getElementById('facilitiesResultsInfo').textContent = 'Error loading facilities';
        })
        .finally(() => {
          facilitiesLoading = false;
        });
    }

    function renderFacilities(venues) {
      const grid = document.getElementById('facilitiesGrid');
      const html = venues.map(venue => {
        const imageUrl = venue.images?.[0]?.image_url || 'https://res.cloudinary.com/dn98ntlkd/image/upload/v1759850278/t4fyv56wog6pglhwvwtn.png';
        const hasChildren = venue.has_children || false;
        const locationTypeIcon = venue.location_type === 'Indoors' ? 'bi-building' : 'bi-tree';
        const capacityText = venue.capacity === 1 ? 'Flexible Capacity' : `Up to ${venue.capacity} people`;
        const feeText = venue.base_fee == 0 ? 'Contact for pricing' : `₱${parseFloat(venue.base_fee).toLocaleString()}`;
        const description = venue.description ? venue.description.substring(0, 100) : 'No description available';
        const locationNote = venue.location_note || 'Location TBA';

        return `
          <div class="col-md-6 col-lg-4">
            <div class="card venue-card h-100" data-facility-id="${venue.facility_id}" data-has-children="${hasChildren}">
              <div class="position-relative">
                <img src="${imageUrl}" class="card-img-top" alt="${escapeHtml(venue.facility_name)}" loading="lazy"
                  onerror="this.onerror=null; this.src='/images/placeholder-facility.png'">
                <span class="category-badge"><i class="bi ${locationTypeIcon} me-1"></i>${venue.location_type}</span>
              </div>
              <div class="card-body d-flex flex-column">
                <h5 class="card-title">${escapeHtml(venue.facility_name)}</h5>
                <p class="card-text text-muted small mb-3">${escapeHtml(description)}${venue.description?.length > 100 ? '...' : ''}</p>
                <div class="d-flex flex-wrap gap-2 mb-3">
                  <span class="location-badge" title="${escapeHtml(locationNote)}">
                    <i class="bi bi-geo-alt"></i><span>${escapeHtml(locationNote.substring(0, 25))}</span>
                  </span>
                  <span class="capacity-badge"><i class="bi bi-people"></i>${capacityText}</span>
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
      }).join('');

      grid.innerHTML = html;

      // Event handlers
      grid.querySelectorAll('.venue-card').forEach(card => {
        card.addEventListener('click', (e) => {
          if (e.target.closest('.view-venue-btn')) return;
          const facilityId = card.dataset.facilityId;
          const hasChildren = card.dataset.hasChildren === 'true';
          
          if (hasChildren) {
            window.location.href = `/pick-a-venue?parent_facility_id=${facilityId}`;
          } else {
            window.location.href = `/facility/${facilityId}`;
          }
        });
      });

      grid.querySelectorAll('.view-venue-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
          e.stopPropagation();
          const card = btn.closest('.venue-card');
          const facilityId = card.dataset.facilityId;
          const hasChildren = card.dataset.hasChildren === 'true';
          
          if (hasChildren) {
            window.location.href = `/pick-a-venue?parent_facility_id=${facilityId}`;
          } else {
            window.location.href = `/facility/${facilityId}`;
          }
        });
      });
    }


    // === EQUIPMENT FUNCTIONS ===
    function loadEquipment() {
      if (equipmentLoading) return;
      equipmentLoading = true;

      const skeletonEl = document.getElementById('equipmentSkeleton');
      const gridEl = document.getElementById('equipmentGrid');
      const noResultsEl = document.getElementById('equipmentNoResults');

      skeletonEl.style.display = 'flex';
      gridEl.style.display = 'none';
      noResultsEl.style.display = 'none';

      let url = `/api/equipment?page=${equipmentPage}&per_page=6`;

      fetch(url, { headers: { 'Accept': 'application/json' } })
        .then(res => res.json())
        .then(res => {
          skeletonEl.style.display = 'none';
          if (res.data?.length > 0) {
            renderEquipment(res.data);
            gridEl.style.display = 'flex';
          } else {
            noResultsEl.style.display = 'block';
          }
        })
        .catch(() => {
          skeletonEl.style.display = 'none';
          noResultsEl.style.display = 'block';
          document.getElementById('equipmentResultsInfo').textContent = 'Error loading equipment';
        })
        .finally(() => {
          equipmentLoading = false;
        });
    }

    function renderEquipment(items) {
      const grid = document.getElementById('equipmentGrid');
      const html = items.map(item => {
        const imageUrl = item.images?.[0]?.image_url || 'https://res.cloudinary.com/dn98ntlkd/image/upload/v1759850278/t4fyv56wog6pglhwvwtn.png';
        const availableQty = item.available_quantity || 0;
        const statusText = availableQty > 0 ? 'Available' : 'Out of Stock';
        const statusColor = availableQty > 0 ? 'var(--cpu-gold)' : '#dc3545';

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

      grid.innerHTML = html;

      grid.querySelectorAll('.venue-card').forEach(card => {
        card.addEventListener('click', (e) => {
          if (e.target.closest('.view-equipment-btn')) return;
          const equipmentId = card.dataset.equipmentId;
          window.location.href = `/equipment-details/${equipmentId}`;
        });
      });

      grid.querySelectorAll('.view-equipment-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
          e.stopPropagation();
          const equipmentId = btn.closest('.venue-card').dataset.equipmentId;
          window.location.href = `/equipment-details/${equipmentId}`;
        });
      });
    }


    function escapeHtml(text) {
      if (!text) return '';
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }
  </script>
@endsection
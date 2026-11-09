<?php
/**
 * Manage Facilities View
 *
 * Uses a single merged API endpoint: GET /api/admin/manage-facilities
 * That one call returns paginated facilities AND all filter-dropdown metadata
 * (statuses, categories, parent buildings) — replacing the previous 3 separate
 * bootstrap calls.
 *
 * Server-side pagination: 12 items per page (handled by the API).
 * Filtering (status, category, building) and search are sent as query
 * parameters so the database does the work instead of the browser.
 *
 * The mass-assign-departments API call is intentionally kept separate and
 * is NOT merged into the above endpoint.
 */
?>
@extends('layouts.admin')

@section('title', 'Manage Facilities')

@section('content')
    <link rel="stylesheet" href="{{ asset('css/admin/manage-resources.css') }}" />
    <main id="main">
        <div class="container-fluid px-3 px-md-4">

            {{-- ============ TOP BAR ============ --}}
            <div class="d-flex gap-2 align-items-center mb-3 flex-nowrap mgmt-topbar">
                <button id="filterPanelToggle" class="btn btn-outline-secondary flex-shrink-0" type="button" title="Filters">
                    <i class="bi bi-funnel"></i>
                </button>

                <div class="input-group flex-grow-1 flex-shrink-1 mgmt-search-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" id="searchInput" class="form-control" placeholder="Search facilities...">
                    <button class="btn btn-outline-secondary" type="button" id="clearSearchBtn" style="display:none;">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <div class="d-flex gap-2 flex-shrink-0">
                    <select id="statusFilter" class="form-select mgmt-status-select">
                        <option value="">All Statuses</option>
                    </select>
                    <a href="{{ url('/admin/add-facility') }}" class="btn btn-primary text-nowrap flex-shrink-0"
                        title="Add New">
                        <i class="bi bi-plus-circle-fill me-2"></i><span class="btn-label">Add Facility</span>
                    </a>
                    <button type="button" id="massAssignBtn" class="btn btn-primary text-nowrap flex-shrink-0"
                        title="Mass Assign Departments">
                        <i class="bi bi-diagram-3-fill me-2"></i><span class="btn-label">Assign Departments</span>
                    </button>
                </div>
            </div>

            {{-- ============ TWO-COLUMN ROW ============ --}}
            <div class="mgmt-row">

                {{-- LEFT SIDEBAR --}}
                <aside class="mgmt-sidebar" id="filterPanel">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Categories</h6>
                        <button type="button" id="clearFiltersBtn" class="btn btn-sm btn-link p-0" disabled>Clear</button>
                    </div>

                    <div class="cat-tree" id="categoryTree">
                        <div class="cat-node">
                            <label class="d-block">
                                <input type="radio" name="categoryRadio" value="" class="category-radio" checked>
                                All Categories
                            </label>
                        </div>
                        {{-- JS fills the rest --}}
                    </div>
                </aside>

                <div class="mgmt-sidebar-backdrop" id="filterPanelBackdrop"></div>

                {{-- RIGHT MAIN --}}
                <section class="mgmt-main" id="mainColumn">

                    {{-- Accordion view --}}
                    <div id="accordionView">

                        {{-- Venues --}}
                        <div class="section-header" data-section="venues">
                            <span><i class="bi bi-chevron-down me-2 toggle-icon"></i>Standalone Venues</span>
                            <span class="count" id="venuesCount">0 items</span>
                        </div>
                        <div class="section-body open" id="venuesBody">
                            <div class="inner" id="venuesSection"></div>
                        </div>

                        {{-- Buildings --}}
                        <div class="section-header" data-section="buildings">
                            <span>
                                <i class="bi bi-chevron-down toggle-icon"></i>Buildings
                                <span class="section-hint">(with rooms)</span>
                            </span>
                            <span class="count" id="buildingsCount">0 items</span>
                        </div>
                        <div class="section-body open" id="buildingsBody">
                            <div class="inner" id="buildingsSection"></div>
                        </div>

                    </div>

                    {{-- Results overlay (used for search AND filters) --}}
                    <div id="resultsView">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0 text-muted" id="resultsHeader">Results</h6>
                        </div>
                        <div id="resultsList"></div>
                    </div>

                    {{-- Loading --}}
                    <div id="loadingOverlay">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2 text-muted mb-0">Loading facilities...</p>
                        </div>
                    </div>

                </section>
            </div>

            {{-- Modals (must be <template>-wrapped partials per Block A) --}}
            @include('partials.resource-management.delete-confirmation-modal', [
                'resourceLabel' => 'Facility',
            ])
            @include('partials.resource-management.mass-assign-departments-modal', [
                'resourceLabel' => 'Facility',
            ])
            @include('partials.resource-management.assignment-warning-modal', [
                'resourceLabel' => 'Facility',
            ])

        </div>
    </main>
@endsection

@section('scripts')
    <script src="{{ asset('js/admin/toast.js') }}"></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {

            const token = localStorage.getItem("adminToken");
            if (!token) return window.location.href = "/admin/login";

            // ==================== DOM ====================
            const $ = (id) => document.getElementById(id);
            const els = {
                filterPanel: $('filterPanel'),
                filterPanelToggle: $('filterPanelToggle'),
                filterPanelBackdrop: $('filterPanelBackdrop'),
                searchInput: $('searchInput'),
                clearSearchBtn: $('clearSearchBtn'),
                clearFiltersBtn: $('clearFiltersBtn'),
                statusFilter: $('statusFilter'),
                categoryTree: $('categoryTree'),
                accordionView: $('accordionView'),
                venuesSection: $('venuesSection'),
                buildingsSection: $('buildingsSection'),
                venuesCount: $('venuesCount'),
                buildingsCount: $('buildingsCount'),
                venuesBody: $('venuesBody'),
                buildingsBody: $('buildingsBody'),
                resultsView: $('resultsView'),
                resultsHeader: $('resultsHeader'),
                resultsList: $('resultsList'),
                loadingOverlay: $('loadingOverlay'),
            };

            // ==================== STATE ====================
            const state = {
                filtersLoaded: false,
                categoriesById: {},
                selectedCategoryId: null,
                selectedSubcategoryId: null,
                childCache: {},
                searchTimer: null,
                searchActive: false,
                currentDeleteId: null,
                pendingAssignment: null,
                sectionState: {
                    venues: {
                        loaded: false,
                        currentPage: 0,
                        lastPage: 1
                    },
                    buildings: {
                        loaded: false,
                        currentPage: 0,
                        lastPage: 1
                    },
                },
            };

            // ==================== API ====================
            const apiFetch = async (url, options = {}) => {
                const res = await fetch(url, {
                    ...options,
                    headers: {
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`,
                        ...(options.body && !(options.body instanceof FormData) ? {
                            'Content-Type': 'application/json'
                        } : {}),
                        ...(options.headers || {}),
                    },
                });
                if (res.status === 401) {
                    localStorage.removeItem("adminToken");
                    alert("Your session has expired. Please log in again.");
                    window.location.href = "/admin/login";
                    throw new Error("Unauthorized");
                }
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                return res.json();
            };

            const showLoading = () => els.loadingOverlay.style.display = 'flex';
            const hideLoading = () => els.loadingOverlay.style.display = 'none';

            // ==================== HELPERS ====================
            const escapeHtml = (s) => s === null || s === undefined ? '' : String(s)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;').replace(/'/g, '&#39;');

            const typeBadge = (t) => ({
                building: '<span class="badge bg-primary badge-type">Building</span>',
                venue: '<span class="badge bg-info badge-type">Venue</span>',
                room: '<span class="badge bg-secondary badge-type">Room</span>',
            } [t] || '');

            const FALLBACK_IMG =
                "https://res.cloudinary.com/dn98ntlkd/image/upload/v1759850278/t4fyv56wog6pglhwvwtn.png";
            const imgFor = (r) => r.primary_image_url || FALLBACK_IMG;

            const hasActiveFilters = () =>
                !!els.statusFilter.value || !!state.selectedCategoryId || !!state.selectedSubcategoryId;

            // ==================== FILTER QUERY ====================
            const buildFilterQuery = () => {
                const p = new URLSearchParams();
                if (els.statusFilter.value) p.set('status_id', els.statusFilter.value);
                if (state.selectedCategoryId) p.set('category_id', state.selectedCategoryId);
                if (state.selectedSubcategoryId) p.set('subcategory_id', state.selectedSubcategoryId);
                return p.toString();
            };

            // ==================== LAZY MODAL LOADER ====================
            // Clones <template> into body on first use. Subsequent calls are no-ops.
            const ensureModal = (templateId, modalId) => {
                if (document.getElementById(modalId)) return true;
                const tpl = document.getElementById(templateId);
                if (!tpl) {
                    console.error(`[ensureModal] template not found: #${templateId}`);
                    return false;
                }
                document.body.appendChild(tpl.content.cloneNode(true));
                return true;
            };

            // ==================== CATEGORY TREE ====================
            const renderCategoryTree = (categories) => {
                state.categoriesById = {};
                const rows = categories.map(c => {
                    state.categoriesById[c.category_id] = c;
                    const subs = (c.subcategories || []).map(s => `
          <div class="sub-node">
            <label class="d-block">
              <input type="radio" name="subcategoryRadio" value="${s.subcategory_id}"
                     data-parent-category="${c.category_id}" class="subcategory-radio">
              ${escapeHtml(s.subcategory_name)}
            </label>
          </div>`).join('');
                    return `
          <div class="cat-node" data-category-id="${c.category_id}">
            <label class="d-block">
              <input type="radio" name="categoryRadio" value="${c.category_id}" class="category-radio">
              ${escapeHtml(c.category_name)}
            </label>
            <div class="sub-list">${subs}</div>
          </div>`;
                }).join('');
                els.categoryTree.innerHTML = `
        <div class="cat-node">
          <label class="d-block">
            <input type="radio" name="categoryRadio" value="" class="category-radio" checked>
            All Categories
          </label>
        </div>${rows}`;
                wireCategoryEvents();
            };

            const wireCategoryEvents = () => {
                els.categoryTree.querySelectorAll('.category-radio').forEach(r => {
                    r.addEventListener('change', () => {
                        if (!r.checked) return;
                        state.selectedCategoryId = r.value ? parseInt(r.value) : null;
                        state.selectedSubcategoryId = null;
                        els.categoryTree.querySelectorAll('.subcategory-radio').forEach(s => s
                            .checked = false);
                        refreshView();
                    });
                });
                els.categoryTree.querySelectorAll('.subcategory-radio').forEach(r => {
                    r.addEventListener('change', () => {
                        if (!r.checked) return;
                        state.selectedSubcategoryId = parseInt(r.value);
                        state.selectedCategoryId = parseInt(r.dataset.parentCategory);
                        const parentRadio = els.categoryTree.querySelector(
                            `.category-radio[value="${state.selectedCategoryId}"]`);
                        if (parentRadio) parentRadio.checked = true;
                        refreshView();
                    });
                });
            };

            const resetFilters = () => {
                state.selectedCategoryId = null;
                state.selectedSubcategoryId = null;
                els.statusFilter.value = '';
                els.categoryTree.querySelectorAll('input[type="radio"]').forEach(r => r.checked = r.value ===
                    '');
                els.clearFiltersBtn.disabled = true;
            };

            // ==================== NODE RENDER ====================
            const renderNode = (row) => {
                const statusBadge = row.status_name ?
                    `<span class="badge" style="background-color:${escapeHtml(row.status_color_code || '#6c757d')}; color:#fff;">${escapeHtml(row.status_name)}</span>` :
                    '';
                const childCount = row.building_type === 'building' ?
                    ` · <strong>${row.children_count} room${row.children_count === 1 ? '' : 's'}</strong>` : '';
                const codeTag = row.facility_code ?
                    ` <span class="text-muted small">(${escapeHtml(row.facility_code)})</span>` : '';

                return `
        <div class="node" data-facility-id="${row.facility_id}" data-type="${row.building_type}">
          <img src="${imgFor(row)}" class="node-img ${row.building_type === 'room' ? 'sm' : ''}"
               onerror="this.src='${FALLBACK_IMG}'" alt="${escapeHtml(row.facility_name)}">
          <div class="node-body">
            <p class="node-title">${escapeHtml(row.facility_name)}${codeTag}</p>
            <p class="node-meta">
              <i class="bi bi-tag-fill me-1"></i>${escapeHtml(row.category_name)}
              ${row.subcategory_name ? ' · ' + escapeHtml(row.subcategory_name) : ''}
              ${childCount}
            </p>
            ${row.base_fee !== null && row.base_fee !== undefined
              ? `<p class="node-meta mb-0">
                                                       <i class="bi bi-cash-stack me-1"></i>
                                                       ₱${parseFloat(row.base_fee).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                       <span class="text-muted">(${escapeHtml(row.rate_type || '')})</span>
                                                     </p>`
              : ''}
            <div class="mt-1">${statusBadge} ${typeBadge(row.building_type)}</div>
          </div>
          <div class="node-actions">
            <a href="/admin/edit-facility?id=${row.facility_id}" class="btn btn-sm btn-primary">Manage</a>
            <button class="btn btn-sm btn-danger btn-delete" data-id="${row.facility_id}">Delete</button>
            ${row.building_type === 'building'
              ? `<button class="btn btn-sm btn-outline-secondary btn-expand"
                                                             data-id="${row.facility_id}" title="Expand"><i class="bi bi-chevron-down"></i></button>`
              : ''}
          </div>
        </div>
${row.building_type === 'building'
  ? `<div class="children-wrap" data-children-for="${row.facility_id}"><div class="inner"></div></div>`
  : ''}
      `;
            };

            const wireNodeEvents = (root) => {
                root.querySelectorAll('.btn-delete').forEach(b =>
                    b.addEventListener('click', () => openDeleteModal(b.dataset.id)));
                root.querySelectorAll('.btn-expand').forEach(b =>
                    b.addEventListener('click', () => toggleBuilding(b.dataset.id, b)));
            };

            // ==================== ACCORDION RENDER ====================
            const loadMoreHtml = (section) => {
                const s = state.sectionState[section];
                if (!s || s.currentPage >= s.lastPage) return '';
                return `
    <div class="text-center my-2">
      <button class="btn btn-sm btn-outline-secondary btn-load-more-section" data-section="${section}">
        <i class="bi bi-arrow-down-circle me-1"></i>
        Load more ${section === 'venues' ? 'venues' : 'buildings'}
        (${s.loadedCount}/${s.total})
      </button>
    </div>`;
            };

            const renderAccordion = (data) => {
                const venues = data.venues || [];
                const buildings = data.buildings || [];
                const venuesTotal = data.venues_total ?? venues.length;
                const buildingsTotal = data.buildings_total ?? buildings.length;

                const PER_PAGE = 10;
                state.sectionState.venues = {
                    loaded: true,
                    currentPage: Math.ceil(venues.length / PER_PAGE) || 1,
                    lastPage: Math.max(1, Math.ceil(venuesTotal / PER_PAGE)),
                    loadedCount: venues.length,
                    total: venuesTotal,
                };
                state.sectionState.buildings = {
                    loaded: true,
                    currentPage: Math.ceil(buildings.length / PER_PAGE) || 1,
                    lastPage: Math.max(1, Math.ceil(buildingsTotal / PER_PAGE)),
                    loadedCount: buildings.length,
                    total: buildingsTotal,
                };

                els.venuesSection.innerHTML = venues.length ?
                    venues.map(renderNode).join('') + loadMoreHtml('venues') :
                    '<p class="text-muted small mb-0 p-2">No standalone venues match your filters.</p>';

                els.buildingsSection.innerHTML = buildings.length ?
                    buildings.map(renderNode).join('') + loadMoreHtml('buildings') :
                    '<p class="text-muted small mb-0 p-2">No buildings match your filters.</p>';

                const fmt = (shown, total) =>
                    shown < total ? `${shown} of ${total} items` : `${total} item${total === 1 ? '' : 's'}`;
                els.venuesCount.textContent = fmt(venues.length, venuesTotal);
                els.buildingsCount.textContent = fmt(buildings.length, buildingsTotal);

                wireNodeEvents(els.venuesSection);
                wireNodeEvents(els.buildingsSection);
                wireLoadMore(els.venuesSection, 'venues');
                wireLoadMore(els.buildingsSection, 'buildings');
            };

            const loadMoreSection = async (section) => {
                const s = state.sectionState[section];
                if (!s || s.currentPage >= s.lastPage) return;

                const btn = document.querySelector(`.btn-load-more-section[data-section="${section}"]`);
                if (btn) btn.disabled = true;

                const p = new URLSearchParams({
                    section,
                    page: s.currentPage + 1
                });
                if (els.statusFilter.value) p.set('status_id', els.statusFilter.value);
                if (state.selectedCategoryId) p.set('category_id', state.selectedCategoryId);
                if (state.selectedSubcategoryId) p.set('subcategory_id', state.selectedSubcategoryId);

                try {
                    const res = await apiFetch(`/api/admin/manage-facilities/section?${p.toString()}`);
                    const rows = res.data || [];
                    const target = section === 'venues' ? els.venuesSection : els.buildingsSection;

                    target.querySelectorAll('.btn-load-more-section').forEach(b => b.parentElement
                        .remove());
                    target.insertAdjacentHTML('beforeend', rows.map(renderNode).join(''));

                    s.currentPage = res.pagination.current_page;
                    s.lastPage = res.pagination.last_page;
                    s.loadedCount += rows.length;

                    wireNodeEvents(target);
                    target.insertAdjacentHTML('beforeend', loadMoreHtml(section));
                    wireLoadMore(target, section);

                    const fmt = (shown, total) =>
                        shown < total ? `${shown} of ${total} items` :
                        `${total} item${total === 1 ? '' : 's'}`;
                    if (section === 'venues') els.venuesCount.textContent = fmt(s.loadedCount, s.total);
                    else els.buildingsCount.textContent = fmt(s.loadedCount, s.total);
                } catch (e) {
                    console.error(e);
                    if (typeof showToast === 'function') showToast('Failed to load more', 'error');
                    if (btn) btn.disabled = false;
                }
            };

            const wireLoadMore = (root, section) => {
                root.querySelectorAll(`.btn-load-more-section[data-section="${section}"]`).forEach(btn => {
                    btn.addEventListener('click', () => loadMoreSection(section));
                });
            };

            // ==================== SECTION COLLAPSE (accordion headers) ====================
            document.querySelectorAll('.section-header').forEach(header => {
                header.addEventListener('click', () => {
                    const section = header.dataset.section;
                    const body = section === 'venues' ? els.venuesBody : els.buildingsBody;
                    body.classList.toggle('open');
                    header.classList.toggle('closed');
                });
            });

            // ==================== LAZY LOAD CHILDREN ====================
            const loadChildrenPage = async (buildingId, page) => {
                const p = new URLSearchParams({
                    page
                });
                if (els.statusFilter.value) p.set('status_id', els.statusFilter.value);
                if (state.selectedSubcategoryId) p.set('subcategory_id', state.selectedSubcategoryId);
                return apiFetch(`/api/admin/facilities/${buildingId}/children?${p.toString()}`);
            };

            const renderChildren = (wrap, buildingId, res, append = false) => {
                if (!wrap) return;
                const inner = wrap.querySelector('.inner');

                const cache = state.childCache[buildingId] ||= {
                    rows: [],
                    currentPage: 0,
                    lastPage: 1
                };
                if (append) cache.rows.push(...(res.data || []));
                else cache.rows = res.data || [];
                cache.currentPage = res.pagination.current_page;
                cache.lastPage = res.pagination.last_page;
                cache.total = res.pagination.total;

                const nodesHtml = cache.rows.map(renderNode).join('');
                const lmHtml = cache.currentPage < cache.lastPage ?
                    `<button class="btn btn-sm btn-outline-secondary btn-load-more my-2" data-id="${buildingId}">
                         <i class="bi bi-arrow-down-circle me-1"></i>Load more rooms (${cache.rows.length}/${res.pagination.total})
                       </button>` :
                    '';

                inner.innerHTML = (cache.rows.length ?
                    nodesHtml :
                    '<p class="text-muted small mb-2 p-2">No rooms match your filters.</p>') + lmHtml;

                wrap.classList.add('open');
                wireNodeEvents(inner);

                const lm = inner.querySelector('.btn-load-more');
                if (lm) lm.addEventListener('click', async () => {
                    lm.disabled = true;
                    try {
                        const next = await loadChildrenPage(buildingId, cache.currentPage + 1);
                        renderChildren(root, buildingId, next, true);
                    } catch (e) {
                        console.error(e);
                        if (typeof showToast === 'function') showToast('Failed to load more rooms',
                            'error');
                        lm.disabled = false;
                    }
                });
            };

            const toggleBuilding = async (buildingId, btn) => {
                const nodeEl = btn.closest('.node');
                const wrap = nodeEl?.nextElementSibling;
                if (!wrap || !wrap.matches(`[data-children-for="${buildingId}"]`)) return;

                const isOpen = wrap.classList.contains('open');
                if (isOpen) {
                    wrap.classList.remove('open');
                    btn.classList.remove('expanded');
                    return;
                }

                wrap.classList.add('open');
                btn.classList.add('expanded');

                // If cached, paint from cache into THIS wrap (no refetch)
                const cache = state.childCache[buildingId];
                if (cache?.rows.length) {
                    renderChildrenFromCache(wrap, buildingId, cache);
                    return;
                }

                const inner = wrap.querySelector('.inner');
                inner.innerHTML = '<p class="text-muted small mb-2 p-2">Loading rooms...</p>';
                try {
                    const res = await loadChildrenPage(buildingId, 1);
                    renderChildren(wrap, buildingId, res, false);
                } catch (e) {
                    console.error(e);
                    inner.innerHTML = '<p class="text-danger small mb-2 p-2">Failed to load rooms.</p>';
                }
            };

            const renderChildrenFromCache = (wrap, buildingId, cache) => {
                const inner = wrap.querySelector('.inner');
                if (!inner) return;

                const nodesHtml = cache.rows.map(renderNode).join('');
                const lmHtml = cache.currentPage < cache.lastPage ?
                    `<button class="btn btn-sm btn-outline-secondary btn-load-more my-2" data-id="${buildingId}">
             <i class="bi bi-arrow-down-circle me-1"></i>Load more rooms (${cache.rows.length}/${cache.lastPage * 10})
           </button>` :
                    '';

                inner.innerHTML = (cache.rows.length ?
                    nodesHtml :
                    '<p class="text-muted small mb-2 p-2">No rooms match your filters.</p>') + lmHtml;

                wireNodeEvents(inner);

                const lm = inner.querySelector('.btn-load-more');
                if (lm) lm.addEventListener('click', async () => {
                    lm.disabled = true;
                    try {
                        const next = await loadChildrenPage(buildingId, cache.currentPage + 1);
                        renderChildren(wrap, buildingId, next, true);
                    } catch (e) {
                        console.error(e);
                        if (typeof showToast === 'function') showToast('Failed to load more rooms',
                            'error');
                        lm.disabled = false;
                    }
                });
            };

            // ==================== RESULTS OVERLAY RENDERER ====================
            const renderResults = (rows, headerText, emptyText) => {
                els.accordionView.classList.add('hidden');
                els.resultsView.classList.add('active');
                els.resultsHeader.textContent = headerText;

                if (!rows.length) {
                    els.resultsList.innerHTML = `
              <div class="text-center py-5 text-muted">
                <i class="bi bi-search fs-1"></i>
                <p class="mt-2 mb-0">${escapeHtml(emptyText)}</p>
              </div>`;
                    return;
                }
                els.resultsList.innerHTML = rows.map(renderNode).join('');
                wireNodeEvents(els.resultsList);
            };

            const exitResults = () => {
                els.resultsView.classList.remove('active');
                els.accordionView.classList.remove('hidden');
            };

            // ==================== FETCHERS ====================
            const fetchIndexForAccordion = async (populate = false) => {
                showLoading();
                try {
                    const res = await apiFetch('/api/admin/manage-facilities');
                    if (populate && !state.filtersLoaded) {
                        els.statusFilter.innerHTML = '<option value="">All Statuses</option>';
                        (res.filters.statuses || []).forEach(s => {
                            els.statusFilter.insertAdjacentHTML('beforeend',
                                `<option value="${s.status_id}">${escapeHtml(s.status_name)}</option>`
                            );
                        });
                        renderCategoryTree(res.filters.categories || []);
                        state.filtersLoaded = true;
                    }
                    exitResults();
                    renderAccordion(res.data);
                } catch (e) {
                    console.error(e);
                    els.venuesSection.innerHTML = '<p class="text-danger small p-2">Failed to load.</p>';
                    els.buildingsSection.innerHTML = '';
                } finally {
                    hideLoading();
                }
            };

            const fetchFilteredResults = async () => {
                showLoading();
                try {
                    const q = buildFilterQuery();
                    const res = await apiFetch(`/api/admin/manage-facilities?${q}`);
                    const rows = [...(res.data.venues || []), ...(res.data.buildings || [])];
                    renderResults(rows,
                        `Filtered Results — ${rows.length} match${rows.length === 1 ? '' : 'es'}`,
                        'No facility matches the selected filters.');
                } catch (e) {
                    console.error(e);
                    if (typeof showToast === 'function') showToast('Failed to load results', 'error');
                } finally {
                    hideLoading();
                }
            };

            const runSearch = async (q) => {
                showLoading();
                try {
                    const res = await apiFetch(
                        `/api/admin/manage-facilities/search?q=${encodeURIComponent(q)}`);
                    const rows = res.results || [];
                    renderResults(rows,
                        `Search Results — ${rows.length} match${rows.length === 1 ? '' : 'es'} for "${q}"`,
                        'No facility found.');
                } catch (e) {
                    console.error(e);
                    if (typeof showToast === 'function') showToast('Search failed', 'error');
                } finally {
                    hideLoading();
                }
            };

            // ==================== VIEW REFRESH ====================
            const refreshView = () => {
                const hasSearch = !!els.searchInput.value.trim();
                if (hasSearch) return;
                if (hasActiveFilters()) {
                    els.clearFiltersBtn.disabled = false;
                    fetchFilteredResults();
                } else {
                    els.clearFiltersBtn.disabled = true;
                    exitResults();
                }
            };

            // ==================== EVENT WIRES ====================
            els.statusFilter.addEventListener('change', refreshView);

            els.searchInput.addEventListener('input', () => {
                clearTimeout(state.searchTimer);
                const q = els.searchInput.value.trim();
                if (!q) {
                    els.clearSearchBtn.style.display = 'none';
                    refreshView();
                    return;
                }
                els.clearSearchBtn.style.display = 'block';
                state.searchTimer = setTimeout(() => runSearch(q), 350);
            });

            els.searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    els.searchInput.value = '';
                    els.clearSearchBtn.style.display = 'none';
                    refreshView();
                }
            });

            els.clearSearchBtn.addEventListener('click', () => {
                els.searchInput.value = '';
                els.clearSearchBtn.style.display = 'none';
                refreshView();
            });

            els.clearFiltersBtn.addEventListener('click', () => {
                resetFilters();
                refreshView();
            });

            els.filterPanelToggle.addEventListener('click', () => {
                els.filterPanel.classList.add('show');
                els.filterPanelBackdrop.classList.add('show');
            });
            els.filterPanelBackdrop.addEventListener('click', () => {
                els.filterPanel.classList.remove('show');
                els.filterPanelBackdrop.classList.remove('show');
            });

            // ==================== DELETE (lazy) ====================
            const openDeleteModal = (id) => {
                state.currentDeleteId = id;
                ensureModal('deleteModalTemplate', 'deleteConfirmationModal');

                if (!state._deleteWired) {
                    document.getElementById('confirmDeleteBtn')?.addEventListener('click', async () => {
                        if (!state.currentDeleteId) return;
                        const btn = document.getElementById('confirmDeleteBtn');
                        btn.disabled = true;
                        try {
                            await apiFetch(`/api/admin/facilities/${state.currentDeleteId}`, {
                                method: 'DELETE'
                            });
                            if (typeof showToast === 'function') showToast(
                                'Facility deleted successfully', 'success');
                            delete state.childCache[state.currentDeleteId];
                            bootstrap.Modal.getInstance(document.getElementById(
                                'deleteConfirmationModal'))?.hide();
                            if (hasActiveFilters()) fetchFilteredResults();
                            else fetchIndexForAccordion();
                        } catch (e) {
                            console.error(e);
                            if (typeof showToast === 'function') showToast(
                                'Failed to delete facility', 'error');
                        } finally {
                            btn.disabled = false;
                            state.currentDeleteId = null;
                        }
                    });
                    state._deleteWired = true;
                }

                const el = document.getElementById('deleteConfirmationModal');
                if (el) new bootstrap.Modal(el).show();
            };

            // ==================== MASS ASSIGN STATE ====================
            const maState = {
                page: 1,
                lastPage: 1,
                total: 0,
                search: '',
                currentRows: [],
                selected: new Map(),
                searchTimer: null,
                departmentsLoaded: false,
                pendingAssignment: null,
                wired: false,
            };

            const maFetchPage = async (page = 1) => {
                const p = new URLSearchParams({
                    page
                });
                if (maState.search) p.set('search', maState.search);
                return apiFetch(`/api/facilities/dropdown?${p.toString()}`);
            };

            const maRenderCheckboxList = () => {
                const list = document.getElementById('massAssignCheckboxList');
                if (!list) return;

                if (!maState.currentRows.length) {
                    list.innerHTML = '<p class="text-muted small mb-0 p-2">No facilities found.</p>';
                    return;
                }

                list.innerHTML = maState.currentRows.map(row => {
                    const checked = maState.selected.has(row.facility_id) ? 'checked' : '';
                    const code = row.facility_code ?
                        `<span class="text-muted small">(${escapeHtml(row.facility_code)})</span>` : '';
                    return `
      <div class="form-check py-1 border-bottom">
        <input class="form-check-input ma-facility-checkbox" type="checkbox"
               value="${row.facility_id}" id="ma-fac-${row.facility_id}" ${checked}>
        <label class="form-check-label small" for="ma-fac-${row.facility_id}">
          ${escapeHtml(row.facility_name)} ${code}
        </label>
      </div>`;
                }).join('');

                list.querySelectorAll('.ma-facility-checkbox').forEach(cb => {
                    cb.addEventListener('change', () => {
                        const id = parseInt(cb.value);
                        const row = maState.currentRows.find(r => r.facility_id === id);
                        if (!row) return;
                        if (cb.checked) maState.selected.set(id, {
                            name: row.facility_name,
                            code: row.facility_code
                        });
                        else maState.selected.delete(id);
                        maRenderSelectedList();
                    });
                });
            };

            const maRenderSelectedList = () => {
                const list = document.getElementById('massAssignSelectedList');
                const count = document.getElementById('massAssignSelectedCount');
                if (!list) return;

                count.textContent = maState.selected.size;

                if (maState.selected.size === 0) {
                    list.innerHTML = '<p class="text-muted small mb-0">No facilities selected yet.</p>';
                    return;
                }

                list.innerHTML = Array.from(maState.selected.entries()).map(([id, info]) => {
                    const code = info.code ?
                        ` <span class="text-muted small">(${escapeHtml(info.code)})</span>` : '';
                    return `
      <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
        <span class="small text-truncate" style="max-width: 85%;">
          ${escapeHtml(info.name)}${code}
        </span>
<button type="button" class="btn btn-sm btn-link text-danger p-0 ma-remove-selected"
        data-id="${id}" title="Remove" style="line-height: 1;">
  <i class="bi bi-x-circle-fill" style="color: #dc3545;"></i>
</button>
      </div>`;
                }).join('');

                list.querySelectorAll('.ma-remove-selected').forEach(btn => {
                    btn.addEventListener('click', () => {
                        maState.selected.delete(parseInt(btn.dataset.id));
                        maRenderSelectedList();
                        maRenderCheckboxList();
                    });
                });
            };

            const maUpdatePaginationUI = () => {
                const indicator = document.getElementById('massAssignPageIndicator');
                const totalCount = document.getElementById('massAssignTotalCount');
                const prev = document.getElementById('massAssignPrev');
                const next = document.getElementById('massAssignNext');
                if (indicator) indicator.textContent = `${maState.page} / ${maState.lastPage}`;
                if (totalCount) totalCount.textContent =
                    `${maState.total} item${maState.total === 1 ? '' : 's'}`;
                if (prev) prev.disabled = maState.page <= 1;
                if (next) next.disabled = maState.page >= maState.lastPage;
            };

            const maLoadPage = async (page) => {
                const res = await maFetchPage(page);
                const rows = Array.isArray(res.data) ? res.data : (res.data || []);
                maState.currentRows = rows;
                maState.page = res.current_page || 1;
                maState.lastPage = res.last_page || 1;
                maState.total = res.total || rows.length;
                maRenderCheckboxList();
                maUpdatePaginationUI();
            };

            const maLoadDepartments = async () => {
                if (maState.departmentsLoaded) return;
                try {
                    const res = await apiFetch("/api/departments");
                    const list = Array.isArray(res) ? res : (res.data || []);
                    const sel = document.getElementById('departmentSingleSelect');
                    if (sel) sel.innerHTML = '<option value="">Select a department...</option>' +
                        list.map(d =>
                            `<option value="${d.department_id}">${escapeHtml(d.department_name)}</option>`)
                        .join('');
                    maState.departmentsLoaded = true;
                } catch (e) {
                    console.error(e);
                    if (typeof showToast === 'function') showToast('Failed to load departments', 'error');
                }
            };

            const wireMassAssignControls = () => {
                const searchInput = document.getElementById('massAssignSearch');
                const searchClear = document.getElementById('massAssignSearchClear');
                const prev = document.getElementById('massAssignPrev');
                const next = document.getElementById('massAssignNext');
                const selectPage = document.getElementById('massAssignSelectPage');
                const clearAll = document.getElementById('massAssignClearAll');
                const deptSel = document.getElementById('departmentSingleSelect');
                const execBtn = document.getElementById('executeMassAssignBtn');
                const confirmBtn = document.getElementById('confirmAssignBtn');
                if (!searchInput || !execBtn || !confirmBtn) {
                    console.error('[mass-assign] modal elements not found after template clone');
                    return;
                }

                searchInput.addEventListener('input', () => {
                    clearTimeout(maState.searchTimer);
                    const q = searchInput.value.trim();
                    searchClear.style.display = q ? 'block' : 'none';
                    maState.searchTimer = setTimeout(async () => {
                        maState.search = q;
                        await maLoadPage(1);
                    }, 300);
                });
                searchInput.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') {
                        searchInput.value = '';
                        searchClear.style.display = 'none';
                        maState.search = '';
                        maLoadPage(1);
                    }
                });
                searchClear.addEventListener('click', () => {
                    searchInput.value = '';
                    searchClear.style.display = 'none';
                    maState.search = '';
                    maLoadPage(1);
                });

                prev.addEventListener('click', () => {
                    if (maState.page > 1) maLoadPage(maState.page - 1);
                });
                next.addEventListener('click', () => {
                    if (maState.page < maState.lastPage) maLoadPage(maState.page + 1);
                });

                selectPage.addEventListener('click', () => {
                    maState.currentRows.forEach(r =>
                        maState.selected.set(r.facility_id, {
                            name: r.facility_name,
                            code: r.facility_code
                        }));
                    maRenderCheckboxList();
                    maRenderSelectedList();
                });

                clearAll.addEventListener('click', () => {
                    maState.selected.clear();
                    maRenderCheckboxList();
                    maRenderSelectedList();
                });

                execBtn.addEventListener('click', () => {
                    const deptId = deptSel?.value;
                    if (maState.selected.size === 0) return showToast(
                        'Please select at least one facility', 'error');
                    if (!deptId) return showToast('Please select a department', 'error');

                    maState.pendingAssignment = {
                        ids: Array.from(maState.selected.keys()),
                        deptId: parseInt(deptId),
                        deptName: deptSel.options[deptSel.selectedIndex].text,
                    };

                    ensureModal('assignmentWarningTemplate', 'assignmentWarningModal');
                    const w = document.getElementById('assignmentWarningModal');
                    if (w) {
                        // Set z-index BEFORE show() — this doesn't interfere with the fade animation.
                        const massAssignEl = document.getElementById('massAssignDepartmentsModal');
                        const massAssignZ = parseInt(massAssignEl?.style.zIndex) || 1055;
                        w.style.zIndex = String(massAssignZ + 100);

                        const modal = bootstrap.Modal.getOrCreateInstance(w);
                        modal.show();

                        // Raise the *new* backdrop once Bootstrap has appended it, without blocking the animation.
                        setTimeout(() => {
                            const backdrops = document.querySelectorAll('.modal-backdrop');
                            if (backdrops.length) {
                                backdrops[backdrops.length - 1].style.zIndex = String(
                                    massAssignZ + 50);
                            }
                        }, 0);
                    }
                });

                confirmBtn.addEventListener('click', async () => {
                    if (!maState.pendingAssignment) return;
                    const btn = confirmBtn;
                    const orig = btn.innerHTML;
                    btn.disabled = true;
                    btn.innerHTML =
                        '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

                    try {
                        await apiFetch('/api/admin/facilities/mass-assign-department', {
                            method: 'POST',
                            body: JSON.stringify({
                                facility_ids: maState.pendingAssignment.ids,
                                department_id: maState.pendingAssignment.deptId,
                            }),
                        });
                        showToast(
                            `Assigned ${maState.pendingAssignment.ids.length} facility/facilities to ${maState.pendingAssignment.deptName}`,
                            'success');
                        bootstrap.Modal.getInstance(document.getElementById(
                            'assignmentWarningModal'))?.hide();
                        bootstrap.Modal.getInstance(document.getElementById(
                            'massAssignDepartmentsModal'))?.hide();
                        maState.selected.clear();
                        state.childCache = {};
                        if (hasActiveFilters()) fetchFilteredResults();
                        else fetchIndexForAccordion();
                    } catch (e) {
                        console.error(e);
                        showToast('Failed: ' + e.message, 'error');
                    } finally {
                        btn.disabled = false;
                        btn.innerHTML = orig;
                        maState.pendingAssignment = null;
                    }
                });
            };

            const openMassAssignModal = async () => {
                ensureModal('massAssignModalTemplate', 'massAssignDepartmentsModal');
                if (!document.getElementById('massAssignDepartmentsModal')) {
                    showToast('Failed to open modal', 'error');
                    return;
                }

                maState.page = 1;
                maState.search = '';
                maState.currentRows = [];
                maState.selected.clear();
                maState.pendingAssignment = null;

                const searchInput = document.getElementById('massAssignSearch');
                const searchClear = document.getElementById('massAssignSearchClear');
                const deptSel = document.getElementById('departmentSingleSelect');
                const selList = document.getElementById('massAssignSelectedList');
                const selCount = document.getElementById('massAssignSelectedCount');
                const summary = document.getElementById('summaryText');

                if (searchInput) searchInput.value = '';
                if (searchClear) searchClear.style.display = 'none';
                if (deptSel) deptSel.value = '';
                if (selList) selList.innerHTML =
                    '<p class="text-muted small mb-0">No facilities selected yet.</p>';
                if (selCount) selCount.textContent = '0';
                if (summary) summary.textContent = 'Please select at least one facility.';

                if (!maState.wired) {
                    wireMassAssignControls();
                    maState.wired = true;
                }

                await Promise.all([maLoadPage(1), maLoadDepartments()]);

                new bootstrap.Modal(document.getElementById('massAssignDepartmentsModal')).show();
            };

            document.getElementById('massAssignBtn').addEventListener('click', openMassAssignModal);

            // ==================== BOOT ====================
            fetchIndexForAccordion(true);
        });
    </script>
@endsection

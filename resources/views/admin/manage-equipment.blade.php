@extends('layouts.admin')

@section('title', 'Manage Equipment')

@section('content')

    <main id="main">
        <link rel="stylesheet" href="{{ asset('css/admin/manage-resources.css') }}" />
        <div class="container-fluid px-3 px-md-4">

            {{-- ============ TOP BAR ============ --}}
            <div class="d-flex gap-2 align-items-center mb-3 flex-nowrap mgmt-topbar">
                <button id="filterPanelToggle" class="btn btn-outline-secondary flex-shrink-0" type="button" title="Filters">
                    <i class="bi bi-funnel"></i>
                </button>

                <div class="input-group flex-grow-1 flex-shrink-1 mgmt-search-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" id="searchInput" class="form-control" placeholder="Search equipment...">
                    <button class="btn btn-outline-secondary" type="button" id="clearSearchBtn" style="display:none;">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <div class="d-flex gap-2 flex-shrink-0">
                    <select id="statusFilter" class="form-select mgmt-status-select">
                        <option value="">All Statuses</option>
                    </select>
                    <a href="{{ url('/admin/add-equipment') }}" class="btn btn-primary text-nowrap flex-shrink-0" title="Add New">
                        <i class="bi bi-plus-circle-fill me-2"></i><span class="btn-label">Add New</span>
                    </a>
                    <button type="button" id="massAssignBtn" class="btn btn-primary text-nowrap flex-shrink-0" title="Mass Assign Departments">
                        <i class="bi bi-diagram-3-fill me-2"></i><span class="btn-label">Assign Departments</span>
                    </button>
                </div>
            </div>

            {{-- ============ TWO-COLUMN ROW ============ --}}
            <div class="mgmt-row">

                {{-- LEFT SIDEBAR --}}
                <aside class="mgmt-sidebar" id="filterPanel">
                    
                    {{-- Categories --}}
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Categories</h6>
                    </div>
                    <div class="cat-tree mb-4" id="categoryTree">
                        <div class="cat-node">
                            <label class="d-block">
                                <input type="radio" name="categoryRadio" value="" class="category-radio" checked>
                                All Categories
                            </label>
                        </div>
                    </div>

                    {{-- Conditions --}}
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Conditions</h6>
                    </div>
                    <div class="cat-tree" id="conditionTree">
                        <div class="cat-node">
                            <label class="d-block">
                                <input type="radio" name="conditionRadio" value="" class="condition-radio" checked>
                                All Conditions
                            </label>
                        </div>
                    </div>

                    {{-- Clear Button --}}
                    <div class="mt-4 pt-3 border-top">
                        <button type="button" id="clearFiltersBtn" class="btn btn-sm btn-outline-secondary w-100" disabled>Clear All Filters</button>
                    </div>

                </aside>

                <div class="mgmt-sidebar-backdrop" id="filterPanelBackdrop"></div>

                {{-- RIGHT MAIN --}}
                <section class="mgmt-main" id="mainColumn">

                    {{-- Nav Tabs --}}
                    <ul class="nav nav-tabs mb-3" id="equipmentTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="equipment-tab" data-bs-toggle="tab" data-bs-target="#equipment-pane" type="button" role="tab" data-section="equipment">
                                Equipment
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="items-tab" data-bs-toggle="tab" data-bs-target="#items-pane" type="button" role="tab" data-section="items">
                                Items
                            </button>
                        </li>
                    </ul>

                    {{-- Wrapper for tab content + overlay --}}
                    <div class="position-relative" style="min-height: 300px;">
                        
                        {{-- Tab Content --}}
                        <div class="tab-content" id="equipmentTabsContent">
                            
                            {{-- EQUIPMENT PANE --}}
                            <div class="tab-pane fade show active" id="equipment-pane" role="tabpanel">
                                <div id="equipmentList"></div>
                                <div id="equipmentPagination" class="d-flex justify-content-center mt-3"></div>
                            </div>

                            {{-- ITEMS PANE --}}
                            <div class="tab-pane fade" id="items-pane" role="tabpanel">
                                <div id="itemsList"></div>
                                <div id="itemsPagination" class="d-flex justify-content-center mt-3"></div>
                            </div>
                        </div>

                        {{-- Results overlay --}}
                        <div id="resultsView" style="display: none;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0 text-muted" id="resultsHeader">Results</h6>
                            </div>
                            <div id="resultsList"></div>
                        </div>

                        {{-- Loading overlay --}}
                        <div id="loadingOverlay" style="display: none;">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status"></div>
                                <p class="mt-2 text-muted mb-0">Loading data...</p>
                            </div>
                        </div>
                    </div>

                </section>
            </div>

            {{-- Modals --}}
            @include('partials.resource-management.delete-confirmation-modal', ['resourceLabel' => 'Equipment'])
            @include('partials.resource-management.mass-assign-departments-modal', ['resourceLabel' => 'Equipment'])
            @include('partials.resource-management.assignment-warning-modal', ['resourceLabel' => 'Equipment'])

        </div>
    </main>
@endsection

@section('scripts')
    <script src="{{ asset('js/admin/toast.js') }}"></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {

            const token = localStorage.getItem("adminToken");
            if (!token) return window.location.href = "/admin/login";

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
                conditionTree: $('conditionTree'), // New Condition Tree
                tabsContainer: $('equipmentTabs'),
                tabContent: $('equipmentTabsContent'),
                resultsView: $('resultsView'),
                resultsHeader: $('resultsHeader'),
                resultsList: $('resultsList'),
                loadingOverlay: $('loadingOverlay'),
            };

            const state = {
                filtersLoaded: false,
                categoriesById: {},
                selectedCategoryId: null,
                selectedConditionId: null, // New condition state
                searchTimer: null,
                currentDeleteId: null,
                currentDeleteType: null,
                currentDeleteEquipmentId: null,
                activeTab: 'equipment',
                pagination: {
                    equipment: { current_page: 1, last_page: 1 },
                    items: { current_page: 1, last_page: 1 }
                },
                childCache: {},
                resultsState: { page: 1, lastPage: 1, query: '' }
            };

            const apiFetch = async (url, options = {}) => {
                const res = await fetch(url, {
                    ...options,
                    headers: {
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`,
                        ...(options.body && !(options.body instanceof FormData) ? { 'Content-Type': 'application/json' } : {}),
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

            const escapeHtml = (s) => s === null || s === undefined ? '' : String(s)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;').replace(/'/g, '&#39;');

            const typeBadge = (t) => ({
                equipment: '<span class="badge bg-primary badge-type">Equipment</span>',
                item: '<span class="badge bg-secondary badge-type">Item</span>',
            } [t] || '');

            const FALLBACK_IMG = "/assets/placeholder-image.png";
            const imgFor = (r) => r.primary_image_url || FALLBACK_IMG;

            const hasActiveFilters = () => !!els.statusFilter.value || !!state.selectedCategoryId || !!state.selectedConditionId;

            const buildFilterQuery = () => {
                const p = new URLSearchParams();
                if (els.statusFilter.value) p.set('status_id', els.statusFilter.value);
                if (state.selectedCategoryId) p.set('category_id', state.selectedCategoryId);
                if (state.selectedConditionId) p.set('condition_id', state.selectedConditionId); // Add to payload
                return p.toString();
            };

            const ensureModal = (templateId, modalId) => {
                if (document.getElementById(modalId)) return true;
                const tpl = document.getElementById(templateId);
                if (!tpl) return false;
                document.body.appendChild(tpl.content.cloneNode(true));
                return true;
            };

            const renderCategoryTree = (categories) => {
                state.categoriesById = {};
                const rows = categories.map(c => {
                    state.categoriesById[c.category_id] = c;
                    return `
            <div class="cat-node" data-category-id="${c.category_id}">
              <label class="d-block">
                <input type="radio" name="categoryRadio" value="${c.category_id}" class="category-radio">
                ${escapeHtml(c.category_name)}
              </label>
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
                        refreshView();
                    });
                });
            };

            // New Condition Tree Renderer
            const renderConditionTree = (conditions) => {
                const rows = conditions.map(c => `
                    <div class="cat-node">
                        <label class="d-block">
                            <input type="radio" name="conditionRadio" value="${c.condition_id}" class="condition-radio">
                            ${escapeHtml(c.condition_name)}
                        </label>
                    </div>`
                ).join('');
                
                els.conditionTree.innerHTML = `
                    <div class="cat-node">
                        <label class="d-block">
                            <input type="radio" name="conditionRadio" value="" class="condition-radio" checked>
                            All Conditions
                        </label>
                    </div>${rows}`;
                wireConditionEvents();
            };

            const wireConditionEvents = () => {
                els.conditionTree.querySelectorAll('.condition-radio').forEach(r => {
                    r.addEventListener('change', () => {
                        if (!r.checked) return;
                        state.selectedConditionId = r.value ? parseInt(r.value) : null;
                        refreshView();
                    });
                });
            };

            const resetFilters = () => {
                state.selectedCategoryId = null;
                state.selectedConditionId = null;
                els.statusFilter.value = '';
                els.categoryTree.querySelectorAll('input[type="radio"]').forEach(r => r.checked = r.value === '');
                els.conditionTree.querySelectorAll('input[type="radio"]').forEach(r => r.checked = r.value === '');
                els.clearFiltersBtn.disabled = true;
            };

            // ==================== NODE RENDER ====================
            const renderNode = (row, hideExpandBtn = false) => {
                const isEquipment = row.equipment_id !== undefined && row.item_id === undefined;
                const statusBadge = row.status_name ?
                    `<span class="badge" style="background-color:${escapeHtml(row.status_color_code || '#6c757d')}; color:#fff;">${escapeHtml(row.status_name)}</span>` : '';

                if (isEquipment) {
                    const itemsBadge = row.items_count > 0 ? ` · <strong>${row.items_count} item${row.items_count === 1 ? '' : 's'}</strong>` : '';
                    const price = row.base_fee !== null && row.base_fee !== undefined ?
                        `<p class="node-meta mb-0"><i class="bi bi-cash-stack me-1"></i>₱${parseFloat(row.base_fee).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} <span class="text-muted ms-1">(${escapeHtml(row.rate_type || '')})</span></p>` : '';
                    
                    const expandBtn = hideExpandBtn ? '' : `<button class="btn btn-sm btn-outline-secondary btn-expand" data-id="${row.equipment_id}" title="Expand"><i class="bi bi-chevron-down"></i></button>`;
                    const childrenWrap = hideExpandBtn ? '' : `<div class="children-wrap" data-children-for="${row.equipment_id}"><div class="inner"></div></div>`;

                    return `
            <div class="node" data-equipment-id="${row.equipment_id}" data-type="equipment">
              <img src="${imgFor(row)}" class="node-img" onerror="this.src='${FALLBACK_IMG}'" alt="${escapeHtml(row.equipment_name)}">
              <div class="node-body">
                <p class="node-title">${escapeHtml(row.equipment_name)}${row.brand ? ` <span class="text-muted small">(${escapeHtml(row.brand)})</span>` : ''}</p>
                <p class="node-meta"><i class="bi bi-tag-fill me-1"></i>${escapeHtml(row.category_name)}${itemsBadge}</p>
                ${price}
                <div class="mt-1">${statusBadge} ${typeBadge('equipment')}</div>
              </div>
              <div class="node-actions">
                <a href="/admin/edit-equipment?id=${row.equipment_id}" class="btn btn-sm btn-primary">Manage</a>
                <button class="btn btn-sm btn-danger btn-delete-equipment" data-id="${row.equipment_id}">Delete</button>
                ${expandBtn}
              </div>
            </div>
            ${childrenWrap}
          `;
                }

                const parentMeta = row.equipment_name ? `<span class="text-muted mx-1">•</span><i class="bi bi-box-seam me-1"></i>Parent: ${escapeHtml(row.equipment_name)}` : '';
                const categoryMeta = row.category_name ? `<i class="bi bi-tag-fill me-1"></i>${escapeHtml(row.category_name)}` : '';
                const barcodeMeta = row.barcode_number ? `<p class="node-meta mb-0"><i class="bi bi-upc-scan me-1"></i>${escapeHtml(row.barcode_number)}</p>` : '';
                const conditionBadge = row.condition_name ? `<span class="badge bg-info text-dark">${escapeHtml(row.condition_name)}</span>` : '';

                return `
          <div class="node" data-item-id="${row.item_id}" data-type="item">
            <img src="${imgFor(row)}" class="node-img sm" onerror="this.src='${FALLBACK_IMG}'" alt="${escapeHtml(row.item_name)}">
            <div class="node-body">
              <p class="node-title">${escapeHtml(row.item_name)}</p>
              <p class="node-meta">${categoryMeta}${parentMeta}</p>
              ${barcodeMeta}
              <div class="mt-1">${statusBadge} ${conditionBadge} ${typeBadge('item')}</div>
            </div>
            <div class="node-actions">
              <a href="/admin/edit-equipment?id=${row.equipment_id}" class="btn btn-sm btn-primary">Manage</a>
              <button class="btn btn-sm btn-danger btn-delete-item" data-id="${row.item_id}" data-equipment-id="${row.equipment_id}">Delete</button>
            </div>
          </div>`;
            };

            // ==================== LAZY LOAD ITEMS ====================
            const loadItemsPage = async (equipmentId, page) => {
                return apiFetch(`/api/admin/equipment/${equipmentId}/items-list?page=${page}`);
            };

            const renderItems = (wrap, equipmentId, res, append = false) => {
                if (!wrap) return;
                const inner = wrap.querySelector('.inner');

                const cache = state.childCache[equipmentId] ||= { rows: [], currentPage: 0, lastPage: 1 };
                if (append) cache.rows.push(...(res.data || []));
                else cache.rows = res.data || [];
                
                cache.currentPage = res.pagination.current_page;
                cache.lastPage = res.pagination.last_page;

                const nodesHtml = cache.rows.map(r => renderNode(r, true)).join(''); 
                const lmHtml = cache.currentPage < cache.lastPage ?
                    `<button class="btn btn-sm btn-outline-secondary btn-load-more-items my-2" data-id="${equipmentId}">
                       <i class="bi bi-arrow-down-circle me-1"></i>Load more items (${cache.rows.length}/${res.pagination.total})
                     </button>` : '';

                inner.innerHTML = (cache.rows.length ? nodesHtml : '<p class="text-muted small mb-2 p-2">No items match your filters.</p>') + lmHtml;
                
                wrap.classList.add('open');
                wireNodeEvents(inner);

                const lm = inner.querySelector('.btn-load-more-items');
                if (lm) lm.addEventListener('click', async () => {
                    lm.disabled = true;
                    try {
                        const next = await loadItemsPage(equipmentId, cache.currentPage + 1);
                        renderItems(wrap, equipmentId, next, true);
                    } catch (e) {
                        console.error(e);
                        if (typeof showToast === 'function') showToast('Failed to load more items', 'error');
                        lm.disabled = false;
                    }
                });
            };

            const toggleEquipment = async (equipmentId, btn) => {
                const nodeEl = btn.closest('.node');
                const wrap = nodeEl?.nextElementSibling;
                if (!wrap || !wrap.matches(`.children-wrap[data-children-for="${equipmentId}"]`)) return;

                const isOpen = wrap.classList.contains('open');
                if (isOpen) {
                    wrap.classList.remove('open');
                    btn.classList.remove('expanded');
                    return;
                }

                wrap.classList.add('open');
                btn.classList.add('expanded');

                const cache = state.childCache[equipmentId];
                if (cache?.rows.length) {
                    const inner = wrap.querySelector('.inner');
                    inner.innerHTML = cache.rows.map(r => renderNode(r, true)).join('');
                    wireNodeEvents(inner);
                    return;
                }

                const inner = wrap.querySelector('.inner');
                inner.innerHTML = '<p class="text-muted small mb-2 p-2">Loading items...</p>';
                try {
                    const res = await loadItemsPage(equipmentId, 1);
                    renderItems(wrap, equipmentId, res, false);
                } catch (e) {
                    console.error(e);
                    inner.innerHTML = '<p class="text-danger small mb-2 p-2">Failed to load items.</p>';
                }
            };

            const wireNodeEvents = (root) => {
                root.querySelectorAll('.btn-delete-equipment').forEach(b => b.addEventListener('click', () => openDeleteModal(b.dataset.id, 'equipment')));
                root.querySelectorAll('.btn-delete-item').forEach(b => b.addEventListener('click', () => openDeleteModal(b.dataset.id, 'item', b.dataset.equipmentId)));
                root.querySelectorAll('.btn-expand').forEach(b => b.addEventListener('click', () => toggleEquipment(b.dataset.id, b)));
            };
            
            // ==================== PAGINATION RENDERER ====================
            const renderPagination = (containerId, section, pagination) => {
                const container = document.getElementById(containerId);
                if (!container) return;

                if (pagination.last_page <= 1) {
                    container.innerHTML = '';
                    return;
                }

                container.innerHTML = `
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item ${pagination.current_page === 1 ? 'disabled' : ''}">
                                <button class="page-link" data-section="${section}" data-page="${pagination.current_page - 1}">Previous</button>
                            </li>
                            <li class="page-item disabled">
                                <span class="page-link">Page ${pagination.current_page} of ${pagination.last_page}</span>
                            </li>
                            <li class="page-item ${pagination.current_page === pagination.last_page ? 'disabled' : ''}">
                                <button class="page-link" data-section="${section}" data-page="${pagination.current_page + 1}">Next</button>
                            </li>
                        </ul>
                    </nav>
                `;

                container.querySelectorAll('.page-link').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.preventDefault();
                        const sec = btn.dataset.section;
                        const page = parseInt(btn.dataset.page);
                        if (page > 0 && page <= pagination.last_page) {
                            fetchTabContent(sec, page);
                        }
                    });
                });
            };

            // ==================== TAB FETCHER ====================
            const fetchTabContent = async (section, page = 1) => {
                showLoading();
                try {
                    const q = buildFilterQuery();
                    let url = '';
                    
                    if (section === 'equipment') {
                        url = `/api/admin/manage-equipment/section?section=equipment&page=${page}${q ? '&' + q : ''}`;
                    } else if (section === 'items') {
                        url = `/api/admin/manage-equipment/section?section=items&page=${page}${q ? '&' + q : ''}`;
                    }

                    const res = await apiFetch(url);
                    
                    const listId = `${section}List`;
                    const paginationId = `${section}Pagination`;
                    const listEl = document.getElementById(listId);

                    if (res.data && res.data.length) {
                        listEl.innerHTML = res.data.map(r => renderNode(r)).join('');
                        wireNodeEvents(listEl);
                    } else {
                        listEl.innerHTML = `<p class="text-muted small mb-0 p-2">No ${section} match your filters.</p>`;
                    }

                    state.pagination[section] = res.pagination;
                    renderPagination(paginationId, section, res.pagination);

                } catch (e) {
                    console.error(e);
                    if (typeof showToast === 'function') showToast(`Failed to load ${section}`, 'error');
                } finally {
                    hideLoading();
                }
            };

            // ==================== RESULTS OVERLAY ====================
            const renderResults = (rows, headerText, emptyText, append = false) => {
                document.querySelector('.tab-content').style.display = 'none';
                els.resultsView.style.display = 'block';
                
                if (!append) {
                    els.resultsHeader.textContent = headerText;
                    els.resultsList.innerHTML = ''; // Clear previous results
                }

                if (!rows.length && !append) {
                    els.resultsList.innerHTML = `
              <div class="text-center py-5 text-muted">
                <i class="bi bi-search fs-1"></i>
                <p class="mt-2 mb-0">${escapeHtml(emptyText)}</p>
              </div>`;
                    return;
                }

                // Remove existing "Load More" button before appending new rows
                const existingLm = els.resultsList.querySelector('.btn-load-more-results');
                if (existingLm) existingLm.remove();

                // Append new rows
                const html = rows.map(r => renderNode(r, true)).join('');
                els.resultsList.insertAdjacentHTML('beforeend', html);
                wireNodeEvents(els.resultsList);

                // Add Load More button if needed
                if (state.resultsState.page < state.resultsState.lastPage) {
                    els.resultsList.insertAdjacentHTML('beforeend', `
                        <div class="text-center my-3">
                            <button class="btn btn-outline-secondary btn-load-more-results">
                                <i class="bi bi-arrow-down-circle me-1"></i> Load more results
                            </button>
                        </div>
                    `);

                    els.resultsList.querySelector('.btn-load-more-results').addEventListener('click', async (e) => {
                        const btn = e.currentTarget;
                        const origHtml = btn.innerHTML;
                        btn.disabled = true;
                        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Loading...';
                        
                        state.resultsState.page++;
                        await loadMoreResults();
                    });
                }
            };

            const exitResults = () => {
                els.resultsView.style.display = 'none';
                document.querySelector('.tab-content').style.display = 'block';
            };

            // ==================== FETCHERS ====================
            const fetchIndexForTabs = async (populate = false) => {
                showLoading();
                try {
                    const res = await apiFetch('/api/admin/manage-equipment');
                    if (populate && !state.filtersLoaded) {
                        els.statusFilter.innerHTML = '<option value="">All Statuses</option>';
                        (res.filters.statuses || []).forEach(s => {
                            els.statusFilter.insertAdjacentHTML('beforeend', `<option value="${s.status_id}">${escapeHtml(s.status_name)}</option>`);
                        });
                        renderCategoryTree(res.filters.categories || []);
                        renderConditionTree(res.filters.conditions || []); // Populate conditions
                        state.filtersLoaded = true;
                    }
                    exitResults();
                    
                    await fetchTabContent('equipment', 1);
                } catch (e) {
                    console.error(e);
                    document.getElementById('equipmentList').innerHTML = '<p class="text-danger small p-2">Failed to load.</p>';
                } finally {
                    hideLoading();
                }
            };

            const fetchFilteredResults = async () => {
                showLoading();
                try {
                    const q = buildFilterQuery();
                    state.resultsState = { page: 1, lastPage: 1, query: q };

                    const res = await apiFetch(`/api/admin/manage-equipment?${q}`);
                    
                    let rows = [];
                    let lastPage = 1;

                    if (state.selectedConditionId) {
                        // Condition selected: ONLY show Items, no Equipment
                        rows = res.data.items || [];
                        lastPage = res.pagination.items?.last_page || 1;
                    } else {
                        // Standard filters: Show both Equipment and Items
                        const equipmentRows = res.data.equipment || [];
                        const itemRows = res.data.items || [];
                        rows = [...equipmentRows, ...itemRows];
                        lastPage = Math.max(
                            res.pagination.equipment?.last_page || 1,
                            res.pagination.items?.last_page || 1
                        );
                    }

                    state.resultsState.lastPage = lastPage;

                    renderResults(rows, `Filtered Results — ${rows.length} match${rows.length === 1 ? '' : 'es'}`, 'No items match the selected filters.', false);
                } catch (e) {
                    console.error(e);
                    if (typeof showToast === 'function') showToast('Failed to load results', 'error');
                } finally {
                    hideLoading();
                }
            };

            const loadMoreResults = async () => {
                try {
                    const q = state.resultsState.query;
                    const page = state.resultsState.page;

                    const url = `/api/admin/manage-equipment?${q}&equipment_page=${page}&items_page=${page}`;
                    const res = await apiFetch(url);

                    let rows = [];

                    if (state.selectedConditionId) {
                        // Condition selected: ONLY load Items
                        rows = res.data.items || [];
                    } else {
                        // Standard filters: Load both
                        const equipmentRows = res.data.equipment || [];
                        const itemRows = res.data.items || [];
                        rows = [...equipmentRows, ...itemRows];
                    }

                    if (rows.length > 0) {
                        const currentText = els.resultsHeader.textContent;
                        const matchCount = parseInt(currentText.match(/\d+/)?.[0] || 0) + rows.length;
                        els.resultsHeader.textContent = `Filtered Results — ${matchCount} matches`;
                    }

                    renderResults(rows, '', '', true);
                } catch (e) {
                    console.error(e);
                    if (typeof showToast === 'function') showToast('Failed to load more results', 'error');
                    state.resultsState.page--;
                }
            };

            const runSearch = async (q) => {
                showLoading();
                try {
                    state.resultsState = { page: 1, lastPage: 1, query: q };

                    const res = await apiFetch(`/api/admin/manage-equipment/search?q=${encodeURIComponent(q)}`);
                    const results = res.results || [];
                    const rows = results.map(r => r.result_type === 'item' ? r.item : r.equipment);
                    
                    renderResults(rows, `Search Results — ${rows.length} match${rows.length === 1 ? '' : 'es'} for "${q}"`, 'No equipment found.', false);
                } catch (e) {
                    console.error(e);
                    if (typeof showToast === 'function') showToast('Search failed', 'error');
                } finally {
                    hideLoading();
                }
            };

            const refreshView = () => {
                const hasSearch = !!els.searchInput.value.trim();
                if (hasSearch) return;
                if (hasActiveFilters()) {
                    els.clearFiltersBtn.disabled = false;
                    fetchFilteredResults();
                } else {
                    els.clearFiltersBtn.disabled = true;
                    exitResults();
                    fetchTabContent(state.activeTab, 1);
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

            document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(tabEl => {
                tabEl.addEventListener('shown.bs.tab', (e) => {
                    state.activeTab = e.target.dataset.section;
                    if (!els.searchInput.value.trim() && !hasActiveFilters()) {
                        const pagination = state.pagination[state.activeTab];
                        fetchTabContent(state.activeTab, pagination.current_page || 1);
                    }
                });
            });

            // ==================== DELETE ====================
            const openDeleteModal = (id, type, equipmentId = null) => {
                state.currentDeleteId = id;
                state.currentDeleteType = type;
                state.currentDeleteEquipmentId = equipmentId;
                ensureModal('deleteModalTemplate', 'deleteConfirmationModal');

                if (!state._deleteWired) {
                    document.getElementById('confirmDeleteBtn')?.addEventListener('click', async () => {
                        if (!state.currentDeleteId) return;
                        const btn = document.getElementById('confirmDeleteBtn');
                        btn.disabled = true;

                        const url = state.currentDeleteType === 'item' ?
                            `/api/admin/equipment/${state.currentDeleteEquipmentId}/items/${state.currentDeleteId}` :
                            `/api/admin/equipment/${state.currentDeleteId}`;

                        try {
                            await apiFetch(url, { method: 'DELETE' });
                            if (typeof showToast === 'function') showToast(`${state.currentDeleteType === 'item' ? 'Item' : 'Equipment'} deleted successfully`, 'success');
                            bootstrap.Modal.getInstance(document.getElementById('deleteConfirmationModal'))?.hide();
                            refreshView();
                        } catch (e) {
                            console.error(e);
                            if (typeof showToast === 'function') showToast('Failed to delete', 'error');
                        } finally {
                            btn.disabled = false;
                            state.currentDeleteId = null;
                            state.currentDeleteType = null;
                        }
                    });
                    state._deleteWired = true;
                }

                const el = document.getElementById('deleteConfirmationModal');
                if (!el) return;
                el.style.zIndex = '1075';
                const modal = new bootstrap.Modal(el);
                el.addEventListener('shown.bs.modal', () => {
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    if (backdrops.length) backdrops[backdrops.length - 1].style.zIndex = '1070';
                }, { once: true });
                modal.show();
            };

            // ==================== MASS ASSIGN ====================
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
                const p = new URLSearchParams({ page, per_page: 15 });
                if (maState.search) p.set('search', maState.search);
                return apiFetch(`/api/admin/manage-equipment/dropdown?${p.toString()}`);
            };

            const maRenderCheckboxList = () => {
                const list = document.getElementById('massAssignCheckboxList');
                if (!list) return;

                if (!maState.currentRows.length) {
                    list.innerHTML = '<p class="text-muted small mb-0 p-2">No equipment found.</p>';
                    return;
                }

                list.innerHTML = maState.currentRows.map(row => {
                    const checked = maState.selected.has(row.equipment_id) ? 'checked' : '';
                    const brand = row.brand ? ` <span class="text-muted small">(${escapeHtml(row.brand)})</span>` : '';
                    return `
            <div class="form-check py-1 border-bottom">
              <input class="form-check-input ma-facility-checkbox" type="checkbox" value="${row.equipment_id}" id="ma-fac-${row.equipment_id}" ${checked}>
              <label class="form-check-label small" for="ma-fac-${row.equipment_id}">
                ${escapeHtml(row.equipment_name)}${brand}
              </label>
            </div>`;
                }).join('');

                list.querySelectorAll('.ma-facility-checkbox').forEach(cb => {
                    cb.addEventListener('change', () => {
                        const id = parseInt(cb.value);
                        const row = maState.currentRows.find(r => r.equipment_id === id);
                        if (!row) return;
                        if (cb.checked) maState.selected.set(id, { name: row.equipment_name, brand: row.brand });
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
                    list.innerHTML = '<p class="text-muted small mb-0">No equipment selected yet.</p>';
                    return;
                }

                list.innerHTML = Array.from(maState.selected.entries()).map(([id, info]) => `
          <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
            <span class="small text-truncate" style="max-width: 85%;">${escapeHtml(info.name)}</span>
            <button type="button" class="btn btn-sm btn-link text-danger p-0 ma-remove-selected" data-id="${id}" title="Remove" style="line-height: 1;">
              <i class="bi bi-x-circle-fill" style="color: #dc3545;"></i>
            </button>
          </div>`).join('');

                list.querySelectorAll('.ma-remove-selected').forEach(btn => {
                    btn.addEventListener('click', () => {
                        maState.selected.delete(parseInt(btn.dataset.id));
                        maRenderSelectedList();
                        maRenderCheckboxList();
                    });
                });
            };

            const maUpdatePaginationUI = () => {
                const ind = document.getElementById('massAssignPageIndicator');
                const total = document.getElementById('massAssignTotalCount');
                const prev = document.getElementById('massAssignPrev');
                const next = document.getElementById('massAssignNext');
                if (ind) ind.textContent = `${maState.page} / ${maState.lastPage}`;
                if (total) total.textContent = `${maState.total} item${maState.total === 1 ? '' : 's'}`;
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
                    const container = document.getElementById('departmentCheckboxList');
                    if (!container) return;

                    container.innerHTML = list.map(d => `
                        <div class="form-check py-1 border-bottom">
                            <input class="form-check-input ma-department-checkbox" type="checkbox" value="${d.department_id}" id="ma-dept-${d.department_id}">
                            <label class="form-check-label small" for="ma-dept-${d.department_id}">
                                ${escapeHtml(d.department_name)}
                            </label>
                        </div>
                    `).join('');

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
                const execBtn = document.getElementById('executeMassAssignBtn');
                const confirmBtn = document.getElementById('confirmAssignBtn');
                if (!searchInput || !execBtn || !confirmBtn) return;

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
                    maState.currentRows.forEach(r => maState.selected.set(r.equipment_id, { name: r.equipment_name, brand: r.brand }));
                    maRenderCheckboxList();
                    maRenderSelectedList();
                });

                clearAll.addEventListener('click', () => {
                    maState.selected.clear();
                    maRenderCheckboxList();
                    maRenderSelectedList();
                });

                execBtn.addEventListener('click', () => {
                    const checkedDepts = Array.from(document.querySelectorAll('.ma-department-checkbox:checked')).map(cb => ({
                        id: parseInt(cb.value),
                        name: document.querySelector(`label[for="${cb.id}"]`)?.textContent.trim()
                    }));

                    if (maState.selected.size === 0) return showToast('Please select at least one equipment', 'error');
                    if (checkedDepts.length === 0) return showToast('Please select at least one department', 'error');

                    maState.pendingAssignment = {
                        ids: Array.from(maState.selected.keys()),
                        depts: checkedDepts,
                    };

                    ensureModal('assignmentWarningTemplate', 'assignmentWarningModal');
                    const w = document.getElementById('assignmentWarningModal');
                    if (w) {
                        const massAssignEl = document.getElementById('massAssignDepartmentsModal');
                        const massAssignZ = parseInt(massAssignEl?.style.zIndex) || 1055;
                        w.style.zIndex = String(massAssignZ + 100);
                        const modal = bootstrap.Modal.getOrCreateInstance(w);
                        modal.show();
                        setTimeout(() => {
                            const backdrops = document.querySelectorAll('.modal-backdrop');
                            if (backdrops.length) backdrops[backdrops.length - 1].style.zIndex = String(massAssignZ + 50);
                        }, 0);
                    }
                });

                confirmBtn.addEventListener('click', async () => {
                    if (!maState.pendingAssignment) return;
                    const btn = confirmBtn;
                    const orig = btn.innerHTML;
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

                    try {
                        for (const dept of maState.pendingAssignment.depts) {
                            await apiFetch('/api/admin/equipment/mass-assign-departments', {
                                method: 'POST',
                                body: JSON.stringify({
                                    equipment_ids: maState.pendingAssignment.ids,
                                    department_id: dept.id,
                                }),
                            });
                        }
                        const countEq = maState.pendingAssignment.ids.length;
                        const countDept = maState.pendingAssignment.depts.length;
                        showToast(`Assigned ${countDept} department(s) to ${countEq} equipment.`, 'success');
                        
                        bootstrap.Modal.getInstance(document.getElementById('assignmentWarningModal'))?.hide();
                        bootstrap.Modal.getInstance(document.getElementById('massAssignDepartmentsModal'))?.hide();
                        maState.selected.clear();
                        if (hasActiveFilters()) fetchFilteredResults();
                        else fetchIndexForTabs();
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

            const openMassAssignModal = async (e) => {
                const btn = e.currentTarget;
                const origHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Loading...';

                try {
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
                    const selList = document.getElementById('massAssignSelectedList');
                    const selCount = document.getElementById('massAssignSelectedCount');

                    if (searchInput) searchInput.value = '';
                    if (searchClear) searchClear.style.display = 'none';
                    
                    document.querySelectorAll('.ma-department-checkbox').forEach(cb => cb.checked = false);
                    
                    if (selList) selList.innerHTML = '<p class="text-muted small mb-0">No equipment selected yet.</p>';
                    if (selCount) selCount.textContent = '0';

                    if (!maState.wired) {
                        wireMassAssignControls();
                        maState.wired = true;
                    }

                    await Promise.all([maLoadPage(1), maLoadDepartments()]);
                    new bootstrap.Modal(document.getElementById('massAssignDepartmentsModal')).show();
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = origHtml;
                }
            };

            document.getElementById('massAssignBtn').addEventListener('click', openMassAssignModal);

            // ==================== BOOT ====================
            fetchIndexForTabs(true);
        });
    </script>
@endsection
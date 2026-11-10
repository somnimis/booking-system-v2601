@extends('layouts.admin')

@section('title', 'Manage Equipment')

@section('content')
    {{-- Local styles — remove if you've already moved these into your external stylesheet --}}
    <style>
        .mgmt-topbar {
            min-width: 0;
        }

        .mgmt-search-group {
            flex: 1 1 240px;
            min-width: 0;
        }

        .mgmt-search-group .form-control {
            min-width: 0;
            flex: 1 1 auto;
        }

        .mgmt-status-select {
            width: auto;
            min-width: 150px;
            max-width: 200px;
            flex-shrink: 0;
        }

        .mgmt-row {
            display: flex;
            gap: 1rem;
            align-items: flex-start;
        }

        .mgmt-sidebar {
            width: 280px;
            flex-shrink: 0;
            position: sticky;
            top: 1rem;
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            max-height: calc(100vh - 2rem);
            overflow-y: auto;
        }

        .mgmt-sidebar h6 {
            color: var(--cpu-primary, #003366);
            font-weight: 600;
        }

        .cat-tree {
            font-size: .9rem;
        }

        .cat-tree .cat-node {
            margin-bottom: .35rem;
        }

        .cat-tree .cat-node label {
            cursor: pointer;
        }

        .cat-tree input[type="radio"] {
            margin-right: .35rem;
        }

        .mgmt-main {
            flex: 1;
            min-width: 0;
            position: relative;
            min-height: 300px;
        }

        .section-header {
            cursor: pointer;
            user-select: none;
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: .65rem 1rem;
            font-weight: 600;
            color: var(--cpu-primary, #003366);
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: .5rem;
        }

        .section-header:hover {
            background: #f0f4f8;
        }

        .section-header>span:first-child {
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .section-header .count {
            font-size: .85rem;
            color: #6c757d;
            font-weight: 400;
        }

        .section-body,
        .children-wrap {
            display: grid;
            grid-template-rows: 0fr;
            transition: grid-template-rows .25s ease;
        }

        .section-body>.inner,
        .children-wrap>.inner {
            overflow: hidden;
            min-height: 0;
        }

        .section-body.open,
        .children-wrap.open {
            grid-template-rows: 1fr;
        }

        .children-wrap {
            margin-left: 2.5rem;
            padding-left: 1rem;
            border-left: 2px solid #e9ecef;
        }

        .section-header .toggle-icon {
            transition: transform .25s ease;
        }

        .section-header.closed .toggle-icon {
            transform: rotate(-90deg);
        }

        .node {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            display: flex;
            gap: .75rem;
            align-items: center;
            padding: .65rem;
            margin-bottom: .5rem;
        }

        .node-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 6px;
            flex-shrink: 0;
        }

        .node-img.sm {
            width: 56px;
            height: 56px;
        }

        .node-body {
            flex: 1;
            min-width: 0;
        }

        .node-title {
            font-weight: 600;
            color: #212529;
            margin: 0;
        }

        .node-meta {
            font-size: .8rem;
            color: #6c757d;
            margin: 0;
        }

        .node-actions {
            display: flex;
            flex-direction: column;
            gap: .25rem;
            flex-shrink: 0;
        }

        .node-actions .btn {
            white-space: nowrap;
        }

        .badge-type {
            font-size: .65rem;
        }

        .btn-expand .bi {
            transition: transform .25s ease;
            display: inline-block;
        }

        .btn-expand.expanded .bi {
            transform: rotate(180deg);
        }

        /* Loading overlay — pure white background per request */
        #loadingOverlay {
            position: absolute;
            inset: 0;
            background: #ffffff;
            z-index: 100;
            display: none;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
        }

        #resultsView {
            display: none;
        }

        #resultsView.active {
            display: block;
        }

        #accordionView.hidden {
            display: none;
        }

        @media (max-width: 991.98px) {
            .mgmt-sidebar {
                position: fixed;
                top: 0;
                left: -320px;
                width: 300px;
                height: 100vh;
                max-height: 100vh;
                z-index: 1060;
                transition: left .25s ease;
                border-radius: 0;
            }

            .mgmt-sidebar.show {
                left: 0;
            }

            .mgmt-sidebar-backdrop {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, .4);
                z-index: 1055;
            }

            .mgmt-sidebar-backdrop.show {
                display: block;
            }
        }

        @media (min-width: 992px) {
            .mgmt-sidebar-backdrop {
                display: none !important;
            }

            #filterPanelToggle {
                display: none !important;
            }
        }
    </style>

    <main id="main">
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
                    <a href="{{ url('/admin/add-equipment') }}" class="btn btn-primary text-nowrap flex-shrink-0"
                        title="Add New">
                        <i class="bi bi-plus-circle-fill me-2"></i><span class="btn-label">Add New</span>
                    </a>
                    <button type="button" id="massAssignBtn" class="btn btn-primary text-nowrap flex-shrink-0"
                        title="Mass Assign Departments">
                        <i class="bi bi-diagram-3-fill me-2"></i><span class="btn-label">Mass Assign Departments</span>
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

                    {{-- Accordion view (single section: Equipment) --}}
                    <div id="accordionView">

                        <div class="section-header" data-section="equipment">
                            <span>
                                <i class="bi bi-chevron-down toggle-icon"></i>Equipment
                            </span>
                            <span class="count" id="equipmentCount">0 items</span>
                        </div>
                        <div class="section-body open" id="equipmentBody">
                            <div class="inner" id="equipmentSection"></div>
                        </div>

                    </div>

                    {{-- Results overlay (search + filtered) --}}
                    <div id="resultsView">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0 text-muted" id="resultsHeader">Results</h6>
                        </div>
                        <div id="resultsList"></div>
                    </div>

                    {{-- Loading overlay (pure white bg) --}}
                    <div id="loadingOverlay">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2 text-muted mb-0">Loading equipment...</p>
                        </div>
                    </div>

                </section>
            </div>

            {{-- Modals (shared partials from facilities; <template>-wrapped, lazy-cloned) --}}
            @include('partials.resource-management.delete-confirmation-modal', [
                'resourceLabel' => 'Equipment',
            ])
            @include('partials.resource-management.mass-assign-departments-modal', [
                'resourceLabel' => 'Equipment',
            ])
            @include('partials.resource-management.assignment-warning-modal', [
                'resourceLabel' => 'Equipment',
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
                equipmentSection: $('equipmentSection'),
                equipmentCount: $('equipmentCount'),
                equipmentBody: $('equipmentBody'),
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
                childCache: {}, // equipmentId -> { rows, currentPage, lastPage }
                searchTimer: null,
                currentDeleteId: null,
                currentDeleteEquipmentId: null,
                sectionState: {
                    equipment: {
                        loaded: false,
                        currentPage: 0,
                        lastPage: 1,
                        loadedCount: 0,
                        total: 0
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
                equipment: '<span class="badge bg-primary badge-type">Equipment</span>',
                item: '<span class="badge bg-secondary badge-type">Item</span>',
            } [t] || '');

            const FALLBACK_IMG =
                "https://res.cloudinary.com/dn98ntlkd/image/upload/v1759850278/t4fyv56wog6pglhwvwtn.png";
            const imgFor = (r) => r.primary_image_url || FALLBACK_IMG;

            const hasActiveFilters = () =>
                !!els.statusFilter.value || !!state.selectedCategoryId;

            const buildFilterQuery = () => {
                const p = new URLSearchParams();
                if (els.statusFilter.value) p.set('status_id', els.statusFilter.value);
                if (state.selectedCategoryId) p.set('category_id', state.selectedCategoryId);
                return p.toString();
            };

            // ==================== LAZY MODAL LOADER ====================
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

            // ==================== CATEGORY TREE (flat) ====================
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

            const resetFilters = () => {
                state.selectedCategoryId = null;
                els.statusFilter.value = '';
                els.categoryTree.querySelectorAll('input[type="radio"]').forEach(r => r.checked = r.value ===
                    '');
                els.clearFiltersBtn.disabled = true;
            };

            // ==================== NODE RENDER ====================
            const renderNode = (row) => {
                const isEquipment = row.equipment_id !== undefined && row.item_id === undefined;
                const statusBadge = row.status_name ?
                    `<span class="badge" style="background-color:${escapeHtml(row.status_color_code || '#6c757d')}; color:#fff;">${escapeHtml(row.status_name)}</span>` :
                    '';

                if (isEquipment) {
                    const itemsBadge = row.items_count > 0 ?
                        ` · <strong>${row.items_count} item${row.items_count === 1 ? '' : 's'}</strong>` : '';
                    const price = row.base_fee !== null && row.base_fee !== undefined ?
                        `<p class="node-meta mb-0">
                 <i class="bi bi-cash-stack me-1"></i>
                 ₱${parseFloat(row.base_fee).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                 <span class="text-muted">(${escapeHtml(row.rate_type || '')})</span>
               </p>` : '';

                    return `
            <div class="node" data-equipment-id="${row.equipment_id}" data-type="equipment">
              <img src="${imgFor(row)}" class="node-img"
                   onerror="this.src='${FALLBACK_IMG}'" alt="${escapeHtml(row.equipment_name)}">
              <div class="node-body">
                <p class="node-title">${escapeHtml(row.equipment_name)}${row.brand ? ` <span class="text-muted small">(${escapeHtml(row.brand)})</span>` : ''}</p>
                <p class="node-meta">
                  <i class="bi bi-tag-fill me-1"></i>${escapeHtml(row.category_name)}
                  ${itemsBadge}
                </p>
                ${price}
                <div class="mt-1">${statusBadge} ${typeBadge('equipment')}</div>
              </div>
              <div class="node-actions">
                <a href="/admin/edit-equipment?id=${row.equipment_id}" class="btn btn-sm btn-primary">Manage</a>
                <button class="btn btn-sm btn-danger btn-delete-equipment" data-id="${row.equipment_id}">Delete</button>
                ${row.items_count > 0
                  ? `<button class="btn btn-sm btn-outline-secondary btn-expand"
                                                     data-id="${row.equipment_id}" title="Expand"><i class="bi bi-chevron-down"></i></button>`
                  : ''}
              </div>
            </div>
            ${row.items_count > 0
              ? `<div class="children-wrap" data-children-for="${row.equipment_id}"><div class="inner"></div></div>`
              : ''}
          `;
                }

                // Item row (used in expanded children AND search item results)
                const parentMeta = row.equipment_name ?
                    `<span class="text-muted mx-1">•</span>
             <i class="bi bi-box-seam me-1"></i>Parent: ${escapeHtml(row.equipment_name)}` :
                    '';
                const categoryMeta = row.category_name ?
                    `<i class="bi bi-tag-fill me-1"></i>${escapeHtml(row.category_name)}` :
                    '';
                const barcodeMeta = row.barcode_number ?
                    `<p class="node-meta mb-0"><i class="bi bi-upc-scan me-1"></i>${escapeHtml(row.barcode_number)}</p>` :
                    '';
                const conditionBadge = row.condition_name ?
                    `<span class="badge bg-info text-dark">${escapeHtml(row.condition_name)}</span>` : '';

                return `
          <div class="node" data-item-id="${row.item_id}" data-type="item">
            <img src="${imgFor(row)}" class="node-img sm"
                 onerror="this.src='${FALLBACK_IMG}'" alt="${escapeHtml(row.item_name)}">
            <div class="node-body">
              <p class="node-title">${escapeHtml(row.item_name)}</p>
              <p class="node-meta">${categoryMeta}${parentMeta}</p>
              ${barcodeMeta}
              <div class="mt-1">${statusBadge} ${conditionBadge} ${typeBadge('item')}</div>
            </div>
            <div class="node-actions">
<a href="/admin/edit-equipment?id=${row.equipment_id}" class="btn btn-sm btn-primary">Manage</a>
<button class="btn btn-sm btn-danger btn-delete-item"
        data-id="${row.item_id}"
        data-equipment-id="${row.equipment_id}">Delete</button>
            </div>
          </div>`;
            };

            const wireNodeEvents = (root) => {
                root.querySelectorAll('.btn-delete-equipment').forEach(b =>
                    b.addEventListener('click', () => openDeleteModal(b.dataset.id, 'equipment')));
                root.querySelectorAll('.btn-delete-item').forEach(b =>
                    b.addEventListener('click', () =>
                        openDeleteModal(b.dataset.id, 'item', b.dataset.equipmentId)));
                root.querySelectorAll('.btn-expand').forEach(b =>
                    b.addEventListener('click', () => toggleEquipment(b.dataset.id, b)));
            };

            // ==================== ACCORDION RENDER ====================
            const loadMoreHtml = (section) => {
                const s = state.sectionState[section];
                if (!s || s.currentPage >= s.lastPage) return '';
                return `
          <div class="text-center my-2">
            <button class="btn btn-sm btn-outline-secondary btn-load-more-section" data-section="${section}">
              <i class="bi bi-arrow-down-circle me-1"></i>
              Load more equipment (${s.loadedCount}/${s.total})
            </button>
          </div>`;
            };

            const renderAccordion = (data) => {
                const equipment = data.equipment || [];
                const total = data.equipment_total ?? equipment.length;
                const PER_PAGE = 10;

                state.sectionState.equipment = {
                    loaded: true,
                    currentPage: Math.ceil(equipment.length / PER_PAGE) || 1,
                    lastPage: Math.max(1, Math.ceil(total / PER_PAGE)),
                    loadedCount: equipment.length,
                    total: total,
                };

                els.equipmentSection.innerHTML = equipment.length ?
                    equipment.map(renderNode).join('') + loadMoreHtml('equipment') :
                    '<p class="text-muted small mb-0 p-2">No equipment match your filters.</p>';

                const fmt = (shown, tot) =>
                    shown < tot ? `${shown} of ${tot} items` : `${tot} item${tot === 1 ? '' : 's'}`;
                els.equipmentCount.textContent = fmt(equipment.length, total);

                wireNodeEvents(els.equipmentSection);
                wireLoadMore(els.equipmentSection, 'equipment');
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

                try {
                    const res = await apiFetch(`/api/admin/manage-equipment/section?${p.toString()}`);
                    const rows = res.data || [];

                    els.equipmentSection.querySelectorAll('.btn-load-more-section').forEach(b => b
                        .parentElement.remove());
                    els.equipmentSection.insertAdjacentHTML('beforeend', rows.map(renderNode).join(''));

                    s.currentPage = res.pagination.current_page;
                    s.lastPage = res.pagination.last_page;
                    s.loadedCount += rows.length;

                    wireNodeEvents(els.equipmentSection);
                    els.equipmentSection.insertAdjacentHTML('beforeend', loadMoreHtml(section));
                    wireLoadMore(els.equipmentSection, section);

                    const fmt = (shown, tot) =>
                        shown < tot ? `${shown} of ${tot} items` : `${tot} item${tot === 1 ? '' : 's'}`;
                    els.equipmentCount.textContent = fmt(s.loadedCount, s.total);
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

            // ==================== SECTION COLLAPSE (header) ====================
            document.querySelectorAll('.section-header').forEach(header => {
                header.addEventListener('click', () => {
                    els.equipmentBody.classList.toggle('open');
                    header.classList.toggle('closed');
                });
            });

            // ==================== LAZY LOAD ITEMS ====================
            const loadItemsPage = async (equipmentId, page) => {
                return apiFetch(`/api/admin/equipment/${equipmentId}/items-list?page=${page}`);
            };

            const renderItems = (wrap, equipmentId, res, append = false) => {
                if (!wrap) return;
                const inner = wrap.querySelector('.inner');

                const cache = state.childCache[equipmentId] ||= {
                    rows: [],
                    currentPage: 0,
                    lastPage: 1
                };
                if (append) cache.rows.push(...(res.data || []));
                else cache.rows = res.data || [];
                cache.currentPage = res.pagination.current_page;
                cache.lastPage = res.pagination.last_page;

                const nodesHtml = cache.rows.map(renderNode).join('');
                const lmHtml = cache.currentPage < cache.lastPage ?
                    `<button class="btn btn-sm btn-outline-secondary btn-load-more my-2" data-id="${equipmentId}">
               <i class="bi bi-arrow-down-circle me-1"></i>Load more items (${cache.rows.length}/${res.pagination.total})
             </button>` :
                    '';

                inner.innerHTML = (cache.rows.length ?
                    nodesHtml :
                    '<p class="text-muted small mb-2 p-2">No items match your filters.</p>') + lmHtml;

                wrap.classList.add('open');
                wireNodeEvents(inner);

                const lm = inner.querySelector('.btn-load-more');
                if (lm) lm.addEventListener('click', async () => {
                    lm.disabled = true;
                    try {
                        const next = await loadItemsPage(equipmentId, cache.currentPage + 1);
                        renderItems(wrap, equipmentId, next, true);
                    } catch (e) {
                        console.error(e);
                        if (typeof showToast === 'function') showToast('Failed to load more items',
                            'error');
                        lm.disabled = false;
                    }
                });
            };

            const renderItemsFromCache = (wrap, equipmentId, cache) => {
                const inner = wrap.querySelector('.inner');
                if (!inner) return;

                const nodesHtml = cache.rows.map(renderNode).join('');
                const lmHtml = cache.currentPage < cache.lastPage ?
                    `<button class="btn btn-sm btn-outline-secondary btn-load-more my-2" data-id="${equipmentId}">
               <i class="bi bi-arrow-down-circle me-1"></i>Load more items (${cache.rows.length}/${cache.lastPage * 10})
             </button>` :
                    '';

                inner.innerHTML = (cache.rows.length ?
                    nodesHtml :
                    '<p class="text-muted small mb-2 p-2">No items match your filters.</p>') + lmHtml;

                wireNodeEvents(inner);

                const lm = inner.querySelector('.btn-load-more');
                if (lm) lm.addEventListener('click', async () => {
                    lm.disabled = true;
                    try {
                        const next = await loadItemsPage(equipmentId, cache.currentPage + 1);
                        renderItems(wrap, equipmentId, next, true);
                    } catch (e) {
                        console.error(e);
                        if (typeof showToast === 'function') showToast('Failed to load more items',
                            'error');
                        lm.disabled = false;
                    }
                });
            };

            const toggleEquipment = async (equipmentId, btn) => {
                const nodeEl = btn.closest('.node');
                const wrap = nodeEl?.nextElementSibling;
                if (!wrap || !wrap.matches(`[data-children-for="${equipmentId}"]`)) return;

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
                    renderItemsFromCache(wrap, equipmentId, cache);
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

            // ==================== RESULTS OVERLAY ====================
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
                    const res = await apiFetch('/api/admin/manage-equipment');
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
                    els.equipmentSection.innerHTML = '<p class="text-danger small p-2">Failed to load.</p>';
                } finally {
                    hideLoading();
                }
            };

            const fetchFilteredResults = async () => {
                showLoading();
                try {
                    const q = buildFilterQuery();
                    const res = await apiFetch(`/api/admin/manage-equipment?${q}`);
                    const equipmentRows = res.data.equipment || [];
                    const itemRows = res.data.matching_items || [];

                    // Merge equipment rows + matching items into a single overlay list.
                    // Items carry equipment_name for context (already in formatItem).
                    const rows = [...equipmentRows, ...itemRows];

                    renderResults(rows,
                        `Filtered Results — ${rows.length} match${rows.length === 1 ? '' : 'es'}`,
                        'No equipment or items match the selected filters.');
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
                        `/api/admin/manage-equipment/search?q=${encodeURIComponent(q)}`);
                    const results = res.results || [];
                    // Flatten: result_type + payload
                    const rows = results.map(r => r.result_type === 'item' ? r.item : r.equipment);
                    renderResults(rows,
                        `Search Results — ${rows.length} match${rows.length === 1 ? '' : 'es'} for "${q}"`,
                        'No equipment found.');
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
                            await apiFetch(url, {
                                method: 'DELETE'
                            });
                            if (typeof showToast === 'function') showToast(
                                `${state.currentDeleteType === 'item' ? 'Item' : 'Equipment'} deleted successfully`,
                                'success');
                            delete state.childCache[state.currentDeleteId];
                            bootstrap.Modal.getInstance(document.getElementById(
                                'deleteConfirmationModal'))?.hide();
                            if (hasActiveFilters()) fetchFilteredResults();
                            else fetchIndexForAccordion();
                        } catch (e) {
                            console.error(e);
                            if (typeof showToast === 'function') showToast('Failed to delete',
                                'error');
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
                }, {
                    once: true
                });
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
                const p = new URLSearchParams({
                    page
                });
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
                    const brand = row.brand ?
                        ` <span class="text-muted small">(${escapeHtml(row.brand)})</span>` : '';
                    return `
            <div class="form-check py-1 border-bottom">
              <input class="form-check-input ma-facility-checkbox" type="checkbox"
                     value="${row.equipment_id}" id="ma-fac-${row.equipment_id}" ${checked}>
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
                        if (cb.checked) maState.selected.set(id, {
                            name: row.equipment_name,
                            brand: row.brand
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
                    list.innerHTML = '<p class="text-muted small mb-0">No equipment selected yet.</p>';
                    return;
                }

                list.innerHTML = Array.from(maState.selected.entries()).map(([id, info]) => `
          <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
            <span class="small text-truncate" style="max-width: 85%;">${escapeHtml(info.name)}</span>
            <button type="button" class="btn btn-sm btn-link text-danger p-0 ma-remove-selected"
                    data-id="${id}" title="Remove" style="line-height: 1;">
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
                const execBtn = document.getElementById('executeMassAssignBtn');
                const confirmBtn = document.getElementById('confirmAssignBtn');
                if (!searchInput || !execBtn || !confirmBtn) return;

                searchInput.addEventListener('input', () => {
                    clearTimeout(maState.searchTimer);
                    const q = searchInput.value.trim();
                    searchClear.style.display = q ? 'block' : 'none';
                    maState.searchTimer = setTimeout(() => {
                        maState.search = q;
                        maLoadPage(1);
                    }, 300);
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
                        maState.selected.set(r.equipment_id, {
                            name: r.equipment_name,
                            brand: r.brand
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
                    const deptSel = document.getElementById('departmentSingleSelect');
                    const deptId = deptSel?.value;
                    if (maState.selected.size === 0) return showToast(
                        'Please select at least one equipment', 'error');
                    if (!deptId) return showToast('Please select a department', 'error');

                    maState.pendingAssignment = {
                        ids: Array.from(maState.selected.keys()),
                        deptId: parseInt(deptId),
                        deptName: deptSel.options[deptSel.selectedIndex].text,
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
                            if (backdrops.length) backdrops[backdrops.length - 1].style.zIndex =
                                String(massAssignZ + 50);
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
                        await apiFetch('/api/admin/equipment/mass-assign-departments', {
                            method: 'POST',
                            body: JSON.stringify({
                                equipment_ids: maState.pendingAssignment.ids,
                                department_id: maState.pendingAssignment.deptId,
                            }),
                        });
                        showToast(
                            `Assigned ${maState.pendingAssignment.ids.length} equipment to ${maState.pendingAssignment.deptName}`,
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

                if (searchInput) searchInput.value = '';
                if (searchClear) searchClear.style.display = 'none';
                if (deptSel) deptSel.value = '';
                if (selList) selList.innerHTML =
                    '<p class="text-muted small mb-0">No equipment selected yet.</p>';
                if (selCount) selCount.textContent = '0';

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

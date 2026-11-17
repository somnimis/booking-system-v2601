@extends('layouts.admin')

@section('title', 'Add Equipment')

@section('content')
    <link rel="stylesheet" href="{{ asset('css/admin/resource-form.css') }}">

    <main id="main">
        <div class="container main-content mb-5" style="max-width: 1100px; margin-left: auto; margin-right: auto;"
            x-data="equipmentForm()" x-cloak>

            <div class="bg-white border rounded-3 admin-livewire-form" style="border-color: #dee2e6;">

                <style>
                    .admin-livewire-form .form-page-title {
                        font-family: "Fraunces", Georgia, serif;
                        font-weight: 900;
                        font-size: 1.25rem;
                        margin: 0;
                        color: #fff;
                    }

                    [x-cloak] {
                        display: none !important;
                    }

                    .dropzone {
                        min-height: 220px;
                        display: flex;
                        flex-direction: column;
                        justify-content: center;
                        align-items: center;
                    }

                    label.required::after {
                        content: " *";
                        color: red;
                    }

                    #barcode {
                        background-color: #e9ecef;
                        cursor: not-allowed;
                        pointer-events: none;
                    }
                </style>

                {{-- Page header --}}
                <div class="d-flex justify-content-between align-items-center p-3"
                    style="background: #045191; border-top-left-radius: 0.375rem; border-top-right-radius: 0.375rem;">
                    <h4 class="form-page-title">Add New Equipment</h4>
                    <a href="{{ url('/admin/manage-equipment') }}" class="btn btn-sm btn-light">
                        <i class="bi bi-arrow-left me-1"></i> Back to Equipment
                    </a>
                </div>

                <div class="p-4">
                    <form id="addEquipmentForm">

                        {{-- ============ PHOTOS | INVENTORY ============ --}}
                        <div class="row mb-3 d-flex align-items-stretch">
                            <div class="col-md-6 mb-3 mb-md-0 d-flex">
                                <div class="card w-100">
                                    <div class="card-header"><strong>Equipment Photos</strong></div>
                                    <div class="card-body">
                                        <div class="dropzone border p-4 text-center" id="equipmentPhotosDropzone"
                                            style="cursor: pointer;">
                                            <i class="bi bi-images fs-1 text-muted"></i>
                                            <p class="mt-2 mb-0">Drag &amp; drop equipment photos here or click to
                                                browse</p>
                                            <input type="file" id="equipmentPhotos" class="d-none" multiple
                                                accept="image/*">
                                        </div>
                                        <small class="text-muted mt-2 d-block">
                                            Max 5 photos. Uploads run in parallel after saving.
                                        </small>
                                        <div id="photosPreview" class="d-flex flex-wrap gap-2 mt-3"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 d-flex">
                                <div class="card w-100">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <strong>Inventory Items</strong>
                                        <button type="button" class="btn btn-sm btn-primary" id="addItemBtn">
                                            <i class="bi bi-plus-lg me-1"></i> Add Item
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <div id="itemsContainer" style="max-height: 320px; overflow-y: auto;">
                                            <p class="text-muted mb-0">No items added yet. Click "Add Item" to track
                                                individual equipment pieces.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ============ EQUIPMENT DETAILS ============ --}}
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header"><strong>Equipment Details</strong></div>
                                    <div class="card-body row g-3">

                                        <div class="col-md-6">
                                            <label for="equipmentName" class="form-label required">Equipment Name</label>
                                            <input type="text" class="form-control" id="equipmentName" required
                                                maxlength="100" placeholder="e.g. Portable Speaker">
                                        </div>

                                        <div class="col-md-6">
                                            <label for="brand" class="form-label">Brand</label>
                                            <input type="text" class="form-control" id="brand" maxlength="80"
                                                placeholder="e.g. JBL">
                                        </div>

                                        <div class="col-12">
                                            <label for="description" class="form-label">Description</label>
                                            <textarea class="form-control" id="description" rows="2" maxlength="255"
                                                placeholder="Short description of the equipment..."></textarea>
                                            <small class="text-muted d-block text-end mt-1" id="descriptionWordCount">0/255
                                                characters</small>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="category" class="form-label required">Category</label>
                                            <select class="form-select" id="category" required>
                                                <option value="">Select Category</option>
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="storageLocation" class="form-label required">Storage
                                                Location</label>
                                            <input type="text" class="form-control" id="storageLocation" required
                                                maxlength="50" placeholder="e.g. Storage Room A">
                                        </div>

                                        <div class="col-md-6">
                                            <label for="companyFee" class="form-label required">Rental Fee (₱)</label>
                                            <div class="input-group">
                                                <span class="input-group-text">₱</span>
                                                <input type="number" class="form-control" id="companyFee" min="0"
                                                    step="0.01" required placeholder="0.00">
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="rateType" class="form-label required">Rate Type</label>
                                            <select class="form-select" id="rateType" required>
                                                <option value="">Select Rate Type</option>
                                                <option value="Per Hour">Per Hour</option>
                                                <option value="Per Event">Per Event</option>
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label required">Availability Status</label>
                                            <div class="department-checkboxes" id="availabilityStatus">
                                                <template x-if="statuses.length === 0">
                                                    <div class="department-checkboxes-loading">
                                                        <span class="spinner-border spinner-border-sm me-2"></span>
                                                        Loading statuses…
                                                    </div>
                                                </template>
                                                <template x-for="st in statuses" :key="st.status_id">
                                                    <label class="status-item"
                                                        :class="{ 'is-active': statusId == st.status_id }">
                                                        <input type="radio" class="form-check-input me-2"
                                                            name="availability_status" :value="st.status_id"
                                                            x-model="statusId">
                                                        <span x-text="st.status_name"></span>
                                                    </label>
                                                </template>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label required">Owning Departments</label>
                                            <div class="department-checkboxes" id="departmentCheckboxes">
                                                <template x-if="departments.length === 0">
                                                    <div class="department-checkboxes-loading">
                                                        <span class="spinner-border spinner-border-sm me-2"></span>
                                                        Loading departments…
                                                    </div>
                                                </template>
                                                <template x-for="dep in departments" :key="dep.department_id">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox"
                                                            :value="dep.department_id" :id="`dep_${dep.department_id}`"
                                                            x-model="departmentIds">
                                                        <label class="form-check-label" :for="`dep_${dep.department_id}`"
                                                            x-text="dep.department_name"></label>
                                                    </div>
                                                </template>
                                            </div>
                                            <small class="text-muted">Select one or more departments.</small>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>

                    </form>
                </div>
                {{-- /.p-4 --}}

            </div>
            {{-- /.admin-livewire-form --}}

            {{-- Action buttons OUTSIDE the form shell --}}
            <div class="bg-white border rounded-3 p-3 mt-3 d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary" id="cancelBtn">Discard</button>
                <button type="submit" form="addEquipmentForm" class="btn btn-primary" id="addEquipmentBtn">
                    <span class="spinner-border spinner-border-sm d-none me-1" id="addEquipmentSpinner"></span>
                    <span id="addEquipmentBtnText">Add New Equipment</span>
                </button>
            </div>

            {{-- ===================== MODALS ===================== --}}

            {{-- Inventory Item Modal --}}
            <div class="modal fade" id="inventoryItemModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Add Inventory Item</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="itemForm">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <label class="form-label fw-bold">Item Photo</label>
                                        <button class="btn btn-sm btn-danger d-none" id="removePhotoBtn" type="button">
                                            <i class="bi bi-trash"></i> Remove
                                        </button>
                                    </div>
                                    <div class="dropzone border p-4 text-center" id="itemPhotoDropzone"
                                        style="cursor: pointer; min-height: 140px;">
                                        <i class="bi bi-image fs-1 text-muted"></i>
                                        <p class="mt-2 mb-0">Click to upload item photo</p>
                                        <input type="file" id="itemPhoto" class="d-none" accept="image/*">
                                    </div>
                                    <div id="itemPhotoPreview" class="mt-3 text-center"></div>
                                </div>

                                <div class="mb-3">
                                    <label for="itemName" class="form-label required">Item Name</label>
                                    <input type="text" class="form-control" id="itemName" maxlength="50"
                                        placeholder="Enter item name" required>
                                </div>

                                <div class="mb-3">
                                    <label for="itemCondition" class="form-label required">Condition</label>
                                    <select class="form-select" id="itemCondition" required>
                                        <option value="">Select Condition</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="barcode" class="form-label">Barcode Number</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="barcode" readonly disabled>
                                        <button class="btn btn-outline-secondary" type="button" id="generateBarcodeBtn">
                                            <i class="bi bi-arrow-repeat"></i> Generate
                                        </button>
                                    </div>
                                    <small class="text-muted">Click Generate to create a unique scannable barcode.</small>
                                </div>

                                <div class="mb-0">
                                    <label for="itemNotes" class="form-label">Item Notes</label>
                                    <textarea class="form-control" id="itemNotes" rows="3" maxlength="100"
                                        placeholder="Additional notes about this item"></textarea>
                                    <small class="text-muted d-block text-end mt-1" id="notesWordCount">0/100
                                        characters</small>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="saveItemBtn">Save Item</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Delete Item Modal --}}
            <div class="modal fade" id="deleteItemModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Confirm Item Deletion</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            Are you sure you want to delete this item? The change will apply when you click
                            "Add New Equipment".
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-danger" id="confirmDeleteItemBtn">Delete Item</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Reset Confirmation Modal --}}
            <div class="modal fade" id="cancelConfirmationModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Discard Changes</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            Are you sure you want to cancel? Unsaved changes will be lost.
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-danger" id="confirmCancelBtn">Confirm</button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        {{-- /.container --}}
    </main>
@endsection

@section('scripts')
    <script>
        function equipmentForm() {
            return {
                departments: [],
                departmentIds: [],
                statuses: [],
                statusId: '',

                async init() {
                    await this.loadDepartments();
                    await this.loadStatuses();
                },

                async loadStatuses() {
                    try {
                        const res = await fetch('/api/availability-statuses', {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
                        if (!res.ok) return;
                        const data = await res.json();
                        this.statuses = Array.isArray(data) ? data : (data.data || []);
                    } catch (e) {
                        console.error('loadStatuses', e);
                    }
                },

                async loadDepartments() {
                    try {
                        const res = await fetch('/api/departments', {
                            headers: {
                                'Authorization': `Bearer ${localStorage.getItem('adminToken')}`,
                                'Accept': 'application/json'
                            }
                        });
                        if (!res.ok) return;
                        const data = await res.json();
                        this.departments = Array.isArray(data) ? data : (data.data || []);
                    } catch (e) {
                        console.error('loadDepartments', e);
                    }
                }
            };
        }

        document.addEventListener('DOMContentLoaded', function() {
            // ---- Auth gate ----
            const token = localStorage.getItem('adminToken');
            if (!token) {
                window.location.href = '/admin/login';
                return;
            }

            // ============================================================
            // STATE
            // ============================================================
            // Equipment photos staged for parallel upload after create.
            // Each: { tempId, file, objectUrl }
            window.pendingEquipmentPhotos = [];

            // Items staged before equipment is created.
            // Each: { tempId, item_name, condition_id, item_notes, file|null, objectUrl|null, condition_name }
            window.pendingItems = [];

            // Tracks editing: null = creating; otherwise index in pendingItems
            window.editingItemIndex = null;

            // Barcode is intentionally omitted from the UI — server generates it.

            // ============================================================
            // DROPDOWN LOADERS
            // ============================================================
            async function populateDropdown(elementId, url, idKey, nameKey, headers = {
                'Accept': 'application/json'
            }) {
                try {
                    const res = await fetch(url, {
                        headers
                    });
                    if (!res.ok) {
                        console.error(`Failed to fetch ${elementId}`, res.status);
                        return;
                    }
                    const data = await res.json();
                    const list = Array.isArray(data) ? data : (data.data || []);
                    const select = document.getElementById(elementId);
                    if (!select) return;
                    while (select.options.length > 1) select.remove(1);
                    list.forEach(row => {
                        const opt = document.createElement('option');
                        opt.value = row[idKey];
                        opt.textContent = row[nameKey];
                        select.appendChild(opt);
                    });
                } catch (e) {
                    console.error(`populateDropdown ${elementId}`, e);
                }
            }

            (async function initDropdowns() {
                const authHeaders = {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                };
                await populateDropdown('category', '/api/equipment-categories', 'category_id',
                    'category_name');
                await populateDropdown('itemCondition', '/api/conditions', 'condition_id', 'condition_name',
                    authHeaders);
            })();

            // ============================================================
            // EQUIPMENT PHOTOS
            // ============================================================
            const dropzone = document.getElementById('equipmentPhotosDropzone');
            const fileInput = document.getElementById('equipmentPhotos');
            const photosPreview = document.getElementById('photosPreview');
            const MAX_PHOTOS = 5;

            function renderPhotos() {
                photosPreview.innerHTML = '';
                window.pendingEquipmentPhotos.forEach((entry) => {
                    const tile = document.createElement('div');
                    tile.className = 'photo-preview';

                    const img = document.createElement('img');
                    img.src = entry.objectUrl;
                    tile.appendChild(img);

                    // Primary Star (z-index ensures it stays on top)
                    const star = document.createElement('button');
                    star.type = 'button';
                    star.className = 'primary-star' + (entry.image_type === 'Primary' ? ' is-primary' : '');
                    star.innerHTML = '<i class="bi bi-star-fill"></i>';
                    star.title = 'Set as primary photo';
                    star.style.zIndex = '2'; // Force it above the image
                    star.addEventListener('click', (e) => {
                        e.preventDefault();
                        if (entry.image_type === 'Primary') return;
                        window.pendingEquipmentPhotos.forEach(p => p.image_type = 'Secondary');
                        entry.image_type = 'Primary';
                        renderPhotos();
                    });
                    tile.appendChild(star);

                    // Action Buttons (Up, Delete, Down)
                    const actions = document.createElement('div');
                    actions.className = 'photo-actions';
                    actions.style.zIndex = '2'; // Force above image

                    [
                        ['bi-arrow-up', () => movePhoto(entry.tempId, -1)],
                        ['bi-x', () => {
                            URL.revokeObjectURL(entry.objectUrl);
                            window.pendingEquipmentPhotos = window.pendingEquipmentPhotos.filter(p => p
                                .tempId !== entry.tempId);

                            // If we deleted the primary, promote the first remaining image
                            if (entry.image_type === 'Primary' && window.pendingEquipmentPhotos.length >
                                0) {
                                window.pendingEquipmentPhotos[0].image_type = 'Primary';
                            }

                            // Recalculate sort order
                            window.pendingEquipmentPhotos.forEach((p, i) => p.sort_order = i);
                            renderPhotos();
                        }],
                        ['bi-arrow-down', () => movePhoto(entry.tempId, 1)]
                    ].forEach(([icon, handler]) => {
                        const b = document.createElement('button');
                        b.type = 'button';
                        b.innerHTML = `<i class="bi ${icon}"></i>`;
                        b.addEventListener('click', (e) => {
                            e.preventDefault();
                            handler();
                        });
                        actions.appendChild(b);
                    });

                    tile.appendChild(actions);
                    photosPreview.appendChild(tile);
                });
            }

            function movePhoto(tempId, delta) {
                const idx = window.pendingEquipmentPhotos.findIndex(e => e.tempId === tempId);
                if (idx === -1) return;
                const newIdx = idx + delta;
                if (newIdx < 0 || newIdx >= window.pendingEquipmentPhotos.length) return;
                const [item] = window.pendingEquipmentPhotos.splice(idx, 1);
                window.pendingEquipmentPhotos.splice(newIdx, 0, item);
                window.pendingEquipmentPhotos.forEach((e, i) => e.sort_order = i);
                renderPhotos();
            }

            function handleFiles(files) {
                for (const file of files) {
                    if (!file.type.startsWith('image/')) {
                        window.showToast('Please upload only image files.', 'error');
                        continue;
                    }
                    if (window.pendingEquipmentPhotos.length >= MAX_PHOTOS) {
                        window.showToast('Maximum of 5 photos allowed.', 'error');
                        break;
                    }

                    const isFirst = window.pendingEquipmentPhotos.length === 0;

                    window.pendingEquipmentPhotos.push({
                        tempId: 'tmp_' + Date.now() + '_' + Math.random().toString(36).slice(2, 7),
                        file,
                        objectUrl: URL.createObjectURL(file),
                        image_type: isFirst ? 'Primary' : 'Secondary',
                        sort_order: window.pendingEquipmentPhotos.length
                    });
                }
                renderPhotos();
            }

            if (dropzone && fileInput) {
                dropzone.addEventListener('click', () => fileInput.click());
                fileInput.addEventListener('change', function() {
                    handleFiles(this.files);
                    this.value = '';
                });
                dropzone.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    dropzone.classList.add('border-primary');
                });
                dropzone.addEventListener('dragleave', () => dropzone.classList.remove('border-primary'));
                dropzone.addEventListener('drop', (e) => {
                    e.preventDefault();
                    dropzone.classList.remove('border-primary');
                    if (e.dataTransfer.files.length) handleFiles(e.dataTransfer.files);
                });
            }

            // ============================================================
            // DESCRIPTION COUNTER
            // ============================================================
            const description = document.getElementById('description');
            const descriptionWordCount = document.getElementById('descriptionWordCount');
            if (description && descriptionWordCount) {
                description.addEventListener('input', function() {
                    if (this.value.length > 255) this.value = this.value.substring(0, 255);
                    descriptionWordCount.textContent = `${this.value.length}/255 characters`;
                });
            }

            const itemNotes = document.getElementById('itemNotes');
            const notesWordCount = document.getElementById('notesWordCount');
            if (itemNotes && notesWordCount) {
                itemNotes.addEventListener('input', function() {
                    if (this.value.length > 100) this.value = this.value.substring(0, 100);
                    notesWordCount.textContent = `${this.value.length}/100 characters`;
                });
            }

            // ============================================================
            // ITEM MODAL
            // ============================================================
            const inventoryItemModal = new bootstrap.Modal('#inventoryItemModal');
            const deleteItemModal = new bootstrap.Modal('#deleteItemModal');

            const itemNameInput = document.getElementById('itemName');
            const itemConditionSelect = document.getElementById('itemCondition');
            const itemPhotoInput = document.getElementById('itemPhoto');
            const itemPhotoDropzone = document.getElementById('itemPhotoDropzone');
            const itemPhotoPreview = document.getElementById('itemPhotoPreview');
            const removePhotoBtn = document.getElementById('removePhotoBtn');
            const saveItemBtn = document.getElementById('saveItemBtn');
            const itemsContainer = document.getElementById('itemsContainer');

            let stagedItemFile = null;
            let stagedItemObjectUrl = null;
            let pendingDeleteIndex = null;
            let currentItemBarcode = '';

            function resetItemModal() {
                itemNameInput.value = '';
                itemConditionSelect.value = '';
                itemNotes.value = '';
                notesWordCount.textContent = '0/100 characters';
                stagedItemFile = null;

                // Add these two lines to reset the barcode
                currentItemBarcode = '';
                document.getElementById('barcode').value = '';

                if (stagedItemObjectUrl) {
                    URL.revokeObjectURL(stagedItemObjectUrl);
                    stagedItemObjectUrl = null;
                }
                itemPhotoPreview.innerHTML = '';
                itemPhotoDropzone.style.display = 'block';
                removePhotoBtn.classList.add('d-none');
                document.querySelector('#inventoryItemModal .modal-title').textContent = 'Add Inventory Item';
                saveItemBtn.textContent = 'Save Item';
                window.editingItemIndex = null;
            }

            document.getElementById('addItemBtn').addEventListener('click', () => {
                resetItemModal();
                inventoryItemModal.show();
            });

            itemPhotoDropzone.addEventListener('click', () => itemPhotoInput.click());

            itemPhotoInput.addEventListener('change', function() {
                if (!this.files?.[0]) return;
                stagedItemFile = this.files[0];
                if (stagedItemObjectUrl) URL.revokeObjectURL(stagedItemObjectUrl);
                stagedItemObjectUrl = URL.createObjectURL(stagedItemFile);

                itemPhotoDropzone.style.display = 'none';
                removePhotoBtn.classList.remove('d-none');
                itemPhotoPreview.innerHTML =
                    `<img src="${stagedItemObjectUrl}" class="img-thumbnail" style="max-height: 150px;">`;
            });

            removePhotoBtn.addEventListener('click', () => {
                if (stagedItemObjectUrl) URL.revokeObjectURL(stagedItemObjectUrl);
                stagedItemObjectUrl = null;
                stagedItemFile = null;
                itemPhotoInput.value = '';
                itemPhotoPreview.innerHTML = '';
                itemPhotoDropzone.style.display = 'block';
                removePhotoBtn.classList.add('d-none');
            });

            function renderItems() {
                itemsContainer.innerHTML = '';
                if (window.pendingItems.length === 0) {
                    itemsContainer.innerHTML =
                        '<p class="text-muted mb-0">No items added yet. Click "Add Item" to track individual equipment pieces.</p>';
                    return;
                }

                window.pendingItems.forEach((item, index) => {
                    const card = document.createElement('div');
                    card.className = 'card mb-2';
                    card.innerHTML = `
                        <div class="card-body d-flex gap-2 align-items-center">
                            <div style="width:60px;height:60px;flex-shrink:0;">
                                <img src="${item.objectUrl || '/assets/placeholder-image.png'}"
                                     class="img-thumbnail"
                                     style="width:60px;height:60px;object-fit:cover;">
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">${escapeHtml(item.item_name)}</h6>
                                <span class="badge bg-secondary">${escapeHtml(item.condition_name)}</span>
                                ${item.item_notes ? `<p class="mb-0 mt-1 small text-muted">${escapeHtml(item.item_notes.substring(0,50))}${item.item_notes.length > 50 ? '…' : ''}</p>` : ''}
                            </div>
                            <div class="d-flex">
                                <button type="button" class="btn btn-sm btn-primary me-1" data-edit="${index}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-danger" data-delete="${index}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    `;
                    itemsContainer.appendChild(card);
                });

                itemsContainer.querySelectorAll('[data-edit]').forEach(btn => {
                    btn.addEventListener('click', () => openEditItem(parseInt(btn.dataset.edit, 10)));
                });
                itemsContainer.querySelectorAll('[data-delete]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        pendingDeleteIndex = parseInt(btn.dataset.delete, 10);
                        deleteItemModal.show();
                    });
                });
            }

            function escapeHtml(str) {
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function openEditItem(index) {
                const item = window.pendingItems[index];
                if (!item) return;

                itemNameInput.value = item.item_name;
                itemConditionSelect.value = item.condition_id;
                itemNotes.value = item.item_notes || '';
                notesWordCount.textContent = `${(item.item_notes || '').length}/100 characters`;

                currentItemBarcode = item.barcode_number || '';
                document.getElementById('barcode').value = currentItemBarcode;

                if (item.objectUrl) {
                    itemPhotoDropzone.style.display = 'none';
                    removePhotoBtn.classList.remove('d-none');
                    itemPhotoPreview.innerHTML =
                        `<img src="${item.objectUrl}" class="img-thumbnail" style="max-height: 150px;">`;
                    stagedItemObjectUrl = item.objectUrl;
                    stagedItemFile = item.file;
                } else {
                    itemPhotoPreview.innerHTML = '';
                    itemPhotoDropzone.style.display = 'block';
                    removePhotoBtn.classList.add('d-none');
                    stagedItemObjectUrl = null;
                    stagedItemFile = null;
                }

                window.editingItemIndex = index;
                document.querySelector('#inventoryItemModal .modal-title').textContent = 'Edit Inventory Item';
                saveItemBtn.textContent = 'Update Item';
                inventoryItemModal.show();
            }

            document.getElementById('generateBarcodeBtn').addEventListener('click', () => {
                const randomPart = Math.random().toString(36).substring(2, 12).toUpperCase();
                currentItemBarcode = `EQ-${randomPart}`;
                document.getElementById('barcode').value = currentItemBarcode;
            });

            saveItemBtn.addEventListener('click', () => {
                const name = (itemNameInput.value || '').trim();
                const conditionId = itemConditionSelect.value;
                const notes = (itemNotes.value || '').trim();

                if (!name) {
                    window.showToast('Item name is required.', 'error');
                    return;
                }
                if (!conditionId) {
                    window.showToast('Condition is required.', 'error');
                    return;
                }

                const conditionName = itemConditionSelect.options[itemConditionSelect.selectedIndex]
                    ?.text || '';

                const payload = {
                    item_name: name,
                    condition_id: conditionId,
                    item_notes: notes || null,
                    file: stagedItemFile,
                    objectUrl: stagedItemObjectUrl,
                    condition_name: conditionName,
                    barcode_number: currentItemBarcode
                };

                if (window.editingItemIndex !== null) {
                    // Preserve tempId + objectUrl if same file untouched
                    const prev = window.pendingItems[window.editingItemIndex];
                    payload.tempId = prev.tempId;
                    if (!stagedItemFile) {
                        payload.objectUrl = prev.objectUrl;
                        payload.file = prev.file;
                    }
                    window.pendingItems[window.editingItemIndex] = payload;
                } else {
                    payload.tempId = 'item_' + Date.now() + '_' + Math.random().toString(36).slice(2,
                        7);
                    window.pendingItems.push(payload);
                }

                renderItems();
                inventoryItemModal.hide();
                resetItemModal();
            });

            document.getElementById('confirmDeleteItemBtn').addEventListener('click', () => {
                if (pendingDeleteIndex === null) return;
                const item = window.pendingItems[pendingDeleteIndex];
                if (item?.objectUrl) URL.revokeObjectURL(item.objectUrl);
                window.pendingItems.splice(pendingDeleteIndex, 1);
                pendingDeleteIndex = null;
                deleteItemModal.hide();
                renderItems();
            });

            // ============================================================
            // CANCEL
            // ============================================================
            const cancelModal = new bootstrap.Modal('#cancelConfirmationModal');
            document.getElementById(
                'cancelBtn').addEventListener('click', () => cancelModal.show());
            document.getElementById(
                'confirmCancelBtn').addEventListener('click', () => {
                cancelModal.hide();
                window.location.href = '/admin/manage-equipment';
            });

            // ============================================================
            // SUBMIT
            // ============================================================
            const form = document.getElementById('addEquipmentForm');
            const submitBtn = document.getElementById('addEquipmentBtn');
            const spinner = document.getElementById('addEquipmentSpinner');
            const btnText = document.getElementById('addEquipmentBtnText');

            function setSubmitting(isSubmitting) {
                submitBtn.disabled = isSubmitting;
                spinner.classList.toggle('d-none', !isSubmitting);
                btnText.textContent = isSubmitting ? 'Processing…' : 'Add New Equipment';
            }

            function extractError(err, fallback) {
                if (!err) return fallback;
                if (err.errors) {
                    const firstKey = Object.keys(err.errors)[0];
                    if (firstKey && err.errors[firstKey].length) return err.errors[firstKey][0];
                }
                return err.message || fallback;
            }

            form.addEventListener('submit', async function(e) {
                e.preventDefault();

                const alpineEl = document.querySelector('[x-data]');
                const alpine = window.Alpine ? window.Alpine.$data(alpineEl) : null;
                const departmentIds = alpine?.departmentIds || [];

                const name = document.getElementById('equipmentName').value.trim();
                const storageLocation = document.getElementById('storageLocation').value.trim();
                const categoryId = document.getElementById('category').value;
                const companyFee = document.getElementById('companyFee').value;
                const rateType = document.getElementById('rateType').value;
                const statusId = alpine?.statusId || '';

                if (!name) return window.showToast('Equipment name is required.', 'error');
                if (!storageLocation) return window.showToast('Storage location is required.',
                    'error');
                if (!categoryId) return window.showToast('Category is required.', 'error');
                if (companyFee === '' || isNaN(parseFloat(companyFee))) return window.showToast(
                    'Rental fee is required.', 'error');
                if (!rateType) return window.showToast('Rate type is required.', 'error');
                if (!statusId) return window.showToast('Availability status is required.', 'error');
                if (departmentIds.length === 0) return window.showToast(
                    'Select at least one department.', 'error');

                setSubmitting(true);

                try {
                    // ---- 1. Create equipment ----
                    const payload = {
                        equipment_name: name,
                        description: document.getElementById('description').value.trim() ||
                            null,
                        brand: document.getElementById('brand').value.trim() || null,
                        storage_location: storageLocation,
                        category_id: categoryId,
                        base_fee: parseFloat(companyFee),
                        rate_type: rateType,
                        status_id: statusId,
                        departments: departmentIds.map(Number),
                        // items are created via /items after equipment exists (multipart with image)
                    };

                    const createRes = await fetch('/api/admin/equipment', {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });

                    if (!createRes.ok) {
                        const err = await createRes.json().catch(() => ({}));
                        throw new Error(extractError(err,
                            `Failed to create equipment (${createRes.status}).`));
                    }

                    const createJson = await createRes.json();
                    const equipmentId = createJson?.data?.equipment_id;
                    if (!equipmentId) throw new Error('Equipment created, but no ID was returned.');

                    // ---- 2. Upload equipment photos in parallel ----
                    if (window.pendingEquipmentPhotos.length > 0) {
                        const uploads = window.pendingEquipmentPhotos.map(entry => {
                            const fd = new FormData();
                            fd.append('image', entry.file);
                            fd.append('description', 'Equipment photo');
                            fd.append('image_type', entry.image_type);
                            fd.append('sort_order', entry.sort_order);

                            return fetch(`/api/admin/equipment/${equipmentId}/images`, {
                                    method: 'POST',
                                    headers: {
                                        'Authorization': `Bearer ${token}`,
                                        'Accept': 'application/json'
                                    },
                                    body: fd
                                }).then(async r => {
                                    if (!r.ok) {
                                        const text = await r.text().catch(() => '');
                                        throw new Error(`HTTP ${r.status}: ${text}`);
                                    }
                                    return r;
                                }).then(r => r.json().catch(() => ({
                                    success: true
                                })))
                                .catch(err => {
                                    // Dump the raw error to the browser console so you can inspect it
                                    console.error('Image Upload Raw Error:', err);
                                    throw err;
                                });
                        });

                        const results = await Promise.allSettled(uploads);
                        const failed = results.filter(r => r.status === 'rejected');

                        // BLOCK REDIRECT: If any image fails, throw an error to stop the flow
                        if (failed.length > 0) {
                            console.error('All upload results:', results);
                            throw new Error(
                                `Failed to upload ${failed.length} image(s). Check the console for details.`
                            );
                        }
                    }

                    // ---- 3. Create staged items sequentially (multipart per item) ----
                    for (const item of window.pendingItems) {
                        const fd = new FormData();
                        fd.append('item_name', item.item_name);
                        fd.append('condition_id', item.condition_id);
                        if (item.item_notes) fd.append('item_notes', item.item_notes);
                        if (item.file) fd.append('image', item.file);
                        if (item.barcode_number) fd.append('barcode_number', item.barcode_number);

                        const itemRes = await fetch(`/api/admin/equipment/${equipmentId}/items`, {
                            method: 'POST',
                            headers: {
                                'Authorization': `Bearer ${token}`,
                                'Accept': 'application/json'
                            },
                            body: fd
                        });

                        if (!itemRes.ok) {
                            const err = await itemRes.json().catch(() => ({}));
                            console.error('Item creation failed:', err);
                            window.showToast(`Item "${item.item_name}" failed: ` + extractError(err,
                                'unknown error'), 'error');
                        }
                    }

                    window.showToast('Equipment created successfully!', 'success');
                    setTimeout(() => {
                        window.location.href = '/admin/manage-equipment';
                    }, 1200);

                } catch (err) {
                    console.error(err);
                    window.showToast(err.message || 'Failed to create equipment.', 'error');
                } finally {
                    setSubmitting(false);
                }
            });
        });
    </script>
@endsection

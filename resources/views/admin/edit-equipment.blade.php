@extends('layouts.admin')

@section('title', 'Edit Equipment')

@section('content')
    <link rel="stylesheet" href="{{ asset('css/admin/resource-form.css') }}">

    <main id="main">
        <div class="container main-content mb-5" style="max-width: 1100px; margin-left: auto; margin-right: auto;"
            x-data="equipmentForm({{ $equipmentId }})" x-cloak>

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
                    <h4 class="form-page-title">Edit Equipment</h4>
                    <a href="{{ url('/admin/manage-equipment') }}" class="btn btn-sm btn-light">
                        <i class="bi bi-arrow-left me-1"></i> Back to Equipment
                    </a>
                </div>

                <div class="p-4">
                    <form id="editEquipmentForm">

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
                                            <p class="text-muted mb-0">Loading items…</p>
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
                <button type="submit" form="editEquipmentForm" class="btn btn-primary" id="editEquipmentBtn">
                    <span class="spinner-border spinner-border-sm d-none me-1" id="editEquipmentSpinner"></span>
                    <span id="editEquipmentBtnText">Save Changes</span>
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
                            "Save Changes".
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
        function equipmentForm(equipmentId) {
            return {
                equipmentId: equipmentId,
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

            const equipmentId = document.querySelector('[x-data]').getAttribute('x-data').match(/\d+/)[0];

            // ============================================================
            // STATE
            // ============================================================
            // One flat array = exactly what the user sees. Array index IS the sort_order.
            // Each entry: { image_id (existing only), file (new only), objectUrl (new only),
            //               image_url (existing only), image_type: 'Primary' | 'Secondary' }
            window.photos = [];
            window.deletedImageIds = []; // Existing photos removed in the UI; deleted on save
            window.pendingItems = []; // Items state array
            window.deletedItemIds = []; // Items marked for deletion
            window.editingItemIndex = null;

            // ============================================================
            // DROPDOWN LOADERS
            // ============================================================
            async function populateDropdown(elementId, url, idKey, nameKey, headers = {
                'Accept': 'application/json'
            }, selectedValue = null) {
                try {
                    const res = await fetch(url, {
                        headers
                    });
                    if (!res.ok) return;
                    const data = await res.json();
                    const list = Array.isArray(data) ? data : (data.data || []);
                    const select = document.getElementById(elementId);
                    if (!select) return;
                    while (select.options.length > 1) select.remove(1);
                    list.forEach(row => {
                        const opt = document.createElement('option');
                        opt.value = row[idKey];
                        opt.textContent = row[nameKey];
                        if (selectedValue !== null && String(opt.value) === String(selectedValue)) {
                            opt.selected = true;
                        }
                        select.appendChild(opt);
                    });
                } catch (e) {
                    console.error(`populateDropdown ${elementId}`, e);
                }
            }

            // ============================================================
            // LOAD EXISTING EQUIPMENT DATA
            // ============================================================
            async function loadEquipmentData() {
                try {
                    const res = await fetch(`/api/equipment/${equipmentId}`, {
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Accept': 'application/json'
                        }
                    });
                    if (!res.ok) throw new Error('Failed to load equipment');
                    const responseData = await res.json();
                    const data = responseData.data;

                    // Populate Text Fields
                    document.getElementById('equipmentName').value = data.equipment_name || '';
                    document.getElementById('brand').value = data.brand || '';
                    document.getElementById('description').value = data.description || '';
                    document.getElementById('descriptionWordCount').textContent =
                        `${(data.description || '').length}/255 characters`;
                    document.getElementById('storageLocation').value = data.storage_location || '';
                    document.getElementById('companyFee').value = data.base_fee || '';

                    // Populate Rate Type
                    document.getElementById('rateType').value = data.rate_type || 'Per Hour';

                    // Populate Dropdowns
                    await populateDropdown('category', '/api/equipment-categories', 'category_id',
                        'category_name', {
                            'Accept': 'application/json'
                        }, data.category_id);
                    await populateDropdown('itemCondition', '/api/conditions', 'condition_id',
                        'condition_name', {
                            'Authorization': `Bearer ${token}`,
                            'Accept': 'application/json'
                        });

                    // Populate Alpine State (Departments & Status)
                    const alpineEl = document.querySelector('[x-data]');
                    const alpine = window.Alpine.$data(alpineEl);

                    alpine.statusId = data.status_id;
                    if (data.departments) {
                        alpine.departmentIds = data.departments.map(d => d.department_id);
                    }

                    // Populate Existing Equipment Photos
                    if (data.images) {
                        window.photos = data.images
                            .slice()
                            .sort((a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0))
                            .map(img => ({
                                image_id: img.image_id,
                                image_url: img.image_url,
                                image_type: img.image_type === 'Primary' ? 'Primary' : 'Secondary'
                            }));
                        renderPhotos();
                    }
                    // Populate Items
                    if (data.items) {
                        window.pendingItems = data.items.map(item => ({
                            item_id: item.item_id,
                            item_name: item.item_name,
                            condition_id: item.condition_id,
                            condition_name: item.condition ? item.condition.condition_name : '',
                            item_notes: item.item_notes,
                            barcode_number: item.barcode_number,
                            file: null,
                            objectUrl: item.image_url,
                            is_existing: true
                        }));
                        renderItems();
                    }
                } catch (err) {
                    console.error(err);
                    window.showToast('Failed to load equipment details.', 'error');
                }
            }

            // ============================================================
            // EQUIPMENT PHOTOS
            // ============================================================
            const dropzone = document.getElementById('equipmentPhotosDropzone');
            const fileInput = document.getElementById('equipmentPhotos');
            const photosPreview = document.getElementById('photosPreview');
            const MAX_PHOTOS = 5;

            function renderPhotos() {
                photosPreview.innerHTML = '';

                window.photos.forEach((photo, index) => {
                    const tile = document.createElement('div');
                    tile.className = 'photo-preview';

                    const img = document.createElement('img');
                    img.src = photo.objectUrl || photo.image_url;
                    tile.appendChild(img);

                    // Primary star: only one photo can be Primary. Clicking the current Primary un-stars it.
                    const star = document.createElement('button');
                    star.type = 'button';
                    star.className = 'primary-star' + (photo.image_type === 'Primary' ? ' is-primary' : '');
                    star.innerHTML = '<i class="bi bi-star-fill"></i>';
                    star.title = 'Set as primary photo';
                    star.style.zIndex = '2';
                    star.addEventListener('click', (e) => {
                        e.preventDefault();
                        const wasPrimary = photo.image_type === 'Primary';
                        window.photos.forEach(p => p.image_type = 'Secondary');
                        if (!wasPrimary) photo.image_type = 'Primary';
                        renderPhotos();
                    });
                    tile.appendChild(star);

                    // Action buttons: move up, remove, move down
                    const actions = document.createElement('div');
                    actions.className = 'photo-actions';
                    actions.style.zIndex = '2';

                    [
                        ['bi-arrow-up', () => movePhoto(index, -1)],
                        ['bi-x', () => removePhoto(index)],
                        ['bi-arrow-down', () => movePhoto(index, 1)]
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

            function movePhoto(index, delta) {
                const newIdx = index + delta;
                if (newIdx < 0 || newIdx >= window.photos.length) return;
                const [photo] = window.photos.splice(index, 1);
                window.photos.splice(newIdx, 0, photo);
                renderPhotos();
            }

            function removePhoto(index) {
                const [photo] = window.photos.splice(index, 1);
                if (!photo) return;
                if (photo.image_id) {
                    window.deletedImageIds.push(photo.image_id); // actually deleted on save
                } else if (photo.objectUrl) {
                    URL.revokeObjectURL(photo.objectUrl);
                }
                renderPhotos();
            }

            function handleFiles(files) {
                for (const file of files) {
                    if (!file.type.startsWith('image/')) {
                        window.showToast('Please upload only image files.', 'error');
                        continue;
                    }
                    if (window.photos.length >= MAX_PHOTOS) {
                        window.showToast('Maximum of 5 photos allowed.', 'error');
                        break;
                    }
                    window.photos.push({
                        file,
                        objectUrl: URL.createObjectURL(file),
                        image_type: 'Secondary'
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
                currentItemBarcode = '';
                document.getElementById('barcode').value = '';

                if (stagedItemObjectUrl && stagedItemObjectUrl.startsWith('blob:')) {
                    URL.revokeObjectURL(stagedItemObjectUrl);
                }
                stagedItemObjectUrl = null;
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
                if (stagedItemObjectUrl && stagedItemObjectUrl.startsWith('blob:')) URL.revokeObjectURL(
                    stagedItemObjectUrl);
                stagedItemObjectUrl = URL.createObjectURL(stagedItemFile);

                itemPhotoDropzone.style.display = 'none';
                removePhotoBtn.classList.remove('d-none');
                itemPhotoPreview.innerHTML =
                    `<img src="${stagedItemObjectUrl}" class="img-thumbnail" style="max-height: 150px;">`;
            });

            removePhotoBtn.addEventListener('click', () => {
                if (stagedItemObjectUrl && stagedItemObjectUrl.startsWith('blob:')) URL.revokeObjectURL(
                    stagedItemObjectUrl);
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
                                <img src="${item.objectUrl || '/assets/placeholder-image.png'}" class="img-thumbnail" style="width:60px;height:60px;object-fit:cover;">
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">${escapeHtml(item.item_name)}</h6>
                                <span class="badge bg-secondary">${escapeHtml(item.condition_name)}</span>
                                ${item.item_notes ? `<p class="mb-0 mt-1 small text-muted">${escapeHtml(item.item_notes.substring(0,50))}${item.item_notes.length > 50 ? '…' : ''}</p>` : ''}
                            </div>
                            <div class="d-flex">
                                <button type="button" class="btn btn-sm btn-primary me-1" data-edit="${index}"><i class="bi bi-pencil"></i></button>
                                <button type="button" class="btn btn-sm btn-danger" data-delete="${index}"><i class="bi bi-trash"></i></button>
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
                return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g,
                    '&quot;').replace(/'/g, '&#039;');
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

                if (!name) return window.showToast('Item name is required.', 'error');
                if (!conditionId) return window.showToast('Condition is required.', 'error');

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
                    const prev = window.pendingItems[window.editingItemIndex];
                    payload.tempId = prev.tempId;
                    payload.item_id = prev.item_id;
                    payload.is_existing = prev.is_existing;
                    if (!stagedItemFile) {
                        payload.objectUrl = prev.objectUrl;
                        payload.file = prev.file;
                    }
                    window.pendingItems[window.editingItemIndex] = payload;
                } else {
                    payload.tempId = 'item_' + Date.now() + '_' + Math.random().toString(36).slice(2, 7);
                    payload.is_existing = false;
                    window.pendingItems.push(payload);
                }

                renderItems();
                inventoryItemModal.hide();
                resetItemModal();
            });

            document.getElementById('confirmDeleteItemBtn').addEventListener('click', () => {
                if (pendingDeleteIndex === null) return;
                const item = window.pendingItems[pendingDeleteIndex];

                // Track for deletion if it exists in the database
                if (item.is_existing && item.item_id) {
                    window.deletedItemIds.push(item.item_id);
                }

                if (item?.objectUrl && item.objectUrl.startsWith('blob:')) {
                    URL.revokeObjectURL(item.objectUrl);
                }

                window.pendingItems.splice(pendingDeleteIndex, 1);
                pendingDeleteIndex = null;
                deleteItemModal.hide();
                renderItems();
            });

            // ============================================================
            // CANCEL
            // ============================================================
            const cancelModal = new bootstrap.Modal('#cancelConfirmationModal');
            document.getElementById('cancelBtn').addEventListener('click', () => cancelModal.show());
            document.getElementById('confirmCancelBtn').addEventListener('click', () => {
                cancelModal.hide();
                window.location.href = '/admin/manage-equipment';
            });

            // ============================================================
            // SUBMIT
            // ============================================================
            const form = document.getElementById('editEquipmentForm');
            const submitBtn = document.getElementById('editEquipmentBtn');
            const spinner = document.getElementById('editEquipmentSpinner');
            const btnText = document.getElementById('editEquipmentBtnText');

            function setSubmitting(isSubmitting) {
                submitBtn.disabled = isSubmitting;
                spinner.classList.toggle('d-none', !isSubmitting);
                btnText.textContent = isSubmitting ? 'Saving…' : 'Save Changes';
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
                if (!storageLocation) return window.showToast('Storage location is required.', 'error');
                if (!categoryId) return window.showToast('Category is required.', 'error');
                if (companyFee === '' || isNaN(parseFloat(companyFee))) return window.showToast(
                    'Rental fee is required.', 'error');
                if (!rateType) return window.showToast('Rate type is required.', 'error');
                if (!statusId) return window.showToast('Availability status is required.', 'error');
                if (departmentIds.length === 0) return window.showToast(
                    'Select at least one department.', 'error');

                setSubmitting(true);

                try {
                    // ---- 1. Update Equipment ----
                    const payload = {
                        equipment_name: name,
                        description: document.getElementById('description').value.trim() || null,
                        brand: document.getElementById('brand').value.trim() || null,
                        storage_location: storageLocation,
                        category_id: categoryId,
                        base_fee: parseFloat(companyFee),
                        rate_type: rateType,
                        status_id: statusId,
                        departments: departmentIds.map(Number),
                    };

                    const updateRes = await fetch(`/api/admin/equipment/${equipmentId}`, {
                        method: 'PUT',
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });

                    if (!updateRes.ok) {
                        const err = await updateRes.json().catch(() => ({}));
                        throw new Error(extractError(err,
                            `Failed to update equipment (${updateRes.status}).`));
                    }

                    // ---- 2. Delete photos the user removed ----
                    for (const imageId of window.deletedImageIds) {
                        const delRes = await fetch(`/api/admin/equipment/${equipmentId}/images/${imageId}`, {
                            method: 'DELETE',
                            headers: {
                                'Authorization': `Bearer ${token}`,
                                'Accept': 'application/json'
                            }
                        });
                        if (!delRes.ok && delRes.status !== 404) {
                            throw new Error(`Failed to delete image (${delRes.status}).`);
                        }
                    }
                    window.deletedImageIds = [];

                    // ---- 2b. Save the photo layout exactly as arranged ----
                    // sort_order = array index; image_type = whatever the star says.
                    const newUploads = [];
                    const existingLayout = [];

                    window.photos.forEach((photo, index) => {
                        if (photo.file) {
                            const fd = new FormData();
                            fd.append('image', photo.file);
                            fd.append('description', 'Equipment photo');
                            fd.append('image_type', photo.image_type);
                            fd.append('sort_order', index);

                            newUploads.push(fetch(`/api/admin/equipment/${equipmentId}/images`, {
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
                                return r.json();
                            }));
                        } else {
                            existingLayout.push({
                                image_id: photo.image_id,
                                sort_order: index,
                                image_type: photo.image_type
                            });
                        }
                    });

                    if (newUploads.length > 0) {
                        const results = await Promise.allSettled(newUploads);
                        const failed = results.filter(r => r.status === 'rejected');
                        if (failed.length > 0) {
                            throw new Error(`Failed to upload ${failed.length} image(s).`);
                        }
                    }

                    if (existingLayout.length > 0) {
                        const reorderRes = await fetch(`/api/admin/equipment/${equipmentId}/images/reorder`, {
                            method: 'POST',
                            headers: {
                                'Authorization': `Bearer ${token}`,
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ images: existingLayout })
                        });
                        if (!reorderRes.ok) {
                            const err = await reorderRes.json().catch(() => ({}));
                            throw new Error(extractError(err, 'Failed to save photo order.'));
                        }
                    }

                    // ---- 3. Handle Item Deletions ----
                    for (const itemId of window.deletedItemIds) {
                        await fetch(`/api/admin/equipment/${equipmentId}/items/${itemId}`, {
                            method: 'DELETE',
                            headers: {
                                'Authorization': `Bearer ${token}`,
                                'Accept': 'application/json'
                            }
                        }).catch(console.error);
                    }

                    // ---- 4. Create / Update Items ----
                    for (const item of window.pendingItems) {
                        const fd = new FormData();
                        fd.append('item_name', item.item_name);
                        fd.append('condition_id', item.condition_id);
                        if (item.item_notes) fd.append('item_notes', item.item_notes);
                        if (item.file) fd.append('image', item.file);
                        if (item.barcode_number) fd.append('barcode_number', item.barcode_number);

                        const isUpdate = !!item.item_id;
                        const url = isUpdate ?
                            `/api/admin/equipment/${equipmentId}/items/${item.item_id}` :
                            `/api/admin/equipment/${equipmentId}/items`;

                        // Need to fake the method for multipart/form-data
                        if (isUpdate) fd.append('_method', 'PUT');

                        const itemRes = await fetch(url, {
                            method: 'POST', // Use POST but _method=PUT allows Laravel to parse the file
                            headers: {
                                'Authorization': `Bearer ${token}`,
                                'Accept': 'application/json'
                            },
                            body: fd
                        });

                        if (!itemRes.ok) {
                            const err = await itemRes.json().catch(() => ({}));
                            console.error('Item save failed:', err);
                            window.showToast(`Item "${item.item_name}" failed: ` + extractError(err,
                                'unknown error'), 'error');
                        }
                    }

                    window.showToast('Equipment updated successfully!', 'success');
                    setTimeout(() => {
                        window.location.href = '/admin/manage-equipment';
                    }, 1200);

                } catch (err) {
                    console.error(err);
                    window.showToast(err.message || 'Failed to update equipment.', 'error');
                } finally {
                    setSubmitting(false);
                }
            });

            // Start the load sequence
            loadEquipmentData();
        });
    </script>
@endsection
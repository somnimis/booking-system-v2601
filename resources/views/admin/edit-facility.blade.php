@extends('layouts.admin')

@section('title', 'Edit Facility')

@section('content')
    <link rel="stylesheet" href="{{ asset('css/admin/resource-form.css') }}">

    <main id="main">
        <div class="container main-content mb-5" style="max-width: 960px; margin-left: auto; margin-right: auto;"
            x-data="facilityForm()" x-cloak>

            <div class="bg-white border rounded-3 admin-livewire-form" style="border-color: #dee2e6;">

                <style>
                    .admin-livewire-form .form-page-title {
                        font-family: "Fraunces", Georgia, serif;
                        font-weight: 900;
                        font-size: 1.25rem;
                        margin: 0;
                        color: #fff;
                    }
                    [x-cloak] { display: none !important; }
                </style>

                {{-- Page header --}}
                <div class="d-flex justify-content-between align-items-center p-3"
                    style="background: #045191; border-top-left-radius: 0.375rem; border-top-right-radius: 0.375rem;">
                    <h4 class="form-page-title">Edit Facility</h4>
                    <a href="{{ url('/admin/manage-facilities') }}" class="btn btn-sm btn-light">
                        <i class="bi bi-arrow-left me-1"></i> Back to Facilities
                    </a>
                </div>

                <div class="p-4 position-relative" style="min-height: 400px;">
                    
                    {{-- Loading Overlay (covers form area, spinner elevated to top third) --}}
                    <div id="pageLoadingOverlay" style="display: flex; position: absolute; inset: 0; background: #fff; z-index: 10; justify-content: center; align-items: flex-start; padding-top: 15vh;">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2 text-muted mb-0">Loading facility data...</p>
                        </div>
                    </div>

                    <form id="editFacilityForm">
                        <input type="hidden" id="facilityId" value="{{ $facilityId }}">

                        {{-- ============ FACILITY TYPE ============ --}}
                        <div class="row mb-3">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header"><strong>Facility Type</strong></div>
                                    <div class="card-body row g-3">

                                        <div class="col-md-4">
                                            <input type="radio" class="btn-check" name="facility_role" id="type_venue"
                                                value="Venue" :checked="facilityRole === 'Venue'"
                                                @change="onFacilityRoleChange('Venue', $event)">
                                            <label class="type-option d-block" for="type_venue">
                                                <div class="type-title">Venue</div>
                                                <div class="type-help">A bookable facility that does not contain
                                                    other rooms (e.g., Gymnasium, Auditorium).</div>
                                            </label>
                                        </div>

                                        <div class="col-md-4">
                                            <input type="radio" class="btn-check" name="facility_role" id="type_container"
                                                value="Container" :checked="facilityRole === 'Container'"
                                                @change="onFacilityRoleChange('Container', $event)">
                                            <label class="type-option d-block" for="type_container">
                                                <div class="type-title">Container</div>
                                                <div class="type-help">Groups rooms together and is not bookable
                                                    itself (e.g., College Building).</div>
                                            </label>
                                        </div>

                                        <div class="col-md-4">
                                            <input type="radio" class="btn-check" name="facility_role" id="type_room"
                                                value="Room" :checked="facilityRole === 'Room'"
                                                @change="onFacilityRoleChange('Room', $event)">
                                            <label class="type-option d-block" for="type_room">
                                                <div class="type-title">Room</div>
                                                <div class="type-help">A bookable space inside a container
                                                    building (e.g., Room 301 in Engineering Hall).</div>
                                            </label>
                                        </div>

                                        <div class="col-12" x-show="facilityRole === 'Room'" x-cloak>
                                            <label for="parentFacilityId" class="form-label">
                                                Parent Building
                                            </label>
                                            <select class="form-select" id="parentFacilityId" x-model="parentFacilityId">
                                                <option value="">Select a container building…</option>
                                                <template x-for="building in containerBuildings"
                                                    :key="building.facility_id">
                                                    <option :value="building.facility_id" x-text="building.facility_name">
                                                    </option>
                                                </template>
                                            </select>
                                            <small class="text-muted">
                                                Rooms must belong to a container building.
                                            </small>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ============ PHOTOS & MAIN INFO ============ --}}
                        <div class="row mb-3 d-flex align-items-stretch">
                            <div class="col-md-6 mb-3 mb-md-0 d-flex">
                                <div class="card w-100">
                                    <div class="card-header"><strong>Facility Photos</strong></div>
                                    <div class="card-body">
                                        <div class="dropzone border p-4 text-center"
                                            id="facilityPhotosDropzone" style="cursor: pointer;">
                                            <i class="bi bi-images fs-1 text-muted"></i>
                                            <p class="mt-2 mb-0">Drag &amp; drop facility photos here or click
                                                to browse</p>
                                            <input type="file" id="facilityPhotos" class="d-none" multiple
                                                accept="image/*">
                                        </div>
                                        <small class="text-muted mt-2 d-block">
                                            Max 5 photos. The blue star marks the primary photo.
                                        </small>
                                        <div id="photosPreview" class="d-flex flex-wrap gap-2 mt-3"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 d-flex">
                                <div class="card w-100">
                                    <div class="card-header"><strong>Main Information</strong></div>
                                    <div class="card-body row g-3">
                                        <div class="col-md-6">
                                            <label for="facilityName" class="form-label">Facility Name</label>
                                            <input type="text" class="form-control" required maxlength="50" id="facilityName" placeholder="e.g. Engineering Hall">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="buildingCode" class="form-label">Facility Code</label>
                                            <input type="text" class="form-control" maxlength="20" id="buildingCode" placeholder="e.g. ENG-HALL">
                                        </div>
                                        <div class="col-12">
                                            <label for="description" class="form-label">Description</label>
                                            <textarea class="form-control" id="description" rows="3" maxlength="250" placeholder="Short description of the facility..."></textarea>
                                            <small class="text-muted d-block text-end mt-1" id="descriptionWordCount">0/250 characters</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="rentalFee" class="form-label">Rental Fee (₱)</label>
                                            <div class="input-group" :data-bs-toggle="isContainer ? 'tooltip' : null" :data-bs-title="isContainer ? 'Not applicable to containers.' : null">
                                                <span class="input-group-text">₱</span>
                                                <input type="number" class="form-control" id="rentalFee" min="0" step="0.01" placeholder="0.00" :disabled="isContainer">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="rateType" class="form-label">Rate Type</label>
                                            <span class="d-block" :data-bs-toggle="isContainer ? 'tooltip' : null" :data-bs-title="isContainer ? 'Not applicable to containers.' : null">
                                                <select class="form-select" id="rateType" :disabled="isContainer">
                                                    <option value="Per Hour">Per Hour</option>
                                                    <option value="Per Event">Per Event</option>
                                                </select>
                                            </span>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="category" class="form-label">Category</label>
                                            <select class="form-select" id="category" required>
                                                <option value="">Select Category</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="subcategory" class="form-label">Subcategory</label>
                                            <select class="form-select" id="subcategory" required>
                                                <option value="">Select Subcategory</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ============ ADDITIONAL DETAILS ============ --}}
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header"><strong>Additional Details</strong></div>
                                    <div class="card-body row g-3">
                                        <div class="col-md-4">
                                            <label for="capacity" class="form-label">Capacity</label>
                                            <input type="number" class="form-control" id="capacity" min="1" required placeholder="e.g. 100">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="floorLevel" class="form-label">Floor Level</label>
                                            <span class="d-block" :data-bs-toggle="isContainer ? 'tooltip' : null" :data-bs-title="isContainer ? 'Not applicable to containers.' : null">
                                                <input type="number" class="form-control" id="floorLevel" min="1" placeholder="e.g. 2" :disabled="isContainer">
                                            </span>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="totalLevels" class="form-label">Total Levels</label>
                                            <input type="number" class="form-control" id="totalLevels" min="1" placeholder="e.g. 5">
                                        </div>
                                        <div class="col-md-9">
                                            <label for="locationNote" class="form-label">Location Note</label>
                                            <input type="text" class="form-control" id="locationNote" maxlength="200" placeholder="Directions to the facility...">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="locationType" class="form-label">Location Type</label>
                                            <select class="form-select" id="locationType" required>
                                                <option value="Indoors">Indoors</option>
                                                <option value="Outdoors">Outdoors</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Owning Departments</label>
                                            <div class="department-checkboxes" id="departmentCheckboxes">
                                                <template x-if="departments.length === 0">
                                                    <div class="department-checkboxes-loading">
                                                        <span class="spinner-border spinner-border-sm me-2"></span> Loading departments…
                                                    </div>
                                                </template>
                                                <template x-for="dep in departments" :key="dep.department_id">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="department_ids" :value="dep.department_id" :id="`main_dep_${dep.department_id}`" x-model="mainDepartmentIds">
                                                        <label class="form-check-label" :for="`main_dep_${dep.department_id}`" x-text="dep.department_name"></label>
                                                    </div>
                                                </template>
                                            </div>
                                            <small class="text-muted">Select one or more departments.</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Availability Status</label>
                                            <div class="department-checkboxes" id="availabilityStatus"
                                                :class="{ 'opacity-50': isContainer }"
                                                :data-bs-toggle="isContainer ? 'tooltip' : null"
                                                :data-bs-title="isContainer ? 'Not applicable to containers.' : null">
                                                <template x-if="statuses.length === 0">
                                                    <div class="department-checkboxes-loading">
                                                        <span class="spinner-border spinner-border-sm me-2"></span> Loading statuses…
                                                    </div>
                                                </template>
                                                <template x-for="st in statuses" :key="st.status_id">
                                                    <label class="status-item"
                                                        :class="{ 'is-active': mainStatusId == st.status_id, 'pe-none': isContainer }">
                                                        <input type="radio" class="form-check-input me-2" name="main_status" :value="st.status_id" x-model="mainStatusId" :disabled="isContainer">
                                                        <span x-text="st.status_name"></span>
                                                    </label>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </form>
                </div>
            </div>

            {{-- Action buttons OUTSIDE the form shell --}}
            <div class="bg-white border rounded-3 p-3 mt-3 d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary" id="cancelBtn">Discard</button>
                <button type="submit" form="editFacilityForm" class="btn btn-primary" id="saveFacilityBtn">
                    <span class="spinner-border spinner-border-sm d-none me-1" id="saveFacilitySpinner"></span>
                    <span id="saveFacilityBtnText">Save Changes</span>
                </button>
            </div>

            <!-- Reset Confirmation Modal -->
            <div class="modal fade" id="cancelConfirmationModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Discard Changes</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
    </main>
@endsection

@section('scripts')
    <script>
        function facilityForm() {
            const tooltipInstances = new Set();
            const jsonHeaders = { 'Accept': 'application/json' };
            return {
                facilityRole: 'Venue',
                parentFacilityId: '',
                containerBuildings: [],
                categories: [],
                departments: [],
                statuses: [],
                mainDepartmentIds: [],
                mainStatusId: '',

                get isContainer() {
                    return this.facilityRole === 'Container';
                },

                async init() {
                    this.refreshTooltips();
                    await Promise.all([
                        this.loadCategories(),
                        this.loadDepartments(),
                        this.loadStatuses(),
                        this.loadContainerBuildings()
                    ]);
                    
                    // Wait for dropdowns to populate before fetching the facility data
                    await this.$nextTick();
                    await this.fetchFacilityData();
                },

                async loadContainerBuildings() {
                    try {
                        const res = await fetch('/api/facilities/dropdown?role=container', { headers: jsonHeaders });
                        if (res.ok) {
                            const data = await res.json();
                            this.containerBuildings = Array.isArray(data.data || data) ? (data.data || data) : [];
                        }
                    } catch (e) { console.error('containerBuildings', e); }
                },
                async loadList(url, prop, headers = jsonHeaders) {
                    try {
                        const res = await fetch(url, { headers });
                        if (res.ok) this[prop] = await res.json();
                    } catch (e) { console.error(prop, e); }
                },
                loadCategories() { return this.loadList('/api/facility-categories/index', 'categories'); },
                loadDepartments() { return this.loadList('/api/departments', 'departments', { 'Authorization': `Bearer ${localStorage.getItem('adminToken')}`, 'Accept': 'application/json' }); },
                loadStatuses() { return this.loadList('/api/availability-statuses', 'statuses'); },

                async fetchFacilityData() {
                    const id = document.getElementById('facilityId').value;
                    try {
                        const res = await fetch(`/api/admin/facilities/${id}`, {
                            headers: {
                                'Authorization': `Bearer ${localStorage.getItem('adminToken')}`,
                                'Accept': 'application/json'
                            }
                        });
                        if (!res.ok) throw new Error('Failed to fetch facility data');
                        
                        const { data } = await res.json();
                        
                        // Populate Alpine state
                        this.facilityRole = data.facility_role;
                        this.parentFacilityId = data.parent_facility_id || '';
                        this.mainDepartmentIds = data.department_ids.map(Number);
                        this.mainStatusId = data.status_id || '';

                        // Populate DOM elements
                        document.getElementById('facilityName').value = data.facility_name || '';
                        document.getElementById('buildingCode').value = data.facility_code || '';
                        document.getElementById('description').value = data.description || '';
                        document.getElementById('description').dispatchEvent(new Event('input')); // Update counter
                        document.getElementById('rentalFee').value = data.base_fee || '';
                        document.getElementById('rateType').value = data.rate_type || 'Per Hour';
                        document.getElementById('capacity').value = data.capacity || '';
                        document.getElementById('floorLevel').value = data.floor_level || '';
                        document.getElementById('totalLevels').value = data.total_levels || '';
                        document.getElementById('locationNote').value = data.location_note || '';
                        document.getElementById('locationType').value = data.location_type || 'Indoors';

                        // Populate categories
                        const catSel = document.getElementById('category');
                        if (data.category_id) {
                            catSel.value = data.category_id;
                            catSel.dispatchEvent(new Event('change')); // Populate subcategories
                            setTimeout(() => {
                                if (data.subcategory_id) {
                                    document.getElementById('subcategory').value = data.subcategory_id;
                                }
                            }, 100);
                        }

                        // Set existing images
                        window.existingImages = data.images || [];
                        renderPhotos();

                        this.refreshTooltips();

                    } catch (e) {
                        console.error(e);
                        window.showToast('Failed to load facility data', 'error');
                    } finally {
                        const overlay = document.getElementById('pageLoadingOverlay');
                        if (overlay) overlay.style.display = 'none';
                    }
                },
                refreshTooltips() {
                    setTimeout(() => {
                        tooltipInstances.forEach(tip => tip.dispose());
                        tooltipInstances.clear();
                        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
                            tooltipInstances.add(new bootstrap.Tooltip(el, { delay: 0, placement: 'top' }));
                        });
                    }, 0);
                },

                onFacilityRoleChange(newRole, event) {
                    if (newRole === this.facilityRole) return;
                    this.facilityRole = newRole;
                    if (newRole !== 'Room') {
                        this.parentFacilityId = '';
                    }
                    this.refreshTooltips();
                }
            };
        }

        document.addEventListener('DOMContentLoaded', function() {
            window.pendingImageUploads = [];
            window.existingImages = [];
            window.pendingImageDeletions = [];

            const token = localStorage.getItem('adminToken');
            if (!token) return window.location.href = '/admin/login';

            // ---- Photos Logic ----
            const facilityDropzone = document.getElementById('facilityPhotosDropzone');
            const facilityFileInput = document.getElementById('facilityPhotos');
            const photosPreview = document.getElementById('photosPreview');

            window.renderPhotos = function() {
                photosPreview.innerHTML = '';
                
                // Render existing images
                window.existingImages.forEach((img) => {
                    if (window.pendingImageDeletions.includes(img.image_id)) return; // Skip marked for deletion

                    const tile = document.createElement('div');
                    tile.className = 'photo-preview';
                    
                    const imageEl = document.createElement('img');
                    imageEl.src = img.image_url;
                    tile.appendChild(imageEl);

                    const star = document.createElement('button');
                    star.type = 'button';
                    star.className = 'primary-star' + (img.image_type === 'Primary' ? ' is-primary' : '');
                    star.innerHTML = '<i class="bi bi-star-fill"></i>';
                    star.addEventListener('click', () => {
                        if (img.image_type === 'Primary') return;
                        window.existingImages.forEach(e => e.image_type = 'Secondary');
                        img.image_type = 'Primary';
                        window.pendingImageUploads.forEach(e => e.image_type = 'Secondary');
                        window.renderPhotos();
                    });
                    tile.appendChild(star);

                    const actions = document.createElement('div');
                    actions.className = 'photo-actions';

                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.innerHTML = '<i class="bi bi-x"></i>';
                    removeBtn.addEventListener('click', () => {
                        window.pendingImageDeletions.push(img.image_id);
                        window.renderPhotos();
                    });
                    actions.appendChild(removeBtn);

                    tile.appendChild(actions);
                    photosPreview.appendChild(tile);
                });

                // Render new uploads
                window.pendingImageUploads.forEach((entry) => {
                    const tile = document.createElement('div');
                    tile.className = 'photo-preview';
                    tile.dataset.tempId = entry.temp_id;

                    const img = document.createElement('img');
                    img.src = entry.objectUrl;
                    tile.appendChild(img);

                    const star = document.createElement('button');
                    star.type = 'button';
                    star.className = 'primary-star' + (entry.image_type === 'Primary' ? ' is-primary' : '');
                    star.innerHTML = '<i class="bi bi-star-fill"></i>';
                    star.addEventListener('click', () => {
                        if (entry.image_type === 'Primary') return;
                        window.pendingImageUploads.forEach(e => e.image_type = 'Secondary');
                        window.existingImages.forEach(e => e.image_type = 'Secondary');
                        entry.image_type = 'Primary';
                        window.renderPhotos();
                    });
                    tile.appendChild(star);

                    const actions = document.createElement('div');
                    actions.className = 'photo-actions';

                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.innerHTML = '<i class="bi bi-x"></i>';
                    removeBtn.addEventListener('click', () => {
                        const idx = window.pendingImageUploads.findIndex(e => e.temp_id === entry.temp_id);
                        if (idx > -1) {
                            URL.revokeObjectURL(window.pendingImageUploads[idx].objectUrl);
                            window.pendingImageUploads.splice(idx, 1);
                        }
                        window.renderPhotos();
                    });
                    actions.appendChild(removeBtn);

                    tile.appendChild(actions);
                    photosPreview.appendChild(tile);
                });
            };

            function handleFacilityFiles(files) {
                for (const file of files) {
                    if (!file.type.startsWith('image/')) {
                        window.showToast('Please upload only image files', 'error');
                        continue;
                    }
                    if (window.pendingImageUploads.length + window.existingImages.length - window.pendingImageDeletions.length >= 5) {
                        window.showToast('Maximum of 5 photos allowed', 'error');
                        break;
                    }
                    const isFirst = (window.pendingImageUploads.length === 0 && window.existingImages.length === 0);
                    window.pendingImageUploads.push({
                        temp_id: 'temp_' + Date.now() + '_' + Math.random().toString(36).slice(2, 7),
                        file: file,
                        objectUrl: URL.createObjectURL(file),
                        image_type: isFirst ? 'Primary' : 'Secondary',
                        sort_order: window.pendingImageUploads.length + window.existingImages.length,
                    });
                }
                window.renderPhotos();
            }

            facilityDropzone.addEventListener('click', () => facilityFileInput.click());
            facilityFileInput.addEventListener('change', function() {
                handleFacilityFiles(this.files);
                this.value = '';
            });
            
            // Drag and drop
            facilityDropzone.addEventListener('dragover', (e) => { e.preventDefault(); facilityDropzone.classList.add('border-primary'); });
            facilityDropzone.addEventListener('dragleave', () => { facilityDropzone.classList.remove('border-primary'); });
            facilityDropzone.addEventListener('drop', (e) => {
                e.preventDefault();
                facilityDropzone.classList.remove('border-primary');
                if (e.dataTransfer.files.length) handleFacilityFiles(e.dataTransfer.files);
            });

            // ---- Description Counter ----
            const description = document.getElementById('description');
            const descriptionWordCount = document.getElementById('descriptionWordCount');
            description.addEventListener('input', function() {
                if (this.value.length > 250) this.value = this.value.substring(0, 250);
                descriptionWordCount.textContent = `${this.value.length}/250 characters`;
            });

            // ---- Main Dropdowns (Categories) ----
            async function populateMainDropdowns() {
                try {
                    const catRes = await fetch('/api/facility-categories/index', { headers: { 'Accept': 'application/json' } });
                    if (catRes.ok) {
                        const cats = await catRes.json();
                        const catSel = document.getElementById('category');
                        while (catSel.options.length > 1) catSel.remove(1);
                        cats.forEach(c => {
                            const o = document.createElement('option');
                            o.value = c.category_id;
                            o.textContent = c.category_name;
                            catSel.appendChild(o);
                        });

                        catSel.addEventListener('change', function() {
                            const cat = cats.find(c => String(c.category_id) === String(this.value));
                            const subSel = document.getElementById('subcategory');
                            while (subSel.options.length > 1) subSel.remove(1);
                            if (cat && cat.subcategories) {
                                cat.subcategories.forEach(s => {
                                    const o = document.createElement('option');
                                    o.value = s.subcategory_id;
                                    o.textContent = s.subcategory_name;
                                    subSel.appendChild(o);
                                });
                            }
                        });
                    }
                } catch (e) { console.error('populateMainDropdowns', e); }
            }
            populateMainDropdowns();

            // ---- Cancel Flow ----
            const cancelConfirmationModal = new bootstrap.Modal('#cancelConfirmationModal', { backdrop: 'static', keyboard: false });
            document.getElementById('cancelBtn').addEventListener('click', (e) => { e.preventDefault(); cancelConfirmationModal.show(); });
            document.getElementById('confirmCancelBtn').addEventListener('click', () => {
                cancelConfirmationModal.hide();
                window.location.href = '/admin/manage-facilities';
            });

            // ---- Submit ----
            const form = document.getElementById('editFacilityForm');
            const btn = document.getElementById('saveFacilityBtn');
            const spinner = document.getElementById('saveFacilitySpinner');
            const btnText = document.getElementById('saveFacilityBtnText');

            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                btn.disabled = true;
                spinner.classList.remove('d-none');
                btnText.textContent = 'Saving...';

                try {
                    const id = document.getElementById('facilityId').value;
                    const alpine = window.Alpine.$data(document.querySelector('[x-data]'));
                    const facilityRole = alpine.facilityRole;

                    if (facilityRole === 'Room' && !alpine.parentFacilityId) {
                        window.showToast('Please select a parent building for this room.', 'error');
                        return;
                    }

                    const requiredIds = ['facilityName', 'capacity', 'category', 'locationType'];
                    if (facilityRole !== 'Container') requiredIds.push('rentalFee', 'rateType');
                    
                    for (const fieldId of requiredIds) {
                        const el = document.getElementById(fieldId);
                        if (!el || !String(el.value).trim()) {
                            window.showToast(`Please fill in the ${fieldId.replace(/([A-Z])/g, ' $1').toLowerCase()} field`, 'error');
                            el?.focus();
                            return;
                        }
                    }

                    if (!alpine.mainDepartmentIds || alpine.mainDepartmentIds.length === 0) {
                        window.showToast('Please select at least one department.', 'error');
                        return;
                    }

                    if (facilityRole !== 'Container' && !alpine.mainStatusId) {
                        window.showToast('Please select an availability status.', 'error');
                        return;
                    }

                    const payload = {
                        facility_name: document.getElementById('facilityName').value.trim(),
                        facility_code: document.getElementById('buildingCode').value.trim() || null,
                        location_type: document.getElementById('locationType').value,
                        category_id: document.getElementById('category').value,
                        subcategory_id: document.getElementById('subcategory').value || null,
                        capacity: parseInt(document.getElementById('capacity').value),
                        floor_level: facilityRole === 'Container' ? null : (document.getElementById('floorLevel').value ? parseInt(document.getElementById('floorLevel').value) : null),
                        base_fee: facilityRole === 'Container' ? 0 : parseFloat(document.getElementById('rentalFee').value),
                        rate_type: facilityRole === 'Container' ? 'Per Hour' : document.getElementById('rateType').value,
                        total_levels: document.getElementById('totalLevels').value ? parseInt(document.getElementById('totalLevels').value) : null,
                        departments: (alpine.mainDepartmentIds || []).map(Number),
                        status_id: facilityRole === 'Container' ? (alpine.statuses[0]?.status_id || null) : alpine.mainStatusId,
                        description: document.getElementById('description').value.trim() || 'No description provided',
                        location_note: document.getElementById('locationNote').value.trim() || 'No location details provided',
                        facility_role: facilityRole,
                        parent_facility_id: facilityRole === 'Room' ? (parseInt(alpine.parentFacilityId) || null) : null
                    };

                    // 1. Update facility details
                    const res = await fetch(`/api/admin/facilities/${id}`, {
                        method: 'PUT',
                        headers: {
                            'Authorization': `Bearer ${localStorage.getItem('adminToken')}`,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });

                    if (!res.ok) {
                        const err = await res.json().catch(() => ({}));
                        let errorMessage = err.message || `Failed to update facility: ${res.status}`;
                        if (err.errors) {
                            const firstErrorKey = Object.keys(err.errors)[0];
                            if (firstErrorKey && err.errors[firstErrorKey].length > 0) {
                                errorMessage = err.errors[firstErrorKey][0];
                            }
                        }
                        throw new Error(errorMessage);
                    }

                    // 2. Handle Image Deletions
                    for (const imageId of window.pendingImageDeletions) {
                        await fetch(`/api/admin/facilities/${id}/images/${imageId}`, {
                            method: 'DELETE',
                            headers: { 'Authorization': `Bearer ${localStorage.getItem('adminToken')}`, 'Accept': 'application/json' }
                        });
                    }

                    // 3. Handle New Image Uploads
                    for (const entry of window.pendingImageUploads) {
                        try {
                            const fd = new FormData();
                            fd.append('image', entry.file);
                            fd.append('image_type', entry.image_type);
                            fd.append('sort_order', entry.sort_order);

                            const upRes = await fetch(`/api/admin/facilities/${id}/images`, {
                                method: 'POST',
                                headers: { 'Authorization': `Bearer ${localStorage.getItem('adminToken')}`, 'Accept': 'application/json' },
                                body: fd
                            });
                            if (!upRes.ok) window.showToast('Some new images failed to upload.', 'error');
                        } catch (err) { console.error(err); }
                    }

                    // 4. Handle Image Reordering / Primary Status Changes
                    if (window.pendingImageDeletions.length === 0 && window.pendingImageUploads.length === 0 && window.existingImages.length > 0) {
                         const sortPayload = window.existingImages.map((img, index) => ({
                             image_id: img.image_id,
                             sort_order: index,
                             image_type: img.image_type
                         }));
                         await fetch(`/api/admin/facilities/${id}/images/reorder`, {
                             method: 'POST',
                             headers: {
                                 'Authorization': `Bearer ${localStorage.getItem('adminToken')}`,
                                 'Content-Type': 'application/json',
                                 'Accept': 'application/json'
                             },
                             body: JSON.stringify({ images: sortPayload })
                         });
                    }

                    window.showToast('Facility updated successfully!', 'success');
                    setTimeout(() => { window.location.href = '/admin/manage-facilities'; }, 1200);

                } catch (err) {
                    console.error(err);
                    window.showToast(err.message, 'error');
                } finally {
                    btn.disabled = false;
                    spinner.classList.add('d-none');
                    btnText.textContent = 'Save Changes';
                }
            });
        });
    </script>
@endsection
@extends('layouts.admin')

@section('title', 'Add Facility')

@section('content')
    <link rel="stylesheet" href="{{ asset('css/admin/facility-form.css') }}">

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

                    [x-cloak] {
                        display: none !important;
                    }
                </style>

                {{-- Page header --}}
                <div class="d-flex justify-content-between align-items-center p-3"
                    style="background: #045191; border-top-left-radius: 0.375rem; border-top-right-radius: 0.375rem;">
                    <h4 class="form-page-title">Add New Facility</h4>
                    <a href="{{ url('/admin/manage-facilities') }}" class="btn btn-sm btn-light">
                        <i class="bi bi-arrow-left me-1"></i> Back to Facilities
                    </a>
                </div>

                <div class="p-4">
                    <form id="addFacilityForm">

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

                        {{-- ============ TABS ============ --}}
                        <div class="row mb-3">
                            <div class="col-12">
                                <div class="facility-tab-strip-wrapper">
                                    <ul class="nav nav-tabs align-items-end facility-tab-strip" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link active" type="button" role="tab" id="tab-main-btn"
                                                data-tab-key="main" @click="activateTab('main')"
                                                :class="{ 'active': activeTabKey === 'main' }">
                                                Main
                                            </button>
                                        </li>

                                        <template x-for="room in rooms" :key="room.key">
                                            <li class="nav-item d-flex align-items-center" role="presentation">
                                                <button class="nav-link" type="button" role="tab"
                                                    :data-tab-key="room.key" @click="activateTab(room.key)"
                                                    :class="{ 'active': activeTabKey === room.key }">
                                                    <span x-text="room.tabLabel"></span>
                                                    <span class="facility-tab-close" title="Remove room"
                                                        @click.stop="requestRemoveRoom(room.key)">
                                                        <i class="bi bi-x-lg"></i>
                                                    </span>
                                                </button>
                                            </li>
                                        </template>
                                    </ul>

                                    <div class="facility-tab-add-btn">
                                        <button type="button" class="btn btn-sm btn-primary" @click="onAddRoomClick()">
                                            <i class="bi bi-plus-lg me-1"></i> Add Room
                                        </button>
                                    </div>
                                </div>

                                {{-- MAIN TAB PANE --}}
                                <div class="border border-top-0 rounded-bottom p-3 bg-white"
                                    x-show="activeTabKey === 'main'">

                                    {{-- ===== Photos | Main Info ===== --}}
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
                                                        <label for="facilityName" class="form-label">Facility
                                                            Name</label>
                                                        <input type="text" class="form-control" required
                                                            maxlength="50" id="facilityName"
                                                            placeholder="e.g. Engineering Hall">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label for="buildingCode" class="form-label">Facility
                                                            Code</label>
                                                        <input type="text" class="form-control" maxlength="20"
                                                            id="buildingCode" placeholder="e.g. ENG-HALL">
                                                    </div>
                                                    <div class="col-12">
                                                        <label for="description" class="form-label">Description</label>
                                                        <textarea class="form-control" id="description" rows="3" maxlength="250"
                                                            placeholder="Short description of the facility..."></textarea>
                                                        <small class="text-muted d-block text-end mt-1"
                                                            id="descriptionWordCount">0/250 characters</small>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label for="rentalFee" class="form-label">Rental Fee
                                                            (₱)</label>
                                                        <div class="input-group"
                                                            :data-bs-toggle="isContainer ? 'tooltip' : null"
                                                            :data-bs-title="isContainer ? 'Not applicable to containers.' : null">
                                                            <span class="input-group-text">₱</span>
                                                            <input type="number" class="form-control"
                                                                id="rentalFee" min="0" step="0.01"
                                                                placeholder="0.00"
                                                                :disabled="isContainer">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label for="rateType" class="form-label">Rate Type</label>
                                                        <span class="d-block"
                                                            :data-bs-toggle="isContainer ? 'tooltip' : null"
                                                            :data-bs-title="isContainer ? 'Not applicable to containers.' : null">
                                                            <select class="form-select" id="rateType"
                                                                :disabled="isContainer">
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

                                    {{-- ===== Additional Details ===== --}}
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="card">
                                                <div class="card-header"><strong>Additional Details</strong></div>
                                                <div class="card-body row g-3">
                                                    <div class="col-md-4">
                                                        <label for="capacity" class="form-label">Capacity</label>
                                                        <input type="number" class="form-control" id="capacity"
                                                            min="1" value="1" required
                                                            placeholder="e.g. 100">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label for="floorLevel" class="form-label">Floor
                                                            Level</label>
                                                        <span class="d-block"
                                                            :data-bs-toggle="isContainer ? 'tooltip' : null"
                                                            :data-bs-title="isContainer ? 'Not applicable to containers.' : null">
                                                            <input type="number" class="form-control" id="floorLevel"
                                                                min="1" placeholder="e.g. 2"
                                                                :disabled="isContainer">
                                                        </span>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label for="totalLevels" class="form-label">Total
                                                            Levels</label>
                                                        <input type="number" class="form-control" id="totalLevels"
                                                            min="1" placeholder="e.g. 5">
                                                    </div>
                                                    <div class="col-md-9">
                                                        <label for="locationNote" class="form-label">Location
                                                            Note</label>
                                                        <input type="text" class="form-control" id="locationNote"
                                                            maxlength="200" placeholder="Directions to the facility...">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label for="locationType" class="form-label">Location
                                                            Type</label>
                                                        <select class="form-select" id="locationType" required>
                                                            <option value="Indoors">Indoors</option>
                                                            <option value="Outdoors">Outdoors</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">
                                                            Owning Departments
                                                        </label>
                                                        <div class="department-checkboxes" id="departmentCheckboxes">
                                                            <template x-if="departments.length === 0">
                                                                <div class="department-checkboxes-loading">
                                                                    <span
                                                                        class="spinner-border spinner-border-sm me-2"></span>
                                                                    Loading departments…
                                                                </div>
                                                            </template>
                                                            <template x-for="dep in departments" :key="dep.department_id">
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox"
                                                                        name="department_ids" :value="dep.department_id"
                                                                        :id="`main_dep_${dep.department_id}`"
                                                                        x-model="mainDepartmentIds">
                                                                    <label class="form-check-label"
                                                                        :for="`main_dep_${dep.department_id}`"
                                                                        x-text="dep.department_name"></label>
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
                                                                    <span
                                                                        class="spinner-border spinner-border-sm me-2"></span>
                                                                    Loading statuses…
                                                                </div>
                                                            </template>
                                                            <template x-for="st in statuses" :key="st.status_id">
                                                                <label class="status-item"
                                                                    :class="{
                                                                        'is-active': mainStatusId == st
                                                                            .status_id,
                                                                        'pe-none': isContainer
                                                                    }">
                                                                    <input type="radio" class="form-check-input me-2"
                                                                        name="main_status" :value="st.status_id"
                                                                        x-model="mainStatusId"
                                                                        :disabled="isContainer">
                                                                    <span x-text="st.status_name"></span>
                                                                </label>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- ROOM TAB PANES --}}
                                <template x-for="room in rooms" :key="room.key">
                                    <div class="border border-top-0 rounded-bottom p-3 bg-white"
                                        x-show="activeTabKey === room.key">

                                        {{-- Room: Main Information --}}
                                        <div class="row mb-3">
                                            <div class="col-12">
                                                <div class="card">
                                                    <div class="card-header"><strong>Main Information</strong></div>
                                                    <div class="card-body row g-3">

                                                        <div class="col-md-6">
                                                            <label class="form-label">Facility Name</label>
                                                            <div class="input-group">
                                                                <input type="text" class="form-control"
                                                                    :id="`room_${room.key}_facilityName`"
                                                                    x-model="room.data.facility_name"
                                                                    :disabled="!room.editing.name"
                                                                    :class="{ 'locked-field': !room.editing.name }"
                                                                    maxlength="50" placeholder="e.g. Room 301">
                                                                <template x-if="!room.editing.name">
                                                                    <button type="button"
                                                                        class="btn btn-outline-secondary"
                                                                        title="Edit facility name"
                                                                        @click="startEdit(room.key, 'name')">
                                                                        <i class="bi bi-pencil"></i>
                                                                    </button>
                                                                </template>
                                                                <template x-if="room.editing.name">
                                                                    <button type="button" class="btn btn-success"
                                                                        title="Save"
                                                                        @click="saveEdit(room.key, 'name')">
                                                                        <i class="bi bi-check-lg"></i>
                                                                    </button>
                                                                </template>
                                                                <template x-if="room.editing.name">
                                                                    <button type="button" class="btn btn-danger"
                                                                        title="Discard"
                                                                        @click="discardEdit(room.key, 'name')">
                                                                        <i class="bi bi-x-lg"></i>
                                                                    </button>
                                                                </template>
                                                            </div>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <label class="form-label">Facility Code</label>
                                                            <input type="text" class="form-control"
                                                                :id="`room_${room.key}_buildingCode`"
                                                                x-model="room.data.facility_code" maxlength="20"
                                                                placeholder="e.g. ENG301">
                                                        </div>

                                                        <div class="col-12">
                                                            <label class="form-label">Description</label>
                                                            <textarea class="form-control" rows="3" maxlength="250" x-model="room.data.description"
                                                                :id="`room_${room.key}_description`" placeholder="Short description of the room..."></textarea>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <label class="form-label">Rental Fee (₱)</label>
                                                            <div class="input-group">
                                                                <span class="input-group-text">₱</span>
                                                                <input type="number" class="form-control"
                                                                    x-model="room.data.rental_fee"
                                                                    :id="`room_${room.key}_rentalFee`" min="0"
                                                                    step="0.01" placeholder="0.00">
                                                            </div>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <label class="form-label">Rate Type</label>
                                                            <select class="form-select" x-model="room.data.rate_type"
                                                                :id="`room_${room.key}_rateType`">
                                                                <option value="Per Hour">Per Hour</option>
                                                                <option value="Per Event">Per Event</option>
                                                            </select>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <label class="form-label">Category</label>
                                                            <select class="form-select" x-model="room.data.category_id"
                                                                :id="`room_${room.key}_category`">
                                                                <option value="">Select Category</option>
                                                                <template x-for="cat in categories"
                                                                    :key="cat.category_id">
                                                                    <option :value="cat.category_id"
                                                                        x-text="cat.category_name"></option>
                                                                </template>
                                                            </select>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <label class="form-label">Subcategory</label>
                                                            <select class="form-select" x-model="room.data.subcategory_id"
                                                                :id="`room_${room.key}_subcategory`">
                                                                <option value="">Select Subcategory</option>
                                                                <template x-for="sub in roomSubcategories(room)"
                                                                    :key="sub.subcategory_id">
                                                                    <option :value="sub.subcategory_id"
                                                                        x-text="sub.subcategory_name"></option>
                                                                </template>
                                                            </select>
                                                        </div>

                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Room: Additional Details --}}
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="card">
                                                    <div class="card-header"><strong>Additional Details</strong></div>
                                                    <div class="card-body row g-3">
                                                        <div class="col-md-4">
                                                            <label class="form-label">Capacity</label>
                                                            <input type="number" class="form-control"
                                                                x-model="room.data.capacity"
                                                                :id="`room_${room.key}_capacity`" min="1"
                                                                placeholder="e.g. 40">
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label">Floor Level</label>
                                                            <input type="number" class="form-control"
                                                                x-model="room.data.floor_level"
                                                                :id="`room_${room.key}_floorLevel`" min="1"
                                                                placeholder="e.g. 3">
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label">Total Levels</label>
                                                            <span class="d-block" data-bs-toggle="tooltip"
                                                                data-bs-title="Already in parent container.">
                                                                <input type="number" class="form-control"
                                                                    x-model="room.data.total_levels"
                                                                    :id="`room_${room.key}_totalLevels`" min="1"
                                                                    placeholder="e.g. 5" disabled>
                                                            </span>
                                                        </div>
                                                        <div class="col-md-9">
                                                            <label class="form-label">Location Note</label>
                                                            <input type="text" class="form-control"
                                                                x-model="room.data.location_note"
                                                                :id="`room_${room.key}_locationNote`" maxlength="200"
                                                                placeholder="Directions to the room...">
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label class="form-label">Location Type</label>
                                                            <select class="form-select" x-model="room.data.location_type"
                                                                :id="`room_${room.key}_locationType`">
                                                                <option value="Indoors">Indoors</option>
                                                                <option value="Outdoors">Outdoors</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">Owning Departments</label>
                                                            <div class="department-checkboxes"
                                                                :id="`room_${room.key}_departments`">
                                                                <template x-if="departments.length === 0">
                                                                    <div class="department-checkboxes-loading">
                                                                        <span
                                                                            class="spinner-border spinner-border-sm me-2"></span>
                                                                        Loading departments…
                                                                    </div>
                                                                </template>
                                                                <template x-for="dep in departments"
                                                                    :key="dep.department_id">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox"
                                                                            :value="dep.department_id"
                                                                            :id="`room_${room.key}_dep_${dep.department_id}`"
                                                                            x-model="room.data.department_ids">
                                                                        <label class="form-check-label"
                                                                            :for="`room_${room.key}_dep_${dep.department_id}`"
                                                                            x-text="dep.department_name"></label>
                                                                    </div>
                                                                </template>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">Availability Status</label>
                                                            <div class="department-checkboxes"
                                                                :id="`room_${room.key}_availabilityStatus`">
                                                                <template x-if="statuses.length === 0">
                                                                    <div class="department-checkboxes-loading">
                                                                        <span
                                                                            class="spinner-border spinner-border-sm me-2"></span>
                                                                        Loading statuses…
                                                                    </div>
                                                                </template>
                                                                <template x-for="st in statuses" :key="st.status_id">
                                                                    <label class="status-item"
                                                                        :class="{
                                                                            'is-active': room.data.status_id == st
                                                                                .status_id
                                                                        }">
                                                                        <input type="radio"
                                                                            class="form-check-input me-2"
                                                                            :name="`room_${room.key}_status`"
                                                                            :value="st.status_id"
                                                                            x-model="room.data.status_id">
                                                                        <span x-text="st.status_name"></span>
                                                                    </label>
                                                                </template>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </template>

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
                <button type="submit" form="addFacilityForm" class="btn btn-primary" id="addFacilityBtn">
                    <span class="spinner-border spinner-border-sm d-none me-1" id="addFacilitySpinner"></span>
                    <span id="addFacilityBtnText">Add New Facility</span>
                </button>
            </div>

            {{-- ===================== MODALS ===================== --}}

            {{-- Add Room Modal --}}
            <div class="modal fade" id="addRoomModal" tabindex="-1" aria-hidden="true" x-ref="addRoomModal">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Add New Room</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label required">Facility Name</label>
                                <input type="text" class="form-control" maxlength="50"
                                    x-model="addRoomForm.facility_name" placeholder="e.g. Room 301">
                                <small class="text-danger d-block" x-show="addRoomErrors.name"
                                    x-text="addRoomErrors.name"></small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Facility Code</label>
                                <input type="text" class="form-control" maxlength="20"
                                    x-model="addRoomForm.facility_code" placeholder="e.g. ENG301">
                                <small class="text-danger d-block" x-show="addRoomErrors.code"
                                    x-text="addRoomErrors.code"></small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" :disabled="addRoomSubmitting"
                                @click="submitAddRoom()">
                                <span x-show="!addRoomSubmitting">Add</span>
                                <span x-show="addRoomSubmitting">
                                    <span class="spinner-border spinner-border-sm me-1"></span> Adding…
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Remove Room Modal --}}
            <div class="modal fade" id="removeRoomModal" tabindex="-1" aria-hidden="true" x-ref="removeRoomModal">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Remove Room</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            Are you sure you want to remove this room tab? Unsaved room content will be lost.
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-danger" @click="confirmRemoveRoom()">Remove</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Facility Type Change Modal --}}
            <div class="modal fade" id="facilityRoleChangeModal" tabindex="-1" aria-hidden="true"
                x-ref="facilityRoleChangeModal">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Change Facility Type?</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            Changing the facility type will remove all Room tabs and their content. Do you want to continue?
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary"
                                @click="cancelFacilityRoleChange()">Cancel</button>
                            <button type="button" class="btn btn-danger"
                                @click="confirmFacilityRoleChange()">Continue</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reset Confirmation Modal (existing) -->
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
        function facilityForm() {
            const tooltipInstances = new Set();
            const jsonHeaders = { 'Accept': 'application/json' };
            return {
                // ---- State ----
                facilityRole: 'Container',
                pendingRoleChange: null,
                parentFacilityId: '',
                containerBuildings: [],
                activeTabKey: 'main',
                rooms: [],
                nextRoomIndex: 1,
                removeTargetKey: null,

                // Dropdown data
                categories: [],
                departments: [],
                statuses: [],

                // Main tab multi-select departments
                mainDepartmentIds: [],

                // Main tab single-select availability status
                mainStatusId: '',

                // Add Room modal
                addRoomForm: {
                    facility_name: '',
                    facility_code: ''
                },
                addRoomErrors: {
                    name: '',
                    code: ''
                },
                addRoomSubmitting: false,

                get isContainer() {
                    return this.facilityRole === 'Container';
                },

                async init() {
                    this.facilityRole = 'Container';

                    // Initialize tooltips right away; they don't depend on the API data below
                    this.refreshTooltips();

                    // Load dropdowns
                    await this.loadCategories();
                    await this.loadDepartments();
                    await this.loadStatuses();
                    await this.loadContainerBuildings();
                },

                async loadContainerBuildings() {
                    try {
                        const res = await fetch('/api/facilities/dropdown?role=container', {
                            headers: jsonHeaders
                        });
                        if (!res.ok) return;
                        const data = await res.json();
                        const items = data.data || data;
                        this.containerBuildings = Array.isArray(items) ? items : [];
                    } catch (e) {
                        console.error('containerBuildings', e);
                    }
                },

                // ---------- Dropdown loaders ----------
                async loadList(url, prop, headers = jsonHeaders) {
                    try {
                        const res = await fetch(url, { headers });
                        if (res.ok) this[prop] = await res.json();
                    } catch (e) {
                        console.error(prop, e);
                    }
                },

                loadCategories() {
                    return this.loadList('/api/facility-categories/index', 'categories');
                },

                loadDepartments() {
                    return this.loadList('/api/departments', 'departments', {
                        'Authorization': `Bearer ${localStorage.getItem('adminToken')}`,
                        'Accept': 'application/json'
                    });
                },

                loadStatuses() {
                    return this.loadList('/api/availability-statuses', 'statuses');
                },

                roomSubcategories(room) {
                    const catId = room.data.category_id;
                    if (!catId) return [];
                    const cat = this.categories.find(c => String(c.category_id) === String(catId));
                    return cat && cat.subcategories ? cat.subcategories : [];
                },

                refreshTooltips() {
                    setTimeout(() => {
                        // Dispose every tracked instance, including those whose
                        // data-bs-toggle attribute Alpine has since removed.
                        tooltipInstances.forEach(tip => tip.dispose());
                        tooltipInstances.clear();
                        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
                            tooltipInstances.add(new bootstrap.Tooltip(el, { delay: 0, placement: 'top' }));
                        });
                    }, 0);
                },

                // ---------- Facility type change guard ----------
                onFacilityRoleChange(newRole, event) {
                    // Same role: nothing to do
                    if (newRole === this.facilityRole) return;

                    // No rooms → allow the switch immediately
                    if (this.rooms.length === 0) {
                        this.facilityRole = newRole;
                        if (newRole !== 'Room') {
                            this.parentFacilityId = '';
                        }
                        this.refreshTooltips();
                        return;
                    }

                    // Rooms exist → block, revert the radio visual, then confirm via modal
                    event.target.checked = false;
                    const prevRadio = document.querySelector(
                        `input[name="facility_role"][value="${this.facilityRole}"]`
                    );
                    if (prevRadio) prevRadio.checked = true;

                    this.pendingRoleChange = newRole;
                    bootstrap.Modal.getOrCreateInstance(this.$refs.facilityRoleChangeModal).show();
                },

                confirmFacilityRoleChange() {
                    if (!this.pendingRoleChange) return;
                    this.rooms = [];
                    this.activeTabKey = 'main';
                    this.facilityRole = this.pendingRoleChange;
                    if (this.facilityRole !== 'Room') {
                        this.parentFacilityId = '';
                    }
                    this.pendingRoleChange = null;
                    bootstrap.Modal.getOrCreateInstance(this.$refs.facilityRoleChangeModal).hide();
                    this.refreshTooltips();
                },

                cancelFacilityRoleChange() {
                    this.pendingRoleChange = null;
                    bootstrap.Modal.getOrCreateInstance(this.$refs.facilityRoleChangeModal).hide();
                },

                // ---------- Tabs ----------
                activateTab(key) {
                    this.activeTabKey = key;
                },

                // ---------- Add Room ----------
                onAddRoomClick() {
                    if (this.facilityRole !== 'Container') {
                        window.showToast('Rooms can only be added when the facility type is Container.', 'error');
                        return;
                    }
                    this.addRoomForm = {
                        facility_name: '',
                        facility_code: ''
                    };
                    this.addRoomErrors = {
                        name: '',
                        code: ''
                    };
                    this.addRoomSubmitting = false;
                    bootstrap.Modal.getOrCreateInstance(this.$refs.addRoomModal).show();
                },

                async checkConflict(name, code) {
                    const res = await fetch('/api/facilities/check-conflict', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            facility_name: name,
                            facility_code: code || null
                        })
                    });
                    return { res, data: await res.json().catch(() => ({})) };
                },

                async submitAddRoom() {
                    this.addRoomErrors = {
                        name: '',
                        code: ''
                    };
                    const name = (this.addRoomForm.facility_name || '').trim();
                    const code = (this.addRoomForm.facility_code || '').trim();

                    if (!name) {
                        this.addRoomErrors.name = 'Facility name is required.';
                        return;
                    }

                    // Local check against already-open room tabs
                    const sameRoom = (field, val) => this.rooms.some(r =>
                        (r.data[field] || '').toLowerCase() === val.toLowerCase());
                    if (sameRoom('facility_name', name)) {
                        this.addRoomErrors.name = 'Another room tab already uses this name.';
                        window.showToast('Facility name conflicts with an existing room tab.', 'error');
                        return;
                    }

                    if (code) {
                        if (sameRoom('facility_code', code)) {
                            this.addRoomErrors.code = 'Another room tab already uses this code.';
                            window.showToast('Facility code conflicts with an existing room tab.', 'error');
                            return;
                        }
                    }

                    this.addRoomSubmitting = true;
                    try {
                        const { res, data } = await this.checkConflict(name, code);

                        if (!res.ok) {
                            this.addRoomErrors.name = data.message || 'Failed to check facility name.';
                            return;
                        }

                        if (!data.available) {
                            if (data.conflicts?.name) this.addRoomErrors.name = data.conflicts.name;
                            if (data.conflicts?.code) this.addRoomErrors.code = data.conflicts.code;
                            window.showToast('Name or code is already taken. Try another one.', 'error');
                            return;
                        }

                        this.createRoomTab(name, code);
                        bootstrap.Modal.getOrCreateInstance(this.$refs.addRoomModal).hide();
                        window.showToast('Room tab added.', 'success');
                    } catch (e) {
                        console.error(e);
                        this.addRoomErrors.name = 'Network error. Please try again.';
                        window.showToast('Failed to check conflict. Please try again.', 'error');
                    } finally {
                        this.addRoomSubmitting = false;
                    }
                },

                createRoomTab(name, code) {
                    const key = 'room_' + this.nextRoomIndex++;

                    this.rooms.push({
                        key: key,
                        tabLabel: name,
                        data: {
                            facility_name: name,
                            facility_code: code,
                            description: '',
                            rental_fee: '',
                            rate_type: 'Per Hour',
                            category_id: '',
                            subcategory_id: '',
                            capacity: '',
                            floor_level: '',
                            total_levels: '',
                            location_note: '',
                            location_type: 'Indoors',
                            department_ids: [],
                            status_id: ''
                        },
                        editing: {
                            name: false
                        },
                        snapshot: {
                            name: name
                        }
                    });

                    this.activeTabKey = key;

                    this.$nextTick(() => {
                        this.refreshTooltips();
                    });
                },

                // ---------- Edit / Save / Discard for locked fields ----------

                startEdit(key, field) {
                    if (field !== 'name') return;
                    const room = this.rooms.find(r => r.key === key);
                    if (!room) return;
                    room.editing.name = true;
                },

                async saveEdit(key, field) {
                    const room = this.rooms.find(r => r.key === key);
                    if (!room) return;

                    const value = (room.data.facility_name || '').trim();

                    if (!value) {
                        window.showToast('Facility name is required.', 'error');
                        return;
                    }

                    // Duplicate check against other room tabs
                    const dupInTabs = this.rooms.some(r => {
                        if (r.key === key) return false;
                        return (r.data.facility_name || '').toLowerCase() === value.toLowerCase();
                    });
                    if (dupInTabs) {
                        window.showToast('Another room tab already uses this name.', 'error');
                        return;
                    }

                    // Server conflict check
                    try {
                        const { res, data } = await this.checkConflict(value, room.data.facility_code);
                        if (res.ok && !data.available && data.conflicts?.name) {
                            window.showToast(data.conflicts.name, 'error');
                            return;
                        }
                    } catch (e) {
                        console.error(e);
                        window.showToast('Failed to verify against server.', 'error');
                        return;
                    }

                    // Commit
                    room.snapshot.name = value;
                    room.data.facility_name = value;
                    room.tabLabel = value;
                    room.editing.name = false;
                },

                discardEdit(key, field) {
                    const room = this.rooms.find(r => r.key === key);
                    if (!room) return;
                    if (field === 'name') room.data.facility_name = room.snapshot.name;
                    room.editing.name = false;
                },

                // ---------- Remove Room ----------
                requestRemoveRoom(key) {
                    this.removeTargetKey = key;
                    bootstrap.Modal.getOrCreateInstance(this.$refs.removeRoomModal).show();
                },

                confirmRemoveRoom() {
                    if (!this.removeTargetKey) return;
                    this.rooms = this.rooms.filter(r => r.key !== this.removeTargetKey);

                    if (this.activeTabKey === this.removeTargetKey) {
                        this.activeTabKey = 'main';
                    }
                    this.removeTargetKey = null;
                    bootstrap.Modal.getOrCreateInstance(this.$refs.removeRoomModal).hide();
                },

                // ---------- Submit gate ----------
                async onSubmit() {
                    // Validation is handled in the DOM submit handler; nothing to block here.
                    window.__alpineSubmitOk = true;
                }
            };
        }

        // ---- Non-Alpine glue (main-only submit, existing store()) ----
        document.addEventListener('DOMContentLoaded', function() {
            window.pendingImageUploads = [];
            window.newFacilityId = null;

            const token = localStorage.getItem('adminToken');
            if (!token) {
                window.location.href = '/admin/login';
                return;
            }

            // ---- Photos (main tab only) ----
            const facilityDropzone = document.getElementById('facilityPhotosDropzone');
            const facilityFileInput = document.getElementById('facilityPhotos');
            const photosPreview = document.getElementById('photosPreview');

            function renderPhotos() {
                photosPreview.innerHTML = '';
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
                        entry.image_type = 'Primary';
                        renderPhotos();
                    });
                    tile.appendChild(star);

                    const actions = document.createElement('div');
                    actions.className = 'photo-actions';

                    [
                        ['bi-arrow-up', () => movePhoto(entry.temp_id, -1)],
                        ['bi-x', () => removePhoto(entry.temp_id)],
                        ['bi-arrow-down', () => movePhoto(entry.temp_id, 1)]
                    ].forEach(([icon, handler]) => {
                        const b = document.createElement('button');
                        b.type = 'button';
                        b.innerHTML = `<i class="bi ${icon}"></i>`;
                        b.addEventListener('click', handler);
                        actions.appendChild(b);
                    });

                    tile.appendChild(actions);
                    photosPreview.appendChild(tile);
                });
            }

            function movePhoto(tempId, delta) {
                const idx = window.pendingImageUploads.findIndex(e => e.temp_id === tempId);
                if (idx === -1) return;
                const newIdx = idx + delta;
                if (newIdx < 0 || newIdx >= window.pendingImageUploads.length) return;
                const [item] = window.pendingImageUploads.splice(idx, 1);
                window.pendingImageUploads.splice(newIdx, 0, item);
                window.pendingImageUploads.forEach((e, i) => e.sort_order = i);
                renderPhotos();
            }

            function removePhoto(tempId) {
                const idx = window.pendingImageUploads.findIndex(e => e.temp_id === tempId);
                if (idx === -1) return;
                URL.revokeObjectURL(window.pendingImageUploads[idx].objectUrl);
                const wasPrimary = window.pendingImageUploads[idx].image_type === 'Primary';
                window.pendingImageUploads.splice(idx, 1);
                if (wasPrimary && window.pendingImageUploads.length) {
                    window.pendingImageUploads[0].image_type = 'Primary';
                }
                window.pendingImageUploads.forEach((e, i) => e.sort_order = i);
                renderPhotos();
            }

            function handleFacilityFiles(files) {
                for (const file of files) {
                    if (!file.type.startsWith('image/')) {
                        window.showToast('Please upload only image files', 'error');
                        continue;
                    }
                    if (window.pendingImageUploads.length >= 5) {
                        window.showToast('Maximum of 5 photos allowed', 'error');
                        break;
                    }
                    const isFirst = window.pendingImageUploads.length === 0;
                    window.pendingImageUploads.push({
                        temp_id: 'temp_' + Date.now() + '_' + Math.random().toString(36).slice(2, 7),
                        file: file,
                        objectUrl: URL.createObjectURL(file),
                        image_type: isFirst ? 'Primary' : 'Secondary',
                        sort_order: window.pendingImageUploads.length,
                    });
                }
                renderPhotos();
            }

            if (facilityDropzone && facilityFileInput) {
                facilityDropzone.addEventListener('click', () => facilityFileInput.click());
                facilityFileInput.addEventListener('change', function() {
                    handleFacilityFiles(this.files);
                    this.value = '';
                });
                facilityDropzone.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    facilityDropzone.classList.add('border-primary');
                });
                facilityDropzone.addEventListener('dragleave', () => {
                    facilityDropzone.classList.remove('border-primary');
                });
                facilityDropzone.addEventListener('drop', (e) => {
                    e.preventDefault();
                    facilityDropzone.classList.remove('border-primary');
                    if (e.dataTransfer.files.length) handleFacilityFiles(e.dataTransfer.files);
                });
            }

            // ---- Description counter (main only) ----
            const description = document.getElementById('description');
            const descriptionWordCount = document.getElementById('descriptionWordCount');
            if (description && descriptionWordCount) {
                description.addEventListener('input', function() {
                    if (this.value.length > 250) this.value = this.value.substring(0, 250);
                    descriptionWordCount.textContent = `${this.value.length}/250 characters`;
                });
            }

            // ---- Populate main-tab dropdowns (departments, statuses, categories) ----
            async function populateMainDropdowns() {
                try {
                    const catRes = await fetch('/api/facility-categories/index', {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
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

                        // Subcategory on change (main tab only)
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
                } catch (e) {
                    console.error('populateMainDropdowns', e);
                }
            }
            populateMainDropdowns();

            // ---- Cancel flow ----
            const cancelConfirmationModal = new bootstrap.Modal('#cancelConfirmationModal', {
                backdrop: 'static',
                keyboard: false
            });
            document.getElementById('cancelBtn').addEventListener('click', (e) => {
                e.preventDefault();
                cancelConfirmationModal.show();
            });
            document.getElementById('confirmCancelBtn').addEventListener('click', () => {
                cancelConfirmationModal.hide();
                window.location.href = '/admin/manage-facilities';
            });

            // ---- Submit (Main only) ----
            const form = document.getElementById('addFacilityForm');
            const btn = document.getElementById('addFacilityBtn');
            const spinner = document.getElementById('addFacilitySpinner');
            const btnText = document.getElementById('addFacilityBtnText');

            form.addEventListener('submit', async function(e) {
                e.preventDefault();

                // Ask Alpine if it's OK (blocks if rooms exist)
                window.__alpineSubmitOk = false;
                const alpineRoot = document.querySelector('[x-data]');
                if (alpineRoot && window.Alpine) {
                    const data = window.Alpine.$data(alpineRoot);
                    await data.onSubmit();
                }
                if (!window.__alpineSubmitOk) {
                    return;
                }

                btn.disabled = true;
                spinner.classList.remove('d-none');
                btnText.textContent = 'Processing…';

                try {
                    const adminId = localStorage.getItem('adminId');
                    const alpine = window.Alpine.$data(alpineRoot);
                    const facilityRole = alpine.facilityRole;

                    if (facilityRole === 'Room' && !alpine.parentFacilityId) {
                        window.showToast('Please select a parent building for this room.', 'error');
                        return;
                    }

                    const requiredIds = ['facilityName', 'capacity', 'category', 'locationType'];
                    if (facilityRole !== 'Container') {
                        requiredIds.push('rentalFee', 'rateType');
                    }
                    for (const id of requiredIds) {
                        const el = document.getElementById(id);
                        if (!el || !String(el.value).trim()) {
                            window.showToast(
                                `Please fill in the ${id.replace(/([A-Z])/g, ' $1').toLowerCase()} field`,
                                'error');
                            el?.focus();
                            return;
                        }
                    }

                    // Departments: at least one required
                    if (!alpine.mainDepartmentIds || alpine.mainDepartmentIds.length === 0) {
                        window.showToast('Please select at least one department.', 'error');
                        return;
                    }

                    // Availability status: required for bookable facilities only
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
                        floor_level: facilityRole === 'Container' ?
                            null : (document.getElementById('floorLevel').value ?
                                parseInt(document.getElementById('floorLevel').value) :
                                null),
                        base_fee: facilityRole === 'Container' ?
                            0 : parseFloat(document.getElementById('rentalFee').value),
                        rate_type: facilityRole === 'Container' ?
                            'Per Hour' : document.getElementById('rateType').value,
                        total_levels: document.getElementById('totalLevels').value ? parseInt(
                            document.getElementById('totalLevels').value) : null,
                        departments: (alpine.mainDepartmentIds || []).map(Number),
                        status_id: facilityRole === 'Container' ?
                            (alpine.statuses[0]?.status_id || null) : alpine.mainStatusId,
                        created_by: adminId,
                        description: document.getElementById('description').value.trim() ||
                            'No description provided',
                        location_note: document.getElementById('locationNote').value.trim() ||
                            'No location details provided',
                        facility_role: facilityRole,
                        parent_facility_id: facilityRole === 'Room' ?
                            (parseInt(alpine.parentFacilityId) || null) : null
                    };

                    // ---- Build request: single store vs batch store ----
                    const hasRooms = alpine.rooms.length > 0;
                    let endpoint, requestBody;

                    if (hasRooms) {
                        // Validate each room client-side before sending
                        for (let i = 0; i < alpine.rooms.length; i++) {
                            const r = alpine.rooms[i];
                            const d = r.data;
                            const missing = [
                                [!d.facility_name || !d.facility_name.trim(), 'facility name'],
                                [!d.category_id, 'category'],
                                [!d.capacity || parseInt(d.capacity) < 1, 'capacity'],
                                [d.rental_fee === '' || d.rental_fee === null || isNaN(parseFloat(d.rental_fee)), 'rental fee'],
                                [!d.rate_type, 'rate type'],
                                [!d.location_type, 'location type'],
                                [!d.status_id, 'availability status']
                            ].find(([invalid]) => invalid);
                            if (missing) {
                                window.showToast(`Room ${i + 1}: ${missing[1]} is required.`, 'error');
                                return;
                            }
                        }

                        endpoint = '/api/admin/add-facilities-batch';
                        requestBody = {
                            facility: payload,
                            rooms: alpine.rooms.map(r => ({
                                facility_name: r.data.facility_name.trim(),
                                facility_code: (r.data.facility_code || '').trim() ||
                                    null,
                                description: (r.data.description || '').trim() || null,
                                location_note: (r.data.location_note || '').trim() ||
                                    null,
                                capacity: parseInt(r.data.capacity),
                                category_id: r.data.category_id,
                                subcategory_id: r.data.subcategory_id || null,
                                departments: (r.data.department_ids || []).map(Number),
                                location_type: r.data.location_type,
                                base_fee: parseFloat(r.data.rental_fee),
                                rate_type: r.data.rate_type,
                                status_id: r.data.status_id,
                                floor_level: r.data.floor_level ? parseInt(r.data
                                    .floor_level) : null,
                                total_levels: r.data.total_levels ? parseInt(r.data
                                    .total_levels) : null,
                            }))
                        };
                    } else {
                        endpoint = '/api/admin/add-facility';
                        requestBody = payload;
                    }

                    const res = await fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${localStorage.getItem('adminToken')}`,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(requestBody)
                    });

                    if (!res.ok) {
                        const err = await res.json().catch(() => ({}));
                        
                        // Extract the first specific validation error if available
                        let errorMessage = err.message || `Failed to create facility: ${res.status}`;
                        if (err.errors) {
                            const firstErrorKey = Object.keys(err.errors)[0];
                            if (firstErrorKey && err.errors[firstErrorKey].length > 0) {
                                errorMessage = err.errors[firstErrorKey][0];
                            }
                        }
                        
                        throw new Error(errorMessage);
                    }

                    const result = await res.json();
                    window.newFacilityId = result.data?.facility_id || result.facility_id;
                    if (!window.newFacilityId) throw new Error('No facility ID returned.');

                    // Upload images sequentially
                    for (const entry of window.pendingImageUploads) {
                        try {
                            const fd = new FormData();
                            fd.append('image', entry.file);
                            fd.append('image_type', entry.image_type);
                            fd.append('sort_order', entry.sort_order);

                            const upRes = await fetch(
                                `/api/admin/facilities/${window.newFacilityId}/images`, {
                                    method: 'POST',
                                    headers: {
                                        'Authorization': `Bearer ${localStorage.getItem('adminToken')}`,
                                        'Accept': 'application/json'
                                    },
                                    body: fd
                                });
                            if (!upRes.ok) {
                                console.error('image upload failed', await upRes.text());
                                window.showToast('Some images failed to upload.', 'error');
                            }
                        } catch (err) {
                            console.error(err);
                            window.showToast('Some images failed to upload.', 'error');
                        }
                    }

                    const roomCount = hasRooms ? alpine.rooms.length : 0;
                    const successMsg = roomCount > 0 ?
                        `Facility and ${roomCount} ${roomCount === 1 ? 'room' : 'rooms'} created successfully!` :
                        'Facility created successfully!';
                    window.showToast(successMsg, 'success');
                    setTimeout(() => {
                        window.location.href = '/admin/manage-facilities';
                    }, 1200);

                } catch (err) {
                    console.error(err);
                    window.showToast('Failed to create facility: ' + err.message, 'error');
                } finally {
                    btn.disabled = false;
                    spinner.classList.add('d-none');
                    btnText.textContent = 'Add New Facility';
                }
            });
        });
    </script>
@endsection
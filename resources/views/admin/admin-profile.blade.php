<template>
    <main id="main">
        <div class="profile-wrapper position-relative">
            <!-- Hero/Wallpaper Section -->
            <div class="profile-hero position-relative mb-5" :style="{ height: '200px', backgroundColor: '#f8f9fa' }">
                <div id="wallpaper-container" class="w-100 h-100"
                    :style="{
                        background: wallpaperUrl ? `url(${wallpaperUrl}) center center / cover no-repeat` : 
                        'url(https://res.cloudinary.com/dn98ntlkd/image/upload/v1751033948/verzp7lqedwsfn3hz8xf.jpg) center center / cover no-repeat'
                    }">
                </div>
                <button class="btn btn-light position-absolute bottom-0 end-0 m-3" @click="triggerWallpaperUpload">
                    <i class="bi bi-image me-2"></i>Change Cover
                </button>
            </div>
            <input type="file" id="wallpaper-upload" class="d-none" accept="image/*" @change="handleWallpaperUpload" ref="wallpaperUpload">

            <div class="container position-relative">
                <!-- Profile Avatar -->
                <div class="position-absolute" style="top: -70px; left: 50px; z-index: 10;">
                    <div class="position-relative">
                        <div class="avatar-container rounded-circle border border-4 border-white"
                            style="width: 150px; height: 150px; overflow: hidden;">
                            <img :src="profilePhotoUrl || 'https://res.cloudinary.com/dn98ntlkd/image/upload/v1751033911/ksdmh4mmpxdtjogdgjmm.png'"
                                class="w-100 h-100 object-fit-cover" id="profile-photo">
                        </div>
                        <button class="btn btn-sm btn-light rounded-circle position-absolute bottom-0 end-0 shadow-sm"
                            style="width: 32px; height: 32px;" @click="triggerPhotoUpload">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <input type="file" id="photo-upload" class="d-none" accept="image/*" @change="handlePhotoUpload" ref="photoUpload">
                    </div>
                </div>

                <!-- Profile Content -->
                <div class="row mt-5 pt-5 g-3">
                    <!-- Main Info Card -->
                    <div class="col-md-8">
                        <div class="card shadow-sm h-100">
                            <div class="card-body p-3">
                                <div v-if="loading" class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                                <div v-else class="mt-3 ms-3">
                                    <h2 class="card-title mb-4">{{ adminFullName }}</h2>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <p><strong>School ID:</strong> <span>{{ adminData.school_id || 'Not set' }}</span></p>
                                            <p><strong>Email:</strong> <span>{{ adminData.email }}</span></p>
                                            <p><strong>Contact:</strong> <span>{{ adminData.contact_number || 'Not set' }}</span></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Member Since:</strong> <span>{{ formatDate(adminData.created_at) }}</span></p>
                                            <p><strong>Last Updated:</strong> <span>{{ formatDate(adminData.updated_at) }}</span></p>
                                        </div>
                                    </div>

                                    <!-- ✨ Edit Profile button -->
                                    <div class="mt-3">
                                        <button type="button" class="btn btn-primary" @click="openEditModal">
                                            <i class="bi bi-pencil me-1"></i> Edit Profile
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Role & Departments Card -->
                    <div class="col-md-4 d-flex flex-column">
                        <div class="card shadow-sm mb-3">
                            <div class="card-body">
                                <h5 class="card-title mb-3">Role Details</h5>
                                <div v-if="adminData.role">
                                    <div class="d-flex align-items-center mb-3">
                                        <span class="badge bg-primary me-2">{{ adminData.role.role_title }}</span>
                                    </div>
                                    <p class="text-muted small">{{ adminData.role.description }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">Managing Departments</h5>
                                <div v-if="adminData.departments?.length > 0">
                                    <div v-for="dept in adminData.departments" :key="dept.department_id" 
                                         class="badge bg-light text-dark me-2 mb-2">
                                        {{ dept.department_name }}{{ dept.pivot? is_primary ? ' (Primary)' : '' }}
                                    </div>
                                </div>
                                <div v-else class="text-muted">No departments assigned</div>
                            </div>
                        </div>
                    </div>

                    <!-- Managing Facilities Card -->
                    <div class="col-12 mt-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-transparent border-bottom-0 d-flex justify-content-between align-items-center py-3">
                                <h5 class="card-title mb-0">Managing Facilities</h5>
                                <span v-if="isHeadAdmin" class="badge bg-info">Head Admin View - All Admins</span>
                                <button v-else type="button" class="btn btn-primary btn-sm" @click="openFacilityModal">
                                    <i class="bi bi-plus-circle me-1"></i> Add new
                                </button>
                            </div>
                            <div class="card-body">
                                <div id="facilities-content">
                                    <!-- Facilities content will be rendered by component logic -->
                                    <component :is="facilitiesComponent" :admins="allAdmins" v-if="isHeadAdmin && allAdmins.length" />
                                    <div v-else-if="!isHeadAdmin && adminFacilities.length" class="row g-4">
                                        <div v-for="facility in adminFacilities" :key="facility.admin_facility_id" 
                                             class="col-md-4 col-lg-3">
                                            <div class="card h-100 shadow-sm border-0 overflow-hidden">
                                                <div class="card-img-top" style="height: 160px; overflow: hidden;">
                                                    <img :src="facility.facility.images?.[0]?.image_url || 'https://res.cloudinary.com/dn98ntlkd/image/upload/v1750895337/oxvsxogzu9koqhctnf7s.webp'" 
                                                         class="w-100 h-100 object-fit-cover"
                                                         :alt="facility.facility.facility_name"
                                                         @error="handleImageError">
                                                    <div class="position-absolute top-0 end-0 m-2">
                                                        <span class="badge" :class="getStatusBadgeClass(facility.facility.status?.status_id)">
                                                            {{ facility.facility.status?.status_name || 'N/A' }}
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="card-body">
                                                    <h6 class="card-title mb-2 one-line-truncate"
                                                        :title="facility.facility.facility_name">
                                                        {{ facility.facility.facility_name }}
                                                    </h6>
                                                    <button class="btn btn-sm btn-danger w-100" 
                                                            @click="removeFacilityAssignment(facility.admin_facility_id)">
                                                        <i class="bi bi-trash me-1"></i> Remove
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="text-center py-5">
                                        <p class="text-muted">No facilities assigned</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Managing Services & Resources Card -->
                    <div class="col-12 mt-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-transparent border-bottom-0 d-flex justify-content-between align-items-center py-3">
                                <h5 class="card-title mb-0">Managing Services & Resources</h5>
                                <span v-if="isHeadAdmin" class="badge bg-info">Head Admin View - All Admins</span>
                                <button v-else type="button" class="btn btn-primary btn-sm" @click="openServiceModal">
                                    <i class="bi bi-plus-circle me-1"></i> Add new
                                </button>
                            </div>
                            <div class="card-body">
                                <div id="services-content">
                                    <component :is="servicesComponent" :admins="allAdmins" v-if="isHeadAdmin && allAdmins.length" />
                                    <div v-else-if="!isHeadAdmin && adminServices.length" class="row g-4">
                                        <div v-for="service in adminServices" :key="service.admin_service_id" 
                                             class="col-md-4 col-lg-3">
                                            <div class="card h-100 shadow-sm border-0">
                                                <div class="card-body text-center">
                                                    <div class="mb-3">
                                                        <i class="bi bi-gear display-4 text-primary"></i>
                                                    </div>
                                                    <h6 class="card-title one-line-truncate" :title="service.service_name">
                                                        {{ service.service_name }}
                                                    </h6>
                                                    <div class="mt-3">
                                                        <button class="btn btn-sm btn-danger w-100" 
                                                                @click="removeServiceAssignment(service.admin_service_id)">
                                                            <i class="bi bi-trash me-1"></i> Remove
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="text-center py-5">
                                        <i class="bi bi-gear display-6 text-muted mb-3"></i>
                                        <p class="text-muted">No services or resources assigned</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Profile Modal -->
        <div class="modal fade" id="editProfileModal" tabindex="-1" ref="editProfileModal">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Profile</h5>
                        <button type="button" class="btn-close" @click="closeEditModal"></button>
                    </div>

                    <div class="modal-body">
                        <form @submit.prevent="saveProfileChanges">
                            <div class="row g-3">
                                <!-- Names on one row -->
                                <div class="col-md-4">
                                    <label class="form-label">First Name</label>
                                    <input type="text" class="form-control" v-model="editForm.first_name" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Middle Name</label>
                                    <input type="text" class="form-control" v-model="editForm.middle_name">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" class="form-control" v-model="editForm.last_name" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label d-flex align-items-center">
                                        School ID
                                        <small class="text-muted ms-2">(Format: 00-0000-00)</small>
                                    </label>
                                    <input type="text" class="form-control" v-model="editForm.school_id"
                                           placeholder="00-0000-00" @input="formatSchoolId" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" v-model="editForm.email" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Contact Number</label>
                                    <input type="tel" class="form-control" v-model="editForm.contact_number"
                                           placeholder="e.g. 09123456789" required>
                                </div>

                                <!-- Role Dropdown - Only for Head Admin -->
                                <div class="col-md-6" v-if="isHeadAdmin">
                                    <label class="form-label">Role</label>
                                    <select class="form-select" v-model="editForm.role_id" required>
                                        <option value="">Select Role</option>
                                        <option v-for="role in roles" :key="role.role_id" :value="role.role_id">
                                            {{ role.role_title }}
                                        </option>
                                    </select>
                                </div>

                                <!-- New Password -->
                                <div :class="isHeadAdmin ? 'col-12' : 'col-md-6'">
                                    <label class="form-label d-flex align-items-center">
                                        New Password
                                        <small class="text-muted ms-2">(Leave blank to keep current)</small>
                                    </label>
                                    <input type="password" class="form-control" v-model="editForm.password"
                                           placeholder="New Password">
                                </div>

                                <!-- Departments Section - Only for Head Admin -->
                                <div class="col-12" v-if="isHeadAdmin">
                                    <label class="form-label">Departments</label>
                                    <div class="d-flex flex-wrap gap-2">
                                        <button v-for="dept in departments" :key="dept.department_id"
                                                type="button"
                                                class="btn"
                                                :class="selectedDepartments.includes(dept.department_id) ? 'btn-primary' : 'btn-outline-primary'"
                                                @click="toggleDepartment(dept.department_id)">
                                            {{ dept.department_name }} ({{ dept.department_code }})
                                        </button>
                                    </div>
                                    <div class="form-text">
                                        Click to select/deselect departments. First selected becomes primary.
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="closeEditModal">Cancel</button>
                        <button type="button" class="btn btn-primary" @click="saveProfileChanges" 
                                :disabled="saving">
                            <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                            Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Facility Modal -->
        <div class="modal fade" id="addFacilityModal" tabindex="-1" ref="addFacilityModal">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Managing Facility</h5>
                        <button type="button" class="btn-close" @click="closeFacilityModal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <!-- Admin Selection (only for Head Admin) -->
                            <div class="col-12 mb-4" v-if="isHeadAdmin">
                                <label class="form-label">Select Admin</label>
                                <select class="form-select" v-model="selectedAdminId">
                                    <option value="">Select an admin (optional)</option>
                                    <option :value="adminData.admin_id">Myself ({{ adminData.first_name }} {{ adminData.last_name }})</option>
                                    <option v-for="admin in otherAdmins" :key="admin.admin_id" :value="admin.admin_id">
                                        {{ admin.first_name }} {{ admin.last_name }} - {{ admin.role?.role_title }} ({{ admin.email }})
                                    </option>
                                </select>
                                <div class="form-text">
                                    Select an admin to assign facilities to. Leave empty to assign to yourself.
                                </div>
                            </div>

                            <!-- Facility Search -->
                            <div class="col-12 mb-4">
                                <label class="form-label">Search Facilities</label>
                                <div class="input-group mb-3">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control" v-model="facilitySearch"
                                           placeholder="Search by facility name, building code, or floor level...">
                                    <button class="btn btn-outline-secondary" type="button" @click="clearFacilitySearch">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Facilities List -->
                            <div class="col-12">
                                <label class="form-label mb-3">Available Facilities</label>
                                <div v-if="loadingFacilities" class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading facilities...</span>
                                    </div>
                                </div>
                                <div v-else>
                                    <div class="list-group" style="max-height: 300px; overflow-y: auto;">
                                        <div v-for="facility in filteredFacilities" :key="facility.facility_id"
                                             class="list-group-item list-group-item-action"
                                             :class="{ active: isFacilitySelected(facility.facility_id) }"
                                             @click="toggleFacility(facility)">
                                            <div class="d-flex align-items-center">
                                                <div class="form-check me-3">
                                                    <input class="form-check-input" type="checkbox" 
                                                           :checked="isFacilitySelected(facility.facility_id)"
                                                           @click.stop @change="toggleFacility(facility)">
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1">{{ facility.facility_name }}</h6>
                                                </div>
                                                <div class="badge bg-light text-dark">
                                                    {{ facility.category?.category_name || 'Uncategorized' }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="filteredFacilities.length === 0" class="text-center py-4">
                                        <i class="bi bi-building display-4 text-muted mb-3"></i>
                                        <p class="text-muted">No facilities found</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Selected Facilities Summary -->
                            <div class="col-12 mt-4" v-if="selectedFacilities.length">
                                <label class="form-label">Selected Facilities</label>
                                <div class="card border-primary">
                                    <div class="card-body p-3">
                                        <div v-for="(facility, index) in selectedFacilities" :key="facility.facility_id"
                                             class="selected-facility-item d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <strong>{{ facility.facility_name }}</strong>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-link text-danger" 
                                                    @click="removeSelectedFacility(index)">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="closeFacilityModal">Cancel</button>
                        <button type="button" class="btn btn-primary" @click="saveFacilityAssignments" 
                                :disabled="!canSaveFacilities || savingFacilities">
                            <span v-if="savingFacilities" class="spinner-border spinner-border-sm me-2"></span>
                            <i class="bi bi-save me-1"></i> Save Assignment
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Service Modal -->
        <div class="modal fade" id="addServiceModal" tabindex="-1" ref="addServiceModal">
            <div class="dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Assign New Service or Resource</h5>
                        <button type="button" class="btn-close" @click="closeServiceModal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <!-- Service Selection -->
                            <div class="col-12 mb-4">
                                <label class="form-label">Select Service/Resource</label>
                                <div v-if="loadingServices" class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading services...</span>
                                    </div>
                                </div>
                                <div v-else>
                                    <div class="list-group" style="max-height: 300px; overflow-y: auto;">
                                        <div v-for="service in availableServices" :key="service.service_id"
                                             class="list-group-item list-group-item-action"
                                             :class="{ active: isServiceSelected(service.service_id) }"
                                             @click="toggleService(service)">
                                            <div class="d-flex align-items-center">
                                                <div class="form-check me-3">
                                                    <input class="form-check-input" type="checkbox" 
                                                           :checked="isServiceSelected(service.service_id)"
                                                           @click.stop @change="toggleService(service)">
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1">{{ service.service_name }}</h6>
                                                </div>
                                                <div>
                                                    <i class="bi bi-gear text-muted"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="availableServices.length === 0" class="text-center py-4">
                                        <i class="bi bi-gear display-4 text-muted mb-3"></i>
                                        <p class="text-muted">No services available</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Selected Services Summary -->
                            <div class="col-12 mt-4" v-if="selectedServices.length">
                                <label class="form-label">Selected Services</label>
                                <div class="card border-primary">
                                    <div class="card-body p-3">
                                        <div v-for="(service, index) in selectedServices" :key="service.service_id"
                                             class="selected-service-item d-flex justify-content-between align-items-center mb-2">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-gear text-primary me-3"></i>
                                                <div>
                                                    <strong>{{ service.service_name }}</strong>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-link text-danger" 
                                                    @click="removeSelectedService(index)">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="closeServiceModal">Cancel</button>
                        <button type="button" class="btn btn-primary" @click="saveServiceAssignments" 
                                :disabled="selectedServices.length === 0 || savingServices">
                            <span v-if="savingServices" class="spinner-border spinner-border-sm me-2"></span>
                            <i class="bi bi-save me-1"></i> Assign Services
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>
</template>

<script>
import { Modal } from 'bootstrap';
import axios from 'axios';

export default {
    name: 'AdminProfile',
    data() {
        return {
            loading: true,
            saving: false,
            savingFacilities: false,
            savingServices: false,
            loadingFacilities: false,
            loadingServices: false,
            
            adminData: {},
            allAdmins: [],
            roles: [],
            departments: [],
            allFacilities: [],
            allServices: [],
            
            adminFacilities: [],
            adminServices: [],
            
            // Edit form
            editForm: {
                first_name: '',
                middle_name: '',
                last_name: '',
                school_id: '',
                email: '',
                contact_number: '',
                role_id: null,
                password: ''
            },
            selectedDepartments: [],
            
            // Facility modal
            selectedAdminId: '',
            facilitySearch: '',
            selectedFacilities: [],
            
            // Service modal
            selectedServices: [],
            
            // Modals
            editModal: null,
            facilityModal: null,
            serviceModal: null,
            
            // Cloudinary config
            cloudinaryConfig: {
                cloudName: 'dn98ntlkd',
                apiKey: 'REDACTED_CLOUDINARY_KEY',
                uploadPresetPhoto: 'admin-photos',
                uploadPresetWallpaper: 'admin-wallpapers'
            }
        };
    },
    computed: {
        isHeadAdmin() {
            return this.adminData.role?.role_title === 'Head Admin';
        },
        adminFullName() {
            if (!this.adminData.first_name) return '';
            return `${this.adminData.first_name} ${this.adminData.last_name}`.trim();
        },
        profilePhotoUrl() {
            return this.adminData.photo_url;
        },
        wallpaperUrl() {
            return this.adminData.wallpaper_url;
        },
        otherAdmins() {
            return this.allAdmins.filter(admin => admin.admin_id !== this.adminData.admin_id);
        },
        filteredFacilities() {
            if (!this.facilitySearch.trim()) return this.allFacilities;
            
            const search = this.facilitySearch.toLowerCase();
            return this.allFacilities.filter(facility => 
                facility.facility_name.toLowerCase().includes(search) ||
                (facility.building_code && facility.building_code.toLowerCase().includes(search)) ||
                (facility.description && facility.description.toLowerCase().includes(search)) ||
                (facility.department?.department_name && facility.department.department_name.toLowerCase().includes(search))
            );
        },
        availableServices() {
            return this.allServices;
        },
        canSaveFacilities() {
            if (this.isHeadAdmin) {
                return this.selectedAdminId && this.selectedFacilities.length > 0;
            }
            return this.selectedFacilities.length > 0;
        },
        facilitiesComponent() {
            return {
                template: `
                    <div class="accordion" id="facilitiesAccordion">
                        <div v-for="(admin, index) in admins" :key="admin.admin_id" 
                             class="accordion-item mb-3 border rounded">
                            <h2 class="accordion-header" :id="'headingFacility' + index">
                                <button class="accordion-button" :class="{ collapsed: index > 0 }" 
                                        type="button" data-bs-toggle="collapse" 
                                        :data-bs-target="'#collapseFacility' + index">
                                    <div class="d-flex align-items-center w-100">
                                        <div class="me-3">
                                            <i class="bi bi-person-circle fs-4"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <strong>{{ admin.first_name }} {{ admin.middle_name || '' }} {{ admin.last_name }}</strong>
                                            <br>
                                            <small class="text-muted">
                                                {{ admin.role?.role_title || 'No Role' }} | {{ admin.email }} | ID: {{ admin.school_id || 'N/A' }}
                                            </small>
                                        </div>
                                        <span class="badge" :class="admin.facilities?.length ? 'bg-primary' : 'bg-secondary'">
                                            {{ admin.facilities?.length || 0 }} Facilities
                                        </span>
                                    </div>
                                </button>
                            </h2>
                            <div :id="'collapseFacility' + index" class="accordion-collapse collapse" 
                                 :class="{ show: index === 0 }" :aria-labelledby="'headingFacility' + index">
                                <div class="accordion-body">
                                    <div v-if="admin.facilities?.length" class="row g-3">
                                        <div v-for="facility in admin.facilities" :key="facility.facility_id" 
                                             class="col-md-4 col-lg-3">
                                            <div class="card h-100 border-0 bg-light">
                                                <div class="card-body p-3">
                                                    <div class="d-flex align-items-center mb-2">
                                                        <i class="bi bi-building text-primary me-2"></i>
                                                        <h6 class="card-title mb-0 one-line-truncate" :title="facility.facility_name">
                                                            {{ facility.facility_name }}
                                                        </h6>
                                                    </div>
                                                    <small class="text-muted d-block">
                                                        <i class="bi bi-hash me-1"></i>ID: {{ facility.facility_id }}
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="text-center py-4">
                                        <i class="bi bi-building-slash display-6 text-muted mb-2"></i>
                                        <p class="text-muted mb-0">No facilities assigned</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `,
                props: ['admins']
            };
        },
        servicesComponent() {
            return {
                template: `
                    <div class="accordion" id="servicesAccordion">
                        <div v-for="(admin, index) in admins" :key="admin.admin_id" 
                             class="accordion-item mb-3 border rounded">
                            <h2 class="accordion-header" :id="'headingService' + index">
                                <button class="accordion-button" :class="{ collapsed: index > 0 }" 
                                        type="button" data-bs-toggle="collapse" 
                                        :data-bs-target="'#collapseService' + index">
                                    <div class="d-flex align-items-center w-100">
                                        <div class="me-3">
                                            <i class="bi bi-person-badge fs-4"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <strong>{{ admin.first_name }} {{ admin.middle_name || '' }} {{ admin.last_name }}</strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        {{ admin.role?.role_title || 'No Role' }} | {{ admin.email }}
                                                    </small>
                                                </div>
                                            </div>
                                            <small v-if="admin.departments?.length" class="text-muted d-block mt-1">
                                                <i class="bi bi-diagram-3 me-1"></i>
                                                {{ admin.departments.map(d => d.department_name).join(', ') }}
                                            </small>
                                        </div>
                                        <span class="badge" :class="admin.services?.length ? 'bg-success' : 'bg-secondary'">
                                            {{ admin.services?.length || 0 }} Services
                                        </span>
                                    </div>
                                </button>
                            </h2>
                            <div :id="'collapseService' + index" class="accordion-collapse collapse" 
                                 :class="{ show: index === 0 }" :aria-labelledby="'headingService' + index">
                                <div class="accordion-body">
                                    <div v-if="admin.services?.length" class="row g-3">
                                        <div v-for="service in admin.services" :key="service.service_id" 
                                             class="col-md-4 col-lg-3">
                                            <div class="card h-100 border-0 bg-light">
                                                <div class="card-body p-3">
                                                    <div class="d-flex align-items-center mb-2">
                                                        <i class="bi bi-gear text-success me-2"></i>
                                                        <h6 class="card-title mb-0 one-line-truncate" :title="service.service_name">
                                                            {{ service.service_name }}
                                                        </h6>
                                                    </div>
                                                    <small class="text-muted d-block">
                                                        <i class="bi bi-hash me-1"></i>ID: {{ service.service_id }}
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="text-center py-4">
                                        <i class="bi bi-gear-wide-connected display-6 text-muted mb-2"></i>
                                        <p class="text-muted mb-0">No services assigned</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `,
                props: ['admins']
            };
        }
    },
    methods: {
        async loadProfile() {
            const token = localStorage.getItem('adminToken');
            if (!token) {
                window.location.href = '/admin/login';
                return;
            }

            try {
                const response = await axios.get('/api/admin/profile', {
                    headers: { Authorization: `Bearer ${token}` }
                });
                
                this.adminData = response.data;
                
                // Store in localStorage for other components
                localStorage.setItem('adminId', this.adminData.admin_id);
                localStorage.setItem('adminRoleTitle', this.adminData.role?.role_title);
                localStorage.setItem('adminRoleId', this.adminData.role?.role_id);
                
                await Promise.all([
                    this.loadManagingFacilities(),
                    this.loadManagingServices()
                ]);
                
                if (this.isHeadAdmin) {
                    await this.loadAllAdmins();
                }
                
                this.loading = false;
            } catch (error) {
                console.error('Error loading profile:', error);
                this.loading = false;
            }
        },
        
        async loadManagingFacilities() {
            if (this.isHeadAdmin) {
                await this.loadAllAdmins();
                return;
            }
            
            const token = localStorage.getItem('adminToken');
            try {
                const response = await axios.get(`/api/admin-facilities/admin/${this.adminData.admin_id}`, {
                    headers: { Authorization: `Bearer ${token}` }
                });
                
                if (response.data.success) {
                    this.adminFacilities = response.data.data.facilities;
                }
            } catch (error) {
                console.error('Error loading facilities:', error);
            }
        },
        
        async loadManagingServices() {
            if (this.isHeadAdmin) {
                await this.loadAllAdmins();
                return;
            }
            
            const token = localStorage.getItem('adminToken');
            try {
                const adminServicesResponse = await axios.get('/api/admin-services', {
                    headers: { Authorization: `Bearer ${token}` }
                });
                
                const adminServices = adminServicesResponse.data.filter(
                    as => as.admin_id == this.adminData.admin_id
                );
                
                if (adminServices.length) {
                    const servicesResponse = await axios.get('/api/extra-services', {
                        headers: { Authorization: `Bearer ${token}` }
                    });
                    
                    this.adminServices = adminServices
                        .map(adminService => {
                            const service = servicesResponse.data.find(
                                s => s.service_id == adminService.service_id
                            );
                            if (service) {
                                return {
                                    ...service,
                                    admin_service_id: adminService.admin_service_id || adminService.id
                                };
                            }
                            return null;
                        })
                        .filter(s => s !== null);
                }
            } catch (error) {
                console.error('Error loading services:', error);
            }
        },
        
        async loadAllAdmins() {
            const token = localStorage.getItem('adminToken');
            try {
                const response = await axios.get('/api/admins', {
                    headers: { Authorization: `Bearer ${token}` }
                });
                this.allAdmins = response.data;
            } catch (error) {
                console.error('Error loading admins:', error);
            }
        },
        
        async loadRolesAndDepartments() {
            const token = localStorage.getItem('adminToken');
            try {
                const [rolesResponse, deptResponse] = await Promise.all([
                    axios.get('/api/admin-role', { headers: { Authorization: `Bearer ${token}` } }),
                    axios.get('/api/departments', { headers: { Authorization: `Bearer ${token}` } })
                ]);
                
                this.roles = rolesResponse.data;
                this.departments = deptResponse.data;
            } catch (error) {
                console.error('Error loading roles and departments:', error);
                throw error;
            }
        },
        
        async loadAvailableFacilities() {
            this.loadingFacilities = true;
            const token = localStorage.getItem('adminToken');
            
            try {
                // Get current admin's assigned facilities
                const assignedResponse = await axios.get(`/api/admin-facilities/admin/${this.adminData.admin_id}`, {
                    headers: { Authorization: `Bearer ${token}` }
                });
                
                const assignedFacilityIds = assignedResponse.data.success
                    ? assignedResponse.data.data.facilities.map(f => f.facility_id)
                    : [];
                
                // Load all facilities
                const facilitiesResponse = await axios.get('/api/facilities?per_page=100', {
                    headers: { Authorization: `Bearer ${token}` }
                });
                
                this.allFacilities = (facilitiesResponse.data.data || [])
                    .filter(facility => !assignedFacilityIds.includes(facility.facility_id));
                    
            } catch (error) {
                console.error('Error loading facilities:', error);
            } finally {
                this.loadingFacilities = false;
            }
        },
        
        async loadAvailableServices() {
            this.loadingServices = true;
            const token = localStorage.getItem('adminToken');
            
            try {
                // Get current admin's assigned services
                const assignedResponse = await axios.get('/api/admin-services', {
                    headers: { Authorization: `Bearer ${token}` }
                });
                
                const assignedServiceIds = assignedResponse.data
                    .filter(as => as.admin_id == this.adminData.admin_id)
                    .map(as => as.service_id);
                
                // Load all services
                const servicesResponse = await axios.get('/api/extra-services', {
                    headers: { Authorization: `Bearer ${token}` }
                });
                
                this.allServices = servicesResponse.data
                    .filter(service => !assignedServiceIds.includes(service.service_id));
                    
            } catch (error) {
                console.error('Error loading services:', error);
            } finally {
                this.loadingServices = false;
            }
        },
        
        formatDate(date) {
            if (!date) return '';
            return new Date(date).toLocaleDateString();
        },
        
        formatSchoolId(event) {
            let digits = event.target.value.replace(/\D/g, '');
            if (digits.length > 2 && digits.length <= 6) {
                digits = digits.slice(0, 2) + '-' + digits.slice(2);
            } else if (digits.length > 6) {
                digits = digits.slice(0, 2) + '-' + digits.slice(2, 6) + '-' + digits.slice(6, 8);
            }
            this.editForm.school_id = digits;
        },
        
        getStatusBadgeClass(statusId) {
            if (statusId === 1) return 'bg-success';
            if (statusId === 2) return 'bg-warning';
            if (statusId === 3) return 'bg-danger';
            return 'bg-secondary';
        },
        
        handleImageError(event) {
            event.target.src = 'https://res.cloudinary.com/dn98ntlkd/image/upload/v1750895337/oxvsxogzu9koqhctnf7s.webp';
        },
        
        // Photo and Wallpaper Upload
        triggerPhotoUpload() {
            this.$refs.photoUpload.click();
        },
        
        triggerWallpaperUpload() {
            this.$refs.wallpaperUpload.click();
        },
        
        async handlePhotoUpload(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            const originalSrc = this.profilePhotoUrl;
            const oldPublicId = this.adminData.photo_public_id;
            
            try {
                // Upload to Cloudinary
                const formData = new FormData();
                formData.append('file', file);
                formData.append('upload_preset', this.cloudinaryConfig.uploadPresetPhoto);
                
                const uploadResponse = await axios.post(
                    `https://api.cloudinary.com/v1_1/${this.cloudinaryConfig.cloudName}/upload`,
                    formData
                );
                
                if (!uploadResponse.data.secure_url || !uploadResponse.data.public_id) {
                    throw new Error('Invalid Cloudinary response');
                }
                
                // Update database
                const token = localStorage.getItem('adminToken');
                await axios.post('/api/admin/update-photo-records', {
                    photo_url: uploadResponse.data.secure_url,
                    photo_public_id: uploadResponse.data.public_id,
                    type: 'photo'
                }, {
                    headers: { Authorization: `Bearer ${token}` }
                });
                
                // Update local data
                this.adminData.photo_url = uploadResponse.data.secure_url;
                this.adminData.photo_public_id = uploadResponse.data.public_id;
                
                // Delete old image if not default
                if (oldPublicId && !['ksdmh4mmpxdtjogdgjmm', 'verzp7lqedwsfn3hz8xf'].includes(oldPublicId)) {
                    await axios.post('/api/admin/delete-cloudinary-image', {
                        public_id: oldPublicId,
                        type: 'photo'
                    }, {
                        headers: { Authorization: `Bearer ${token}` }
                    });
                }
                
                this.$toast?.success('Profile photo updated successfully!');
                
            } catch (error) {
                console.error('Error uploading photo:', error);
                this.$toast?.error('Failed to upload photo: ' + error.message);
                this.adminData.photo_url = originalSrc;
            } finally {
                event.target.value = '';
            }
        },
        
        async handleWallpaperUpload(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            const originalBackground = this.wallpaperUrl;
            const oldPublicId = this.adminData.wallpaper_public_id;
            
            try {
                // Upload to Cloudinary
                const formData = new FormData();
                formData.append('file', file);
                formData.append('upload_preset', this.cloudinaryConfig.uploadPresetWallpaper);
                
                const uploadResponse = await axios.post(
                    `https://api.cloudinary.com/v1_1/${this.cloudinaryConfig.cloudName}/upload`,
                    formData
                );
                
                if (!uploadResponse.data.secure_url || !uploadResponse.data.public_id) {
                    throw new Error('Invalid Cloudinary response');
                }
                
                // Update database
                const token = localStorage.getItem('adminToken');
                await axios.post('/api/admin/update-photo-records', {
                    wallpaper_url: uploadResponse.data.secure_url,
                    wallpaper_public_id: uploadResponse.data.public_id,
                    type: 'wallpaper'
                }, {
                    headers: { Authorization: `Bearer ${token}` }
                });
                
                // Update local data
                this.adminData.wallpaper_url = uploadResponse.data.secure_url;
                this.adminData.wallpaper_public_id = uploadResponse.data.public_id;
                
                // Delete old image if not default
                if (oldPublicId && !['ksdmh4mmpxdtjogdgjmm', 'verzp7lqedwsfn3hz8xf'].includes(oldPublicId)) {
                    await axios.post('/api/admin/delete-cloudinary-image', {
                        public_id: oldPublicId,
                        type: 'wallpaper'
                    }, {
                        headers: { Authorization: `Bearer ${token}` }
                    });
                }
                
                this.$toast?.success('Wallpaper updated successfully!');
                
            } catch (error) {
                console.error('Error uploading wallpaper:', error);
                this.$toast?.error('Failed to upload wallpaper: ' + error.message);
                this.adminData.wallpaper_url = originalBackground;
            } finally {
                event.target.value = '';
            }
        },
        
        // Edit Profile Modal
        openEditModal() {
            this.editForm = {
                first_name: this.adminData.first_name || '',
                middle_name: this.adminData.middle_name || '',
                last_name: this.adminData.last_name || '',
                school_id: this.adminData.school_id || '',
                email: this.adminData.email || '',
                contact_number: this.adminData.contact_number || '',
                role_id: this.adminData.role_id,
                password: ''
            };
            
            if (this.adminData.departments) {
                this.selectedDepartments = this.adminData.departments.map(d => d.department_id);
            }
            
            this.loadRolesAndDepartments().then(() => {
                this.editModal.show();
            });
        },
        
        closeEditModal() {
            this.editModal.hide();
        },
        
        toggleDepartment(deptId) {
            const index = this.selectedDepartments.indexOf(deptId);
            if (index === -1) {
                this.selectedDepartments.push(deptId);
            } else {
                this.selectedDepartments.splice(index, 1);
            }
        },
        
        async saveProfileChanges() {
            // Validate School ID
            const schoolIdPattern = /^\d{2}-\d{4}-\d{2}$/;
            if (!schoolIdPattern.test(this.editForm.school_id)) {
                this.$toast?.error('School ID must follow the format ##-####-##');
                return;
            }
            
            // Validate department selection
            if (this.isHeadAdmin) {
                const noDeptRequiredRoleIds = [1, 2];
                if (this.selectedDepartments.length === 0 && !noDeptRequiredRoleIds.includes(this.editForm.role_id)) {
                    this.$toast?.error('Please select at least one department');
                    return;
                }
            }
            
            this.saving = true;
            
            const jsonData = {
                first_name: this.editForm.first_name,
                last_name: this.editForm.last_name,
                middle_name: this.editForm.middle_name,
                school_id: this.editForm.school_id,
                email: this.editForm.email,
                contact_number: this.editForm.contact_number,
                role_id: this.editForm.role_id
            };
            
            if (this.isHeadAdmin) {
                jsonData.department_ids = this.selectedDepartments;
            }
            
            if (this.editForm.password) {
                jsonData.password = this.editForm.password;
            }
            
            try {
                const token = localStorage.getItem('adminToken');
                await axios.post(`/api/admin/update/${this.adminData.admin_id}`, jsonData, {
                    headers: { Authorization: `Bearer ${token}` }
                });
                
                this.$toast?.success('Profile updated successfully!');
                this.closeEditModal();
                setTimeout(() => location.reload(), 1000);
                
            } catch (error) {
                console.error('Error updating profile:', error);
                this.$toast?.error('Failed to update profile: ' + (error.response?.data?.message || error.message));
            } finally {
                this.saving = false;
            }
        },
        
        // Facility Modal
        async openFacilityModal() {
            this.selectedFacilities = [];
            this.facilitySearch = '';
            this.selectedAdminId = '';
            
            await this.loadAvailableFacilities();
            this.facilityModal.show();
        },
        
        closeFacilityModal() {
            this.facilityModal.hide();
        },
        
        isFacilitySelected(facilityId) {
            return this.selectedFacilities.some(f => f.facility_id === facilityId);
        },
        
        toggleFacility(facility) {
            const index = this.selectedFacilities.findIndex(f => f.facility_id === facility.facility_id);
            if (index === -1) {
                this.selectedFacilities.push(facility);
            } else {
                this.selectedFacilities.splice(index, 1);
            }
        },
        
        removeSelectedFacility(index) {
            this.selectedFacilities.splice(index, 1);
        },
        
        clearFacilitySearch() {
            this.facilitySearch = '';
        },
        
        async saveFacilityAssignments() {
            const targetAdminId = this.isHeadAdmin && this.selectedAdminId 
                ? this.selectedAdminId 
                : this.adminData.admin_id;
            
            if (!targetAdminId || this.selectedFacilities.length === 0) return;
            
            this.savingFacilities = true;
            
            const requestData = {
                admin_id: parseInt(targetAdminId),
                facility_ids: this.selectedFacilities.map(f => parseInt(f.facility_id))
            };
            
            try {
                const token = localStorage.getItem('adminToken');
                const response = await axios.post('/api/admin-facilities/batch', requestData, {
                    headers: { Authorization: `Bearer ${token}` }
                });
                
                this.$toast?.success(response.data.message || 'Facilities assigned successfully!');
                this.closeFacilityModal();
                
                // Refresh data
                await this.loadManagingFacilities();
                
            } catch (error) {
                console.error('Error saving facilities:', error);
                this.$toast?.error('Failed to save assignments: ' + (error.response?.data?.message || error.message));
            } finally {
                this.savingFacilities = false;
            }
        },
        
        async removeFacilityAssignment(assignmentId) {
            if (!confirm('Remove this assignment?')) return;
            
            try {
                const token = localStorage.getItem('adminToken');
                await axios.delete(`/api/admin-facilities/${assignmentId}`, {
                    headers: { Authorization: `Bearer ${token}` }
                });
                
                this.$toast?.success('Removed successfully');
                await this.loadManagingFacilities();
                
            } catch (error) {
                console.error('Error removing facility:', error);
                this.$toast?.error('Failed to remove: ' + (error.response?.data?.message || error.message));
            }
        },
        
        // Service Modal
        async openServiceModal() {
            this.selectedServices = [];
            await this.loadAvailableServices();
            this.serviceModal.show();
        },
        
        closeServiceModal() {
            this.serviceModal.hide();
        },
        
        isServiceSelected(serviceId) {
            return this.selectedServices.some(s => s.service_id === serviceId);
        },
        
        toggleService(service) {
            const index = this.selectedServices.findIndex(s => s.service_id === service.service_id);
            if (index === -1) {
                this.selectedServices.push(service);
            } else {
                this.selectedServices.splice(index, 1);
            }
        },
        
        removeSelectedService(index) {
            this.selectedServices.splice(index, 1);
        },
        
        async saveServiceAssignments() {
            if (this.selectedServices.length === 0) return;
            
            this.savingServices = true;
            
            const requestData = {
                service_ids: this.selectedServices.map(s => parseInt(s.service_id))
            };
            
            try {
                const token = localStorage.getItem('adminToken');
                const response = await axios.post('/api/extra-services/assign', requestData, {
                    headers: { Authorization: `Bearer ${token}` }
                });
                
                this.$toast?.success(response.data.message || 'Services assigned successfully!');
                this.closeServiceModal();
                
                // Refresh data
                await this.loadManagingServices();
                
            } catch (error) {
                console.error('Error saving services:', error);
                this.$toast?.error('Failed to assign services: ' + (error.response?.data?.message || error.message));
            } finally {
                this.savingServices = false;
            }
        },
        
        async removeServiceAssignment(assignmentId) {
            if (!confirm('Are you sure you want to remove this service assignment?')) return;
            
            try {
                const token = localStorage.getItem('adminToken');
                await axios.delete(`/api/admin-services/${assignmentId}`, {
                    headers: { Authorization: `Bearer ${token}` }
                });
                
                this.$toast?.success('Service removed successfully');
                await this.loadManagingServices();
                
            } catch (error) {
                console.error('Error removing service:', error);
                this.$toast?.error('Failed to remove service: ' + (error.response?.data?.message || error.message));
            }
        }
    },
    
    mounted() {
        // Initialize modals
        this.editModal = new Modal(this.$refs.editProfileModal);
        this.facilityModal = new Modal(this.$refs.addFacilityModal);
        this.serviceModal = new Modal(this.$refs.addServiceModal);
        
        // Load profile data
        this.loadProfile();
    }
};
</script>

<style scoped>
.card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border-radius: 12px;
    overflow: hidden;
    border: 0 !important;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
}

.card-img-container {
    position: relative;
    height: 160px;
    overflow: hidden;
}

.card-img-container img {
    transition: transform 0.4s ease;
}

.status-badge {
    font-size: 0.7rem;
    padding: 4px 8px;
    border-radius: 20px;
    backdrop-filter: blur(4px);
    background-color: rgba(255, 255, 255, 0.9);
}

.text-truncate-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
}

.department-text {
    display: block;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

#facility-list .list-group-item {
    cursor: pointer;
    transition: all 0.2s;
}

#facility-list .list-group-item:hover {
    background-color: #f8f9fa;
}

#facility-list .list-group-item.active {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

#facility-list .list-group-item .form-check-input {
    cursor: pointer;
}

.selected-facility-item {
    padding: 8px 12px;
    background-color: #f8f9fa;
    border-radius: 6px;
    margin-bottom: 8px;
    border-left: 4px solid #0d6efd;
}

.selected-facility-item:last-child {
    margin-bottom: 0;
}

.selected-facility-item .remove-facility {
    cursor: pointer;
    color: #dc3545;
}

.selected-facility-item .remove-facility:hover {
    color: #b02a37;
}

#department-buttons-container button {
    display: inline-flex !important;
    width: auto !important;
}

#password-full-width-container .form-control,
#password-half-width-container .form-control {
    width: 100%;
}

main#main .profile-wrapper .profile-hero {
    --side-gap: clamp(20px, 9vw, 150px);
    height: clamp(180px, 20vh, 300px) !important;
    width: calc(100vw - (2 * var(--side-gap))) !important;
    position: relative !important;
    left: 50% !important;
    right: 50% !important;
    margin-left: calc(-50vw + var(--side-gap)) !important;
    margin-right: calc(-50vw + var(--side-gap)) !important;
    margin-top: 0 !important;
    margin-bottom: 0 !important;
    padding-top: 0 !important;
    max-width: calc(100vw - (2 * var(--side-gap))) !important;
}

main#main .profile-wrapper {
    margin-top: 0 !important;
    padding-top: 0 !important;
}

body {
    padding-top: 0 !important;
}

main#main {
    margin-top: 0 !important;
    padding-top: 0 !important;
}

.container.position-relative {
    margin-top: 0 !important;
    padding-top: 0 !important;
}

#wallpaper-container {
    margin-top: 0 !important;
    padding-top: 0 !important;
}

.one-line-truncate {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

#service-list .list-group-item {
    cursor: pointer;
    transition: all 0.2s;
}

#service-list .list-group-item:hover {
    background-color: #f8f9fa;
}

#service-list .list-group-item.active {
    background-color: #0d6efd;
    border-color: #0d6efd;
    color: white;
}

#service-list .list-group-item.active .form-check-input {
    background-color: white;
    border-color: white;
}

#service-list .list-group-item .form-check-input {
    cursor: pointer;
}

.selected-service-item {
    padding: 8px 12px;
    background-color: #f8f9fa;
    border-radius: 6px;
    margin-bottom: 8px;
    border-left: 4px solid #0d6efd;
}

.selected-service-item:last-child {
    margin-bottom: 0;
}

.selected-service-item .remove-service {
    cursor: pointer;
    color: #dc3545;
    padding: 0;
    background: none;
    border: none;
}

.selected-service-item .remove-service:hover {
    color: #b02a37;
}
</style>
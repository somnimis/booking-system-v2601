/**
 * BookingCatalog Module
 * A reusable class for managing booking catalogs with facilities, rooms, and equipment
 */

class BookingCatalog {
    constructor(config = {}) {
        // Configuration
        this.config = {
            containerId: config.containerId || "catalogItemsContainer",
            loadingIndicatorId: config.loadingIndicatorId || "loadingIndicator",
            categoryFilterId: config.categoryFilterId || "categoryFilterList",
            paginationId: config.paginationId || "pagination",
            requisitionBadgeId: config.requisitionBadgeId || "requisitionBadge",
            heroTitleId: config.heroTitleId || "catalogHeroTitle",
            searchInputId: config.searchInputId || "catalogSearchInput",
            searchFormId: config.searchFormId || "catalogSearchForm",
            clearSearchBtnId: config.clearSearchBtnId || "clearSearchBtn",
            filterDropdownId: config.filterDropdownId || "filterDropdown",
            filterDropdownMenuId:
                config.filterDropdownMenuId || "filterDropdownMenu",
            itemsPerPage: config.itemsPerPage || 6,
            defaultCatalogType: config.defaultCatalogType || "venues",
            defaultLayout: config.defaultLayout || "grid",
            apiEndpoints: config.apiEndpoints || {
                venues: {
                    items: "/api/facilities/venues",
                    categories: "/api/facility-categories/venues",
                },
                rooms: {
                    items: "/api/facilities/rooms",
                    categories: "/api/facility-categories/rooms",
                },
                equipment: {
                    items: "/api/equipment",
                    categories: "/api/equipment-categories",
                },
            },
            onItemAdded: config.onItemAdded || null,
            onItemRemoved: config.onItemRemoved || null,
            onError: config.onError || null,
        };

        // State variables
        this.catalogType = this.config.defaultCatalogType;
        this.currentPage = 1;
        this.allItems = [];
        this.itemCategories = [];
        this.filteredItems = [];
        this.currentLayout = this.config.defaultLayout;
        this.selectedItems = [];
        this.allowedStatusIds = [1, 2];
        this.statusFilter = "All";
        this.formStatuses = {};
        this.availabilityCalendarInstance = null;
        this.currentFacilityId = null;
        this.searchQuery = "";
        this.searchTimeout = null;

        // DOM Elements
        this.elements = {};

        // Calendar module reference
        this.CalendarModule = null;
    }

    /**
     * Initialize the Booking Catalog
     */
    async init(CalendarModuleClass) {
        this.CalendarModule = CalendarModuleClass;

        // Cache DOM elements
        this.cacheDomElements();

        // Set up event listeners
        this.setupEventListeners();

        // Set initial UI states
        this.setInitialUI();

        // Load catalog data
        await this.loadCatalogData();

        // Update schedule summary
        this.updateScheduleSummary();

        // Load form statuses for calendar
        await this.loadFormStatuses();

        console.log("BookingCatalog initialized successfully");
    }

    /**
     * Cache DOM elements
     */
    cacheDomElements() {
        this.elements = {
            loadingIndicator: document.getElementById(
                this.config.loadingIndicatorId,
            ),
            catalogItemsContainer: document.getElementById(
                this.config.containerId,
            ),
            categoryFilterList: document.getElementById(
                this.config.categoryFilterId,
            ),
            pagination: document.getElementById(this.config.paginationId),
            requisitionBadge: document.getElementById(
                this.config.requisitionBadgeId,
            ),
            catalogHeroTitle: document.getElementById(this.config.heroTitleId),
            searchInput: document.getElementById(this.config.searchInputId),
            searchForm: document.getElementById(this.config.searchFormId),
            clearSearchBtn: document.getElementById(
                this.config.clearSearchBtnId,
            ),
            filterDropdown: document.getElementById(
                this.config.filterDropdownId,
            ),
        };
    }

    /**
     * Set initial UI states
     */
    setInitialUI() {
        // Set active catalog type tab
        document.querySelectorAll(".catalog-type-tab").forEach((tab) => {
            if (tab.dataset.type === this.catalogType) {
                tab.classList.add("active");
            }
        });

        // Set initial status filter radio
        const statusRadio = document.querySelector(
            `#${this.config.filterDropdownMenuId} .status-option[data-status="${this.statusFilter}"]`,
        );
        if (statusRadio) {
            statusRadio.checked = true;
        }

        // Set initial layout radio
        const layoutRadio = document.querySelector(
            `#${this.config.filterDropdownMenuId} .layout-option[data-layout="${this.currentLayout}"]`,
        );
        if (layoutRadio) {
            layoutRadio.checked = true;
        }
    }

    /**
     * Set up all event listeners
     */
    setupEventListeners() {
        // Catalog type switcher
        document.querySelectorAll(".catalog-type-tab").forEach((tab) => {
            tab.addEventListener("click", (e) => {
                e.preventDefault();
                this.switchCatalogType(tab.dataset.type);
            });
        });

        // Search form submission
        if (this.elements.searchForm) {
            this.elements.searchForm.addEventListener("submit", (e) => {
                e.preventDefault();
                this.performSearch();
            });
        }

        // Real-time search with debounce
        if (this.elements.searchInput) {
            this.elements.searchInput.addEventListener("input", () => {
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => {
                    this.performSearch();
                }, 300);
            });

            this.elements.searchInput.addEventListener("keydown", (e) => {
                if (e.key === "Escape") {
                    this.clearSearch();
                }
            });
        }

        // Clear search button
        if (this.elements.clearSearchBtn) {
            this.elements.clearSearchBtn.addEventListener("click", () => {
                this.clearSearch();
            });
        }

        // Add/Remove buttons (delegation)
        this.elements.catalogItemsContainer.addEventListener(
            "click",
            async (e) => {
                const button = e.target.closest(".add-remove-btn");
                if (!button || button.disabled) return;
                await this.handleAddRemoveAction(button);
            },
        );

        // Quantity input validation for equipment
        this.elements.catalogItemsContainer.addEventListener("input", (e) => {
            if (e.target.classList.contains("quantity-input")) {
                this.validateQuantityInput(e.target);
            }
        });

        // Check availability buttons (delegation)
        this.elements.catalogItemsContainer.addEventListener(
            "click",
            async (e) => {
                const button = e.target.closest(".check-availability-btn");
                if (!button) return;
                e.preventDefault();
                this.showFacilityAvailability(button);
            },
        );

        // Filter dropdown - Status radio buttons
        document
            .querySelectorAll(
                `#${this.config.filterDropdownMenuId} .status-option`,
            )
            .forEach((radio) => {
                radio.addEventListener("change", () => {
                    if (radio.checked) {
                        this.statusFilter = radio.dataset.status;
                        if (this.elements.filterDropdown) {
                            this.elements.filterDropdown.innerHTML = `<i class="bi bi-sliders2 me-1"></i> <span class="d-none d-sm-inline">Filters</span>`;
                        }
                        this.filterAndRenderItems();
                    }
                });
            });

        // Filter dropdown - Layout radio buttons
        document
            .querySelectorAll(
                `#${this.config.filterDropdownMenuId} .layout-option`,
            )
            .forEach((radio) => {
                radio.addEventListener("change", () => {
                    if (radio.checked) {
                        this.currentLayout = radio.dataset.layout;
                        this.filterAndRenderItems();
                    }
                });
            });

        // Storage events for cross-page sync
        window.addEventListener("storage", (e) => {
            if (e.key === "request_info") {
                this.updateScheduleSummary();
            }
            if (e.key === "formUpdated") {
                this.updateAllUI();
            }
        });
    }

    /**
     * Handle add/remove actions
     */
    async handleAddRemoveAction(button) {
        const id = button.dataset.id;
        const type = button.dataset.type;
        const action = button.dataset.action;
        let quantity = 1;

        if (type === "equipment") {
            const quantityInput = button
                .closest(".equipment-quantity-selector")
                .querySelector(".quantity-input");
            quantity = parseInt(quantityInput.value) || 1;
        }

        try {
            if (action === "add") {
                await this.addToForm(id, type, quantity);
            } else if (action === "remove") {
                await this.removeFromForm(id, type);
            }
            await this.updateAllUI();
        } catch (error) {
            console.error("Error handling form action:", error);
            this.showToast(error.message || "Error processing action", "error");
        }
    }

    /**
     * Validate quantity input
     */
    validateQuantityInput(input) {
        const button = input
            .closest(".equipment-quantity-selector")
            .querySelector(".add-remove-btn");
        const id = button.dataset.id;
        const quantity = parseInt(input.value) || 0;

        const equipmentItem = this.allItems.find(
            (item) =>
                item.equipment_id === parseInt(id) &&
                this.catalogType === "equipment",
        );

        if (equipmentItem) {
            const availableQty = equipmentItem.available_quantity || 0;

            if (quantity > availableQty) {
                input.classList.add("is-invalid");
                let errorMsg =
                    input.parentNode.querySelector(".quantity-error");
                if (!errorMsg) {
                    errorMsg = document.createElement("div");
                    errorMsg.className =
                        "quantity-error text-danger small mt-1";
                    input.parentNode.appendChild(errorMsg);
                }
                errorMsg.textContent = `Max: ${availableQty}`;
            } else {
                input.classList.remove("is-invalid");
                const errorMsg =
                    input.parentNode.querySelector(".quantity-error");
                if (errorMsg) errorMsg.remove();
            }

            if (quantity < 1) {
                input.classList.add("is-invalid");
            }
        }
    }

    /**
     * Switch catalog type (updated)
     */
    switchCatalogType(type) {
        this.clearSearch();
        this.catalogType = type;
        this.currentPage = 1;
        this.paginationMeta = null;

        const titles = {
            venues: "Venues & Event Spaces",
            rooms: "Rooms & Dormitories",
            equipment: "Equipment Catalog",
        };

        if (this.elements.catalogHeroTitle) {
            this.elements.catalogHeroTitle.textContent = titles[type];
        }

        // Update active state on tab buttons
        document.querySelectorAll(".catalog-type-tab").forEach((tab) => {
            if (tab.dataset.type === type) {
                tab.classList.add("active");
            } else {
                tab.classList.remove("active");
            }
        });

        this.loadCatalogData();
    }

    /**
     * Load catalog data from API with pagination support
     */
    async loadCatalogData() {
        try {
            const api = this.config.apiEndpoints[this.catalogType];

            // Build URL with pagination and search parameters
            let itemsUrl = api.items;
            const params = new URLSearchParams();

            params.append("page", this.currentPage);
            params.append("per_page", this.config.itemsPerPage);

            if (this.searchQuery && this.searchQuery.trim() !== "") {
                params.append("search", this.searchQuery);
            }

            // Add status filter if any
            if (this.statusFilter !== "All") {
                const statusMap = { Available: 1, Unavailable: 2 };
                if (statusMap[this.statusFilter]) {
                    params.append("status", statusMap[this.statusFilter]);
                }
            }

            const queryString = params.toString();
            if (queryString) {
                itemsUrl += `?${queryString}`;
            }

            // Show loading indicator
            if (this.elements.loadingIndicator) {
                this.elements.loadingIndicator.style.display = "block";
            }
            if (this.elements.catalogItemsContainer) {
                this.elements.catalogItemsContainer.classList.add("d-none");
            }

            console.log(`Fetching ${this.catalogType} from API: ${itemsUrl}`);

            // Fetch paginated items
            const response = await this.fetchData(itemsUrl);

            // Extract paginated items and pagination meta - FIXED FOR YOUR API STRUCTURE
            if (response && response.success) {
                // Your API returns data directly at root level
                this.allItems = response.data || [];
                this.paginationMeta = {
                    current_page: response.current_page || 1,
                    last_page: response.last_page || 1,
                    per_page: response.per_page || this.config.itemsPerPage,
                    total: response.total || 0,
                    next_page_url: response.next_page_url || null,
                    prev_page_url: response.prev_page_url || null,
                };

                console.log(
                    `Loaded ${this.allItems.length} items, page ${this.paginationMeta.current_page} of ${this.paginationMeta.last_page}, total: ${this.paginationMeta.total}`,
                );
            } else {
                this.allItems = [];
                this.paginationMeta = {
                    current_page: 1,
                    last_page: 1,
                    per_page: this.config.itemsPerPage,
                    total: 0,
                    next_page_url: null,
                    prev_page_url: null,
                };
            }

            // Fetch categories separately (these can be cached)
            if (
                !this.itemCategories.length ||
                this.itemCategories.length === 0
            ) {
                const categoriesData = await this.fetchData(api.categories);
                this.itemCategories = this.extractCategories(categoriesData);

                // Cache categories
                if (!this.searchQuery) {
                    this.saveToCache(this.catalogType, {
                        categories: this.itemCategories,
                    });
                }
            }

            // Render UI
            this.renderCategoryFilters();
            this.renderItems(this.allItems); // Items are already paginated from server
            this.renderPagination();
            this.updateCartBadge();

            // Hide loading indicator
            if (this.elements.catalogItemsContainer) {
                this.elements.catalogItemsContainer.classList.remove("d-none");
            }
            if (this.elements.loadingIndicator) {
                this.elements.loadingIndicator.style.display = "none";
            }

            // Fetch selected items
            await this.fetchSelectedItems();
        } catch (error) {
            console.error(`Error loading ${this.catalogType} data:`, error);
            this.showToast(
                `Failed to load ${this.catalogType}. Please try again.`,
                "error",
            );
            if (this.elements.loadingIndicator) {
                this.elements.loadingIndicator.style.display = "none";
            }
            if (this.config.onError) {
                this.config.onError(error);
            }
        }
    }

    /**
     * Get selected categories for filtering
     */
    getSelectedCategories() {
        const categoryCheckboxes = Array.from(
            document.querySelectorAll(".category-filter"),
        ).filter((cb) => cb.id !== "allCategories" && cb.checked);
        const subcategoryCheckboxes = Array.from(
            document.querySelectorAll(".subcategory-filter"),
        ).filter((cb) => cb.checked);

        return [
            ...categoryCheckboxes.map((cb) => cb.value),
            ...subcategoryCheckboxes.map((cb) => cb.value),
        ];
    }

    /**
     * Apply catalog type specific filtering
     */
    applyCatalogTypeFilter() {
        if (this.catalogType === "venues") {
            this.allItems = this.allItems.filter(
                (item) => !item.parent_facility_id,
            );
        } else if (this.catalogType === "rooms") {
            this.allItems = this.allItems.filter(
                (item) => item.parent_facility_id !== null,
            );
        }
    }

    /**
     * Extract items from API response (updated)
     */
    extractItems(itemsData) {
        // Handle paginated response
        if (itemsData && itemsData.success && itemsData.data) {
            if (Array.isArray(itemsData.data)) {
                return itemsData.data;
            } else if (
                itemsData.data.data &&
                Array.isArray(itemsData.data.data)
            ) {
                return itemsData.data.data;
            }
        }
        return [];
    }

    /**
     * Extract categories from API response
     */
    extractCategories(categoriesData) {
        if (Array.isArray(categoriesData)) {
            return categoriesData;
        } else if (
            categoriesData &&
            categoriesData.data &&
            Array.isArray(categoriesData.data)
        ) {
            return categoriesData.data;
        } else if (
            categoriesData &&
            categoriesData.success &&
            categoriesData.data
        ) {
            return Array.isArray(categoriesData.data)
                ? categoriesData.data
                : [];
        }
        return [];
    }
/**
 * Fetch selected items from requisition form and sync buttons
 */
async fetchSelectedItems() {
    try {
        const response = await this.fetchData("/api/requisition/get-items");
        this.selectedItems = response.data?.selected_items || [];
        this.updateCartBadge();
        
        // === SYNC BUTTONS DIRECTLY HERE ===
        this.selectedItems.forEach(selectedItem => {
            const id = selectedItem.equipment_id || selectedItem.facility_id;
            const type = selectedItem.type;
            
            const button = document.querySelector(`.add-remove-btn[data-id="${id}"][data-type="${type}"]`);
            if (!button) return;
            
            if (type === "equipment") {
                button.textContent = "Remove";
                button.classList.remove("btn-primary");
                button.classList.add("btn-danger");
                button.dataset.action = "remove";
                
                const card = button.closest(".catalog-card");
                const quantityInput = card?.querySelector(".quantity-input");
                if (quantityInput) {
                    quantityInput.value = selectedItem.quantity || 1;
                }
            } else {
                button.textContent = "Remove from form";
                button.classList.remove("btn-primary");
                button.classList.add("btn-danger");
                button.dataset.action = "remove";
            }
        });
    } catch (e) {
        console.warn("Failed to fetch selected items:", e);
        this.selectedItems = [];
    }
}

    /**
     * Save data to localStorage cache
     */
    saveToCache(type, data) {
        // Only cache categories, not paginated items
        if (data.categories) {
            try {
                const cacheKey = `catalog_cache_${type}`;
                const cacheData = {
                    categories: data.categories,
                    timestamp: Date.now(),
                };
                localStorage.setItem(cacheKey, JSON.stringify(cacheData));
                console.log(`Cached ${type} categories`);
            } catch (e) {
                console.warn("Failed to save to cache:", e);
            }
        }
    }

    /**
     * Get data from localStorage cache
     */
    getFromCache(type) {
        try {
            const cacheKey = `catalog_cache_${type}`;
            const cached = localStorage.getItem(cacheKey);
            if (!cached) return null;
            return JSON.parse(cached);
        } catch (e) {
            console.warn("Failed to read from cache:", e);
            return null;
        }
    }

    /**
     * Fetch data with CSRF token handling
     */
    async fetchData(url, options = {}) {
        let csrfToken = document.querySelector(
            'meta[name="csrf-token"]',
        )?.content;

        if (!csrfToken) {
            csrfToken = this.getCookie("XSRF-TOKEN");
        }

        if (!csrfToken) {
            console.error("CSRF token not available");
            await this.refreshCsrfToken();
            csrfToken = document.querySelector(
                'meta[name="csrf-token"]',
            )?.content;
        }

        try {
            const headers = {
                "X-CSRF-TOKEN": csrfToken,
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
                ...(options.headers || {}),
            };

            const response = await fetch(url, {
                ...options,
                headers: headers,
                credentials: "same-origin",
                mode: "same-origin",
            });

            if (response.status === 419) {
                console.error("CSRF token mismatch - attempting to refresh");
                await this.refreshCsrfToken();
                return this.fetchData(url, options);
            }

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(
                    errorData.message ||
                        `HTTP error! status: ${response.status}`,
                );
            }

            return await response.json();
        } catch (error) {
            console.error("Fetch error:", { url, error: error.message });
            throw error;
        }
    }

    /**
     * Get cookie value
     */
    getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(";").shift();
    }

    /**
     * Refresh CSRF token
     */
    async refreshCsrfToken() {
        try {
            const response = await fetch("/csrf-token", {
                method: "GET",
                headers: {
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
                credentials: "same-origin",
            });

            if (response.ok) {
                const data = await response.json();
                const metaTag = document.querySelector(
                    'meta[name="csrf-token"]',
                );
                if (metaTag) {
                    metaTag.content = data.csrf_token;
                }
                document.cookie = `XSRF-TOKEN=${data.csrf_token}; path=/`;
                return data.csrf_token;
            }
        } catch (error) {
            console.error("Failed to refresh CSRF token:", error);
        }
        return null;
    }

    /**
     * Show toast notification
     */
    showToast(message, type = "success", duration = 3000) {
        const toast = document.createElement("div");
        toast.className = `toast align-items-center border-0 position-fixed end-0 mb-2`;
        toast.style.zIndex = "1100";
        toast.style.bottom = "0";
        toast.style.right = "0";
        toast.style.margin = "1rem";
        toast.style.opacity = "0";
        toast.style.transform = "translateY(20px)";
        toast.style.transition = "transform 0.4s ease, opacity 0.4s ease";
        toast.setAttribute("role", "alert");
        toast.setAttribute("aria-live", "assertive");
        toast.setAttribute("aria-atomic", "true");

        const bgColor = type === "success" ? "#004183ff" : "#dc3545";
        toast.style.backgroundColor = bgColor;
        toast.style.color = "#fff";
        toast.style.minWidth = "250px";
        toast.style.borderRadius = "0.3rem";

        toast.innerHTML = `
            <div class="d-flex align-items-center px-3 py-1"> 
                <i class="bi ${type === "success" ? "bi-check-circle-fill" : "bi-exclamation-circle-fill"} me-2"></i>
                <div class="toast-body flex-grow-1" style="padding: 0.25rem 0;">${message}</div>
                <button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="loading-bar" style="height: 3px; background: rgba(255,255,255,0.7); width: 100%; transition: width ${duration}ms linear;"></div>
        `;

        document.body.appendChild(toast);

        const bsToast = new bootstrap.Toast(toast, { autohide: false });
        bsToast.show();

        requestAnimationFrame(() => {
            toast.style.opacity = "1";
            toast.style.transform = "translateY(0)";
        });

        const loadingBar = toast.querySelector(".loading-bar");
        requestAnimationFrame(() => {
            loadingBar.style.width = "0%";
        });

        setTimeout(() => {
            toast.style.opacity = "0";
            toast.style.transform = "translateY(20px)";
            setTimeout(() => {
                bsToast.hide();
                toast.remove();
            }, 400);
        }, duration);
    }

    /**
     * Update cart badge
     */
    updateCartBadge() {
        if (!this.elements.requisitionBadge) return;
        if (this.selectedItems.length > 0) {
            this.elements.requisitionBadge.textContent =
                this.selectedItems.length;
            this.elements.requisitionBadge.style.display = "";
            this.elements.requisitionBadge.classList.remove("d-none");
        } else {
            this.elements.requisitionBadge.style.display = "none";
            this.elements.requisitionBadge.classList.add("d-none");
        }
    }

    /**
     * Get primary image URL
     */
    getPrimaryImage(item) {
        return (
            item.images?.find((img) => img.image_type === "Primary")
                ?.image_url ||
            "https://res.cloudinary.com/dn98ntlkd/image/upload/v1759850278/t4fyv56wog6pglhwvwtn.png"
        );
    }

    /**
     * Truncate text
     */
    truncateText(text, maxLength) {
        if (!text) return "";
        return text.length > maxLength
            ? text.substring(0, maxLength) + "..."
            : text;
    }

    /**
     * Get request info from localStorage
     */
    getRequestInfo() {
        return JSON.parse(localStorage.getItem("request_info") || "{}");
    }

    /**
     * Update schedule summary
     */
    updateScheduleSummary() {
        const requestInfo = this.getRequestInfo();
        const scheduleEl = document.getElementById("schedule-summary");
        if (scheduleEl) {
            scheduleEl.textContent = this.formatScheduleDisplay(requestInfo);
        }
    }

    /**
     * Format schedule display
     */
    formatScheduleDisplay(requestInfo) {
        if (!requestInfo.start_date) return "No schedule set";

        const startDate = new Date(requestInfo.start_date).toLocaleDateString(
            "en-US",
            {
                month: "short",
                day: "numeric",
                year: "numeric",
            },
        );

        if (requestInfo.all_day) {
            if (requestInfo.start_date === requestInfo.end_date) {
                return `${startDate} (All Day)`;
            } else {
                const endDate = new Date(
                    requestInfo.end_date,
                ).toLocaleDateString("en-US", {
                    month: "short",
                    day: "numeric",
                    year: "numeric",
                });
                return `${startDate} - ${endDate} (All Day)`;
            }
        } else {
            const endDate = new Date(requestInfo.end_date).toLocaleDateString(
                "en-US",
                {
                    month: "short",
                    day: "numeric",
                    year: "numeric",
                },
            );
            return `${startDate} ${requestInfo.start_time} - ${endDate} ${requestInfo.end_time}`;
        }
    }
/**
 * Render category filters (updated for rooms with parent buildings)
 */
renderCategoryFilters() {
    if (!this.elements.categoryFilterList) return;

    this.elements.categoryFilterList.innerHTML = "";

    const allCategoriesItem = document.createElement("div");
    allCategoriesItem.className = "category-item";
    allCategoriesItem.innerHTML = `
        <div class="form-check">
            <input class="form-check-input category-filter" type="checkbox" id="allCategories" value="All" checked disabled>
            <label class="form-check-label" for="allCategories">All Categories</label>
        </div>
    `;
    this.elements.categoryFilterList.appendChild(allCategoriesItem);

    if (this.catalogType === "equipment") {
        this.renderEquipmentCategories();
    } else if (this.catalogType === "rooms") {
        this.renderRoomCategoriesWithParentBuildings();
    } else {
        this.renderFacilityCategories();
    }

    this.setupCategoryFilterEvents();
}

/**
 * Render room categories with parent building hierarchy
 */
async renderRoomCategoriesWithParentBuildings() {
    try {
        // First, load parent buildings
        const response = await this.fetchData("/api/facilities/parent-buildings-for-rooms");
        const parentBuildings = response.data || [];

        // Create a container for parent buildings section
        const parentBuildingsSection = document.createElement("div");
        parentBuildingsSection.className = "parent-buildings-section mb-3";
        parentBuildingsSection.innerHTML = `
            <div class="fw-semibold mb-2 small text-muted">Filter by Building:</div>
            <div id="parentBuildingsList"></div>
        `;
        this.elements.categoryFilterList.appendChild(parentBuildingsSection);

        const parentBuildingsList = parentBuildingsSection.querySelector("#parentBuildingsList");

        if (parentBuildings.length === 0) {
            parentBuildingsList.innerHTML = '<div class="text-muted small">No buildings available</div>';
        } else {
            parentBuildings.forEach(building => {
                const buildingItem = document.createElement("div");
                buildingItem.className = "form-check mb-1";
                buildingItem.innerHTML = `
                    <input class="form-check-input parent-building-filter" type="checkbox" 
                           id="building${building.facility_id}" value="${building.facility_id}">
                    <label class="form-check-label small" for="building${building.facility_id}">
                        ${building.facility_name}
                        ${building.facility_code ? `<span class="text-muted">(${building.facility_code})</span>` : ''}
                    </label>
                `;
                parentBuildingsList.appendChild(buildingItem);
            });
        }

        // Add divider
        const divider = document.createElement("hr");
        divider.className = "my-2";
        this.elements.categoryFilterList.appendChild(divider);

        // Add categories section header
        const categoriesHeader = document.createElement("div");
        categoriesHeader.className = "fw-semibold mb-2 small text-muted";
        categoriesHeader.textContent = "Filter by Room Type:";
        this.elements.categoryFilterList.appendChild(categoriesHeader);

        // Now render categories with IDs 2 and 3 only
        const roomCategories = this.itemCategories.filter(category => 
            category.category_id === 2 || category.category_id === 3
        );

        roomCategories.forEach((category) => {
            const categoryItem = document.createElement("div");
            categoryItem.className = "category-item mb-2";
            categoryItem.innerHTML = `
                <div class="form-check d-flex justify-content-between align-items-center">
                    <div>
                        <input class="form-check-input category-filter" type="checkbox" 
                               id="category${category.category_id}" value="${category.category_id}">
                        <label class="form-check-label" for="category${category.category_id}">
                            ${category.category_name}
                        </label>
                    </div>
                    ${category.subcategories && category.subcategories.length > 0 ? 
                        '<i class="bi bi-chevron-up toggle-arrow" style="cursor:pointer"></i>' : ""}
                </div>
                ${category.subcategories && category.subcategories.length > 0 ? `
                    <div class="subcategory-list ms-3 mt-1" style="overflow: hidden; max-height: ${category.subcategories.length * 35}px;">
                        ${category.subcategories
                            .map(sub => `
                                <div class="form-check mb-1">
                                    <input class="form-check-input subcategory-filter" type="checkbox" 
                                           id="subcategory${sub.subcategory_id}" value="${sub.subcategory_id}" 
                                           data-category="${category.category_id}">
                                    <label class="form-check-label small" for="subcategory${sub.subcategory_id}">
                                        ${sub.subcategory_name}
                                    </label>
                                </div>
                            `)
                            .join("")}
                    </div>
                ` : ""}
            `;
            this.elements.categoryFilterList.appendChild(categoryItem);

            const toggleArrow = categoryItem.querySelector(".toggle-arrow");
            if (toggleArrow) {
                const subcategoryList = categoryItem.querySelector(".subcategory-list");
                toggleArrow.addEventListener("click", () => {
                    const isExpanded = subcategoryList.style.maxHeight !== "0px";
                    if (isExpanded) {
                        subcategoryList.style.maxHeight = "0";
                        toggleArrow.classList.replace("bi-chevron-up", "bi-chevron-down");
                    } else {
                        subcategoryList.style.maxHeight = `${subcategoryList.scrollHeight}px`;
                        toggleArrow.classList.replace("bi-chevron-down", "bi-chevron-up");
                    }
                });
            }
        });

        // Store parent building filters reference
        this.parentBuildingFilters = parentBuildingsList;
        
        // Setup parent building filter events
        this.setupParentBuildingFilterEvents();

    } catch (error) {
        console.error("Error rendering room categories:", error);
        // Fallback to regular category rendering
        this.renderFacilityCategories();
    }
}

/**
 * Setup parent building filter events for rooms
 */
setupParentBuildingFilterEvents() {
    const parentBuildingCheckboxes = document.querySelectorAll(".parent-building-filter");
    const allCategoriesCheckbox = document.getElementById("allCategories");
    
    parentBuildingCheckboxes.forEach((checkbox) => {
        checkbox.addEventListener("change", () => {
            // When building filter is applied, uncheck "All Categories"
            if (checkbox.checked && allCategoriesCheckbox) {
                allCategoriesCheckbox.checked = false;
                allCategoriesCheckbox.disabled = false;
            }
            this.filterAndRenderItems();
        });
    });
}

/**
 * Get selected parent buildings for room filtering
 */
getSelectedParentBuildings() {
    const parentBuildingCheckboxes = Array.from(
        document.querySelectorAll(".parent-building-filter")
    ).filter(cb => cb.checked);
    
    return parentBuildingCheckboxes.map(cb => cb.value);
}

    /**
     * Render facility categories
     */
    renderFacilityCategories() {
        this.itemCategories.forEach((category) => {
            const categoryItem = document.createElement("div");
            categoryItem.className = "category-item";
            categoryItem.innerHTML = `
                <div class="form-check d-flex justify-content-between align-items-center">
                    <div>
                        <input class="form-check-input category-filter" type="checkbox" id="category${category.category_id}" value="${category.category_id}">
                        <label class="form-check-label" for="category${category.category_id}">${category.category_name}</label>
                    </div>
                    ${category.subcategories && category.subcategories.length > 0 ? '<i class="bi bi-chevron-up toggle-arrow" style="cursor:pointer"></i>' : ""}
                </div>
                ${
                    category.subcategories && category.subcategories.length > 0
                        ? `
                    <div class="subcategory-list ms-3" style="overflow: hidden; max-height: ${category.subcategories.length * 35}px;">
                        ${category.subcategories
                            .map(
                                (sub) => `
                            <div class="form-check">
                                <input class="form-check-input subcategory-filter" type="checkbox" id="subcategory${sub.subcategory_id}" value="${sub.subcategory_id}" data-category="${category.category_id}">
                                <label class="form-check-label" for="subcategory${sub.subcategory_id}">${sub.subcategory_name}</label>
                            </div>
                        `,
                            )
                            .join("")}
                    </div>
                `
                        : ""
                }
            `;
            this.elements.categoryFilterList.appendChild(categoryItem);

            const toggleArrow = categoryItem.querySelector(".toggle-arrow");
            if (toggleArrow) {
                const subcategoryList =
                    categoryItem.querySelector(".subcategory-list");
                toggleArrow.addEventListener("click", () => {
                    const isExpanded =
                        subcategoryList.style.maxHeight !== "0px";
                    if (isExpanded) {
                        subcategoryList.style.maxHeight = "0";
                        toggleArrow.classList.replace(
                            "bi-chevron-up",
                            "bi-chevron-down",
                        );
                    } else {
                        subcategoryList.style.maxHeight = `${subcategoryList.scrollHeight}px`;
                        toggleArrow.classList.replace(
                            "bi-chevron-down",
                            "bi-chevron-up",
                        );
                    }
                });
            }
        });
    }

    /**
     * Render equipment categories
     */
    renderEquipmentCategories() {
        this.itemCategories.forEach((category) => {
            const categoryItem = document.createElement("div");
            categoryItem.className = "category-item";
            categoryItem.innerHTML = `
                <div class="form-check">
                    <input class="form-check-input category-filter" type="checkbox" id="category${category.category_id}" value="${category.category_id}">
                    <label class="form-check-label" for="category${category.category_id}">${category.category_name}</label>
                </div>
            `;
            this.elements.categoryFilterList.appendChild(categoryItem);
        });
    }

    /**
     * Setup category filter events
     */
    setupCategoryFilterEvents() {
        const allCategoriesCheckbox = document.getElementById("allCategories");
        const categoryCheckboxes = Array.from(
            document.querySelectorAll(".category-filter"),
        ).filter((cb) => cb.id !== "allCategories");
        const subcategoryCheckboxes = Array.from(
            document.querySelectorAll(".subcategory-filter"),
        );

        const updateAllCategoriesCheckbox = () => {
            const anyChecked =
                categoryCheckboxes.some((c) => c.checked) ||
                subcategoryCheckboxes.some((s) => s.checked);
            if (anyChecked) {
                allCategoriesCheckbox.checked = false;
                allCategoriesCheckbox.disabled = false;
            } else {
                allCategoriesCheckbox.checked = true;
                allCategoriesCheckbox.disabled = true;
            }
        };

        categoryCheckboxes.forEach((cb) => {
            cb.addEventListener("change", () => {
                if (this.catalogType !== "equipment") {
                    const catId = cb.value;
                    const relatedSubs = subcategoryCheckboxes.filter(
                        (sub) => sub.dataset.category === catId,
                    );

                    if (!cb.checked) {
                        relatedSubs.forEach((sub) => {
                            sub.checked = false;
                        });
                    } else {
                        relatedSubs.forEach((sub) => {
                            sub.checked = true;
                        });
                    }
                }
                updateAllCategoriesCheckbox();
                this.filterAndRenderItems();
            });
        });

        subcategoryCheckboxes.forEach((sub) => {
            sub.addEventListener("change", () => {
                updateAllCategoriesCheckbox();
                this.filterAndRenderItems();
            });
        });

        allCategoriesCheckbox.addEventListener("change", () => {
            if (allCategoriesCheckbox.checked) {
                categoryCheckboxes.forEach((cb) => {
                    cb.checked = false;
                });
                subcategoryCheckboxes.forEach((sub) => {
                    sub.checked = false;
                });
                allCategoriesCheckbox.disabled = true;
                this.filterAndRenderItems();
            }
        });
    }

/**
 * Filter items (updated to handle parent building filters for rooms)
 */
filterItems() {
    const allCategoriesCheckbox = document.getElementById("allCategories");
    const categoryCheckboxes = Array.from(
        document.querySelectorAll(".category-filter"),
    ).filter((cb) => cb.id !== "allCategories");
    const subcategoryCheckboxes = Array.from(
        document.querySelectorAll(".subcategory-filter"),
    );

    this.filteredItems = [...this.allItems];

    // Apply search filter
    if (this.searchQuery && this.searchQuery.trim() !== "") {
        const query = this.searchQuery.toLowerCase().trim();
        this.filteredItems = this.filteredItems.filter((item) => {
            if (this.catalogType === "equipment") {
                if (item.items && Array.isArray(item.items)) {
                    return item.items.some(
                        (equipmentItem) =>
                            equipmentItem.item_name &&
                            equipmentItem.item_name
                                .toLowerCase()
                                .includes(query),
                    );
                }
                return false;
            } else {
                const nameMatch =
                    item.facility_name &&
                    item.facility_name.toLowerCase().includes(query);
                const descMatch =
                    item.description &&
                    item.description.toLowerCase().includes(query);
                return nameMatch || descMatch;
            }
        });
    }

    // Apply status filter
    if (this.statusFilter === "Available") {
        this.filteredItems = this.filteredItems.filter(
            (item) => item.status.status_id === 1,
        );
    } else if (this.statusFilter === "Unavailable") {
        this.filteredItems = this.filteredItems.filter(
            (item) => item.status.status_id === 2,
        );
    }

    // Apply parent building filter for rooms
    if (this.catalogType === "rooms") {
        const selectedBuildings = this.getSelectedParentBuildings();
        if (selectedBuildings.length > 0) {
            this.filteredItems = this.filteredItems.filter((item) =>
                selectedBuildings.includes(item.parent_facility_id?.toString())
            );
        }
    }

    // Apply category filter
    if (!allCategoriesCheckbox.checked) {
        if (this.catalogType === "equipment") {
            this.filterEquipmentByCategory(categoryCheckboxes);
        } else {
            this.filterFacilitiesByCategory(
                categoryCheckboxes,
                subcategoryCheckboxes,
            );
        }
    }

    // Additional filter for data integrity
    if (this.catalogType === "venues") {
        this.filteredItems = this.filteredItems.filter(
            (item) => !item.parent_facility_id,
        );
    } else if (this.catalogType === "rooms") {
        this.filteredItems = this.filteredItems.filter(
            (item) => item.parent_facility_id !== null,
        );
    }
}

    /**
     * Filter facilities by category
     */
    filterFacilitiesByCategory(categoryCheckboxes, subcategoryCheckboxes) {
        const selectedCategories = categoryCheckboxes
            .filter((cb) => cb.checked)
            .map((cb) => cb.value);
        const selectedSubcategories = subcategoryCheckboxes
            .filter((cb) => cb.checked)
            .map((cb) => cb.value);

        if (
            selectedCategories.length === 0 &&
            selectedSubcategories.length === 0
        ) {
            this.filteredItems = [];
            return;
        }

        this.filteredItems = this.filteredItems.filter((facility) => {
            const matchesSubcategory =
                selectedSubcategories.length > 0 &&
                facility.subcategory &&
                selectedSubcategories.includes(
                    facility.subcategory.subcategory_id.toString(),
                );

            const matchesCategory =
                selectedCategories.length > 0 &&
                selectedCategories.includes(
                    facility.category.category_id.toString(),
                );

            if (
                selectedSubcategories.length > 0 &&
                selectedCategories.length > 0
            ) {
                return matchesSubcategory || matchesCategory;
            } else if (selectedSubcategories.length > 0) {
                return matchesSubcategory;
            } else {
                return matchesCategory;
            }
        });
    }

    /**
     * Filter equipment by category
     */
    filterEquipmentByCategory(categoryCheckboxes) {
        const selectedCategories = categoryCheckboxes
            .filter((cb) => cb.checked)
            .map((cb) => cb.value);

        if (selectedCategories.length === 0) {
            this.filteredItems = [];
            return;
        }

        this.filteredItems = this.filteredItems.filter((equipment) =>
            selectedCategories.includes(
                equipment.category.category_id.toString(),
            ),
        );
    }

    /**
     * Filter and render items (updated for server-side pagination)
     */
    filterAndRenderItems() {
        // Reset to first page when filters change
        if (this.currentPage !== 1) {
            this.currentPage = 1;
        }
        // Reload data with current filters from server
        this.loadCatalogData();
    }
    /**
     * Render items (no need for client-side pagination since server handles it)
     */
    renderItems(items) {
        this.elements.catalogItemsContainer.innerHTML = "";

        if (!items || items.length === 0) {
            this.elements.catalogItemsContainer.classList.remove(
                "grid-layout",
                "list-layout",
            );
            let icon = "bi-building";
            if (this.catalogType === "rooms") icon = "bi-door-open";
            else if (this.catalogType === "equipment") icon = "bi-box-seam";

            this.elements.catalogItemsContainer.innerHTML = `
            <div style="grid-column: 1 / -1; display: flex; flex-direction: column; justify-content: center; align-items: center; min-height: 220px; width: 100%;">
                <i class="bi ${icon} fs-1 text-muted"></i>
                <h4 class="mt-2">No ${this.catalogType} found</h4>
                <p class="text-muted">Try adjusting your filters or search criteria</p>
            </div>
        `;
            return;
        }

        this.elements.catalogItemsContainer.classList.remove(
            "grid-layout",
            "list-layout",
        );
        this.elements.catalogItemsContainer.classList.add(
            `${this.currentLayout}-layout`,
        );

        if (this.currentLayout === "grid") {
            this.renderGridLayout(items);
        } else {
            this.renderListLayout(items);
        }

        // Add click handlers for item titles
        document
            .querySelectorAll(".catalog-card-details h5")
            .forEach((title) => {
                title.addEventListener("click", () => {
                    const id = title.getAttribute("data-id");
                    this.showItemDetails(id);
                });
            });
    }
    /**
     * Render grid layout
     */
    renderGridLayout(items) {
        this.elements.catalogItemsContainer.innerHTML = items
            .map((item) => {
                const isEquipment = this.catalogType === "equipment";
                const itemName = isEquipment
                    ? item.equipment_name
                    : item.facility_name;
                const itemId = isEquipment
                    ? item.equipment_id
                    : item.facility_id;
                const primaryImage = this.getPrimaryImage(item);
                const truncatedName = this.truncateText(itemName, 18);
                const description = this.truncateText(
                    item.description || "No description available",
                    100,
                );

                let buildingInfo = "";
                if (this.catalogType === "rooms" && item.parent_facility) {
                    buildingInfo = `<span><i class="bi bi-building"></i> ${item.parent_facility.facility_name}</span>`;
                }

                return `
                <div class="catalog-card">
                    <span class="item-type-badge badge ${isEquipment ? "bg-info" : "bg-warning"}">
                        ${isEquipment ? "Equipment" : this.catalogType === "rooms" ? "Room" : "Venue"}
                    </span>
                    <img src="${primaryImage}" alt="${itemName}" class="catalog-card-img" onerror="this.src='https://res.cloudinary.com/dn98ntlkd/image/upload/v1759850278/t4fyv56wog6pglhwvwtn.png'">
                    <div class="catalog-card-details">
                        <h5 data-id="${itemId}" title="${itemName}">${truncatedName}</h5>
                        <span class="status-banner" style="background-color: ${item.status.color_code}">
                            ${item.status.status_name}
                        </span>
                        <div class="catalog-card-meta">
                            ${
                                isEquipment
                                    ? `<span><i class="bi bi-tags-fill"></i> ${item.category.category_name}</span>
                                   <span><i class="bi bi-box-seam"></i> ${item.available_quantity}/${item.total_quantity} available</span>`
                                    : `<span><i class="bi bi-people-fill"></i> ${item.capacity || "N/A"}</span>
                                   <span><i class="bi bi-tags-fill"></i> ${item.subcategory?.subcategory_name || item.category.category_name}</span>
                                   ${buildingInfo}`
                            }
                        </div>
                        <p class="facility-description" title="${item.description || ""}">${description}</p>
                        <div class="catalog-card-fee">
                            <i class="bi bi-cash-stack"></i> ₱${parseFloat(item.base_fee).toLocaleString()} (${item.rate_type})
                        </div>
                    </div>
                    <div class="catalog-card-actions">
                        ${isEquipment ? this.getEquipmentActionsHtml(item) : this.getFacilityActionsHtml(item)}
                        ${this.getCheckAvailabilityButtonHtml(item)}
                    </div>
                </div>
            `;
            })
            .join("");
    }

    /**
     * Render list layout
     */
    renderListLayout(items) {
        this.elements.catalogItemsContainer.innerHTML = items
            .map((item) => {
                const isEquipment = this.catalogType === "equipment";
                const itemName = isEquipment
                    ? item.equipment_name
                    : item.facility_name;
                const itemId = isEquipment
                    ? item.equipment_id
                    : item.facility_id;
                const primaryImage = this.getPrimaryImage(item);
                const truncatedName = this.truncateText(itemName, 30);
                const description = this.truncateText(
                    item.description || "No description available",
                    150,
                );

                let buildingInfo = "";
                if (this.catalogType === "rooms" && item.parent_facility) {
                    buildingInfo = `<span><i class="bi bi-building"></i> ${item.parent_facility.facility_name}</span>`;
                }

                return `
                <div class="catalog-card">
                    <img src="${primaryImage}" alt="${itemName}" class="catalog-card-img" onerror="this.src='https://res.cloudinary.com/dn98ntlkd/image/upload/v1759850278/t4fyv56wog6pglhwvwtn.png'">
                    <div class="catalog-card-details">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 data-id="${itemId}" title="${itemName}">${truncatedName}</h5>
                            <span class="status-banner" style="background-color: ${item.status.color_code}">
                                ${item.status.status_name}
                            </span>
                        </div>
                        <div class="catalog-card-meta">
                            ${
                                isEquipment
                                    ? `<span><i class="bi bi-tags-fill"></i> ${item.category.category_name}</span>
                                   <span><i class="bi bi-box-seam"></i> ${item.available_quantity}/${item.total_quantity} available</span>`
                                    : `<span><i class="bi bi-people-fill"></i> ${item.capacity || "N/A"}</span>
                                   <span><i class="bi bi-tags-fill"></i> ${item.subcategory?.subcategory_name || item.category.category_name}</span>
                                   ${buildingInfo}`
                            }
                        </div>
                        <p class="facility-description" title="${item.description || ""}">${description}</p>
                    </div>
                    <div class="catalog-card-actions">
                        <div class="catalog-card-fee mb-2 text-center">
                            <i class="bi bi-cash-stack"></i> ₱${parseFloat(item.base_fee).toLocaleString()} (${item.rate_type})
                        </div>
                        ${isEquipment ? this.getEquipmentActionsHtml(item) : this.getFacilityActionsHtml(item)}
                        ${this.getCheckAvailabilityButtonHtml(item)}
                    </div>
                </div>
            `;
            })
            .join("");
    }

    /**
     * Get equipment actions HTML
     */
    getEquipmentActionsHtml(item) {
        const isSelected = this.selectedItems.some(
            (selectedItem) =>
                selectedItem.type === "equipment" &&
                parseInt(selectedItem.equipment_id) === item.equipment_id,
        );

        const selectedItem = isSelected
            ? this.selectedItems.find(
                  (selectedItem) =>
                      selectedItem.type === "equipment" &&
                      parseInt(selectedItem.equipment_id) === item.equipment_id,
              )
            : null;

        const currentQty = selectedItem ? selectedItem.quantity : 1;
        const maxQty = item.available_quantity || 0;
        const isUnavailable = item.status.status_id !== 1 || maxQty === 0;

        if (isUnavailable) {
            return `
                <div class="equipment-actions">
                    <div class="equipment-quantity-selector">
                        <input type="number" class="form-control quantity-input" value="${currentQty}" min="1" max="${maxQty}" disabled>
                        <button class="btn btn-secondary add-remove-btn form-action-btn" disabled>Unavailable</button>
                    </div>
                </div>
            `;
        }

        if (isSelected) {
            return `
                <div class="equipment-actions">
                    <div class="equipment-quantity-selector">
                        <input type="number" class="form-control quantity-input" value="${currentQty}" min="1" max="${maxQty}">
                        <button class="btn btn-danger add-remove-btn form-action-btn" data-id="${item.equipment_id}" data-type="equipment" data-action="remove">Remove</button>
                    </div>
                </div>
            `;
        } else {
            return `
                <div class="equipment-actions">
                    <div class="equipment-quantity-selector">
                        <input type="number" class="form-control quantity-input" value="1" min="1" max="${maxQty}">
                        <button class="btn btn-primary add-remove-btn form-action-btn" data-id="${item.equipment_id}" data-type="equipment" data-action="add">Add</button>
                    </div>
                </div>
            `;
        }
    }

    /**
     * Get facility actions HTML
     */
    getFacilityActionsHtml(item) {
        const isUnavailable = item.status.status_id === 2;
        const isSelected = this.selectedItems.some(
            (selectedItem) =>
                selectedItem.type === "facility" &&
                parseInt(selectedItem.facility_id) === item.facility_id,
        );

        if (isUnavailable) {
            return `<button class="btn btn-secondary add-remove-btn form-action-btn" disabled>Unavailable</button>`;
        }

        if (isSelected) {
            return `<button class="btn btn-danger add-remove-btn form-action-btn" data-id="${item.facility_id}" data-type="facility" data-action="remove">Remove from form</button>`;
        } else {
            return `<button class="btn btn-primary add-remove-btn form-action-btn" data-id="${item.facility_id}" data-type="facility" data-action="add">Add to form</button>`;
        }
    }

    /**
     * Get check availability button HTML
     */
    getCheckAvailabilityButtonHtml(item) {
        const isEquipment = this.catalogType === "equipment";
        const itemId = isEquipment ? item.equipment_id : item.facility_id;
        const itemName = isEquipment ? item.equipment_name : item.facility_name;
        const primaryImage = this.getPrimaryImage(item);
        const availableQty = isEquipment ? item.available_quantity : null;

        let additionalData = "";
        if (isEquipment) {
            additionalData = `data-item-available-qty="${availableQty}" data-item-total-qty="${item.total_quantity}"`;
        } else {
            additionalData = `data-item-capacity="${item.capacity || "N/A"}" data-item-fee="${parseFloat(item.base_fee).toLocaleString()}"`;
            if (this.catalogType === "rooms" && item.parent_facility) {
                additionalData += ` data-item-building="${item.parent_facility.facility_name}"`;
            }
        }

        return `
            <button class="btn btn-light btn-custom check-availability-btn form-action-btn" 
                    data-item-id="${itemId}"
                    data-item-name="${itemName}"
                    data-item-type="${isEquipment ? "equipment" : "facility"}"
                    data-item-image="${primaryImage}"
                    data-item-category="${item.category.category_name}"
                    data-item-status="${item.status.status_name}"
                    data-item-status-color="${item.status.color_code}"
                    ${additionalData}>
                Check Availability
            </button>
        `;
    }

    /**
     * Render pagination with server-side support
     */
    renderPagination() {
        if (!this.elements.pagination) return;

        const totalPages = this.paginationMeta.last_page || 1;
        const currentPage = this.paginationMeta.current_page || 1;

        this.elements.pagination.innerHTML = "";

        if (totalPages <= 1) return;

        // Previous button
        if (currentPage > 1) {
            const prevItem = document.createElement("li");
            prevItem.className = "page-item";
            prevItem.innerHTML = `<a class="page-link" href="#" aria-label="Previous"><span aria-hidden="true">&laquo;</span></a>`;
            prevItem.addEventListener("click", (e) => {
                e.preventDefault();
                this.goToPage(currentPage - 1);
            });
            this.elements.pagination.appendChild(prevItem);
        }

        // Page numbers
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, currentPage + 2);

        for (let i = startPage; i <= endPage; i++) {
            const pageItem = document.createElement("li");
            pageItem.className = `page-item ${i === currentPage ? "active" : ""}`;
            pageItem.innerHTML = `<a class="page-link" href="#">${i}</a>`;
            pageItem.addEventListener("click", (e) => {
                e.preventDefault();
                this.goToPage(i);
            });
            this.elements.pagination.appendChild(pageItem);
        }

        // Next button
        if (currentPage < totalPages) {
            const nextItem = document.createElement("li");
            nextItem.className = "page-item";
            nextItem.innerHTML = `<a class="page-link" href="#" aria-label="Next"><span aria-hidden="true">&raquo;</span></a>`;
            nextItem.addEventListener("click", (e) => {
                e.preventDefault();
                this.goToPage(currentPage + 1);
            });
            this.elements.pagination.appendChild(nextItem);
        }
    }

    /**
     * Go to specific page
     */
    async goToPage(page) {
        this.currentPage = page;
        await this.loadCatalogData();

        // Scroll to catalog container
        if (this.elements.catalogItemsContainer) {
            window.scrollTo({
                top: this.elements.catalogItemsContainer.offsetTop - 100,
                behavior: "smooth",
            });
        }
    }

async addToForm(id, type, quantity = 1) {
    try {
        const requestBody = {
            type: type,
            equipment_id: type === "equipment" ? parseInt(id) : undefined,
            facility_id: type === "facility" ? parseInt(id) : undefined,
            quantity: parseInt(quantity),
        };

        const response = await this.fetchData("/api/requisition/add-item", {
            method: "POST",
            body: JSON.stringify(requestBody),
        });

        if (!response || !response.success) {
            throw new Error(response?.message || "Failed to add item");
        }

        this.selectedItems = response.data?.selected_items || [];
        this.showToast(`${type} added to form`, "success");
        
        // === CHANGE THE BUTTON THAT WAS CLICKED ===
        const button = document.querySelector(`.add-remove-btn[data-id="${id}"][data-type="${type}"]`);
        if (button) {
            if (type === "equipment") {
                button.textContent = "Remove";
                button.classList.remove("btn-primary");
                button.classList.add("btn-danger");
                button.dataset.action = "remove";
            } else {
                button.textContent = "Remove from form";
                button.classList.remove("btn-primary");
                button.classList.add("btn-danger");
                button.dataset.action = "remove";
            }
        }
        
        this.updateCartBadge();
        localStorage.setItem("formUpdated", Date.now().toString());

        if (this.config.onItemAdded) {
            this.config.onItemAdded(id, type, quantity);
        }
    } catch (error) {
        console.error("Add to form error:", error);
        this.showToast(error.message || "Error adding item to form", "error");
    }
}

async removeFromForm(id, type) {
    try {
        const requestBody = {
            type: type,
            equipment_id: type === "equipment" ? parseInt(id) : undefined,
            facility_id: type === "facility" ? parseInt(id) : undefined,
        };

        const response = await this.fetchData("/api/requisition/remove-item", {
            method: "POST",
            body: JSON.stringify(requestBody),
        });

        if (!response.success) {
            throw new Error(response.message || "Failed to remove item");
        }

        this.selectedItems = response.data.selected_items || [];
        this.showToast(`${type} removed from form`);
        
        // === CHANGE THE BUTTON THAT WAS CLICKED ===
        const button = document.querySelector(`.add-remove-btn[data-id="${id}"][data-type="${type}"]`);
        if (button) {
            if (type === "equipment") {
                button.textContent = "Add";
                button.classList.remove("btn-danger");
                button.classList.add("btn-primary");
                button.dataset.action = "add";
            } else {
                button.textContent = "Add to form";
                button.classList.remove("btn-danger");
                button.classList.add("btn-primary");
                button.dataset.action = "add";
            }
        }
        
        this.updateCartBadge();
        localStorage.setItem("formUpdated", Date.now().toString());

        if (this.config.onItemRemoved) {
            this.config.onItemRemoved(id, type);
        }
    } catch (error) {
        console.error("Error removing item:", error);
        this.showToast(error.message || "Error removing item from form", "error");
    }
}

    /**
     * Update all UI
     */
    async updateAllUI() {
        try {
            const response = await this.fetchData("/api/requisition/get-items");
            this.selectedItems = response.data?.selected_items || [];
            this.updateCartBadge();
        } catch (error) {
            console.error("Error updating UI:", error);
        }
    }

    /**
     * Perform search
     */
    performSearch() {
        const newQuery = this.elements.searchInput.value.trim();
        if (newQuery !== this.searchQuery) {
            this.searchQuery = newQuery;
            this.currentPage = 1; // Reset to first page on new search
            if (this.elements.clearSearchBtn) {
                this.elements.clearSearchBtn.style.display = this.searchQuery
                    ? "block"
                    : "none";
            }
            this.loadCatalogData(); // Reload with search query
        }
    }

    /**
     * Clear search
     */
    clearSearch() {
        if (this.elements.searchInput) {
            this.elements.searchInput.value = "";
        }
        this.searchQuery = "";
        this.currentPage = 1;
        if (this.elements.clearSearchBtn) {
            this.elements.clearSearchBtn.style.display = "none";
        }
        this.loadCatalogData(); // Reload without search
    }

    /**
     * Show item details
     */
    showItemDetails(itemId) {
        const item = this.allItems.find((item) => {
            if (this.catalogType === "equipment") {
                return item.equipment_id == itemId;
            } else {
                return item.facility_id == itemId;
            }
        });

        if (!item) return;

        const isEquipment = this.catalogType === "equipment";
        const primaryImage = this.getPrimaryImage(item);
        const isUnavailable = item.status.status_id === 2;
        const itemType =
            this.catalogType === "equipment" ? "equipment" : "facility";
        const isSelected = this.selectedItems.some(
            (selectedItem) =>
                selectedItem.type === itemType &&
                parseInt(selectedItem[`${itemType}_id`]) == itemId,
        );

        document.getElementById("itemDetailModalLabel").textContent =
            isEquipment ? item.equipment_name : item.facility_name;

        document.getElementById("itemDetailContent").innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <img src="${primaryImage}" alt="${isEquipment ? item.equipment_name : item.facility_name}" 
                         class="img-fluid rounded" style="max-height: 300px; object-fit: cover;">
                </div>
                <div class="col-md-6">
                    <div class="item-details">
                        <p><strong>Status:</strong> <span class="badge" style="background-color: ${item.status.color_code}">${item.status.status_name}</span></p>
                        <p><strong>Category:</strong> ${item.category.category_name}</p>
                        ${!isEquipment ? `<p><strong>Subcategory:</strong> ${item.subcategory?.subcategory_name || "N/A"}</p>` : ""}
                        ${!isEquipment ? `<p><strong>Capacity:</strong> ${item.capacity}</p>` : `<p><strong>Available Quantity:</strong> ${item.available_quantity}/${item.total_quantity}</p>`}
                        <p><strong>Rate:</strong> ₱${parseFloat(item.base_fee).toLocaleString()} (${item.rate_type})</p>
                        <p><strong>Description:</strong></p>
                        <p>${item.description || "No description available."}</p>
                    </div>
                    <div class="mt-3">
                        ${
                            isUnavailable
                                ? `<button class="btn btn-secondary" disabled>Unavailable</button>`
                                : `<button class="btn ${isSelected ? "btn-danger" : "btn-primary"} add-remove-btn" 
                                data-id="${itemId}" 
                                data-type="${this.catalogType.slice(0, -1)}" 
                                data-action="${isSelected ? "remove" : "add"}">
                                ${isSelected ? "Remove from Form" : "Add to Form"}
                              </button>`
                        }
                    </div>
                </div>
            </div>
        `;

        const modal = new bootstrap.Modal(
            document.getElementById("itemDetailModal"),
        );
        modal.show();
    }

    /**
     * Show facility availability
     */
    showFacilityAvailability(button) {
        try {
            const itemData = {
                id: button.dataset.itemId,
                name: button.dataset.itemName,
                type: button.dataset.itemType,
                image: button.dataset.itemImage,
                category: button.dataset.itemCategory,
                status: button.dataset.itemStatus,
                statusColor: button.dataset.itemStatusColor,
                building: button.dataset.itemBuilding || null,
            };

            if (button.dataset.itemType === "equipment") {
                itemData.availableQty = button.dataset.itemAvailableQty;
                itemData.totalQty = button.dataset.itemTotalQty;
            } else {
                itemData.capacity = button.dataset.itemCapacity;
                itemData.fee = button.dataset.itemFee;
            }

            this.currentFacilityId = itemData.id;
            const isEquipment = itemData.type === "equipment";
            const isRoom = this.catalogType === "rooms";

            // Update modal title
            const modalTitleElement = document.getElementById(
                "singleFacilityAvailabilityModalLabel",
            );
            if (modalTitleElement) {
                let icon = "bi-calendar-check";
                let title = isEquipment
                    ? "Equipment Availability"
                    : "Facility Availability";
                if (isRoom) title = "Room Availability";

                modalTitleElement.innerHTML = `
                    <i class="bi ${isEquipment ? "bi-tools" : icon} me-2"></i>
                    <span id="facilityAvailabilityName">${title}</span>
                `;
            }

            // Update facility info
            const titleElement = document.getElementById("facilityTitleText");
            if (titleElement) titleElement.textContent = itemData.name || "N/A";

            const capacityElement = document.getElementById("facilityCapacity");
            if (capacityElement) {
                if (isEquipment) {
                    capacityElement.textContent = `${itemData.availableQty || "N/A"}/${itemData.totalQty || "N/A"} available`;
                } else {
                    let capacityText = `Capacity: ${itemData.capacity || "N/A"}`;
                    if (isRoom && itemData.building) {
                        capacityText += ` | Building: ${itemData.building}`;
                    }
                    capacityElement.textContent = capacityText;
                }
            }

            // Update image
            const imageContainer = document.getElementById(
                "facilityAvailabilityImage",
            );
            if (imageContainer) {
                if (itemData.image) {
                    imageContainer.innerHTML = `
                        <div class="facility-img-wrapper text-center">
                            <img src="${itemData.image}" alt="${itemData.name}" class="img-fluid rounded"
                                 style="max-height: 150px; object-fit: ${isEquipment ? "contain" : "cover"};"
                                 onerror="this.src='https://res.cloudinary.com/dn98ntlkd/image/upload/v1759850278/t4fyv56wog6pglhwvwtn.png'">
                        </div>
                    `;
                } else {
                    imageContainer.innerHTML = `<i class="bi ${isEquipment ? "bi-tools" : "bi-building"} fs-1 text-muted"></i>`;
                }
            }

            // Update category
            const categoryElement = document.getElementById("facilityCategory");
            if (categoryElement)
                categoryElement.textContent = itemData.category || "N/A";

            // Update status badge
            const statusBadge = document.getElementById("facilityStatusBadge");
            if (statusBadge) {
                statusBadge.textContent = itemData.status || "N/A";
                statusBadge.style.backgroundColor =
                    itemData.statusColor || "#6c757d";
                statusBadge.style.color = "#fff";
            }

            // Update book button
            const bookNowBtn = document.getElementById("bookNowBtn");
            if (bookNowBtn) {
                bookNowBtn.innerHTML = `
                    <i class="bi ${isEquipment ? "bi-cart-plus" : "bi-calendar-plus"} me-1"></i> 
                    ${isEquipment ? "Add to Form" : "Book This Facility"}
                `;
                bookNowBtn.onclick = () => {
                    if (this.currentFacilityId) {
                        const type = isEquipment ? "equipment" : "facility";
                        this.addToForm(this.currentFacilityId, type);
                        const modal = bootstrap.Modal.getInstance(
                            document.getElementById(
                                "singleFacilityAvailabilityModal",
                            ),
                        );
                        if (modal) modal.hide();
                    }
                };
            }

            // Initialize legend
            this.renderDynamicLegend();

            // Show modal
            const modalElement = document.getElementById(
                "singleFacilityAvailabilityModal",
            );
            if (modalElement) {
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
                modalElement.addEventListener("hidden.bs.modal", () => {
                    if (
                        this.availabilityCalendarInstance &&
                        this.availabilityCalendarInstance.calendar
                    ) {
                        try {
                            this.availabilityCalendarInstance.calendar.destroy();
                        } catch (e) {
                            console.log("Calendar already destroyed");
                        }
                        this.availabilityCalendarInstance = null;
                    }
                });
            }

            // Show loading overlay
            const loadingOverlay = document.getElementById(
                "availabilityLoadingOverlay",
            );
            if (loadingOverlay) loadingOverlay.classList.remove("hidden");

            // Initialize calendar
            setTimeout(() => {
                if (isEquipment) {
                    this.initEquipmentAvailabilityCalendar(itemData.id);
                } else {
                    this.initAvailabilityCalendar(itemData.id);
                }
            }, 300);
        } catch (error) {
            console.error("Error showing facility availability:", error);
            this.showToast("Failed to open availability modal", "error");
        }
    }

    /**
     * Initialize equipment availability calendar
     */
    async initEquipmentAvailabilityCalendar(equipmentId) {
        if (!this.CalendarModule) {
            console.error("CalendarModule not provided");
            return;
        }

        try {
            const loadingOverlay = document.getElementById(
                "availabilityLoadingOverlay",
            );
            if (loadingOverlay) loadingOverlay.classList.remove("hidden");

            if (
                this.availabilityCalendarInstance &&
                this.availabilityCalendarInstance.calendar
            ) {
                this.availabilityCalendarInstance.calendar.destroy();
                this.availabilityCalendarInstance = null;
            }

            this.availabilityCalendarInstance = new this.CalendarModule({
                isAdmin: false,
                apiEndpoint: `/api/requisition-forms/calendar-events`,
                containerId: "facilityAvailabilityCalendar",
                miniCalendarContainerId: "availabilityMiniCalendarDays",
                monthYearId: "availabilityCurrentMonthYear",
                eventModalId: "calendarEventModal",
            });

            const originalHideLoadingOverlay =
                this.availabilityCalendarInstance.hideLoadingOverlay;
            this.availabilityCalendarInstance.hideLoadingOverlay = () => {
                const availabilityOverlay = document.getElementById(
                    "availabilityLoadingOverlay",
                );
                if (availabilityOverlay)
                    availabilityOverlay.classList.add("hidden");
                if (typeof originalHideLoadingOverlay === "function")
                    originalHideLoadingOverlay.call(this);
            };

            this.availabilityCalendarInstance.updateLoadingState = (
                isLoading,
            ) => {
                const availabilityOverlay = document.getElementById(
                    "availabilityLoadingOverlay",
                );
                if (availabilityOverlay) {
                    if (isLoading)
                        availabilityOverlay.classList.remove("hidden");
                    else availabilityOverlay.classList.add("hidden");
                }
            };

            await this.availabilityCalendarInstance.loadStatuses();

            const originalLoadEvents =
                this.availabilityCalendarInstance.loadCalendarEvents;
            this.availabilityCalendarInstance.loadCalendarEvents = async () => {
                try {
                    const headers = {};
                    if (
                        this.availabilityCalendarInstance.config.isAdmin &&
                        this.availabilityCalendarInstance.config.adminToken
                    ) {
                        headers["Authorization"] =
                            `Bearer ${this.availabilityCalendarInstance.config.adminToken}`;
                    }

                    const response = await fetch(
                        `${this.availabilityCalendarInstance.config.apiEndpoint}`,
                        { headers },
                    );
                    const result = await response.json();

                    if (result.success && result.data) {
                        this.availabilityCalendarInstance.allEvents =
                            result.data
                                .filter((event) => event != null)
                                .filter((event) => {
                                    const eventEquipment =
                                        event.extendedProps?.equipment || [];
                                    return eventEquipment.some(
                                        (eq) =>
                                            eq.equipment_id &&
                                            eq.equipment_id.toString() ===
                                                equipmentId.toString(),
                                    );
                                })
                                .map((event) => {
                                    const statusName =
                                        event.extendedProps?.status;
                                    const statusColor =
                                        this.availabilityCalendarInstance
                                            .statusColors[statusName] ||
                                        event.extendedProps?.color ||
                                        "#007bff";
                                    return {
                                        ...event,
                                        color: statusColor,
                                        extendedProps: {
                                            ...event.extendedProps,
                                            color: statusColor,
                                        },
                                    };
                                });

                        this.availabilityCalendarInstance.applyFilters();

                        if (
                            this.availabilityCalendarInstance.allEvents
                                .length === 0
                        ) {
                            const calendarEl = document.getElementById(
                                "facilityAvailabilityCalendar",
                            );
                            if (calendarEl) {
                                calendarEl.innerHTML = `
                                    <div class="text-center py-5">
                                        <i class="bi bi-calendar-x fs-1 text-muted"></i>
                                        <p class="mt-2">No bookings found for this equipment</p>
                                        <p class="text-muted small">This equipment has not been booked yet</p>
                                    </div>
                                `;
                            }
                        }

                        this.availabilityCalendarInstance.updateLoadingState(
                            false,
                        );
                    } else {
                        this.availabilityCalendarInstance.allEvents = [];
                        this.availabilityCalendarInstance.applyFilters();
                        this.availabilityCalendarInstance.updateLoadingState(
                            false,
                        );
                    }
                } catch (error) {
                    console.error("Error loading calendar events:", error);
                    this.availabilityCalendarInstance.allEvents = [];
                    this.availabilityCalendarInstance.updateLoadingState(false);
                }
            };

            await this.availabilityCalendarInstance.initialize();

            setTimeout(() => {
                if (this.availabilityCalendarInstance.calendar) {
                    this.availabilityCalendarInstance.calendar.updateSize();
                }
            }, 300);
        } catch (error) {
            console.error(
                "Failed to initialize equipment availability calendar:",
                error,
            );
            const loadingOverlay = document.getElementById(
                "availabilityLoadingOverlay",
            );
            if (loadingOverlay) loadingOverlay.classList.add("hidden");

            const calendarEl = document.getElementById(
                "facilityAvailabilityCalendar",
            );
            if (calendarEl) {
                calendarEl.innerHTML = `
                    <div class="text-center py-5">
                        <i class="bi bi-exclamation-triangle fs-1 text-danger"></i>
                        <p class="mt-2">Failed to load availability</p>
                        <p class="text-muted small">Please try again later</p>
                    </div>
                `;
            }
        }
    }

    /**
     * Initialize availability calendar
     */
    async initAvailabilityCalendar(facilityId) {
        if (!this.CalendarModule) {
            console.error("CalendarModule not provided");
            return;
        }

        try {
            const loadingOverlay = document.getElementById(
                "availabilityLoadingOverlay",
            );
            if (loadingOverlay) loadingOverlay.classList.remove("hidden");

            if (
                this.availabilityCalendarInstance &&
                this.availabilityCalendarInstance.calendar
            ) {
                this.availabilityCalendarInstance.calendar.destroy();
                this.availabilityCalendarInstance = null;
            }

            this.availabilityCalendarInstance = new this.CalendarModule({
                isAdmin: false,
                apiEndpoint: `/api/requisition-forms/calendar-events`,
                containerId: "facilityAvailabilityCalendar",
                miniCalendarContainerId: "availabilityMiniCalendarDays",
                monthYearId: "availabilityCurrentMonthYear",
                eventModalId: "calendarEventModal",
            });

            const originalHideLoadingOverlay =
                this.availabilityCalendarInstance.hideLoadingOverlay;
            this.availabilityCalendarInstance.hideLoadingOverlay = () => {
                const availabilityOverlay = document.getElementById(
                    "availabilityLoadingOverlay",
                );
                if (availabilityOverlay)
                    availabilityOverlay.classList.add("hidden");
                if (typeof originalHideLoadingOverlay === "function")
                    originalHideLoadingOverlay.call(this);
            };

            this.availabilityCalendarInstance.updateLoadingState = (
                isLoading,
            ) => {
                const availabilityOverlay = document.getElementById(
                    "availabilityLoadingOverlay",
                );
                if (availabilityOverlay) {
                    if (isLoading)
                        availabilityOverlay.classList.remove("hidden");
                    else availabilityOverlay.classList.add("hidden");
                }
            };

            await this.availabilityCalendarInstance.loadStatuses();

            const originalLoadEvents =
                this.availabilityCalendarInstance.loadCalendarEvents;
            this.availabilityCalendarInstance.loadCalendarEvents = async () => {
                try {
                    const headers = {};
                    if (
                        this.availabilityCalendarInstance.config.isAdmin &&
                        this.availabilityCalendarInstance.config.adminToken
                    ) {
                        headers["Authorization"] =
                            `Bearer ${this.availabilityCalendarInstance.config.adminToken}`;
                    }

                    const response = await fetch(
                        `${this.availabilityCalendarInstance.config.apiEndpoint}`,
                        { headers },
                    );
                    const result = await response.json();

                    if (result.success && result.data) {
                        this.availabilityCalendarInstance.allEvents =
                            result.data
                                .filter((event) => event != null)
                                .filter((event) => {
                                    const eventFacilities =
                                        event.extendedProps?.facilities || [];
                                    return eventFacilities.some(
                                        (f) =>
                                            f.facility_id &&
                                            f.facility_id.toString() ===
                                                facilityId.toString(),
                                    );
                                })
                                .map((event) => {
                                    const statusName =
                                        event.extendedProps?.status;
                                    const statusColor =
                                        this.availabilityCalendarInstance
                                            .statusColors[statusName] ||
                                        event.extendedProps?.color ||
                                        "#007bff";
                                    return {
                                        ...event,
                                        color: statusColor,
                                        extendedProps: {
                                            ...event.extendedProps,
                                            color: statusColor,
                                        },
                                    };
                                });

                        this.availabilityCalendarInstance.applyFilters();
                        this.availabilityCalendarInstance.updateLoadingState(
                            false,
                        );
                    } else {
                        this.availabilityCalendarInstance.allEvents = [];
                        this.availabilityCalendarInstance.applyFilters();
                        this.availabilityCalendarInstance.updateLoadingState(
                            false,
                        );
                    }
                } catch (error) {
                    console.error("Error loading calendar events:", error);
                    this.availabilityCalendarInstance.allEvents = [];
                    this.availabilityCalendarInstance.updateLoadingState(false);
                }
            };

            await this.availabilityCalendarInstance.initialize();

            setTimeout(() => {
                if (this.availabilityCalendarInstance.calendar) {
                    this.availabilityCalendarInstance.calendar.updateSize();
                }
            }, 300);
        } catch (error) {
            console.error("Failed to initialize availability calendar:", error);
            const loadingOverlay = document.getElementById(
                "availabilityLoadingOverlay",
            );
            if (loadingOverlay) loadingOverlay.classList.add("hidden");
        }
    }

    /**
     * Render dynamic legend
     */
    async renderDynamicLegend() {
        const legendContainer = document.getElementById("dynamicLegend");
        if (!legendContainer) return;

        try {
            const response = await fetch("/api/form-statuses");
            if (response.ok) {
                const statuses = await response.json();
                if (Array.isArray(statuses)) {
                    const activeStatuses = statuses.filter(
                        (status) => status.status_id <= 6,
                    );
                    legendContainer.innerHTML = "";
                    activeStatuses.forEach((status) => {
                        const legendItem = document.createElement("div");
                        legendItem.className = "d-flex align-items-center";
                        legendItem.innerHTML = `
                            <div class="color-box me-2" style="background-color: ${status.color_code}; width: 16px; height: 16px; border-radius: 3px;"></div>
                            <small>${status.status_name}</small>
                        `;
                        legendContainer.appendChild(legendItem);
                    });
                }
            }
        } catch (error) {
            console.error("Failed to load form statuses:", error);
            legendContainer.innerHTML =
                '<div class="text-muted small">Failed to load status colors</div>';
        }
    }

    /**
     * Load form statuses
     */
    async loadFormStatuses() {
        try {
            const statusesResponse = await this.fetchData("/api/form-statuses");
            if (statusesResponse && Array.isArray(statusesResponse)) {
                const activeStatuses = statusesResponse.filter(
                    (status) => status.status_id <= 6,
                );
                activeStatuses.forEach((status) => {
                    this.formStatuses[status.status_name] = status.color_code;
                });
            }
        } catch (error) {
            console.error("Failed to load form statuses:", error);
        }
    }
}

// Export for use in other files
if (typeof module !== "undefined" && module.exports) {
    module.exports = BookingCatalog;
}

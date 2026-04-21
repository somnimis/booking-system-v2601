/**
 * BookingCatalog Module - Simplified Version
 */

console.log("Catalog.js loaded successfully");
window.addEventListener("error", function (e) {
    console.error(
        "Global error caught:",
        e.message,
        "at",
        e.filename,
        "line",
        e.lineno,
    );
    return false;
});

class BookingCatalog {
    constructor(config = {}) {
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

        // State
        this.catalogType = this.config.defaultCatalogType;
        this.currentPage = 1;
        this.allItems = [];
        this.itemCategories = [];
        this.currentLayout = this.config.defaultLayout;
        this.selectedItems = [];
        this.statusFilter = "All";
        this.searchQuery = "";
        this.searchTimeout = null;
        this.paginationMeta = {};
        this.elements = {};
        this.CalendarModule = null;
    }

    async init(CalendarModuleClass) {
        this.CalendarModule = CalendarModuleClass;
        this.cacheDomElements();
        this.setupEventListeners();
        this.setInitialUI();
        await this.loadCategories();
        await this.loadCatalogData();
        await this.loadFormStatuses();
        console.log("BookingCatalog initialized");
    }

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

    setInitialUI() {
        document.querySelectorAll(".catalog-type-tab").forEach((tab) => {
            if (tab.dataset.type === this.catalogType)
                tab.classList.add("active");
        });

        const statusRadio = document.querySelector(
            `#${this.config.filterDropdownMenuId} .status-option[data-status="All"]`,
        );
        if (statusRadio) statusRadio.checked = true;

        const layoutRadio = document.querySelector(
            `#${this.config.filterDropdownMenuId} .layout-option[data-layout="grid"]`,
        );
        if (layoutRadio) layoutRadio.checked = true;
    }

    setupEventListeners() {
        // Tab switching
        document.querySelectorAll(".catalog-type-tab").forEach((tab) => {
            tab.addEventListener("click", (e) => {
                e.preventDefault();
                this.switchCatalogType(tab.dataset.type);
            });
        });

        // Search
        if (this.elements.searchForm) {
            this.elements.searchForm.addEventListener("submit", (e) =>
                e.preventDefault(),
            );
        }
        if (this.elements.searchInput) {
            this.elements.searchInput.addEventListener("input", () => {
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(
                    () => this.performSearch(),
                    300,
                );
            });
            this.elements.searchInput.addEventListener("keydown", (e) => {
                if (e.key === "Escape") this.clearSearch();
            });
        }
        if (this.elements.clearSearchBtn) {
            this.elements.clearSearchBtn.addEventListener("click", () =>
                this.clearSearch(),
            );
        }

        // Add/Remove buttons (delegation)
        this.elements.catalogItemsContainer.addEventListener(
            "click",
            async (e) => {
                const button = e.target.closest(".add-remove-btn");
                if (button && !button.disabled)
                    await this.handleAddRemoveAction(button);
            },
        );

        // Quantity input validation for equipment
        this.elements.catalogItemsContainer.addEventListener("input", (e) => {
            if (e.target.classList.contains("quantity-input")) {
                this.validateQuantityInput(e.target);
            }
        });

        // Check availability
        this.elements.catalogItemsContainer.addEventListener(
            "click",
            async (e) => {
                const button = e.target.closest(".check-availability-btn");
                if (button) {
                    e.preventDefault();
                    this.showFacilityAvailability(button);
                }
            },
        );

        // Filters
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
            if (e.key === "formUpdated") {
                this.fetchSelectedItems();
            }
        });
    }

    async switchCatalogType(type) {
        this.catalogType = type;
        this.currentPage = 1;
        this.searchQuery = "";
        if (this.elements.searchInput) this.elements.searchInput.value = "";
        if (this.elements.clearSearchBtn)
            this.elements.clearSearchBtn.style.display = "none";

        const titles = {
            venues: "Venues & Event Spaces",
            rooms: "Rooms & Dormitories",
            equipment: "Equipment Catalog",
        };
        if (this.elements.catalogHeroTitle)
            this.elements.catalogHeroTitle.textContent = titles[type];

        document.querySelectorAll(".catalog-type-tab").forEach((tab) => {
            tab.classList.toggle("active", tab.dataset.type === type);
        });

        // Show/hide building filter card for rooms
        const buildingCard = document.getElementById("buildingFilterCard");
        if (buildingCard)
            buildingCard.style.display = type === "rooms" ? "block" : "none";

        // Reload categories for the new tab
        await this.loadCategories();
        await this.loadCatalogData();
    }

    async loadCategories() {
        const api = this.config.apiEndpoints[this.catalogType];
        const response = await this.fetchData(api.categories);
        this.itemCategories = Array.isArray(response)
            ? response
            : response.data || [];
        this.renderCategoryFilters();

        // Load buildings for rooms tab
        if (this.catalogType === "rooms") {
            await this.loadBuildings();
        }
    }

    async loadBuildings() {
        const buildingFilterList =
            document.getElementById("buildingFilterList");
        if (!buildingFilterList) return;

        const response = await this.fetchData(
            "/api/facilities/parent-buildings-for-rooms",
        );
        const buildings = response.data || [];

        buildingFilterList.innerHTML =
            buildings.length === 0
                ? '<div class="text-muted small">No buildings available</div>'
                : buildings
                      .map(
                          (building) => `
                <div class="form-check mb-1">
                    <input class="form-check-input building-filter" type="checkbox" 
                           id="building${building.facility_id}" value="${building.facility_id}">
                    <label class="form-check-label small" for="building${building.facility_id}">
                        ${building.facility_name}
                        ${building.facility_code ? `<span class="text-muted">(${building.facility_code})</span>` : ""}
                    </label>
                </div>
            `,
                      )
                      .join("");

        document.querySelectorAll(".building-filter").forEach((cb) => {
            cb.addEventListener("change", () => this.filterAndRenderItems());
        });
    }

    renderCategoryFilters() {
        if (!this.elements.categoryFilterList) return;

        const container = this.elements.categoryFilterList;
        container.innerHTML = `
            <div class="category-item">
                <div class="form-check">
                    <input class="form-check-input category-filter" type="checkbox" id="allCategories" value="All" checked disabled>
                    <label class="form-check-label" for="allCategories">All Categories</label>
                </div>
            </div>
        `;

        let categories = this.itemCategories;
        if (this.catalogType === "rooms") {
            categories = this.itemCategories.filter(
                (c) => c.category_id === 2 || c.category_id === 3,
            );
        }

        categories.forEach((category) => {
            const hasSubcats = category.subcategories?.length > 0;
            const categoryHtml = `
                <div class="category-item" data-category-id="${category.category_id}">
                    <div class="form-check d-flex justify-content-between align-items-center">
                        <div>
                            <input class="form-check-input category-filter" type="checkbox" 
                                   id="category${category.category_id}" value="${category.category_id}">
                            <label class="form-check-label" for="category${category.category_id}">${category.category_name}</label>
                        </div>
                        ${hasSubcats ? '<i class="bi bi-chevron-up toggle-arrow" style="cursor:pointer"></i>' : ""}
                    </div>
${
    hasSubcats
        ? `
    <div class="subcategory-list ms-3" style="overflow: hidden; max-height: ${category.subcategories.length * 35}px;">
        ${category.subcategories
            .map(
                (sub) => `
            <div class="form-check">
                <input class="form-check-input subcategory-filter" type="checkbox" 
                       id="subcategory${sub.subcategory_id}" value="${sub.subcategory_id}" 
                       data-category="${category.category_id}">
                <label class="form-check-label small" for="subcategory${sub.subcategory_id}">
                    ${sub.subcategory_name}
                </label>
            </div>
        `,
            )
            .join("")}
    </div>
`
        : ""
}
                </div>
            `;
            container.insertAdjacentHTML("beforeend", categoryHtml);

            // Setup toggle for subcategories
            const toggleArrow = container.querySelector(
                `[data-category-id="${category.category_id}"] .toggle-arrow`,
            );
            if (toggleArrow) {
                const subcatList = container.querySelector(
                    `[data-category-id="${category.category_id}"] .subcategory-list`,
                );
                toggleArrow.addEventListener("click", () => {
                    const isExpanded = subcatList.style.maxHeight !== "0px";
                    subcatList.style.maxHeight = isExpanded
                        ? "0"
                        : `${subcatList.scrollHeight}px`;
                    toggleArrow.classList.replace(
                        isExpanded ? "bi-chevron-up" : "bi-chevron-down",
                        isExpanded ? "bi-chevron-down" : "bi-chevron-up",
                    );
                });
            }
        });

        this.setupCategoryEvents();
    }

    setupCategoryEvents() {
        const allCategories = document.getElementById("allCategories");
        const categoryCheckboxes = document.querySelectorAll(
            ".category-filter:not(#allCategories)",
        );
        const subcategoryCheckboxes = document.querySelectorAll(
            ".subcategory-filter",
        );

        const updateAllCategories = () => {
            if (!allCategories) return;
            const anyChecked =
                [...categoryCheckboxes].some((cb) => cb.checked) ||
                [...subcategoryCheckboxes].some((cb) => cb.checked);
            allCategories.checked = !anyChecked;
            allCategories.disabled = anyChecked;
        };

        // Category checkbox logic
        categoryCheckboxes.forEach((cb) => {
            cb.addEventListener("change", () => {
                const catId = cb.value;
                const relatedSubs = [...subcategoryCheckboxes].filter(
                    (sub) => sub.dataset.category === catId,
                );

                if (cb.checked) {
                    relatedSubs.forEach((sub) => (sub.checked = true));
                } else {
                    relatedSubs.forEach((sub) => (sub.checked = false));
                }

                updateAllCategories();
                this.filterAndRenderItems();
            });
        });

        // Subcategory checkbox logic
        subcategoryCheckboxes.forEach((sub) => {
            sub.addEventListener("change", () => {
                const catId = sub.dataset.category;
                const parentCategory = [...categoryCheckboxes].find(
                    (cb) => cb.value === catId,
                );
                const siblings = [...subcategoryCheckboxes].filter(
                    (s) => s.dataset.category === catId,
                );
                const allSiblingsChecked =
                    siblings.length > 0 && siblings.every((s) => s.checked);

                if (parentCategory) {
                    parentCategory.checked = allSiblingsChecked;
                }

                updateAllCategories();
                this.filterAndRenderItems();
            });
        });

        // All Categories logic
        if (allCategories) {
            allCategories.addEventListener("change", () => {
                if (allCategories.checked) {
                    categoryCheckboxes.forEach((cb) => (cb.checked = false));
                    subcategoryCheckboxes.forEach(
                        (sub) => (sub.checked = false),
                    );
                    this.filterAndRenderItems();
                }
            });
        }
    }

    getSelectedFilters() {
        const categories = [
            ...document.querySelectorAll(".category-filter:checked"),
        ]
            .filter((cb) => cb.id !== "allCategories")
            .map((cb) => cb.value);
        const subcategories = [
            ...document.querySelectorAll(".subcategory-filter:checked"),
        ].map((cb) => cb.value);
        const buildings = [
            ...document.querySelectorAll(".building-filter:checked"),
        ].map((cb) => cb.value);
        return { categories, subcategories, buildings };
    }

    async loadCatalogData() {
        try {
            // Show loading indicator
            if (this.elements.loadingIndicator) {
                this.elements.loadingIndicator.style.display = "block";
            }
            if (this.elements.catalogItemsContainer) {
                this.elements.catalogItemsContainer.classList.add("d-none");
            }

            const api = this.config.apiEndpoints[this.catalogType];
            const { categories, subcategories, buildings } =
                this.getSelectedFilters();

            const params = new URLSearchParams({
                page: this.currentPage,
                per_page: this.config.itemsPerPage,
            });

            if (this.searchQuery) params.append("search", this.searchQuery);
            if (this.statusFilter !== "All")
                params.append(
                    "status",
                    this.statusFilter === "Available" ? 1 : 2,
                );

            categories.forEach((c) => params.append("categories[]", c));
            subcategories.forEach((s) => params.append("subcategories[]", s));
            if (this.catalogType === "rooms")
                buildings.forEach((b) => params.append("buildings[]", b));

            const response = await this.fetchData(
                `${api.items}?${params.toString()}`,
            );

            // Handle paginated response (equipment) and non-paginated (venues/rooms)
            if (response && response.data) {
                this.allItems = Array.isArray(response.data)
                    ? response.data
                    : [];
                this.paginationMeta = {
                    current_page: response.current_page || 1,
                    last_page: response.last_page || 1,
                    total: response.total || this.allItems.length,
                    per_page: response.per_page || this.config.itemsPerPage,
                };
            } else if (Array.isArray(response)) {
                this.allItems = response;
                this.paginationMeta = {
                    current_page: 1,
                    last_page: 1,
                    total: response.length,
                    per_page: response.length,
                };
            } else {
                this.allItems = [];
                this.paginationMeta = {
                    current_page: 1,
                    last_page: 1,
                    total: 0,
                    per_page: this.config.itemsPerPage,
                };
            }

            this.renderItems();
            this.renderPagination();
            await this.fetchSelectedItems();

            // Hide loading indicator
            if (this.elements.catalogItemsContainer) {
                this.elements.catalogItemsContainer.classList.remove("d-none");
            }
            if (this.elements.loadingIndicator) {
                this.elements.loadingIndicator.style.display = "none";
            }
        } catch (error) {
            console.error("Error loading data:", error);
            if (this.elements.loadingIndicator) {
                this.elements.loadingIndicator.style.display = "none";
            }
            if (this.config.onError) this.config.onError(error);
        }
    }

    filterAndRenderItems() {
        this.currentPage = 1;
        this.loadCatalogData();
    }

    renderItems() {
        const container = this.elements.catalogItemsContainer;
        container.innerHTML = "";
        container.classList.remove("grid-layout", "list-layout");
        container.classList.add(`${this.currentLayout}-layout`);

        if (!this.allItems.length) {
            let icon = "bi-building";
            if (this.catalogType === "rooms") icon = "bi-door-open";
            else if (this.catalogType === "equipment") icon = "bi-box-seam";

            container.innerHTML = `<div style="grid-column:1/-1; display:flex; flex-direction:column; justify-content:center; align-items:center; min-height:220px;">
                <i class="bi ${icon} fs-1 text-muted"></i>
                <h4 class="mt-2">No ${this.catalogType} found</h4>
                <p class="text-muted">Try adjusting your filters or search criteria</p>
            </div>`;
            return;
        }

        const renderMethod =
            this.currentLayout === "grid"
                ? this.renderGridCard
                : this.renderListCard;
        container.innerHTML = this.allItems
            .map((item) => renderMethod.call(this, item))
            .join("");

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

    renderGridCard(item) {
        const isEquipment = this.catalogType === "equipment";
        const name = isEquipment ? item.equipment_name : item.facility_name;
        const id = isEquipment ? item.equipment_id : item.facility_id;
        const image = this.getPrimaryImage(item);
        const buildingInfo =
            this.catalogType === "rooms" && item.parent_facility
                ? `<span><i class="bi bi-building"></i> ${item.parent_facility.facility_name}</span>`
                : "";

        return `
            <div class="catalog-card">
                <span class="item-type-badge badge ${isEquipment ? "bg-info" : "bg-warning"}">${isEquipment ? "Equipment" : this.catalogType === "rooms" ? "Room" : "Venue"}</span>
                <img src="${image}" class="catalog-card-img" onerror="this.src='https://res.cloudinary.com/dn98ntlkd/image/upload/v1759850278/t4fyv56wog6pglhwvwtn.png'">
                <div class="catalog-card-details">
                    <h5 data-id="${id}" title="${name}">${this.truncateText(name, 18)}</h5>
                    <span class="status-banner" style="background-color: ${item.status.color_code}">${item.status.status_name}</span>
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
                    <p class="facility-description" title="${item.description || ""}">${this.truncateText(item.description || "No description available", 100)}</p>
                    <div class="catalog-card-fee"><i class="bi bi-cash-stack"></i> ₱${parseFloat(item.base_fee).toLocaleString()} (${item.rate_type})</div>
                </div>
                <div class="catalog-card-actions">
                    ${isEquipment ? this.getEquipmentActions(item) : this.getFacilityActions(item)}
                    ${this.getCheckAvailabilityBtn(item, id, name, image)}
                </div>
            </div>
        `;
    }

    renderListCard(item) {
        const isEquipment = this.catalogType === "equipment";
        const name = isEquipment ? item.equipment_name : item.facility_name;
        const id = isEquipment ? item.equipment_id : item.facility_id;
        const image = this.getPrimaryImage(item);
        const buildingInfo =
            this.catalogType === "rooms" && item.parent_facility
                ? `<span><i class="bi bi-building"></i> ${item.parent_facility.facility_name}</span>`
                : "";

        return `
            <div class="catalog-card">
                <img src="${image}" class="catalog-card-img" onerror="this.src='https://res.cloudinary.com/dn98ntlkd/image/upload/v1759850278/t4fyv56wog6pglhwvwtn.png'">
                <div class="catalog-card-details">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 data-id="${id}" title="${name}">${this.truncateText(name, 30)}</h5>
                        <span class="status-banner" style="background-color: ${item.status.color_code}">${item.status.status_name}</span>
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
                    <p class="facility-description" title="${item.description || ""}">${this.truncateText(item.description || "No description available", 150)}</p>
                </div>
                <div class="catalog-card-actions">
                    <div class="catalog-card-fee mb-2 text-center"><i class="bi bi-cash-stack"></i> ₱${parseFloat(item.base_fee).toLocaleString()} (${item.rate_type})</div>
                    ${isEquipment ? this.getEquipmentActions(item) : this.getFacilityActions(item)}
                    ${this.getCheckAvailabilityBtn(item, id, name, image)}
                </div>
            </div>
        `;
    }

    getEquipmentActions(item) {
        const isSelected = this.selectedItems.some(
            (s) =>
                s.type === "equipment" &&
                parseInt(s.equipment_id) === item.equipment_id,
        );
        const selectedItem = this.selectedItems.find(
            (s) =>
                s.type === "equipment" &&
                parseInt(s.equipment_id) === item.equipment_id,
        );
        const currentQty = selectedItem?.quantity || 1;
        const maxQty = item.available_quantity || 0;
        const isUnavailable = item.status.status_id !== 1 || maxQty === 0;

        if (isUnavailable) {
            return `<div class="equipment-actions"><div class="equipment-quantity-selector"><input type="number" class="form-control quantity-input" value="${currentQty}" min="1" max="${maxQty}" disabled><button class="btn btn-secondary add-remove-btn form-action-btn" disabled>Unavailable</button></div></div>`;
        }

        if (isSelected) {
            return `<div class="equipment-actions"><div class="equipment-quantity-selector"><input type="number" class="form-control quantity-input" value="${currentQty}" min="1" max="${maxQty}"><button class="btn btn-danger add-remove-btn form-action-btn" data-id="${item.equipment_id}" data-type="equipment" data-action="remove">Remove</button></div></div>`;
        }

        return `<div class="equipment-actions"><div class="equipment-quantity-selector"><input type="number" class="form-control quantity-input" value="1" min="1" max="${maxQty}"><button class="btn btn-primary add-remove-btn form-action-btn" data-id="${item.equipment_id}" data-type="equipment" data-action="add">Add</button></div></div>`;
    }

    getFacilityActions(item) {
        const isUnavailable = item.status.status_id === 2;
        const isSelected = this.selectedItems.some(
            (s) =>
                s.type === "facility" &&
                parseInt(s.facility_id) === item.facility_id,
        );

        if (isUnavailable)
            return `<button class="btn btn-secondary add-remove-btn form-action-btn" disabled>Unavailable</button>`;
        if (isSelected)
            return `<button class="btn btn-danger add-remove-btn form-action-btn" data-id="${item.facility_id}" data-type="facility" data-action="remove">Remove from form</button>`;
        return `<button class="btn btn-primary add-remove-btn form-action-btn" data-id="${item.facility_id}" data-type="facility" data-action="add">Add to form</button>`;
    }

    getCheckAvailabilityBtn(item, id, name, image) {
        const isEquipment = this.catalogType === "equipment";
        let additionalData = "";
        if (isEquipment) {
            additionalData = `data-item-available-qty="${item.available_quantity || 0}" data-item-total-qty="${item.total_quantity || 0}"`;
        } else {
            additionalData = `data-item-capacity="${item.capacity || "N/A"}" data-item-fee="${parseFloat(item.base_fee).toLocaleString()}"`;
            if (this.catalogType === "rooms" && item.parent_facility) {
                additionalData += ` data-item-building="${this.escapeHtml(item.parent_facility.facility_name)}"`;
            }
        }

        return `<button class="btn btn-light btn-custom check-availability-btn form-action-btn" 
                    data-item-id="${id}" 
                    data-item-name="${this.escapeHtml(name)}" 
                    data-item-type="${isEquipment ? "equipment" : "facility"}"
                    data-item-image="${image}" 
                    data-item-category="${this.escapeHtml(item.category.category_name)}"
                    data-item-status="${item.status.status_name}" 
                    data-item-status-color="${item.status.color_code}"
                    ${additionalData}>
                    <i class="bi bi-calendar-check me-1"></i> Check Availability
                </button>`;
    }

    renderPagination() {
        const container = this.elements.pagination;
        if (!container) return;

        const { current_page, last_page } = this.paginationMeta;
        if (last_page <= 1) {
            container.innerHTML = "";
            return;
        }

        let html = "";
        if (current_page > 1)
            html += `<li class="page-item"><a class="page-link" href="#" data-page="${current_page - 1}">&laquo;</a></li>`;

        for (
            let i = Math.max(1, current_page - 2);
            i <= Math.min(last_page, current_page + 2);
            i++
        ) {
            html += `<li class="page-item ${i === current_page ? "active" : ""}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
        }

        if (current_page < last_page)
            html += `<li class="page-item"><a class="page-link" href="#" data-page="${current_page + 1}">&raquo;</a></li>`;

        container.innerHTML = html;
        container.querySelectorAll(".page-link").forEach((link) => {
            link.addEventListener("click", (e) => {
                e.preventDefault();
                this.currentPage = parseInt(link.dataset.page);
                this.loadCatalogData();
                window.scrollTo({
                    top: this.elements.catalogItemsContainer?.offsetTop - 100,
                    behavior: "smooth",
                });
            });
        });
    }

    async fetchSelectedItems() {
        try {
            const response = await this.fetchData("/api/requisition/get-items");
            this.selectedItems = response.data?.selected_items || [];
            this.updateCartBadge();
            this.syncButtonsWithSelectedItems();
        } catch (e) {
            console.warn("Failed to fetch selected items:", e);
            this.selectedItems = [];
        }
    }

    syncButtonsWithSelectedItems() {
        // Reset all add-remove buttons to default state first
        document.querySelectorAll(".add-remove-btn").forEach((btn) => {
            const type = btn.dataset.type;
            if (type === "equipment") {
                btn.textContent = "Add";
                btn.classList.remove("btn-danger");
                btn.classList.add("btn-primary");
                btn.dataset.action = "add";
            } else {
                btn.textContent = "Add to form";
                btn.classList.remove("btn-danger");
                btn.classList.add("btn-primary");
                btn.dataset.action = "add";
            }
        });

        // Update buttons for selected items
        this.selectedItems.forEach((selectedItem) => {
            const id = selectedItem.equipment_id || selectedItem.facility_id;
            const type = selectedItem.type;

            const button = document.querySelector(
                `.add-remove-btn[data-id="${id}"][data-type="${type}"]`,
            );
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
    }

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

    validateQuantityInput(input) {
        const button = input
            .closest(".equipment-quantity-selector")
            ?.querySelector(".add-remove-btn");
        if (!button) return;

        const id = button.dataset.id;
        const quantity = parseInt(input.value) || 0;

        const equipmentItem = this.allItems.find(
            (item) => item.equipment_id === parseInt(id),
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

    async handleAddRemoveAction(button) {
        const id = button.dataset.id;
        const type = button.dataset.type;
        const action = button.dataset.action;
        let quantity = 1;

        if (type === "equipment") {
            const quantityInput = button
                .closest(".equipment-quantity-selector")
                ?.querySelector(".quantity-input");
            quantity = parseInt(quantityInput?.value) || 1;
        }

        try {
            if (action === "add") {
                await this.addToForm(id, type, quantity);
            } else if (action === "remove") {
                await this.removeFromForm(id, type);
            }
            await this.fetchSelectedItems();
            localStorage.setItem("formUpdated", Date.now().toString());
        } catch (error) {
            this.showToast(error.message, "error");
        }
    }

    async addToForm(id, type, quantity) {
        const response = await this.fetchData("/api/requisition/add-item", {
            method: "POST",
            body: JSON.stringify({
                type: type,
                equipment_id: type === "equipment" ? parseInt(id) : undefined,
                facility_id: type === "facility" ? parseInt(id) : undefined,
                quantity: quantity,
            }),
        });

        if (!response?.success)
            throw new Error(response?.message || "Failed to add item");
        this.showToast(`${type} added to form`, "success");
        if (this.config.onItemAdded)
            this.config.onItemAdded(id, type, quantity);
    }

    async removeFromForm(id, type) {
        const response = await this.fetchData("/api/requisition/remove-item", {
            method: "POST",
            body: JSON.stringify({
                type: type,
                equipment_id: type === "equipment" ? parseInt(id) : undefined,
                facility_id: type === "facility" ? parseInt(id) : undefined,
            }),
        });

        if (!response?.success)
            throw new Error(response?.message || "Failed to remove item");
        this.showToast(`${type} removed from form`);
        if (this.config.onItemRemoved) this.config.onItemRemoved(id, type);
    }

    performSearch() {
        this.searchQuery = this.elements.searchInput?.value.trim() || "";
        if (this.elements.clearSearchBtn)
            this.elements.clearSearchBtn.style.display = this.searchQuery
                ? "block"
                : "none";
        this.currentPage = 1;
        this.loadCatalogData();
    }

    clearSearch() {
        if (this.elements.searchInput) this.elements.searchInput.value = "";
        this.searchQuery = "";
        if (this.elements.clearSearchBtn)
            this.elements.clearSearchBtn.style.display = "none";
        this.currentPage = 1;
        this.loadCatalogData();
    }

    showItemDetails(itemId) {
        const item = this.allItems.find((item) => {
            if (this.catalogType === "equipment")
                return item.equipment_id == itemId;
            return item.facility_id == itemId;
        });

        if (!item) return;

        const isEquipment = this.catalogType === "equipment";

        // Redirect based on item type
        if (isEquipment) {
            window.location.href = `/equipment-details/${itemId}`;
        } else {
            window.location.href = `/facility/${itemId}`;
        }
    }

    showFacilityAvailability(button) {
        const itemId = button.dataset.itemId;
        const itemType = button.dataset.itemType;
        const itemName = button.dataset.itemName;
        const itemImage = button.dataset.itemImage;
        const itemCategory = button.dataset.itemCategory;
        const itemStatus = button.dataset.itemStatus;
        const itemStatusColor = button.dataset.itemStatusColor;

        // Prepare facility data for the calendar
        let facilityData = {
            id: itemId,
            name: itemName,
            image: itemImage,
            category: itemCategory,
            status: itemStatus,
            statusColor: itemStatusColor,
            type: itemType,
        };

        // Add additional data based on type
        if (itemType === "equipment") {
            facilityData.totalQuantity = button.dataset.itemAvailableQty;
            facilityData.availableQuantity = button.dataset.itemAvailableQty;
        } else {
            facilityData.capacity = button.dataset.itemCapacity;
            facilityData.fee = button.dataset.itemFee;
            if (button.dataset.itemBuilding) {
                facilityData.building = button.dataset.itemBuilding;
            }
        }

        // Store original button content and show loading state
        const originalHtml = button.innerHTML;
        button.classList.add("btn-loading");
        button.innerHTML =
            '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Loading Calendar...';
        button.disabled = true;

        // Function to remove loading state
        const removeLoadingState = () => {
            button.classList.remove("btn-loading");
            button.innerHTML = originalHtml;
            button.disabled = false;
        };

        // Listen for modal shown event to remove loading state
        const modal = document.getElementById(
            "singleFacilityAvailabilityModal",
        );
        const handleModalShown = () => {
            removeLoadingState();
            modal.removeEventListener("shown.bs.modal", handleModalShown);
        };

        if (modal) {
            modal.addEventListener("shown.bs.modal", handleModalShown);
        }

        // Check if calendar is available and use it
        if (typeof window.showAvailabilityCalendar === "function") {
            window.showAvailabilityCalendar(facilityData);

            // Fallback timeout in case modal doesn't trigger shown event
            setTimeout(() => {
                if (button.classList.contains("btn-loading")) {
                    removeLoadingState();
                    modal?.removeEventListener(
                        "shown.bs.modal",
                        handleModalShown,
                    );
                }
            }, 3000);
        } else {
            // Fallback: dispatch event for calendar to initialize
            console.warn("Calendar not ready, retrying...");

            // Set up a one-time listener for when calendar becomes available
            const calendarReadyHandler = () => {
                if (typeof window.showAvailabilityCalendar === "function") {
                    window.showAvailabilityCalendar(facilityData);
                    document.removeEventListener(
                        "calendarReady",
                        calendarReadyHandler,
                    );
                }
            };

            document.addEventListener("calendarReady", calendarReadyHandler);

            // Dispatch event to trigger calendar initialization
            const event = new CustomEvent("facilityAvailabilityRequested", {
                detail: { facilityData },
            });
            document.dispatchEvent(event);

            // Timeout fallback
            setTimeout(() => {
                document.removeEventListener(
                    "calendarReady",
                    calendarReadyHandler,
                );
                if (button.classList.contains("btn-loading")) {
                    removeLoadingState();
                    modal?.removeEventListener(
                        "shown.bs.modal",
                        handleModalShown,
                    );
                }
                if (typeof window.showToast === "function") {
                    window.showToast(
                        "Calendar is loading, please try again in a moment.",
                        "info",
                        3000,
                    );
                }
            }, 5000);

            // Show a temporary loading message
            if (typeof window.showToast === "function") {
                window.showToast(
                    "Loading availability calendar...",
                    "info",
                    2000,
                );
            }
        }
    }

    // Helper method to escape HTML
    escapeHtml(text) {
        if (!text) return "";
        const div = document.createElement("div");
        div.textContent = text;
        return div.innerHTML;
    }
    getPrimaryImage(item) {
        return (
            item.images?.find((img) => img.image_type === "Primary")
                ?.image_url ||
            "https://res.cloudinary.com/dn98ntlkd/image/upload/v1759850278/t4fyv56wog6pglhwvwtn.png"
        );
    }

    truncateText(text, maxLength) {
        if (!text) return "";
        return text.length > maxLength
            ? text.substring(0, maxLength) + "..."
            : text;
    }

    async fetchData(url, options = {}) {
        let csrfToken = document.querySelector(
            'meta[name="csrf-token"]',
        )?.content;

        const response = await fetch(url, {
            ...options,
            headers: {
                "X-CSRF-TOKEN": csrfToken,
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
                ...(options.headers || {}),
            },
            credentials: "same-origin",
        });

        if (!response.ok)
            throw new Error(`HTTP error! status: ${response.status}`);
        return await response.json();
    }

    /**
     * Show toast notification - uses external toast.js
     */
    showToast(message, type = "success", duration = 3000) {
        console.log("Showing toast:", message, type); // Debug log
        if (typeof window.showToast === "function") {
            window.showToast(message, type, duration);
        } else {
            // Fallback alert if toast.js isn't loaded
            console.warn("Toast function not available, using alert fallback");
            alert(message);
        }
    }

    async loadFormStatuses() {
        try {
            const response = await this.fetchData("/api/form-statuses");
            if (Array.isArray(response)) {
                this.formStatuses = response.reduce((acc, status) => {
                    acc[status.status_name] = status.color_code;
                    return acc;
                }, {});
            }
        } catch (error) {
            console.error("Failed to load form statuses:", error);
        }
    }
}

if (typeof module !== "undefined" && module.exports)
    module.exports = BookingCatalog;

// Ensure calendar is loaded before using it
if (typeof window.showAvailabilityCalendar !== "function") {
    console.log("Waiting for calendar to load...");
    // Wait for calendar to load
    const checkCalendar = setInterval(() => {
        if (typeof window.showAvailabilityCalendar === "function") {
            console.log("Calendar loaded successfully");
            clearInterval(checkCalendar);
        }
    }, 100);

    // Timeout after 5 seconds
    setTimeout(() => {
        clearInterval(checkCalendar);
        if (typeof window.showAvailabilityCalendar !== "function") {
            console.error("Calendar failed to load");
        }
    }, 5000);
}

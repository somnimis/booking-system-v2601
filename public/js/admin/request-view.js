/**
 * Request View Module
 * Handles single requisition form viewing, approvals, fees, and timeline
 */

const RequestView = (function () {
    // Private variables
    let requestId = null;
    let adminToken = null;
    let currentComments = [];
    let currentFees = [];
    let selectedStatus = "";
    let currentTimelineFilter = "all";
    let isTimelineLoaded = false;

    // DOM Elements cache
    let elements = {};

    // Helper: Format money
    function formatMoney(amount) {
        let num = parseFloat(amount);
        if (isNaN(num)) return "₱0.00";
        return (
            "₱" +
            num.toLocaleString("en-PH", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            })
        );
    }

    // Helper: Escape HTML
    function escapeHtml(text) {
        const div = document.createElement("div");
        div.textContent = text;
        return div.innerHTML;
    }

    // Helper: Format time ago
    function formatTimeAgo(timestamp) {
        const now = new Date();
        const commentTime = new Date(timestamp);
        const diffInSeconds = Math.floor((now - commentTime) / 1000);
        if (diffInSeconds < 60) return "just now";
        if (diffInSeconds < 3600) {
            const minutes = Math.floor(diffInSeconds / 60);
            return `${minutes} minute${minutes !== 1 ? "s" : ""} ago`;
        }
        if (diffInSeconds < 86400) {
            const hours = Math.floor(diffInSeconds / 3600);
            return `${hours} hour${hours !== 1 ? "s" : ""} ago`;
        }
        if (diffInSeconds < 604800) {
            const days = Math.floor(diffInSeconds / 86400);
            return `${days} day${days !== 1 ? "s" : ""} ago`;
        }
        return commentTime.toLocaleDateString();
    }

    // Helper: Show toast notification
    function showToast(message, type = "success", duration = 3000) {
        const toast = document.createElement("div");
        toast.className = `toast align-items-center border-0 position-fixed start-0 mb-2`;
        toast.style.zIndex = "1100";
        toast.style.bottom = "0";
        toast.style.left = "0";
        toast.style.margin = "1rem";
        toast.style.opacity = "0";
        toast.style.transform = "translateY(20px)";
        toast.style.transition = "transform 0.4s ease, opacity 0.4s ease";
        toast.style.backgroundColor =
            type === "success" ? "#004183ff" : "#dc3545";
        toast.style.color = "#fff";
        toast.style.borderRadius = "0.3rem";
        toast.innerHTML = `
            <div class="d-flex align-items-center px-3 py-1">
                <i class="bi ${type === "success" ? "bi-check-circle-fill" : "bi-exclamation-circle-fill"} me-2"></i>
                <div class="toast-body flex-grow-1">${message}</div>
                <button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="toast"></button>
            </div>
        `;
        document.body.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast, { autohide: false });
        bsToast.show();
        setTimeout(() => {
            toast.style.opacity = "1";
            toast.style.transform = "translateY(0)";
        }, 10);
        setTimeout(() => {
            toast.style.opacity = "0";
            toast.style.transform = "translateY(20px)";
            setTimeout(() => {
                bsToast.hide();
                toast.remove();
            }, 400);
        }, duration);
    }

    // Format date endorsed
    function formatDateEndorsed(dateString) {
        if (!dateString || dateString === "N/A") return "N/A";
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString("en-US", {
                year: "numeric",
                month: "long",
                day: "numeric",
            });
        } catch {
            return dateString;
        }
    }

    // Format start date/time
    function formatStartDateTime(schedule) {
        const startDate = new Date(
            schedule.start_date + "T" + schedule.start_time,
        );
        return `${startDate.toLocaleDateString("en-US", { year: "numeric", month: "short", day: "numeric" })} | ${startDate.toLocaleTimeString("en-US", { hour: "numeric", minute: "2-digit", hour12: true })}`;
    }

    // Format end date/time
    function formatEndDateTime(schedule) {
        const endDate = new Date(schedule.end_date + "T" + schedule.end_time);
        return `${endDate.toLocaleDateString("en-US", { year: "numeric", month: "short", day: "numeric" })} | ${endDate.toLocaleTimeString("en-US", { hour: "numeric", minute: "2-digit", hour12: true })}`;
    }

    // Mark notification as read
    async function markNotificationAsRead() {
        try {
            await fetch(
                `/api/admin/notifications/requisition/${requestId}/mark-as-read`,
                {
                    method: "POST",
                    headers: {
                        Authorization: `Bearer ${adminToken}`,
                        Accept: "application/json",
                    },
                },
            );
        } catch (error) {
            console.error("Error marking notification as read:", error);
        }
    }

    // Load approval history
    async function loadApprovalHistory() {
        try {
            const response = await fetch(
                `/api/admin/requisition/${requestId}/approval-history`,
                {
                    headers: { Authorization: `Bearer ${adminToken}` },
                },
            );
            if (!response.ok)
                throw new Error("Failed to fetch approval history");
            const approvalHistory = await response.json();

            const approvalsModalBody = document.querySelector(
                "#approvalsModal .modal-body",
            );
            const rejectionsModalBody = document.querySelector(
                "#rejectionsModal .modal-body",
            );

            if (approvalsModalBody) {
                approvalsModalBody.innerHTML = generateApprovalHistoryHTML(
                    approvalHistory.filter(
                        (item) => item.action === "approved",
                    ),
                );
            }
            if (rejectionsModalBody) {
                rejectionsModalBody.innerHTML = generateApprovalHistoryHTML(
                    approvalHistory.filter(
                        (item) => item.action === "rejected",
                    ),
                );
            }
        } catch (error) {
            console.error("Error loading approval history:", error);
        }
    }

    function generateApprovalHistoryHTML(history) {
        if (!history || history.length === 0)
            return '<div class="text-center text-muted py-4">No records found</div>';
        return history
            .map(
                (item) => `
            <div class="d-flex align-items-center mb-3 p-2 border rounded">
                <div class="me-3 flex-shrink-0">
                    ${
                        item.admin_photo
                            ? `<img src="${item.admin_photo}" class="rounded-circle" width="45" height="45">`
                            : `<div class="rounded-circle d-flex align-items-center justify-content-center bg-secondary text-white" style="width: 45px; height: 45px;">
                            ${item.admin_name
                                .split(" ")
                                .map((n) => n.charAt(0))
                                .join("")}
                        </div>`
                    }
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <strong class="d-block">${item.admin_name}</strong>
                            <small class="text-muted">${item.action} this request</small>
                            ${item.remarks ? `<div class="mt-1 small text-muted">"${item.remarks}"</div>` : ""}
                        </div>
                        <small class="text-muted">${item.formatted_date}</small>
                    </div>
                </div>
            </div>
        `,
            )
            .join("");
    }

    // Load mixed activity (comments + fees)
    async function loadMixedActivity() {
        try {
            const commentsResponse = await fetch(
                `/api/admin/requisition/${requestId}/comments`,
                {
                    headers: { Authorization: `Bearer ${adminToken}` },
                },
            );
            const feesResponse = await fetch(
                `/api/admin/requisition/${requestId}/fees`,
                {
                    headers: { Authorization: `Bearer ${adminToken}` },
                },
            );
            const commentsResult = await commentsResponse.json();
            const feesResult = await feesResponse.json();
            currentComments = commentsResult.comments || [];
            currentFees = feesResult || [];
        } catch (error) {
            console.error("Error loading activity:", error);
        }
    }

    // Update document buttons and icons
    function updateDocumentIcons(documents) {
        function updateButton(
            buttonId,
            iconId,
            hasDocument,
            documentUrl,
            title,
        ) {
            const button = document.getElementById(buttonId);
            const icon = document.getElementById(iconId);
            if (button && icon) {
                if (hasDocument) {
                    button.classList.remove("btn-document-null");
                    button.classList.add("btn-primary");
                    button.disabled = false;
                    button.setAttribute("data-document-url", documentUrl);
                    button.setAttribute("data-document-title", title);
                    button.setAttribute("data-bs-toggle", "modal");
                    button.setAttribute("data-bs-target", "#documentModal");
                    icon.classList.remove("text-muted");
                    icon.classList.add("text-primary");
                } else {
                    button.classList.remove("btn-primary");
                    button.classList.add("btn-document-null");
                    button.disabled = true;
                    icon.classList.add("text-muted");
                    icon.classList.remove("text-primary");
                }
            }
        }

        updateButton(
            "formalLetterBtn",
            "formalLetterIcon",
            documents.formal_letter.url,
            documents.formal_letter.url,
            "Formal Letter",
        );
        updateButton(
            "facilityLayoutBtn",
            "facilityLayoutIcon",
            documents.facility_layout.url,
            documents.facility_layout.url,
            "Facility Layout",
        );
        updateButton(
            "proofOfPaymentBtn",
            "proofOfPaymentIcon",
            documents.proof_of_payment.url,
            documents.proof_of_payment.url,
            "Proof of Payment",
        );

        const hasOfficialReceipt =
            documents.official_receipt.url ||
            documents.form_details?.official_receipt_num;
        const receiptUrl =
            documents.official_receipt.url ||
            (documents.form_details?.official_receipt_num
                ? `/official-receipt/${requestId}`
                : null);
        updateButton(
            "officialReceiptBtn",
            "officialReceiptIcon",
            hasOfficialReceipt,
            receiptUrl,
            "Official Receipt",
        );

        if (
            documents.form_details?.official_receipt_num &&
            !documents.official_receipt.url
        ) {
            const button = document.getElementById("officialReceiptBtn");
            if (button) {
                button.onclick = () =>
                    window.open(`/official-receipt/${requestId}`, "_blank");
            }
        }
    }

    // Update base fees display
    function updateBaseFees(requestedItems, schedule) {
        const facilitiesContainer = document.getElementById("facilitiesFees");
        const equipmentContainer = document.getElementById("equipmentFees");
        if (!facilitiesContainer || !equipmentContainer) return;

        facilitiesContainer.innerHTML = "";
        equipmentContainer.innerHTML = "";

        const startDateTime = new Date(
            `${schedule.start_date}T${schedule.start_time}`,
        );
        const endDateTime = new Date(
            `${schedule.end_date}T${schedule.end_time}`,
        );
        const durationHours = Math.max(
            0,
            (endDateTime - startDateTime) / (1000 * 60 * 60),
        );

        if (requestedItems.facilities?.length > 0) {
            requestedItems.facilities.forEach((facility) => {
                const feeAmount = parseFloat(facility.fee);
                let itemTotal, rateDescription;
                if (facility.rate_type === "Per Hour" && durationHours > 0) {
                    itemTotal = feeAmount * durationHours;
                    rateDescription = `${formatMoney(feeAmount)}/hr × ${durationHours.toFixed(1)} hrs`;
                } else {
                    itemTotal = feeAmount;
                    rateDescription = `${formatMoney(feeAmount)}/event`;
                }
                const element = document.createElement("div");
                element.className = `fee-item d-flex justify-content-between align-items-center mb-2 p-2 rounded ${facility.is_waived ? "waived" : ""}`;
                element.innerHTML = `
                    <div class="d-flex align-items-center">
                        <div class="form-check me-2">
                            <input class="form-check-input waiver-checkbox" type="checkbox" data-type="facility" data-id="${facility.requested_facility_id}" ${facility.is_waived ? "checked" : ""}>
                        </div>
                        <span class="item-name">${escapeHtml(facility.name)}</span>
                    </div>
                    <div class="text-end">
                        <small>${rateDescription}</small>
                        <div><strong>${formatMoney(itemTotal)}</strong></div>
                    </div>
                `;
                facilitiesContainer.appendChild(element);
            });
        } else {
            facilitiesContainer.innerHTML =
                '<div class="text-muted small">No facilities requested</div>';
        }

        if (requestedItems.equipment?.length > 0) {
            requestedItems.equipment.forEach((equipment) => {
                const unitFee = parseFloat(equipment.fee);
                const quantity = equipment.quantity || 1;
                let itemTotal, rateDescription;
                if (equipment.rate_type === "Per Hour" && durationHours > 0) {
                    itemTotal = unitFee * durationHours * quantity;
                    rateDescription = `${formatMoney(unitFee)}/hr × ${durationHours.toFixed(1)} hrs × ${quantity}`;
                } else {
                    itemTotal = unitFee * quantity;
                    rateDescription = `${formatMoney(unitFee)}/event × ${quantity}`;
                }
                const element = document.createElement("div");
                element.className = `fee-item d-flex justify-content-between align-items-center mb-2 p-2 rounded ${equipment.is_waived ? "waived" : ""}`;
                element.innerHTML = `
                    <div class="d-flex align-items-center">
                        <div class="form-check me-2">
                            <input class="form-check-input waiver-checkbox" type="checkbox" data-type="equipment" data-id="${equipment.requested_equipment_id}" ${equipment.is_waived ? "checked" : ""}>
                        </div>
                        <span class="item-name">${escapeHtml(equipment.name)} ${quantity > 1 ? `(×${quantity})` : ""}</span>
                    </div>
                    <div class="text-end">
                        <small>${rateDescription}</small>
                        <div><strong>${formatMoney(itemTotal)}</strong></div>
                    </div>
                `;
                equipmentContainer.appendChild(element);
            });
        } else {
            equipmentContainer.innerHTML =
                '<div class="text-muted small">No equipment requested</div>';
        }

        document
            .querySelectorAll("#baseFeesContainer .waiver-checkbox")
            .forEach((checkbox) => {
                const newCheckbox = checkbox.cloneNode(true);
                checkbox.parentNode.replaceChild(newCheckbox, checkbox);
                newCheckbox.addEventListener("change", () =>
                    handleWaiverChange(newCheckbox),
                );
            });
    }

    // Update additional fees display
    function updateAdditionalFees(requisitionFees) {
        const container = document.getElementById("additionalFeesContainer");
        if (!container) return;
        container.innerHTML = "";

        if (requisitionFees?.length > 0) {
            requisitionFees.forEach((fee) => {
                let amountText = "";
                let isDiscount = false;
                if (fee.type === "fee") {
                    amountText = formatMoney(fee.fee_amount);
                } else if (fee.type === "discount") {
                    isDiscount = true;
                    amountText =
                        fee.discount_type === "Percentage"
                            ? `-${parseFloat(fee.discount_amount)}%`
                            : `-${formatMoney(fee.discount_amount)}`;
                } else if (fee.type === "mixed") {
                    const feePart =
                        fee.fee_amount > 0 ? formatMoney(fee.fee_amount) : "";
                    const discountPart =
                        fee.discount_amount > 0
                            ? fee.discount_type === "Percentage"
                                ? `-${parseFloat(fee.discount_amount)}%`
                                : `-${formatMoney(fee.discount_amount)}`
                            : "";
                    amountText = `${feePart} ${discountPart}`.trim();
                }

                let labelHtml = escapeHtml(fee.label);
                if (fee.account_num)
                    labelHtml += ` <span class="text-muted small">(${escapeHtml(fee.account_num)})</span>`;

                const element = document.createElement("div");
                element.className =
                    "fee-item d-flex justify-content-between align-items-center mb-2 p-2 rounded";
                element.innerHTML = `
                    <div><span class="item-name ${isDiscount ? "text-danger" : ""}">${labelHtml}</span></div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="item-price fw-bold ${isDiscount ? "text-danger" : ""}">${amountText}</span>
                        <button class="btn btn-sm btn-danger delete-fee-btn" data-fee-id="${fee.fee_id}"><i class="fa fa-times"></i></button>
                    </div>
                `;
                container.appendChild(element);
            });

            container.querySelectorAll(".delete-fee-btn").forEach((btn) => {
                btn.addEventListener("click", async (e) => {
                    const feeId = e.currentTarget.dataset.feeId;
                    if (!feeId) return;
                    try {
                        const response = await fetch(
                            `/api/admin/requisition/${requestId}/fee/${feeId}`,
                            {
                                method: "DELETE",
                                headers: {
                                    Authorization: `Bearer ${adminToken}`,
                                },
                            },
                        );
                        if (!response.ok)
                            throw new Error("Failed to delete fee");
                        showToast("Fee removed successfully", "success");
                        await fetchRequestDetails();
                    } catch (error) {
                        console.error("Error removing fee:", error);
                        showToast("Failed to remove fee", "error");
                    }
                });
            });
        } else {
            container.innerHTML =
                '<div class="text-center text-muted py-4"><i class="fa fa-coins fa-2x d-block mb-2"></i><p class="mb-0">No additional fees or discounts</p></div>';
        }
    }

    // Handle waiver change
    async function handleWaiverChange(checkbox) {
        const type = checkbox.dataset.type;
        const id = parseInt(checkbox.dataset.id);
        const isWaived = checkbox.checked;

        const itemRow = checkbox.closest(".fee-item");
        if (itemRow) itemRow.classList.toggle("waived", isWaived);

        const waivedFacilities = [];
        const waivedEquipment = [];
        document.querySelectorAll(".waiver-checkbox").forEach((cb) => {
            if (cb.checked) {
                if (cb.dataset.type === "facility")
                    waivedFacilities.push(parseInt(cb.dataset.id));
                else if (cb.dataset.type === "equipment")
                    waivedEquipment.push(parseInt(cb.dataset.id));
            }
        });

        try {
            const response = await fetch(
                `/api/admin/requisition/${requestId}/waive`,
                {
                    method: "POST",
                    headers: {
                        Authorization: `Bearer ${adminToken}`,
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({
                        waived_facilities: waivedFacilities,
                        waived_equipment: waivedEquipment,
                    }),
                },
            );
            if (!response.ok) throw new Error("Failed to update waiver");
            await fetchRequestDetails();
        } catch (error) {
            checkbox.checked = !isWaived;
            if (itemRow) itemRow.classList.toggle("waived");
            showToast("Failed to update waiver: " + error.message, "error");
        }
    }

    // Handle waive all
    async function handleWaiveAll(switchElement) {
        const waiveAll = switchElement.checked;
        try {
            const response = await fetch(
                `/api/admin/requisition/${requestId}/waive`,
                {
                    method: "POST",
                    headers: {
                        Authorization: `Bearer ${adminToken}`,
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({ waive_all: waiveAll }),
                },
            );
            if (!response.ok) throw new Error("Failed to update waive all");
            await fetchRequestDetails();
            showToast(
                waiveAll
                    ? "All items waived successfully"
                    : "All waivers removed",
                "success",
            );
        } catch (error) {
            switchElement.checked = !waiveAll;
            showToast("Failed to update waive all: " + error.message, "error");
        }
    }

    // Refresh all fee displays
    async function refreshAllFeeDisplays() {
        try {
            const response = await fetch(
                `/api/admin/requisition-forms/${requestId}`,
                {
                    headers: { Authorization: `Bearer ${adminToken}` },
                },
            );
            if (!response.ok)
                throw new Error("Failed to fetch updated request data");
            const requestData = await response.json();

            const feeTotalElement =
                document.getElementById("feeBreakdownTotal");
            if (feeTotalElement)
                feeTotalElement.textContent = formatMoney(
                    requestData.fees?.approved_fee || 0,
                );

            if (requestData.fees?.requisition_fees)
                updateAdditionalFees(requestData.fees.requisition_fees);
            if (requestData.requested_items && requestData.schedule)
                updateBaseFees(
                    requestData.requested_items,
                    requestData.schedule,
                );

            document.getElementById("approvalsCount").textContent =
                requestData.approval_info?.approval_count || 0;
            document.getElementById("rejectionsCount").textContent =
                requestData.approval_info?.rejection_count || 0;

            const statusBadge = document.getElementById("statusBadge");
            if (statusBadge && requestData.form_details?.status) {
                statusBadge.textContent = requestData.form_details.status.name;
                statusBadge.style.backgroundColor =
                    requestData.form_details.status.color;
            }
        } catch (error) {
            console.error("Error refreshing fee displays:", error);
        }
    }

    // Main fetch request details
    async function fetchRequestDetails() {
        try {
            document.getElementById("loadingState").style.display = "block";
            document.getElementById("contentState").style.display = "none";

            const response = await fetch(
                `/api/admin/requisition-forms/${requestId}`,
                {
                    headers: { Authorization: `Bearer ${adminToken}` },
                },
            );
            if (!response.ok)
                throw new Error("Failed to fetch request details");
            const request = await response.json();

            document.getElementById("requestIdTitle").textContent =
                "RID #" + String(requestId).padStart(4, "0");
            const statusBadge = document.getElementById("statusBadge");
            statusBadge.textContent = request.form_details.status.name;
            statusBadge.style.backgroundColor =
                request.form_details.status.color;

            document.getElementById("formDetails").innerHTML = `
                <div class="d-flex justify-content-between py-1 border-bottom"><span class="text-muted">Requester:</span><span class="fw-medium">${request.user_details.first_name} ${request.user_details.last_name}</span></div>
                <div class="d-flex justify-content-between py-1 border-bottom"><span class="text-muted">School ID:</span><span class="fw-medium">${request.user_details.school_id || "N/A"}</span></div>
                <div class="d-flex justify-content-between py-1 border-bottom"><span class="text-muted">Email:</span><span class="fw-medium">${request.user_details.email}</span></div>
                <div class="d-flex justify-content-between py-1 border-bottom"><span class="text-muted">Organization:</span><span class="fw-medium">${request.user_details.organization_name || "N/A"}</span></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Contact #:</span><span class="fw-medium">${request.user_details.contact_number || "N/A"}</span></div>
            `;

            document.getElementById("eventDetails").innerHTML = `
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="mb-3"><div class="fw-bold text-primary">Rental Purpose</div><div>${request.form_details.purpose}</div></div>
                        <div class="mb-3"><div class="fw-bold text-primary">Number of Tables</div><div>${request.form_details.num_tables || 0}</div></div>
                        <div class="mb-3"><div class="fw-bold text-primary">Start Schedule</div><div>${formatStartDateTime(request.schedule)}</div></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="mb-3"><div class="fw-bold text-primary">Participants</div><div>${request.form_details.num_participants}</div></div>
                        <div class="mb-3"><div class="fw-bold text-primary">Number of Chairs</div><div>${request.form_details.num_chairs || 0}</div></div>
                        <div class="mb-3"><div class="fw-bold text-primary">End Schedule</div><div>${formatEndDateTime(request.schedule)}</div></div>
                    </div>
                    <div class="col-12"><div class="fw-bold text-primary">Additional Requests</div><div>${request.form_details.additional_requests || "No additional requests."}</div></div>
                </div>
            `;

            document.getElementById("requestedItems").innerHTML = `
                <div class="requested-items-list">
                    ${
                        request.requested_items.facilities.length > 0
                            ? `
                        <div class="mb-3"><h6 class="fw-bold mb-2">Facilities:</h6>
                        ${request.requested_items.facilities.map((f) => `<div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded"><span>${f.name}</span><span class="fw-bold">${formatMoney(f.fee)}${f.rate_type === "Per Hour" ? "/hour" : "/event"}</span></div>`).join("")}
                        </div>
                    `
                            : ""
                    }
                    ${
                        request.requested_items.equipment.length > 0
                            ? `
                        <div><h6 class="fw-bold mb-2">Equipment:</h6>
                        ${request.requested_items.equipment.map((e) => `<div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded"><span>${e.name} × ${e.quantity || 1}</span><span class="fw-bold">${formatMoney(e.fee)}${e.rate_type === "Per Hour" ? "/hour" : "/event"}</span></div>`).join("")}
                        </div>
                    `
                            : ""
                    }
                </div>
            `;

            document.getElementById("approvalsCount").textContent =
                request.approval_info?.approval_count || 0;
            document.getElementById("rejectionsCount").textContent =
                request.approval_info?.rejection_count || 0;
            updateDocumentIcons(request.documents);

            await loadMixedActivity();
            await loadApprovalHistory();

            if (request.fees?.approved_fee !== undefined) {
                const feeTotalElement =
                    document.getElementById("feeBreakdownTotal");
                if (feeTotalElement)
                    feeTotalElement.textContent = formatMoney(
                        request.fees.approved_fee,
                    );
                if (request.requested_items && request.schedule)
                    updateBaseFees(request.requested_items, request.schedule);
                if (request.fees.requisition_fees)
                    updateAdditionalFees(request.fees.requisition_fees);
            }

            await checkAdminRoleAndUpdateUI();

            document.getElementById("loadingState").style.display = "none";
            document.getElementById("contentState").style.display = "block";
        } catch (error) {
            console.error("Error:", error);
            showToast("Failed to load request details", "error");
            document.getElementById("loadingState").style.display = "none";
            document.getElementById("contentState").innerHTML =
                `<div class="alert alert-danger">Failed to load request details. ${error.message}</div>`;
            document.getElementById("contentState").style.display = "block";
        }
    }

    // Check admin role and update UI
    async function checkAdminRoleAndUpdateUI() {
        try {
            // Get admin data
            const adminResponse = await fetch("/api/admin/profile", {
                headers: { Authorization: `Bearer ${adminToken}` },
            });
            if (!adminResponse.ok) return;
            const adminData = await adminResponse.json();

            // Get request data
            const requestResponse = await fetch(
                `/api/admin/requisition-forms/${requestId}`,
                {
                    headers: { Authorization: `Bearer ${adminToken}` },
                },
            );
            const request = await requestResponse.json();

            const statusId = request.form_details.status.id;
            const roleId = adminData.role_id;
            const isHeadAdmin = roleId === 1;
            const isApprovingOfficer =
                roleId === 2 || roleId === 3 || roleId === 5;

            // Check if admin already acted
            const hasActed = await checkIfAdminHasTakenAction(
                adminData.admin_id,
            );

            // Clear existing dynamic buttons
            document.getElementById("primaryActionContainer").innerHTML = "";
            document.getElementById("secondaryActionsContainer").innerHTML = "";
            document.getElementById("dangerZoneContainer").innerHTML = "";

            // =============================================
            // 1. PRIMARY ACTION (Main button)
            // =============================================
            const primaryAction = getPrimaryAction(
                statusId,
                isHeadAdmin,
                isApprovingOfficer,
                hasActed,
            );
            if (primaryAction) {
                document.getElementById("primaryActionContainer").innerHTML =
                    primaryAction.html;
                // Attach event listener
                const btn = document.getElementById(primaryAction.id);
                if (btn && primaryAction.handler) {
                    btn.addEventListener("click", primaryAction.handler);
                }
            }

            // =============================================
            // 2. SECONDARY ACTIONS (Status-specific)
            // =============================================
            const secondaryActions = getSecondaryActions(statusId, isHeadAdmin);
            if (secondaryActions.length > 0) {
                document.getElementById("secondaryActionsContainer").innerHTML =
                    `
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle w-100" 
                            data-bs-toggle="dropdown">
                        <i class="bi bi-three-dots-vertical"></i> More Actions
                    </button>
                    <ul class="dropdown-menu w-100">
                        ${secondaryActions
                            .map(
                                (action) => `
                            <li>
                                <a class="dropdown-item" href="#" data-action="${action.value}">
                                    <i class="bi ${action.icon} me-2"></i> ${action.label}
                                </a>
                            </li>
                        `,
                            )
                            .join("")}
                    </ul>
                </div>
            `;

                // Attach dropdown handlers
                document
                    .querySelectorAll(
                        "#secondaryActionsContainer .dropdown-item[data-action]",
                    )
                    .forEach((item) => {
                        item.addEventListener("click", (e) => {
                            e.preventDefault();
                            const action = item.dataset.action;
                            handleSecondaryAction(action);
                        });
                    });
            }

            // =============================================
            // 3. DANGER ZONE (Close/Cancel)
            // =============================================
            if (isHeadAdmin && !isTerminalStatus(statusId)) {
                document.getElementById("dangerZoneContainer").innerHTML = `
                <button class="btn btn-outline-danger btn-sm w-100" id="closeFormBtn">
                    <i class="bi bi-x-circle me-1"></i> Close Form
                </button>
            `;
                document
                    .getElementById("closeFormBtn")
                    .addEventListener("click", () => {
                        new bootstrap.Modal(
                            document.getElementById("closeFormModal"),
                        ).show();
                    });
            }
        } catch (error) {
            console.error("Error updating UI:", error);
        }
    }

    // =============================================
    // HELPER: Get Primary Action
    // =============================================
    function getPrimaryAction(
        statusId,
        isHeadAdmin,
        isApprovingOfficer,
        hasActed,
    ) {
        // Terminal statuses - no actions
        if ([7, 8, 9].includes(statusId)) {
            return {
                id: "noAction",
                html: `<div class="text-center text-muted py-2">
                      <i class="bi bi-check-circle fs-4 d-block mb-1"></i>
                      <small>Request is ${getStatusName(statusId)}</small>
                   </div>`,
            };
        }

        // Head Admin Actions
        if (isHeadAdmin) {
            // Status 1-2: Finalize
            if ([1, 2].includes(statusId)) {
                return {
                    id: "finalizeBtn",
                    html: `<button class="btn btn-primary w-100" id="finalizeBtn">
                          <i class="bi bi-check-circle me-1"></i> Finalize Request
                       </button>`,
                    handler: () =>
                        new bootstrap.Modal(
                            document.getElementById("finalizeModal"),
                        ).show(),
                };
            }

            // Status 3: Mark Scheduled
            if (statusId === 3) {
                return {
                    id: "markScheduledBtn",
                    html: `<button class="btn btn-success w-100" id="markScheduledBtn">
                          <i class="bi bi-calendar-check me-1"></i> Mark as Scheduled
                       </button>`,
                    handler: () =>
                        new bootstrap.Modal(
                            document.getElementById("markScheduledModal"),
                        ).show(),
                };
            }

            // Status 4: Mark Ongoing
            if (statusId === 4) {
                return {
                    id: "markOngoingBtn",
                    html: `<button class="btn btn-primary w-100" id="markOngoingBtn">
                          <i class="bi bi-play-circle me-1"></i> Mark as Ongoing
                       </button>`,
                    handler: () => handleStatusAction("Ongoing"),
                };
            }

            // Status 5: Mark Overdue
            if (statusId === 5) {
                return {
                    id: "markOverdueBtn",
                    html: `<button class="btn btn-warning w-100" id="markOverdueBtn">
                          <i class="bi bi-exclamation-triangle me-1"></i> Mark as Overdue
                       </button>`,
                    handler: () => handleStatusAction("Late"),
                };
            }

            // Status 6: Add Penalty
            if (statusId === 6) {
                return {
                    id: "penaltyBtn",
                    html: `<button class="btn btn-danger w-100" id="penaltyBtn">
                          <i class="bi bi-cash me-1"></i> Add Late Penalty
                       </button>`,
                    handler: () => {
                        const amount = prompt("Enter late penalty amount (₱):");
                        if (amount && !isNaN(parseFloat(amount))) {
                            addLatePenalty(parseFloat(amount));
                        }
                    },
                };
            }
        }

        // Approving Officer Actions
        if (isApprovingOfficer && !hasActed && [1, 2].includes(statusId)) {
            return {
                id: "approveReject",
                html: `
                <div class="d-flex gap-2">
                    <button class="btn btn-success flex-fill" id="approveBtn">
                        <i class="bi bi-check-lg"></i> Approve
                    </button>
                    <button class="btn btn-danger flex-fill" id="rejectBtn">
                        <i class="bi bi-x-lg"></i> Reject
                    </button>
                </div>
            `,
                handler: null, // Handled separately
            };
        }

        // No action available
        return null;
    }

    // =============================================
    // HELPER: Get Secondary Actions
    // =============================================
    function getSecondaryActions(statusId, isHeadAdmin) {
        const actions = [];

        if (!isHeadAdmin) return actions;

        // Add status-specific secondary actions
        switch (statusId) {
            case 4: // Scheduled
                actions.push({
                    label: "Mark as Completed",
                    value: "completed",
                    icon: "bi-check-circle",
                });
                break;
            case 5: // Ongoing
                actions.push({
                    label: "Mark as Returned",
                    value: "returned",
                    icon: "bi-arrow-return-left",
                });
                break;
            case 6: // Overdue
                actions.push({
                    label: "Mark as Returned (Late)",
                    value: "late_return",
                    icon: "bi-clock-history",
                });
                break;
        }

        // Always add comment option
        actions.push({
            label: "Add Comment",
            value: "comment",
            icon: "bi-chat",
        });

        return actions;
    }

    // =============================================
    // HELPER: Handle Secondary Actions
    // =============================================
    function handleSecondaryAction(action) {
        switch (action) {
            case "completed":
                if (confirm("Mark this request as Completed?")) {
                    updateStatus("Completed");
                }
                break;
            case "returned":
                if (confirm("Mark equipment as returned?")) {
                    updateStatus("Returned");
                }
                break;
            case "late_return":
                const penalty = prompt("Enter late penalty amount (₱):");
                if (penalty !== null) {
                    updateStatus("Late Return", parseFloat(penalty) || 0);
                }
                break;
            case "comment":
                document.getElementById("timelineComment").focus();
                break;
        }
    }

    // =============================================
    // HELPER: Update Status
    // =============================================
    async function updateStatus(statusName, penaltyFee = null) {
        try {
            const data = { status_name: statusName };
            if (penaltyFee !== null) {
                data.late_penalty_fee = penaltyFee;
            }

            const response = await fetch(
                `/api/admin/requisition/${requestId}/update-status`,
                {
                    method: "POST",
                    headers: {
                        Authorization: `Bearer ${adminToken}`,
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify(data),
                },
            );

            if (!response.ok) throw new Error("Failed to update status");

            showToast(`Status updated to ${statusName}!`, "success");
            setTimeout(() => location.reload(), 1000);
        } catch (error) {
            showToast("Error: " + error.message, "error");
        }
    }

    // =============================================
    // HELPER: Check Terminal Status
    // =============================================
    function isTerminalStatus(statusId) {
        return [7, 8, 9].includes(statusId);
    }

    // =============================================
    // HELPER: Get Status Name
    // =============================================
    function getStatusName(statusId) {
        const names = {
            1: "Pencil Booked",
            2: "Pending Approval",
            3: "Awaiting Payment",
            4: "Scheduled",
            5: "Ongoing",
            6: "Overdue",
            7: "Completed",
            8: "Rejected",
            9: "Cancelled",
        };
        return names[statusId] || "Unknown";
    }

    async function checkIfAdminHasTakenAction(adminId) {
        try {
            const response = await fetch(
                `/api/admin/requisition/${requestId}/approval-history`,
                {
                    headers: { Authorization: `Bearer ${adminToken}` },
                },
            );
            if (!response.ok) return false;
            const approvalHistory = await response.json();
            return approvalHistory.some(
                (record) => record.admin_id === adminId,
            );
        } catch {
            return false;
        }
    }

    async function addLatePenalty(penaltyAmount) {
        try {
            const response = await fetch(
                `/api/admin/requisition/${requestId}/late-penalty`,
                {
                    method: "POST",
                    headers: {
                        Authorization: `Bearer ${adminToken}`,
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({ penalty_amount: penaltyAmount }),
                },
            );
            if (!response.ok) throw new Error("Failed to add late penalty");
            showToast("Late penalty added successfully!", "success");
            fetchRequestDetails();
        } catch (error) {
            showToast("Error: " + error.message, "error");
        }
    }

    function showStatusUpdateModal(status) {
        const modalContent = document.getElementById("statusModalContent");

        let penaltyHtml = "";
        if (status === "Late" || status === "Late Return") {
            penaltyHtml = `
            <div class="mt-3">
                <label for="latePenaltyAmount" class="form-label">Late Penalty Amount</label>
                <input type="number" class="form-control" id="latePenaltyAmount" 
                       placeholder="Enter amount" step="0.01" min="0">
                <small class="text-muted">Leave empty for no penalty</small>
            </div>
        `;
        }

        modalContent.innerHTML = `
        <div class="text-center">
            <i class="bi bi-exclamation-triangle fs-1 text-warning mb-3 d-block"></i>
            <p class="fw-bold">Change status to: <span class="text-primary">${status}</span></p>
            <p class="text-muted small">This action will update the request status. 
               ${status === "Completed" ? "This will close the form." : ""}</p>
            ${penaltyHtml}
        </div>
    `;

        selectedStatus = status;
        new bootstrap.Modal(
            document.getElementById("statusUpdateModal"),
        ).show();
    }

    function handleStatusAction(action) {
        if (action === "Scheduled") {
            new bootstrap.Modal(
                document.getElementById("markScheduledModal"),
            ).show();
        } else {
            showStatusUpdateModal(action);
        }
    }

    // Timeline functions
    async function loadTimelineContent() {
        const timelineContent = document.getElementById("timelineContent");
        timelineContent.innerHTML =
            '<div class="text-center text-muted py-4"><div class="spinner-border spinner-border-sm text-primary mb-2"></div><p class="small mb-0">Loading activity...</p></div>';

        try {
            let activities = [];
            if (
                currentTimelineFilter === "all" ||
                currentTimelineFilter === "comment"
            ) {
                const commentsResponse = await fetch(
                    `/api/admin/requisition/${requestId}/comments`,
                    {
                        headers: { Authorization: `Bearer ${adminToken}` },
                    },
                );
                const commentsResult = await commentsResponse.json();
                (commentsResult.comments || []).forEach((comment) => {
                    activities.push({
                        type: "comment",
                        data: comment,
                        timestamp: new Date(comment.created_at),
                    });
                });
            }
            if (
                currentTimelineFilter === "all" ||
                currentTimelineFilter === "fee"
            ) {
                const feesResponse = await fetch(
                    `/api/admin/requisition/${requestId}/fees`,
                    {
                        headers: { Authorization: `Bearer ${adminToken}` },
                    },
                );
                const feesResult = await feesResponse.json();
                (feesResult || []).forEach((fee) => {
                    activities.push({
                        type: "fee",
                        data: fee,
                        timestamp: new Date(fee.created_at),
                    });
                });
            }

            activities.sort((a, b) => b.timestamp - a.timestamp);

            if (activities.length === 0) {
                timelineContent.innerHTML =
                    '<div class="text-center text-muted py-4"><i class="fas fa-comment-slash fa-2x mb-2"></i><p class="small mb-0">No activity yet</p></div>';
            } else {
                timelineContent.innerHTML = activities
                    .map((activity) => {
                        if (activity.type === "comment")
                            return generateTimelineCommentHTML(activity.data);
                        return generateTimelineFeeHTML(activity.data);
                    })
                    .join("");
            }
            isTimelineLoaded = true;
        } catch (error) {
            console.error("Error loading timeline:", error);
            timelineContent.innerHTML =
                '<div class="text-center text-danger py-4"><i class="fas fa-exclamation-triangle fa-2x mb-2"></i><p class="small mb-0">Failed to load activity</p></div>';
        }
    }

    function generateTimelineCommentHTML(comment) {
        return `
            <div class="timeline-item mb-3">
                <div class="d-flex align-items-start">
                    <div class="me-2 flex-shrink-0">
                        ${
                            comment.admin.photo_url
                                ? `<img src="${comment.admin.photo_url}" class="rounded-circle" width="32" height="32">`
                                : `<div class="rounded-circle d-flex align-items-center justify-content-center bg-secondary text-white" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                ${comment.admin.first_name.charAt(0)}${comment.admin.last_name.charAt(0)}
                            </div>`
                        }
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong class="small">${comment.admin.first_name} ${comment.admin.last_name}</strong>
                            <small class="text-muted">${formatTimeAgo(comment.created_at)}</small>
                        </div>
                        <div class="bg-light p-2 rounded small">${escapeHtml(comment.comment)}</div>
                    </div>
                </div>
            </div>
        `;
    }

    function generateTimelineFeeHTML(fee) {
        const amount = parseFloat(
            fee.type === "discount" ? fee.discount_amount : fee.fee_amount,
        );
        const typeName =
            fee.type === "discount" ? "Discount" : "Additional fee";
        const adminName = fee.added_by?.name || "Admin";
        let amountDisplay =
            fee.type === "discount"
                ? fee.discount_type === "Percentage"
                    ? `${parseFloat(amount)}%`
                    : `-${formatMoney(amount)}`
                : formatMoney(amount);
        return `
            <div class="timeline-item mb-3">
                <div class="d-flex align-items-start">
                    <div class="me-2 flex-shrink-0">
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background-color: #d4edda; color: #28a745;">
                            <i class="fas fa-money-bill" style="font-size: 0.9rem;"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong class="small">${adminName}</strong>
                            <small class="text-muted">${formatTimeAgo(fee.created_at)}</small>
                        </div>
                        <div class="bg-light p-2 rounded small">added ${typeName} - ${fee.label}: ${amountDisplay}</div>
                    </div>
                </div>
            </div>
        `;
    }

    async function sendComment() {
        const commentText = document
            .getElementById("timelineComment")
            .value.trim();
        if (!commentText) {
            showToast("Please enter a comment", "error");
            return;
        }
        try {
            const response = await fetch(
                `/api/admin/requisition/${requestId}/comment`,
                {
                    method: "POST",
                    headers: {
                        Authorization: `Bearer ${adminToken}`,
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({ comment: commentText }),
                },
            );
            const result = await response.json();
            if (!response.ok)
                throw new Error(result.message || "Failed to add comment");
            if (result.success) {
                document.getElementById("timelineComment").value = "";
                isTimelineLoaded = false;
                loadTimelineContent();
                showToast("Comment added successfully", "success");
            }
        } catch (error) {
            showToast("Failed to add comment: " + error.message, "error");
        }
    }

    // Setup event listeners
    function setupEventListeners() {
        document
            .getElementById("approveBtn")
            .addEventListener("click", () =>
                new bootstrap.Modal(
                    document.getElementById("approveModal"),
                ).show(),
            );
        document
            .getElementById("rejectBtn")
            .addEventListener("click", () =>
                new bootstrap.Modal(
                    document.getElementById("rejectModal"),
                ).show(),
            );
        document
            .getElementById("finalizeBtn")
            .addEventListener("click", () =>
                new bootstrap.Modal(
                    document.getElementById("finalizeModal"),
                ).show(),
            );
        document
            .getElementById("closeForm")
            .addEventListener("click", () =>
                new bootstrap.Modal(
                    document.getElementById("closeFormModal"),
                ).show(),
            );
        document
            .getElementById("addFeeBtn")
            .addEventListener("click", () =>
                new bootstrap.Modal(document.getElementById("feeModal")).show(),
            );
        document
            .getElementById("waiveAllSwitch")
            .addEventListener("change", (e) => handleWaiveAll(e.target));

        document.querySelectorAll(".status-option").forEach((option) => {
            option.addEventListener("click", (e) => {
                e.preventDefault();
                handleStatusAction(option.dataset.value);
            });
        });

        // =============================================
        // APPROVE HANDLER
        // =============================================
        document
            .getElementById("confirmApprove")
            .addEventListener("click", async () => {
                const remarks = document.getElementById("approveRemarks").value;
                const btn = document.getElementById("confirmApprove");

                btn.disabled = true;
                btn.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-1"></span> Processing...';

                try {
                    const response = await fetch(
                        `/api/admin/requisition/${requestId}/approve`,
                        {
                            method: "POST",
                            headers: {
                                Authorization: `Bearer ${adminToken}`,
                                "Content-Type": "application/json",
                                Accept: "application/json",
                            },
                            body: JSON.stringify({
                                remarks: remarks || null,
                            }),
                        },
                    );

                    const result = await response.json();

                    if (!response.ok) {
                        throw new Error(
                            result.error ||
                                result.message ||
                                "Failed to approve",
                        );
                    }

                    showToast(
                        result.message || "Request approved successfully!",
                        "success",
                    );

                    // Close modal
                    const modal = bootstrap.Modal.getInstance(
                        document.getElementById("approveModal"),
                    );
                    if (modal) modal.hide();

                    // Refresh the page after a moment
                    setTimeout(() => location.reload(), 1000);
                } catch (error) {
                    showToast("Error: " + error.message, "error");
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = "Confirm Approval";
                }
            });

        // =============================================
        // REJECT HANDLER
        // =============================================
        document
            .getElementById("confirmReject")
            .addEventListener("click", async () => {
                const remarks = document.getElementById("rejectRemarks").value;
                const btn = document.getElementById("confirmReject");

                btn.disabled = true;
                btn.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-1"></span> Processing...';

                try {
                    const response = await fetch(
                        `/api/admin/requisition/${requestId}/reject`,
                        {
                            method: "POST",
                            headers: {
                                Authorization: `Bearer ${adminToken}`,
                                "Content-Type": "application/json",
                                Accept: "application/json",
                            },
                            body: JSON.stringify({
                                remarks: remarks || null,
                            }),
                        },
                    );

                    const result = await response.json();

                    if (!response.ok) {
                        throw new Error(
                            result.error ||
                                result.message ||
                                "Failed to reject",
                        );
                    }

                    showToast(
                        result.message || "Request rejected successfully!",
                        "success",
                    );

                    // Close modal
                    const modal = bootstrap.Modal.getInstance(
                        document.getElementById("rejectModal"),
                    );
                    if (modal) modal.hide();

                    // Redirect to manage requests page
                    setTimeout(() => {
                        window.location.href = "/admin/manage-requests";
                    }, 1500);
                } catch (error) {
                    showToast("Error: " + error.message, "error");
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = "Confirm Rejection";
                }
            });

        document
            .getElementById("confirmFinalize")
            .addEventListener("click", async () => {
                try {
                    const response = await fetch(
                        `/api/admin/requisition/${requestId}/finalize`,
                        {
                            method: "POST",
                            headers: {
                                Authorization: `Bearer ${adminToken}`,
                                "Content-Type": "application/json",
                            },
                            body: JSON.stringify({}),
                        },
                    );
                    if (!response.ok) throw new Error("Failed to finalize");
                    showToast("Form finalized successfully!", "success");
                    bootstrap.Modal.getInstance(
                        document.getElementById("finalizeModal"),
                    ).hide();
                    setTimeout(() => location.reload(), 1500);
                } catch (error) {
                    showToast("Error: " + error.message, "error");
                }
            });

        document
            .getElementById("confirmMarkScheduled")
            .addEventListener("click", async () => {
                const officialReceiptNum = document
                    .getElementById("officialReceiptNum")
                    .value.trim();
                if (!officialReceiptNum) {
                    showToast(
                        "Please enter an official receipt number",
                        "error",
                    );
                    return;
                }
                try {
                    const response = await fetch(
                        `/api/admin/requisition/${requestId}/mark-scheduled`,
                        {
                            method: "POST",
                            headers: {
                                Authorization: `Bearer ${adminToken}`,
                                "Content-Type": "application/json",
                            },
                            body: JSON.stringify({
                                official_receipt_num: officialReceiptNum,
                            }),
                        },
                    );
                    if (!response.ok)
                        throw new Error("Failed to mark as scheduled");
                    showToast(
                        "Request marked as scheduled successfully!",
                        "success",
                    );
                    bootstrap.Modal.getInstance(
                        document.getElementById("markScheduledModal"),
                    ).hide();
                    setTimeout(() => location.reload(), 1500);
                } catch (error) {
                    showToast("Error: " + error.message, "error");
                }
            });

        document
            .getElementById("confirmCloseForm")
            .addEventListener("click", async () => {
                try {
                    const response = await fetch(
                        `/api/admin/requisition/${requestId}/close`,
                        {
                            method: "POST",
                            headers: { Authorization: `Bearer ${adminToken}` },
                        },
                    );
                    if (!response.ok) throw new Error("Failed to close form");
                    showToast("Form closed successfully!", "success");
                    bootstrap.Modal.getInstance(
                        document.getElementById("closeFormModal"),
                    ).hide();
                    setTimeout(
                        () => (window.location.href = "/admin/calendar"),
                        1500,
                    );
                } catch (error) {
                    showToast("Error: " + error.message, "error");
                }
            });

        document
            .getElementById("confirmStatusUpdate")
            .addEventListener("click", async () => {
                if (!selectedStatus) return;

                const btn = this;
                btn.disabled = true;
                btn.innerHTML =
                    '<span class="spinner-border spinner-border-sm"></span> Updating...';

                try {
                    const requestData = { status_name: selectedStatus };

                    // Check for penalty amount
                    const penaltyInput =
                        document.getElementById("latePenaltyAmount");
                    if (penaltyInput && parseFloat(penaltyInput.value) > 0) {
                        requestData.late_penalty_fee = parseFloat(
                            penaltyInput.value,
                        );
                    }

                    const response = await fetch(
                        `/api/admin/requisition/${requestId}/update-status`,
                        {
                            method: "POST",
                            headers: {
                                Authorization: `Bearer ${adminToken}`,
                                "Content-Type": "application/json",
                            },
                            body: JSON.stringify(requestData),
                        },
                    );

                    if (!response.ok) {
                        const error = await response.json();
                        throw new Error(
                            error.message || "Failed to update status",
                        );
                    }

                    showToast(
                        `Status updated to ${selectedStatus}!`,
                        "success",
                    );
                    bootstrap.Modal.getInstance(
                        document.getElementById("statusUpdateModal"),
                    ).hide();
                    setTimeout(() => location.reload(), 1000);
                } catch (error) {
                    showToast("Error: " + error.message, "error");
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = "Confirm Change";
                }
            });
        document
            .getElementById("saveFeeBtn")
            .addEventListener("click", async () => {
                const type = document.getElementById("feeType").value;
                const value = parseFloat(
                    document.getElementById("feeValue").value,
                );
                const label = document.getElementById("feeLabel").value;
                const discountType =
                    document.getElementById("discountType").value;
                const accountNum = document
                    .getElementById("accountNum")
                    .value.trim();

                if (!type || !value || !label) {
                    showToast("Please fill all required fields.", "error");
                    return;
                }

                try {
                    let endpoint, requestData;
                    if (type === "additional") {
                        endpoint = `/api/admin/requisition/${requestId}/fee`;
                        requestData = {
                            label,
                            fee_amount: value,
                            account_num: accountNum || null,
                        };
                    } else {
                        endpoint = `/api/admin/requisition/${requestId}/discount`;
                        requestData = {
                            label,
                            discount_amount: value,
                            discount_type: discountType,
                            account_num: accountNum || null,
                        };
                    }
                    const response = await fetch(endpoint, {
                        method: "POST",
                        headers: {
                            Authorization: `Bearer ${adminToken}`,
                            "Content-Type": "application/json",
                        },
                        body: JSON.stringify(requestData),
                    });
                    if (!response.ok)
                        throw new Error("Failed to add fee/discount");
                    showToast("Fee/discount added successfully", "success");
                    bootstrap.Modal.getInstance(
                        document.getElementById("feeModal"),
                    ).hide();
                    document.getElementById("feeType").value = "";
                    document.getElementById("feeValue").value = "";
                    document.getElementById("feeLabel").value = "";
                    document.getElementById("accountNum").value = "";
                    await refreshAllFeeDisplays();
                } catch (error) {
                    showToast(
                        "Failed to add fee/discount: " + error.message,
                        "error",
                    );
                }
            });

        document
            .getElementById("feeType")
            .addEventListener("change", function () {
                const discountSection = document.getElementById(
                    "discountTypeSection",
                );
                discountSection.style.display =
                    this.value === "discount" ? "block" : "none";
                if (this.value === "vat") {
                    document.getElementById("feeLabel").value = "Less VAT";
                    document.getElementById("feeValue").value = "12";
                    document.getElementById("discountType").value =
                        "Percentage";
                }
            });

        // Timeline
        const stickyBtn = document.getElementById("stickyTimelineBtn");
        const timelineModal = document.getElementById("timelineModal");
        const closeBtn = document.getElementById("closeTimelineBtn");

        stickyBtn.addEventListener("click", () => {
            timelineModal.classList.toggle("show");
            if (!isTimelineLoaded) loadTimelineContent();
        });
        if (closeBtn)
            closeBtn.addEventListener("click", () =>
                timelineModal.classList.remove("show"),
            );
        document
            .getElementById("refreshTimelineBtn")
            .addEventListener("click", () => {
                isTimelineLoaded = false;
                loadTimelineContent();
                showToast("Timeline refreshed", "success");
            });
        document
            .getElementById("timelineFilter")
            .addEventListener("change", (e) => {
                currentTimelineFilter = e.target.value;
                loadTimelineContent();
            });
        document
            .getElementById("timelineSendBtn")
            .addEventListener("click", sendComment);
        document
            .getElementById("timelineComment")
            .addEventListener("keydown", (e) => {
                if (e.key === "Enter" && !e.shiftKey) {
                    e.preventDefault();
                    sendComment();
                }
            });

        // Back to top
        const backToTop = document.getElementById("backToTop");
        window.addEventListener("scroll", () =>
            backToTop.classList.toggle("show", window.pageYOffset > 300),
        );
        backToTop.addEventListener("click", () =>
            window.scrollTo({ top: 0, behavior: "smooth" }),
        );

        // Document preview
        document.addEventListener("click", (event) => {
            let button = event.target.closest("[data-document-url]");
            if (button && button.hasAttribute("data-document-url")) {
                event.preventDefault();
                const url = button.getAttribute("data-document-url");
                const title = button.getAttribute("data-document-title");
                const ext = url.split(".").pop().toLowerCase();
                const isPDF = ext === "pdf";
                const isImage = ["jpg", "jpeg", "png", "gif", "webp"].includes(
                    ext,
                );

                const overlay = document.createElement("div");
                overlay.style.cssText =
                    "position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.9);z-index:9999;display:flex;justify-content:center;align-items:center;cursor:pointer";
                overlay.onclick = (e) =>
                    e.target === overlay && overlay.remove();

                const closeBtnDoc = document.createElement("button");
                closeBtnDoc.innerHTML = "&times;";
                closeBtnDoc.style.cssText =
                    "position:absolute;top:20px;right:20px;background:rgba(255,255,255,0.2);color:white;border:none;border-radius:50%;width:40px;height:40px;font-size:24px;cursor:pointer";
                closeBtnDoc.onclick = () => overlay.remove();
                overlay.appendChild(closeBtnDoc);

                if (isPDF) {
                    const iframe = document.createElement("iframe");
                    iframe.src = `https://docs.google.com/gview?url=${encodeURIComponent(url)}&embedded=true`;
                    iframe.style.cssText =
                        "width:90%;height:90%;border:none;border-radius:8px";
                    overlay.appendChild(iframe);
                } else if (isImage) {
                    const img = document.createElement("img");
                    img.src = url;
                    img.style.cssText =
                        "max-width:90%;max-height:90%;border-radius:8px";
                    overlay.appendChild(img);
                } else {
                    overlay.innerHTML += `<div style="background:white;padding:2rem;border-radius:8px;text-align:center"><p>This file type cannot be previewed.</p><a href="${url}" target="_blank" class="btn btn-primary">Download File</a></div>`;
                }
                document.body.appendChild(overlay);
            }
        });
    }

    // ============================================
    // PUBLIC: Exposed to the outside world
    // ============================================
    function init(id) {
        requestId = id;
        adminToken = localStorage.getItem("adminToken");
        if (!adminToken) {
            console.error("No authentication token found");
            return;
        }
        markNotificationAsRead();
        fetchRequestDetails();
        setupEventListeners();
    }

    return {
        init: init,
    };
})();

window.RequestView = RequestView;

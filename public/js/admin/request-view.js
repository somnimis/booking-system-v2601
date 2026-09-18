/**
 * ============================================================================
 * Request View (Thin) Module
 * ============================================================================
 * Streamlined version using single-query API endpoint.
 *
 * This module handles the admin-side detailed view of a single requisition
 * request. It fetches all related data (user, form, schedule, fees, approvals,
 * comments, documents) from a single API endpoint and renders it across
 * multiple UI sections including:
 *   - Header (request ID + status badge)
 *   - Action buttons (approve/reject/finalize/schedule/ongoing/close)
 *   - Request details (letter body, event info, attachments)
 *   - Timeline (comments, fees, approvals/rejections)
 *   - Approval tracker (categorized approvers with status)
 *
 * @module RequestViewThin
 * ============================================================================
 */

const RequestViewThin = (function () {
    /**
     * ========================================================================
     * STATE
     * ========================================================================
     * Module-level mutable state. All values are set during `init()` and
     * subsequently referenced by renderers, actions, and event handlers.
     * ========================================================================
     */
    let requestId = null; // The requisition request ID (from URL path)
    let adminToken = null; // Admin auth token (from localStorage)
    let requestData = null; // Full request payload from the API
    let timelineFilter = "all"; // Active timeline filter (all|comment|fee|approval)

    /**
     * ========================================================================
     * HELPERS — Formatting & UI Utilities
     * ========================================================================
     * Small, pure utility functions used across renderers. Includes money
     * formatting, HTML escaping, relative/absolute time formatting, and a
     * lightweight toast notification.
     * ========================================================================
     */

    /**
     * Format a numeric amount as Philippine Peso currency (₱1,234.56).
     * @param {number|string} amount
     * @returns {string}
     */
    const formatMoney = (amount) => {
        const num = parseFloat(amount) || 0;
        return (
            "₱" +
            num.toLocaleString("en-PH", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            })
        );
    };

    /**
     * Escape a string for safe insertion into HTML.
     * Uses a detached div's textContent to auto-escape entities.
     * @param {string} text
     * @returns {string}
     */
    const escapeHtml = (text) => {
        const div = document.createElement("div");
        div.textContent = text || "";
        return div.innerHTML;
    };

    /**
     * Convert a timestamp into a short relative time string
     * (e.g., "just now", "5m ago", "2h ago", "3d ago").
     * @param {string|Date} timestamp
     * @returns {string}
     */
    const formatTimeAgo = (timestamp) => {
        if (!timestamp) return "";
        const now = new Date();
        const time = new Date(timestamp);
        const diff = Math.floor((now - time) / 1000);

        if (diff < 60) return "just now";
        if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
        if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
        if (diff < 604800) return `${Math.floor(diff / 86400)}d ago`;
        return time.toLocaleDateString();
    };

    /**
     * Combine a date and time string into a formatted absolute datetime.
     * @param {string} date - ISO date (YYYY-MM-DD)
     * @param {string} time - Time string (HH:MM)
     * @returns {string}
     */
    const formatDateTime = (date, time) => {
        if (!date) return "N/A";
        const dt = new Date(`${date}T${time || "00:00"}`);
        return dt.toLocaleDateString("en-US", {
            year: "numeric",
            month: "short",
            day: "numeric",
            hour: "numeric",
            minute: "2-digit",
            hour12: true,
        });
    };

    /**
     * Format an ISO timestamp as an absolute date + time string,
     * e.g. "Sep 13, 2026, 5:10 PM". Used for submission / completion dates.
     * @param {string} iso
     * @returns {string}
     */
    const formatAbsoluteDate = (iso) => {
        if (!iso) return "N/A";
        const dt = new Date(iso);
        return dt.toLocaleString("en-US", {
            year: "numeric",
            month: "short",
            day: "numeric",
            hour: "numeric",
            minute: "2-digit",
            hour12: true,
        });
    };

    /**
     * ========================================================================
     * API — Network Requests
     * ========================================================================
     * Wrappers around the admin requisition endpoints. All requests include
     * the admin bearer token for authentication.
     * ========================================================================
     */

    /**
     * Fetch the consolidated view-data payload for the current request.
     * @returns {Promise<Object>} Parsed JSON response
     * @throws {Error} If the response is not OK
     */
    const fetchData = async () => {
        const response = await fetch(
            `/api/admin/requisition/${requestId}/view-data`,
            {
                headers: {
                    Authorization: `Bearer ${adminToken}`,
                    Accept: "application/json",
                },
            },
        );
        if (!response.ok) throw new Error("Failed to load request");
        return response.json();
    };

    /**
     * POST an action to a request sub-endpoint (approve, reject, finalize,
     * mark-scheduled, update-status, comment, close, etc.).
     * @param {string} endpoint - Sub-path appended after the request ID
     * @param {Object} [data={}] - JSON body to send
     * @returns {Promise<Object>} Parsed JSON response
     * @throws {Error} If the response is not OK
     */
    const postAction = async (endpoint, data = {}) => {
        const response = await fetch(
            `/api/admin/requisition/${requestId}/${endpoint}`,
            {
                method: "POST",
                headers: {
                    Authorization: `Bearer ${adminToken}`,
                    "Content-Type": "application/json",
                    Accept: "application/json",
                },
                body: JSON.stringify(data),
            },
        );
        const result = await response.json();
        if (!response.ok) {
            const parts = [result.error, result.details].filter(Boolean);
            throw new Error(
                parts.join(". ") || result.message || "Action failed",
            );
        }
        return result;
    };

    /**
     * ========================================================================
     * RENDERERS — UI Construction
     * ========================================================================
     * Functions that take API data and populate specific DOM sections of the
     * request view. Each renderer is idempotent and can be re-run to refresh
     * its section.
     * ========================================================================
     */

    /**
     * Render the request ID, status badge, and subtitle row.
     *
     * Subtitle composition:
     *   - "Submitted {date}" (+ "· Finalized on {date} by {admin}" when finalized)
     *   - Finalization indicator: color-coded dot + label
     *   - "Completed on {date}" only for terminal statuses
     *
     * @param {Object} data - Full request payload
     */
    const renderHeader = (data) => {
        const formattedId = String(data.request_id).padStart(4, "0");
        document.getElementById("requestIdDisplay").textContent = formattedId;
        document.getElementById("requestIdDisplayMirror").textContent =
            formattedId;

        const status = data.form_details.status;
        document.getElementById("statusBadgeContainer").innerHTML =
            `<span class="badge" style="background:${status.color};color:white;padding:8px 16px;font-size:0.9rem">${status.name}</span>`;

        // ----- Subtitle -----
        const tracking = data.status_tracking || {};
        const approval = data.approval_info || {};

        const submittedAt = formatAbsoluteDate(tracking.created_at);

        // Finalization indicator — color-coded dot + label (no pill chrome)
        let pillLabel, dotColor;
        if (approval.is_finalized) {
            pillLabel = "Finalized";
            dotColor = "#198754"; // Bootstrap success
        } else if (approval.can_finalize) {
            pillLabel = "Can Finalize";
            dotColor = "#ffc107"; // Bootstrap warning
        } else {
            pillLabel = "Not Finalized";
            dotColor = "#6c757d"; // Bootstrap secondary
        }

        const tooltipText =
            "Once finalized, the form can no longer be edited and the approved fee is locked in.";

        // Terminal statuses where we surface the completion timestamp
        const terminalStatuses = ["Completed", "Rejected", "Cancelled"];
        const showCompletedOn =
            terminalStatuses.includes(status.name) && tracking.returned_at;

        const finalizedBy = tracking.finalized_by
            ? `${tracking.finalized_by.first_name} ${tracking.finalized_by.last_name}`
            : null;
        const closedBy = tracking.closed_by
            ? `${tracking.closed_by.first_name} ${tracking.closed_by.last_name}`
            : null;

        // Build the finalized suffix as a single inline string so it merges
        // into the "Submitted ..." segment (no flex-gap between).
        const finalizedSuffix =
            tracking.is_finalized && tracking.finalized_at
                ? ` &middot; <i class="bi bi-circle-fill me-1" style="color:#198754;font-size:0.5rem;vertical-align:middle;"></i>Finalized on ${escapeHtml(formatAbsoluteDate(tracking.finalized_at))}${finalizedBy ? ` by ${escapeHtml(finalizedBy)}` : ""}`
                : "";

        const parts = [
            `<span><i class="bi bi-calendar3 me-1"></i>Submitted ${escapeHtml(submittedAt)}${finalizedSuffix}</span>`,
        ];

        // Indicator only renders pre-finalization — once finalized, the
        // timestamp sentence above carries the full context.
        if (!approval.is_finalized) {
            parts.push(`<span class="text-muted">&middot;</span>`);
            parts.push(
                `<span data-bs-toggle="tooltip"
                       data-bs-placement="top"
                       title="${escapeHtml(tooltipText)}"
                       style="cursor:help;">
                    <i class="bi bi-circle-fill me-1" style="color:${dotColor};font-size:0.5rem;vertical-align:middle;"></i>${pillLabel}
                </span>`,
            );
        }

        document.getElementById("requestSubtitle").innerHTML = parts.join(" ");
    };

    /**
     * Render contextual action buttons (approve, reject, finalize, schedule,
     * ongoing, close) based on the current status, and bind their click
     * handlers to confirmation modals / API calls.
     * @param {Object} data - Full request payload
     */
    const renderActionButtons = (data) => {
        const container = document.getElementById("actionButtonsTop");
        const statusId = data.form_details.status.id;
        const approvalInfo = data.approval_info;
        const canAct = approvalInfo.current_admin_can_act === true;

        const FINALIZED_STATUS = 2;
        const TERMINAL_STATUSES = [5, 6, 7];

        // Terminal statuses: no actions at all.
        if (TERMINAL_STATUSES.includes(statusId)) {
            container.innerHTML =
                '<span class="text-muted small fst-italic">No actions available</span>';
            return;
        }

        const dropdownItems = [];

        // Order per design: Reject · Finalize · ⋮ · Approve
        // Each segment is built separately, then concatenated in visual order.
        // Event bindings below are unchanged — IDs are preserved.
        const rejectHtml = canAct
            ? `<button class="btn btn-outline-secondary" id="rejectBtn"><i class="bi bi-x-lg"></i> Reject</button>`
            : "";
        const approveHtml = canAct
            ? `<button class="btn btn-success fw-bold" id="approveBtn"><i class="bi bi-check-lg"></i> Approve Request</button>`
            : "";

        // Finalize visibility:
        //   - Finalize (status 1) is only for Head Admin (role 1) and
        //     Final Approving Officer (role 2). Stage-1 dept heads don't
        //     see it; the backend also 403s them if the button is forced.
        //   - Finalize Reservation (status 3) stays visible for all — the
        //     backend authorizes by role when the action fires.
        const currentRoleId = window.Admin?.role_id;
        const isStage1Officer = currentRoleId === 3;

        // Finalize (status 1) is gone — finalization is automatic when the
        // last stage-2 approver acts. Only Finalize Reservation (status 3)
        // remains as a manual action.
        let finalizeHtml = "";
        if (statusId === 3) {
            finalizeHtml = `<button class="btn btn-primary" id="finalizeReservationBtn"><i class="bi bi-bookmark-check"></i> Finalize Reservation</button>`;
        }

        // Dropdown is hidden for stage-1 approvers (role 3). They only act on
        // Approve/Reject; Close Form is reserved for higher-authority roles.
        // Dropdown items — Close Form is the sole entry now.
        if (!isStage1Officer) {
            dropdownItems.push(
                `<li><button class="dropdown-item" id="closeFormBtn"><i class="bi bi-x-circle me-2"></i>Close Form</button></li>`,
            );
        }

        let dropdownHtml = "";
        if (dropdownItems.length > 0) {
            dropdownHtml = `
                <div class="dropdown">
                    <button class="btn btn-outline-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="More Actions">
                        <i class="bi bi-three-dots"></i>
                    </button>
                    <ul class="dropdown-menu shadow-sm dropdown-menu-end">
                        ${dropdownItems.join("")}
                    </ul>
                </div>
            `;
        }

        // Message shown when this admin has already acted on this request.
        // Pairs visually with the missing Approve/Reject buttons.
        const actedMsgHtml = !canAct
            ? `<span class="d-inline-flex flex-column align-items-center justify-content-center text-muted small pe-2"
             style="min-height:42px;line-height:1.15;">
           <i class="bi bi-check-circle-fill text-success mb-1"></i>
           <span class="text-center">You have already<br>acted on this request</span>
            </span>`
            : "";

        // Final composition: [acted message] · Reject · Finalize · ⋮ | Approve Request
        const dividerHtml = approveHtml
            ? `<div class="vr mx-1 align-self-stretch"></div>`
            : "";
        container.innerHTML =
            actedMsgHtml +
            rejectHtml +
            finalizeHtml +
            dropdownHtml +
            dividerHtml +
            approveHtml;

        // ----- Event bindings (unchanged handlers, but finalize now sends closure reason for close) -----
        document.getElementById("approveBtn")?.addEventListener("click", () =>
            showActionModal({
                title: "Confirm Approval",
                body: `<p>Are you sure you want to approve this request?</p>
                   <label class="form-label">Remarks (Optional)</label>
                   <textarea class="form-control" id="approveRemarks" rows="3"></textarea>`,
                confirmClass: "btn-success",
                confirmText: "Approve",
                onConfirm: async () => {
                    const remarks =
                        document.getElementById("approveRemarks").value;
                    await postAction("approve", { remarks: remarks || null });
                    showToast("Request approved", "success");
                    await refreshAfterAction();
                },
            }),
        );

        document.getElementById("rejectBtn")?.addEventListener("click", () =>
            showActionModal({
                title: "Confirm Rejection",
                body: `<p>Are you sure you want to reject this request?</p>
                   <label class="form-label">Reason (Optional)</label>
                   <textarea class="form-control" id="rejectRemarks" rows="3"></textarea>`,
                confirmClass: "btn-danger",
                confirmText: "Reject",
                onConfirm: async () => {
                    const remarks =
                        document.getElementById("rejectRemarks").value;
                    await postAction("reject", { remarks: remarks || null });
                    showToast("Request rejected", "success");
                    await refreshAfterAction();
                },
            }),
        );

        document
            .getElementById("finalizeReservationBtn")
            ?.addEventListener("click", () =>
                showActionModal({
                    title: "Finalize Reservation",
                    body: `<p class="small text-muted">Confirm the official receipt number to finalize this reservation.</p>
                   <label class="form-label">Official Receipt Number *</label>
                   <input type="text" class="form-control" id="officialReceiptNum" required>`,
                    confirmClass: "btn-primary",
                    confirmText: "Finalize Reservation",
                    onConfirm: async () => {
                        const receiptNum = document
                            .getElementById("officialReceiptNum")
                            .value.trim();
                        if (!receiptNum)
                            throw new Error("Please enter receipt number");
                        await postAction("finalize-reservation", {
                            official_receipt_num: receiptNum,
                        });
                        showToast("Reservation finalized", "success");
                        setTimeout(() => location.reload(), 1000);
                    },
                }),
            );

        document.getElementById("closeFormBtn")?.addEventListener("click", () =>
            showActionModal({
                title: "Close Form",
                body: `<div class="text-left mb-3">
                     <p class="mb-0">Are you sure you want to close this form?<br><strong>This action cannot be undone.</strong></p>
                   </div>
                   <label class="form-label">Closure Reason (Optional)</label>
                   <textarea class="form-control" id="closureReason" rows="3"></textarea>`,
                confirmClass: "btn-danger",
                confirmText: "Close Form",
                onConfirm: async () => {
                    const reason = document
                        .getElementById("closureReason")
                        .value.trim();
                    await postAction("close", {
                        closure_reason: reason || null,
                    });
                    showToast("Form closed", "success");
                    setTimeout(() => (location.href = "/admin/calendar"), 1000);
                },
            }),
        );
    };

    /**
     * Render one requested item (facility/equipment/service) as a line in
     * Booking Details.
     *
     * Rules:
     *   - Flat rate        → name [×qty]                          ₱X/event
     *   - Per Hour         → name [×qty] (N hrs)                  ₱X/hr
     *                                                              ₱subtotal
     *
     * All values come straight from the API. No client-side math.
     */
    const renderLineItem = (item) => {
        const isPerHour = item.rate_type === "Per Hour";
        const suffix = isPerHour ? "/hr" : "/event";

        // Left side: name + optional qty + optional duration hint
        const qtyText =
            item.quantity && item.quantity > 1
                ? ` &times;${item.quantity}`
                : "";
        const durText = isPerHour
            ? ` (${escapeHtml(item.duration_text || "")})`
            : "";
        // Prefer duration text from item if provided; fall back to data.duration.text via closure is
        // not available here, so we rely on the caller to have set it. Currently we don't pass it
        // per item, so we omit the parenthetical when unknown.
        const left = `${escapeHtml(item.name)}${qtyText}`;

        const waivedClass = item.is_waived
            ? "text-muted text-decoration-line-through"
            : "";

        return `
        <div class="d-flex justify-content-between small">
            <span>${left}</span>
            <span class="${waivedClass}">
                ${formatMoney(item.fee)}${suffix}
            </span>
        </div>
        ${
            isPerHour && !item.is_waived
                ? `<div class="d-flex justify-content-end small">
                       <span class="text-muted">${formatMoney(item.subtotal)}</span>
                   </div>`
                : ""
        }
    `;
    };

    /**
     * Render the request details panel — letter header, letter body (requester,
     * purpose, participants, additional requests), event details (schedule,
     * facilities, equipment, total fee), and attachment links.
     * @param {Object} data - Full request payload
     */
    const renderDetails = (data) => {
        const user = data.user_details;
        const form = data.form_details;
        const schedule = data.schedule;
        const cal = form.calendar_info || {};

        document.getElementById("letterHeader").innerHTML = `
        <div>
            <div class="letter-title">${escapeHtml(cal.title || "Untitled Request")}</div>
        </div>
    `;

        document.getElementById("letterBody").innerHTML = `
        ${
            cal.description || form.purpose
                ? `<div class="letter-section">
                    <div class="letter-section-label">Description</div>
                    <div>${escapeHtml(cal.description || form.purpose || "")}</div>
                </div>`
                : ""
        }

        <div class="letter-section">
            <div class="letter-section-label">Requester</div>
            <div class="fw-semibold">${escapeHtml(user.first_name)} ${escapeHtml(user.last_name)}</div>
            <div class="letter-meta">
                ${escapeHtml(user.organization_name || "N/A")}
                ${user.school_id ? " &middot; ID: " + escapeHtml(user.school_id) : ""}
            </div>
            <div class="letter-meta">
                ${escapeHtml(user.email)} &middot; ${escapeHtml(user.contact_number || "N/A")}
            </div>
        </div>

        ${
            form.additional_requests
                ? `<div class="letter-section">
            <div class="letter-section-label">Additional Requests</div>
            <div class="letter-meta">${escapeHtml(form.additional_requests)}</div>
        </div>`
                : ""
        }

        <div class="letter-section">
            <!-- 1. Main Parent Layout Row -->
            <div class="row g-4 align-items-start">
                
                <!-- 2. Left Column Content (8 out of 12 columns wide) -->
                <div class="col-md-8">
                    <div class="letter-section-label">Purpose &amp; Participants</div>
                    
                    <!-- Purpose Text -->
                    <div class="mb-1.5">
                        <div class="d-flex align-items-start gap-1.5 text-dark" style="font-size: 0.95rem;">
                            <i class="fa-solid fa-calendar-days text-secondary" style="width: 14px; margin-right: 10px; margin-top: 4px;"></i>
                            <div class="letter-meta">
                            <span style="white-space: pre-wrap;">${escapeHtml(form.purpose || "N/A")}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Nested Logistics Matrix Row (Stacks on mobile, 2x2 on tablets/desktops) -->
                    <div class="row g-2 mt-0 text-dark" style="letter-spacing: -0.1px; max-width: 450px;">
                        
                        <!-- Top Left: Participants -->
                        <div class="letter-meta col-12 col-sm-6 d-flex align-items-center">
                            <i class="fa-solid fa-user text-secondary" style="width: 16px; margin-right: 10px;"></i> 
                            <span>Participants: ${form.num_participants || 0}</span>
                        </div>
                        
                        <!-- Top Right: Tables -->
                        <div class="letter-meta col-12 col-sm-6 d-flex align-items-center">
                            <i class="fa-solid fa-table-columns text-secondary" style="width: 16px; margin-right: 10px;"></i> 
                            <span>Tables: ${form.num_tables || 0}</span>
                        </div>
                        
                        <!-- Bottom Left: Chairs -->
                        <div class="letter-meta col-12 col-sm-6 d-flex align-items-center">
                            <!-- Removed font-size: 0.9rem custom string icon scale rule -->
                            <i class="fa-solid fa-chair text-secondary" style="width: 16px; margin-right: 10px;"></i> 
                            <span>Chairs: ${form.num_chairs || 0}</span>
                        </div>
                        
                        <!-- Bottom Right: Microphones -->
                        <div class="letter-meta col-12 col-sm-6 d-flex align-items-center">
                            <i class="fa-solid fa-microphone-lines text-secondary" style="width: 16px; margin-right: 10px;"></i> 
                            <span>Microphones: ${form.num_microphones || 0}</span>
                        </div>
                        
                    </div> <!-- Closes Inner Matrix Row -->

                                    
                </div> <!-- Closes Main Left Column (col-md-8) cleanly! -->

                <!-- 3. Right Column Content (4 out of 12 columns wide) -->
                <div class="col-md-4">
                    <div class="letter-section-label">Attachments</div>
                    <!-- Dynamic elements load right into here -->
                    <div id="attachmentsContainer"></div>
                </div> <!-- Closes Main Right Column (col-md-4) -->

            </div> <!-- Closes Main Parent Layout Row -->
        </div> <!-- Closes Main Letter Section Block -->

        `;

        const facilities = data.requested_items.facilities || [];
        const equipment = data.requested_items.equipment || [];
        const services = data.requested_items.services || [];

        document.getElementById("eventDetails").innerHTML = `
        <div class="mb-3">
            <div class="fw-bold letter-section-label">Start</div>
            <div>${formatDateTime(schedule.start_date, schedule.start_time)}</div>
        </div>
        <div class="mb-3">
            <div class="fw-bold letter-section-label">End</div>
            <div>${formatDateTime(schedule.end_date, schedule.end_time)}</div>
        </div>
        <div class="mb-3">
            <div class="fw-bold letter-section-label">Duration</div>
            <div>${escapeHtml(data.duration?.text || `${data.duration_hours} hours`)}</div>
        </div>
        ${
            facilities.length
                ? `<div class="mb-3">
                    <div class="fw-bold letter-section-label">Facilities</div>
                    ${facilities.map(renderLineItem).join("")}
                </div>`
                : ""
        }
        ${
            equipment.length
                ? `<div class="mb-3">
                    <div class="fw-bold letter-section-label">Equipment</div>
                    ${equipment.map(renderLineItem).join("")}
                </div>`
                : ""
        }
        ${
            services.length
                ? `<div class="mb-3">
                    <div class="fw-bold letter-section-label">Services</div>
                    ${services.map(renderLineItem).join("")}
                </div>`
                : ""
        }
        <div class="pt-3 border-top"> <!-- Increased padding-top to lower content from top border -->
            <!-- Align-items-baseline ensures the label matches the vertical alignment of its value -->
            <div class="d-flex justify-content-between align-items-baseline">
                <span class="fw-bold letter-section-label">Tentative Fee</span>
                <span class="fw-semibold">${formatMoney(data.fees.tentative_fee)}</span>
            </div>
            ${
                data.fees.adjustments_total !== 0
                    ? `<div class="d-flex justify-content-between align-items-baseline small mt-1">
                           <span class="text-muted">Fee Adjustments</span>
                           <span class="${data.fees.adjustments_total > 0 ? "text-success" : "text-danger"}">
                               ${data.fees.adjustments_total > 0 ? "+" : "-"}${formatMoney(Math.abs(data.fees.adjustments_total))}
                           </span>
                       </div>`
                    : ""
            }
            <div class="border-top mt-3 mb-3"></div> <!-- Increased bottom margin from mb-2 to mb-3 -->
            <div class="d-flex justify-content-between align-items-center">
                <!-- lh-sm reduces line-height to bring the Approved Fee label and value closer together -->
                <div class="lh-sm">
                    <div class="fw-bold letter-section-label">Approved Fee</div>
                    <div class="fs-4 fw-bold text-primary">${formatMoney(data.fees.approved_fee)}</div>
                </div>
                ${
                    data.approval_info.is_finalized
                        ? `<span data-bs-toggle="tooltip" data-bs-placement="top"
                                 title="Fees are locked once the form is finalized"
                                 style="cursor:not-allowed;">
                               <button class="btn btn-sm btn-outline-secondary" disabled style="pointer-events:none;">
                                   <i class="bi bi-lock me-1"></i> Fees Locked
                               </button>
                           </span>`
                        : `<a href="/admin/requisition/${requestId}/financials"
                              class="btn btn-sm btn-outline-secondary">
                               <i class="bi bi-pencil me-1"></i> Edit Fees
                           </a>`
                }
            </div>
        </div>
        `;

        const docs = data.documents;
        document.getElementById("attachmentsContainer").innerHTML = `
        <div class="d-flex flex-column gap-2">
            <a href="${docs.formal_letter.url || "#"}" target="_blank"
               class="btn btn-sm ${docs.formal_letter.url ? "btn-outline-secondary" : "btn-outline-secondary disabled"}">
                <i class="bi bi-file-text me-1"></i> Formal Letter
            </a>
            <a href="${docs.proof_of_payment.url || "#"}" target="_blank"
               class="btn btn-sm ${docs.proof_of_payment.url ? "btn-outline-secondary" : "btn-outline-secondary disabled"}">
                <i class="bi bi-receipt me-1"></i> Proof of Payment
            </a>
            ${
                docs.official_receipt.number
                    ? `<a href="/official-receipt/${requestId}" target="_blank" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-receipt-cutoff me-1"></i> Official Receipt
                       </a>`
                    : `<button class="btn btn-sm btn-outline-secondary disabled">
                        <i class="bi bi-receipt-cutoff me-1"></i> No Receipt
                       </button>`
            }
        </div>
        `;
    };

    /**
     * Render the activity timeline (comments, fees, approvals/rejections),
     * honoring the current `timelineFilter`. Items are merged and sorted
     * chronologically in descending order.
     */
    const renderTimeline = () => {
        const container = document.getElementById("timelineContent");
        if (!requestData) return;

        let items = [];

        // Comments
        if (timelineFilter === "all" || timelineFilter === "comment") {
            (requestData.comments || []).forEach((c) => {
                items.push({
                    type: "comment",
                    timestamp: new Date(c.created_at),
                    data: c,
                });
            });
        }

        // Fees
        if (timelineFilter === "all" || timelineFilter === "fee") {
            (requestData.requisition_fees || []).forEach((f) => {
                items.push({
                    type: "fee",
                    timestamp: new Date(f.created_at),
                    data: f,
                });
            });
        }

        // Approvals
        if (timelineFilter === "all" || timelineFilter === "approval") {
            (requestData.approval_history || [])
                .filter((a) => a.action !== "pending")
                .forEach((a) => {
                    items.push({
                        type:
                            a.action === "approved" ? "approval" : "rejection",
                        timestamp: new Date(a.date_updated || a.acted_at),
                        data: a,
                    });
                });
        }

        // Sort by timestamp desc
        items.sort((a, b) => b.timestamp - a.timestamp);

        if (!items.length) {
            container.innerHTML =
                '<div class="text-center text-muted py-4">No activity found</div>';
            return;
        }

        container.innerHTML = items
            .map((item) => {
                if (item.type === "comment") {
                    const c = item.data;
                    return `
                    <div class="timeline-item comment">
                        <div class="d-flex justify-content-between">
                            <strong class="small">${escapeHtml(c.admin?.first_name)} ${escapeHtml(c.admin?.last_name)}</strong>
                            <small class="text-muted">${formatTimeAgo(c.created_at)}</small>
                        </div>
                        <div class="small bg-white p-2 rounded mt-1">${escapeHtml(c.comment)}</div>
                    </div>
                `;
                }

                if (item.type === "fee") {
                    const f = item.data;
                    const isDiscount = f.discount_amount > 0;
                    return `
                    <div class="timeline-item fee">
                        <div class="d-flex justify-content-between">
                            <strong class="small">${escapeHtml(f.added_by?.name || "Admin")}</strong>
                            <small class="text-muted">${formatTimeAgo(f.created_at)}</small>
                        </div>
                        <div class="small bg-white p-2 rounded mt-1">
                            ${isDiscount ? "Added discount" : "Added fee"}: <strong>${escapeHtml(f.label)}</strong>
                            - ${isDiscount ? "-" : ""}${formatMoney(isDiscount ? f.discount_amount : f.fee_amount)}
                        </div>
                    </div>
                `;
                }

                const a = item.data;
                const admin = a.acted_by || a.required_admin;
                return `
                <div class="timeline-item ${item.type}">
                    <div class="d-flex justify-content-between">
                        <strong class="small">${escapeHtml(admin?.name || "Unknown")}</strong>
                        <small class="text-muted">${formatTimeAgo(a.date_updated || a.acted_at)}</small>
                    </div>
                    <div class="small bg-white p-2 rounded mt-1">
                        ${
                            item.type === "approval"
                                ? `<i class="bi bi-check-circle-fill text-success me-1"></i>Approved`
                                : `<i class="bi bi-x-circle-fill text-danger me-1"></i>Rejected`
                        }
                        <span class="text-muted">&middot; Stage ${a.stage}</span>
                        ${a.remarks ? `<br><em>"${escapeHtml(a.remarks)}"</em>` : ""}
                    </div>
                </div>
            `;
            })
            .join("");
    };

    /**
     * ========================================================================
     * ACTIONS — User-Triggered Operations
     * ========================================================================
     * Async functions that perform side-effecting operations (status updates,
     * comments, data refresh) and update the UI accordingly.
     * ========================================================================
     */

    /**
     * Update the request's status by name (e.g., "Ongoing") and reload.
     * @param {string} statusName
     * @param {Object} [extra={}] - Additional payload fields
     */
    const updateStatus = async (statusName, extra = {}) => {
        try {
            await postAction("update-status", {
                status_name: statusName,
                ...extra,
            });
            showToast(`Status updated to ${statusName}, "success"`);
            setTimeout(() => location.reload(), 1000);
        } catch (error) {
            showToast(error.message, "error");
        }
    };

    /**
     * Read the comment input, POST it, then refresh only the comments
     * portion of `requestData` and re-render the timeline.
     */
    const addComment = async () => {
        const input = document.getElementById("commentInput");
        const comment = input.value.trim();
        if (!comment) return;

        try {
            await postAction("comment", { comment });
            input.value = "";

            // Refresh comments in data
            const freshData = await fetchData();
            requestData.comments = freshData.data.comments;
            renderTimeline();
            showToast("Comment added", "success");
        } catch (error) {
            showToast(error.message, "error");
        }
    };

    /**
     * Surgical refresh after an approve/reject action.
     *
     * Mirrors the approach used by addComment: re-fetch view-data once,
     * assign ONLY the fields that change on approve/reject, then re-render
     * only the sections those fields drive. No full-page reload.
     *
     * Sections touched:
     *   - Header (status badge + finalization pill via status_tracking)
     *   - Action buttons (approval_info.current_admin_can_act flips)
     *   - Timeline (approval_history gains a row)
     *   - Approval tracker card (separate endpoint)
     */
    const refreshAfterAction = async () => {
        const fresh = await fetchData();

        // Patch only the fields that can change on approve/reject.
        requestData.approval_history = fresh.data.approval_history;
        requestData.approval_info = fresh.data.approval_info;
        requestData.form_details.status = fresh.data.form_details.status;
        requestData.status_tracking = fresh.data.status_tracking;

        // Re-render affected sections only.
        renderHeader(requestData);
        renderActionButtons(requestData);
        renderTimeline();

        // Tracker card uses its own endpoint — refresh it too.
        try {
            const tracker = await fetchApprovalTracker();
            renderApprovalTracker(tracker);
        } catch (err) {
            console.error("Approval tracker refresh failed:", err);
        }
    };

    /**
     * ========================================================================
     * APPROVAL TRACKER — Categorized Approver List
     * ========================================================================
     * Fetches and renders the list of required approvers grouped by stage
     * (Approving Officers, Final Approving Officer, Issuing Officer) with
     * per-admin status indicators.
     * ========================================================================
     */

    /**
     * Fetch the approval-status payload for the current request.
     * @returns {Promise<Object>}
     * @throws {Error} If the response is not OK
     */
    const fetchApprovalTracker = async () => {
        const response = await fetch(
            `/api/admin/requisition/${requestId}/approval-status`,
            {
                headers: {
                    Authorization: `Bearer ${adminToken}`,
                    Accept: "application/json",
                },
            },
        );
        if (!response.ok) throw new Error("Failed to load approval tracker");
        return response.json();
    };

    /**
     * Render the approval tracker card: progress badge, per-stage groups,
     * and an "Other Approvers" fallback group for uncategorized admins.
     * @param {Object} tracker - Approval tracker payload from the API
     */
    const renderApprovalTracker = (tracker) => {
        const container = document.getElementById("approvalsContainer");
        const badge = document.getElementById("approvalCountBadge");

        const current = tracker.current_approvals || 0;
        const max = tracker.max_approvals || 0;

        badge.textContent = `${current} of ${max} acted`;
        badge.className = "ms-auto text-muted small fw-normal";

        const admins = tracker.required_admins || [];

        if (!admins.length) {
            container.innerHTML = `
        <div class="text-center text-muted py-4">
            <i class="bi bi-people fs-3 d-block mb-2 opacity-50"></i>
            <small>No approvers assigned</small>
        </div>`;
            return;
        }

        // Categorize by stage (from ApprovalChainService)
        //   stage 1 = Approving Officers (Department Managers, role_id 3)
        //   stage 2 = Final Approving Officer (role_id 2)
        //   stage 3 = Issuing Officer (role_id 5)
        const categories = [
            {
                key: "stage1",
                label: "Approving Officers",
                icon: "bi-people",
                stage: 1,
            },
            {
                key: "stage2",
                label: "Final Approving Officer",
                icon: "bi-shield-check",
                stage: 2,
            },
            {
                key: "stage3",
                label: "Issuing Officer",
                icon: "bi-box-seam",
                stage: 3,
            },
        ];

        const categorized = {};
        const usedIds = new Set();

        categories.forEach((cat) => {
            categorized[cat.key] = admins.filter((a) => {
                if (usedIds.has(a.admin_id)) return false;
                if (a.stage === cat.stage) {
                    usedIds.add(a.admin_id);
                    return true;
                }
                return false;
            });
        });

        const uncategorized = admins.filter((a) => !usedIds.has(a.admin_id));

        const renderAdminRow = (a) => {
            const isApproved = a.status === "Approved";
            const isRejected = a.status === "Rejected";

            const initials = (a.name || "?")
                .split(" ")
                .map((n) => n[0])
                .slice(0, 2)
                .join("")
                .toUpperCase();

            const rowBg = isApproved
                ? "bg-success-subtle"
                : isRejected
                  ? "bg-danger-subtle"
                  : "bg-light";
            const avatarBg = isApproved
                ? "#198754"
                : isRejected
                  ? "#dc3545"
                  : "#6c757d";
            const badgeClass = isApproved
                ? "bg-success"
                : isRejected
                  ? "bg-danger"
                  : "bg-secondary text-white";
            const badgeLabel = isApproved
                ? "Approved"
                : isRejected
                  ? "Rejected"
                  : "Pending";

            const overlayIcon = isApproved
                ? `<i class="bi bi-check-circle-fill text-success position-absolute"
        style="bottom:-2px;right:-2px;font-size:14px;"></i>`
                : isRejected
                  ? `<i class="bi bi-x-circle-fill text-danger position-absolute"
          style="bottom:-2px;right:-2px;font-size:14px;"></i>`
                  : "";

            return `
        <div class="d-flex align-items-center p-2 mb-1 rounded ${rowBg}">
            <div class="me-2 flex-shrink-0 position-relative">
                ${
                    a.photo_url
                        ? `<img src="${a.photo_url}" class="rounded-circle" width="36" height="36"
                               style="object-fit:cover;" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                           <div class="rounded-circle d-none align-items-center justify-content-center text-white"
                                style="width:36px;height:36px;font-size:0.75rem;background:${avatarBg};">
                               ${escapeHtml(initials)}
                           </div>`
                        : `<div class="rounded-circle d-flex align-items-center justify-content-center text-white"
                                style="width:36px;height:36px;font-size:0.75rem;background:${avatarBg};">
                               ${escapeHtml(initials)}
                           </div>`
                }
                ${overlayIcon}
            </div>
            <div class="flex-grow-1 min-w-0">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="text-truncate">
                        <div class="fw-semibold small text-truncate">${escapeHtml(a.name)}</div>
                        <div class="text-muted text-truncate" style="font-size:0.72rem;">
                            ${escapeHtml(a.title || a.role_name || "Approver")}
                        </div>
                    </div>
                    <span class="badge ${badgeClass} ms-2"
                          style="font-size:0.65rem;white-space:nowrap;">
                        ${badgeLabel}
                    </span>
                </div>
            </div>
        </div>
    `;
        };

        let html = "";

        categories.forEach((cat) => {
            const group = categorized[cat.key];
            if (!group.length) return;

            html += `
<div class="mb-3">
    <div class="d-flex align-items-center mb-2 px-1">
        <i class="bi ${cat.icon} text-secondary me-2"></i>
        <span class="text-uppercase fw-bold text-muted"
              style="font-size:0.7rem;letter-spacing:0.05em;">
            ${cat.label}
        </span>
        <span class="ms-auto text-muted" style="font-size:0.7rem;">
            ${group.length}
        </span>
    </div>
    ${group.map(renderAdminRow).join("")}
</div>
    `;
        });

        if (uncategorized.length) {
            html += `
        <div class="mb-3">
            <div class="d-flex align-items-center mb-2 px-1">
                <i class="bi bi-person-gear text-secondary me-2"></i>
                <span class="text-uppercase fw-bold text-muted"
                      style="font-size:0.7rem;letter-spacing:0.05em;">
                    Other Approvers
                </span>
            </div>
            ${uncategorized.map(renderAdminRow).join("")}
        </div>
    `;
        }

        container.innerHTML = html;
    };

    /**
     * ========================================================================
     * MODALS & EVENT SETUP
     * ========================================================================
     * Shared modal builder and one-time DOM event binding for tabs, the
     * timeline filter, comment input, and back-to-top button.
     * ========================================================================
     */

    /**
     * Show a generic confirmation modal with dynamic title, body, confirm
     * button styling, and an onConfirm callback. The confirm button is
     * cloned to remove prior listeners before attaching the new handler.
     * @param {Object} config
     * @param {string} config.title
     * @param {string} config.body
     * @param {string} [config.confirmClass="btn-primary"]
     * @param {string} [config.confirmText="Confirm"]
     * @param {Function} config.onConfirm - async callback; errors are toasted
     */
    const showActionModal = (config) => {
        document.getElementById("actionModalTitle").textContent = config.title;
        document.getElementById("actionModalBody").innerHTML = config.body;

        const confirmBtn = document.getElementById("actionModalConfirm");
        confirmBtn.className = `btn ${config.confirmClass || "btn-primary"}`;
        confirmBtn.textContent = config.confirmText || "Confirm";

        const modal = bootstrap.Modal.getOrCreateInstance(
            document.getElementById("actionModal"),
        );

        const fresh = confirmBtn.cloneNode(true);
        confirmBtn.replaceWith(fresh);
        fresh.addEventListener("click", async () => {
            try {
                await config.onConfirm();
                modal.hide();
            } catch (err) {
                showToast(err.message, "error");
            }
        });

        modal.show();
    };

    /**
     * Wire up tab buttons (`[data-tab]`) to toggle active classes on both
     * the tab header and its corresponding `${tab}Pane` content pane.
     */
    const setupTabs = () => {
        document.querySelectorAll("[data-tab]").forEach((tab) => {
            tab.addEventListener("click", () => {
                document
                    .querySelectorAll("[data-tab]")
                    .forEach((t) => t.classList.remove("active"));
                document
                    .querySelectorAll(".tab-pane")
                    .forEach((p) => p.classList.remove("active"));

                tab.classList.add("active");
                document
                    .getElementById(`${tab.dataset.tab}Pane`)
                    .classList.add("active");
            });
        });
    };

    /**
     * Bind one-time event listeners for the timeline filter, refresh button,
     * comment input/send button, and the back-to-top scroll button.
     */
    const setupEventListeners = () => {
        // Timeline
        document
            .getElementById("timelineFilter")
            ?.addEventListener("change", (e) => {
                timelineFilter = e.target.value;
                renderTimeline();
            });

        document
            .getElementById("refreshTimeline")
            ?.addEventListener("click", async () => {
                const freshData = await fetchData();
                // Timeline-related fields.
                requestData.comments = freshData.data.comments;
                requestData.requisition_fees = freshData.data.requisition_fees;
                requestData.approval_history = freshData.data.approval_history;
                // Header + actions.
                requestData.approval_info = freshData.data.approval_info;
                requestData.status_tracking = freshData.data.status_tracking;
                requestData.form_details.status = freshData.data.form_details.status;

                renderTimeline();
                renderHeader(requestData);
                renderActionButtons(requestData);

                // Approval Status card.
                try {
                    const tracker = await fetchApprovalTracker();
                    renderApprovalTracker(tracker);
                } catch (err) {
                    console.error("Approval tracker refresh failed:", err);
                }

                showToast("Refreshed", "success");
            });

        document
            .getElementById("sendCommentBtn")
            ?.addEventListener("click", addComment);
        document
            .getElementById("commentInput")
            ?.addEventListener("keydown", (e) => {
                if (e.key === "Enter") addComment();
            });

        // Bootstrap tooltips (re-init after header render)
        document
            .querySelectorAll('[data-bs-toggle="tooltip"]')
            .forEach((el) => bootstrap.Tooltip.getOrCreateInstance(el));

        // Back to top
        const backToTop = document.getElementById("backToTop");
        window.addEventListener("scroll", () => {
            backToTop.classList.toggle("show", window.scrollY > 300);
        });
        backToTop.addEventListener("click", () =>
            window.scrollTo({ top: 0, behavior: "smooth" }),
        );
    };

    /**
     * ========================================================================
     * INIT — Module Entry Point
     * ========================================================================
     * Called once on DOMContentLoaded with the request ID parsed from the URL
     * path. Orchestrates the initial fetch + render sequence, then wires up
     * all event listeners.
     * ========================================================================
     */

    /**
     * Initialize the request view for a given request ID.
     * @param {string|number} id - Requisition request ID
     */
    const init = async (id) => {
        requestId = id;
        adminToken = localStorage.getItem("adminToken");

        if (!adminToken) {
            document.getElementById("loadingState").innerHTML =
                '<div class="alert alert-danger">Authentication required</div>';
            return;
        }

        try {
            // Mark notification as read (fire and forget)
            fetch(
                `/api/admin/notifications/requisition/${requestId}/mark-as-read`,
                {
                    method: "POST",
                    headers: { Authorization: `Bearer ${adminToken}` },
                },
            ).catch(() => {});

            // Fetch all data in single query
            const result = await fetchData();
            requestData = result.data;

            // Render
            renderHeader(requestData);
            renderActionButtons(requestData);
            renderDetails(requestData);
            renderTimeline();

            // Fetch approval tracker (separate endpoint, non-blocking)
            fetchApprovalTracker()
                .then(renderApprovalTracker)
                .catch((err) => {
                    console.error("Approval tracker error:", err);
                    document.getElementById("approvalsContainer").innerHTML =
                        '<div class="text-center text-muted py-4"><small>Failed to load approvers</small></div>';
                });

            // Show content
            document.getElementById("loadingState").style.display = "none";
            document.getElementById("contentState").style.display = "block";

            // Setup
            setupTabs();
            setupEventListeners();
        } catch (error) {
            console.error("Init error:", error);
            document.getElementById("loadingState").innerHTML =
                `<div class="alert alert-danger">
                    <strong>Error:</strong> ${escapeHtml(error.message)}
                    <br><button class="btn btn-sm btn-outline-danger mt-2" onclick="location.reload()">Retry</button>
                </div>`;
        }
    };

    return { init };
})();

window.RequestViewThin = RequestViewThin;

/**
 * ============================================================================
 * BOOTSTRAP — DOMContentLoaded Entry
 * ============================================================================
 * Parses the request ID from the URL path and kicks off the module. If the
 * module failed to load (e.g., script error), shows a fallback error message.
 * ============================================================================
 */
document.addEventListener("DOMContentLoaded", () => {
    const pathParts = window.location.pathname.split("/");
    const requestId = pathParts[pathParts.length - 1];

    if (typeof RequestViewThin !== "undefined") {
        RequestViewThin.init(requestId);
    } else {
        console.error("RequestViewThin module not loaded");
        document.getElementById("loadingState").innerHTML =
            '<div class="alert alert-danger">Failed to load module. Please refresh.</div>';
    }
});

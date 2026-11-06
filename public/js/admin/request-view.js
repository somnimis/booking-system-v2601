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
    return dt
        .toLocaleDateString("en-US", {
            year: "numeric",
            month: "short",
            day: "numeric",
            hour: "numeric",
            minute: "2-digit",
            hour12: true,
        })
        .replace(/\b(AM|PM)\b/g, (m) => m.toLowerCase());
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
    const postAction = async (endpoint, data = {}, method = "POST") => {
        const response = await fetch(
            `/api/admin/requisition/${requestId}/${endpoint}`,
            {
                method: method, // Changed from POST to dynamic method
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
     * Render contextual action buttons based on the current admin's role
     * and the form's status.
     *
     * Role matrix:
     *   Role 1 (Head Admin)              → Confirm Reservation · Close Reservation (always)
     *   Role 5 (Issuing Officer)         → Confirm Reservation · Close Reservation (always)
     *   Role 2 (Final Approving Officer) → Approve · Reject (status 1 only; reject also closes)
     *   Role 3 (Approving Officer)       → Approve · Reject (status 1 only)
     *   Other roles                      → no actions
     *
     * Terminal statuses (5/6/7) always show "No actions available".
     * Head Admin overrides bypass all status guards (backend also permits this).
     */
    const renderActionButtons = (data) => {
        const container = document.getElementById("actionButtonsTop");
        const statusId = data.form_details.status.id;
        const approvalInfo = data.approval_info;
        const canAct = approvalInfo.current_admin_can_act === true;
        const roleId = window.Admin?.role_id;

        const TERMINAL_STATUSES = [5, 6, 7];
        const RESERVED_STATUS = 4;

        // ---- Hard blockers (evaluated before any status logic) ----

        // Role 4 (Inventory Manager) never sees action buttons.
        if (roleId === 4) {
            container.innerHTML =
                '<span class="text-muted small fst-italic">No actions available for your role</span>';
            return;
        }

        // Approving Officer (role 3) on Reserved sees a specific message.
        if (statusId === RESERVED_STATUS && roleId === 3) {
            container.innerHTML =
                '<span class="text-muted small fst-italic">This form is already Reserved.</span>';
            return;
        }

        // Terminal statuses: no actions at all.
        if (TERMINAL_STATUSES.includes(statusId)) {
            container.innerHTML =
                '<span class="text-muted small fst-italic">No actions available</span>';
            return;
        }

        // ---- Build button HTML per role ----

        let buttonsHtml = "";
        const isIssuingOrHeadAdmin = roleId === 1 || roleId === 5;
        const isApprover = roleId === 2 || roleId === 3;

        if (statusId === RESERVED_STATUS) {
            // Reserved: Close Reservation only, for roles 1, 2, 5.
            if (roleId === 1 || roleId === 2 || roleId === 5) {
                const isHeadAdmin = roleId === 1;
                container.dataset.headAdmin = isHeadAdmin ? "1" : "0";
                buttonsHtml = `
                    <button class="btn btn-danger" id="closeReservationBtn">
                        <i class="bi bi-x-circle"></i> Close Reservation
                    </button>
                `;
            }
        } else if (isIssuingOrHeadAdmin) {
            // Roles 1 & 5: Confirm + Close Reservation, always.
            const isHeadAdmin = roleId === 1;
            container.dataset.headAdmin = isHeadAdmin ? "1" : "0";

            buttonsHtml = `
                <div class="d-flex flex-column gap-2">
                    <button class="btn btn-primary" id="confirmReservationBtn">
                        <i class="bi bi-bookmark-check"></i> Confirm Reservation
                    </button>
                    <button class="btn btn-danger" id="closeReservationBtn">
                        <i class="bi bi-x-circle"></i> Close Reservation
                    </button>
                </div>
            `;
        } else if (isApprover && statusId === 1) {
            // Only show buttons if the admin HAS NOT acted yet (canAct is true)
            if (canAct) {
                const isFinalApprover = roleId === 2;
                container.dataset.finalApprover = isFinalApprover ? "1" : "0";

                buttonsHtml = `
                    <button class="btn btn-outline-secondary" id="rejectBtn">
                        <i class="bi bi-x-lg"></i> Reject
                    </button>
                    <div class="vr mx-1 align-self-stretch"></div>
                    <button class="btn btn-success fw-bold" id="approveBtn">
                        <i class="bi bi-check-lg"></i> Approve Request
                    </button>
                `;
            }
        }
        // Message shown when this admin has already acted on this request.
        const actedMsgHtml =
            !canAct && isApprover && statusId === 1
                ? `<span class="d-inline-flex flex-column align-items-center justify-content-center text-muted small pe-2"
                          style="min-height:42px;line-height:1.15;">
                       <i class="bi bi-check-circle-fill text-success mb-1"></i>
                       <span class="text-center">You have already<br>acted on this request</span>
                   </span>`
                : "";

        // No buttons and no acted message → informational fallback.
        if (!buttonsHtml && !actedMsgHtml) {
            container.innerHTML =
                '<span class="text-muted small fst-italic">No actions available for your role</span>';
            return;
        }

        container.innerHTML = actedMsgHtml + buttonsHtml;

        // ---- Event bindings ----

        // Approve (roles 2 & 3)
        // Approve (roles 2 & 3)
        document.getElementById("approveBtn")?.addEventListener("click", () => {
            const isFinalApprover = roleId === 2;
            const isIssuingOfficer = roleId === 5;

            let contextText = "Are you sure you want to approve this request?";
            
            if (isFinalApprover) {
            contextText = `Approving this request will <strong>finalize all fee changes</strong> and notify the requester of the final amount. Do you want to proceed?`;
            } else if (isIssuingOfficer) {
                contextText = `This will confirm the reservation and email the requester their Use Permit. Do you want to proceed?`;
            }

            showActionModal({
                title: "Confirm Approval",
                body: `<p class="mb-3">${contextText}</p>
                       <label class="form-label">Remarks (Optional)</label>
                       <textarea class="form-control" id="approveRemarks" rows="3"></textarea>`,
                confirmClass: "btn-success",
                confirmText: "Approve",
                onConfirm: async () => {
                    const remarks = document.getElementById("approveRemarks").value;
                    await postAction("approve", { remarks: remarks || null });
                    showToast("Request approved", "success");
                    await refreshAfterAction();
                },
            });
        });

        // Reject (roles 2 & 3). Role 2 also closes the form with the same remark.
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

                    // 1. Reject the request.
                    await postAction("reject", { remarks: remarks || null });

                    // 2. Final Approving Officers also close the form in the
                    //    same action, using the same remark as the closure reason.
                    if (container.dataset.finalApprover === "1") {
                        await postAction("close", {
                            closure_reason: remarks || null,
                        });
                    }

                    showToast("Request rejected", "success");
                    await refreshAfterAction();
                },
            }),
        );

        // Confirm Reservation (roles 1 & 5)
        document
            .getElementById("confirmReservationBtn")
            ?.addEventListener("click", () => {
                const isHeadAdmin = container.dataset.headAdmin === "1";
                const hasOr = !!data.documents.official_receipt.number;

                // If OR is missing AND it's not a head admin, block them.
                if (!hasOr && !isHeadAdmin) {
                    const isAwaitingPayment = data.form_details.status.name === 'Awaiting Payment';
                    if (isAwaitingPayment) {
                        showToast("The requester hasn't uploaded their payment yet. Please wait until the form moves to 'Verifying Payment'.", "error");
                    } else {
                        showToast("Please enter the Official Receipt Number in the Attachments section before confirming.", "error");
                    }
                    return;
                }

                // Build the modal body
                let modalBody = "";
                if (isHeadAdmin) {
                    modalBody += `<div class="alert alert-warning small mb-3">
                        <i class="bi bi-exclamation-triangle me-1"></i> You are about to confirm this reservation as Head Administrator.
                    </div>`;
                }

                modalBody += `<p class="mb-3">Are you sure you want to confirm this reservation?</p>`;

                // If OR is missing, provide the input field for the Admin override
                if (!hasOr) {
                    modalBody += `
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Official Receipt Number *</label>
                            <input type="text" class="form-control" id="overrideOfficialReceipt" placeholder="Enter OR Number" required>
                            <div class="form-text text-danger small mt-1">The Official Receipt is required to generate the permit.</div>
                        </div>
                    `;
                } else {
                    modalBody += `
                        <div class="alert alert-success small mb-0">
                            <i class="bi bi-receipt me-1"></i> Official Receipt: <strong>${escapeHtml(data.documents.official_receipt.number)}</strong>
                        </div>
                    `;
                }

                showActionModal({
                    title: "Confirm Reservation",
                    body: modalBody,
                    confirmClass: "btn-primary",
                    confirmText: "Confirm Reservation",
                    onConfirm: async () => {
                        let finalOr = data.documents.official_receipt.number;

                        // If the Admin typed it into the modal, validate and save it
                        if (!hasOr) {
                            finalOr = document.getElementById("overrideOfficialReceipt").value.trim();
                            if (!finalOr) throw new Error("Please enter the Official Receipt Number.");
                            
                            // Save to DB via inline endpoint
                            await postAction("official-receipt", { official_receipt_num: finalOr }, "PUT");
                            
                            // Update local data so the next steps don't fail
                            data.documents.official_receipt.number = finalOr; 
                        }

                        // If this admin still has a pending approval row, record it
                        if (canAct) {
                            await postAction("approve", { remarks: null });
                        }

                        // Call finalize
                        await postAction("finalize-reservation", {});

                        showToast("Reservation confirmed", "success");
                        await refreshAfterAction();
                    },
                });
            });

        // Close Reservation (roles 1 & 5)
        document
            .getElementById("closeReservationBtn")
            ?.addEventListener("click", () => {
                const isHeadAdmin = container.dataset.headAdmin === "1";
                const warningHtml = isHeadAdmin
                    ? `<div class="alert alert-warning small mb-3">
                           <i class="bi bi-exclamation-triangle me-1"></i>
                           You are about to close this reservation as Head Administrator.
                       </div>`
                    : "";

                showActionModal({
                    title: "Close Reservation",
                    body: `${warningHtml}
                           <p class="mb-3">Are you sure you want to close this reservation?<br>
                           <strong>This action cannot be undone.</strong></p>
                           <label class="form-label">Closure Reason (Optional)</label>
                           <textarea class="form-control" id="closureReason" rows="3"></textarea>`,
                    confirmClass: "btn-danger",
                    confirmText: "Close Reservation",
                    onConfirm: async () => {
                        const reason = document
                            .getElementById("closureReason")
                            .value.trim();

                        await postAction("close", {
                            closure_reason: reason || null,
                        });

                        showToast("Reservation closed", "success");
                        await refreshAfterAction();
                    },
                });
            });
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
            <div class="letter-section-label">Purpose &amp; Participants</div>
            <div class="mb-1.5">
                <div class="d-flex align-items-start gap-1.5 text-dark" style="font-size: 0.95rem;">
                    <i class="fa-solid fa-calendar-days text-secondary" style="width: 14px; margin-right: 10px; margin-top: 4px;"></i>
                    <div class="letter-meta">
                        <span style="white-space: pre-wrap;">${escapeHtml(form.purpose || "N/A")}</span>
                    </div>
                </div>
            </div>
            <div class="row g-2 mt-0 text-dark" style="letter-spacing: -0.1px; max-width: 450px;">
                <div class="letter-meta col-12 col-sm-6 d-flex align-items-center">
                    <i class="fa-solid fa-user text-secondary" style="width: 16px; margin-right: 10px;"></i>
                    <span>Participants: ${form.num_participants || 0}</span>
                </div>
                <div class="letter-meta col-12 col-sm-6 d-flex align-items-center">
                    <i class="fa-solid fa-table-columns text-secondary" style="width: 16px; margin-right: 10px;"></i>
                    <span>Tables: ${form.num_tables || 0}</span>
                </div>
                <div class="letter-meta col-12 col-sm-6 d-flex align-items-center">
                    <i class="fa-solid fa-chair text-secondary" style="width: 16px; margin-right: 10px;"></i>
                    <span>Chairs: ${form.num_chairs || 0}</span>
                </div>
                <div class="letter-meta col-12 col-sm-6 d-flex align-items-center">
                    <i class="fa-solid fa-microphone-lines text-secondary" style="width: 16px; margin-right: 10px;"></i>
                    <span>Microphones: ${form.num_microphones || 0}</span>
                </div>
            </div>
        </div>
    `;
};

    /**
 * Booking Details card — schedule-only summary.
 * Start / End / Duration / All Day / Cleanup.
 */
const renderBookingDetails = (data) => {
    const schedule = data.schedule;
    const container = document.getElementById("bookingDetails");
    if (!container) return;

    const startText = formatDateTime(schedule.start_date, schedule.start_time);
    const endText = formatDateTime(schedule.end_date, schedule.end_time);

    container.innerHTML = `
        <div class="mb-3">
            <div class="fw-bold letter-section-label">Start Schedule</div>
            <div>${escapeHtml(startText)}</div>
        </div>
        <div class="mb-3">
            <div class="fw-bold letter-section-label">End Schedule</div>
            <div>${escapeHtml(endText)}</div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-sm-4">
                <div class="fw-bold letter-section-label">Duration</div>
                <div>${escapeHtml(data.duration?.text || `${data.duration_hours} hours`)}</div>
            </div>
            <div class="col-12 col-sm-4">
                <div class="fw-bold letter-section-label">All Day</div>
                <div>${schedule.all_day ? "Yes" : "No"}</div>
            </div>
            <div class="col-12 col-sm-4">
                <div class="fw-bold letter-section-label">Cleanup</div>
                <div>${schedule.cleanup_hours || 0} hour(s)</div>
            </div>
        </div>
        <div class="pt-3 border-top mt-auto">
            <div class="fw-bold letter-section-label mb-2">Attachments</div>
            <div id="attachmentsContainer"></div>
        </div>
    `;
};

/**
 * Attachments card — formal letter, proof of payment, official receipt.
 */
const renderAttachments = (data) => {
    const docs = data.documents;
    const container = document.getElementById("attachmentsContainer");
    if (!container) return;

    const statusName = data.form_details.status.name;
    const roleId = window.Admin?.role_id;
    
    // Allow editing IF (Status is Verifying Payment) OR (Admin Override).
    // Notice we removed the !is_finalized check, because Stage 2 finalizes the fee BEFORE Stage 3 enters the OR.
    const isEditableStage = (statusName === 'Verifying Payment') || roleId === 1;

    container.innerHTML = `
        <div class="d-flex flex-column gap-2">
            <a href="${docs.formal_letter.url || "#"}" target="_blank"
               class="btn btn-sm ${docs.formal_letter.url ? "btn-outline-secondary" : "btn-outline-secondary disabled"}">
                <i class="bi bi-file-text me-1"></i> Formal Letter
            </a>
            <a href="${docs.proof_of_payment.url || "#"}" target="_blank"
               class="btn btn-sm ${docs.proof_of_payment.url ? "btn-outline-secondary" : "btn-outline-secondary disabled"}">
                <i class="bi bi-receipt me-1"></i> Proof of Payment
            </a>
            
            ${isEditableStage ? `
                <div class="mt-2">
                    <label class="form-label small fw-bold text-muted mb-1">Official Receipt No.</label>
                    <div class="d-flex gap-2">
                        <input type="text" class="form-control form-control-sm" id="inlineOfficialReceipt"
                            value="${docs.official_receipt.number || ''}" placeholder="Enter OR Number">
                        <button class="btn btn-sm btn-primary" type="button" id="saveReceiptBtn">
                            <i class="bi bi-check-lg"></i>
                        </button>
                    </div>
                </div>
            ` : (docs.official_receipt.number ? `
                <a href="/official-receipt/${requestId}" target="_blank" class="btn btn-sm btn-primary mt-1">
                    <i class="bi bi-receipt-cutoff me-1"></i> View Receipt
                </a>
            ` : `
                <span class="text-muted small mt-1 d-inline-block">
                    <i class="bi bi-receipt-cutoff me-1"></i> No Receipt Uploaded
                </span>
            `)}
        </div>
    `;

    // Bind the save button if it exists
    document.getElementById('saveReceiptBtn')?.addEventListener('click', async function() {
        const val = document.getElementById('inlineOfficialReceipt').value.trim();
        if (!val) return showToast("Receipt number cannot be empty.", "error");

        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        try {
            const res = await fetch(`/api/admin/requisition/${requestId}/official-receipt`, {
                method: 'PUT',
                headers: { 
                    Authorization: `Bearer ${localStorage.getItem("adminToken")}`, 
                    "Content-Type": "application/json", 
                    Accept: "application/json" 
                },
                body: JSON.stringify({ official_receipt_num: val })
            });

            if (!res.ok) {
                const err = await res.json();
                throw new Error(err.details?.official_receipt_num?.[0] || err.error || "Failed to save");
            }

            showToast("Official Receipt saved", "success");
            
            // Refresh the data and re-render
            const fresh = await fetchData();
            requestData = fresh.data;
            renderActionButtons(requestData);
            renderAttachments(requestData);

        } catch (error) {
            showToast(error.message, "error");
            this.disabled = false;
            this.innerHTML = '<i class="bi bi-save"></i>';
        }
    });
};

/**
 * Event Resources Breakdown — mirrors resources/partials table design.
 * Renders facilities, equipment, and services in one table with a total footer.
 */
/**
 * Renders the unified resources table (Base items + Misc Fees) with waiving logic.
 */
/**
 * Renders the unified resources table (Base items + Misc Fees) with waiving logic.
 */
const renderResourcesBreakdown = (data) => {
    const container = document.getElementById("resourcesBreakdown");
    if (!container) return;

    const facilities = data.requested_items.facilities || [];
    const equipment = data.requested_items.equipment || [];
    const services = data.requested_items.services || [];
    const miscFees = data.requisition_fees || [];

    const editBtn = document.getElementById("editFeesBtn");
    const addFeeBtn = document.getElementById("addFeeBtn");
    const waiveAllWrapper = document.getElementById("waiveAllSwitch")?.closest('.form-check');

    if (data.approval_info?.is_finalized) {
        if (editBtn) editBtn.style.display = 'none';
        if (addFeeBtn) addFeeBtn.style.display = 'none';
        if (waiveAllWrapper) waiveAllWrapper.style.display = 'none';
    } else {
        // Ensure they show if the form somehow gets unfinalized (e.g. debugging)
        if (editBtn) editBtn.style.display = '';
        if (addFeeBtn) addFeeBtn.style.display = '';
        if (waiveAllWrapper) waiveAllWrapper.style.display = '';
    }

    // Renders base items with a waive checkbox
    const baseRow = (item, type, idField) => {
        const adminName = item.waived_by_name || (window.Admin ? `${window.Admin.first_name} ${window.Admin.last_name}` : 'Admin');
        const strikeClass = item.is_waived ? 'text-decoration-line-through' : '';
        
        // Check finalization state
        const isFinalized = data.approval_info?.is_finalized;
        const disabledAttr = isFinalized ? 'disabled' : '';
        const disabledClass = isFinalized ? 'disabled' : '';

        return `
        <tr class="small fee-item ${item.is_waived ? 'waived' : ''}" style="border-color: #e9ecef;">
            <td class="text-center">
                <input class="form-check-input waiver-checkbox ${disabledClass}" type="checkbox" 
                       data-type="${type}" data-id="${item[idField]}" data-admin-name="${escapeHtml(adminName)}" ${item.is_waived ? 'checked' : ''} ${disabledAttr}>
            </td>
            <td>
                <div class="d-inline-block">${escapeHtml(item.name)}${item.quantity && item.quantity > 1 ? ` <span class="text-muted">x${item.quantity}</span>` : ""}</div>
                <br>
                <div class="waived-by-text text-danger ${item.is_waived ? 'd-inline-block' : 'd-none'}" style="font-size: 0.7rem;">
                    Waived by ${escapeHtml(adminName)}
                </div>
            </td>
            <td class="text-center text-muted">${type.charAt(0).toUpperCase() + type.slice(1)}</td>
            <td class="text-center text-muted">${escapeHtml(item.rate_type)}</td>
            <td class="text-center text-muted">${item.duration_text || "-"}</td>
            <td class="text-end text-muted ${strikeClass}">${formatMoney(item.unit_price)}</td>
            <td class="text-end fw-semibold ${strikeClass}">${formatMoney(item.subtotal)}</td>
        </tr>
    `;
    };

    // Renders misc fees with a delete button instead of a checkbox
    const miscRow = (fee) => {
        const isDiscount = fee.type === 'discount';
        const isPercentage = isDiscount && fee.discount_type === 'Percentage';
        
        // Check finalization state
        const isFinalized = data.approval_info?.is_finalized;

        // Calculate the subtotal to show the correct deduction amount
        const baseSubtotal = facilities.reduce((sum, f) => sum + (!f.is_waived ? f.subtotal : 0), 0) +
                             equipment.reduce((sum, e) => sum + (!e.is_waived ? e.subtotal : 0), 0) +
                             services.reduce((sum, s) => sum + (!s.is_waived ? s.subtotal : 0), 0);
        
        let unitPriceText = '';
        let totalValueText = '';

        if (isDiscount) {
            if (isPercentage) {
                unitPriceText = `-${fee.discount_amount}%`;
                const calculatedDiscount = (fee.discount_amount / 100) * baseSubtotal;
                totalValueText = `-${formatMoney(calculatedDiscount)}`;
            } else {
                unitPriceText = formatMoney(fee.discount_amount);
                totalValueText = `-${formatMoney(fee.discount_amount)}`;
            }
        } else {
            unitPriceText = formatMoney(fee.fee_amount);
            totalValueText = formatMoney(fee.fee_amount);
        }

        const addedBy = fee.added_by ? `<br><span class="text-muted" style="font-size:0.7rem;">Added by ${escapeHtml(fee.added_by.name)}</span>` : '';
        
        return `
        <tr class="small fee-item" style="border-color: #e9ecef; background-color: #fafafa;">
            <td class="text-center">
                ${!isFinalized ? `
                    <button class="btn btn-sm btn-link text-danger p-0 delete-fee-btn" 
                            data-fee-id="${fee.fee_id}" title="Delete">
                        <i class="bi bi-trash3"></i>
                    </button>
                ` : `
                    <i class="bi bi-lock-fill text-muted" title="Fees are locked"></i>
                `}
            </td>
            <td>${escapeHtml(fee.label)}${addedBy}</td>
            <td class="text-center text-muted">${isDiscount ? 'Discount' : 'Fee'}</td>
            <td class="text-center text-muted">Flat</td>
            <td class="text-center text-muted">-</td>
            <td class="text-end text-muted">${unitPriceText}</td>
            <td class="text-end fw-semibold ${isDiscount ? 'text-danger' : ''}">${totalValueText}</td>
        </tr>`;
    };
    
    const rowsHtml = [
        ...facilities.map((f) => baseRow(f, "facility", "requested_facility_id")),
        ...equipment.map((e) => baseRow(e, "equipment", "requested_equipment_id")),
        ...services.map((s) => baseRow(s, "service", "requested_service_id")),
        ...miscFees.map(miscRow),
    ].join("");
    
    container.innerHTML = `
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0" style="border-color: #e9ecef;">
                <thead class="bg-light">
                    <tr class="text-muted small">
                        <th class="text-center" style="width: 5%;">Action</th>
                        <th style="width: 25%;">Resource</th>
                        <th class="text-center" style="width: 12%;">Type</th>
                        <th class="text-center" style="width: 12%;">Rate</th>
                        <th class="text-center" style="width: 12%;">Duration</th>
                        <th class="text-end" style="width: 12%;">Base Fee</th>
                        <th class="text-end" style="width: 12%;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    ${rowsHtml || '<tr><td colspan="7" class="text-center text-muted py-4">No resources requested</td></tr>'}
                </tbody>
                <tfoot class="bg-light">
                    <tr style="border-color: #dee2e6; border-bottom: none;">
                        <td colspan="6" class="fw-bold pt-3 pb-3 fs-6">Total Amount</td>
                        <td class="text-end fw-bold text-primary pt-3 pb-3 fs-5" id="tableTotalFee">${formatMoney(data.fees.approved_fee)}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    `;

    // Bind standard waive events
    attachWaiverHandlers();
    updateWaiveAllToggle();

    // Bind delete events for misc fees
    document.querySelectorAll('.delete-fee-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            if (!confirm('Are you sure you want to delete this fee/discount?')) return;
            
            const feeId = this.dataset.feeId;
            const token = localStorage.getItem("adminToken");
            const originalHtml = this.innerHTML;
            
            // Show loading state
            this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            this.disabled = true;

            try {
                const res = await fetch(`/api/admin/requisition/${requestId}/fee/${feeId}`, {
                    method: 'DELETE',
                    headers: { Authorization: `Bearer ${token}`, Accept: "application/json" }
                });
                
                if (!res.ok) throw new Error("Failed to delete fee");
                
                const result = await res.json();
                showToast("Fee deleted successfully", "success");
                
                // Update total immediately
                document.getElementById('tableTotalFee').textContent = formatMoney(result.updated_approved_fee);
                
                // Remove the row from the DOM without full re-render
                this.closest('tr').remove();
                
                // Refresh timeline
                const fresh = await fetchData();
                requestData.requisition_fees = fresh.data.requisition_fees;
                renderTimeline();

            } catch (error) {
                showToast(error.message, "error");
                // Revert button
                this.innerHTML = originalHtml;
                this.disabled = false;
            }
        });
    });
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

        // Waivers
        if (timelineFilter === "all" || timelineFilter === "waiver") {
            (requestData.waivers || []).forEach((w) => {
                items.push({
                    type: "waiver",
                    timestamp: new Date(w.timestamp),
                    data: w,
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
    const amount = isDiscount ? f.discount_amount : f.fee_amount;

    return `
        <div class="timeline-item fee">
            <div class="d-flex justify-content-between align-items-center">
                <strong class="small">${escapeHtml(f.added_by?.name || "Admin")}</strong>
                <small class="text-muted">${formatTimeAgo(f.created_at)}</small>
            </div>

            <div class="d-flex justify-content-between align-items-center bg-white p-2 rounded mt-1">
                <div class="small">
                    <span class="text-muted">${isDiscount ? "Discount applied" : "Additional fee"}</span>
                    <div class="fw-semibold text-dark">${escapeHtml(f.label)}</div>
                </div>

                <span class="badge rounded-pill ${isDiscount ? "bg-danger-subtle text-danger" : "bg-success-subtle text-success"}">
                    ${isDiscount ? "−" : "+"}${formatMoney(amount)}
                </span>
            </div>
        </div>
    `;
}

                if (item.type === "waiver") {
                    const w = item.data;
                    return `
                    <div class="timeline-item waiver">
                        <div class="d-flex justify-content-between">
                            <strong class="small">${escapeHtml(w.admin_name)}</strong>
                            <small class="text-muted">${formatTimeAgo(w.timestamp)}</small>
                        </div>
                        <div class="small bg-white p-2 rounded mt-1">
                            <i class="bi bi-tag-fill text-primary me-1"></i> Waived <strong>${escapeHtml(w.item_name)}</strong> 
                            <span class="text-muted">&middot; ${w.item_type}</span>
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
        requestData = fresh.data;

        // Patch only the fields that can change on approve/reject.
        requestData.approval_history = fresh.data.approval_history;
        requestData.approval_info = fresh.data.approval_info;
        requestData.form_details.status = fresh.data.form_details.status;
        requestData.status_tracking = fresh.data.status_tracking;

        // Re-render affected sections only.
        renderHeader(requestData);
        renderActionButtons(requestData);
        renderAttachments(requestData);
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
                ? `<div class="position-absolute bg-white rounded-circle d-flex align-items-center justify-content-center" style="bottom:-2px; right:-2px; width:16px; height:16px;">
                       <i class="bi bi-check-circle-fill text-success" style="font-size:14px;"></i>
                   </div>`
                : isRejected
                  ? `<div class="position-absolute bg-white rounded-circle d-flex align-items-center justify-content-center" style="bottom:-2px; right:-2px; width:16px; height:16px;">
                         <i class="bi bi-x-circle-fill text-danger" style="font-size:14px;"></i>
                     </div>`
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
            const originalHtml = fresh.innerHTML;
            const originalClasses = fresh.className;
            
            fresh.disabled = true;
            fresh.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Processing...`;

            try {
                await config.onConfirm();
                modal.hide();
            } catch (err) {
                showToast(err.message, "error");
                // Revert button on error so they can try again
                fresh.disabled = false;
                fresh.innerHTML = originalHtml;
                fresh.className = originalClasses;
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

        // Add Fee Button
document.getElementById("addFeeBtn")?.addEventListener("click", () => {
    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("feeModal"));
    modal.show();
});

// Fee Type Toggle
document.getElementById('feeType')?.addEventListener('change', function () {
    document.getElementById('discountTypeSection').style.display = this.value === 'discount' ? 'block' : 'none';
    if (this.value === 'vat') {
        document.getElementById('feeLabel').value = 'Less VAT';
        document.getElementById('feeValue').value = '12';
        document.getElementById('discountType').value = 'Percentage';
    }
});

// Save Fee Button
document.getElementById('saveFeeBtn')?.addEventListener('click', async function () {
        // Safety catch
    if (requestData.approval_info?.is_finalized) {
        showToast("Fees are locked because the form is finalized.", "error");
        return;
    }
    const type = document.getElementById('feeType').value;
    const value = parseFloat(document.getElementById('feeValue').value);
    const label = document.getElementById('feeLabel').value;
    const discountType = document.getElementById('discountType').value; // 'Fixed' or 'Percentage'
    const accountNum = document.getElementById('accountNum').value.trim();

    if (!type || !value || !label) {
        showToast("Please fill all required fields.", "error");
        return;
    }

    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Adding...';

    const token = localStorage.getItem("adminToken");
    let endpoint = type === 'additional' ? 'fee' : 'discount';
    
    // Ensure discount type is correctly set when the type is 'discount'
    let body = type === 'additional' 
        ? { label, fee_amount: value, account_num: accountNum || null }
        : { label, discount_amount: value, discount_type: discountType, account_num: accountNum || null };

    try {
        const res = await fetch(`/api/admin/requisition/${requestId}/${endpoint}`, {
            method: 'POST',
            headers: { Authorization: `Bearer ${token}`, "Content-Type": "application/json", Accept: "application/json" },
            body: JSON.stringify(body)
        });
        
        if (!res.ok) throw new Error("Failed to add fee");

        showToast('Fee added successfully', 'success');
        bootstrap.Modal.getInstance(document.getElementById('feeModal')).hide();
        document.getElementById('feeForm').reset();
        document.getElementById('discountTypeSection').style.display = 'none';
        
        // Re-fetch just the data we need to repaint the table
        const fresh = await fetchData();
        requestData.requisition_fees = fresh.data.requisition_fees;
        requestData.fees = fresh.data.fees;

        renderTimeline();
        
        // Re-render the table
        renderResourcesBreakdown(requestData);

    } catch (error) {
        showToast(error.message, "error");
    } finally {
        this.disabled = false;
        this.innerHTML = 'Add';
    }
});

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
                requestData.form_details.status =
                    freshData.data.form_details.status;

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
 * Binds change events to the newly rendered waiver checkboxes.
 */
const attachWaiverHandlers = () => {
    document.querySelectorAll('.waiver-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            handleWaiverChange(this);
        });
    });
};

/**
 * Fires the waive API immediately on checkbox change.
 */
const handleWaiverChange = async (checkbox) => {
        // Safety catch
    if (requestData.approval_info?.is_finalized) {
        showToast("Fees are locked because the form is finalized.", "error");
        return;
    }
    const isChecked = checkbox.checked;
    const type = checkbox.dataset.type; 
    const id = parseInt(checkbox.dataset.id);
    const adminName = checkbox.dataset.adminName;
    const row = checkbox.closest('tr');
    const waivedText = row.querySelector('.waived-by-text');
    const unitPriceCell = row.cells[5]; 
    const totalCell = row.cells[6];
    
    const token = localStorage.getItem("adminToken");
    const adminId = localStorage.getItem("adminId");

    // Optimistic UI update
        if (isChecked) {
            row.classList.add('waived');
            waivedText.innerHTML = `Waived by ${escapeHtml(adminName)}`;
            waivedText.classList.remove('d-none');
            waivedText.classList.add('d-inline-block');
            
            unitPriceCell.classList.add('text-decoration-line-through');
            totalCell.classList.add('text-decoration-line-through');
        } else {
            row.classList.remove('waived');
            waivedText.classList.add('d-none');
            waivedText.classList.remove('d-inline-block');
            
            unitPriceCell.classList.remove('text-decoration-line-through');
            totalCell.classList.remove('text-decoration-line-through');
        }
    updateWaiveAllToggle();

    // Gather all currently checked boxes of each type
    const getChecked = (t) => Array.from(document.querySelectorAll(`.waiver-checkbox[data-type="${t}"]:checked`)).map(cb => parseInt(cb.dataset.id));

    const payload = {
        admin_id: adminId,
        waived_facilities: getChecked('facility'),
        waived_equipment: getChecked('equipment'),
        waived_services: getChecked('service')
    };

    try {
        const res = await fetch(`/api/admin/requisition/${requestId}/waive`, {
            method: 'POST',
            headers: { Authorization: `Bearer ${token}`, "Content-Type": "application/json", Accept: "application/json" },
            body: JSON.stringify(payload)
        });
        if (!res.ok) throw new Error("Failed to update waivers");
        
        const result = await res.json();
        document.getElementById('tableTotalFee').textContent = formatMoney(result.updated_approved_fee);
        
        const fresh = await fetchData();
        requestData.waivers = fresh.data.waivers;
        requestData.requested_items = fresh.data.requested_items;
        renderTimeline();

        showToast("Waiver updated", "success");
    } catch (err) {
        // Revert on failure
        checkbox.checked = !isChecked;
        if (!isChecked) {
            row.classList.add('waived');
            waivedText.innerHTML = `Waived by ${escapeHtml(adminName)}`;
            waivedText.style.display = 'block';
        } else {
            row.classList.remove('waived');
            waivedText.style.display = 'none';
        }
        showToast(err.message, "error");
    }
};

/**
 * Toggles all checkboxes based on the Waive All switch.
 */
const updateWaiveAllToggle = () => {
    const checkboxes = document.querySelectorAll('.waiver-checkbox');
    const waiveAllSwitch = document.getElementById('waiveAllSwitch');
    if (checkboxes.length > 0 && waiveAllSwitch) {
        waiveAllSwitch.checked = Array.from(checkboxes).every(cb => cb.checked);
    }
};

document.getElementById('waiveAllSwitch')?.addEventListener('change', async function() {
    const isChecked = this.checked;
    
    document.querySelectorAll('.waiver-checkbox').forEach(cb => {
        cb.checked = isChecked;
        const row = cb.closest('tr');
        const waivedText = row.querySelector('.waived-by-text');
        const adminName = cb.dataset.adminName;

        // Target the price columns to toggle their strike-through classes
        const unitPriceCell = row.cells[5]; 
        const totalCell = row.cells[6];

        if (isChecked) {
            row.classList.add('waived');
            waivedText.innerHTML = `Waived by ${escapeHtml(adminName)}`;
            waivedText.classList.remove('d-none');
            waivedText.classList.add('d-inline-block');
            
            unitPriceCell.classList.add('text-decoration-line-through');
            totalCell.classList.add('text-decoration-line-through');
        } else {
            row.classList.remove('waived');
            waivedText.classList.add('d-none');
            waivedText.classList.remove('d-inline-block');
            
            unitPriceCell.classList.remove('text-decoration-line-through');
            totalCell.classList.remove('text-decoration-line-through');
        }
    });

    const token = localStorage.getItem("adminToken");
    const adminId = localStorage.getItem("adminId");

    try {
        const res = await fetch(`/api/admin/requisition/${requestId}/waive`, {
            method: 'POST',
            headers: { Authorization: `Bearer ${token}`, "Content-Type": "application/json", Accept: "application/json" },
            body: JSON.stringify({ admin_id: adminId, waive_all: isChecked })
        });
        if (!res.ok) throw new Error("Failed to toggle all waivers");
        
        const result = await res.json();
        document.getElementById('tableTotalFee').textContent = formatMoney(result.updated_approved_fee);
        
        const fresh = await fetchData();
        requestData.waivers = fresh.data.waivers;
        requestData.requested_items = fresh.data.requested_items;
        renderTimeline();

        showToast(isChecked ? "All items waived" : "All waivers removed", "success");
    } catch (err) {
        showToast(err.message, "error");
        renderResourcesBreakdown(requestData);
    }
});

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
            renderBookingDetails(requestData);
            renderAttachments(requestData);
            renderResourcesBreakdown(requestData);
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
                    <br><button class="btn btn-sm btn-danger mt-2" onclick="location.reload()">Retry</button>
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

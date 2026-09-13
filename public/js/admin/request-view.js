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
    let requestId = null;        // The requisition request ID (from URL path)
    let adminToken = null;       // Admin auth token (from localStorage)
    let requestData = null;      // Full request payload from the API
    let timelineFilter = "all";  // Active timeline filter (all|comment|fee|approval)

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
     * Display a temporary toast notification at the bottom-left.
     * @param {string} message - Text to display (escaped internally)
     * @param {"success"|"error"} [type="success"]
     */
    const showToast = (message, type = "success") => {
        const toast = document.createElement("div");
        toast.className =
            "position-fixed bottom-0 start-0 m-3 p-3 rounded text-white";
        toast.style.cssText = `z-index:1100;background:${type === "success" ? "#004080" : "#dc3545"};opacity:0;transition:opacity 0.3s`;
        toast.innerHTML = `<i class="bi ${type === "success" ? "bi-check-circle" : "bi-exclamation-circle"} me-2"></i>${escapeHtml(message)}`;
        document.body.appendChild(toast);

        requestAnimationFrame(() => (toast.style.opacity = "1"));
        setTimeout(() => {
            toast.style.opacity = "0";
            setTimeout(() => toast.remove(), 300);
        }, 3000);
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
        if (!response.ok)
            throw new Error(result.error || result.message || "Action failed");
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
     * Render the request ID and colored status badge in the page header.
     * @param {Object} data - Full request payload
     */
    const renderHeader = (data) => {
        document.getElementById("requestIdDisplay").textContent = String(
            data.request_id,
        ).padStart(4, "0");

        const status = data.form_details.status;
        document.getElementById("statusBadgeContainer").innerHTML =
            `<span class="badge" style="background:${status.color};color:white;padding:8px 16px;font-size:0.9rem">${status.name}</span>`;
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

        let html = "";

        // Approve/Reject for pending statuses (1, 2)
        if ([1, 2].includes(statusId)) {
            html = `
                <button class="btn" id="approveBtn"><i class="bi bi-check-lg"></i> Approve</button>
                <button class="btn" id="rejectBtn"><i class="bi bi-x-lg"></i> Reject</button>
            `;
        }

        // Head admin actions
        if ([1, 2].includes(statusId)) {
            html += `<button class="btn" id="finalizeBtn"><i class="bi bi-check-circle"></i> Finalize</button>`;
        } else if (statusId === 3) {
            html = `<button class="btn" id="markScheduledBtn"><i class="bi bi-calendar-check"></i> Mark Scheduled</button>`;
        } else if (statusId === 4) {
            html = `<button class="btn" id="markOngoingBtn"><i class="bi bi-play-circle"></i> Mark Ongoing</button>`;
        }

        // Close form for non-terminal
        if (![7, 8, 9].includes(statusId)) {
            html += `<button class="btn" id="closeFormBtn"><i class="bi bi-x-circle"></i> Close</button>`;
        }

        container.innerHTML =
            html || '<span class="text-muted">No actions available</span>';
        // Bind events
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
                    showToast("Request approved");
                    setTimeout(() => location.reload(), 1000);
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
                    showToast("Request rejected");
                    setTimeout(
                        () => (location.href = "/admin/manage-requests"),
                        1000,
                    );
                },
            }),
        );

        document.getElementById("finalizeBtn")?.addEventListener("click", () =>
            showActionModal({
                title: "Finalize Request",
                body: `<div class="row g-2 mb-3">
                     <div class="col"><div class="alert alert-success mb-0">
                       <strong>Approvals:</strong> ${approvalInfo.approval_count}
                     </div></div>
                     <div class="col"><div class="alert alert-danger mb-0">
                       <strong>Rejections:</strong> ${approvalInfo.rejection_count}
                     </div></div>
                   </div>
                   <p class="text-center fw-bold mb-0">Are you sure? This action cannot be undone.</p>`,
                confirmClass: "btn-primary",
                confirmText: "Finalize",
                onConfirm: async () => {
                    await postAction("finalize");
                    showToast("Request finalized");
                    setTimeout(() => location.reload(), 1000);
                },
            }),
        );

        document
            .getElementById("markScheduledBtn")
            ?.addEventListener("click", () =>
                showActionModal({
                    title: "Mark as Scheduled",
                    body: `<label class="form-label">Official Receipt Number *</label>
                   <input type="text" class="form-control" id="officialReceiptNum" required>`,
                    confirmClass: "btn-primary",
                    confirmText: "Confirm",
                    onConfirm: async () => {
                        const receiptNum = document
                            .getElementById("officialReceiptNum")
                            .value.trim();
                        if (!receiptNum)
                            throw new Error("Please enter receipt number");
                        await postAction("mark-scheduled", {
                            official_receipt_num: receiptNum,
                        });
                        showToast("Marked as scheduled");
                        setTimeout(() => location.reload(), 1000);
                    },
                }),
            );

        document
            .getElementById("markOngoingBtn")
            ?.addEventListener("click", () => updateStatus("Ongoing"));

        document.getElementById("closeFormBtn")?.addEventListener("click", () =>
            showActionModal({
                title: "Close Form",
                body: `<div class="text-center">
                     <i class="bi bi-exclamation-triangle fs-1 text-danger mb-3 d-block"></i>
                     <p class="mb-0">Are you sure you want to close this form?</p>
                   </div>`,
                confirmClass: "btn-danger",
                confirmText: "Close Form",
                onConfirm: async () => {
                    await postAction("close");
                    showToast("Form closed");
                    setTimeout(() => (location.href = "/admin/calendar"), 1000);
                },
            }),
        );
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
            <div class="letter-meta">
                ${escapeHtml(schedule.formatted.start)} &mdash; ${escapeHtml(schedule.formatted.end)}
                &middot; ${data.duration_hours} hr${data.duration_hours > 1 ? "s" : ""}
                ${data.is_multi_day ? " &middot; multi-day" : ""}
            </div>
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

        <div class="letter-section">
            <div class="letter-section-label">Purpose &amp; Participants</div>
            <div class="letter-item">
                <span class="text-muted">Purpose</span>
                <span class="fw-medium">${escapeHtml(form.purpose || "N/A")}</span>
            </div>
            <div class="letter-item">
                <span class="text-muted">Participants</span>
                <span class="fw-medium">${form.num_participants || 0}</span>
            </div>
            <div class="letter-item">
                <span class="text-muted">Tables / Chairs</span>
                <span class="fw-medium">${form.num_tables || 0} / ${form.num_chairs || 0}</span>
            </div>
            <div class="letter-item">
                <span class="text-muted">Microphones</span>
                <span class="fw-medium">${form.num_microphones || 0}</span>
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
    `;

        const facilities = data.requested_items.facilities || [];
        const equipment = data.requested_items.equipment || [];

        document.getElementById("eventDetails").innerHTML = `
        <div class="mb-3">
            <div class="fw-bold text-primary small">Start</div>
            <div>${formatDateTime(schedule.start_date, schedule.start_time)}</div>
        </div>
        <div class="mb-3">
            <div class="fw-bold text-primary small">End</div>
            <div>${formatDateTime(schedule.end_date, schedule.end_time)}</div>
        </div>
        ${
            facilities.length
                ? `<div class="mb-3">
                    <div class="fw-bold text-primary small">Facilities</div>
                    ${facilities
                        .map(
                            (
                                f,
                            ) => `<div class="d-flex justify-content-between small">
                                <span>${escapeHtml(f.name)}</span>
                                <span class="${f.is_waived ? "text-muted text-decoration-line-through" : ""}">
                                    ${formatMoney(f.total_fee)}
                                </span>
                            </div>`,
                        )
                        .join("")}
                </div>`
                : ""
        }
        ${
            equipment.length
                ? `<div class="mb-3">
                    <div class="fw-bold text-primary small">Equipment</div>
                    ${equipment
                        .map(
                            (
                                e,
                            ) => `<div class="d-flex justify-content-between small">
                                <span>${escapeHtml(e.name)} ${e.quantity > 1 ? `&times;${e.quantity}` : ""}</span>
                                <span class="${e.is_waived ? "text-muted text-decoration-line-through" : ""}">
                                    ${formatMoney(e.total_fee)}
                                </span>
                            </div>`,
                        )
                        .join("")}
                </div>`
                : ""
        }
        <div class="pt-2 border-top">
            <div class="fw-bold text-primary small">Total Fee</div>
            <div class="fs-4 fw-bold text-primary">${formatMoney(data.fees.approved_fee)}</div>
        </div>
    `;

        const docs = data.documents;
        document.getElementById("attachmentsContainer").innerHTML = `
        <div class="d-flex flex-column gap-2">
            <a href="${docs.formal_letter.url || "#"}" target="_blank"
               class="btn btn-sm ${docs.formal_letter.url ? "btn-outline-primary" : "btn-outline-secondary disabled"}">
                <i class="bi bi-file-text me-1"></i> Formal Letter
            </a>
            <a href="${docs.proof_of_payment.url || "#"}" target="_blank"
               class="btn btn-sm ${docs.proof_of_payment.url ? "btn-outline-primary" : "btn-outline-secondary disabled"}">
                <i class="bi bi-receipt me-1"></i> Proof of Payment
            </a>
            ${
                docs.official_receipt.number
                    ? `<a href="/official-receipt/${requestId}" target="_blank" class="btn btn-sm btn-outline-success">
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
                        ${item.type === "approval" ? "✅ Approved" : "❌ Rejected"} at stage ${a.stage}
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
            showToast(`Status updated to ${statusName}`);
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
            showToast("Comment added");
        } catch (error) {
            showToast(error.message, "error");
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

        badge.textContent = `${current}/${max}`;
        badge.className = `badge ${tracker.is_fully_approved ? "bg-success" : "bg-primary"}`;

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
                color: "info",
                stage: 1,
            },
            {
                key: "stage2",
                label: "Final Approving Officer",
                icon: "bi-shield-check",
                color: "primary",
                stage: 2,
            },
            {
                key: "stage3",
                label: "Issuing Officer",
                icon: "bi-box-seam",
                color: "warning",
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
                  : "bg-warning text-dark";
            const badgeLabel = isApproved
                ? "Approved"
                : isRejected
                  ? "Rejected"
                  : "Pending";

            const overlayIcon = isApproved
                ? `<i class="bi bi-check-circle-fill text-success position-absolute"
                    style="bottom:-2px;right:-2px;font-size:14px;background:white;border-radius:50%;"></i>`
                : isRejected
                  ? `<i class="bi bi-x-circle-fill text-danger position-absolute"
                    style="bottom:-2px;right:-2px;font-size:14px;background:white;border-radius:50%;"></i>`
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
                <i class="bi ${cat.icon} text-${cat.color} me-2"></i>
                <span class="text-uppercase fw-bold text-muted"
                      style="font-size:0.7rem;letter-spacing:0.05em;">
                    ${cat.label}
                </span>
                <span class="badge bg-${cat.color} ms-auto" style="font-size:0.65rem;">
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
                requestData.comments = freshData.data.comments;
                requestData.requisition_fees = freshData.data.requisition_fees;
                requestData.approval_history = freshData.data.approval_history;
                renderTimeline();
                showToast("Timeline refreshed");
            });

        document
            .getElementById("sendCommentBtn")
            ?.addEventListener("click", addComment);
        document
            .getElementById("commentInput")
            ?.addEventListener("keydown", (e) => {
                if (e.key === "Enter") addComment();
            });

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
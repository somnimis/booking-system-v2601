/**
 * requests-list.js
 *
 * Shared UI utilities for admin requisition list views
 * (pending-requests.blade.php, actionable-requests.blade.php).
 *
 * Exposes window.RequestsList with:
 *   - escapeHtml(text)
 *   - fetchList(url, token) → Promise<{data, meta}>
 *   - renderCards(requisitions, container, { onClick })
 *   - renderPagination(meta, container, { onPageChange })
 *   - showLoading(container, message)
 *   - showError(container, message, retryFn)
 *   - showEmpty(container, message)
 *
 * Callers own state (page, perPage, sort) and URL construction.
 */
(function () {
    'use strict';

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        const div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }

    function showLoading(container, message = 'Loading requisitions...') {
        if (!container) return;
        container.innerHTML = `
            <div class="text-center text-muted py-4">
                <div class="spinner-border spinner-border-sm" role="status"></div>
                <div class="mt-2">${escapeHtml(message)}</div>
            </div>
        `;
    }

    function showEmpty(container, message = 'No requisitions found') {
        if (!container) return;
        container.innerHTML = `
            <div class="text-center text-muted py-4 small">
                <i class="bi bi-inbox fs-4"></i>
                <div class="mt-2">${escapeHtml(message)}</div>
            </div>
        `;
    }

    function showError(container, message, retryFn) {
        if (!container) return;
        const retryButton = typeof retryFn === 'function'
            ? `<div class="mt-2">
                   <button class="btn btn-sm btn-outline-danger" data-retry>
                       <i class="bi bi-arrow-repeat"></i> Retry
                   </button>
               </div>`
            : '';

        container.innerHTML = `
            <div class="text-center text-danger py-4">
                <i class="bi bi-exclamation-triangle fs-4"></i>
                <div class="mt-2">Failed to load requisitions</div>
                <small class="text-muted">${escapeHtml(message)}</small>
                ${retryButton}
            </div>
        `;

        if (typeof retryFn === 'function') {
            const btn = container.querySelector('[data-retry]');
            if (btn) btn.addEventListener('click', retryFn);
        }
    }

    function fetchList(url, token) {
        return fetch(url, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            },
            credentials: 'include'
        })
            .then(response => {
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                return response.json();
            })
            .then(data => {
                if (data.success === false) {
                    throw new Error(data.message || 'Failed to load data');
                }
                return data;
            });
    }

    function renderCards(requisitions, container, options = {}) {
        if (!container) return;
        const onClick = options.onClick;

        if (!requisitions || requisitions.length === 0) {
            showEmpty(container);
            return;
        }

        const cardsHTML = requisitions.map(req => {
            const requestId = req.request_id;
            const requesterName = escapeHtml(req.requester?.name);
            const organization = escapeHtml(req.requester?.organization);
            const statusName = escapeHtml(req.status?.name);
            const statusColor = req.status?.color || '#6c757d';
            const schedule = escapeHtml(req.schedule?.display);

            const dateSubmitted = req.created_at
                ? new Date(req.created_at).toLocaleDateString('en-US', {
                    year: 'numeric', month: 'short', day: 'numeric'
                })
                : 'Date unknown';

            const eventTitle = escapeHtml(
                req.event_title && req.event_title.trim() !== '' ? req.event_title : 'No Event Title'
            );
            const eventDetails = escapeHtml(
                req.event_details && req.event_details.trim() !== '' ? req.event_details : 'No Description'
            );

            return `
                <div class="requisition-card clickable-requisition-item py-2" data-request-id="${requestId}">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <div class="d-flex align-items-center flex-wrap gap-1">
                            <span class="requester-name">${requesterName}</span>
                            <span class="text-muted small">- ${organization}</span>
                        </div>
                        <span class="status-badge" style="background-color: ${statusColor}; color: white; border: none;">
                            ${statusName}
                        </span>
                    </div>

                    <div class="mb-2 small">
                        <strong>${eventTitle}</strong> — ${eventDetails}
                    </div>

                    <div class="schedule-info">
                        <i class="bi bi-calendar3 me-1"></i> ${schedule}
                    </div>

                    <div class="schedule-info mt-1">
                        <i class="bi bi-clock-history me-1"></i> Submitted: ${dateSubmitted}
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-1">
                        <span class="request-id">#${String(requestId).padStart(4, '0')}</span>
                        <i class="bi bi-chevron-right text-primary" style="font-size: 0.8rem;"></i>
                    </div>
                </div>
            `;
        }).join('');

        container.innerHTML = cardsHTML;

        container.querySelectorAll('.clickable-requisition-item').forEach(item => {
            item.addEventListener('click', function () {
                const requestId = this.getAttribute('data-request-id');
                if (!requestId) return;
                if (typeof onClick === 'function') {
                    onClick(requestId);
                } else {
                    window.location.href = `/admin/requisition/${requestId}`;
                }
            });
        });
    }

    function renderPagination(meta, container, options = {}) {
        if (!container) return;

        if (!meta || meta.total === 0 || meta.last_page === 0) {
            container.style.display = 'none';
            container.innerHTML = '';
            return;
        }

        const onPageChange = typeof options.onPageChange === 'function'
            ? options.onPageChange
            : () => { };

        container.style.display = 'flex';
        container.innerHTML = `
            <div class="pagination-info">
                Showing ${meta.from || 0} to ${meta.to || 0} of ${meta.total} entries
            </div>
            <div class="pagination-controls"></div>
        `;

        const controls = container.querySelector('.pagination-controls');
        let buttonsHTML = '';

        buttonsHTML += `
            <button class="btn-pagination" data-page="${meta.current_page - 1}" ${meta.current_page === 1 ? 'disabled' : ''}>
                <i class="bi bi-chevron-left"></i> Previous
            </button>
        `;

        if (meta.last_page <= 5) {
            for (let i = 1; i <= meta.last_page; i++) {
                buttonsHTML += `
                    <button class="btn-pagination ${i === meta.current_page ? 'active' : ''}" data-page="${i}">
                        ${i}
                    </button>
                `;
            }
        } else {
            const startPage = Math.max(1, meta.current_page - 2);
            const endPage = Math.min(meta.last_page, startPage + 4);

            if (startPage > 1) {
                buttonsHTML += `<button class="btn-pagination" data-page="1">1</button>`;
                if (startPage > 2) buttonsHTML += `<span class="px-1">...</span>`;
            }

            for (let i = startPage; i <= endPage; i++) {
                buttonsHTML += `
                    <button class="btn-pagination ${i === meta.current_page ? 'active' : ''}" data-page="${i}">
                        ${i}
                    </button>
                `;
            }

            if (endPage < meta.last_page) {
                if (endPage < meta.last_page - 1) buttonsHTML += `<span class="px-1">...</span>`;
                buttonsHTML += `<button class="btn-pagination" data-page="${meta.last_page}">${meta.last_page}</button>`;
            }
        }

        buttonsHTML += `
            <button class="btn-pagination" data-page="${meta.current_page + 1}" ${meta.current_page === meta.last_page ? 'disabled' : ''}>
                Next <i class="bi bi-chevron-right"></i>
            </button>
        `;

        controls.innerHTML = buttonsHTML;

        controls.querySelectorAll('.btn-pagination[data-page]').forEach(btn => {
            btn.addEventListener('click', function () {
                const page = parseInt(this.getAttribute('data-page'), 10);
                if (!page || page < 1) return;
                onPageChange(page);
            });
        });
    }

    window.RequestsList = {
        escapeHtml,
        fetchList,
        renderCards,
        renderPagination,
        showLoading,
        showError,
        showEmpty
    };
})();
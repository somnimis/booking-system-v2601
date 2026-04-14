@extends('layouts.admin')

@section('title', 'User Feedback Management')

@section('content')
    <main main="id">
        <div class="container-fluid px-4">

            <!-- Statistics Cards Row -->
            <div class="row g-3 mb-4">
                <!-- Total Feedback Card -->
                <div class="col-md-3 col-sm-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Total Feedback</h6>
                                    <p class="mb-0 fw-bold" id="totalCount">0</p>
                                </div>
                                <div class="rounded-circle p-3" style="background-color: rgba(19, 91, 163, 0.1);">
                                    <i class="fas fa-comments fa-2x" style="color: var(--cpu-primary, #003366);"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Average System Performance Card -->
                <div class="col-md-3 col-sm-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">System Performance</h6>
                                    <p class="mb-0 fw-bold" id="avgSystemPerformance">-</p>
                                </div>
                                <div class="rounded-circle p-3" style="background-color: rgba(19, 91, 163, 0.1);">
                                    <i class="fas fa-chart-line fa-2x" style="color: var(--cpu-primary, #003366);"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Average Booking Experience Card -->
                <div class="col-md-3 col-sm-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Booking Experience</h6>
                                    <p class="mb-0 fw-bold" id="avgBookingExperience">-</p>
                                </div>
                                <div class="rounded-circle p-3" style="background-color: rgba(19, 91, 163, 0.1);">
                                    <i class="fas fa-calendar-check fa-2x" style="color: var(--cpu-primary, #003366);"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Would Recommend Card -->
                <div class="col-md-3 col-sm-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Would Recommend</h6>
                                    <p class="mb-0 fw-bold" id="wouldRecommend">-</p>
                                </div>
                                <div class="rounded-circle p-3" style="background-color: rgba(19, 91, 163, 0.1);">
                                    <i class="fas fa-thumbs-up fa-2x" style="color: var(--cpu-primary, #003366);"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Feedback Listings Card -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0 fw-bold" style="color: var(--cpu-primary, #003366);">
                            <i class="fas fa-list me-2"></i>All Feedback Entries
                        </h5>
                        <div class="d-flex gap-2">
                            <select id="perPageSelect" class="form-select form-select-sm" style="width: auto;">
                                <option value="10">10 per page</option>
                                <option value="20">20 per page</option>
                                <option value="50">50 per page</option>
                            </select>
                            <div class="input-group input-group-sm" style="width: 250px;">
                                <span class="input-group-text bg-white">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" id="searchInput" class="form-control" placeholder="Search feedback...">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Loading Spinner -->
                    <div id="loadingSpinner" class="text-center py-5">
                        <div class="spinner-border" style="color: var(--cpu-primary, #003366);" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading feedback data...</p>
                    </div>

                    <!-- Feedback Grid Container -->
                    <div id="feedbackListContainer" style="display: none;">
                        <div id="feedbackItems" class="row g-3 feedback-grid"></div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                            <div id="paginationInfo" class="text-muted small"></div>
                            <nav>
                                <ul class="pagination mb-0" id="pagination"></ul>
                            </nav>
                        </div>
                    </div>

                    <!-- No Data Message -->
                    <div id="noDataMessage" class="text-center py-5" style="display: none;">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No feedback entries found.</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <style>
        .feedback-item {
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
        }

        .feedback-item:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }

        .rating-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            background-color: #e9ecef;
            color: #495057;
        }

        .rating-badge.excellent,
        .rating-badge.outstanding,
        .rating-badge.very-easy,
        .rating-badge.very-likely {
            background-color: #d4edda;
            color: #155724;
        }

        .rating-badge.very-good,
        .rating-badge.good,
        .rating-badge.easy,
        .rating-badge.likely {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .rating-badge.satisfactory,
        .rating-badge.neutral {
            background-color: #fff3cd;
            color: #856404;
        }

        .rating-badge.fair,
        .rating-badge.difficult,
        .rating-badge.unlikely {
            background-color: #ffe5d9;
            color: #c95a0f;
        }

        .rating-badge.poor,
        .rating-badge.very-difficult,
        .rating-badge.very-unlikely {
            background-color: #f8d7da;
            color: #721c24;
        }

        .page-link {
            color: var(--cpu-primary, #003366);
        }

        .page-item.active .page-link {
            background-color: var(--cpu-primary, #003366);
            border-color: var(--cpu-primary, #003366);
            color: white;
        }

        .page-link:hover {
            color: var(--cpu-primary-hover, #004a94);
        }
    </style>
@endsection

@section('scripts')
    <script>
        class FeedbackManager {
            constructor() {
                this.currentPage = 1;
                this.perPage = 10;
                this.searchTerm = '';
                this.totalPages = 1;
                this.totalItems = 0;
                this.init();
            }

            init() {
                this.setupEventListeners();
                this.loadFeedback();
            }

            setupEventListeners() {
                // Per page change
                document.getElementById('perPageSelect').addEventListener('change', (e) => {
                    this.perPage = parseInt(e.target.value);
                    this.currentPage = 1;
                    this.loadFeedback();
                });

                // Search input with debounce
                const searchInput = document.getElementById('searchInput');
                let debounceTimer;
                searchInput.addEventListener('input', (e) => {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => {
                        this.searchTerm = e.target.value;
                        this.currentPage = 1;
                        this.loadFeedback();
                    }, 500);
                });
            }

            async loadFeedback() {
                this.showLoading();

                try {
                    const token = localStorage.getItem('adminToken');
                    if (!token) {
                        throw new Error('No authentication token found');
                    }

                    let url = `/api/feedback?page=${this.currentPage}&per_page=${this.perPage}`;
                    if (this.searchTerm) {
                        url += `&search=${encodeURIComponent(this.searchTerm)}`;
                    }

                    const response = await fetch(url, {
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Accept': 'application/json'
                        }
                    });

                    if (!response.ok) throw new Error('Failed to fetch feedback');

                    const data = await response.json();

                    // Handle both paginated and non-paginated responses
                    let feedbackData, paginationData;
                    if (data.data && data.current_page !== undefined) {
                        // Paginated response
                        feedbackData = data.data;
                        paginationData = data;
                    } else if (Array.isArray(data)) {
                        // Non-paginated response
                        feedbackData = data;
                        paginationData = {
                            total: data.length,
                            current_page: 1,
                            per_page: data.length,
                            last_page: 1
                        };
                    } else {
                        feedbackData = [];
                        paginationData = { total: 0, current_page: 1, per_page: this.perPage, last_page: 1 };
                    }

                    this.totalItems = paginationData.total;
                    this.totalPages = paginationData.last_page;

                    this.updateStatistics(feedbackData);
                    this.renderFeedbackList(feedbackData);
                    this.renderPagination(paginationData);

                    this.hideLoading();
                } catch (error) {
                    console.error('Error loading feedback:', error);
                    this.showError('Failed to load feedback data. Please try again.');
                    this.hideLoading();
                }
            }

            updateStatistics(feedbackData) {
                // Update total count
                document.getElementById('totalCount').textContent = this.totalItems;

                if (feedbackData.length === 0) {
                    document.getElementById('avgSystemPerformance').textContent = '-';
                    document.getElementById('avgBookingExperience').textContent = '-';
                    document.getElementById('wouldRecommend').textContent = '-';
                    return;
                }

                // Calculate averages for ratings
                const systemPerformanceMap = {
                    'poor': 1,
                    'fair': 2,
                    'satisfactory': 3,
                    'very good': 4,
                    'outstanding': 5
                };

                const bookingExperienceMap = {
                    'poor': 1,
                    'fair': 2,
                    'good': 3,
                    'very good': 4,
                    'excellent': 5
                };

                const usabilityMap = {
                    'very unlikely': 1,
                    'unlikely': 2,
                    'neutral': 3,
                    'likely': 4,
                    'very likely': 5
                };

                let systemSum = 0;
                let bookingSum = 0;
                let usabilitySum = 0;
                let usabilityCount = 0;

                feedbackData.forEach(feedback => {
                    systemSum += systemPerformanceMap[feedback.system_performance] || 0;
                    bookingSum += bookingExperienceMap[feedback.booking_experience] || 0;
                    if (feedback.useability) {
                        usabilitySum += usabilityMap[feedback.useability] || 0;
                        usabilityCount++;
                    }
                });

                const avgSystem = systemSum / feedbackData.length;
                const avgBooking = bookingSum / feedbackData.length;
                const avgUsability = usabilityCount > 0 ? usabilitySum / usabilityCount : 0;

                // Get rating labels
                const getSystemRatingLabel = (avg) => {
                    if (avg >= 4.5) return 'Outstanding';
                    if (avg >= 3.5) return 'Very Good';
                    if (avg >= 2.5) return 'Satisfactory';
                    if (avg >= 1.5) return 'Fair';
                    return 'Poor';
                };

                const getBookingRatingLabel = (avg) => {
                    if (avg >= 4.5) return 'Excellent';
                    if (avg >= 3.5) return 'Very Good';
                    if (avg >= 2.5) return 'Good';
                    if (avg >= 1.5) return 'Fair';
                    return 'Poor';
                };

                const getRecommendLabel = (avg) => {
                    if (avg >= 4.5) return 'Very Likely';
                    if (avg >= 3.5) return 'Likely';
                    if (avg >= 2.5) return 'Neutral';
                    if (avg >= 1.5) return 'Unlikely';
                    return 'Very Unlikely';
                };

                document.getElementById('avgSystemPerformance').textContent = getSystemRatingLabel(avgSystem);
                document.getElementById('avgBookingExperience').textContent = getBookingRatingLabel(avgBooking);
                document.getElementById('wouldRecommend').textContent = getRecommendLabel(avgUsability);
            }

            getRatingClass(rating, type) {
                const ratings = {
                    'system_performance': {
                        'poor': 'poor',
                        'fair': 'fair',
                        'satisfactory': 'satisfactory',
                        'very good': 'very-good',
                        'outstanding': 'outstanding'
                    },
                    'booking_experience': {
                        'poor': 'poor',
                        'fair': 'fair',
                        'good': 'good',
                        'very good': 'very-good',
                        'excellent': 'excellent'
                    },
                    'ease_of_use': {
                        'very difficult': 'very-difficult',
                        'difficult': 'difficult',
                        'neutral': 'neutral',
                        'easy': 'easy',
                        'very easy': 'very-easy'
                    },
                    'useability': {
                        'very unlikely': 'very-unlikely',
                        'unlikely': 'unlikely',
                        'neutral': 'neutral',
                        'likely': 'likely',
                        'very likely': 'very-likely'
                    }
                };

                return ratings[type]?.[rating] || '';
            }

            formatDate(dateString) {
                const date = new Date(dateString);
                return date.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }

            formatDateShort(dateString) {
                const date = new Date(dateString);
                return date.toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric'
                });
            }

            renderFeedbackList(feedbackData) {
                const container = document.getElementById('feedbackItems');

                if (feedbackData.length === 0) {
                    document.getElementById('feedbackListContainer').style.display = 'none';
                    document.getElementById('noDataMessage').style.display = 'block';
                    return;
                }

                document.getElementById('feedbackListContainer').style.display = 'block';
                document.getElementById('noDataMessage').style.display = 'none';

                const feedbackHtml = feedbackData.map((feedback, index) => {
                    const hasLongFeedback = feedback.additional_feedback && feedback.additional_feedback.length > 100;
                    const shortFeedback = feedback.additional_feedback ?
                        (feedback.additional_feedback.length > 100 ?
                            feedback.additional_feedback.substring(0, 100) + '...' :
                            feedback.additional_feedback) : '';

                    return `
            <div class="col-sm-6 col-md-6 col-lg-4 col-xl-4">
                <div class="feedback-item h-100 p-3 border rounded bg-white">
                    <!-- Header: Email and Date -->
                    <div class="pb-2 mb-2 border-bottom">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            ${feedback.email ? `
                                <div class="d-flex align-items-center gap-1 flex-grow-1 me-2">
                                    <i class="fas fa-envelope flex-shrink-0" style="color: var(--cpu-primary, #003366); font-size: 0.7rem;"></i>
                                    <small class="text-truncate" style="max-width: 140px;" title="${this.escapeHtml(feedback.email)}">
                                        ${this.escapeHtml(feedback.email.length > 20 ? feedback.email.substring(0, 20) + '...' : feedback.email)}
                                    </small>
                                </div>
                            ` : `
                                <div class="d-flex align-items-center gap-1 flex-grow-1 me-2">
                                    <i class="fas fa-envelope flex-shrink-0" style="color: #6c757d; font-size: 0.7rem;"></i>
                                    <small class="text-muted">No email provided</small>
                                </div>
                            `}
                            <div class="d-flex align-items-center gap-1 flex-shrink-0">
                                <i class="fas fa-calendar-alt flex-shrink-0" style="color: var(--cpu-primary, #003366); font-size: 0.7rem;"></i>
                                <small class="text-muted text-nowrap">${this.formatDateShort(feedback.created_at)}</small>
                            </div>
                        </div>
                        ${feedback.request_id ? `
                            <div class="mt-1">
                                <small class="text-muted">
                                    <i class="fas fa-receipt me-1" style="font-size: 0.7rem;"></i>
                                    Request #${feedback.request_id}
                                </small>
                            </div>
                        ` : ''}
                    </div>

                    <!-- Ratings - Vertical Stack -->
                    <div class="mb-2">
                        <!-- System Performance -->
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                <i class="fas fa-tachometer-alt flex-shrink-0" style="color: var(--cpu-primary, #003366); width: 20px;"></i>
                                <span class="small fw-semibold text-nowrap">System Performance:</span>
                            </div>
                            <span class="rating-badge ${this.getRatingClass(feedback.system_performance, 'system_performance')} text-nowrap">
                                ${feedback.system_performance}
                            </span>
                        </div>

                        <!-- Booking Experience -->
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                <i class="fas fa-calendar-check flex-shrink-0" style="color: var(--cpu-primary, #003366); width: 20px;"></i>
                                <span class="small fw-semibold text-nowrap">Booking Experience:</span>
                            </div>
                            <span class="rating-badge ${this.getRatingClass(feedback.booking_experience, 'booking_experience')} text-nowrap">
                                ${feedback.booking_experience}
                            </span>
                        </div>

                        <!-- Ease of Use -->
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                <i class="fas fa-smile flex-shrink-0" style="color: var(--cpu-primary, #003366); width: 20px;"></i>
                                <span class="small fw-semibold text-nowrap">Ease of Use:</span>
                            </div>
                            <span class="rating-badge ${this.getRatingClass(feedback.ease_of_use, 'ease_of_use')} text-nowrap">
                                ${feedback.ease_of_use}
                            </span>
                        </div>

                        <!-- Would Recommend -->
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                <i class="fas fa-thumbs-up flex-shrink-0" style="color: var(--cpu-primary, #003366); width: 20px;"></i>
                                <span class="small fw-semibold text-nowrap">Would Recommend:</span>
                            </div>
                            <span class="rating-badge ${this.getRatingClass(feedback.useability, 'useability')} text-nowrap">
                                ${feedback.useability}
                            </span>
                        </div>
                    </div>

                    <!-- Additional Feedback -->
                    <div class="mt-2 pt-2 border-top">
                        <div class="d-flex align-items-start gap-2">
                            <i class="fas fa-quote-left mt-1 flex-shrink-0" style="color: var(--cpu-primary, #003366); font-size: 0.7rem;"></i>
                            ${feedback.additional_feedback ? `
                                <div class="flex-grow-1">
                                    <p class="mb-0 text-muted small" style="font-size: 0.75rem; line-height: 1.3; word-wrap: break-word; overflow-wrap: break-word; word-break: break-word;">
                                        "${shortFeedback}"
                                    </p>
                                    ${hasLongFeedback ? `
                                        <button class="btn btn-link btn-sm p-0 mt-1 read-more-btn" 
                                            data-feedback-index="${index}"
                                            style="font-size: 0.7rem; color: var(--cpu-primary, #003366); text-decoration: none;">
                                            Read more
                                        </button>
                                    ` : ''}
                                </div>
                            ` : `
                                <p class="mb-0 text-muted small" style="font-size: 0.75rem;">
                                    <em>No additional feedback</em>
                                </p>
                            `}
                        </div>
                    </div>
                </div>
            </div>
        `}).join('');

                container.innerHTML = feedbackHtml;

                // Attach read more event listeners
                const readMoreBtns = document.querySelectorAll('.read-more-btn');
                readMoreBtns.forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        const index = parseInt(btn.dataset.feedbackIndex);
                        const feedback = feedbackData[index];
                        if (feedback && feedback.additional_feedback) {
                            this.showSimpleModal(feedback.additional_feedback);
                        }
                    });
                });
            }
            escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            showSimpleModal(content) {
                // Remove existing modal if any
                const existingModal = document.getElementById('simpleModal');
                if (existingModal) {
                    existingModal.remove();
                }

                // Create modal HTML
                const modalHtml = `
            <div id="simpleModal" style="
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
            ">
                <div style="
                    background: white;
                    max-width: 500px;
                    width: 90%;
                    border-radius: 8px;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
                ">
                    <div style="
                        padding: 15px 20px;
                        border-bottom: 1px solid #dee2e6;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                    ">
                        <h6 style="margin: 0; color: var(--cpu-primary, #003366);">
                            <i class="fas fa-quote-left me-2"></i>Additional Feedback
                        </h6>
                        <button id="closeModalBtn" style="
                            background: none;
                            border: none;
                            font-size: 1.5rem;
                            cursor: pointer;
                            color: #6c757d;
                            padding: 0;
                            line-height: 1;
                        ">&times;</button>
                    </div>
                    <div style="padding: 20px;">
                        <p style="margin: 0; line-height: 1.5; word-wrap: break-word;">${this.escapeHtml(content)}</p>
                    </div>
                </div>
            </div>
        `;

                // Add modal to body
                document.body.insertAdjacentHTML('beforeend', modalHtml);

                // Close modal function
                const closeModal = () => {
                    const modal = document.getElementById('simpleModal');
                    if (modal) modal.remove();
                };

                // Close on X button click
                document.getElementById('closeModalBtn').addEventListener('click', closeModal);

                // Close on background click
                document.getElementById('simpleModal').addEventListener('click', (e) => {
                    if (e.target.id === 'simpleModal') {
                        closeModal();
                    }
                });

                // Close on ESC key
                const escHandler = (e) => {
                    if (e.key === 'Escape') {
                        closeModal();
                        document.removeEventListener('keydown', escHandler);
                    }
                };
                document.addEventListener('keydown', escHandler);
            }

            renderPagination(pagination) {
                const paginationElement = document.getElementById('pagination');
                const infoElement = document.getElementById('paginationInfo');

                const currentPage = pagination.current_page;
                const lastPage = pagination.last_page;
                const total = pagination.total;
                const perPage = pagination.per_page;

                // Update info text
                const start = (currentPage - 1) * perPage + 1;
                const end = Math.min(currentPage * perPage, total);
                infoElement.textContent = `Showing ${start} to ${end} of ${total} entries`;

                if (lastPage <= 1) {
                    paginationElement.innerHTML = '';
                    return;
                }

                let paginationHtml = '';

                // Previous button
                paginationHtml += `
                <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage - 1}" tabindex="-1">Previous</a>
                </li>
            `;

                // Page numbers
                const maxVisible = 5;
                let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
                let endPage = Math.min(lastPage, startPage + maxVisible - 1);

                if (endPage - startPage < maxVisible - 1) {
                    startPage = Math.max(1, endPage - maxVisible + 1);
                }

                if (startPage > 1) {
                    paginationHtml += `
                    <li class="page-item">
                        <a class="page-link" href="#" data-page="1">1</a>
                    </li>
                    ${startPage > 2 ? '<li class="page-item disabled"><span class="page-link">...</span></li>' : ''}
                `;
                }

                for (let i = startPage; i <= endPage; i++) {
                    paginationHtml += `
                    <li class="page-item ${i === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `;
                }

                if (endPage < lastPage) {
                    paginationHtml += `
                    ${endPage < lastPage - 1 ? '<li class="page-item disabled"><span class="page-link">...</span></li>' : ''}
                    <li class="page-item">
                        <a class="page-link" href="#" data-page="${lastPage}">${lastPage}</a>
                    </li>
                `;
                }

                // Next button
                paginationHtml += `
                <li class="page-item ${currentPage === lastPage ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage + 1}">Next</a>
                </li>
            `;

                paginationElement.innerHTML = paginationHtml;

                // Add click handlers
                paginationElement.querySelectorAll('.page-link').forEach(link => {
                    link.addEventListener('click', (e) => {
                        e.preventDefault();
                        const page = parseInt(link.dataset.page);
                        if (page && !isNaN(page) && page !== currentPage && page >= 1 && page <= lastPage) {
                            this.currentPage = page;
                            this.loadFeedback();
                            window.scrollTo({ top: 0, behavior: 'smooth' });
                        }
                    });
                });
            }

            showLoading() {
                document.getElementById('loadingSpinner').style.display = 'block';
                document.getElementById('feedbackListContainer').style.display = 'none';
                document.getElementById('noDataMessage').style.display = 'none';
            }

            hideLoading() {
                document.getElementById('loadingSpinner').style.display = 'none';
            }

            showError(message) {
                const container = document.getElementById('feedbackItems');
                container.innerHTML = `
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
                document.getElementById('feedbackListContainer').style.display = 'block';
            }
        }

        // Initialize the feedback manager when the page loads
        document.addEventListener('DOMContentLoaded', () => {
            new FeedbackManager();
        });
    </script>
@endsection
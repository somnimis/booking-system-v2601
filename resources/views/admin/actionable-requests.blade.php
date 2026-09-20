@extends('layouts.admin')
@section('title', 'Needs My Action')
@section('content')
    <style>
        /* Create Reservation Banner — same visual as pending-requests */
        .create-reservation-banner {
            background: linear-gradient(135deg, #0e388c 0%, #1a4a9e 100%);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(11, 45, 114, 0.2), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
        }

        .create-reservation-banner::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 320'%3E%3Cpath fill='rgba(255,255,255,0.05)' d='M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,154.7C960,171,1056,181,1152,165.3C1248,149,1344,107,1392,85.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: bottom;
            background-size: cover;
            opacity: 0.3;
            pointer-events: none;
        }

        .banner-content {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .banner-text h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: white;
        }

        .banner-text p {
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 0;
            font-size: 0.9rem;
            max-width: 550px;
            line-height: 1.5;
        }

        .btn-create-reservation {
            background: white;
            color: #0b2d72;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .btn-create-reservation:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(11, 45, 114, 0.3);
            color: #0b2d72;
            background: #f8f9fa;
        }

        @media (max-width: 768px) {
            .banner-content { flex-direction: column; text-align: center; }
            .banner-text p { max-width: 100%; }
            .create-reservation-banner { padding: 1.25rem; }
            .banner-text h3 { font-size: 1.25rem; }
        }

        /* Requisition card (identical to pending-requests) */
        .requisition-card {
            background: #fff;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 0.75rem;
            border: 1px solid #e9ecef;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .requisition-card:hover {
            background-color: #f8f9fa;
            border-color: #dee2e6;
        }

        .requester-name {
            font-weight: 600;
            color: #212529;
            margin-bottom: 0.25rem;
        }

        .schedule-info {
            font-size: 0.7rem;
            color: #6c757d;
            margin-top: 0.25rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
            flex-wrap: wrap;
        }

        .request-id {
            font-size: 0.65rem;
            color: #adb5bd;
            font-weight: 500;
        }

        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-weight: 500;
        }

        /* Pagination */
        .pagination-container {
            margin-top: 1.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.75rem;
        }

        .pagination-info { font-size: 0.85rem; color: #6c757d; }

        .pagination-controls {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .btn-pagination {
            background: #fff;
            border: 1px solid #dee2e6;
            color: #495057;
            padding: 0.375rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.85rem;
            transition: all 0.2s;
        }

        .btn-pagination:hover:not(:disabled) {
            background: #e9ecef;
            border-color: #ced4da;
        }

        .btn-pagination:disabled { opacity: 0.5; cursor: not-allowed; }

        .btn-pagination.active {
            background: #0b2d72;
            border-color: #0b2d72;
            color: white;
        }
    </style>

    <main id="main">
        <div class="container-fluid px-4">
            <div class="row g-0">
                <!-- Create Reservation Banner -->
                <div class="col-12">
                    <div class="create-reservation-banner">
                        <div class="banner-content">
                            <div class="banner-text">
                                <h3>
                                    <i class="bi bi-plus-circle me-2"></i>
                                    Create Reservation
                                </h3>
                                <p>Create reservations on behalf of users.</p>
                            </div>
                            <a href="{{ url('/admin/reservations/create') }}" class="btn-create-reservation">
                                <i class="bi bi-calendar-plus"></i>
                                Create New Reservation
                                <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Needs My Action List -->
                <div class="col-12">
                    <div class="card p-3">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                            <div>
                                <h5 class="mb-0">Needs My Action</h5>
                                <small class="text-muted">Requisitions awaiting your decision.</small>
                            </div>

                            <div class="d-flex align-items-center gap-3">
                                <div class="sort-selector d-flex align-items-center gap-1">
                                    <label for="sortOrder" class="small text-muted mb-0">Sort:</label>
                                    <select id="sortOrder" class="form-select form-select-sm" style="width: 140px;">
                                        <option value="desc">Newest First</option>
                                        <option value="asc" selected>Oldest First</option>
                                    </select>
                                </div>

                                <div class="per-page-selector d-flex align-items-center gap-1">
                                    <label for="perPage" class="small text-muted mb-0">Show:</label>
                                    <select id="perPage" class="form-select form-select-sm" style="width: 90px;">
                                        <option value="4" selected>4</option>
                                        <option value="10">10</option>
                                        <option value="15">15</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div id="actionableContainer">
                            <div class="text-center text-muted py-4">
                                <div class="spinner-border spinner-border-sm" role="status"></div>
                                <div class="mt-2">Loading requisitions...</div>
                            </div>
                        </div>

                        <div id="actionablePagination" class="pagination-container" style="display: none;"></div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@section('scripts')
    <script src="{{ asset('js/admin/requests-list.js') }}"></script>
    <script>
        // State
        let currentPage = 1;
        let currentPerPage = 4;
        let currentSortOrder = 'asc';

        // DOM
        let container, paginationContainer, perPageSelect, sortOrderSelect;

        document.addEventListener('DOMContentLoaded', function () {
            container = document.getElementById('actionableContainer');
            paginationContainer = document.getElementById('actionablePagination');
            perPageSelect = document.getElementById('perPage');
            sortOrderSelect = document.getElementById('sortOrder');

            perPageSelect.addEventListener('change', function () {
                currentPerPage = parseInt(this.value, 10);
                currentPage = 1;
                fetchActionable();
            });

            sortOrderSelect.addEventListener('change', function () {
                currentSortOrder = this.value;
                currentPage = 1;
                fetchActionable();
            });

            fetchActionable();
        });

        /**
         * Fetch requisitions where this admin has an actionable pending approval.
         */
        function fetchActionable(page = 1) {
            const token = localStorage.getItem('adminToken');
            if (!token) {
                RequestsList.showError(container, 'Not authenticated.');
                return;
            }

            currentPage = page;
            RequestsList.showLoading(container);
            paginationContainer.style.display = 'none';

            const url = `/api/admin/requisitions/actionable?page=${page}&per_page=${currentPerPage}&sort_order=${currentSortOrder}`;

            RequestsList.fetchList(url, token)
                .then(data => {
                    RequestsList.renderCards(data.data || [], container);
                    RequestsList.renderPagination(data.meta, paginationContainer, {
                        onPageChange: (p) => fetchActionable(p)
                    });
                })
                .catch(error => {
                    console.error('Error loading actionable requisitions:', error);
                    RequestsList.showError(container, error.message, () => fetchActionable(currentPage));
                });
        }
    </script>
@endsection
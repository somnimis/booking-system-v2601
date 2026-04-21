@extends('layouts.admin')

@section('title', 'Archives')

@section('content')

    <style>
        .status-badge {
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: 600;
            border-radius: 0.25rem;
            display: inline-block;
            min-width: 80px;
            text-align: center;
        }

        /* Main container to allow flex growth */
        main {
            display: flex;
            flex-direction: column;
            min-height: calc(100vh - 70px);
            /* Adjust based on your header height */
            padding-bottom: 20px;
            /* Bottom padding to prevent touching */
        }

        /* Make the card container flex and grow */
        .card.mb-4 {
            display: flex;
            flex-direction: column;
            flex: 1;
            margin-bottom: 0 !important;
            /* Remove bottom margin to control via padding */
            height: 100%;
        }

        /* Card body/table container should take remaining space */
        .card .table-responsive {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }

        /* Ensure table takes full width */
        .card .table {
            width: 100%;
            margin-bottom: 0;
        }

        /* Add left padding to table cells */
        #archiveTable td,
        #archiveTable th {
            padding-left: 5rem !important;
            /* Add left padding */
        }

        #archiveTable td {
            padding-top: 1rem !important;
        }

        /* Keep first cell styling consistent */
        #archiveTable td:first-child,
        #archiveTable th:first-child {
            padding-left: 1.5rem !important;
        }

        /* Shimmer loading styles - updated to replace table body */
        .shimmer-container {
            width: 100%;
        }

        .shimmer-table-body {
            width: 100%;
        }

        .shimmer-row {
            display: flex;
            width: 100%;
            padding: 0.75rem 0;
            border-bottom: 1px solid #dee2e6;
            align-items: center;
        }

        .shimmer-cell {
            height: 20px;
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            border-radius: 4px;
            margin: 0 10px;
        }

        .shimmer-cell.id {
            width: 8%;
            margin-left: 1.5rem;
        }

        .shimmer-cell.purpose {
            width: 30%;
        }

        .shimmer-cell.date {
            width: 20%;
        }

        .shimmer-cell.status {
            width: 12%;
        }

        .shimmer-cell.actions {
            width: 15%;
        }

        @keyframes shimmer {
            0% {
                background-position: 200% 0;
            }

            100% {
                background-position: -200% 0;
            }
        }

        /* Empty state styling with proper padding */
        #emptyState {
            padding: 3rem 1rem !important;
            box-sizing: border-box;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .col-md-2.border-end {
                border-right: none !important;
                padding-bottom: 1rem;
                margin-bottom: 1rem;
                border-bottom: 1px solid #dee2e6;
            }

            .shimmer-cell.id {
                width: 10%;
            }

            .shimmer-cell.purpose {
                width: 25%;
            }

            .shimmer-cell.date {
                width: 18%;
            }

            .shimmer-cell.status {
                width: 15%;
            }

            .shimmer-cell.actions {
                width: 20%;
            }

            /* Adjust table cell padding for mobile */
            #archiveTable td,
            #archiveTable th {
                padding-left: 0.75rem !important;
            }
        }

        /* Fix for very small screens */
        @media (max-width: 576px) {
            .card .table-responsive {
                max-height: calc(100vh - 250px);
            }

            #archiveTable td,
            #archiveTable th {
                padding-left: 0.5rem !important;
                padding-right: 0.5rem !important;
            }
        }
    </style>
    <main id="main">
        <div class="container-fluid px-4">
            <!-- Completed Transactions Card -->
            <div class="card mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="m-0 font-weight-bold text-primary fw-bold">Completed Transactions</h5>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-secondary" id="refreshBtn">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                        <button class="btn btn-sm btn-primary" id="exportArchiveBtn">
                            <i class="fas fa-file-export me-2"></i>Export Archive
                        </button>
                    </div>
                </div>

                <!-- Table directly attached without padding -->
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="archiveTable" width="100%" cellspacing="0">
                        <thead class="table-light">
                            <tr>
                                <th>Request ID</th>
                                <th>Purpose</th>
                                <th>Date Completed</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="archiveTableBody">
                            <!-- Data will be loaded via JavaScript -->
                        </tbody>
                        <!-- Shimmer Loading inside tbody -->
                        <tbody id="loadingState" style="display: none;">
                            <tr>
                                <td colspan="5" style="padding: 0;">
                                    <div class="shimmer-container">
                                        <div class="shimmer-table-body">
                                            <div class="shimmer-row">
                                                <div class="shimmer-cell id"></div>
                                                <div class="shimmer-cell purpose"></div>
                                                <div class="shimmer-cell date"></div>
                                                <div class="shimmer-cell status"></div>
                                                <div class="shimmer-cell actions"></div>
                                            </div>
                                            <div class="shimmer-row">
                                                <div class="shimmer-cell id"></div>
                                                <div class="shimmer-cell purpose"></div>
                                                <div class="shimmer-cell date"></div>
                                                <div class="shimmer-cell status"></div>
                                                <div class="shimmer-cell actions"></div>
                                            </div>
                                            <div class="shimmer-row">
                                                <div class="shimmer-cell id"></div>
                                                <div class="shimmer-cell purpose"></div>
                                                <div class="shimmer-cell date"></div>
                                                <div class="shimmer-cell status"></div>
                                                <div class="shimmer-cell actions"></div>
                                            </div>
                                            <div class="shimmer-row">
                                                <div class="shimmer-cell id"></div>
                                                <div class="shimmer-cell purpose"></div>
                                                <div class="shimmer-cell date"></div>
                                                <div class="shimmer-cell status"></div>
                                                <div class="shimmer-cell actions"></div>
                                            </div>
                                            <div class="shimmer-row">
                                                <div class="shimmer-cell id"></div>
                                                <div class="shimmer-cell purpose"></div>
                                                <div class="shimmer-cell date"></div>
                                                <div class="shimmer-cell status"></div>
                                                <div class="shimmer-cell actions"></div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State (shown inside table area) -->
                <div id="emptyState" class="text-center" style="display: none;">
                    <i class="fas fa-archive fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No Archived Requisitions</h5>
                    <p class="text-muted">There are no completed, cancelled, or rejected requisitions to display.</p>
                </div>


                <!-- View Details Modal -->
                <div class="modal fade" id="viewDetailsModal" tabindex="-1" aria-labelledby="viewDetailsModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="viewDetailsModalLabel">Requisition Details</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body" id="requisitionDetails">
                                <!-- Details will be populated by JavaScript -->
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    </main>
@endsection
@section('scripts')
    <script src="{{ asset('js/admin/toast.js') }}"></script>
    <script>
        class ArchiveManager {
            constructor() {
                this.archivedRequisitions = [];
                this.currentYear = new Date().getFullYear();
                this.init();
            }

            init() {
                this.loadArchivedRequisitions();
                this.setupEventListeners();
            }

            setupEventListeners() {
                // Refresh button
                document.getElementById('refreshBtn').addEventListener('click', () => {
                    this.loadArchivedRequisitions();
                });

                // Export button
                document.getElementById('exportArchiveBtn').addEventListener('click', () => {
                    this.exportArchive();
                });


                // Handle modal show event
                const viewDetailsModal = document.getElementById('viewDetailsModal');
                viewDetailsModal.addEventListener('show.bs.modal', (event) => {
                    const button = event.relatedTarget;
                    if (button) {
                        const requestId = button.getAttribute('data-request-id');
                        this.showRequisitionDetails(requestId);
                    }
                });

                // Also handle direct row clicks
                document.addEventListener('click', (event) => {
                    const row = event.target.closest('tr');
                    if (row && row.hasAttribute('onclick')) {
                        // Extract requestId from the onclick attribute
                        const onclickAttr = row.getAttribute('onclick');
                        const match = onclickAttr.match(/archiveManager\.showRequisitionDetails\((\d+)\)/);
                        if (match) {
                            const requestId = match[1];
                            this.showRequisitionDetails(requestId);
                            // Manually trigger the modal
                            const modal = new bootstrap.Modal(document.getElementById('viewDetailsModal'));
                            modal.show();
                        }
                    }
                });
            }

            exportArchive() {
                if (this.archivedRequisitions.length === 0) {
                    this.showError('No data available to export.');
                    return;
                }

                try {
                    // Create CSV content with all details for export
                    const headers = [
                        'Request ID',
                        'Official Receipt #',
                        'Requester Name',
                        'Email',
                        'Organization',
                        'Purpose',
                        'Status',
                        'Start Schedule',
                        'End Schedule',
                        'Participants',
                        'Facilities',
                        'Equipment',
                        'Date Completed'
                    ];

                    const csvData = this.archivedRequisitions.map(requisition => [
                        requisition.request_id,
                        `"${requisition.official_receipt_num || ''}"`,
                        `"${requisition.requester_name}"`,
                        `"${requisition.email}"`,
                        `"${requisition.organization_name || ''}"`,
                        `"${requisition.purpose}"`,
                        `"${requisition.status}"`,
                        `"${requisition.start_schedule}"`,
                        `"${requisition.end_schedule}"`,
                        requisition.num_participants,
                        `"${requisition.facilities.join(', ')}"`,
                        `"${requisition.equipment.join(', ')}"`,
                        new Date(requisition.updated_at).toLocaleDateString()
                    ]);

                    const csvContent = [headers, ...csvData]
                        .map(row => row.join(','))
                        .join('\n');

                    // Create and download file
                    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                    const link = document.createElement('a');
                    const url = URL.createObjectURL(blob);

                    link.setAttribute('href', url);
                    link.setAttribute('download', `archives_${new Date().toISOString().split('T')[0]}.csv`);
                    link.style.visibility = 'hidden';

                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);

                    this.showSuccess('Archive exported successfully!');

                } catch (error) {
                    console.error('Export error:', error);
                    this.showError('Failed to export archive: ' + error.message);
                }
            }

            showSuccess(message) {
                showToast(message, 'success');
            }
            async loadArchivedRequisitions() {
                const loadingState = document.getElementById('loadingState');
                const emptyState = document.getElementById('emptyState');
                const tableBody = document.getElementById('archiveTableBody');

                // Show shimmer (inside table), hide empty state and actual table body
                loadingState.style.display = 'table-row-group'; // or 'block' depending on display type
                emptyState.style.display = 'none';
                tableBody.style.display = 'none'; // Hide the actual table body

                try {
                    const token = localStorage.getItem('adminToken');
                    const response = await fetch('/api/admin/archives', {
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Accept': 'application/json'
                        }
                    });

                    if (!response.ok) throw new Error('Failed to fetch archived requisitions');

                    const result = await response.json();

                    if (result.success) {
                        this.archivedRequisitions = result.data;
                        this.renderArchiveTable();
                    } else {
                        throw new Error(result.message);
                    }
                } catch (error) {
                    console.error('Error loading archived requisitions:', error);
                    this.showError('Failed to load archived requisitions: ' + error.message);
                } finally {
                    // Hide shimmer after loading completes
                    loadingState.style.display = 'none';
                    tableBody.style.display = 'table-row-group'; // Show the table body
                }
            }

            renderArchiveTable() {
                const tableBody = document.getElementById('archiveTableBody');
                const emptyState = document.getElementById('emptyState');
                const loadingState = document.getElementById('loadingState');

                if (this.archivedRequisitions.length === 0) {
                    tableBody.innerHTML = '';
                    tableBody.style.display = 'none';
                    emptyState.style.display = 'block';
                    loadingState.style.display = 'none';
                    return;
                }

                emptyState.style.display = 'none';
                tableBody.style.display = 'table-row-group';
                loadingState.style.display = 'none';

                tableBody.innerHTML = this.archivedRequisitions.map(requisition => `
                            <tr onclick="archiveManager.showRequisitionDetails(${requisition.request_id})" style="cursor: pointer;">
                                <td class="fw-bold">#${requisition.request_id.toString().padStart(4, '0')}</td>
                                <td>${requisition.purpose}</td>
                                <td class="small">
                                    ${new Date(requisition.updated_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}<br>
                                    <small class="text-muted">${new Date(requisition.updated_at).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true })}</small>
                                </td>
                                <td>
                                    <span class="status-badge" style="background-color: ${requisition.status_color}; color: white;">
                                        ${requisition.status}
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#viewDetailsModal"
                                            data-request-id="${requisition.request_id}"
                                            onclick="event.stopPropagation()">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                        `).join('');
            }
            renderArchiveTable() {
                const tableBody = document.getElementById('archiveTableBody');
                const emptyState = document.getElementById('emptyState');

                if (this.archivedRequisitions.length === 0) {
                    tableBody.innerHTML = '';
                    emptyState.style.display = 'block';
                    return;
                }

                emptyState.style.display = 'none';

                tableBody.innerHTML = this.archivedRequisitions.map(requisition => `
                                                <tr onclick="archiveManager.showRequisitionDetails(${requisition.request_id})" style="cursor: pointer;">
                                                    <td class="fw-bold">#${requisition.request_id.toString().padStart(4, '0')}</td>
                                                    <td>${requisition.purpose}</td>
                                                    <td class="small">
                                                        ${new Date(requisition.updated_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}<br>
                                                        <small class="text-muted">${new Date(requisition.updated_at).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true })}</small>
                                                    </td>
                                                    <td>
                                                        <span class="status-badge" style="background-color: ${requisition.status_color}; color: white;">
                                                            ${requisition.status}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-primary" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#viewDetailsModal"
                                                                data-request-id="${requisition.request_id}"
                                                                onclick="event.stopPropagation()">
                                                            <i class="fas fa-eye"></i> View
                                                        </button>
                                                    </td>
                                                </tr>
                                            `).join('');
            }

            showRequisitionDetails(requestId) {
                console.log('showRequisitionDetails called with requestId:', requestId);

                // Convert to number for comparison
                const id = parseInt(requestId);
                const requisition = this.archivedRequisitions.find(r => r.request_id === id);
                console.log('Found requisition:', requisition);

                if (!requisition) {
                    console.error('No requisition found for requestId:', requestId);
                    return;
                }

                const modalBody = document.getElementById('requisitionDetails');
                console.log('Modal body element:', modalBody);

                // Set the modal content
                modalBody.innerHTML = `
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h6>Requester Information</h6>
                                                    <table class="table table-sm table-borderless">
                                                        <tr>
                                                            <td><strong>Request ID:</strong></td>
                                                            <td>#${requisition.request_id.toString().padStart(4, '0')}</td>
                                                        </tr>
                                                        <tr>
                                                            <td><strong>Name:</strong></td>
                                                            <td>${requisition.requester_name}</td>
                                                        </tr>
                                                        <tr>
                                                            <td><strong>Email:</strong></td>
                                                            <td>${requisition.email}</td>
                                                        </tr>
                                                        ${requisition.organization_name ? `
                                                        <tr>
                                                            <td><strong>Organization:</strong></td>
                                                            <td>${requisition.organization_name}</td>
                                                        </tr>` : ''}
                                                        <tr>
                                                            <td><strong>Participants:</strong></td>
                                                            <td>${requisition.num_participants}</td>
                                                        </tr>
                                                    </table>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6>Booking Details</h6>
                                                    <table class="table table-sm table-borderless">
                                                        <tr>
                                                            <td><strong>Purpose:</strong></td>
                                                            <td>${requisition.purpose}</td>
                                                        </tr>
                                                        <tr>
                                                            <td><strong>Status:</strong></td>
                                                            <td>
                                                                <span class="status-badge" style="background-color: ${requisition.status_color}; color: white;">
                                                                    ${requisition.status}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td><strong>Start Schedule:</strong></td>
                                                            <td>${requisition.start_schedule}</td>
                                                        </tr>
                                                        <tr>
                                                            <td><strong>End Schedule:</strong></td>
                                                            <td>${requisition.end_schedule}</td>
                                                        </tr>
                                                        <tr>
                                                            <td><strong>Official Receipt:</strong></td>
                                                            <td>
                                                                ${requisition.official_receipt_num ?
                        `<div class="d-flex align-items-center gap-2">
                                                                        <span class="badge bg-info">${requisition.official_receipt_num}</span>
                                                                        <a href="/official-receipt/${requisition.request_id}" 
                                                                           class="btn btn-sm btn-outline-primary" 
                                                                           target="_blank">
                                                                            <i class="fas fa-receipt me-1"></i>Open Receipt
                                                                        </a>
                                                                    </div>` :
                        '<span class="text-muted">N/A</span>'
                    }
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </div>
                                            </div>

                                            <div class="row mt-3">
                                                <div class="col-md-6">
                                                    <h6>Facilities</h6>
                                                    ${requisition.facilities.length > 0 ?
                        requisition.facilities.map(facility =>
                            `<span class="badge bg-secondary me-1 mb-1">${facility}</span>`
                        ).join('') :
                        '<p class="text-muted">No facilities requested</p>'
                    }
                                                </div>
                                                <div class="col-md-6">
                                                    <h6>Equipment</h6>
                                                    ${requisition.equipment.length > 0 ?
                        requisition.equipment.map(eq =>
                            `<span class="badge bg-info me-1 mb-1">${eq}</span>`
                        ).join('') :
                        '<p class="text-muted">No equipment requested</p>'
                    }
                                                </div>
                                            </div>
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <h6>Timeline</h6>
                                            <table class="table table-sm table-borderless">
                                                <tr>
                                                    <td><strong>Submitted:</strong></td>
                                                    <td>${new Date(requisition.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })} at ${new Date(requisition.created_at).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true })}</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Last Updated:</strong></td>
                                                    <td>${new Date(requisition.updated_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })} at ${new Date(requisition.updated_at).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true })}</td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                        `;

                console.log('Modal content set successfully');
            }

            showError(message) {
                // You can implement a toast notification system here
                console.error(message);
                showToast(message, 'error');
            }
        }

        // Initialize archive manager when the page loads
        const archiveManager = new ArchiveManager();
    </script>
@endsection
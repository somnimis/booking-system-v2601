@extends('layouts.app')

@section('title', 'My Bookings')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/public/booking-card.css') }}">
@endpush

@section('content')
<main class="page-surface">
    <div class="page-container">
        <h2 class="page-title">Your Bookings</h2>
        <p class="page-subtitle">Enter your access code or the email you used to book.</p>

        {{-- Search --}}
        <form id="searchForm" class="search-row" onsubmit="event.preventDefault(); searchForms();">
            <input type="text" id="searchInput" class="search-input"
                   placeholder="Access code or email…" required>
            <button type="submit" class="btn-primary-action">
                <i class="fas fa-search"></i> Search
            </button>
        </form>

        <div id="loadingSpinner" class="loading-state d-none">
            <div class="spinner-border text-secondary" role="status"></div>
            <p class="mt-2 text-muted small">Searching…</p>
        </div>

        <div id="filterBar" class="filter-row d-none">
            <div class="dropdown">
                <button class="filter-btn dropdown-toggle" type="button"
                        data-bs-toggle="dropdown" id="filterBtn">
                    <i class="fas fa-filter"></i> Filter by Status
                </button>
                <ul class="dropdown-menu" id="filterMenu"></ul>
            </div>
        </div>

        <div id="resultsContainer"></div>

        <div id="noResultsMessage" class="empty-state d-none">
            <i class="fas fa-search"></i>
            <p class="fw-semibold mb-1">No requisition forms found</p>
            <p class="text-muted small">Check your reference code or email and try again.</p>
        </div>
    </div>
</main>

{{-- Cancel modal --}}
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Confirm Cancellation</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to cancel this request? This cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Keep Request</button>
                <button class="btn btn-danger" id="confirmCancelBtn">Yes, Cancel</button>
            </div>
        </div>
    </div>
</div>

{{-- Upload receipt modal (server-side upload) --}}
<div class="modal fade" id="uploadReceiptModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Upload Payment Receipt</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="uploadArea" class="upload-area">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Drag & drop or click to browse</p>
                    <p class="small text-muted mb-0">JPG, PNG, PDF — max 5MB</p>
                </div>
                <input type="file" id="receiptFile" accept=".jpg,.jpeg,.png,.pdf" hidden>

                <div id="uploadPreview" class="alert alert-info mt-3 d-none">
                    <i class="fas fa-file"></i> <span id="fileName"></span>
                    <button type="button" class="btn-close float-end" onclick="clearFileSelection()"></button>
                </div>

                <div id="uploadError" class="alert alert-danger mt-3 d-none"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="confirmUploadBtn" disabled onclick="uploadReceipt()">
                    Upload
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Thin JS: fetch pre-rendered HTML from server, inject, wire modals only.
let currentRequestId = null;
let selectedFile = null;

function searchForms() {
    const input = document.getElementById('searchInput');
    const q = input.value.trim();
    if (!q) return;

    document.getElementById('noResultsMessage').classList.add('d-none');
    document.getElementById('loadingSpinner').classList.remove('d-none');
    document.getElementById('resultsContainer').innerHTML = '';
    document.getElementById('filterBar').classList.add('d-none');

    fetch(`/api/requester/search?q=${encodeURIComponent(q)}`)
        .then(r => r.json())
        .then(data => {
            document.getElementById('loadingSpinner').classList.add('d-none');

            if (!data.success) {
                document.getElementById('noResultsMessage').classList.remove('d-none');
                return;
            }

            document.getElementById('resultsContainer').innerHTML = data.html;
            buildFilterMenu(data.statuses);
        })
        .catch(() => {
            document.getElementById('loadingSpinner').classList.add('d-none');
            document.getElementById('noResultsMessage').classList.remove('d-none');
        });
}

function buildFilterMenu(statuses) {
    const bar = document.getElementById('filterBar');
    const menu = document.getElementById('filterMenu');

    menu.innerHTML = '<li><a class="dropdown-item" href="#" data-status="all">All</a></li>' +
        statuses.map(s =>
            `<li><a class="dropdown-item" href="#" data-status="${s.slug}">${s.status_name}</a></li>`
        ).join('');

    menu.querySelectorAll('a').forEach(a => {
        a.addEventListener('click', e => {
            e.preventDefault();
            const status = a.dataset.status;
            document.querySelectorAll('.booking-card').forEach(card => {
                card.style.display = (status === 'all' || card.dataset.status === status) ? '' : 'none';
            });
            document.getElementById('filterBtn').innerHTML =
                `<i class="fas fa-filter"></i> ${a.textContent}`;
        });
    });

    bar.classList.remove('d-none');
    document.getElementById('filterBtn').innerHTML = '<i class="fas fa-filter"></i> Filter by Status';
}

function showCancelModal(id) {
    currentRequestId = id;
    new bootstrap.Modal(document.getElementById('cancelModal')).show();
}

function showUploadModal(id) {
    currentRequestId = id;
    clearFileSelection();
    new bootstrap.Modal(document.getElementById('uploadReceiptModal')).show();
}

function clearFileSelection() {
    selectedFile = null;
    document.getElementById('receiptFile').value = '';
    document.getElementById('uploadPreview').classList.add('d-none');
    document.getElementById('confirmUploadBtn').disabled = true;
}

function handleFile(file) {
    const validTypes = ['image/jpeg', 'image/png', 'application/pdf'];
    const errEl = document.getElementById('uploadError');

    if (!validTypes.includes(file.type)) {
        errEl.textContent = 'Invalid file type. Use JPG, PNG, or PDF.';
        errEl.classList.remove('d-none');
        return;
    }
    if (file.size > 5 * 1024 * 1024) {
        errEl.textContent = 'File too large. Max 5MB.';
        errEl.classList.remove('d-none');
        return;
    }

    selectedFile = file;
    errEl.classList.add('d-none');
    document.getElementById('fileName').textContent = file.name;
    document.getElementById('uploadPreview').classList.remove('d-none');
    document.getElementById('confirmUploadBtn').disabled = false;
}

function uploadReceipt() {
    if (!selectedFile || !currentRequestId) return;

    const btn = document.getElementById('confirmUploadBtn');
    btn.disabled = true;
    btn.textContent = 'Uploading…';

    const fd = new FormData();
    fd.append('receipt', selectedFile);

    fetch(`/api/requester/requisition/${currentRequestId}/upload-receipt`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: fd,
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('uploadReceiptModal')).hide();
            alert('Receipt uploaded successfully.');
            searchForms();
        } else {
            const errEl = document.getElementById('uploadError');
            errEl.textContent = data.message || 'Upload failed.';
            errEl.classList.remove('d-none');
            btn.disabled = false;
            btn.textContent = 'Upload';
        }
    })
    .catch(() => {
        const errEl = document.getElementById('uploadError');
        errEl.textContent = 'Network error. Try again.';
        errEl.classList.remove('d-none');
        btn.disabled = false;
        btn.textContent = 'Upload';
    });
}

document.addEventListener('DOMContentLoaded', () => {
    // Cancel confirm
    document.getElementById('confirmCancelBtn').addEventListener('click', () => {
        if (!currentRequestId) return;
        fetch(`/api/requester/requisition/${currentRequestId}/cancel`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        })
        .then(r => r.json())
        .then(data => {
            if (data.request_id) {
                bootstrap.Modal.getInstance(document.getElementById('cancelModal')).hide();
                alert('Request cancelled successfully.');
                searchForms();
            } else {
                alert(data.details || data.error || 'Failed to cancel.');
            }
        });
    });

    // Upload area
    const area = document.getElementById('uploadArea');
    const input = document.getElementById('receiptFile');
    area.addEventListener('click', () => input.click());
    area.addEventListener('dragover', e => { e.preventDefault(); area.style.borderColor = 'var(--cpu-primary)'; });
    area.addEventListener('dragleave', () => area.style.borderColor = '#d1d5db');
    area.addEventListener('drop', e => {
        e.preventDefault();
        area.style.borderColor = '#d1d5db';
        if (e.dataTransfer.files.length) handleFile(e.dataTransfer.files[0]);
    });
    input.addEventListener('change', e => {
        if (e.target.files.length) handleFile(e.target.files[0]);
    });
});
</script>
@endsection
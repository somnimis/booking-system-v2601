{{-- Conflict Modal (shared public + admin) --}}
<div class="modal fade" id="conflictModal" tabindex="-1" x-data
    x-on:open-conflict-modal.window="new bootstrap.Modal($el).show()">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header text-dark">
                <h5 class="modal-title">
                    <i class="fas fa-triangle-exclamation text-danger me-2"></i>Scheduling Conflict Found
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-start" style="max-height: 70vh; overflow-y: auto;">
                <div>{!! $conflictHtml !!}</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
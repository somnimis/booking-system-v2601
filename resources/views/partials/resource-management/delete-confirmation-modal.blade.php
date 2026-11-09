{{--
  Generic delete confirmation modal.
  Usage: @include('partials.resource-management.delete-confirmation-modal', ['resourceLabel' => 'Facility'])
  Optional: $resourceLabel defaults to "Resource".
  Trigger: new bootstrap.Modal(document.getElementById('deleteConfirmationModal')).show()
  Confirm button id: #confirmDeleteBtn  (listener must be attached by caller)
--}}
@php($resourceLabel = $resourceLabel ?? 'Resource')

<div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to delete this {{ strtolower($resourceLabel) }}? This action cannot be undone.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete {{ $resourceLabel }}</button>
      </div>
    </div>
  </div>
</div>
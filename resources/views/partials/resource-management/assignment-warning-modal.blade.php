{{--
  Generic "Confirm Department Assignment" warning modal.
  Sits on top of the mass-assign modal, so it's a second step.
  Usage: @include('partials.resource-management.assignment-warning-modal', ['resourceLabel' => 'Facility'])
  Optional: $resourceLabel defaults to "Resource".
  Required DOM hooks:
    #assignmentWarningModal, #confirmAssignBtn  (listener attached by caller)
--}}
@php($resourceLabel = $resourceLabel ?? 'Resource')

<div class="modal fade" id="assignmentWarningModal" tabindex="-1" aria-labelledby="warningModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="warningModalLabel" style="color: #003366;">Confirm Department Assignment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="text-center mb-3">
          <i class="bi bi-exclamation-triangle-fill" style="color: #003366; font-size: 2.5rem;"></i>
        </div>
        <p class="text-center mb-0">
          This action will <strong>replace the managed_by field</strong> for all selected
          {{ strtolower($resourceLabel) }}s with the selected department.
        </p>
        <p class="text-center text-muted mt-2 mb-0">Are you sure you want to continue?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn" id="confirmAssignBtn"
          style="background-color: #003366; color: white;">Yes, Assign Department</button>
      </div>
    </div>
  </div>
</div>
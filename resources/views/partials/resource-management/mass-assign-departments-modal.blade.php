{{--
  Mass Assign Department modal — LAZY template.
  The modal markup lives inside <template id="massAssignModalTemplate">.
  JS clones it into <body> the first time the modal is opened.
  Caller must call window.openMassAssignModal() to open.
--}}
@php
    $resourceLabel = $resourceLabel ?? 'Resource';
    // Naive pluralization — good enough for "Facility", "Equipment", etc.
    $plural = \Illuminate\Support\Str::plural($resourceLabel);
@endphp

<template id="massAssignModalTemplate">
    <div class="modal fade" id="massAssignDepartmentsModal" tabindex="-1" aria-labelledby="massAssignDepartmentsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="massAssignDepartmentsModalLabel">
                        Mass Assign Department to {{ $plural }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <form id="massAssignForm">
                        <div class="row g-3">

                            {{-- LEFT: Facilities --}}
                            <div class="col-md-7 d-flex flex-column">
                                <label class="form-label fw-bold mb-2" style="color: #003366;">
                                    Select {{ $plural }}
                                </label>

                                <div class="input-group input-group-sm mb-2">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" id="massAssignSearch" class="form-control"
                                        placeholder="Search by name or code...">
                                    <button class="btn btn-outline-secondary" type="button" id="massAssignSearchClear"
                                        style="display:none;">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>

                                <div id="massAssignCheckboxList" class="border rounded p-2 flex-grow-1"
                                    style="max-height: 320px; min-height: 320px; overflow-y: auto; background: #fff;">
                                    {{-- JS fills checkboxes --}}
                                </div>

                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <div class="text-muted small">
                                        <span id="massAssignPageIndicator">1 / 1</span>
                                        <span class="mx-2">·</span>
                                        <span id="massAssignTotalCount">0 items</span>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                            id="massAssignPrev" title="Previous page"><i
                                                class="bi bi-chevron-left"></i></button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                            id="massAssignNext" title="Next page"><i
                                                class="bi bi-chevron-right"></i></button>
                                    </div>
                                </div>
                            </div>

                            {{-- RIGHT: Department + Selected --}}
                            <div class="col-md-5 d-flex flex-column">
                                <label class="form-label fw-bold mb-2" style="color: #003366;">
                                    Select Department to Assign
                                </label>
                                <select id="departmentSingleSelect" class="form-select mb-3"
                                    style="border-color: #003366;">
                                    <option value="">Select a department...</option>
                                </select>

                                {{-- SELECTED section: fixed to same height as left column's checkbox list --}}
                                <div class="border rounded flex-grow-1"
                                    style="background:#f8f9fa; min-height: 320px; max-height: 320px; overflow: hidden;">
                                    <div class="d-flex justify-content-between align-items-center py-2 px-3 border-bottom"
                                        style="background:#fff;">
                                        <strong class="small" style="color: #003366;">
                                            Selected <span id="massAssignSelectedCount"
                                                class="badge bg-primary">0</span>
                                        </strong>
                                        <div class="d-flex gap-2">
                                            <button type="button" id="massAssignSelectPage"
                                                class="btn btn-sm btn-primary py-1 px-2" style="font-size: .75rem;">
                                                <i class="bi bi-plus-lg me-1"></i>Page
                                            </button>
                                            <button type="button" id="massAssignClearAll"
                                                class="btn btn-sm btn-outline-secondary py-1 px-2"
                                                style="font-size: .75rem;">
                                                Clear All
                                            </button>
                                        </div>
                                    </div>
                                    <div id="massAssignSelectedList" class="px-3 py-2"
                                        style="max-height: 280px; overflow-y: auto;">
                                        <p class="text-muted small mb-0">No {{ strtolower($plural) }} selected yet.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Selection summary alert removed per request --}}
                    </form>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn" id="executeMassAssignBtn"
                        style="background-color: #003366; color: white;">Assign Department</button>
                </div>
            </div>
        </div>
    </div>
</template>

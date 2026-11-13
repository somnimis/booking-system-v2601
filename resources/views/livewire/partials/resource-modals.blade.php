{{-- Facility Modal --}}
<style>
    button:disabled {
        opacity: 0.5;
        cursor: not-allowed !important;
    }

    .custom-pagination>div {
        display: flex !important;
        flex-direction: row !important;
        justify-content: space-between !important;
        align-items: center !important;
        width: 100%;
    }


    /* Navy colors only */
    .custom-pagination .page-link {
        color: var(--navy) !important;
        background-color: #fff;
        border-color: var(--border);
    }

    .custom-pagination .page-link:hover {
        background-color: var(--navy-light);
        color: var(--navy) !important;
    }

    .custom-pagination .page-item.active .page-link {
        background-color: var(--navy) !important;
        border-color: var(--navy) !important;
        color: #fff !important;
    }

    .custom-pagination .page-item.disabled .page-link {
        color: var(--text-muted) !important;
        background-color: var(--surface);
    }

    /* Break quantity controls to a second row on very narrow screens */
    @media (max-width: 400px) {
        .equipment-card-row {
            flex-wrap: wrap !important;
        }

        .equipment-controls {
            width: 100%;
            justify-content: space-between !important;
            margin-top: 10px;
        }
    }
</style>

<div class="modal fade" :class="{ 'show d-block': openModal === 'facilityModal' }" tabindex="-1"
    style="background: rgba(0,0,0,0.5);" x-show="openModal === 'facilityModal'" x-cloak>
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Select Facilities</h5>
                <button type="button" class="btn-close" x-on:click="openModal = null"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-8">
                        <input type="text" class="form-control" placeholder="Search facilities..."
                            wire:model.live.debounce.300ms="facilitySearch">
                        <div wire:loading wire:target="facilitySearch" class="text-primary small mt-1">
                            <span class="spinner-border spinner-border-sm"></span> Searching...
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" wire:model.live="facilityCategoryFilter">
                            <option value="">All Categories</option>
                            @foreach ($facilityCategories as $cat)
                                <option value="category:{{ $cat->category_id }}">{{ $cat->category_name }}</option>
                                @if (isset($cat->subcategories))
                                    @foreach ($cat->subcategories as $sub)
                                        <option value="subcategory:{{ $sub->subcategory_id }}">--
                                            {{ $sub->subcategory_name }}</option>
                                    @endforeach
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>
                <div style="height: 300px; overflow-y: auto;">
                    @forelse($facilities as $facility)
                        @php
                            $img = $facility->images->firstWhere('image_type', 'Primary') ?? $facility->images->first();
                            $imgUrl = $img ? $img->image_url : null;
                        @endphp
                        <div class="d-flex justify-content-between align-items-center p-2 border-bottom">
                            <div class="d-flex align-items-center gap-2">
                                @if ($imgUrl)
                                    <img src="{{ $imgUrl }}"
                                        style="width: 40px; height: 40px; border-radius: 4px; object-fit: cover;">
                                @else
                                    <div class="d-flex align-items-center justify-content-center bg-light rounded text-secondary flex-shrink-0"
                                        style="width: 40px; height: 40px; font-size: 1.2rem;">
                                        <i class="fa-solid fa-building"></i>
                                    </div>
                                @endif
                                <div>
                                    <strong>{{ $facility->facility_name }}</strong>
                                    <small class="text-muted d-block">
                                        {{ $facility->facility_code }}
                                        @if ($facility->parentFacility)
                                            &bull; {{ $facility->parentFacility->facility_name }}
                                        @endif
                                    </small>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-primary"
                                x-on:click="$wire.addItem('facility', {{ $facility->facility_id }}, '{{ $facility->facility_name }}', {{ $facility->base_fee }}, '{{ $facility->rate_type }}', '{{ $imgUrl }}'); openModal = null;">Add</button>
                        </div>
                    @empty
                        <div class="text-center text-muted py-4">No facilities found matching "{{ $facilitySearch }}".
                        </div>
                    @endforelse
                </div>
                {{-- Pagination moved directly inside modal-body, right under the scroll list --}}
                @if ($facilities->hasPages())
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <small class="text-muted">
                            Showing {{ $facilities->firstItem() }} to {{ $facilities->lastItem() }} of
                            {{ $facilities->total() }} results
                        </small>

                        {{-- Using the standard bootstrap view but forcing LIVE styles --}}
                        <ul class="pagination pagination-sm mb-0 custom-pagination">
                            <li class="page-item {{ $facilities->onFirstPage() ? 'disabled' : '' }}">
                                <button type="button" class="page-link" wire:click="previousPage('facilitiesPage')"
                                    wire:loading.attr="disabled">« Previous</button>
                            </li>
                            <li class="page-item {{ !$facilities->hasMorePages() ? 'disabled' : '' }}">
                                <button type="button" class="page-link" wire:click="nextPage('facilitiesPage')"
                                    wire:loading.attr="disabled">Next »</button>
                            </li>
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Equipment Modal --}}
<div class="modal fade" :class="{ 'show d-block': openModal === 'equipmentModal' }" tabindex="-1"
    style="background: rgba(0,0,0,0.5);" x-show="openModal === 'equipmentModal'" x-cloak>
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Select Equipment</h5>
                <button type="button" class="btn-close" x-on:click="openModal = null"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-8">
                        <input type="text" class="form-control" placeholder="Search equipment..."
                            wire:model.live.debounce.300ms="equipmentSearch">
                        <div wire:loading wire:target="equipmentSearch" class="text-primary small mt-1">
                            <span class="spinner-border spinner-border-sm"></span> Searching...
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" wire:model.live="equipmentCategoryFilter">
                            <option value="">All Categories</option>
                            @foreach ($equipmentCategories as $cat)
                                <option value="category:{{ $cat->category_id }}">{{ $cat->category_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div style="height: 300px; overflow-y: auto;">
                    @forelse($equipment as $item)
                        @php
                            $img = $item->images->firstWhere('image_type', 'Primary') ?? $item->images->first();
                            $imgUrl = $img ? $img->image_url : null;
                            $available = $item->items
                                ->filter(fn($i) => $i->status_id == 1 && in_array($i->condition_id, [1, 2, 3]))
                                ->count();
                            $total = $item->items->where('status_id', '!=', 5)->count();
                            $inCart = collect($selectedItems)
                                ->where('type', 'equipment')
                                ->firstWhere('equipment_id', $item->equipment_id);
                            $currentQty = $inCart['quantity'] ?? 1;
                        @endphp
                        <div class="d-flex justify-content-between align-items-center gap-2 p-2 border-bottom flex-nowrap equipment-card-row"
                            x-data="{ qty: {{ max(1, min($currentQty, max(1, $available))) }} }">

                            {{-- Left: image + details (shrinkable) --}}
                            <div class="d-flex align-items-center gap-2 flex-grow-1" style="min-width: 0;">
                                @if ($imgUrl)
                                    <img src="{{ $imgUrl }}" class="flex-shrink-0"
                                        style="width: 40px; height: 40px; border-radius: 4px; object-fit: cover;">
                                @else
                                    <div class="d-flex align-items-center justify-content-center bg-light rounded text-secondary flex-shrink-0"
                                        style="width: 40px; height: 40px; font-size: 1.2rem;">
                                        <i class="fa-solid fa-cube"></i>
                                    </div>
                                @endif
                                <div style="min-width: 0;">
                                    <strong class="d-block text-truncate">{{ $item->equipment_name }}</strong>
                                    <small class="text-muted d-block text-truncate">
                                        ₱{{ number_format($item->base_fee, 2) }}
                                        &bull;
                                        @if ($available > 0)
                                            <span class="text-success">{{ $available }} of {{ $total }}
                                                available</span>
                                        @else
                                            <span class="text-danger">Out of stock</span>
                                        @endif
                                    </small>
                                </div>
                            </div>

                            {{-- Right: quantity control + Add (never shrinks) --}}
                            @if ($available > 0)
                                <div class="d-flex align-items-center flex-shrink-0 equipment-controls"
                                    style="gap: 6px;">
                                    <button type="button" x-on:click="qty = Math.max(1, qty - 1)"
                                        x-bind:disabled="qty <= 1" onmouseover="this.style.backgroundColor='#f8f9fa'"
                                        onmouseout="this.style.backgroundColor='#fff'"
                                        style="width: 32px; height: 32px; padding: 0; border: 1px solid #ced4da; border-radius: 4px; background: #fff; color: #495057; font-weight: bold; line-height: 1; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: background-color 0.15s ease-in-out;">
                                        &minus;
                                    </button>

                                    <input type="text" x-model.number="qty" readonly
                                        style="width: 36px; height: 32px; padding: 0; text-align: center; border: 1px solid #ced4da; border-radius: 4px; background: #fff; color: #212529;">

                                    <button type="button" x-on:click="qty = Math.min({{ $available }}, qty + 1)"
                                        x-bind:disabled="qty >= {{ $available }}"
                                        onmouseover="this.style.backgroundColor='#f8f9fa'"
                                        onmouseout="this.style.backgroundColor='#fff'"
                                        style="width: 32px; height: 32px; padding: 0; border: 1px solid #ced4da; border-radius: 4px; background: #fff; color: #495057; font-weight: bold; line-height: 1; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: background-color 0.15s ease-in-out;">
                                        &plus;
                                    </button>

                                    <button type="button" class="btn btn-sm btn-primary ms-2 text-nowrap"
                                        x-on:click="$wire.addItem('equipment', {{ $item->equipment_id }}, '{{ $item->equipment_name }}', {{ $item->base_fee }}, '{{ $item->rate_type }}', '{{ $imgUrl }}', qty); openModal = null;">
                                        {{ $inCart ? 'Update' : 'Add' }}
                                    </button>
                                </div>
                            @else
                                <button type="button" class="btn btn-sm btn-secondary flex-shrink-0"
                                    disabled>Unavailable</button>
                            @endif
                        </div>
                    @empty
                        <div class="text-center text-muted py-4">No equipment found matching "{{ $equipmentSearch }}".
                        </div>
                    @endforelse
                </div>
                {{-- Pagination moved directly inside modal-body, right under the scroll list --}}
                @if ($equipment->hasPages())
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <small class="text-muted">
                            Showing {{ $equipment->firstItem() }} to {{ $equipment->lastItem() }} of
                            {{ $equipment->total() }} results
                        </small>

                        <ul class="pagination pagination-sm mb-0 custom-pagination">
                            <li class="page-item {{ $equipment->onFirstPage() ? 'disabled' : '' }}">
                                <button type="button" class="page-link" wire:click="previousPage('equipmentPage')"
                                    wire:loading.attr="disabled">« Previous</button>
                            </li>
                            <li class="page-item {{ !$equipment->hasMorePages() ? 'disabled' : '' }}">
                                <button type="button" class="page-link" wire:click="nextPage('equipmentPage')"
                                    wire:loading.attr="disabled">Next »</button>
                            </li>
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

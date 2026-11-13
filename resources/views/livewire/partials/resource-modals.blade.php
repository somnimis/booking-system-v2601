{{-- Facility Modal --}}
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
                    @forelse($facilities as $parent)
                        <div>
                            @if ($parent->children->isNotEmpty())
                                <div class="fw-bold mt-2 text-primary">{{ $parent->facility_name }}</div>
                                @foreach ($parent->children as $child)
                                    @php
                                        $img = $child->images->firstWhere('image_type', 'Primary') ?? $child->images->first();
                                        $imgUrl = $img ? $img->image_url : null;
                                    @endphp
                                    <div class="d-flex justify-content-between align-items-center p-2 border-bottom">
                                        <div class="d-flex align-items-center gap-2">
                                            @if ($imgUrl)
                                                <img src="{{ $imgUrl }}" style="width: 40px; height: 40px; border-radius: 4px; object-fit: cover;">
                                            @endif
                                            <div>
                                                <strong>{{ $child->facility_name }}</strong>
                                                <small class="text-muted d-block">{{ $child->facility_code }}</small>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-primary"
                                            x-on:click="$wire.addItem('facility', {{ $child->facility_id }}, '{{ $child->facility_name }}', {{ $child->base_fee }}, '{{ $child->rate_type }}', '{{ $imgUrl }}'); openModal = null;">Add</button>
                                    </div>
                                @endforeach
                            @else
                                @php
                                    $img = $parent->images->firstWhere('image_type', 'Primary') ?? $parent->images->first();
                                    $imgUrl = $img ? $img->image_url : null;
                                @endphp
                                <div class="d-flex justify-content-between align-items-center p-2 border-bottom">
                                    <div class="d-flex align-items-center gap-2">
                                        @if ($imgUrl)
                                            <img src="{{ $imgUrl }}" style="width: 40px; height: 40px; border-radius: 4px; object-fit: cover;">
                                        @endif
                                        <div>
                                            <strong>{{ $parent->facility_name }}</strong>
                                            <small class="text-muted d-block">{{ $parent->facility_code }}</small>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-primary"
                                        x-on:click="$wire.addItem('facility', {{ $parent->facility_id }}, '{{ $parent->facility_name }}', {{ $parent->base_fee }}, '{{ $parent->rate_type }}', '{{ $imgUrl }}'); openModal = null;">Add</button>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center text-muted py-4">No facilities found matching "{{ $facilitySearch }}".</div>
                    @endforelse
                </div>
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
                        @endphp
                        <div class="d-flex justify-content-between align-items-center p-2 border-bottom">
                            <div class="d-flex align-items-center gap-2">
                                @if ($imgUrl)
                                    <img src="{{ $imgUrl }}" style="width: 40px; height: 40px; border-radius: 4px; object-fit: cover;">
                                @endif
                                <div>
                                    <strong>{{ $item->equipment_name }}</strong>
                                    <small class="text-muted d-block">₱{{ number_format($item->base_fee, 2) }}</small>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-primary"
                                x-on:click="$wire.addItem('equipment', {{ $item->equipment_id }}, '{{ $item->equipment_name }}', {{ $item->base_fee }}, '{{ $item->rate_type }}', '{{ $imgUrl }}'); openModal = null;">Add</button>
                        </div>
                    @empty
                        <div class="text-center text-muted py-4">No equipment found matching "{{ $equipmentSearch }}".</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
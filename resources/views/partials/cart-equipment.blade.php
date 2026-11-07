@php $equipment = collect($items)->where('type', 'equipment'); @endphp
@if($equipment->isEmpty())
    <div class="text-muted empty-message">No equipment added yet.</div>
@else
    @foreach($equipment as $item)
        <div class="card h-100 border-0 shadow-sm mb-2">
            <div class="card-body p-2 d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="mb-1 fw-semibold">{{ $item['name'] }}</h6>
                    <span class="badge bg-primary">{{ $item['rate_type'] }}</span>
                </div>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeSelectedItem({{ $item['equipment_id'] }}, 'equipment')">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>
    @endforeach
@endif
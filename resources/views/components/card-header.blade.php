@props(['title', 'icon' => null])
<div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">{{ $title }}</h5>
    @if($icon)<i class="bi bi-{{ $icon }} text-muted"></i>@endif
</div>
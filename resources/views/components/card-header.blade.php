@props(['title', 'icon' => null])
<style>
    h6 {
    font-family: "Fraunces", Georgia, serif;
    font-weight: 700;
    color: var(--navy);
    margin: 0 0 0.2rem 0;
    letter-spacing: -0.3px;
    line-height: 1.2;
}
</style>
<div class="card-header d-flex justify-content-between align-items-center border-bottom-0">
    <h6 class="mb-0">{{ $title }}</h6>
    
    <div class="d-flex align-items-center gap-2">
        <!-- 1. Check if custom actions were passed to the slot -->
        @if(isset($actions))
            {{ $actions }}
        <!-- 2. Fallback to just the icon if no actions exist -->
        @elseif(isset($icon))
            <i class="bi bi-{{ $icon }} text-muted"></i>
        @endif
    </div>
</div>
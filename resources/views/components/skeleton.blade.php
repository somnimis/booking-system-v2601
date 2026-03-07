@props([
    'width' => '100%',
    'height' => 'auto',
    'lines' => 3,
    'lineHeights' => ['20px', '20px', '20px'],
    'margins' => ['10px', '10px', '0'],
    'colWidths' => [12, 11, 8],
    'rounded' => 'lg', // sm, md, lg, xl, pill, circle
    'speed' => '1.5s', // Animation speed
    'baseColor' => '#dfdfdf', // Base color
    'shimmerColor' => '#efefef' // Shimmer highlight color
])

@php
    $roundedClasses = [
        'sm' => 'rounded',
        'md' => 'rounded-md',
        'lg' => 'rounded-lg',
        'xl' => 'rounded-xl',
        '2xl' => 'rounded-2xl',
        '3xl' => 'rounded-3xl',
        'pill' => 'rounded-pill',
        'circle' => 'rounded-circle',
    ][$rounded] ?? 'rounded-lg';
    
    $borderRadius = match($rounded) {
        'pill' => '50px',
        'circle' => '50%',
        'sm' => '0.25rem',
        'md' => '0.375rem',
        'lg' => '0.5rem',
        'xl' => '0.75rem',
        '2xl' => '1rem',
        '3xl' => '1.5rem',
        default => '0.5rem',
    };
@endphp

<style>
    .shimmer-skeleton {
        width: 100%;
        display: flex;
        flex-direction: column;
    }
    
    .shimmer-line {
        background: {{ $baseColor }};
        background-image: linear-gradient(
            90deg,
            transparent 0%,
            rgba(255, 255, 255, 0.1) 20%,
            {{ $shimmerColor }} 50%,
            rgba(255, 255, 255, 0.1) 80%,
            transparent 100%
        );
        background-size: 200% 100%;
        background-repeat: no-repeat;
        background-position: 100% 0;
        animation: shimmer {{ $speed }} infinite;
        border-radius: {{ $borderRadius }};
    }
    
    @keyframes shimmer {
        0% {
            background-position: 150% 0;
        }
        100% {
            background-position: -50% 0;
        }
    }
    
    /* Alternative smoother shimmer */
    .shimmer-line-smooth {
        background: linear-gradient(
            90deg,
            {{ $baseColor }} 0%,
            {{ $shimmerColor }} 30%,
            {{ $baseColor }} 60%,
            {{ $baseColor }} 100%
        );
        background-size: 200% 100%;
        animation: shimmer-smooth {{ $speed }} infinite;
        border-radius: {{ $borderRadius }};
    }
    
    @keyframes shimmer-smooth {
        0% {
            background-position: 100% 0;
        }
        100% {
            background-position: -100% 0;
        }
    }
</style>

<div class="shimmer-skeleton" style="width: {{ $width }}; height: {{ $height }};">
    @for($i = 0; $i < $lines; $i++)
        <div class="shimmer-line" 
             style="
                 width: {{ ($colWidths[$i] ?? 12) / 12 * 100 }}%;
                 height: {{ $lineHeights[$i] ?? '20px' }};
                 margin-bottom: {{ $margins[$i] ?? '10px' }};
                 animation-delay: {{ $i * 0.1 }}s;
             ">
        </div>
    @endfor
</div>
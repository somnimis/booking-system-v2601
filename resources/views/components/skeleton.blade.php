@props([
    'lines'       => 1,
    'rounded'     => 'md',      // none | sm | md | lg | pill
    'intensity'   => 'medium',  // light | medium | strong
    'lineHeights' => null,      // array of CSS heights per line
    'colWidths'   => null,      // array of grid columns (1-12) per line
    'margins'     => null,      // array of margin-bottom per line
])

@php
    // Line heights: default to 16px if not provided per line
    $heights = is_array($lineHeights) && count($lineHeights)
        ? $lineHeights
        : array_fill(0, $lines, '16px');

    // Col widths (out of 12): default to full width
    $widths = is_array($colWidths) && count($colWidths)
        ? $colWidths
        : array_fill(0, $lines, 12);

    // Margins: default 10px between rows, none on last
    $mg = is_array($margins) && count($margins)
        ? $margins
        : array_merge(array_fill(0, max($lines - 1, 0), '10px'), ['0']);

    // Border radius map
    $radiusMap = [
        'none' => '0',
        'sm'   => '4px',
        'md'   => '8px',
        'lg'   => '12px',
        'pill' => '999px',
    ];
    $radius = $radiusMap[$rounded] ?? '8px';

    // Intensity → gradient colors
    $intensityMap = [
        'light'  => ['#f1f3f5', '#e9ecef'],
        'medium' => ['#e9ecef', '#dee2e6'],
        'strong' => ['#dee2e6', '#ced4da'],
    ];
    [$c1, $c2] = $intensityMap[$intensity] ?? $intensityMap['medium'];
@endphp

<div {{ $attributes->merge(['class' => 'skeleton-block']) }} role="status" aria-label="Loading">
    @for ($i = 0; $i < $lines; $i++)
        <div class="row g-0" style="margin-bottom: {{ $mg[$i] ?? '0' }};">
            <div class="col-{{ $widths[$i] ?? 12 }}">
                <div
                    class="skeleton-shimmer"
                    style="
                        height: {{ $heights[$i] ?? '16px' }};
                        border-radius: {{ $radius }};
                        --sk-c1: {{ $c1 }};
                        --sk-c2: {{ $c2 }};
                    "
                ></div>
            </div>
        </div>
    @endfor
</div>
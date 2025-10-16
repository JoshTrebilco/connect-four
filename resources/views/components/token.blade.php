@props(['color', 'size'])

<svg style="width: {{ $size }}px; height: {{ $size }}px;" viewBox="0 0 {{ $size }} {{ $size }}">
    <g class="player-token">
        <!-- Token glow effect -->
        <circle
            cx="{{ $size / 2 }}"
            cy="{{ $size / 2 }}"
            r="{{ $size * 0.45 }}"
            @class([
                'transition-opacity',
                'fill-cyan-400/30' => $color === 'blue',
                'fill-emerald-400/30' => $color === 'green',
                'fill-orange-400/30' => $color === 'red',
                'fill-yellow-400/30' => $color === 'yellow',
            ])
        />
        <!-- Token background -->
        <circle
            cx="{{ $size / 2 }}"
            cy="{{ $size / 2 }}"
            r="{{ $size * 0.42 }}"
            @class([
                'transition-opacity',
                'fill-cyan-500 stroke-cyan-400' => $color === 'blue',
                'fill-emerald-500 stroke-emerald-400' => $color === 'green',
                'fill-orange-500 stroke-orange-400' => $color === 'red',
                'fill-yellow-400 stroke-yellow-300' => $color === 'yellow',
            ])
        />
        <!-- Token border -->
        <circle
            cx="{{ $size / 2 }}"
            cy="{{ $size / 2 }}"
            r="{{ $size * 0.42 }}"
            @class([
                'fill-none stroke-[3]',
                'stroke-cyan-300' => $color === 'blue',
                'stroke-emerald-300' => $color === 'green',
                'stroke-orange-300' => $color === 'red',
                'stroke-yellow-200' => $color === 'yellow',
            ])
        />
    </g>
</svg>

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
                'fill-blue-500/20' => $color === 'blue',
                'fill-green-500/20' => $color === 'green',
                'fill-red-500/20' => $color === 'red',
                'fill-yellow-500/20' => $color === 'yellow',
            ])
        />
        <!-- Token background -->
        <circle
            cx="{{ $size / 2 }}"
            cy="{{ $size / 2 }}"
            r="{{ $size * 0.42 }}"
            @class([
                'transition-opacity',
                'fill-blue-500/50 stroke-blue-400' => $color === 'blue',
                'fill-green-500/50 stroke-green-400' => $color === 'green',
                'fill-red-500/50 stroke-red-400' => $color === 'red',
                'fill-yellow-500/50 stroke-yellow-400' => $color === 'yellow',
            ])
        />
        <!-- Token border -->
        <circle
            cx="{{ $size / 2 }}"
            cy="{{ $size / 2 }}"
            r="{{ $size * 0.42 }}"
            @class([
                'fill-none stroke-[3]',
                'stroke-blue-300' => $color === 'blue',
                'stroke-green-300' => $color === 'green',
                'stroke-red-300' => $color === 'red',
                'stroke-yellow-300' => $color === 'yellow',
            ])
        />
    </g>
</svg>

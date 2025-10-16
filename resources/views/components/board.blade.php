@props(['game', 'auth_player_id', 'channel'])

<div class="relative w-full max-w-[600px] mx-auto lg:mx-0 lg:w-[600px] h-[400px] sm:h-[500px] lg:h-[600px] bg-white/80 backdrop-blur-sm rounded-2xl shadow-2xl border border-cyan-200 p-3 sm:p-4 lg:p-6 flex-1">
    {{-- Column headers for dropping tokens --}}
    @if($game->active_player_id == $auth_player_id)
        <div class="grid grid-cols-7 gap-1 sm:gap-2 mb-2 sm:mb-4">
            @for($i = 0; $i < 7; $i++)
                <div class="h-4 sm:h-6 rounded-full bg-gradient-to-r from-cyan-500 to-teal-500 hover:from-cyan-400 hover:to-teal-400 cursor-pointer transition-all duration-200 shadow-lg hover:shadow-xl hover:scale-105 flex items-center justify-center text-white font-bold text-xs sm:text-sm" 
                        onclick="window.board.placeToken({{ $i }})">
                    {{ $i + 1 }}
                </div>
            @endfor
        </div>
    @else
        <div class="grid grid-cols-7 gap-1 sm:gap-2 mb-2 sm:mb-4">
            @for($i = 0; $i < 7; $i++)
                <div class="h-4 sm:h-6 rounded-full bg-cyan-100"></div>
            @endfor
        </div>
    @endif

    {{-- Game board --}}
    <div class="grid grid-cols-7 gap-1 sm:gap-2">
        @for($i = 5; $i >= 0; $i--)
            @for($j = 0; $j < 7; $j++)
                <div class="w-12 h-12 sm:w-16 sm:h-16 lg:w-18 lg:h-18 rounded-full flex items-center justify-center bg-sky-50 shadow-inner border-2 border-cyan-200 hover:border-cyan-300 transition-all duration-200" 
                     data-row="{{ $i }}" 
                     data-col="{{ $j }}">
                    @if($game->board()->tokenColorAt($j, $i))
                        <x-token :color="$game->board()->tokenColorAt($j, $i)" :size="32" />
                    @else
                        <div class="w-8 h-8 sm:w-10 sm:h-10 lg:w-12 lg:h-12 rounded-full bg-cyan-100 shadow-inner"></div>
                    @endif
                </div>
            @endfor
        @endfor
    </div>
</div>


<script>
    class Board {
        constructor() {
            this.channel = window.Echo.channel(@json($channel));
        }

        handleEvent(event, gameState) {
            if (event === 'App\\Events\\Gameplay\\PlacedToken') {
                // Trigger token drop animation
                this.animateTokenDrop(gameState);
            }
        }

        animateTokenDrop(gameState) {
            // Find the last placed token by comparing with previous state
            // For now, we'll animate all tokens and let CSS handle the timing
            const tokenCells = document.querySelectorAll('[data-row][data-col]');
            
            tokenCells.forEach(cell => {
                const row = parseInt(cell.dataset.row);
                const col = parseInt(cell.dataset.col);
                
                // Check if this cell has a token
                if (cell.querySelector('svg')) {
                    // Determine animation class based on row (higher row = longer drop)
                    let animationClass = 'animate-drop-token';
                    if (row >= 4) {
                        animationClass = 'animate-drop-token-short';
                    } else if (row <= 1) {
                        animationClass = 'animate-drop-token-long';
                    }
                    
                    // Add animation class
                    cell.classList.add(animationClass);
                    
                    // Remove animation class after animation completes
                    setTimeout(() => {
                        cell.classList.remove(animationClass);
                    }, 1000);
                }
            });
        }

        async placeToken(column) {
            try {
                const url = '{{ $auth_player_id ? route('players.placeToken', ['game_id' => $game->id, 'player_id' => $auth_player_id, 'column' => 'PLACEHOLDER']) : null }}';
                const finalUrl = url.replace('PLACEHOLDER', column);
                
                await axios.post(finalUrl, {
                    _token: '{{ csrf_token() }}'
                });
            } catch (error) {
                console.error('Error placing token:', error.response.data);
            }
        }

        delay(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        }

        init() {
            this.channel.listen('BroadcastEvent', (data) => {
                this.handleEvent(data.event, data.playerState);
            });
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        window.board = new Board();
        window.board.init();
    });
</script>
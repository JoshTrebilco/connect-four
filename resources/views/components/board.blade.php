@props(['game', 'auth_player_id', 'channel'])

<div class="relative max-w-[600px] mx-auto lg:mx-0 lg:w-[600px] h-[400px] sm:h-[500px] lg:h-[600px] bg-white/80 backdrop-blur-sm rounded-2xl shadow-2xl border border-cyan-200 p-3 sm:p-4 lg:p-6 flex-1">
    {{-- Column headers for dropping tokens --}}
    @if($game->active_player_id == $auth_player_id)
        <div class="grid grid-cols-7 gap-1 sm:gap-2 mb-2 sm:mb-4" id="column-headers">
            @for($i = 0; $i < 7; $i++)
                <div class="h-4 sm:h-6 rounded-full bg-gradient-to-r from-cyan-500 to-teal-500 hover:from-cyan-400 hover:to-teal-400 cursor-pointer transition-all duration-200 shadow-lg hover:shadow-xl hover:scale-105 flex items-center justify-center text-white font-bold text-xs sm:text-sm" 
                        data-column="{{ $i }}"
                        onclick="window.board.placeToken({{ $i }})">
                    {{ $i + 1 }}
                </div>
            @endfor
        </div>
    @else
        <div class="grid grid-cols-7 gap-1 sm:gap-2 mb-2 sm:mb-4">
            @for($i = 0; $i < 7; $i++)
                <div class="h-4 sm:h-6 rounded-full bg-cyan-100" data-column="{{ $i }}"></div>
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
                        <x-token :color="$game->board()->tokenColorAt($j, $i)" :size="64" />
                    @else
                        <div class="w-8 h-8 sm:w-10 sm:h-10 lg:w-12 lg:h-12 rounded-full bg-cyan-100 shadow-inner"></div>
                    @endif
                </div>
            @endfor
        @endfor
    </div>

    {{-- Pre-rendered tokens for animation --}}
    <div id="token-drop-overlay" class="absolute inset-0 pointer-events-none hidden">
        {{-- Blue token --}}
        <div id="token-blue" class="absolute w-12 h-12 sm:w-16 sm:h-16 lg:w-18 lg:h-18 rounded-full flex items-center justify-center opacity-0 -left-20 -top-20">
            <x-token color="blue" :size="64" />
        </div>
        {{-- Green token --}}
        <div id="token-green" class="absolute w-12 h-12 sm:w-16 sm:h-16 lg:w-18 lg:h-18 rounded-full flex items-center justify-center opacity-0 -left-20 -top-20">
            <x-token color="green" :size="64" />
        </div>
        {{-- Red token --}}
        <div id="token-red" class="absolute w-12 h-12 sm:w-16 sm:h-16 lg:w-18 lg:h-18 rounded-full flex items-center justify-center opacity-0 -left-20 -top-20">
            <x-token color="red" :size="64" />
        </div>
        {{-- Yellow token --}}
        <div id="token-yellow" class="absolute w-12 h-12 sm:w-16 sm:h-16 lg:w-18 lg:h-18 rounded-full flex items-center justify-center opacity-0 -left-20 -top-20">
            <x-token color="yellow" :size="64" />
        </div>
    </div>
</div>


<script>
    class Board {
        constructor() {
            this.channel = window.Echo.channel(@json($channel));
            this.overlay = document.getElementById('token-drop-overlay');
            this.tokens = {
                blue: document.getElementById('token-blue'),
                green: document.getElementById('token-green'),
                red: document.getElementById('token-red'),
                yellow: document.getElementById('token-yellow')
            };
        }

        handleEvent(event, gameState, playerState) {
            if (event === 'App\\Events\\Gameplay\\PlacedToken') {
                this.animateTokenDrop(playerState.last_placed_column, playerState.color);
            }
        }   

        async animateTokenDrop(column, color) {
            // Show the overlay
            this.overlay.classList.remove('hidden');
            
            // Get the column header position
            const columnHeader = document.querySelector(`[data-column="${column}"]`);
            if (!columnHeader) return;
            
            const headerRect = columnHeader.getBoundingClientRect();
            const boardRect = this.overlay.getBoundingClientRect();
            
            // Position the token at the column header, centered
            const startX = headerRect.left - boardRect.left; // Center horizontally
            const startY = headerRect.bottom - boardRect.top - 8; // Just below the header
            
            // Get the appropriate pre-rendered token
            const token = this.tokens[color];
            if (!token) {
                console.warn(`Token color ${color} not found`);
                return;
            }
            
            // Reset token position and make it visible
            token.style.left = `${startX}px`;
            token.style.top = `${startY}px`;
            token.style.opacity = '1';
            token.style.transform = 'translateY(0)';
            token.style.transition = 'none'; // Reset any previous transitions
            
            // Animate the drop
            await this.delay(100); // Small delay to show the token
            
            // Drop animation
            token.style.transition = 'transform 0.4s ease-in, opacity 0.3s ease-out';
            token.style.transform = 'translateY(40px)'; // Drop down 40px
            
            // Fade out after drop
            await this.delay(400);
            token.style.opacity = '0';
            
            // Hide overlay and refresh page after animation
            await this.delay(300);
            this.overlay.classList.add('hidden');
            
            // Reset token position for next use
            token.style.left = '-80px';
            token.style.top = '-80px';
            token.style.transform = 'translateY(0)';
            token.style.transition = 'none';
            
            window.location.reload();
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
                this.handleEvent(data.event, data.gameState, data.playerState);
            });
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        window.board = new Board();
        window.board.init();
    });
</script>
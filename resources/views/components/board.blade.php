@props(['game', 'auth_player_id', 'channel'])

<div class="relative w-[600px] h-[600px] bg-white/80 backdrop-blur-sm rounded-2xl shadow-2xl border border-cyan-200 p-6 flex-1">
    {{-- Column headers for dropping tokens --}}
    @if($game->active_player_id == $auth_player_id)
        <div class="grid grid-cols-7 gap-2 mb-4">
            @for($i = 0; $i < 7; $i++)
                <div class="h-6 rounded-full bg-gradient-to-r from-cyan-500 to-teal-500 hover:from-cyan-400 hover:to-teal-400 cursor-pointer transition-all duration-200 shadow-lg hover:shadow-xl hover:scale-105 flex items-center justify-center text-white font-bold text-sm" 
                        onclick="window.board.placeToken({{ $i }})">
                    {{ $i + 1 }}
                </div>
            @endfor
        </div>
    @else
        <div class="grid grid-cols-7 gap-2 mb-4">
            @for($i = 0; $i < 7; $i++)
                <div class="h-6 rounded-full bg-cyan-100"></div>
            @endfor
        </div>
    @endif

    {{-- Game board --}}
    <div class="grid grid-cols-7 gap-2">
        @for($i = 5; $i >= 0; $i--)
            @for($j = 0; $j < 7; $j++)
                <div class="w-18 h-18 rounded-full flex items-center justify-center bg-sky-50 shadow-inner border-2 border-cyan-200 hover:border-cyan-300 transition-all duration-200">
                    @if($game->board()->tokenColorAt($j, $i))
                        <x-token :color="$game->board()->tokenColorAt($j, $i)" :size="60" />
                    @else
                        <div class="w-12 h-12 rounded-full bg-cyan-100 shadow-inner"></div>
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
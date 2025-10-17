@props(['game', 'auth_player_id', 'channel'])
<div class="space-y-6">
    @if(! $game->hasPlayer($auth_player_id) && ! $game->isInProgress() && ! $game->ended)
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl p-4 sm:p-6 border border-cyan-200 shadow-xl">
            <div class="flex items-center space-x-3 mb-4">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-gradient-to-br from-cyan-400 to-teal-500 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 5a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm0 0v3m-4 1h8m-8 0c0 2 1.5 3 4 3s4-1 4-3m-8 0l-1 8h10l-1-8" />
                        </svg>
                    </div>
                </div>
                <h3 class="text-lg font-semibold text-slate-700">
                    Choose Your Token
                </h3>
            </div>
            <div class="flex justify-center gap-4">
                @foreach($game->available_colors as $color)
                    <form action="{{ route('players.join', ['game_id' => $game->id]) }}" method="post">
                        @csrf
                        <input type="hidden" name="color" value="{{ $color }}">
                        <button type="submit" class="transform transition hover:scale-110">
                            <x-token :color="$color" :size="50" />
                        </button>
                    </form>
                @endforeach
            </div>
        </div>
    @endif

    @if ($game->hasPlayer($auth_player_id) && !$game->isInProgress() && !$game->ended) 
        <!-- Share Game Section -->
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl p-4 sm:p-6 border border-teal-200 shadow-xl">
            <div class="flex items-center space-x-3 mb-4">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-gradient-to-br from-teal-400 to-emerald-500 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                        </svg>
                    </div>
                </div>
                <h3 class="text-lg font-semibold text-slate-700">
                    Invite Players
                </h3>
            </div>

            <div id="copy-section" class="space-y-3">
                <p class="text-slate-600 text-sm">Share this link with your friends to invite them to join your game:</p>

                <div class="flex gap-2">
                    <input
                        type="text"
                        readonly
                        value="{{ url('/games/' . $game->id) }}"
                        class="w-full px-4 py-2 rounded-lg border-2 border-teal-300 bg-white/70 text-slate-700 text-sm focus:outline-none"
                    />
                    <button
                        id="copy-button"
                        class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-teal-500 to-emerald-500 text-white rounded-lg font-semibold transform transition hover:translate-y-[-2px]"
                    >
                        <span id="copy-text">Copy</span>
                        <svg id="copy-icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                        </svg>
                        <svg id="check-icon" class="w-4 h-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if(! $game->hasPlayer($auth_player_id) && $game->isInProgress())
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl p-4 sm:p-6 border border-orange-200 shadow-xl lg:max-w-60">
                <div>
                    <h3 class="text-lg font-semibold text-slate-700">
                        Game in Progress
                    </h3>
                    <p class="text-slate-600 text-sm mt-4">
                        You're spectating this game.
                    </p>
                    <p class="text-slate-600 text-sm mt-2">
                        Wait for it to finish before joining a new one!
                    </p>
                </div>
        </div>
    @endif

    <div class="grid gap-4 sm:gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-1">
        <!-- Players List -->
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl p-4 sm:p-6 border border-cyan-200 shadow-xl">
            <h3 class="text-lg font-semibold text-slate-700 mb-4">Players</h3>
            <ul class="space-y-3">
                @foreach ($game->players() as $player)
                    <li class="flex items-center space-x-3">
                        <x-token :color="$player->color" :size="25" />

                        <div class="flex items-center space-x-2 flex-grow">
                            <span class="text-slate-700">{{ $player->name }}</span>

                            <div class="flex items-center gap-2 ml-auto">
                                @if ($player->id == $auth_player_id)
                                    <span class="inline-flex items-center rounded-md bg-cyan-400/10 px-2 py-1 text-xs font-medium text-cyan-600 ring-1 ring-inset ring-cyan-400/30">
                                        You
                                    </span>
                                @endif

                                @if ($player->id == $game->activePlayer()?->id && ! $game->ended)
                                    <svg class="w-4 h-4 text-cyan-500 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                @endif

                                @if ($player->id == $game->winner()?->id)
                                    <span class="inline-flex items-center rounded-md bg-cyan-400/10 px-2 py-1 text-xs font-medium text-cyan-600 ring-1 ring-inset ring-cyan-400/30">
                                        Winner
                                    </span>
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>

        <!-- Turn Text Section -->
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl p-4 sm:p-6 border border-teal-200 shadow-xl {{ $game->isInProgress() && ! $game->ended ? '' : 'hidden' }}">
            <div class="flex justify-center">
                <div class="flex flex-col items-center">
                    <div class="mt-2 text-center text-slate-700 h-6 w-32 flex items-center justify-center" id="turn-text">
                        <!-- Turn text will be updated by JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    class Panel {
        constructor() {
            this.players = {!! json_encode($game->players()->map(fn($p) => ['id' => (string)$p->id, 'name' => $p->name])) !!};
            this.authPlayerId = '{{ $auth_player_id ?? 'null' }}';
            this.activePlayerId = '{{ $game->activePlayer()?->id ?? 'null' }}';
            this.channel = window.Echo.channel(@json($channel));
        }

        updateUI() {
            const turnText = document.getElementById('turn-text');

            const isMyTurn = this.authPlayerId && this.activePlayerId === this.authPlayerId;

            this.updateTurnText(turnText, isMyTurn);
        }

        updateTurnText(turnText, isMyTurn) {
            if (isMyTurn) {
                turnText.innerHTML = '<span class="inline-block animate-[pulse_2s_ease-in-out_infinite]">It\'s your turn</span>';
            } else {
                const activePlayer = this.players.find(p => p.id === this.activePlayerId);
                turnText.textContent = `It's ${activePlayer?.name || 'Unknown Player'}'s turn`;
            }
        }

        handleEvent(event, gameState) {
            if (event === 'App\\Events\\Gameplay\\EndedTurn' && gameState?.active_player_id !== undefined) {
                this.activePlayerId = String(gameState.active_player_id);
                this.updateUI();
            }
        }

        setupCopyButton() {
            const copyButton = document.getElementById('copy-button');
            if (!copyButton) return;

            copyButton.addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText('{{ url('/games/' . $game->id) }}');
                    this.showCopySuccess();
                } catch (err) {
                    console.error('Copy failed:', err);
                }
            });
        }

        showCopySuccess() {
            const copyText = document.getElementById('copy-text');
            const copyIcon = document.getElementById('copy-icon');
            const checkIcon = document.getElementById('check-icon');
            
            copyText.textContent = 'Copied!';
            copyIcon.classList.add('hidden');
            checkIcon.classList.remove('hidden');
            
            setTimeout(() => {
                copyText.textContent = 'Copy';
                copyIcon.classList.remove('hidden');
                checkIcon.classList.add('hidden');
            }, 2000);
        }

        init() {
            const currentRoll = {{ $game->last_roll ?? 'null' }};
            if (currentRoll) {
                document.getElementById('die-container').innerHTML = this.createDie(currentRoll);
            }

            this.channel.listen('BroadcastEvent', (data) => {
                this.handleEvent(data.event, data.gameState);
            });

            this.updateUI();
            this.setupCopyButton();
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        window.panel = new Panel();
        window.panel.init();
    });
</script>

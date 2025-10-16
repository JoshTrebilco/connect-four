<x-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Hero Section -->
        <div class="text-center mb-16">
            <h1 class="text-6xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-teal-600 to-cyan-500 mb-4">
                Connect Four
            </h1>
            <p class="text-xl text-slate-700">
                Drop your pieces and connect four to win! 🏖️
            </p>
        </div>

        <!-- Game Options Cards -->
        <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
            <!-- Join Game Card -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl transform transition duration-500 hover:scale-105 border border-cyan-200">
                <div class="p-8">
                    <div class="flex items-center justify-center w-16 h-16 bg-gradient-to-br from-cyan-400 to-teal-500 rounded-full mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-slate-700 mb-4">Join a Game</h2>
                    <p class="text-slate-600 mb-6">Got an invite? Enter the game code below to join your friends!</p>

                    <form id="joinGameForm" class="space-y-4">
                        <input
                            type="text"
                            id="gameId"
                            name="game_id"
                            class="w-full px-4 py-3 rounded-lg border-2 border-cyan-300 bg-white/70 text-slate-700 placeholder-slate-400 focus:border-cyan-500 focus:ring-0 focus:outline-none"
                            placeholder="Enter game code..."
                            required
                        />
                        <button
                            type="submit"
                            id="joinButton"
                            class="w-full bg-gradient-to-r from-cyan-500 to-teal-500 text-white rounded-lg px-4 py-3 font-semibold transform transition hover:translate-y-[-2px] disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Join Game
                        </button>
                    </form>

                    <script>
                        document.getElementById('joinGameForm').addEventListener('submit', function(e) {
                            e.preventDefault();
                            
                            const gameId = document.getElementById('gameId').value.trim();
                            const button = document.getElementById('joinButton');
                            
                            if (gameId === '') {
                                alert('Please enter a game code');
                                return;
                            }
                            
                            // Disable button to prevent double submission
                            button.disabled = true;
                            button.textContent = 'Joining...';
                            
                            // Redirect to the game
                            window.location.href = `./games/${gameId}`;
                        });

                        // Enable/disable button based on input
                        document.getElementById('gameId').addEventListener('input', function() {
                            const button = document.getElementById('joinButton');
                            const gameId = this.value.trim();
                            
                            button.disabled = gameId === '';
                        });
                    </script>
                </div>
            </div>

            <!-- Create Game Card -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl transform transition duration-500 hover:scale-105 border border-teal-200">
                <div class="p-8 h-full flex flex-col">
                    <div class="flex items-center justify-center w-16 h-16 bg-gradient-to-br from-teal-400 to-emerald-500 rounded-full mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-slate-700 mb-4">Start New Game</h2>
                    <p class="text-slate-600 mb-6">Be the host! Create a new game and invite your friends to join the fun.</p>

                    <form action="{{ route('games.store') }}" method="post" class="mt-auto">
                        @csrf
                        <button type="submit"
                            class="w-full bg-gradient-to-r from-teal-500 to-emerald-500 text-white rounded-lg px-4 py-3 font-semibold transform transition hover:translate-y-[-2px]">
                            Create New Game
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Auth Section -->
        <div class="mt-12 text-center">
            @auth
                <div class="inline-flex items-center space-x-2 bg-white/80 backdrop-blur-sm rounded-full px-6 py-3 shadow-lg border border-cyan-200">
                    <span class="text-slate-700">Playing as {{ auth()->user()->name }}</span>
                    <form action="{{ route('logout.destroy') }}" method="post" class="inline">
                        @csrf
                        <button type="submit"
                            class="text-orange-500 hover:text-orange-600 font-semibold">
                            Logout
                        </button>
                    </form>
                </div>
            @else
                <a href="{{ route('login.index') }}"
                    class="inline-flex items-center space-x-2 bg-white/80 backdrop-blur-sm rounded-full px-6 py-3 shadow-lg text-slate-700 hover:shadow-xl transition duration-300 border border-cyan-200">
                    <span>Login to Save Progress</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            @endauth
        </div>

        <!-- Fun Footer -->
        <div class="mt-16 text-center text-teal-600">
            <p class="text-sm">🏖️ Drop your pieces and start playing! 🏖️</p>
        </div>
    </div>
</x-layout>

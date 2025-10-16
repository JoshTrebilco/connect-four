<x-layout>
    <div class="min-h-screen w-full">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <!-- Hero Section -->
            <div class="text-center mb-16">
                <h1 class="text-6xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-teal-600 to-cyan-500 mb-4">
                    Welcome
                </h1>
                <p class="text-xl text-slate-700">
                    Ready to start your adventure? 🏖️
                </p>
            </div>

            <!-- Login Card -->
            <div class="max-w-md mx-auto">
                <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl transform transition duration-500 hover:scale-105 border border-cyan-200">
                    <div class="p-8">
                        <div class="flex items-center justify-center w-16 h-16 bg-gradient-to-br from-cyan-400 to-teal-500 rounded-full mb-6 mx-auto">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>

                        <form class="space-y-6" action="{{ route('login.store') }}" method="POST">
                            @csrf
                            @if($game_id)
                                <input type="hidden" name="game_id" value="{{ $game_id }}">
                            @endif

                            <div>
                                <label for="name" class="block text-lg font-semibold text-slate-700 mb-2">
                                    Enter your name:
                                </label>
                                <input
                                    id="name"
                                    name="name"
                                    autocomplete="name"
                                    value="{{ old('name') }}"
                                    required
                                    class="w-full px-4 py-3 rounded-lg border-2 border-cyan-300 bg-white/70 text-slate-700 placeholder-slate-400 focus:border-cyan-500 focus:ring-0 focus:outline-none"
                                    placeholder="Your adventurer name..."
                                >
                            </div>

                            <button
                                type="submit"
                                class="w-full bg-gradient-to-r from-cyan-500 to-teal-500 text-white rounded-lg px-4 py-3 font-semibold transform transition hover:translate-y-[-2px]"
                            >
                                Begin Adventure
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Back Link -->
                <div class="mt-8 text-center">
                    <a href="{{ route('games.index') }}"
                        class="inline-flex items-center space-x-2 text-slate-700 hover:translate-x-[-2px] transition-transform">
                        <svg class="w-5 h-5 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                        <span>Back to Games</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-layout>

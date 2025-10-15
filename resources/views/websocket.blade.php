<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebSocket Debug Console</title>
    @vite(['resources/js/app.js'])
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=play:400,500,600&display=swap" rel="stylesheet" />
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'mono': ['JetBrains Mono', 'Fira Code', 'Monaco', 'Consolas', 'monospace'],
                        'sans': ['Play', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>
</head>
<body class="font-sans antialiased bg-slate-950 min-h-screen bg-gradient-to-b from-slate-950 to-purple-950">
    <!-- Header -->
    <header class="bg-slate-900/50 backdrop-blur-sm border-b border-slate-800/50 shadow-xl">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 bg-gradient-to-br from-purple-600 to-blue-500 rounded-xl flex items-center justify-center shadow-lg shadow-purple-500/25">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-blue-300">WebSocket Debug Console</h1>
                        <p class="text-sm text-blue-200/80">Real-time event monitoring with Laravel Reverb</p>
                    </div>
                </div>
                <div class="flex items-center space-x-8">
                    <!-- Stats in header -->
                    <div class="flex items-center space-x-6">
                        <div class="text-center">
                            <div class="text-xs text-blue-200/60 uppercase tracking-wide font-medium mb-1">Events</div>
                            <div class="text-xl font-bold text-blue-300" id="event-count">0</div>
                        </div>
                        <div class="text-center">
                            <div class="text-xs text-blue-200/60 uppercase tracking-wide font-medium mb-1">Players</div>
                            <div class="text-xl font-bold text-blue-300" id="player-count">0</div>
                        </div>
                        <div class="text-center">
                            <div class="text-xs text-blue-200/60 uppercase tracking-wide font-medium mb-1">Last Event</div>
                            <div class="text-xl font-semibold text-blue-300" id="last-event-time">Never</div>
                        </div>
                        <div class="text-center">
                            <div class="text-xs text-blue-200/60 uppercase tracking-wide font-medium mb-1">Status</div>
                            <div id="status" class="disconnected text-sm font-semibold">Disconnected</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-6 py-6">
        <!-- Message Log -->
        <div class="bg-slate-900/50 backdrop-blur-sm rounded-2xl shadow-xl border border-slate-800/50 h-[calc(100vh-8rem)] flex flex-col">
            <div class="px-6 py-5 border-b border-slate-800/50 flex-shrink-0">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-2 h-2 bg-blue-400 rounded-full shadow-lg shadow-blue-500/50"></div>
                        <h2 class="text-lg font-semibold text-blue-300">Event Log</h2>
                    </div>
                    <div class="flex items-center space-x-3">
                        <div class="relative">
                            <input 
                                type="text" 
                                id="search-input" 
                                placeholder="Search events, players, or content..." 
                                class="bg-slate-800/50 border border-slate-700/50 rounded-lg px-4 py-2 pr-10 text-sm text-blue-200 placeholder-blue-200/50 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-200 w-80"
                            />
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                <svg class="w-4 h-4 text-blue-200/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <button id="clear-search" class="text-sm text-blue-200 hover:text-blue-300 hover:bg-slate-800/50 px-3 py-2 rounded-lg font-medium transition-all duration-200 hidden">
                            Clear Search
                        </button>
                        <button id="clear-log" class="text-sm text-blue-200 hover:text-blue-300 hover:bg-slate-800/50 px-3 py-2 rounded-lg font-medium transition-all duration-200">
                            Clear Log
                        </button>
                        <button id="toggle-all" class="text-sm text-blue-200 hover:text-blue-300 hover:bg-slate-800/50 px-3 py-2 rounded-lg font-medium transition-all duration-200">
                            Expand All
                        </button>
                    </div>
                </div>
            </div>
            <div id="messages" class="flex-1 overflow-y-auto bg-slate-900/30"></div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const status = document.getElementById('status');
            const messages = document.getElementById('messages');
            const eventCount = document.getElementById('event-count');
            const lastEventTime = document.getElementById('last-event-time');
            const playerCount = document.getElementById('player-count');
            const clearLogBtn = document.getElementById('clear-log');
            const toggleAllBtn = document.getElementById('toggle-all');
            const searchInput = document.getElementById('search-input');
            const clearSearchBtn = document.getElementById('clear-search');

            let eventCounter = 0;
            let uniquePlayers = new Set();
            let allMessages = []; // Store all messages for filtering

            // Button event listeners
            clearLogBtn.addEventListener('click', function() {
                messages.innerHTML = '';
                eventCounter = 0;
                uniquePlayers.clear();
                allMessages = [];
                allExpanded = false;
                toggleAllBtn.textContent = 'Expand All';
                searchInput.value = '';
                clearSearchBtn.classList.add('hidden');
                lastEventTime.textContent = 'Never';
                updateStats();
            });

            // Search functionality
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                if (searchTerm) {
                    clearSearchBtn.classList.remove('hidden');
                    filterMessages(searchTerm);
                } else {
                    clearSearchBtn.classList.add('hidden');
                    showAllMessages();
                }
            });

            clearSearchBtn.addEventListener('click', function() {
                searchInput.value = '';
                this.classList.add('hidden');
                showAllMessages();
            });

            let allExpanded = false;

            toggleAllBtn.addEventListener('click', function() {
                const contents = messages.querySelectorAll('.event-content');
                const icons = messages.querySelectorAll('.toggle-icon');
                
                if (allExpanded) {
                    // Collapse all
                    contents.forEach(content => content.classList.add('hidden'));
                    icons.forEach(icon => icon.textContent = '▶');
                    toggleAllBtn.textContent = 'Expand All';
                    allExpanded = false;
                } else {
                    // Expand all
                    contents.forEach(content => content.classList.remove('hidden'));
                    icons.forEach(icon => icon.textContent = '▼');
                    toggleAllBtn.textContent = 'Collapse All';
                    allExpanded = true;
                }
            });

            function updateStats() {
                eventCount.textContent = eventCounter;
                playerCount.textContent = uniquePlayers.size;
            }

            function updateGlobalToggleState() {
                const contents = messages.querySelectorAll('.event-content');
                const visibleCount = Array.from(contents).filter(content => !content.classList.contains('hidden')).length;
                const totalCount = contents.length;
                
                if (totalCount === 0) {
                    // No items, reset to default state
                    allExpanded = false;
                    toggleAllBtn.textContent = 'Expand All';
                } else if (visibleCount === totalCount) {
                    // All items are expanded
                    allExpanded = true;
                    toggleAllBtn.textContent = 'Collapse All';
                } else if (visibleCount === 0) {
                    // All items are collapsed
                    allExpanded = false;
                    toggleAllBtn.textContent = 'Expand All';
                } else {
                    // Mixed state - show expand all since not all are expanded
                    allExpanded = false;
                    toggleAllBtn.textContent = 'Expand All';
                }
            }

            function getPlayerColorClasses(color) {
                const colorMap = {
                    'blue': 'bg-blue-500/20 text-blue-300 border-blue-500/30',
                    'green': 'bg-green-500/20 text-green-300 border-green-500/30',
                    'red': 'bg-red-500/20 text-red-300 border-red-500/30',
                    'yellow': 'bg-yellow-500/20 text-yellow-300 border-yellow-500/30'
                };
                return colorMap[color] || 'bg-purple-500/20 text-purple-300 border-purple-500/30';
            }

            function filterMessages(searchTerm) {
                messages.innerHTML = '';
                const filteredMessages = allMessages.filter(messageData => {
                    const searchableText = messageData.searchableText.toLowerCase();
                    return searchableText.includes(searchTerm);
                });
                
                filteredMessages.forEach(messageData => {
                    // Remove any existing highlights first
                    removeHighlights(messageData.element);
                    // Apply new highlights
                    applyHighlights(messageData.element, searchTerm);
                    messages.appendChild(messageData.element);
                });
            }

            function showAllMessages() {
                messages.innerHTML = '';
                allMessages.forEach(messageData => {
                    // Remove highlights when showing all messages
                    removeHighlights(messageData.element);
                    messages.appendChild(messageData.element);
                });
            }

            function highlightSearchTerm(text, searchTerm) {
                if (!searchTerm) return text;
                const regex = new RegExp(`(${searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
                return text.replace(regex, '<mark class="bg-blue-300/60 text-blue-100 rounded font-medium">$1</mark>');
            }

            function applyHighlights(element, searchTerm) {
                if (!searchTerm) return;
                
                // Highlight text in all text nodes
                const walker = document.createTreeWalker(
                    element,
                    NodeFilter.SHOW_TEXT,
                    null,
                    false
                );
                
                const textNodes = [];
                let node;
                while (node = walker.nextNode()) {
                    textNodes.push(node);
                }
                
                textNodes.forEach(textNode => {
                    const parent = textNode.parentNode;
                    if (parent.tagName !== 'MARK') { // Don't highlight inside existing marks
                        const highlightedHTML = highlightSearchTerm(textNode.textContent, searchTerm);
                        if (highlightedHTML !== textNode.textContent) {
                            const wrapper = document.createElement('span');
                            wrapper.innerHTML = highlightedHTML;
                            parent.replaceChild(wrapper, textNode);
                        }
                    }
                });
            }

            function removeHighlights(element) {
                const marks = element.querySelectorAll('mark');
                marks.forEach(mark => {
                    const parent = mark.parentNode;
                    parent.replaceChild(document.createTextNode(mark.textContent), mark);
                    parent.normalize(); // Merge adjacent text nodes
                });
            }

            function log(message) {
                eventCounter++;
                
                const msg = document.createElement('div');
                msg.className = 'border-b border-slate-800/50 last:border-b-0 hover:bg-slate-800/30 transition-colors';

                // Create header with timestamp, event info, and toggle button
                const header = document.createElement('div');
                header.className = 'px-6 py-4 flex justify-between items-center cursor-pointer hover:bg-slate-800/50 transition-all duration-200 group';
                
                const timestamp = new Date().toLocaleTimeString();
                const timestampEl = document.createElement('span');
                timestampEl.className = 'text-xs text-blue-200/60 font-mono mr-5 bg-slate-800/50 px-2 py-1 rounded-md border border-slate-700/50';
                timestampEl.textContent = timestamp;
                
                // Extract event name and player info
                let eventInfo = '';
                let playerName = '';
                let playerColor = '';
                if (typeof message === 'object' && message !== null) {
                    if (message.event) {
                        eventInfo = message.event.split('\\').pop();
                    }
                    if (message.playerState && message.playerState.name) {
                        playerName = message.playerState.name;
                        playerColor = message.playerState.color || 'purple';
                        uniquePlayers.add(playerName);
                    }
                } else if (typeof message === 'string' && message.startsWith('{')) {
                    try {
                        const json = JSON.parse(message);
                        if (json.event) {
                            eventInfo = json.event.split('\\').pop();
                        }
                        if (json.playerState && json.playerState.name) {
                            playerName = json.playerState.name;
                            playerColor = json.playerState.color || 'purple';
                            uniquePlayers.add(playerName);
                        }
                    } catch (e) {
                        eventInfo = 'Text Message';
                    }
                } else {
                    eventInfo = 'Text Message';
                }
                
                const eventEl = document.createElement('div');
                eventEl.className = 'flex-1 flex items-center space-x-4';
                
                if (playerName) {
                    const playerEl = document.createElement('span');
                    playerEl.className = `inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border ${getPlayerColorClasses(playerColor)}`;
                    playerEl.textContent = playerName;
                    eventEl.appendChild(playerEl);
                }
                
                const eventName = document.createElement('span');
                eventName.className = 'text-sm font-semibold text-blue-300';
                eventName.textContent = eventInfo;
                eventEl.appendChild(eventName);
                
                const toggleIcon = document.createElement('span');
                toggleIcon.className = 'toggle-icon text-blue-200/60 text-sm group-hover:text-blue-300 transition-all duration-200 bg-slate-800/50 group-hover:bg-slate-700/50 w-6 h-6 rounded-full flex items-center justify-center border border-slate-700/50';
                toggleIcon.textContent = '▶';
                
                header.appendChild(timestampEl);
                header.appendChild(eventEl);
                header.appendChild(toggleIcon);
                
                // Create content area (collapsed by default)
                const content = document.createElement('div');
                content.className = 'event-content px-6 pb-6 bg-slate-800/30 hidden';
                
                // Handle different types of messages
                if (typeof message === 'object' && message !== null) {
                    // If passed a direct object, format it
                    formatJsonData(message, content);
                } else if (typeof message === 'string' && message.startsWith('{')) {
                    // If passed a JSON string, parse and format it
                    try {
                        const json = JSON.parse(message);
                        formatJsonData(json, content);
                    } catch (e) {
                        // If JSON parsing fails, display the original message
                        content.textContent = message;
                        content.className = 'event-content px-6 pb-6 bg-slate-800/30 text-blue-200 text-sm font-mono hidden';
                    }
                } else {
                    // For normal text messages
                    content.textContent = message;
                    content.className = 'event-content px-6 pb-6 bg-slate-800/30 text-blue-200 text-sm font-mono hidden';
                }

                // Add toggle functionality
                header.addEventListener('click', function() {
                    const isCollapsed = content.classList.contains('hidden');
                    if (isCollapsed) {
                        content.classList.remove('hidden');
                        toggleIcon.textContent = '▼';
                    } else {
                        content.classList.add('hidden');
                        toggleIcon.textContent = '▶';
                    }
                    
                    // Update global state based on individual toggles
                    updateGlobalToggleState();
                });

                msg.appendChild(header);
                msg.appendChild(content);
                
                // Create searchable text for filtering
                const searchableText = [
                    eventInfo,
                    playerName || '',
                    typeof message === 'object' ? JSON.stringify(message) : message.toString()
                ].join(' ').toLowerCase();
                
                // Store message data for filtering
                const messageData = {
                    element: msg,
                    searchableText: searchableText
                };
                allMessages.push(messageData);
                
                // Check if we should show this message based on current search
                const currentSearch = searchInput.value.toLowerCase().trim();
                if (!currentSearch || searchableText.includes(currentSearch)) {
                    // Apply highlights if there's an active search
                    if (currentSearch) {
                        applyHighlights(msg, currentSearch);
                    }
                    messages.appendChild(msg);
                }

                // Update stats
                updateStats();
                lastEventTime.textContent = new Date().toLocaleTimeString();

                // Auto-scroll to bottom
                messages.scrollTop = messages.scrollHeight;
            }

            // Function to format JSON data into pretty UI elements
            function formatJsonData(json, container) {
                // Create container for formatted JSON
                const jsonContainer = document.createElement('div');
                jsonContainer.className = 'mt-1 text-sm font-mono';

                // If it's an event, display the event name prominently
                if (json.event) {
                    const eventName = document.createElement('div');
                    eventName.className = 'font-bold text-blue-400 mb-1';
                    eventName.textContent = 'Event: ' + json.event.split('\\').pop();
                    jsonContainer.appendChild(eventName);
                }

                // Function to recursively render JSON
                function renderJson(obj, container, level = 0) {
                    const indent = level * 12; // indent level in pixels

                    if (Array.isArray(obj)) {
                        // Handle arrays
                        for (let i = 0; i < obj.length; i++) {
                            const itemRow = document.createElement('div');
                            itemRow.style.paddingLeft = `${indent}px`;
                            itemRow.className = 'flex items-start';

                            const keyEl = document.createElement('span');
                            keyEl.className = 'text-purple-400 mr-2';
                            keyEl.textContent = `[${i}]:`;
                            itemRow.appendChild(keyEl);

                            const valueContainer = document.createElement('div');
                            valueContainer.className = 'flex-1';

                            if (typeof obj[i] === 'object' && obj[i] !== null) {
                                keyEl.className += ' cursor-pointer';
                                keyEl.onclick = function() {
                                    valueContainer.classList.toggle('hidden');
                                };
                                renderJson(obj[i], valueContainer, level + 1);
                            } else {
                                renderPrimitive(obj[i], valueContainer);
                            }

                            itemRow.appendChild(valueContainer);
                            container.appendChild(itemRow);
                        }
                    } else if (typeof obj === 'object' && obj !== null) {
                        // Handle objects
                        for (const key in obj) {
                            const itemRow = document.createElement('div');
                            itemRow.style.paddingLeft = `${indent}px`;
                            itemRow.className = 'flex items-start';

                            const keyEl = document.createElement('span');
                            keyEl.className = 'text-blue-400 mr-2 font-medium';
                            keyEl.textContent = `${key}:`;
                            itemRow.appendChild(keyEl);

                            const valueContainer = document.createElement('div');
                            valueContainer.className = 'flex-1';

                            if (typeof obj[key] === 'object' && obj[key] !== null) {
                                keyEl.className += ' cursor-pointer';
                                keyEl.onclick = function() {
                                    valueContainer.classList.toggle('hidden');
                                };
                                renderJson(obj[key], valueContainer, level + 1);
                            } else {
                                renderPrimitive(obj[key], valueContainer);
                            }

                            itemRow.appendChild(valueContainer);
                            container.appendChild(itemRow);
                        }
                    }
                }

                // Function to render primitive values with appropriate styling
                function renderPrimitive(value, container) {
                    const valueEl = document.createElement('span');

                    if (typeof value === 'string') {
                        valueEl.className = 'text-green-400';
                        valueEl.textContent = `"${value}"`;
                    } else if (typeof value === 'number') {
                        valueEl.className = 'text-orange-400';
                        valueEl.textContent = value;
                    } else if (typeof value === 'boolean') {
                        valueEl.className = 'text-purple-400 font-medium';
                        valueEl.textContent = value;
                    } else if (value === null) {
                        valueEl.className = 'text-blue-200/60 italic';
                        valueEl.textContent = 'null';
                    }

                    container.appendChild(valueEl);
                }

                renderJson(json, jsonContainer);
                container.appendChild(jsonContainer);
            }

            const connection = window.Echo.connector.pusher.connection;

            connection.bind('connected', () => {
                status.textContent = 'Connected';
                status.className = 'connected text-sm font-semibold text-green-400 bg-green-500/10 px-3 py-1 rounded-full border border-green-500/30';
                log('✅ Connected to Reverb server');
            });

            connection.bind('disconnected', () => {
                status.textContent = 'Disconnected';
                status.className = 'disconnected text-sm font-semibold text-red-400 bg-red-500/10 px-3 py-1 rounded-full border border-red-500/30';
                log('❌ Disconnected from Reverb server');
            });

            connection.bind('error', (error) => {
                log('⚠️ Connection error: ' + JSON.stringify(error));
            });

            const channelName = "debug-channel";

            // Subscribe to a test channel
            const channel = window.Echo.channel(channelName);
            log("📡 Subscribed to channel: " + channelName);

            channel.listen("TestMessage", (data) => {
                log("TestMessage received: " + JSON.stringify(data));
            });

            channel.listen("BroadcastEvent", (data) => {
                // Directly log the data object without stringifying and adding prefix
                log(data);
            });
        });
    </script>
</body>
</html>
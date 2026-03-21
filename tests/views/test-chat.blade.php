<!DOCTYPE html>
<html>
<head>
    <title>AnyChat Test Page</title>
    @livewireStyles
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="p-10">
        <h1 class="text-2xl font-bold">Package Testing Environment</h1>
        <p>This page exists only in local/testing environments.</p>
    </div>

    {{-- YOUR ANONYMOUS COMPONENT --}}
    <div x-data="anychatWidget" class="relative">
    {{-- Main Trigger Button --}}
    <button 
        dusk="chat-trigger"
        popovertarget="chatbox" 
        class="fixed bottom-5 right-5 z-50 flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-full shadow-2xl transition-transform active:scale-95"
    >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
        </svg>
        <span class="font-bold text-sm">Chat with us</span>
    </button>

    {{-- 
        CRITICAL FIX: Removed the <template> tag. 
        Popovers must be top-level elements to be accessible by the browser's top layer.
    --}}
    <div popover="manual" id="chatbox" class="chat-window m-0 mt-10 w-72 h-[400px] bg-white shadow-2xl border rounded-lg p-0">
        <div class="flex flex-col h-full">
            {{-- Fixed Header --}}
            <div class="p-3 border-b bg-blue-500 text-white flex justify-between items-center">
                <span class="text-xs font-bold">Anonymous Chat</span>
                <button popovertarget="chatbox" popovertargetaction="hide" class="text-white">✕</button>
            </div>

            {{-- Scrollable Messages --}}
            <div class="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50" x-ref="messagePanel">
                <template x-for="(nestedValue, index) in currentMessages" :key="index">
                    <div class="flex flex-col items-end">
                        <div class="bg-blue-600 text-white inline-block px-4 py-2 rounded-2xl text-sm max-w-[90%] shadow-sm"
                             x-html="nestedValue.message">
                        </div>
                        <span class="text-[9px] text-gray-400 mt-1" x-text="nestedValue.auth == 1 ? 'Support' : 'You'"></span>
                    </div>
                </template>
            </div>

            {{-- Footer Input --}}
            <div class="p-3 border-t bg-white rounded-b-xl">
                <div class="flex gap-2">
                    <input type="text" 
                        dusk="chat-input" 
                        x-model="message" 
                        placeholder="Type a message..."
                        class="flex-1 text-sm border border-gray-300 rounded-full px-4 py-2 focus:ring-2 focus:ring-blue-500 outline-none"
                        @keydown.enter="sendChatMessage()">
                    
                    <button type="button" dusk="send-button" @click="sendChatMessage()" 
                        class="bg-blue-600 text-white rounded-full p-2 hover:bg-blue-700 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('anychatWidget', () => ({
            currentMessages: [],
            message: '',
            chatId: localStorage.getItem('anychat_id'), // Persistent ID local check

            init() {
                // Listen for Handshake
                this.$wire.on('token-handshake', (data) => {
                    localStorage.setItem('anychat_token', data.token);
                    localStorage.setItem('anychat_id', data.chatId);
                    this.chatId = data.chatId;
                    this.subscribe(data.chatId);
                });

                // Resume session if data exists
                if (this.chatId) {
                    this.subscribe(this.chatId); 
                }
            },

            subscribe(id) {
                if (!id || !window.Echo) return;
                window.Echo.channel(`chat.${id}`)
                    .listen('.message.new', (e) => {
                        this.currentMessages.push(e.message);
                        // Auto-scroll to bottom
                        this.$nextTick(() => {
                            this.$refs.messagePanel.scrollTop = this.$refs.messagePanel.scrollHeight;
                        });
                    });
            },

            async sendChatMessage() {
                if (this.message.trim() === '') return;
                
                // Optimized: Local echo for better UX
                const pendingMsg = { message: this.message, auth: 0 };
                this.currentMessages.push(pendingMsg);

                await this.$wire.sendMessage(this.message);
                this.message = '';
            }
        }));
    });

    document.addEventListener('livewire:init', () => {
        Livewire.hook('request', ({ options }) => {
            options.headers['X-AnyChat-Token'] = localStorage.getItem('anychat_token');
        });
    });
</script>

    @livewireScripts
  
</body>
</html>
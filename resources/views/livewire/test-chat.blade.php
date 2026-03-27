@props([
    'variant' => 'primary',
    'color' => 'blue',
    'height' => '250px',
    'width' => '380px',
    'primaryColor' => null, {{-- User Color --}}
    'adminColor' => null,   {{-- Admin Color --}}
    'size' => 'base',
])

@php
    use Illuminate\Support\Arr;

    // 1. Set Default User Colors (Fallback if no primaryColor prop)
    $defaultUserBg = match ($color) {
        'red'  => '#ef4444',
        'zinc' => '#18181b',
        default => '#2563eb', // Blue
    };

    // 2. Window Classes
    $windowClasses = Arr::toCssClasses([
        'bg-white dark:bg-slate-900 shadow-2xl border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden',
        'text-sm' => $size === 'base',
        'text-xs' => $size === 'xs',
    ]);

    // 3. Shared Bubble Base Classes
    $bubbleBase = 'p-3 rounded-2xl shadow-sm max-w-[85%]';
@endphp

<div x-data="anychatWidget" class="relative" wire:ignore>
    
    {{-- Trigger --}}
    <button dusk="chat-trigger"
            popovertarget="chatbox" 
            style="background-color: {{ $primaryColor ?? $defaultUserBg }};"
            class="fixed bottom-5 right-5 z-50 p-4 rounded-full text-white shadow-2xl">
        <span>Chat</span>
    </button>

    <div popover="manual" id="chatbox" @toggle="handleToggle($event)"
         class="{{ $windowClasses }} anychat-popover" 
         style="height: {{ $height }}; width: {{ $width }}; margin: 0; inset: auto 20px 85px auto;">
        
        <div class="flex flex-col h-full">
            {{-- Header --}}
            <div class="p-4 text-white flex justify-between items-center" 
                 style="background-color: {{ $primaryColor ?? $defaultUserBg }};">
                <span class="font-bold">Support</span>
                <button popovertarget="chatbox">&times;</button>
            </div>

            <div class="flex-1 overflow-y-auto p-4 space-y-4 bg-slate-50" x-ref="messagePanel">
                <template x-for="(msg, index) in currentMessages" :key="index">
                   <div :class="msg.auth == 1 ? 'flex justify-start' : 'flex justify-end'">
                        
                        {{-- THE FIX IS HERE: Separate Style Logic --}}
                        <div class="{{ $bubbleBase }}"
                             :style="msg.auth == 1 
                                ? 'background-color: {{ $adminColor ?? '#f1f5f9' }}; color: {{ $adminColor ? '#ffffff' : '#1e293b' }}; border-top-left-radius: 0;' 
                                : 'background-color: {{ $primaryColor ?? $defaultUserBg }}; color: #ffffff; border-top-right-radius: 0;'">
                            <p x-text="msg.message"></p>
                        </div>

                    </div>
                                </template>
</div>
    <div class="p-4 border-t border-slate-100 bg-white shrink-0">
            <div class="relative flex items-center">
                <input type="text" 
                       x-model="message" 
                       @keydown.enter="sendChatMessage()" 
                       placeholder="send..."
                       class="w-full text-sm border border-slate-200 rounded-xl pl-4 pr-12 py-3 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 outline-none transition-all bg-slate-50/50">
                
                <button @click="sendChatMessage()" 
                        class="absolute right-2 p-2 text-blue-600 hover:text-blue-700 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path></svg>
                </button>
            </div>
        </div>
            </div>
        </div>
    </div>

    <style>
        .anychat-popover:popover-open { display: flex !important; flex-direction: column; border: none; padding: 0; }
    </style>
</div>



<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('anychatWidget', () => ({
            currentMessages:  JSON.parse(localStorage.getItem('current_session')) || [],  
            message: '',
            chatId: localStorage.getItem('anychat_id'), // Persistent ID local check
            welcomeSent: false,

            init() {

                    this.$watch('currentMessages', (value) => {
            console.log("Saving to localStorage...", value);
        localStorage.setItem('current_session', JSON.stringify(value));
    }, { deep: true });
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

            handleToggle(event) {
            // Check if the popover just switched to 'open'
            if (event.newState === 'open') {
                console.log("Chat opened via Popover API");
                
                // Trigger welcome message only on first open
                if (!this.welcomeSent) {
                    this.sendWelcomeMessage();
                }
            }
        },

        sendWelcomeMessage() {
            this.currentMessages.push({
                message: "Hello! How can we help you today?",
                auth: 1, // Admin style
                created_at: new Date()
            });

            this.welcomeSent = true;
            this.$nextTick(() => this.scrollToBottom());
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

                console.log(this.currentMessages)
            },
                scrollToBottom(id) {
            const panel = document.querySelector(`#chat-thread-${id} [x-ref="messagePanel"]`);
            if (panel) panel.scrollTop = panel.scrollHeight;
        }

        
        }));
    });

    document.addEventListener('livewire:init', () => {
        Livewire.hook('request', ({ options }) => {
            options.headers['X-AnyChat-Token'] = localStorage.getItem('anychat_token');
        });
    });
</script>

@props([
    'variant' => 'primary',
    'color' => 'blue',
    'height' => '250px',
    'width' => '380px',
    'primaryColor' => null,
    'adminColor' => null,
    'size' => 'base',
    'allowEmojis' => false,
    'allowUploads' => false,
])

@php
    use Illuminate\Support\Arr;
    $defaultUserBg = match ($color) {
        'red'  => '#ef4444',
        'zinc' => '#18181b',
        default => '#2563eb',
    };
    $windowClasses = Arr::toCssClasses([
        'bg-white dark:bg-slate-900 shadow-2xl border border-slate-200 dark:border-slate-800 rounded-2xl',
        'text-sm' => $size === 'base',
        'text-xs' => $size === 'xs',
    ]);
    $bubbleBase = 'p-3 rounded-2xl shadow-sm max-w-[85%]';
@endphp
<div x-data="anychatWidget" wire:ignore.self>




    {{-- Chat Window --}}
    <div x-show="isOpen" 
         class="{{ $windowClasses }} anychat-popover-manual" 
         style="height: {{ $height }}; width: {{ $width }}; position: fixed; bottom: 85px; right: 20px; z-index: 9998; display: none;">
        
        <div class="flex flex-col h-full bg-white rounded-2xl overflow-hidden shadow-2xl">
            {{-- Header --}}
            <div class="p-4 text-white flex justify-between items-center shrink-0" 
                 style="background-color: {{ $primaryColor ?? $defaultUserBg }};">
                <div class="flex items-center space-x-2">
                    <span class="font-bold">Live Support</span>
                </div>
                <button @click="isOpen = false" class="text-2xl hover:opacity-75">&times;</button>
            </div>

            {{-- Messages Area --}}
            <div class="flex-1 overflow-y-auto p-4 space-y-4 bg-slate-50" x-ref="messagePanel">
                <template x-for="(msg, index) in currentMessages" :key="index">
                   <div :class="Number(msg.auth) === 1 ? 'flex justify-start' : 'flex justify-end'">
                        <div class="{{ $bubbleBase }}"
                             :style="Number(msg.auth) === 1 
                                ? 'background-color: {{ $adminColor ?? '#f1f5f9' }}; color: #1e293b; border-top-left-radius: 0;' 
                                : 'background-color: {{ $primaryColor ?? $defaultUserBg }}; color: #ffffff; border-top-right-radius: 0;'">
                            <p x-text="msg.message" class="text-sm"></p>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Footer --}}
              <div x-show="showPicker" 
                         @click.away="showPicker = false" 
                         class="absolute bottom-0 z-[10000]"
                         style="display: none; right: calc(100% + 40px);"
                         x-transition>
                        <emoji-picker @emoji-click="addEmoji($event.detail.unicode)" class="light shadow-2xl"></emoji-picker>
                    </div>
       
{{-- Footer --}}
<div class="p-4 border-t bg-white">
    {{-- Combined Input Bar --}}
    <div class="flex items-center gap-2">
        
        {{-- 1. Upload Button (Moved inside the main group) --}}
        @if($allowUploads)
            <label class="cursor-pointer text-slate-400 hover:text-blue-600 shrink-0">
                <input type="file" wire:model="attachment" class="hidden">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </label>
        @endif

        {{-- 2. Text Input --}}
        <input type="text" 
               x-model="message" 
               @keydown.enter="sendChatMessage()" 
               placeholder="Type here..."
               class="flex-1 text-sm border rounded-xl px-4 py-2 outline-none focus:border-blue-500">

        {{-- 3. Emoji Button --}}
        @if($allowEmojis)      
            <button @click.stop="showPicker = !showPicker" type="button" class="p-2 text-slate-400 hover:text-indigo-500 shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
        @endif

        {{-- 4. Send Button --}}
        <button @click="sendChatMessage()" class="text-blue-600 p-2 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M13 5l7 7-7 7M5 5l7 7-7 7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
    </div>

    {{-- File Preview (Shows up only when a file is ready) --}}
    @if($allowUploads)
        <div x-show="$wire.attachment" class="mt-2 text-xs text-blue-600 italic flex items-center gap-1">
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"/></svg>
            File ready to send...
            <button @click="$wire.set('attachment', null)" class="text-red-500 ml-2">Remove</button>
        </div>
    @endif
</div>
            </div>
        </div>
    </div>

    {{-- Trigger --}}
    <button @click="toggleChat" 
            :style="{
            'background-color': '{{ $primaryColor ?? $defaultUserBg }}',
            'position': 'fixed',
            'bottom': '20px',
            'right': '20px',
            'z-index': '9999'
        }"
            class="fixed bottom-5 right-5 p-4 rounded-full text-white shadow-xl z-[9999]">
        <span x-show="!isOpen">Chat</span>
        <span x-show="isOpen">&times;</span>
    </button>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('anychatWidget', () => ({
                isOpen: false,
                currentMessages: JSON.parse(localStorage.getItem('current_session')) || [],  
                message: '',
                conversation_id: localStorage.getItem('conversation_id'),
                token: localStorage.getItem('session_token'),
                showPicker: false,

              init() {
    this.$watch('currentMessages', (v) => localStorage.setItem('current_session', JSON.stringify(v)), { deep: true });
    
    this.$wire.on('token-handshake', (data) => {
        // Ensure we use 'chatId' consistently as per NewMessage_4.php
        const id = data.chatId || data.conversation_id; 
        
        localStorage.setItem('session_token', data.token);
        localStorage.setItem('conversation_id', id);
        
        this.token = data.token;
        this.conversation_id = id;
        
        this.subscribe(id);
    });

    // Use a small timeout or interval to ensure Echo is ready
    let checkEcho = setInterval(() => {
        if (window.Echo && this.conversation_id) {
            this.subscribe(this.conversation_id);
            clearInterval(checkEcho);
        }
    }, 500);
},

                async sendChatMessage() {
                    if (!this.message.trim()) return;
                    let text = this.message;
                    this.message = '';

                    // 1. Add to local UI immediately
                    this.currentMessages = [...this.currentMessages, { message: text, auth: 0 }];
                    this.$nextTick(() => this.scrollToBottom());

                    // 2. Send to Livewire (X-WireChat-Token header handles thread persistence)
                    await this.$wire.set('message', text);
                    await this.$wire.sendMessage();
                },

             subscribe(id) {
    if (!id || !window.Echo) return;
    
    // Cleanup to prevent multiple listeners if the component re-renders
    window.Echo.leave(`chat.${id}`);

    window.Echo.channel(`chat.${id}`)
        .listen('.message.new', (e) => {
            console.log("Incoming Broadcast:", e); // Check this in F12 console
            
            // Safely extract the message text
            let text = '';
            if (typeof e.message === 'string') text = e.message;
            else if (e.message && e.message.message) text = e.message.message;
            else if (e.content) text = e.content;

            // Determine if it's from Admin
            // Usually, if it comes via Echo to the guest, it's from the Admin (auth: 1)
            const isFromAdmin = e.message && e.message.auth !== undefined 
                ? Number(e.message.auth) === 1 
                : true;

            // Push to UI
            this.currentMessages = [...this.currentMessages, {
                message: text,
                auth: isFromAdmin ? 1 : 0,
                created_at: new Date().toISOString()
            }];

            console.log(this.currentMessages) 

            this.$nextTick(() => this.scrollToBottom());
        });
},

                toggleChat() {
                    this.isOpen = !this.isOpen;
                    if (this.isOpen) this.$nextTick(() => this.scrollToBottom());
                },
                 addEmoji(emoji) {
                    this.message += emoji; 
                    this.showPicker = false; 
                },

                scrollToBottom() {
                    if (this.$refs.messagePanel) {
                        this.$refs.messagePanel.scrollTop = this.$refs.messagePanel.scrollHeight;
                    }
                }
            }));
        });

        // CRITICAL: This ensures every Livewire request carries the conversation token[cite: 7]
        document.addEventListener('livewire:init', () => {
            Livewire.hook('request', ({ options }) => {
                const token = localStorage.getItem('session_token');
                if (token) {
                    options.headers['X-AnyChat-Token'] = token;
                }
            });
        });
    </script>
   <script type="module" src="https://cdn.jsdelivr.net/npm/emoji-picker-element@1/index.js"></script>

</div>
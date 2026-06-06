
@props([
    'variant' => 'primary',
    'color' => 'blue',
    'height' => '450px',
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
{{-- resources/views/livewire/test-chat.blade.php --}}
<div x-data="anychatWidget" wire:ignore.self> {{-- SINGLE ROOT ELEMENT --}}

  


    {{-- Chat Window --}}
    <div x-show="isOpen" 
         class="bg-white dark:bg-slate-900 shadow-2xl border border-slate-200 dark:border-slate-800 rounded-2xl" 
         style="height: {{ $height }}; width: {{ $width }}; position: fixed; bottom: 85px; right: 20px; z-index: 9998; display: none;">
        
        <div class="flex flex-col h-full overflow-hidden rounded-2xl">
            {{-- Header with Identity Distinction[cite: 4] --}}
            <div class="p-4 text-white flex justify-between items-center" 
                 style="background-color: {{ $primaryColor ?? '#2563eb' }};">
                <div class="flex flex-col">
                    <span class="font-bold">Live Support</span>
                    <span class="text-[10px] opacity-80 uppercase tracking-wider">
                        Connected as: {{ $senderName }}
                    </span>
                </div>
                <button @click="isOpen = false" class="text-2xl hover:opacity-75">&times;</button>
            </div>

            {{-- Messages Area --}}
            <div class="flex-1 overflow-y-auto p-4 space-y-4 bg-slate-50" x-ref="messagePanel">
                <template x-for="(msg, index) in currentMessages" :key="index">
                   <div :class="Number(msg.auth) === 1 ? 'flex justify-start' : 'flex justify-end'">
                        <div class="p-3 rounded-2xl shadow-sm max-w-[85%]"
                             :style="Number(msg.auth) === 1 
                                ? 'background-color: {{ $adminColor ?? '#f1f5f9' }}; color: #1e293b; border-top-left-radius: 0;' 
                                : 'background-color: {{ $primaryColor ?? '#2563eb' }}; color: #ffffff; border-top-right-radius: 0;'">
                            <p x-text="msg.message" class="text-sm"></p>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Footer Section --}}
            <div class="p-3 border-t bg-white">
                <div class="flex items-center gap-2">
                    {{-- 1. Upload Icon (Always blue for visibility) --}}
                    @if($allowUploads)
                        <label class="cursor-pointer text-blue-600 hover:text-blue-800 shrink-0 p-1">
                            <input type="file" wire:model="attachment" class="hidden">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </label>
                    @endif
                    
                        <div x-show="showPicker" 
                         @click.away="showPicker = false" 
                         class="absolute bottom-0 z-[10000]"
                         style="display: none; right: calc(100% + 40px);"
                         x-transition>
                        <emoji-picker @emoji-click="addEmoji($event.detail.unicode)" class="light shadow-2xl"></emoji-picker>
                    </div>
                    {{-- 2. Input --}}
                    <input type="text" x-model="message" @keydown.enter="sendChatMessage()" placeholder="Type here..." class="flex-1 text-sm border rounded-xl px-3 py-2 outline-none focus:border-blue-500">

                    {{-- 3. Emoji & Send --}}
                    @if($allowEmojis)      
                        <button @click.stop="showPicker = !showPicker" type="button" class="text-slate-400 hover:text-indigo-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    @endif

                    <button @click="sendChatMessage()" class="text-blue-600 p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M13 5l7 7-7 7M5 5l7 7-7 7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>
                 
                     @error('message')
                        <span class="text-red-500 text-[11px] mt-1 ml-2 font-medium animate-pulse">
                            {{ $message }}
                        </span>
                    @enderror
                
                
                {{-- Attachment Status --}}
                @if($attachment)
                    <div class="mt-1 text-[10px] text-blue-600 italic">File attached.</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Trigger Button --}}
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

    {{-- Scripts inside the root element[cite: 3] --}}
    <script>
     document.addEventListener('alpine:init', () => {
    Alpine.data('anychatWidget', () => ({
        isOpen: false,
        currentMessages: JSON.parse(localStorage.getItem('current_session')) || [],  
        message: '',
        // Initialize from storage so we can subscribe immediately on load
        conversation_id: localStorage.getItem('conversation_id'),
        showPicker: false,

        init() {
            // Save messages to local storage whenever they change
            this.$watch('currentMessages', (v) => localStorage.setItem('current_session', JSON.stringify(v)), { deep: true });
            
            // 1. CRITICAL: Subscribe immediately for returning users
            if (this.conversation_id) {
                this.subscribe(this.conversation_id);
            }

            // 2. Handle new sessions (Handshake)
            this.$wire.on('token-handshake', (data) => {
                localStorage.setItem('session_token', data.token);
                localStorage.setItem('conversation_id', data.chatId);
                
                // Fix: use 'data.chatId' instead of undefined 'id'
                this.conversation_id = data.chatId;
                this.subscribe(data.chatId);
            });
        },

        async sendChatMessage() {
            if (!this.message.trim()) return;
            let text = this.message;
            this.message = '';
            
            // Add user message locally
            this.currentMessages = [...this.currentMessages, { message: text, auth: 0 }];
            
            await this.$wire.set('message', text);
            await this.$wire.sendMessage();
            this.$nextTick(() => this.scrollToBottom());
        },

        subscribe(id) {
            if (!id || !window.Echo) return;
            
            // Prevent duplicate listeners
            window.Echo.leave(`chat.${id}`);

            window.Echo.channel(`chat.${id}`)
                .listen('.message.new', (e) => {
                    console.log("Admin Reply Received:", e); 
                    
                    // Extract message text safely
                    let text = e.message?.message || e.message || e.content || "No content";

                    // If it arrives on the guest's channel, it's from the admin (auth: 1)[cite: 7]
                    const isFromAdmin = e.message && e.message.auth !== undefined 
                        ? Number(e.message.auth) === 1 
                        : true;

                    this.currentMessages = [...this.currentMessages, {
                        message: text,
                        auth: isFromAdmin ? 1 : 0,
                        created_at: new Date().toISOString()
                    }];

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
</script>
     <script type="module" src="https://cdn.jsdelivr.net/npm/emoji-picker-element@^1/index.js"></script>

</div>
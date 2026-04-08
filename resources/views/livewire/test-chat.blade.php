@props([
    'variant' => 'primary',
    'color' => 'blue',
    'height' => '250px',
    'width' => '380px',
    'primaryColor' => null,
    'adminColor' => null,
    'size' => 'base',
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

{{-- ROOT: No relative class --}}
<div x-data="anychatWidget" wire:ignore>

    {{-- Chat Window --}}
    <div x-show="isOpen" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-4 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         class="{{ $windowClasses }} anychat-popover-manual" 
         style="height: {{ $height }}; width: {{ $width }}; position: fixed; bottom: 85px; right: 20px; z-index: 9998; display: none;">
        
        <div class="flex flex-col h-full" style="overflow: visible !important;">
            {{-- Header --}}
            <div class="p-4 text-white flex justify-between items-center shrink-0 rounded-t-2xl" 
                 style="background-color: {{ $primaryColor ?? $defaultUserBg }};">
                <span class="font-bold">Support</span>
                <button @click="isOpen = false" class="text-2xl hover:opacity-75">&times;</button>
            </div>

            {{-- Messages --}}
            <div class="flex-1 overflow-y-auto p-4 space-y-4 bg-slate-50" x-ref="messagePanel">
                <template x-for="(msg, index) in currentMessages" :key="index">
                   <div :class="msg.auth == 1 ? 'flex justify-start' : 'flex justify-end'">
                        <div class="{{ $bubbleBase }}"
                             :style="msg.auth == 1 
                                ? 'background-color: {{ $adminColor ?? '#f1f5f9' }}; color: {{ $adminColor ? '#ffffff' : '#1e293b' }}; border-top-left-radius: 0;' 
                                : 'background-color: {{ $primaryColor ?? $defaultUserBg }}; color: #ffffff; border-top-right-radius: 0;'">
                            <p x-text="msg.message"></p>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Footer --}}
            <div class="p-4 border-t border-slate-100 bg-white shrink-0 rounded-b-2xl" style="overflow: visible !important;">
                <div class="relative flex items-center" style="overflow: visible !important;">
                    
                    {{-- Emoji Tray: Positioned way to the left --}}
                    <div x-show="showPicker" 
                         @click.away="showPicker = false" 
                         class="absolute bottom-0 z-[10000]"
                         style="display: none; right: calc(100% + 40px);"
                         x-transition>
                        <emoji-picker @emoji-click="addEmoji($event.detail.unicode)" class="light shadow-2xl"></emoji-picker>
                    </div>

                    <input type="text" 
                           x-model="message" 
                           @keydown.enter="sendChatMessage()" 
                           placeholder="Type a message..."
                           class="w-full text-sm border border-slate-200 rounded-xl pl-4 pr-20 py-3 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 outline-none transition-all bg-slate-50/50">

                    <div class="absolute right-2 flex items-center space-x-1">
                        <button @click.stop="showPicker = !showPicker" type="button" class="p-2 text-slate-400 hover:text-indigo-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <button @click="sendChatMessage()" class="p-2 text-blue-600 hover:text-blue-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Trigger: Pinned bottom-right --}}
    <button @click="toggleChat"
            style="background-color: {{ $primaryColor ?? $defaultUserBg }}; position: fixed !important; bottom: 20px !important; right: 20px !important;"
            class="z-[9999] p-4 rounded-full text-white shadow-2xl hover:scale-110 transition-transform">
        <span x-show="!isOpen">chat</span>
        <span x-show="isOpen" class="text-xl">&times;</span>
    </button>

    <style>
        .anychat-popover-manual { display: flex; flex-direction: column; border: none; padding: 0; }
        emoji-picker { width: 300px; height: 350px; border-radius: 1rem; }
    </style>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('anychatWidget', () => ({
                isOpen: false,
                currentMessages: JSON.parse(localStorage.getItem('current_session')) || [],  
                message: '',
                chatId: localStorage.getItem('anychat_id'),
                welcomeSent: false,
                showPicker: false,

                init() {
                    this.$watch('currentMessages', (v) => localStorage.setItem('current_session', JSON.stringify(v)), { deep: true });
                    this.$wire.on('token-handshake', (data) => {
                        localStorage.setItem('anychat_token', data.token);
                        localStorage.setItem('anychat_id', data.chatId);
                        this.chatId = data.chatId;
                        this.subscribe(data.chatId);
                    });
                    if (this.chatId) this.subscribe(this.chatId);
                },

                toggleChat() {
                    this.isOpen = !this.isOpen;
                    if (this.isOpen && !this.welcomeSent) {
                        this.currentMessages.push({ message: "Hello! How can we help you today?", auth: 1 });
                        this.welcomeSent = true;
                        this.$nextTick(() => this.scrollToBottom());
                    }
                },

                addEmoji(emoji) {
                    this.message += emoji; 
                    this.showPicker = false; 
                },

                async sendChatMessage() {
                    if (typeof this.message !== 'string' || this.message.trim() === '') return;
                    let msg = this.message;
                    await this.$wire.set('message', this.message);
                    await this.$wire.sendMessage();
                    if (this.$wire.message === '') {
                        this.currentMessages.push({ message: msg, auth: 0 });
                        this.message = '';
                        this.$nextTick(() => this.scrollToBottom());
                    }
                },

                scrollToBottom() {
                    if (this.$refs.messagePanel) {
                        this.$refs.messagePanel.scrollTop = this.$refs.messagePanel.scrollHeight;
                    }
                },

                subscribe(id) {
                    if (!id || !window.Echo) return;
                    window.Echo.channel(`chat.${id}`).listen('.message.new', (e) => {
                        this.currentMessages.push(e.message);
                        this.$nextTick(() => this.scrollToBottom());
                    });
                }
            }));
        });
    </script>
    <script type="module" src="https://cdn.jsdelivr.net/npm/emoji-picker-element@1/index.js"></script>
</div>

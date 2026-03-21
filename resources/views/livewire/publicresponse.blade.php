<div x-data="chat" wire:ignore>

 <div class="fixed left-0 top-0 h-screen w-80 bg-slate-900 shadow-2xl border-r border-slate-800 flex flex-col z-40">
        
        <div class="p-6 border-b border-slate-800 bg-slate-900 shrink-0">
            <h2 class="text-xl font-bold  tracking-tight">Inbound Messages</h2>
            <p class="text-xs text-slate-400 mt-1">Live support queue</p>


        </div>
            <div class="flex-1 overflow-y-auto custom-scrollbar">
                <template x-for="chatId in Object.keys(sessions)" :key="chatId">
                    <div class="border-b">
                        <button :popovertarget="'chat-thread-' + chatId" @click="setActiveChat(chatId)" class="w-full text-left p-4 hover:bg-blue-50 transition">
                            <span class="font-bold text-xs text-blue-600" x-text="'ID: ' + chatId.substring(0,8)"></span>
                            <p class="text-xs text-gray-500 truncate mt-1" x-text="sessions[chatId][sessions[chatId].length - 1].message.message || '...'"></p>
                        </button>

                        {{-- Individual Chat Window (Fixed Height & Scroll) --}}
                 
                        <div popover="manual" :id="'chat-thread-' + chatId" class="chat-thread-window antialiased">
    <div class="flex flex-col h-full w-full bg-white">
        
        {{-- Header: Glassmorphism effect --}}
        <div class="p-4 bg-slate-900 text-white flex justify-between items-center shrink-0 shadow-md z-10">
            <div class="flex items-center gap-2">
                <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                <span class="text-xs font-semibold tracking-wide uppercase" x-text="'Session: ' + chatId.substring(0,8)"></span>
            </div>
            <button :popovertarget="'chat-thread-' + chatId" popovertargetaction="hide" class="opacity-70 hover:opacity-100 transition-opacity">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        {{-- Scrollable Message Area --}}
        <div class="message-viewport modern-bg p-4 space-y-4" x-ref="messagePanel" :id="'panel-' + chatId">
            <template x-for="msg in sessions[chatId]">
                <div :class="msg.auth == 1 ? 'flex justify-end' : 'flex justify-start'">
                    <div :class="msg.auth == 1 
                        ? 'bg-blue-600 text-white rounded-l-xl rounded-tr-xl shadow-blue-100' 
                        : 'bg-white border border-slate-200 text-slate-700 rounded-r-xl rounded-tl-xl shadow-sm'" 
                        class="px-4 py-2.5 text-sm max-w-[85%] shadow-md">
                        
                        <p x-text="msg.auth === 0 ? msg.message.message : msg.message" class="leading-relaxed"></p>
                        
                        <div :class="msg.auth == 1 ? 'text-blue-200' : 'text-slate-400'" class="text-[10px] mt-1 text-right font-medium">
                            <span x-text="new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})"></span>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Footer: Clean and centered --}}
        <div class="p-4 border-t border-slate-100 bg-white shrink-0">
            <div class="relative flex items-center">
                <input type="text" 
                       x-model="message" 
                       @keydown.enter="sendChatMessage()" 
                       placeholder="Reply to customer..."
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
                </template>
                      <template x-if="Object.keys(sessions).length === 0">
                <div class="p-10 text-center">
                    <p class="text-slate-500 text-sm italic">No active messages...</p>
                </div>
            </template>
        
            </div>
        </div>
    </div>

    <style>
/* Positioning the Individual Threads to the right of the sidebar */
    .chat-thread-window:popover-open {
        margin: 0;
        border: none;
        padding: 0;
        width: 380px !important;
        height: 550px !important;
        
        /* Positioning: Start at 330px from left (Sidebar width + 10px gap) */
        inset: auto auto 20px 330px; 
        
        display: flex !important;
        flex-direction: column !important;
        background: white;
        border-radius: 1rem;
        box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        overflow: hidden;
    }

    /* Message Area: Locked height with internal scrolling */
    .message-viewport {
        flex: 1 1 0%;
        overflow-y: auto !important;
        min-height: 0;
    }

    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
</style>
</div>


<script>
document.addEventListener("alpine:init", () => {
    Alpine.data("chat", () => ({
        sessions: JSON.parse(localStorage.getItem('anychat_sessions')) || {},  
        activeChatId: null,
        message: '',

        setActiveChat(id) {
            this.activeChatId = id;
        },

        init() {


                        // 2. Set up a watcher to auto-save every time sessions is updated
            this.$watch('sessions', (value) => {
            console.log("Saving to localStorage...", value);
        localStorage.setItem('anychat_sessions', JSON.stringify(value));
    }, { deep: true });
            
            
            Echo.channel('anychat-support')
                .listen('.user.sent', (e) => {
                    console.log("Payload:", e);

                    // Fix for 'undefined' ID: Extract from e.message object
                    const id = e.message.chatId; 

                    if (!id) return;

                    if (!this.sessions[id]) {
                        this.sessions[id] = [];
                    }

                    this.sessions[id].push({
                        message: e.message, // This is the object {message: "...", chatId: "..."}
                        auth: 0,
                        created_at: new Date()
                    });

                    if (this.activeChatId === id) {
                        this.$nextTick(() => this.scrollToBottom(id));
                    }
                });
        },

        async sendChatMessage() {
            if (!this.message.trim() || !this.activeChatId) return;

            const text = this.message;
            const id = this.activeChatId;

            await this.$wire.sendMessage(id, text);
            
            // For Admin, we push a simple string to 'message' to keep logic clean
            this.sessions[id].push({ message: text, auth: 1 });
            this.message = '';
            this.$nextTick(() => this.scrollToBottom(id));
        },

        scrollToBottom(id) {
            const panel = document.querySelector(`#chat-thread-${id} [x-ref="messagePanel"]`);
            if (panel) panel.scrollTop = panel.scrollHeight;
        }
    }));
});
</script>
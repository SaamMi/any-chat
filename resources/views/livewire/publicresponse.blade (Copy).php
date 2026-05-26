
<div x-data="chatAdmin" class="flex h-screen w-full bg-slate-50 overflow-hidden">
    
    {{-- Sidebar --}}
    <div class="w-80 bg-slate-900 shadow-2xl border-r border-slate-800 flex flex-col z-40 shrink-0">
        <div class="p-6 border-b border-slate-800 bg-slate-900">
            <h2 class="text-xl font-bold tracking-tight text-white">AnyChat Console</h2>
        </div>


        
        <div class="flex-1 overflow-y-auto custom-scrollbar">
            <div class="p-4 bg-slate-800/50 text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                Active Support Queue
            </div>

         {{-- Updated Search Section --}}
<div class="p-4 border-b border-slate-200 bg-slate-50">
    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Search History</label>
    <input type="text" 
           x-model="searchQuery" 
           {{-- Pass searchQuery explicitly to the backend method --}}
           @input.debounce.500ms="results = await $wire.performSearch(searchQuery)"
           placeholder="Type to search..." 
           {{-- High contrast styling for visibility on light backgrounds --}}
           class="w-full bg-white border border-slate-300 text-slate-900 text-sm rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm">
    
    {{-- Search Results Dropdown --}}
    <div x-show="searchQuery.length > 2 && results.length > 0" 
         x-transition
         class="mt-2 max-h-60 overflow-y-auto bg-white rounded-lg shadow-2xl absolute w-64 z-[100] border border-slate-200">
        <template x-for="result in results" :key="result.id">
            <button @click="jumpToMessage(result)" class="w-full text-left p-3 border-b border-slate-100 hover:bg-blue-50 transition-colors">
                <div class="font-bold text-blue-600 text-xs" x-text="result.sender"></div>
                <div class="text-slate-600 text-xs truncate" x-text="result.body"></div>
            </button>
        </template>
    </div>

    {{-- "No results" indicator --}}
    <div x-show="searchQuery.length > 2 && results.length === 0" class="mt-2 p-2 bg-slate-100 text-[10px] text-slate-500 rounded text-center">
        No matches found.
    </div>
</div>
            {{-- Loop through local sessions (Guests) --}}
            <template x-for="chatId in Object.keys(sessions)" :key="chatId">
                <button @click="setActiveChat(chatId, sessions[chatId].metadata.type || '', sessions[chatId].metadata.name)" 
                        :class="activeChatId === chatId ? 'bg-slate-800 border-l-4 border-blue-500' : 'hover:bg-slate-800/50'"
                        class="w-full text-left p-4 border-b border-slate-800 transition-all">
                    <span class="font-bold text-sm text-slate-900" x-text="sessions[chatId].metadata.name"></span>
                </button>
            </template>

            <div class="p-4 text-xs font-semibold text-slate-500 uppercase bg-slate-800/30">Registered Users</div>
            {{-- Loop through Database Users --}}
            @foreach($users as $user)
                <button @click="setActiveChat('{{ $user->id }}', '{{ addslashes(get_class($user)) }}', '{{ $user->name }}')"
                        :class="activeChatId == '{{ $user->id }}' ? 'bg-slate-800' : ''"
                        class="w-full text-left p-4 border-b border-slate-800 hover:bg-slate-800/50 transition-all">
                    <span class="font-bold text-sm text-slate-900">{{ $user->name }}</span>
                </button>
            @endforeach
        </div>
    </div>

    {{-- Main Chat Area --}}
    <div class="flex-1 flex flex-col relative h-full bg-white">
             <template x-if="activeChatId">
            <div class="flex flex-col h-full w-full">
                {{-- Header: Uses Alpine metadata for instant name updates --}}
                <div class="p-4 border-b flex justify-between items-center shadow-sm bg-white">
                    <div class="flex items-center gap-3">
                        <span class="font-bold text-slate-800" x-text="sessions[activeChatId]?.metadata?.name"></span>
                    </div>
                </div>

                {{-- Messages: wire:ignore goes ONLY here to protect the Alpine-managed list --}}
                <div wire:ignore class="flex-1 overflow-y-auto p-6 space-y-4 bg-slate-50" :id="'panel-' + activeChatId">
                    <template x-for="(msg, index) in sessions[activeChatId]?.messages || []" :key="index">
                        <div :class="Number(msg.auth) === 1 ? 'flex justify-end' : 'flex justify-start'" class="msg-row">
                            <div class="p-3 rounded-2xl shadow-sm max-w-[70%] msg-bubble transition-all duration-500"
                                 :style="Number(msg.auth) === 1 ? 'background-color: #2563eb; color: #fff;' : 'background-color: #fff; border: 1px solid #e2e8f0;'">
                                <p x-text="msg.message" class="text-sm" :id="'msg-' + msg.id"></p>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="p-4 border-t bg-white">
                    <input type="text" x-model="message" @keydown.enter="sendChatMessage()" placeholder="Reply..." class="w-full border rounded-2xl px-4 py-3 outline-none">
                </div>
            </div>
        </template>
        
        <div x-show="!activeChatId" class="flex-1 flex items-center justify-center text-slate-400">
            Select a contact to start.
        </div>
    </div>
    <style>
    /* Custom highlight class that won't be purged by Tailwind */
    .message-jump-flash {
        background-color: #fde047 !important; /* yellow-300 */
        color: #0f172a !important;            /* slate-900 */
        box-shadow: 0 0 0 4px #eab308 !important; /* yellow-500 ring */
        transition: all 0.5s ease;
    }
</style>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('chatAdmin', () => ({
        sessions: {}, // Initialized empty to prevent JSON errors
        activeChatId: null,
        message: '',
        results: [],
        searchQuery: '',

   async setActiveChat(id, type, name, shouldScroll = true) { // Added shouldScroll parameter
    this.activeChatId = id;
    
    if (!this.sessions[id]) {
        this.sessions[id] = {
            metadata: { name: name, id: id, type: type },
            messages: []
        };
    }

    const history = await this.$wire.selectUser(id, type);
    this.sessions[id].messages = history;
    this.sessions = { ...this.sessions };
    
    // Only scroll to bottom if we aren't jumping to a specific message
    if (shouldScroll) {
        this.$nextTick(() => this.scrollToBottom(id));
    }
},
        async sendChatMessage() {
            if (!this.message.trim() || !this.activeChatId) return;

            const text = this.message;
            const id = this.activeChatId;

            await this.$wire.sendMessage(text); // Server already knows the state
            
            this.sessions[id].messages.push({ message: text, auth: 1 });
            this.sessions = { ...this.sessions };
            this.message = '';
            this.$nextTick(() => this.scrollToBottom(id));
        },
 async jumpToMessage(result) {
    // 1. Switch chat without scrolling to bottom
    await this.setActiveChat(result.chatId, result.chatType, result.sender, false);
    
    this.searchQuery = '';
    this.results = [];

    // 2. Wait for Alpine to render the new messages
    setTimeout(() => {
        const textElement = document.getElementById(`msg-${result.id}`);
        
        if (textElement) {
            textElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            const bubble = textElement.closest('.msg-bubble');
            if (bubble) {
                // 3. Apply the custom CSS class
                bubble.classList.add('message-jump-flash');
                
                // Log AFTER adding class so you can see it in the console
                console.log('Highlighting element:', bubble);

                setTimeout(() => {
                    bubble.classList.remove('message-jump-flash');
                }, 3000);
            }
        } else {
            console.error("Jump failed: Message element not found for ID", result.id);
        }
    }, 400); 
},
    // In your existing message loop, ensure IDs are set:
    // <div :id="'msg-' + msg.id" ...

        scrollToBottom(id) {
            const panel = document.getElementById(`panel-${id}`);
            if (panel) panel.scrollTop = panel.scrollHeight;
        },

        init() {
            // Safe JSON load
            const saved = localStorage.getItem('anychat_admin_sessions');
            if (saved) {
                try { this.sessions = JSON.parse(saved); } catch (e) { this.sessions = {}; }
            }

            this.$watch('sessions', (val) => localStorage.setItem('anychat_admin_sessions', JSON.stringify(val)), { deep: true });
        }
    }));
});
</script>
@props([
    'heading' => '',
    'subheading' => '',
    'variant' => 'outline',
    'color' => null,
    'primaryColor' => null,
    'adminColor' => null,
    'users' => [],
    'guestConversations' => [],
    'group' => [],
])

@php
    $defaultUserBg = match ($color) {
        'red'  => '#ef4444',
        'zinc' => '#18181b',
        default => '#2563eb',
    };

    $pColor = $primaryColor ?? $defaultUserBg;
    $aColor =$adminColor ?? '#f1f5f9';
@endphp

<section class="w-full anychat-container">
    <div x-data="chatAdmin" class="w-full">
        
        {{-- Page Header (Matches partials.settings-heading) --}}
        <div class="relative mb-6 w-full">
            <flux:heading size="xl" level="1">{{ __('Chat') }}</flux:heading>
            <flux:subheading size="lg" class="mb-6">{{ __('Manage your conversations, groups, and support requests') }}</flux:subheading>
            <flux:separator variant="subtle" />
        </div>

        <div class="flex items-start max-md:flex-col">
            {{-- Left Sidebar --}}
            <div class="me-10 w-full pb-4 md:w-[240px] space-y-6">
                
                <flux:navlist>
                    <flux:navlist.group :heading="__('Actions')">
                        <flux:navlist.item icon="plus" :href="route('group.create')" :current="request()->routeIs('group.create')" wire:navigate>
                            {{ __('Create group chat') }}
                        </flux:navlist.item>
                        <flux:navlist.item icon="pencil-square" :href="route('group.edit')" :current="request()->routeIs('group.edit')" wire:navigate>
                            {{ __('Edit group chat') }}
                        </flux:navlist.item>
                        <flux:navlist.item icon="eye" @click="$wire.groupVisibility()">
                            {{ __('Group visibility') }}
                        </flux:navlist.item>
                        <flux:navlist.item icon="chat-bubble-left-right" :href="route('chatresponse')" :current="request()->routeIs('chatresponse')" wire:navigate>
                            {{ __('Chats') }}
                        </flux:navlist.item>
                    </flux:navlist.group>
                </flux:navlist>

                <flux:separator variant="subtle" />

                {{-- Conversation Search --}}
                <div wire:ignore class="relative">
                    <flux:input 
                        :label="__('Search conversations')" 
                        type="search" 
                        icon="magnifying-glass"
                        x-model="searchQuery" 
                        @input.debounce.500ms="performSearch(searchQuery)" 
                        :placeholder="__('Find message...')" 
                        autocomplete="off" 
                    />
                    
                    <div x-cloak x-show="results.length > 0" class="mt-2 flex items-center justify-between rounded-md bg-zinc-100 dark:bg-zinc-800 px-2.5 py-1 border border-zinc-200 dark:border-zinc-700">
                        <flux:text size="sm" class="font-mono text-xs" x-text="(activeResultIndex + 1) + ' / ' + results.length" />
                        <div class="flex items-center gap-1">
                            <flux:button size="xs" variant="ghost" icon="chevron-up" @click="navigateResults('prev')" />
                            <flux:button size="xs" variant="ghost" icon="chevron-down" @click="navigateResults('next')" />
                        </div>
                    </div>

                    <div x-cloak 
                         x-show="searchQuery.length > 2 && results.length > 0" 
                         x-transition 
                         class="mt-1 max-h-48 overflow-y-auto bg-white dark:bg-zinc-800 rounded-lg shadow-lg absolute w-full z-20 border border-zinc-200 dark:border-zinc-700 divide-y divide-zinc-100 dark:divide-zinc-700/50">
                        <template x-for="(result, index) in results" :key="result.id">
                            <button type="button"
                                    @click="activeResultIndex = index; jumpToMessage(result)" 
                                    :class="activeResultIndex === index ? 'bg-zinc-100 dark:bg-zinc-700/50' : ''" 
                                    class="w-full text-left p-2.5 hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition-colors">
                                <div class="flex justify-between items-center mb-0.5">
                                    <span class="font-semibold text-xs text-zinc-900 dark:text-zinc-100" x-text="result.sender"></span>
                                    <span class="text-[10px] text-zinc-400" x-text="result.chatType ? result.chatType.split('\\').pop() : ''"></span>
                                </div>
                                <div class="text-zinc-500 dark:text-zinc-400 text-xs truncate" x-text="result.body"></div>
                            </button>
                        </template>
                    </div>
                </div>

                <flux:separator variant="subtle" />

                {{-- Groups, Guests & Direct Users --}}
                <flux:navlist>
                    <flux:navlist.group :heading="__('Your groups')">
                        @foreach($group as $gr)
                            <flux:navlist.item 
                                :href="route('chatresponse', $gr['id'])" 
                                wire:navigate
                                @click="setActiveChat('group{{ $gr['id'] }}', '{{ addslashes($gr['type']) }}', '{{ addslashes($gr['name']) }}')"
                                ::class="activeChatId == 'group{{ $gr['id'] }}' \vert{}\vert{} activeChatId == '{{$gr['id'] }}' ? 'bg-zinc-100 dark:bg-zinc-800 font-medium' : ''">
                                {{ $gr['name'] }}
                            </flux:navlist.item>
                        @endforeach
                    </flux:navlist.group>

                    <div wire:poll.15s>
                        <flux:navlist.group :heading="__('Support guests')">
                            @foreach($guestConversations as $guest)
                                <flux:navlist.item 
                                    @click="setActiveChat('{{ $guest['id'] }}', '{{ addslashes($guest['type']) }}', '{{ addslashes($guest['name']) }}')"
                                    ::class="activeChatId == '{{ $guest['id'] }}' ? 'bg-zinc-100 dark:bg-zinc-800 font-medium' : ''">
                                    <div class="flex items-center justify-between w-full">
                                        <span class="truncate">{{ $guest['name'] }}</span>
                                        <flux:badge size="sm" color="blue" x-cloak x-show="getMatchCount('{{ $guest['id'] }}') > 0" x-text="getMatchCount('{{ $guest['id'] }}')" />
                                    </div>
                                </flux:navlist.item>
                            @endforeach
                        </flux:navlist.group>
                    </div>

                    <flux:navlist.group :heading="__('Direct chat users')">
                        @foreach($users as $user)
                            <flux:navlist.item 
                                @click="setActiveChat('{{ $user->id }}', '{{ addslashes(get_class($user)) }}', '{{ addslashes($user->name) }}')"
                                ::class="activeChatId == '{{ $user->id }}' ? 'bg-zinc-100 dark:bg-zinc-800 font-medium' : ''">
                                <div class="flex items-center justify-between w-full">
                                    <span class="truncate">{{ $user->name }}</span>
                                    <flux:badge size="sm" color="blue" x-cloak x-show="getMatchCount('{{ $user->id }}') > 0" x-text="getMatchCount('{{ $user->id }}')" />
                                </div>
                            </flux:navlist.item>
                        @endforeach
                    </flux:navlist.group>
                </flux:navlist>
            </div>

            <flux:separator class="md:hidden" />

            {{-- Main Content Slot (Matches x-pages::settings.layout) --}}
            <div class="flex-1 self-stretch max-md:pt-6">
                <flux:heading>{{ $heading }}</flux:heading>
                <flux:subheading>{{ $subheading }}</flux:subheading>

                <div class="mt-5 w-full">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    .anychat-container {
        --primary: {{ $pColor }};
        --admin-bg: {{ $aColor }};
        --admin-text: #1e293b;
    }
    .bg-dynamic-user { background-color: var(--primary) !important; }
    .text-dynamic-user { color: var(--primary) !important; }
    .border-dynamic-user { border-color: var(--primary) !important; }
    .bg-dynamic-admin { background-color: var(--admin-bg) !important; }
    .text-dynamic-admin { color: var(--admin-text) !important; }
    .message-jump-flash { background-color: #fef08a !important; color: #0f172a !important; box-shadow: 0 0 0 4px #eab308 !important; transition: all 0.3s ease-in-out; }
    [x-cloak] { display: none !important; }
</style>

@script
<script>
    Alpine.data('chatAdmin', () => ({
        sessions: {}, 
        activeChatId: null,
        editChatId: null,
        editGroupName: '',
        message: '',
        results: [],
        searchQuery: '',
        userSearchQuery: '',
        activeResultIndex: -1, 
        isDrafting: false,
        allResults: [],
        activeNotifications: [],
        displayGroupModal: false,
        editGroupModal: false,

        async draftWithAI() {
            if (!this.activeChatId || this.isDrafting) return;
            this.isDrafting = true;
            try {
                const suggestion = await this.$wire.generateAiReply(this.activeChatId);
                if (suggestion) this.message = suggestion;
            } catch (error) {
                console.error("AI generation failed:", error);
                alert("Could not reach the AI intent classifier.");
            } finally {
                this.isDrafting = false;
            }
        },
        
        editGroup() { this.editGroupModal = true; },
        createGroup() { this.displayGroupModal = true; },

        async setActiveChat(id, type, name, shouldScroll = true) {
            this.activeChatId = id;
            
            const safeId = String(id);
            const rawId = safeId.startsWith('group') ? safeId.replace('group', '') : safeId;

            if (!this.sessions[id]) {
                this.sessions[id] = { metadata: { name: name, id: id, type: type }, messages: [] };
            }

            let history;  
            if (safeId.startsWith('group')) {
                history = await this.$wire.selectGroup(id, type);
            } else {  
                history = await this.$wire.selectUser(id, type);
            }
            this.sessions[id].messages = history;

            this.syncCurrentChatResults();
            
            if (window.currentEchoChannel) {
                window.Echo.leave(window.currentEchoChannel);
            }

            window.currentEchoChannel = `chat.${rawId}`;
            window.Echo.private(window.currentEchoChannel).listen('.message.new', (e) => {
                let data = e.message || e;
                this.sessions[id].messages.push({
                    body: data.message || data.body || data.content,
                    auth: 0,
                    id: data.id || Date.now(),
                    time: data.time || new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })
                });
                this.sessions = { ...this.sessions };
                this.$nextTick(() => this.scrollToBottom(id));
            });

            this.sessions = { ...this.sessions };
            if (shouldScroll) this.$nextTick(() => this.scrollToBottom(id));
        },

        async setEditChat(id, type, name) {
            this.editChatId = id;
            this.editGroupName = name;
        },

        async sendChatMessage() {
            if (!this.message.trim() || !this.activeChatId) return;

            const text = this.message;
            const id = this.activeChatId;
            this.message = '';

            await this.$wire.sendMessage(text);
            
            this.sessions[id].messages.push({ 
                body: text, senderName: text, auth: 1, id: Date.now(),
                time: new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })
            });
          
            this.sessions = { ...this.sessions };
            this.$nextTick(() => this.scrollToBottom(id));
        },

        async jumpToMessage(result) {
            await this.setActiveChat(result.chatId, result.chatType, result.sender, false);
            setTimeout(() => {
                const textElement = document.getElementById(`msg-${result.id}`);
                if (textElement) {
                    textElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    const bubble = textElement.closest('.msg-bubble');
                    if (bubble) {
                        bubble.classList.add('message-jump-flash');
                        setTimeout(() => bubble.classList.remove('message-jump-flash'), 1500);
                    }
                }
            }, 300);
        },
        
        async performSearch(query) {
            if (query.length < 3) {
                this.allResults = []; this.results = []; this.activeResultIndex = -1; return;
            }
            const raw = await this.$wire.performSearch(query);
            this.allResults = Array.isArray(raw) ? raw : Object.values(raw);
            this.syncCurrentChatResults();
        },
        
        async performUserSearch(userSearchQuery) {
            if (userSearchQuery.length < 3) { this.allResults = []; return; }
            const raw = await this.$wire.performUserSearch(userSearchQuery);
            this.allResults = Array.isArray(raw) ? raw : Object.values(raw);
        },

        syncCurrentChatResults() {
            if (!this.activeChatId) { this.results = []; this.activeResultIndex = -1; return; }
            this.results = this.allResults.filter(r => r.chatId == this.activeChatId);
            this.activeResultIndex = this.results.length > 0 ? 0 : -1;
        },

        getMatchCount(id) {
            if (!this.searchQuery || this.searchQuery.length < 3) return 0;
            return this.allResults.filter(r => r.chatId == id).length;
        },

        async navigateResults(direction) {
            if (this.results.length === 0) return;
            if (direction === 'next') {
                this.activeResultIndex = (this.activeResultIndex + 1) % this.results.length;
            } else {
                this.activeResultIndex = (this.activeResultIndex - 1 + this.results.length) % this.results.length;
            }
            await this.jumpToMessage(this.results[this.activeResultIndex]);
        },

        scrollToBottom(id) {
            const panel = document.getElementById(`panel-${id}`);
            if (panel) panel.scrollTop = panel.scrollHeight;
        },
        
        init() {
            const saved = localStorage.getItem('anychat_admin_sessions');
            if (saved) {
                try { this.sessions = JSON.parse(saved); } catch (e) { this.sessions = {}; }
            }

            const preloaded = @json(property_exists($this, 'preloadedChat') ?$this->preloadedChat : null);

            if (preloaded) {
                this.activeChatId = preloaded.id;

                if (!this.sessions[preloaded.id]) {
                    this.sessions[preloaded.id] = {
                        metadata: { name: preloaded.name, id: preloaded.id, type: preloaded.type },
                        messages: []
                    };
                }
                this.sessions[preloaded.id].messages = preloaded.messages;

                this.$nextTick(() => this.scrollToBottom(preloaded.id));
            }

            this.$watch('sessions', (val) => {
                localStorage.setItem('anychat_admin_sessions', JSON.stringify(val));
            }, { deep: true });
        },
    }));
</script>
@endscript
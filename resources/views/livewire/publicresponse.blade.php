@props([
    'type' => 'xs',
    'variant' => 'outline',
    'color' => null,
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

    // 1. Determine base colors FIRST
    $defaultUserBg = match ($color) {
        'red'  => '#ef4444',
        'zinc' => '#18181b',
        default => '#2563eb',
    };

    // 2. Set active variables
    $pColor = $primaryColor ?? $defaultUserBg;
    $aColor = $adminColor ?? '#f1f5f9';

    $windowClasses = Arr::toCssClasses([
        'bg-white dark:bg-slate-900 shadow-2xl border border-slate-200 dark:border-slate-800 rounded-2xl',
        'text-sm' => $size === 'base',
        'text-xs' => $size === 'xs',
    ]);
@endphp

<div x-data="chatAdmin" class="flex h-screen w-full bg-slate-50 overflow-hidden anychat-container">

 
    {{-- Sidebar --}}
    <div class="md:w-[220px] bg-slate-900 shadow-2xl border-r border-slate-800 flex flex-col z-10 shrink-0">
        <div class="p-6 border-b border-slate-800 bg-slate-900">
            <h2 class="text-xl font-bold tracking-tight text-gray-600">AnyChat Console</h2>
        </div>

         <button @click="createGroup" class="text-white text-[10px]"> + Create group chat 

         </button>

          <button @click="editGroup" class="text-white text-[10px]"> + Edit group chat 

         </button>


       
        

        <div class="flex-1 overflow-y-auto custom-scrollbar">
            <div class="p-4 bg-slate-800/50 text-[10px] font-bold text-slate-500 uppercase tracking-widest flex justify-between items-center">
                <span>Active Support Queue</span>
                
            </div>

            <div class="wire:ignore p-4 border-b border-slate-200 bg-slate-50">
                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Search Conversations</label>
                
                <div class="relative">
                    <input type="text" 
                           x-model="searchQuery" 
                           @input.debounce.500ms="performSearch(searchQuery)"
                           placeholder="Find message..." 
                           class="w-full bg-white border border-slate-300 text-slate-900 text-sm rounded-lg pl-3 pr-20 py-2 outline-none focus:ring-2 focus:ring-blue-500">
                    
                           
                    {{-- Navigation Controls --}}
                    <div x-show="results.length > 0" class="absolute right-2 top-1.5 z-20 flex items-center gap-1 bg-slate-100 rounded px-1 border border-slate-200 shadow-sm">
                        <span class="text-[10px] text-slate-500 font-mono" x-text="(activeResultIndex + 1) + '/' + results.length"></span>
                        <button @click="navigateResults('prev')" class="p-1 hover:bg-slate-200 rounded text-slate-600">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7"/></svg>
                        </button>
                        <button @click="navigateResults('next')" class="p-1 hover:bg-slate-200 rounded text-slate-600">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                    </div>
                </div>

                {{-- Search Dropdown --}}
                <div x-show="searchQuery.length > 2 && results.length > 0" 
                     x-transition
                     class="mt-2 max-h-48 overflow-y-auto bg-white rounded-lg shadow-xl absolute w-64 z-20 border border-slate-200">
                    <template x-for="(result, index) in results" :key="result.id">
                        <button @click="activeResultIndex = index; jumpToMessage(result)" 
                                :class="activeResultIndex === index ? 'bg-blue-50' : ''"
                                class="w-full text-left p-2 border-b border-slate-100 hover:bg-slate-50 transition-colors">
                            <div class="flex justify-between">
                                <span class="font-bold text-blue-600 text-[10px]" x-text="result.sender"></span>
                                <span class="text-[9px] text-slate-400" x-text="result.chatType ? result.chatType.split('\\').pop() : ''"></span>
                            </div>
                            <div class="text-slate-600 text-xs truncate" x-text="result.body"></div>
                        </button>
                    </template>
                </div>
            </div>

            {{-- Group Queue --}}


              <div class="p-4 text-xs font-semibold text-slate-500 uppercase bg-slate-800/30">GroupChat Users</div>
          

          @foreach($group as $gr)

        
    <button @click="setActiveChat('group{{ $gr['id'] }}', '{{ addslashes($gr['type']) }}', '{{ addslashes($gr['name']) }}')" 
            :class="activeChatId == '{{ $gr['id'] }}' ? 'bg-slate-800' : 'hover:bg-slate-800/50'"
            class="w-full flex items-center justify-between p-4 transition-colors border-b border-slate-800">
        
        <div class="flex flex-col text-left">
            {{-- Blade handles the text rendering directly --}}
            <span class="font-bold text-sm text-zinc-400">{{ $gr['name'] }}</span>
       
        </div>

     
    </button>
@endforeach


    
    

             {{-- Guest Queue --}}
            <div class="p-4 text-xs font-semibold text-slate-500 uppercase bg-slate-800/30">Guest Users</div>
            
            {{-- Poll every 15 seconds to fetch incoming new guest chat sessions seamlessly --}}
            <div wire:poll.15s class="flex flex-col">
                @foreach($guestConversations as $guest)
                    <button @click="setActiveChat('{{ $guest['id'] }}', '{{ addslashes($guest['type']) }}', '{{ addslashes($guest['name']) }}')" 
                            :class="activeChatId == '{{ $guest['id'] }}' ? 'bg-slate-800 border-l-4 border-blue-500' : 'hover:bg-slate-800/50'"
                            class="w-full flex items-center justify-between p-4 border-b border-slate-800 transition-all text-left">
                            
                        <span class="font-bold text-sm text-zinc-400">{{ $guest['name'] }}</span>

                        {{-- Search Result Badge matching registered users layout --}}
                        <div x-cloak 
                             x-show="getMatchCount('{{ $guest['id'] }}') > 0" 
                             class="bg-blue-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-sm">
                             <span x-text="getMatchCount('{{ $guest['id'] }}')"></span>
                        </div>
                    </button>
                @endforeach
            </div>

            <div class="p-4 text-xs font-semibold text-slate-500 uppercase bg-slate-800/30">Registered Users</div>
          

            {{-- Registered Users Queue --}}
          @foreach($users as $user)
    <button @click="setActiveChat('{{ $user->id }}', '{{ addslashes(get_class($user)) }}', '{{ addslashes($user->name) }}')"
            :class="activeChatId == '{{ $user->id }}' ? 'bg-slate-800' : 'hover:bg-slate-800/50'"
            class="w-full flex items-center justify-between p-4 transition-colors border-b border-slate-800">
        
        <div class="flex flex-col text-left">
            {{-- Blade handles the text rendering directly --}}
            <span class="font-bold text-sm text-zinc-400">{{ $user->name }}</span>
       
        </div>

        {{-- NEW: Search Result Badge --}}
        {{-- Blade injects the ID, Alpine evaluates the function --}}
        <div x-cloak 
             x-show="getMatchCount('{{ $user->id }}') > 0" 
             class="bg-blue-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-sm">
             <span x-text="getMatchCount('{{ $user->id }}')"></span>
        </div>
    </button>
@endforeach

   
    
        </div>
    </div>

    {{-- Main Chat Area --}}
  {{-- Main Chat Area --}}
    <div class="flex w-200 flex-col relative h-full min-w-0 max-w-full overflow-hidden">
        <template x-if="activeChatId">
            <div class="flex flex-col h-full w-full min-w-0">
                
                {{-- Chat Header --}}
                <div class="p-4 border-b flex justify-between items-center shadow-sm bg-white z-10 min-w-0">
                    <div class="flex items-center gap-3 min-w-0 w-full">
                        {{-- Added truncate so long names don't push the layout out --}}
                        <span class="font-bold text-slate-400 truncate" x-text="sessions[activeChatId]?.metadata?.name || 'Loading...'"></span>
                    </div>
                </div>

                {{-- Message Panel --}}
                {{-- Added overflow-x-hidden to strictly forbid horizontal blowout --}}
                <div wire:ignore class="flex-1 overflow-y-auto overflow-x-hidden p-6 space-y-4 bg-slate-50 min-w-0" :id="'panel-' + activeChatId">
                    <template x-for="(msg, index) in sessions[activeChatId]?.messages || []" :key="index">
                        
                        {{-- Row Alignment (Moved 'flex' to standard class to avoid binding conflicts) --}}
                        <div class="msg-row flex w-full mb-4 min-w-0" :class="(msg.auth == 1) ? 'justify-end' : 'justify-start'">
                            
                            {{-- Bubble Layout --}}
                            <div class="p-3 rounded-2xl shadow-sm max-w-[85%] msg-bubble transition-all duration-500 min-w-0"
                                 :class="(msg.auth == 1) 
                                        ? 'bg-dynamic-admin text-dynamic-admin rounded-tr-sm' 
                                        : '{{ $variant === "outline" ? "border-2 border-dynamic-user text-dynamic-user bg-transparent rounded-tl-sm" : "bg-dynamic-user text-white rounded-tl-sm" }}'">
                                
                                {{-- Added min-w-0 and break-words here --}}
                                <p x-text="msg.body || msg.message" class="text-sm break-words whitespace-pre-wrap min-w-0" :id="'msg-' + msg.id"></p>
                                 <span x-text="msg.senderName" class="text-[9px] opacity-60 mt-1 block" :class="(msg.auth == 1) ? 'text-right' : 'text-left'"></span>
                                 <span x-text="msg.time" class="text-[9px] opacity-60 mt-1 block" :class="(msg.auth == 1) ? 'text-right' : 'text-left'"></span>
                   
                            </div>
                            
                        </div>
                    </template>
                </div>

                {{-- Input Area with AI Integration --}}
                <div class="p-4 border-t bg-white relative min-w-0 w-full">
                    <div class="flex items-center gap-2 min-w-0 w-full">
                        
                        {{-- AI Draft Button --}}
                        <button @click="draftWithAI()" 
                                :disabled="isDrafting"
                                class="p-2.5 text-purple-600 hover:bg-purple-50 rounded-full transition-all flex-shrink-0 disabled:opacity-50"
                                title="Draft reply using AI">
                            
                            {{-- Sparkle Icon / Loading Spinner --}}
                            <svg x-show="!isDrafting" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            <svg x-show="isDrafting" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        </button>

                        {{-- Text Input --}}
                        <input type="text" 
                               x-model="message" 
                               @keydown.enter="sendChatMessage()" 
                               placeholder="Type your reply or ask AI..." 
                               class="w-full border border-slate-300 rounded-2xl px-4 py-3 outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-all min-w-0">
                    </div>
                </div>
            </div>
        </template>
       
 
        {{-- Empty State --}}
        <div x-show="!activeChatId" class="flex-1 flex items-center justify-center text-slate-400 bg-slate-50 min-w-0">
          
        <div class="text-center">
                <svg class="w-16 h-16 mx-auto mb-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                <p>Select a conversation to start messaging</p>
            </div>

        </div>
  <div x-show="editGroupModal" 
     x-cloak
     class="flex flex-row gap-6 p-6 bg-white rounded-xl shadow-2xl absolute top-4 left-4 right-4 z-30 border border-slate-200 overflow-visible"
     @click.away="editGroupModal = false">

    <!-- LEFT COLUMN: Takes up all available remaining space (flex-1) -->
    <div class="flex-1 flex flex-col min-w-0">
        
        <!-- The Edit Form -->
        <template x-if="editChatId">
            <div class="w-full">
                <div class="flex justify-between items-center mb-4">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Group Name</label>
                    <button @click="editGroupModal = false" class="text-slate-400 hover:text-slate-600 font-bold text-sm">✕</button>
                </div>

                <input 
                    type="text" 
                    wire:model="name" 
                    placeholder="Type a group name..." 
                    class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 mb-4"
                >

                <div class="border-t border-slate-200 pt-4">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Add Users</label>
                    
                    <form wire:submit.prevent="save">
                        <div class="flex flex-row items-start gap-4 w-full">
                            <!-- Search Box Container -->
                            <div 
                                x-data="{
                                    query: '',
                                    results: [],
                                    showDropdown: false,
                                    async performRowSearch() {
                                        if (this.query.length < 2) {
                                            this.results = [];
                                            this.showDropdown = false;
                                            return;
                                        }
                                        this.results = await $wire.searchAvailableUsers(this.query);
                                        this.showDropdown = true;
                                    }
                                }" 
                                class="flex-1 relative"
                                @click.away="showDropdown = false"
                            >
                                <input 
                                    type="text" 
                                    x-model="query" 
                                    @input.debounce.300ms="performRowSearch"
                                    placeholder="Type to search users..." 
                                    class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 h-[40px]"
                                >
                                
                                <!-- Search Dropdown -->
                                <div 
                                    x-cloak
                                    x-show="showDropdown && results && Object.values(results).length > 0" 
                                    @wheel.stop
                                    class="absolute top-full left-0 z-50 w-full mt-1 bg-white border border-slate-200 rounded-md shadow-xl max-h-[280px] overflow-y-auto overscroll-contain"
                                >
                                    <template x-for="user in Object.values(results)" :key="user.id">
                                        <div class="flex flex-row items-center justify-between py-2.5 px-3 border-b border-slate-100 last:border-0 hover:bg-slate-50">   
                                            <label class="flex items-center gap-3 cursor-pointer flex-1">
                                                <input 
                                                    type="checkbox" 
                                                    :value="user.id" 
                                                    @change="$wire.toggleUser(user.id, user.name, $event.target.closest('.flex-row').querySelector('select').value)"
                                                    class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                                >
                                                <span x-text="user.name" class="font-medium text-sm text-slate-700"></span>
                                            </label>
                                            
                                            <select 
                                                @change="$wire.updateRole(user.id, $event.target.value)"
                                                class="block w-28 rounded-md border-slate-300 py-1.5 pl-3 pr-8 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                @foreach ($this->availableRoles as $role)
                                                    <option value="{{ $role['value'] }}">{{ $role['label'] }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- Save Button -->
                            <button 
                                type="submit" 
                                class="shrink-0 px-4 py-2 h-[40px] bg-indigo-600 text-white text-sm font-bold rounded-md hover:bg-indigo-700 shadow-sm"
                            >
                                Save Users
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>

        <!-- Empty State -->
        <div x-show="!editChatId" class="flex-1 flex flex-col items-center justify-center text-slate-400 bg-slate-50 min-h-[250px] rounded-lg min-w-0">
            <svg class="w-16 h-16 mx-auto mb-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
            <p>Select a group to edit</p>
        </div>

    </div>

    <!-- RIGHT COLUMN: Pinned Sidebar (Fixed width, shrink-0 prevents compressing) -->
    <div class="w-[280px] shrink-0 border-l border-slate-200 pl-6 flex flex-col">   
        
        <div class="p-4 text-xs font-semibold text-slate-500 uppercase bg-slate-800/30 rounded-t-lg">GroupChat Users</div>
        
        <div class="overflow-y-auto max-h-[50vh] border border-t-0 border-slate-800/30 rounded-b-lg">
            @foreach($group as $gr)
                <button @click="setEditChat('group{{ $gr['id'] }}', '{{ addslashes($gr['type']) }}', '{{ addslashes($gr['name']) }}')" 
                        :class="editChatId == '{{ $gr['id'] }}' ? 'bg-slate-800' : 'hover:bg-slate-800/50'"
                        class="w-full flex items-center justify-between p-4 transition-colors border-b border-slate-800 last:border-b-0">
                    
                    <div class="flex flex-col text-left">
                        <span class="font-bold text-sm text-zinc-400">{{ $gr['name'] }}</span>
                    </div>
                </button>
            @endforeach
        </div>

    </div>
</div>





     
        <!-- Modal Container: Removed overflow-y-auto & max-h-72 to prevent whole-modal scrolling -->
<div x-show="displayGroupModal" 
     x-cloak
     class="p-5 bg-white rounded-xl shadow-2xl absolute top-4 left-4 right-4 z-30 border border-slate-200 overflow-visible"
      @click.away="displayGroupModal = false">

    <div class="flex justify-between items-center mb-4">
        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Group Name</label>
        <button @click="displayGroupModal = false" class="text-slate-400 hover:text-slate-600 font-bold text-sm">✕</button>
    </div>

    <input 
        type="text" 
        wire:model="name" 
        placeholder="Type a group name..." 
        class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 mb-4"
    >

    <div class="border-t border-slate-200 pt-4">
        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Add Users</label>
        
        <form wire:submit.prevent="save">
            <div class="flex flex-row items-start gap-4 w-full">
                
                <!-- Search Box Container -->
                <div 
                    x-data="{
                        query: '',
                        results: [],
                        showDropdown: false,
                        
                        async performRowSearch() {
                            if (this.query.length < 2) {
                                this.results = [];
                                this.showDropdown = false;
                                return;
                            }
                            this.results = await $wire.searchAvailableUsers(this.query);
                            this.showDropdown = true;
                        }
                    }" 
                    class="flex-1 relative"
                    @click.away="showDropdown = false"
                >
                    <input 
                        type="text" 
                        x-model="query" 
                        @input.debounce.300ms="performRowSearch"
                        placeholder="Type to search users..." 
                        class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 h-[40px]"
                    >
                    
                    <!-- Search Dropdown: Height increased to 280px (fits 5 hits without scrolling) -->
                    <div 
                        x-cloak
                        x-show="showDropdown && results && Object.values(results).length > 0" 
                        @wheel.stop
                        class="absolute top-full left-0 z-50 w-full mt-1 bg-white border border-slate-200 rounded-md shadow-xl max-h-[280px] overflow-y-auto overscroll-contain"
                    >
                        <template x-for="user in Object.values(results)" :key="user.id">
                            <div class="flex flex-row items-center justify-between py-2.5 px-3 border-b border-slate-100 last:border-0 hover:bg-slate-50">   
                                
                                <label class="flex items-center gap-3 cursor-pointer flex-1">
                                    <input 
                                        type="checkbox" 
                                        :value="user.id" 
                                        @change="$wire.toggleUser(user.id, user.name, $event.target.closest('.flex-row').querySelector('select').value)"
                                        class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                    >
                                    <span x-text="user.name" class="font-medium text-sm text-slate-700"></span>
                                </label>
                                
                                <select 
                                    @change="$wire.updateRole(user.id, $event.target.value)"
                                    class="block w-28 rounded-md border-slate-300 py-1.5 pl-3 pr-8 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    @foreach ($this->availableRoles as $role)
                                        <option value="{{ $role['value'] }}">{{ $role['label'] }}</option>
                                    @endforeach
                                </select>

                            </div>
                        </template>
                    </div>

                </div>

                <!-- Save Button -->
                <button 
                    type="submit" 
                    class="shrink-0 px-4 py-2 h-[40px] bg-indigo-600 text-white text-sm font-bold rounded-md hover:bg-indigo-700 shadow-sm"
                >
                    Save Users
                </button>
                
            </div>
        </form>
    </div>
</div>
     <!-- end of didplayGroupModal --> 
    </div>

    {{-- Component Styles --}}
   <style>
    .anychat-container {
        --primary: {{ $pColor }};
        --admin-bg: {{ $aColor }};
        --admin-text: #1e293b;
    }

    /* Bulletproof Custom Utilities */
    .bg-dynamic-user { background-color: var(--primary) !important; }
    .text-dynamic-user { color: var(--primary) !important; }
    .border-dynamic-user { border-color: var(--primary) !important; }

    .bg-dynamic-admin { background-color: var(--admin-bg) !important; }
    .text-dynamic-admin { color: var(--admin-text) !important; }

    .message-jump-flash {
        background-color: #fef08a !important; 
        color: #0f172a !important; 
        box-shadow: 0 0 0 4px #eab308 !important; 
        transition: all 0.3s ease-in-out;
    }
    [x-cloak] { display: none !important; }
</style>
</div>


{{-- Logic --}}
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('chatAdmin', () => ({
        sessions: {}, 
        activeChatId: null,
        editChatId: null,
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
                // Fetch the AI suggestion from the Livewire backend
                const suggestion = await this.$wire.generateAiReply(this.activeChatId);
                
                if (suggestion) {
                    // Populate the input field with the AI response
                    this.message = suggestion;
                }
            } catch (error) {
                console.error("AI generation failed:", error);
                alert("Could not reach the AI intent classifier.");
            } finally {
                this.isDrafting = false;
            }
        },
editGroup(){
     this.editGroupModal = true;
    },

    createGroup(){
    this.displayGroupModal = true;
    },

    async setActiveChat(id, type, name, shouldScroll = true) {
    this.activeChatId = id;

   console.log(this.activeChatId);

    const rawId = id.startsWith('group') ? id.replace('group', '') : id;

    // 1. Update the browser URL without reloading the page
    const newUrl = `{{ $this->path }}/${id}/${encodeURIComponent(type)}`;
    window.history.pushState({}, '', newUrl);
    
    if (!this.sessions[id]) {
        this.sessions[id] = {
            metadata: { name: name, id: id, type: type },
            messages: []
        };
    }

    // 2. Call selectUser to set the Livewire state and get the history

   
  let history;  

if (id.startsWith('group')) {
     history = await this.$wire.selectGroup(id, type);
   
} else

   {  history = await this.$wire.selectUser(id, type);
}
    this.sessions[id].messages = history;

    // 3. Update search results to match the newly clicked user
    this.syncCurrentChatResults();
    
    // 4. REAL-TIME EVENT LISTENER (FIXES ECHO LOOP)
    // Unsubscribe from any previously monitored guest channel to avoid stacking listeners
    if (window.currentEchoChannel) {
        window.Echo.leave(window.currentEchoChannel);
    }

    // Bind to the unique channel for this specific guest/user conversation
    window.currentEchoChannel = `chat.${rawId}`;
    window.Echo.private(window.currentEchoChannel)
        .listen('.message.new', (e) => {
            // Extract payload smoothly whether wrapped inside an object or flat
            let data = e.message || e;

            // CRITICAL FIX: If auth is 1, it means the admin sent it.
            // Ignore it entirely since sendChatMessage() already put it on screen.
          /*  if (data.auth && Number(data.auth) === 1) {
                return; 
            } */
console.log(e);
            // This is a authentic inbound message from the guest. Push it!
            this.sessions[id].messages.push({
                body: data.message || data.body || data.content,
                auth: 0, // Force Guest alignment
                id: data.id || Date.now(),
                time: data.time || new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })
            });

            // Trigger reactivity updates
            this.sessions = { ...this.sessions };
            this.$nextTick(() => this.scrollToBottom(id));
        });

    // Trigger Alpine reactivity for initial load
    this.sessions = { ...this.sessions };
    
    if (shouldScroll) {
        this.$nextTick(() => this.scrollToBottom(id));
    }
},

 async setEditChat(id, type, name, shouldScroll = true) {
  this.editChatId = id;


},

        async sendChatMessage() {
            if (!this.message.trim() || !this.activeChatId) return;

            const text = this.message;
            const id = this.activeChatId;

            // Optimistically clear the input
            this.message = '';

            // Send to Livewire backend
            await this.$wire.sendMessage(text);
            
            // Push to local Alpine state immediately
            this.sessions[id].messages.push({ 
                body: text, 
                senderName: text,
                auth: 1, 
                id: Date.now(),
                time: new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })
            });
          
            // Trigger Alpine reactivity and scroll
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
        this.allResults = [];
        this.results = [];
        this.activeResultIndex = -1;
        return;
    }
    
    // 1. Fetch all matches globally
    const raw = await this.$wire.performSearch(query);

    
    this.allResults = Array.isArray(raw) ? raw : Object.values(raw);
    
    // 2. Immediately filter for the current chat if one is open
    this.syncCurrentChatResults();
},
async performUserSearch(userSearchQuery) {
    if (userSearchQuery.length < 3) {
        this.allResults = [];
        return;
}
    const raw = await this.$wire.performUserSearch(userSearchQuery);

 

    
    this.allResults = Array.isArray(raw) ? raw : Object.values(raw);

},



        

syncCurrentChatResults() {
    if (!this.activeChatId) {
        this.results = [];
        this.activeResultIndex = -1;
        return;
    }
    
    // Filter global results to only those matching the current active user/chat
    this.results = this.allResults.filter(r => r.chatId == this.activeChatId);
    this.activeResultIndex = this.results.length > 0 ? 0 : -1;
},

getMatchCount(id) {
    // Returns the number of search matches for a specific user ID
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
        try { 
            this.sessions = JSON.parse(saved); 
        } catch (e) { 
            this.sessions = {}; 
        }
    }

    // 1. Check if the URL passed a specific user ID to open immediately
    const urlChatId = '{{ $initialChatId }}';
    const urlChatType = '{{ addslashes($initialChatType) }}';

    if (urlChatId) {
        // If a name isn't available yet, use a placeholder until history loads
        this.setActiveChat(urlChatId, urlChatType, 'Loading...', true);
    }

    this.$watch('sessions', (val) => {
        localStorage.setItem('anychat_admin_sessions', JSON.stringify(val));
    }, { deep: true });
}
     
    }));
});
</script>
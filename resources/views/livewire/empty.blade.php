     {{-- Empty State --}}



    

      <x-layout :heading="__('Profile')" 
          :subheading="__('Update your name and email address')"
          :users="$users"
          :guest-conversations="$guestConversations"
          :group="$group"
          variant="outline"> {{-- Pass as a static string, not a dynamic variable :variant --}}
    
    <div class="flex-1 flex items-center justify-center text-slate-400 bg-slate-50 min-w-0">
        
        <template x-if="activeChatId">
            <div class="flex flex-col h-full w-full min-w-0">
                
                {{-- Chat Header --}}
                <div class="p-4 border-b flex justify-between items-center shadow-sm bg-white z-10 min-w-0">
                    <div class="flex items-center gap-3 min-w-0 w-full">
                        <span class="font-bold text-slate-400 truncate" x-text="sessions[activeChatId]?.metadata?.name || 'Loading...'"></span>
                    </div>
                </div>

                {{-- Message Panel --}}
                <div wire:ignore class="flex-1 overflow-y-auto overflow-x-hidden p-6 space-y-4 bg-slate-50 min-w-0" :id="'panel-' + activeChatId">
                    <template x-for="(msg, index) in sessions[activeChatId]?.messages || []" :key="index">
                        <div class="msg-row flex w-full mb-4 min-w-0" :class="(msg.auth == 1) ? 'justify-end' : 'justify-start'">
                            
                            {{-- Bubble Layout: fallback with $variant ?? 'outline' --}}
                            <div class="p-3 rounded-2xl shadow-sm max-w-[85%] msg-bubble transition-all duration-500 min-w-0"
                                 :class="(msg.auth == 1) 
                                        ? 'bg-dynamic-admin text-dynamic-admin rounded-tr-sm' 
                                        : '{{ ($variant ?? 'outline') === 'outline' ? 'border-2 border-dynamic-user text-dynamic-user bg-transparent rounded-tl-sm' : 'bg-dynamic-user text-white rounded-tl-sm' }}'">
                                
                                <p x-text="msg.body || msg.message" class="text-sm break-words whitespace-pre-wrap min-w-0" :id="'msg-' + msg.id"></p>
                                <span x-text="msg.senderName" class="text-[9px] opacity-60 mt-1 block" :class="(msg.auth == 1) ? 'text-right' : 'text-left'"></span>
                                <span x-text="msg.time" class="text-[9px] opacity-60 mt-1 block" :class="(msg.auth == 1) ? 'text-right' : 'text-left'"></span>
                            </div>
                            
                        </div>
                    </template>
                </div>

                {{-- Input Area --}}
                <div class="p-4 border-t bg-white relative min-w-0 w-full">
                    <div class="flex items-center gap-2 min-w-0 w-full">
                        <button @click="draftWithAI()" 
                                :disabled="isDrafting"
                                class="p-2.5 text-purple-600 hover:bg-purple-50 rounded-full transition-all flex-shrink-0 disabled:opacity-50"
                                title="Draft reply using AI">
                            <svg x-show="!isDrafting" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            <svg x-show="isDrafting" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        </button>

                        <flux:input type="text" 
                               x-model="message" 
                               @keydown.enter="sendChatMessage()" 
                               placeholder="Type your reply or ask AI..." 
                               required autofocus autocomplete="name" />
                    </div>
                </div>
            </div>
        </template>
        
        {{-- Empty State --}}
        <div class="text-center" x-show="!activeChatId" x-cloak>
            <svg class="w-full h-16 mx-auto mb-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
            <p>Select a conversation to start messaging</p>
        </div>
    </div>
</x-layout>
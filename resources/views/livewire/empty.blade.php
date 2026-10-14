<x-layout :heading="__('Messages')" 
          :subheading="__('View and reply to active conversations')"
          :users="$users"
          :guest-conversations="$guestConversations"
          :group="$group"
          variant="outline">
    
    <div class="my-6 flex flex-col flex-1 min-h-[520px] w-full rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 overflow-hidden">
        
        <template x-if="activeChatId">
            <div class="flex flex-col h-[520px] w-full min-w-0">
                
                {{-- Chat Header --}}
                <div class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700 flex justify-between items-center bg-white dark:bg-zinc-900 z-10 min-w-0">
                    <flux:heading size="sm" class="truncate" x-text="sessions[activeChatId]?.metadata?.name || '{{ __('Loading...') }}'" />
                </div>

                {{-- Message Panel --}}
                <div wire:ignore class="flex-1 overflow-y-auto overflow-x-hidden p-6 space-y-4 bg-zinc-50/50 dark:bg-zinc-950/30 min-w-0" :id="'panel-' + activeChatId">
                    <template x-for="(msg, index) in sessions[activeChatId]?.messages || []" :key="index">
                        <div class="msg-row flex w-full mb-4 min-w-0" :class="(msg.auth == 1) ? 'justify-end' : 'justify-start'">
                            
                            <div class="p-3 rounded-2xl shadow-xs max-w-[85%] msg-bubble transition-all duration-500 min-w-0"
                                 :class="(msg.auth == 1) 
                                        ? 'bg-dynamic-admin text-dynamic-admin rounded-tr-sm' 
                                        : '{{ ($variant ?? 'outline') === 'outline' ? 'border border-dynamic-user text-dynamic-user bg-transparent rounded-tl-sm' : 'bg-dynamic-user text-white rounded-tl-sm' }}'">
                                
                                <p x-text="msg.body || msg.message" class="text-sm break-words whitespace-pre-wrap min-w-0" :id="'msg-' + msg.id"></p>
                                <span x-text="msg.senderName" class="text-[10px] opacity-60 mt-1 block" :class="(msg.auth == 1) ? 'text-right' : 'text-left'"></span>
                                <span x-text="msg.time" class="text-[10px] opacity-60 mt-0.5 block" :class="(msg.auth == 1) ? 'text-right' : 'text-left'"></span>
                            </div>
                            
                        </div>
                    </template>
                </div>

                {{-- Input Area --}}
                <div class="p-4 border-t border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 min-w-0 w-full">
                    <div class="flex items-center gap-2 min-w-0 w-full">
                        <flux:button 
                            variant="subtle" 
                            icon="bolt"
                            @click="draftWithAI()" 
                            ::disabled="isDrafting"
                            title="{{ __('Draft reply using AI') }}" 
                        />

                        <div class="flex-1">
                            <flux:input 
                                type="text" 
                                x-model="message" 
                                @keydown.enter="sendChatMessage()" 
                                :placeholder="__('Type your reply or ask AI...')" 
                                autocomplete="off" 
                            />
                        </div>

                        <flux:button variant="primary" icon="paper-airplane" @click="sendChatMessage()">
                            {{ __('Send') }}
                        </flux:button>
                    </div>
                </div>
            </div>
        </template>
        
        {{-- Empty State --}}
        <div class="flex-1 flex flex-col items-center justify-center p-6 text-center" x-show="!activeChatId" x-cloak>
            <flux:icon.chat-bubble-left-right class="size-10 text-zinc-400 mb-3" />
            <flux:heading size="sm">{{ __('No conversation selected') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Select a conversation from the sidebar to start messaging.') }}</flux:text>
        </div>
    </div>
</x-layout>
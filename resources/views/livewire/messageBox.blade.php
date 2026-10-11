      <div class="mt-5 w-full max-w-lg">     
                
                {{-- Main Chat Area --}}
                <div class="flex w-200 flex-col relative h-full min-w-0 max-w-full overflow-hidden">
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
                                    
                                    {{-- Row Alignment --}}
                                    <div class="msg-row flex w-full mb-4 min-w-0" :class="(msg.auth == 1) ? 'justify-end' : 'justify-start'">
                                        
                                        {{-- Bubble Layout --}}
                                        <div class="p-3 rounded-2xl shadow-sm max-w-[85%] msg-bubble transition-all duration-500 min-w-0"
                                             :class="(msg.auth == 1) 
                                                    ? 'bg-dynamic-admin text-dynamic-admin rounded-tr-sm' 
                                                    : '{{ $variant === "outline" ? "border-2 border-dynamic-user text-dynamic-user bg-transparent rounded-tl-sm" : "bg-dynamic-user text-white rounded-tl-sm" }}'">
                                            
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
                                    <flux:input type="text" 
                                           x-model="message" 
                                           @keydown.enter="sendChatMessage()" 
                                           placeholder="Type your reply or ask AI..." 
                                           required autofocus autocomplete="name"
                                            />
                                </div>
                            </div>
                        </div>
                    </template>
                   
               
                    
                  

                    <!-- Modal Container -->
                 
                    <!-- end of displayGroupModal --> 

                </div>
            </div>
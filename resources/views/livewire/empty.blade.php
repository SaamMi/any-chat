     {{-- Empty State --}}



    

       <x-layout :heading="__('Profile')" 
                 :subheading="__('Update your name and email address')"
                 :users="$users"
                 :guest-conversations="$guestConversations"
                 :group="$group">
                    <div class="flex-1 flex items-center justify-center text-slate-400 bg-slate-50 min-w-0">
                        <div class="text-center">
                            <svg class="w-full h-16 mx-auto mb-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            <p>Select a conversation to start messaging</p>
                        </div>
                    </div>

        </x-layout>             
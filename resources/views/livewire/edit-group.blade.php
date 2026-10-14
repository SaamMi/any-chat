<x-layout :heading="__('Edit Group')" 
          :subheading="__('Manage your group members and roles')"
          :users="$users"
          :guest-conversations="$guestConversations"
          :group="$group">

    <div class="my-6 flex flex-col lg:flex-row items-start gap-8 w-full">
        
        {{-- Left Column: Edit Form or Empty State --}}
        <div class="flex-1 w-full min-w-0">
            <template x-if="editChatId">
                <form @submit.prevent="$wire.saveGroup(editChatId, editGroupName)" class="w-full space-y-6">
                    
                    <flux:input 
                        x-model="editGroupName" 
                        :label="__('Group name')" 
                        type="text" 
                        required 
                    />

                    <flux:separator variant="subtle" />

                    <div class="flex flex-row items-end gap-4 w-full">
                        {{-- Search Box Container --}}
                        <div x-data="{
                                query: '',
                                results: [],
                                showDropdown: false,
                                
                                init() {
                                    this.$watch('editChatId', () => {
                                        this.query = '';
                                        this.results = [];
                                        this.showDropdown = false;
                                    });
                                },

                                async performRowSearch() {
                                    if (this.query.length < 2) {
                                        this.results = [];
                                        this.showDropdown = false;
                                        return;
                                    }
                                    this.results = await $wire.searchAvailableTeamUsers(this.query, this.editChatId);
                                    this.showDropdown = true;
                                }
                            }" 
                            class="flex-1 relative"
                            @click.away="showDropdown = false">
                            
                            <flux:input 
                                :label="__('Search users')"
                                type="search" 
                                icon="magnifying-glass"
                                x-model="query" 
                                @input.debounce.300ms="performRowSearch"
                                :placeholder="__('Type to search users...')" 
                            />
                            
                            {{-- Search Dropdown --}}
                            <div x-cloak
                                 x-show="showDropdown && results && Object.values(results).length > 0" 
                                 @wheel.stop
                                 class="absolute top-full left-0 z-50 w-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-lg max-h-[280px] overflow-y-auto overscroll-contain divide-y divide-zinc-100 dark:divide-zinc-700/50">
                                <template x-for="user in results" :key="user.id">
                                    <div x-data="{ 
                                             status: user.status, 
                                             dbRole: user.role,
                                             isDeleting: false 
                                         }" 
                                         x-show="!isDeleting"         
                                         class="flex flex-row items-center justify-between gap-3 py-2.5 px-3 hover:bg-zinc-50 dark:hover:bg-zinc-700/50">   
                                        
                                        <label class="flex items-center gap-3 cursor-pointer flex-1 min-w-0">
                                            <flux:checkbox 
                                                ::value="user.id" 
                                                ::checked="status === 'member'"
                                                @change="
                                                    status = $event.target.checked ? 'member' : 'non-member';
                                                    $wire.toggleGroupUser(user.id, user.name, dbRole, status);
                                                " 
                                            />
                                            <flux:text x-text="user.name" class="font-medium truncate" />
                                        </label>
                                        
                                        <div class="w-32 shrink-0">
                                            <flux:select size="sm" x-model="dbRole" @change="$wire.updateGroupRole(user.id, user.name, dbRole)">
                                                @foreach ($this->availableRoles as $role)
                                                    <flux:select.option value="{{ $role['value'] }}">
                                                        {{ $role['label'] }}
                                                    </flux:select.option>
                                                @endforeach
                                            </flux:select>
                                        </div>
                                        
                                        <flux:badge 
                                            size="sm" 
                                            ::color="status === 'member' ? 'green' : 'zinc'" 
                                            x-text="status" 
                                            class="capitalize shrink-0" 
                                        />
                                        
                                        <flux:button 
                                            size="sm" 
                                            variant="filled" 
                                            type="button"
                                            x-text="status === 'non-member' ? '{{ __('Add user') }}' : '{{ __('Remove user') }}'"
                                            @click="
                                                status = (status === 'member' ? 'non-member' : 'member');
                                                $wire.toggleGroupUser(user.id, user.name, dbRole, status);
                                            " 
                                        />
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Save Button --}}
                        <flux:button variant="primary" type="submit">
                            {{ __('Save users') }}
                        </flux:button>
                    </div>
                </form>
            </template>

            {{-- Empty State --}}
            <div x-show="!editChatId" x-cloak class="flex flex-col items-center justify-center min-h-[260px] rounded-xl border border-dashed border-zinc-200 dark:border-zinc-700 p-6 text-center">
                <flux:icon.chat-bubble-left-ellipsis class="size-10 text-zinc-400 mb-3" />
                <flux:heading size="sm">{{ __('No group selected') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Select a group from the list to manage its members.') }}</flux:text>
            </div>
        </div>

        {{-- Right Column: Group Selector --}}
        <div class="w-full lg:w-[240px] shrink-0 lg:border-l lg:border-zinc-200 dark:lg:border-zinc-700 lg:pl-6">
            <flux:navlist>
                <flux:navlist.group :heading="__('Groups')">
                    @foreach($group as $gr)
                        <flux:navlist.item 
                            @click="setEditChat('{{ $gr['id'] }}', '{{ addslashes($gr['type']) }}', '{{ addslashes($gr['name']) }}')" 
                            ::class="editChatId == '{{ $gr['id'] }}' ? 'bg-zinc-100 dark:bg-zinc-800 font-medium' : ''">
                            {{ $gr['name'] }}
                        </flux:navlist.item>
                    @endforeach
                </flux:navlist.group>
            </flux:navlist>
        </div>

    </div>
</x-layout>
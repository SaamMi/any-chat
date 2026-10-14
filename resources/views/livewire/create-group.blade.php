<x-layout :heading="__('Create Group')" 
          :subheading="__('Create a new group and assign roles to members')"
          :users="$users"
          :guest-conversations="$guestConversations"
          :group="$group">

    <form wire:submit="save" class="my-6 w-full space-y-6">
        <flux:input 
            wire:model="name" 
            :label="__('Group name')" 
            type="text" 
            required 
            autofocus 
            autocomplete="off" 
            :placeholder="__('Type a group name...')" 
        />

        <flux:separator variant="subtle" />

        <div class="flex flex-row items-end gap-4 w-full">
            <!-- Search Box Container -->
            <div x-data="{
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
                @click.away="showDropdown = false">
                
                <flux:input 
                    :label="__('Add users')"
                    type="search" 
                    icon="magnifying-glass"
                    x-model="query" 
                    @input.debounce.300ms="performRowSearch"
                    :placeholder="__('Type to search users...')" 
                />
                
                <!-- Search Dropdown -->
                <div x-cloak
                     x-show="showDropdown && results && Object.values(results).length > 0" 
                     @wheel.stop
                     class="absolute top-full left-0 z-50 w-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-lg max-h-[280px] overflow-y-auto overscroll-contain divide-y divide-zinc-100 dark:divide-zinc-700/50">
                    <template x-for="user in Object.values(results)" :key="user.id">
                        <div class="flex flex-row items-center justify-between py-2.5 px-3 hover:bg-zinc-50 dark:hover:bg-zinc-700/50">   
                            
                            <label class="flex items-center gap-3 cursor-pointer flex-1">
                                <flux:checkbox 
                                    ::value="user.id" 
                                    @change="$wire.toggleUser(user.id, user.name, $event.target.closest('.flex-row').querySelector('select').value)" 
                                />
                                <flux:text x-text="user.name" class="font-medium" />
                            </label>
                            
                            <div class="w-32">
                                <flux:select size="sm" @change="$wire.updateRole(user.id, $event.target.value)">
                                    @foreach ($this->availableRoles as $role)
                                        <flux:select.option value="{{ $role['value'] }}">
                                            {{ $role['label'] }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Save Button -->
            <flux:button variant="primary" type="submit" data-test="create-group-button">
                {{ __('Save') }}
            </flux:button>
        </div>
    </form>
</x-layout>
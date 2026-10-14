      <x-layout :heading="__('Profile')" 
                 :subheading="__('Update your name and email address')"
                 :users="$users"
                 :guest-conversations="$guestConversations"
                 :group="$group">

                 
<div class="p-5 bg-white rounded-xl border border-slate-200 overflow-visible w-full">

                       

                        <flux:input 
                               wire:model="name" 
                               :label="__('Group name')" type="text" required autofocus autocomplete="name" 
                               class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 mb-4" />

                        <div class="border-t border-slate-200 pt-4">
                         
                            <form wire:submit.prevent="save">
                                <div class="flex flex-row items-start gap-4 w-full">
                                    
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
                                               type="text" 
                                               x-model="query" 
                                               @input.debounce.300ms="performRowSearch"
                                               required autofocus
                                               placeholder="Type to search users...." 
                                               class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 h-[40px]" />
                                        
                                        <!-- Search Dropdown -->
                                        <div x-cloak
                                             x-show="showDropdown && results && Object.values(results).length > 0" 
                                             @wheel.stop
                                             class="absolute top-full left-0 z-50 w-full mt-1 bg-white border border-slate-200 rounded-md shadow-xl max-h-[280px] overflow-y-auto overscroll-contain">
                                            <template x-for="user in Object.values(results)" :key="user.id">
                                                <div class="flex flex-row items-center justify-between py-2.5 px-3 border-b border-slate-100 last:border-0 hover:bg-slate-50">   
                                                    
                                                    <label class="flex items-center gap-3 cursor-pointer flex-1">
                                                         <flux:checkbox 
                                                               value="user.id" 
                                                               @change="$wire.toggleUser(user.id, user.name, $event.target.closest('.flex-row').querySelector('select').value)"
                                                               class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500" />
                                                        <span x-text="user.name" class="font-medium text-sm text-slate-700"></span>
                                                    </label>
                                                    
                                                    <flux:select @change="$wire.updateRole(user.id, $event.target.value)"
                                                            class="block w-28 rounded-md border-slate-300 py-1.5 pl-3 pr-8 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                        @foreach ($this->availableRoles as $role)
                                                            <flux:select.option value="{{ $role['value'] }}">{{ $role['label'] }}</flux:select.option>
                                                        @endforeach
                                                    </flux:select>
                                                </div>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- Save Button -->
                                   <flux:button variant="primary" type="submit">
                        {{ __('Save') }}
                    </flux:button>
                                </div>
                            </form>
                        </div>
                    </div>      
    </x-layout>
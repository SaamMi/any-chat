  <div  
                         x-cloak
                         class="flex flex-row w-full gap-6 p-6 bg-white rounded-xl shadow-2xl absolute top-4 left-4 right-4 z-30 border border-slate-200 overflow-visible"
                         @click.away="editGroupModal = false">

                        <!-- LEFT COLUMN: Takes up all available remaining space (flex-1) -->
                        <div class="flex-1 flex flex-col min-w-0">
                            
                            <!-- The Edit Form -->
                            <template x-if="editChatId">
                                <div class="w-full">
                                    <div class="flex justify-between items-center mb-4">
                                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Edit group </label>
                                        <button @click="editGroupModal = false" class="text-slate-400 hover:text-slate-600 font-bold text-sm">✕</button>
                                    </div>

                                    <div class="border-t border-slate-200 pt-4">
                                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Search users</label>
                                        
                                        <form @submit.prevent="$wire.saveGroup(editChatId,editGroupName)">
                                            <div class="flex flex-row items-start gap-4 w-full">
                                                <!-- Search Box Container -->
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
                                                    
                                                    <input type="text" 
                                                           x-model="query" 
                                                           @input.debounce.300ms="performRowSearch"
                                                           placeholder="Type to search users..." 
                                                           class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 h-[40px]">
                                                    
                                                    <!-- Search Dropdown -->
                                                    <div x-cloak
                                                         x-show="showDropdown && results" 
                                                         @wheel.stop
                                                         class="absolute top-full left-0 z-50 w-full mt-1 bg-white border border-slate-200 rounded-md shadow-xl max-h-[280px] overflow-y-auto overscroll-contain">
                                                        <template x-for="user in results" :key="user.id">
                                                            <div x-data="{ 
                                                                     status: user.status, 
                                                                     dbRole: user.role,
                                                                     isDeleting: false 
                                                                 }" 
                                                                 x-show="!isDeleting"         
                                                                 class="flex flex-row items-center justify-between py-2.5 px-3 border-b border-slate-100 last:border-0 hover:bg-slate-50">   
                                                                
                                                                <label class="flex items-center gap-3 cursor-pointer flex-1"
                                                                       :class="status === 'non-member' ? 'bg-white' : 'bg-slate-100'">
                                                                    <input type="checkbox" 
                                                                           :value="user.id" 
                                                                           :checked="status === 'member'"
                                                                           @change="
                                                                               status = $event.target.checked ? 'member' : 'non-member';
                                                                               $wire.toggleGroupUser(user.id, user.name, dbRole, status);
                                                                           "
                                                                           class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                                                    <span x-text="user.name" class="font-medium text-sm text-slate-700"></span>
                                                                </label>
                                                                
                                                                <select x-model="dbRole"
                                                                        @change="$wire.updateGroupRole(user.id, user.name, dbRole)"
                                                                        class="block w-28 rounded-md border-slate-300 py-1.5 pl-3 pr-8 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                                    @foreach ($this->availableRoles as $role)
                                                                        <option value="{{ $role['value'] }}">{{ $role['label'] }}</option>
                                                                    @endforeach
                                                                </select>
                                                                
                                                                <span x-text="status" class="font-medium text-sm text-slate-700 w-24 text-center capitalize"></span>
                                                                
                                                                <span>   
                                                                    <button type="button"
                                                                            x-text="status === 'non-member' ? 'Add user' : 'Remove user'"
                                                                            @click="
                                                                                status = (status === 'member' ? 'non-member' : 'member');
                                                                                $wire.toggleGroupUser(user.id, user.name, dbRole, status);
                                                                            "   
                                                                            class="px-4 py-2 bg-indigo-600 text-white text-sm font-bold rounded-md hover:bg-indigo-700 shadow-sm">
                                                                    </button> 
                                                                </span>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>

                                                <!-- Save Button -->
                                                <button type="submit" 
                                                        class="shrink-0 px-4 py-2 h-[40px] bg-indigo-600 text-white text-sm font-bold rounded-md hover:bg-indigo-700 shadow-sm">
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
                            <div class="p-4 text-xs font-semibold text-slate-500 uppercase bg-slate-800/30 rounded-t-lg">Groups</div>
                            <div class="overflow-y-auto max-h-[50vh] border border-t-0 border-slate-800/30 rounded-b-lg">
                                @foreach($group as $gr)
                                    <button @click="setEditChat('{{ $gr['id'] }}', '{{ addslashes($gr['type']) }}', '{{ addslashes($gr['name']) }}')" 
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
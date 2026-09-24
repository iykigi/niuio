<div>
    @if ($editingPath)
        {{-- Code editor --}}
        <div class="mb-3 flex items-center justify-between">
            <div class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                <x-heroicon-o-code-bracket class="h-4 w-4" />
                {{ $editingPath }}
            </div>
            <div class="flex gap-2">
                <button wire:click="saveFile" class="btn-primary !py-1.5 text-sm">Save</button>
                <button wire:click="closeEditor" class="btn-secondary !py-1.5 text-sm">Close</button>
            </div>
        </div>

        <div
            wire:ignore
            wire:key="editor-{{ md5($editingPath) }}"
            x-data="portwayCodeEditor(@entangle('editingContent'), @js(pathinfo($editingPath, PATHINFO_EXTENSION)))"
            class="h-[65vh] overflow-hidden rounded-xl2 border border-surface-200 dark:border-white/10"
        >
            <textarea
                x-ref="textarea"
                x-model="content"
                spellcheck="false"
                class="h-full w-full resize-none border-0 bg-white p-4 font-mono text-sm text-slate-800 focus:ring-0 dark:bg-surface-900 dark:text-slate-100"
            ></textarea>
            <div x-ref="monaco" class="hidden h-full w-full"></div>
        </div>
    @else
        {{-- Toolbar --}}
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-1 text-sm text-slate-500 dark:text-slate-400">
                <button wire:click="openDirectory('')" class="hover:text-harbor-600">Home</button>
                @foreach ($breadcrumbs as $crumb)
                    <span>/</span>
                    <button wire:click="openDirectory(@js($crumb['path']))" class="hover:text-harbor-600">{{ $crumb['label'] }}</button>
                @endforeach
            </div>

            <div class="flex items-center gap-2">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search…" class="input !w-40">
                <button wire:click="$set('showCreateFolder', true)" class="btn-secondary !py-1.5 text-sm"><x-heroicon-o-folder-plus class="h-4 w-4" /></button>
                <button wire:click="$set('showCreateFile', true)" class="btn-secondary !py-1.5 text-sm"><x-heroicon-o-document-plus class="h-4 w-4" /></button>
                <label class="btn-secondary cursor-pointer !py-1.5 text-sm">
                    <x-heroicon-o-arrow-up-tray class="h-4 w-4" /> Upload
                    <input type="file" wire:model="uploads" multiple class="hidden">
                </label>
                @if (! empty($selected))
                    <button wire:click="zipSelected" class="btn-secondary !py-1.5 text-sm"><x-heroicon-o-archive-box class="h-4 w-4" /> Zip</button>
                    <button wire:click="deleteSelected" class="btn-danger !py-1.5 text-sm"><x-heroicon-o-trash class="h-4 w-4" /></button>
                @endif
            </div>
        </div>

        <div wire:loading.class="opacity-50" class="card overflow-hidden !p-0">
            <table class="w-full text-sm">
                <thead class="border-b border-surface-200 bg-surface-50 text-left text-xs uppercase tracking-wide text-slate-500 dark:border-white/10 dark:bg-white/5 dark:text-slate-400">
                    <tr>
                        <th class="w-8 px-4 py-2"></th>
                        <th class="cursor-pointer px-4 py-2" wire:click="$set('sortBy', 'name')">Name</th>
                        <th class="hidden cursor-pointer px-4 py-2 sm:table-cell" wire:click="$set('sortBy', 'size')">Size</th>
                        <th class="hidden cursor-pointer px-4 py-2 md:table-cell" wire:click="$set('sortBy', 'modified_at')">Modified</th>
                        <th class="px-4 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-100 dark:divide-white/5">
                    @if ($path !== '')
                        <tr>
                            <td colspan="5" class="px-4 py-2">
                                <button wire:click="goUp" class="flex items-center gap-2 text-slate-500 dark:text-slate-400">
                                    <x-heroicon-o-arrow-uturn-left class="h-4 w-4" /> ..
                                </button>
                            </td>
                        </tr>
                    @endif
                    @forelse ($entries as $entry)
                        <tr wire:key="entry-{{ $entry['path'] }}">
                            <td class="px-4 py-2">
                                <input type="checkbox" wire:click="toggleSelect(@js($entry['path']))" @checked(in_array($entry['path'], $selected)) class="rounded border-surface-300">
                            </td>
                            <td class="px-4 py-2">
                                @if ($entry['type'] === 'directory')
                                    <button wire:click="openDirectory(@js($entry['path']))" class="flex items-center gap-2 font-medium text-slate-700 dark:text-slate-200">
                                        <x-heroicon-o-folder class="h-4 w-4 text-harbor-500" /> {{ $entry['name'] }}
                                    </button>
                                @elseif ($entry['editable'])
                                    <button wire:click="openFile(@js($entry['path']))" class="flex items-center gap-2 text-slate-700 dark:text-slate-200">
                                        <x-heroicon-o-document-text class="h-4 w-4 text-slate-400" /> {{ $entry['name'] }}
                                    </button>
                                @else
                                    <span class="flex items-center gap-2 text-slate-700 dark:text-slate-200">
                                        <x-heroicon-o-document class="h-4 w-4 text-slate-400" /> {{ $entry['name'] }}
                                    </span>
                                @endif
                            </td>
                            <td class="hidden px-4 py-2 text-slate-500 dark:text-slate-400 sm:table-cell">
                                {{ $entry['size'] !== null ? number_format($entry['size'] / 1024, 1).' KB' : '—' }}
                            </td>
                            <td class="hidden px-4 py-2 text-slate-500 dark:text-slate-400 md:table-cell">
                                {{ \Carbon\Carbon::createFromTimestamp($entry['modified_at'])->diffForHumans() }}
                            </td>
                            <td class="px-4 py-2 text-right">
                                <div class="inline-flex gap-1">
                                    @if ($entry['type'] === 'file')
                                        <a href="{{ $this->downloadUrl($entry['path']) }}" class="btn-ghost !p-1.5"><x-heroicon-o-arrow-down-tray class="h-4 w-4" /></a>
                                    @endif
                                    @if (($entry['extension'] ?? '') === 'zip')
                                        <button wire:click="extract(@js($entry['path']))" class="btn-ghost !p-1.5"><x-heroicon-o-archive-box-x-mark class="h-4 w-4" /></button>
                                    @endif
                                    <button wire:click="openRename(@js($entry['path']))" class="btn-ghost !p-1.5"><x-heroicon-o-pencil class="h-4 w-4" /></button>
                                    <button wire:click="openPermissions(@js($entry['path']))" class="btn-ghost !p-1.5"><x-heroicon-o-lock-closed class="h-4 w-4" /></button>
                                    <button wire:click="delete(@js($entry['path']))" class="btn-ghost !p-1.5 text-rose-600 dark:text-rose-400"><x-heroicon-o-trash class="h-4 w-4" /></button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-slate-400">This folder is empty.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    <x-modal :show="$showCreateFolder" wire:click.outside="$set('showCreateFolder', false)">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">New folder</h3>
        <input type="text" wire:model="newName" wire:keydown.enter="createFolder" autofocus class="input mt-4" placeholder="folder-name">
        @error('newName') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        <div class="mt-5 flex justify-end gap-2">
            <button wire:click="$set('showCreateFolder', false)" class="btn-secondary">Cancel</button>
            <button wire:click="createFolder" class="btn-primary">Create</button>
        </div>
    </x-modal>

    <x-modal :show="$showCreateFile" wire:click.outside="$set('showCreateFile', false)">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">New file</h3>
        <input type="text" wire:model="newName" wire:keydown.enter="createFile" autofocus class="input mt-4" placeholder="index.php">
        @error('newName') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        <div class="mt-5 flex justify-end gap-2">
            <button wire:click="$set('showCreateFile', false)" class="btn-secondary">Cancel</button>
            <button wire:click="createFile" class="btn-primary">Create</button>
        </div>
    </x-modal>

    <x-modal :show="$showRename" wire:click.outside="$set('showRename', false)">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Rename</h3>
        <input type="text" wire:model="newName" wire:keydown.enter="rename" autofocus class="input mt-4">
        <div class="mt-5 flex justify-end gap-2">
            <button wire:click="$set('showRename', false)" class="btn-secondary">Cancel</button>
            <button wire:click="rename" class="btn-primary">Rename</button>
        </div>
    </x-modal>

    <x-modal :show="$showPermissions" wire:click.outside="$set('showPermissions', false)">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Permissions</h3>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $permissionsTarget }}</p>
        <input type="text" wire:model="permissionsValue" class="input mt-4 font-mono" placeholder="0644">
        <div class="mt-5 flex justify-end gap-2">
            <button wire:click="$set('showPermissions', false)" class="btn-secondary">Cancel</button>
            <button wire:click="savePermissions" class="btn-primary">Save</button>
        </div>
    </x-modal>
</div>

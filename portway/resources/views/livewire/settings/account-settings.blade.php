<div class="space-y-6">
    <div>
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Account settings</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Manage your profile, API access, and account.</p>
    </div>

    <div class="card max-w-2xl">
        <h3 class="mb-4 font-semibold text-slate-800 dark:text-slate-100">Profile</h3>
        <div class="space-y-4">
            <div>
                <label class="label">Name</label>
                <input type="text" wire:model="name" class="input">
                @error('name') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Email address</label>
                <input type="email" wire:model="email" class="input">
                @error('email') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="label">Timezone</label>
                    <select wire:model="timezone" class="input">
                        @foreach (timezone_identifiers_list() as $tz)
                            <option value="{{ $tz }}">{{ $tz }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Language</label>
                    <select wire:model="locale" class="input">
                        <option value="en">English</option>
                        <option value="es">Español</option>
                        <option value="fr">Français</option>
                        <option value="de">Deutsch</option>
                        <option value="ar">العربية</option>
                        <option value="ku">Kurdî</option>
                    </select>
                </div>
            </div>
        </div>
        <button wire:click="updateProfile" class="btn-primary mt-5">Save profile</button>
    </div>

    <div class="card max-w-2xl">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="font-semibold text-slate-800 dark:text-slate-100">API tokens</h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Use the Portway API to manage your websites, domains, and backups programmatically.</p>
            </div>
        </div>

        <div class="mb-5 space-y-3 rounded-xl2 border border-surface-200 p-4 dark:border-white/10">
            <div>
                <label class="label">Token name</label>
                <input type="text" wire:model="tokenName" placeholder="CI deployment token" class="input">
                @error('tokenName') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Abilities</label>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                    @foreach (\App\Livewire\Settings\AccountSettings::AVAILABLE_ABILITIES as $ability => $desc)
                        <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                            <input type="checkbox" wire:model="tokenAbilities" value="{{ $ability }}" class="rounded border-surface-300 text-harbor-600">
                            {{ $desc }}
                        </label>
                    @endforeach
                </div>
                @error('tokenAbilities') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <button wire:click="createToken" class="btn-primary">Create token</button>
        </div>

        @if ($tokens->isEmpty())
            <p class="text-sm text-slate-400">You haven't created any API tokens yet.</p>
        @else
            <ul class="divide-y divide-surface-100 dark:divide-white/5">
                @foreach ($tokens as $token)
                    <li class="flex items-center justify-between py-3 text-sm">
                        <div>
                            <span class="font-medium text-slate-700 dark:text-slate-200">{{ $token->name }}</span>
                            <span class="ml-2 text-xs text-slate-400">
                                {{ $token->last_used_at ? 'Last used '.$token->last_used_at->diffForHumans() : 'Never used' }}
                            </span>
                        </div>
                        <button wire:click="revokeToken({{ $token->id }})" class="btn-ghost !py-1 !px-2 text-xs text-rose-600">Revoke</button>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="card max-w-2xl border-rose-200 dark:border-rose-500/30">
        <h3 class="mb-2 font-semibold text-rose-700 dark:text-rose-400">Danger zone</h3>
        <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">
            Deleting your account removes access to all of your websites, domains, and databases. This cannot be undone.
        </p>
        <button wire:click="confirmDeletion" class="btn-danger">Delete my account</button>
    </div>

    <x-modal :show="(bool) $plainTextToken" max-width="lg" wire:click.outside="closeTokenReveal">
        <h3 class="mb-2 font-semibold text-slate-800 dark:text-slate-100">Your new API token</h3>
        <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">Copy this token now — you won't be able to see it again.</p>
        <div class="break-all rounded-lg bg-surface-50 p-3 font-mono text-sm dark:bg-white/5">{{ $plainTextToken }}</div>
        <button wire:click="closeTokenReveal" class="btn-primary mt-5">Done</button>
    </x-modal>

    <x-modal :show="$confirmingDeletion" wire:click.outside="$set('confirmingDeletion', false)">
        <h3 class="mb-2 font-semibold text-slate-800 dark:text-slate-100">Delete your account?</h3>
        <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">This will remove your access immediately. Enter your password to confirm.</p>
        <input type="password" wire:model="deleteConfirmPassword" placeholder="Current password" class="input">
        @error('deleteConfirmPassword') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        <div class="mt-4 flex justify-end gap-2">
            <button wire:click="$set('confirmingDeletion', false)" class="btn-secondary">Cancel</button>
            <button wire:click="deleteAccount" class="btn-danger">Delete my account</button>
        </div>
    </x-modal>
</div>

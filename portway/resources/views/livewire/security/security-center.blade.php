<div class="space-y-6" x-data>
    <div>
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Security</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Protect your account with two-factor authentication, review recent sign-ins, and manage who can reach your websites.</p>
    </div>

    {{-- Two-factor authentication --}}
    <div class="card max-w-2xl">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h3 class="font-semibold text-slate-800 dark:text-slate-100">Two-factor authentication</h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    @if (auth()->user()->hasTwoFactorEnabled())
                        Your account is protected with an authenticator app.
                    @else
                        Add an extra layer of protection using an authenticator app like Google Authenticator or 1Password.
                    @endif
                </p>
            </div>
            @if (auth()->user()->hasTwoFactorEnabled())
                <span class="badge-success shrink-0">Enabled</span>
            @else
                <span class="badge-neutral shrink-0">Disabled</span>
            @endif
        </div>

        @if (! auth()->user()->hasTwoFactorEnabled() && ! $settingUp2fa)
            <button wire:click="beginTwoFactorSetup" class="btn-primary mt-4">Enable two-factor authentication</button>
        @endif

        @if ($settingUp2fa)
            <div class="mt-5 rounded-xl2 border border-surface-200 p-4 dark:border-white/10">
                <p class="mb-3 text-sm text-slate-600 dark:text-slate-300">Scan this QR code with your authenticator app, then enter the 6-digit code it shows you.</p>
                <div class="mb-4 inline-block rounded-lg bg-white p-3">
                    {!! app(\App\Services\Security\TwoFactorService::class)->qrCodeSvg(auth()->user(), $pendingSecret) !!}
                </div>
                <p class="mb-4 break-all font-mono text-xs text-slate-400">Manual entry key: {{ $pendingSecret }}</p>
                <div class="max-w-xs">
                    <label class="label">Authentication code</label>
                    <input type="text" wire:model="confirmationCode" inputmode="numeric" maxlength="6" class="input font-mono">
                    @error('confirmationCode') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="mt-4 flex gap-2">
                    <button wire:click="confirmTwoFactorSetup" class="btn-primary">Confirm &amp; enable</button>
                    <button wire:click="cancelTwoFactorSetup" class="btn-secondary">Cancel</button>
                </div>
            </div>
        @endif

        @if (auth()->user()->hasTwoFactorEnabled())
            <div class="mt-4 flex flex-wrap gap-2">
                <button wire:click="regenerateRecoveryCodes" class="btn-secondary">Regenerate recovery codes</button>
                <button wire:click="confirmDisableTwoFactor" class="btn-ghost text-rose-600">Disable two-factor</button>
            </div>
        @endif
    </div>

    {{-- Password --}}
    <div class="card max-w-2xl">
        <h3 class="mb-4 font-semibold text-slate-800 dark:text-slate-100">Change password</h3>
        <div class="space-y-4">
            <div>
                <label class="label">Current password</label>
                <input type="password" wire:model="currentPassword" class="input">
                @error('currentPassword') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">New password</label>
                <input type="password" wire:model="newPassword" class="input">
                @error('newPassword') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Confirm new password</label>
                <input type="password" wire:model="newPassword_confirmation" class="input">
            </div>
        </div>
        <button wire:click="changePassword" class="btn-primary mt-5">Update password</button>
    </div>

    {{-- Sessions --}}
    <div class="card max-w-2xl">
        <div class="mb-3 flex items-center justify-between">
            <h3 class="font-semibold text-slate-800 dark:text-slate-100">Active sessions</h3>
            <button wire:click="confirmSignOutOthers" class="btn-ghost text-sm text-rose-600">Sign out other sessions</button>
        </div>

        @if ($sessions->isEmpty())
            <p class="text-sm text-slate-500 dark:text-slate-400">Session details aren't available in this configuration.</p>
        @else
            <ul class="divide-y divide-surface-100 dark:divide-white/5">
                @foreach ($sessions as $s)
                    <li class="flex items-center justify-between py-3 text-sm">
                        <div>
                            <span class="text-slate-700 dark:text-slate-200">{{ $s->ip_address }}</span>
                            <span class="ml-2 text-slate-400">{{ $s->last_active->diffForHumans() }}</span>
                        </div>
                        @if ($s->is_current_device)
                            <span class="badge-info">This device</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    {{-- Recent login attempts --}}
    <div class="card max-w-2xl">
        <h3 class="mb-3 font-semibold text-slate-800 dark:text-slate-100">Recent sign-in activity</h3>

        @if ($loginAttempts->isEmpty())
            <x-empty-state icon="finger-print" title="No sign-in activity recorded yet" />
        @else
            <ul class="divide-y divide-surface-100 dark:divide-white/5">
                @foreach ($loginAttempts as $attempt)
                    <li class="flex items-center justify-between py-3 text-sm">
                        <div>
                            <span class="{{ $attempt->successful ? 'badge-success' : 'badge-danger' }}">
                                {{ $attempt->successful ? 'Success' : 'Failed' }}
                            </span>
                            <span class="ml-2 font-mono text-slate-500 dark:text-slate-400">{{ $attempt->ip_address }}</span>
                        </div>
                        <span class="text-slate-400">{{ $attempt->created_at->diffForHumans() }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    {{-- Blocked IPs --}}
    <div class="card max-w-2xl">
        <h3 class="mb-1 font-semibold text-slate-800 dark:text-slate-100">Blocked IP addresses</h3>
        <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">Requests to your websites from these addresses will be refused.</p>

        <div class="mb-4 flex flex-wrap items-end gap-2">
            <div>
                <label class="label">IP address</label>
                <input type="text" wire:model="blockIp" placeholder="203.0.113.42" class="input font-mono text-sm">
                @error('blockIp') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div class="flex-1">
                <label class="label">Reason (optional)</label>
                <input type="text" wire:model="blockReason" class="input">
            </div>
            <button wire:click="addBlockedIp" class="btn-secondary">Block</button>
        </div>

        @if ($blockedIps->isEmpty())
            <p class="text-sm text-slate-400">No IP addresses are currently blocked.</p>
        @else
            <ul class="divide-y divide-surface-100 dark:divide-white/5">
                @foreach ($blockedIps as $blocked)
                    <li class="flex items-center justify-between py-2.5 text-sm">
                        <div>
                            <span class="font-mono text-slate-700 dark:text-slate-200">{{ $blocked->ip_address }}</span>
                            @if ($blocked->reason)
                                <span class="ml-2 text-slate-400">{{ $blocked->reason }}</span>
                            @endif
                        </div>
                        <button wire:click="removeBlockedIp({{ $blocked->id }})" class="btn-ghost !py-1 !px-2 text-xs text-rose-600">Remove</button>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    {{-- Recovery codes modal --}}
    <x-modal :show="$showRecoveryCodes" max-width="lg" wire:click.outside="closeRecoveryCodes">
        <h3 class="mb-2 font-semibold text-slate-800 dark:text-slate-100">Save your recovery codes</h3>
        <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">
            Each code can be used once to sign in if you lose access to your authenticator app. Store them somewhere safe — they won't be shown again.
        </p>
        <div class="grid grid-cols-2 gap-2 rounded-lg bg-surface-50 p-4 font-mono text-sm dark:bg-white/5">
            @foreach ($freshRecoveryCodes as $code)
                <span>{{ $code }}</span>
            @endforeach
        </div>
        <button wire:click="closeRecoveryCodes" class="btn-primary mt-5">I've saved these codes</button>
    </x-modal>

    {{-- Disable 2FA confirmation --}}
    <x-modal :show="$confirmingDisable" wire:click.outside="$set('confirmingDisable', false)">
        <h3 class="mb-2 font-semibold text-slate-800 dark:text-slate-100">Disable two-factor authentication?</h3>
        <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">Confirm your password to turn off two-factor authentication.</p>
        <input type="password" wire:model="disableConfirmPassword" placeholder="Current password" class="input">
        @error('disableConfirmPassword') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        <div class="mt-4 flex justify-end gap-2">
            <button wire:click="$set('confirmingDisable', false)" class="btn-secondary">Cancel</button>
            <button wire:click="disableTwoFactor" class="btn-danger">Disable</button>
        </div>
    </x-modal>

    {{-- Sign out other sessions confirmation --}}
    <x-modal :show="$confirmingSignOutOthers" wire:click.outside="$set('confirmingSignOutOthers', false)">
        <h3 class="mb-2 font-semibold text-slate-800 dark:text-slate-100">Sign out other sessions?</h3>
        <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">Confirm your password to sign out of every other browser and device.</p>
        <input type="password" wire:model="signOutPassword" placeholder="Current password" class="input">
        @error('signOutPassword') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        <div class="mt-4 flex justify-end gap-2">
            <button wire:click="$set('confirmingSignOutOthers', false)" class="btn-secondary">Cancel</button>
            <button wire:click="signOutOtherSessions" class="btn-danger">Sign out others</button>
        </div>
    </x-modal>
</div>

<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
class UsersManager extends Component
{
    use WithPagination;

    public string $search = '';

    public string $roleFilter = '';

    public string $statusFilter = '';

    public ?int $suspendTargetId = null;

    public string $suspensionReason = '';

    public ?int $deleteTargetId = null;

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function confirmSuspend(int $userId): void
    {
        $this->suspendTargetId = $userId;
        $this->suspensionReason = '';
    }

    public function suspend(): void
    {
        $target = User::findOrFail($this->suspendTargetId);
        $this->authorize('suspend', $target);

        $target->update([
            'is_suspended' => true,
            'suspension_reason' => $this->suspensionReason ?: null,
            'suspended_at' => now(),
            'suspended_by' => Auth::id(),
        ]);

        $this->suspendTargetId = null;
        $this->dispatch('toast', message: "{$target->name} has been suspended.", level: 'success');
    }

    public function unsuspend(int $userId): void
    {
        $target = User::findOrFail($userId);
        $this->authorize('suspend', $target);

        $target->update([
            'is_suspended' => false,
            'suspension_reason' => null,
            'suspended_at' => null,
            'suspended_by' => null,
        ]);

        $this->dispatch('toast', message: "{$target->name} has been reinstated.", level: 'success');
    }

    public function confirmDelete(int $userId): void
    {
        $this->deleteTargetId = $userId;
    }

    public function delete(): void
    {
        $target = User::findOrFail($this->deleteTargetId);
        $this->authorize('delete', $target);

        $target->delete();
        $this->deleteTargetId = null;
        $this->dispatch('toast', message: 'Account deleted.', level: 'success');
    }

    public function render()
    {
        $query = User::query()->withCount('sites');

        if ($this->search !== '') {
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%"));
        }

        if ($this->roleFilter !== '') {
            $query->whereHas('roles', fn ($q) => $q->where('name', $this->roleFilter));
        }

        if ($this->statusFilter === 'suspended') {
            $query->where('is_suspended', true);
        } elseif ($this->statusFilter === 'active') {
            $query->where('is_suspended', false);
        }

        return view('livewire.admin.users-manager', [
            'users' => $query->latest()->paginate(20),
            'roles' => Role::orderBy('name')->pluck('name'),
        ])->title('Users · Admin · Portway');
    }
}

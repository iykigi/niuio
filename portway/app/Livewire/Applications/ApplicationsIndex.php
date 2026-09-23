<?php

namespace App\Livewire\Applications;

use App\Enums\ReleaseStatus;
use App\Models\Application;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Applications')]
class ApplicationsIndex extends Component
{
    use WithPagination;

    public bool $showCreate = false;

    public string $name = '';

    public string $tagline = '';

    public string $description = '';

    public string $website_url = '';

    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Application::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->authorize('create', Application::class);
        $this->reset(['name', 'tagline', 'description', 'website_url']);
        $this->resetValidation();
        $this->showCreate = true;
    }

    public function create(): void
    {
        $this->authorize('create', Application::class);

        $data = $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'tagline' => ['nullable', 'string', 'max:140'],
            'description' => ['nullable', 'string', 'max:5000'],
            'website_url' => ['nullable', 'url', 'max:255'],
        ]);

        $max = (int) config('portway.releases.max_applications_per_user', 10);

        if ($max > 0 && auth()->user()->applications()->count() >= $max) {
            $this->addError('name', "You have reached the limit of {$max} applications on this account.");

            return;
        }

        $application = new Application($data);
        $application->user_id = auth()->id();
        $application->slug = $this->uniqueSlug($data['name']);
        $application->save();

        $this->showCreate = false;
        $this->dispatch('toast', message: 'Application created. Upload your first build to publish it.', level: 'success');

        $this->redirectRoute('applications.show', ['application' => $application->slug], navigate: true);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'app';
        $slug = $base;
        $suffix = 2;

        while (Application::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    public function render()
    {
        $applications = auth()->user()->applications()
            ->when($this->search !== '', fn ($query) => $query->where('name', 'like', '%'.$this->search.'%'))
            ->withCount([
                'releases as live_releases_count' => fn ($query) => $query->where('status', ReleaseStatus::Active),
            ])
            ->latest()
            ->paginate(12);

        return view('livewire.applications.applications-index', [
            'applications' => $applications,
        ]);
    }
}

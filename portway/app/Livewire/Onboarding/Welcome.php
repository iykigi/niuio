<?php

namespace App\Livewire\Onboarding;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Welcome extends Component
{
    public int $step = 1;

    public function mount(): void
    {
        // Onboarding is a one-time welcome tour, not a gate — a returning
        // user who already has a website is sent straight to the dashboard.
        if (Auth::user()->sites()->exists()) {
            $this->redirectRoute('dashboard', navigate: true);
        }
    }

    public function next(): void
    {
        $this->step = min(3, $this->step + 1);
    }

    public function back(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function finish(): void
    {
        $this->redirectRoute('sites.create', navigate: true);
    }

    public function skip(): void
    {
        $this->redirectRoute('dashboard', navigate: true);
    }

    public function render()
    {
        return view('livewire.onboarding.welcome')->title('Welcome · Portway');
    }
}

@props(['show' => false, 'maxWidth' => 'md'])

@php
    $widths = ['sm' => 'max-w-sm', 'md' => 'max-w-md', 'lg' => 'max-w-lg', 'xl' => 'max-w-xl', '2xl' => 'max-w-2xl'];
@endphp

{{-- Usage: <x-modal :show="$showModal"> ... </x-modal> from a Livewire
     component with a public $showModal boolean. Because Livewire
     re-renders on state change, no Alpine binding is needed for the
     open/closed state itself — only for entrance animation. A button
     inside typically does wire:click="$set('showModal', false)". --}}
@if ($show)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data x-transition.opacity>
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" {{ $attributes->whereStartsWith('wire:click') }}></div>

        <div
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            class="relative w-full {{ $widths[$maxWidth] }} rounded-xl2 bg-white p-6 shadow-lift dark:bg-surface-900"
        >
            {{ $slot }}
        </div>
    </div>
@endif

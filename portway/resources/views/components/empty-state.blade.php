@props(['icon' => 'inbox', 'title', 'description' => null])

<div class="flex flex-col items-center justify-center rounded-xl2 border border-dashed border-surface-200 py-12 text-center dark:border-white/10">
    <span class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-surface-100 text-slate-400 dark:bg-white/5 dark:text-slate-500">
        <x-dynamic-component :component="'heroicon-o-'.$icon" class="h-6 w-6" />
    </span>
    <p class="font-medium text-slate-700 dark:text-slate-200">{{ $title }}</p>
    @if ($description)
        <p class="mt-1 max-w-sm text-sm text-slate-500 dark:text-slate-400">{{ $description }}</p>
    @endif
    @isset($action)
        <div class="mt-4">{{ $action }}</div>
    @endisset
</div>

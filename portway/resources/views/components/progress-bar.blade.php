@props(['percent' => 0, 'warn' => 80, 'danger' => 95])

@php
    $percent = max(0, min(100, (float) $percent));
    $barClass = $percent >= $danger ? 'bg-rose-500' : ($percent >= $warn ? 'bg-amber-500' : 'bg-harbor-500');
@endphp

<div {{ $attributes->class(['h-2 w-full overflow-hidden rounded-full bg-surface-200 dark:bg-white/10']) }}>
    <div class="h-full rounded-full {{ $barClass }} transition-all duration-500" style="width: {{ $percent }}%"></div>
</div>

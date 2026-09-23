<div
    x-data="{ toasts: [] }"
    @toast.window="toasts.push({ id: Date.now(), message: $event.detail.message, level: $event.detail.level || 'info' }); setTimeout(() => toasts.shift(), 5000)"
    class="pointer-events-none fixed bottom-4 right-4 z-50 flex w-80 flex-col gap-2"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="true"
            x-transition
            class="pointer-events-auto rounded-lg border p-3 text-sm shadow-lift"
            :class="{
                'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300': toast.level === 'success',
                'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300': toast.level === 'warning',
                'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300': toast.level === 'danger',
                'border-surface-200 bg-white text-slate-700 dark:border-white/10 dark:bg-surface-800 dark:text-slate-200': toast.level === 'info',
            }"
            x-text="toast.message"
        ></div>
    </template>
</div>

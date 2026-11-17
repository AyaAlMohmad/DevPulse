<script setup>
defineProps({
    title: { type: String, required: true },
    description: { type: String, required: true },
});
</script>

<template>
    <main class="flex min-h-screen items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <div class="mb-8 text-center">
                <span class="text-2xl font-bold tracking-tight text-emerald-400">DevPulse</span>
                <p class="mt-2 text-sm text-slate-400">A clearer view of your API's health.</p>
            </div>
            <section class="rounded-2xl border border-slate-800 bg-slate-900 p-6 shadow-xl sm:p-8">
                <h1 class="text-2xl font-semibold">{{ title }}</h1>
                <p class="mt-2 mb-8 text-sm text-slate-400">{{ description }}</p>
                <slot />
            </section>
        </div>
    </main>
</template>

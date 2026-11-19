<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import Preferences from '../Components/Preferences.vue';

defineProps({
    title: { type: String, required: true },
    description: { type: String, required: true },
});
const page = usePage();
</script>

<template>
    <div class="min-h-screen">
        <header class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-6 py-6">
            <Link :href="page.props.urls.home" class="text-xl font-bold text-emerald-400">DevPulse.</Link>
            <Preferences />
        </header>
        <main class="flex items-center justify-center px-4 py-10 sm:py-16">
            <div class="w-full max-w-md">
                <div class="mb-8 text-center">
                    <p class="text-sm text-slate-400">{{ $t("A clearer view of your API's health.") }}</p>
                </div>
                <section class="rounded-2xl border border-slate-800 bg-slate-900 p-6 shadow-xl shadow-slate-950/10 sm:p-8">
                    <h1 class="text-2xl font-semibold">{{ title }}</h1>
                    <p class="mt-2 mb-8 text-sm text-slate-400">{{ description }}</p>
                    <slot />
                </section>
            </div>
        </main>
    </div>
</template>

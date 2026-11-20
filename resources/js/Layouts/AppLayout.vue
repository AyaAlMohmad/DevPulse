<script setup>
import { onUnmounted, watch } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import Preferences from '../Components/Preferences.vue';

const page = usePage();
const logoutForm = useForm({});

let pollTimer = null;
watch(() => page.props.pending_checks, (pending) => {
    clearTimeout(pollTimer);
    pollTimer = pending ? setTimeout(() => router.reload({ preserveScroll: true }), 2000) : null;
}, { immediate: true });
onUnmounted(() => clearTimeout(pollTimer));
</script>

<template>
    <div class="min-h-screen">
        <header class="border-b border-slate-800 bg-slate-900">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-6 py-5">
                <Link :href="page.props.urls.home" class="text-xl font-bold tracking-tight text-emerald-400">DevPulse<span class="text-slate-400">.</span></Link>
                <div class="flex flex-wrap items-center gap-4">
                    <Preferences />
                    <form class="flex flex-wrap items-center gap-4" @submit.prevent="logoutForm.post(page.props.urls.logout)">
                        <span dir="auto" class="max-w-48 truncate text-sm text-slate-300">{{ page.props.auth.user.name }}</span>
                        <button :disabled="logoutForm.processing" class="rounded-lg border border-slate-700 px-4 py-2 text-sm hover:bg-slate-800 disabled:opacity-50">{{ $t('Sign out') }}</button>
                    </form>
                </div>
            </div>
        </header>
        <main class="mx-auto max-w-7xl px-6 py-10">
            <p v-if="page.props.flash.success" role="status" class="mb-6 rounded-xl border border-emerald-800 bg-emerald-950 px-4 py-3 text-sm text-emerald-200">{{ page.props.flash.success }}</p>
            <slot />
        </main>
        <div v-if="page.props.pending_checks" role="status" aria-live="polite" class="fixed inset-x-0 bottom-6 z-50 mx-auto flex w-fit items-center gap-3 rounded-full border border-sky-400/40 bg-slate-900 px-5 py-3 shadow-lg shadow-slate-950/50">
            <span class="inline-block size-4 animate-spin rounded-full border-2 border-sky-300 border-t-transparent" aria-hidden="true"></span>
            <span class="text-sm text-sky-200">{{ $t('Running checks… results refresh automatically.') }}</span>
        </div>
    </div>
</template>
